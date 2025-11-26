<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use App\Models\Post;
use App\Models\Cadre;
use App\Models\Candidate;

use Carbon\Carbon;

class AllocationController extends Controller
{
    // In-memory caches for speed
    private array $cadresByCode = [];
    private array $cadresByAbbr = [];
    private array $postsByCode = []; // snapshot for this iteration

    public function runAllocation()
    {
        $iterationContinue = true;
        $loopCount = 1;

        while ($iterationContinue) {

            $numOfAssignedCandidates = Candidate::whereNotNull('assigned_cadre')->count();

            echo "= Candidate count at the starting phase of the iteration: " . $numOfAssignedCandidates . "<br><br>";
            echo "= Starting Iteration: " . $loopCount . "<br><br>";

            $queues = $this->generateAllocationQueues();

            $sortedQueues = $this->sortAllocationQueues($queues);

            $allocationResult = $this->allocateCadre($sortedQueues);

            $solvedResult = $this->solveMultipleAllocations($allocationResult);

            $updated = $this->updateSolvedResultToDatabase($solvedResult);

            echo "= Iteration " . $loopCount . " done successfully...<br><br>";

            $numOfAssignedCandidatesAfterIteration = Candidate::whereNotNull('assigned_cadre')->count();

            echo "= Candidate count after the ending phase of the iteration: " . $numOfAssignedCandidatesAfterIteration . "<br><br>";

            // Stop if nothing changed for a few iterations (safety). You can adjust loopCount threshold.
            if ($numOfAssignedCandidates == $numOfAssignedCandidatesAfterIteration && $loopCount > 5) {
                $iterationContinue = false;
            }

            $loopCount++;

            // reload posts cache (because DB mutations changed post counts)
            $this->loadCadresAndPosts();

        }

    }

    // Load Cadres and Posts once into memory maps
    private function loadCadresAndPosts(): void
    {
        $this->cadresByCode = Cadre::all()->keyBy('cadre_code')->toArray();

        $this->cadresByAbbr = [];
        foreach ($this->cadresByCode as $code => $row) {
            $this->cadresByAbbr[$row['cadre_abbr']] = $row;
        }

        // Snapshot of posts (use *_post_left fields)
        $this->postsByCode = Post::all()->mapWithKeys(function ($p) {
            return [$p->cadre_code => [
                'cadre_code' => $p->cadre_code,
                'total_post_left' => intval($p->total_post_left),
                'mq_post_left' => intval($p->mq_post_left),
                'cff_post_left' => intval($p->cff_post_left),
                'em_post_left' => intval($p->em_post_left),
                'phc_post_left' => intval($p->phc_post_left),
                'allocated_post_count' => intval($p->allocated_post_count),
            ]];
        })->toArray();
    }

    /**
     * Build per-cadre queues.
     * Important: do NOT block GG/TT by candidate cadre_category here.
     * Each queue entry contains choice_index so we can pick highest-priority later.
     */
    private function generateAllocationQueues()
    {
        $queues = [];

        $candidates = Candidate::orderBy('general_merit_position', 'ASC')->get();

        foreach ($candidates as $candidate) {

            // Only consider candidates not already finalised
            if ($candidate->allocation_status === 'final') {
                continue;
            }

            $choices = $this->parse_choices_list($candidate->choice_list);

            if (empty($choices)) continue;

            $technicalPassedInfo = json_decode($candidate->technical_passed_cadres, true) ?? [];

            foreach ($choices as $choiceIndex => $choiceAbbr)
            {

                $cadre_code = $this->get_cadre_code_by_abbr($choiceAbbr);

                if ($cadre_code === null) continue;

                $cadre_type = $this->get_cadre_type_by_code($cadre_code);
                $cadre_abbr = $this->get_cadre_abbr_by_code($cadre_code);

                // If this is a technical cadre ensure candidate has passed that subject
                if ($cadre_type === 'TT') {
                    if (!is_array($technicalPassedInfo) || !in_array($cadre_abbr, array_keys($technicalPassedInfo))) {
                        continue;
                    }
                }

                // Check open seats using posts snapshot
                $postSnapshot = $this->postsByCode[$cadre_code] ?? null;

                if ($postSnapshot === null) continue;

                if ($postSnapshot['total_post_left'] <= 0) {
                    // no seats left at all
                    continue;
                }

                $rawChoiceList = $this->parse_choices_list( $candidate->choice_list );

                $currentIterationCadreIndex = array_search( $cadre_abbr, $rawChoiceList );

                $existingCadreIndex = array_search( $this->get_cadre_abbr_by_code( $candidate->assigned_cadre ), $rawChoiceList );

                $alreadyAssigned = $candidate->assigned_cadre ?? null;

                if( $alreadyAssigned !== NULL && $alreadyAssigned == $cadre_code ) {
                    continue; // skip this cadre; candidate already holds it
                }

                if( $existingCadreIndex && $existingCadreIndex < $currentIterationCadreIndex  ){
                    continue;
                }

                // Add to this cadre queue with candidate info & choice_index
                $queues[$cadre_code][] = [
                    'reg' => $candidate->reg,
                    'general_merit_position' => ($candidate->general_merit_position == 0 || $candidate->general_merit_position == null) ? 99999 : intval($candidate->general_merit_position),
                    'technical_merit_position' => ($candidate->technical_merit_position == 0 || $candidate->technical_merit_position == null) ? 99999 : intval($candidate->technical_merit_position),
                    'candidate' => $candidate,
                    'choice_index' => intval($choiceIndex),
                ];
            }
        }

        return $queues;
    }

    /**
     * Sort per-cadre queues by appropriate merit
     * Returns arrays (not Collections) for easier downstream use.
     */
    private function sortAllocationQueues($queues)
    {
        $sortedQueues = [];

        foreach ($queues as $cadreCode => $queue) {
            $cadreCodeType = $this->get_cadre_code_type($cadreCode);

            $queueAsCollection = collect($queue);
            if ($cadreCodeType == 'GG') {
                $sorted = $queueAsCollection->sortBy('general_merit_position')->values()->all();
            } else {
                $sorted = $queueAsCollection->sortBy('technical_merit_position')->values()->all();
            }

            $sortedQueues[$cadreCode] = $sorted;
        }

        return $sortedQueues;
    }

    /**
     * Allocate seats per cadre based on sorted queues.
     * Uses the in-memory posts snapshot ($this->postsByCode) and decrements that snapshot as we allocate.
     */
    private function allocateCadre($sortedQueues)
    {
        $allocationResult = [];

        foreach ($sortedQueues as $cadreCode => $queue) {
            // Use snapshot values
            $postsSnapshot = $this->postsByCode[$cadreCode] ?? null;

            if ($postsSnapshot === null) continue;

            $mq_posts_left = max(0, $postsSnapshot['mq_post_left']);
            $cff_posts_left = max(0, $postsSnapshot['cff_post_left']);
            $em_posts_left = max(0, $postsSnapshot['em_post_left']);
            $phc_posts_left = max(0, $postsSnapshot['phc_post_left']);
            $total_post_left = max(0, $postsSnapshot['total_post_left']);
            $allocated_post = max(0, $postsSnapshot['allocated_post_count']);

            if ($total_post_left <= 0) continue;
            if (count($queue) <= 0) continue;

            foreach ($queue as $entry) {

                if ($total_post_left <= 0) break; // no more seats in this cadre

                $candidateObj = $entry['candidate'];
                $reg = $candidateObj->reg;

                // Decide seat type: prefer MQ then quotas
                $allocated_quota = null;

                if ($mq_posts_left > 0) {
                    $allocated_quota = 'MQ';
                } else {
                    if ($candidateObj->has_quota == 1) {
                        $quotaInfo = json_decode($candidateObj->quota_info, true);
                        if (!is_array($quotaInfo)) $quotaInfo = [];

                        $postsLeft = [
                            'CFF' => $cff_posts_left,
                            'EM' => $em_posts_left,
                            'PHC' => $phc_posts_left,
                        ];

                        foreach (['CFF', 'EM', 'PHC'] as $key) {
                            $hasQuota = isset($quotaInfo[$key]) && (bool)$quotaInfo[$key];
                            if ($hasQuota && ($postsLeft[$key] > 0)) {
                                $allocated_quota = $key;
                                break;
                            }
                        }
                    }
                }

                if ($allocated_quota === null) {
                    // Cannot allocate any seat here for this candidate (no quota & MQ exhausted)
                    continue;
                }

                // Commit allocation into allocationResult and update local snapshot counters
                $allocationResult[$cadreCode][] = [
                    'reg' => $reg,
                    'candidate' => $candidateObj,
                    'allocation_type' => $allocated_quota,
                    'choice_index' => $entry['choice_index'] ?? PHP_INT_MAX,
                ];

                // decrement snapshot counters
                $total_post_left--;
                $allocated_post++;
                if ($allocated_quota === 'MQ') {
                    $mq_posts_left--;
                } elseif ($allocated_quota === 'CFF') {
                    $cff_posts_left--;
                } elseif ($allocated_quota === 'EM') {
                    $em_posts_left--;
                } elseif ($allocated_quota === 'PHC') {
                    $phc_posts_left--;
                }
            }

            // Save back snapshot changes for this cadre so other logic reads updated values
            $this->postsByCode[$cadreCode]['total_post_left'] = $total_post_left;
            $this->postsByCode[$cadreCode]['mq_post_left'] = $mq_posts_left;
            $this->postsByCode[$cadreCode]['cff_post_left'] = $cff_posts_left;
            $this->postsByCode[$cadreCode]['em_post_left'] = $em_posts_left;
            $this->postsByCode[$cadreCode]['phc_post_left'] = $phc_posts_left;
            $this->postsByCode[$cadreCode]['allocated_post_count'] = $allocated_post;
        }

        return $allocationResult;
    }

    /**
     * Solve multiple allocations: choose the allocation with lowest choice_index (highest preference).
     * Also build waiting list of still-open higher choices.
     */
    private function solveMultipleAllocations($allocationResult)
    {
        $solvedResult = [];
        $allocMap = []; // reg => list of allocations (cadre, choice_index, allocation_type, candidate)

        // Build allocations per candidate (using reg as key)
        foreach ($allocationResult as $cadreCode => $allocList) {
            foreach ($allocList as $entry) {
                
                $reg = $entry['reg'];

                if (!isset($allocMap[$reg])) $allocMap[$reg] = [];

                $allocMap[$reg][] = [
                    'cadre' => $cadreCode,
                    'choice_index' => $entry['choice_index'] ?? PHP_INT_MAX,
                    'allocation_type' => $entry['allocation_type'],
                    'candidate' => $entry['candidate'],
                ];

            }
        }

        // For each candidate decide best allocation
        foreach ($allocMap as $reg => $allocs) {

            // sort by choice_index ascending (lower = higher priority)
            usort($allocs, function ($a, $b) {
                return $a['choice_index'] <=> $b['choice_index'];
            });

            $best = $allocs[0];
            $bestCadre = $best['cadre'];
            $bestChoiceIndex = $best['choice_index'];
            $candidate = $best['candidate'];

            // Build waiting list: check the candidate's choice list left of the bestChoiceIndex
            $rawChoiceList = $this->parse_choices_list($candidate->choice_list);
            $leftChoices = [];
            if ($bestChoiceIndex > 0 && count($rawChoiceList) > 0) {
                $leftChoices = array_slice($rawChoiceList, 0, $bestChoiceIndex);
            }

            $waiting = [];

            $choiceListArray = $this->parse_choices_list( $best['candidate']->choice_list );  

            $bestIndex = $bestChoiceIndex;

            foreach ($choiceListArray as $ci => $cad) {
                if ($ci < $bestIndex) {        // These are higher priority
                    $waiting[] = $cad;
                }
            }

            /*
            if (!empty($leftChoices)) {
                // Determine open higher choices that the candidate is eligible for
                $all_open_post_codes = $this->get_all_open_posts_codes_collection();

                $technicalPassedInfo = json_decode($candidate->technical_passed_cadres, true) ?? [];

                foreach ($leftChoices as $cadreAbbr) {
                    $cadreCode = $this->get_cadre_code_by_abbr($cadreAbbr);
                    if ($cadreCode === null) continue;

                    // must be open (have posts left) and candidate must be eligible (if TT require passed)
                    $isTech = $this->get_cadre_type_by_code($cadreCode) === 'TT';
                    $passed = true;
                    if ($isTech) {
                        $passed = is_array($technicalPassedInfo) && in_array($cadreAbbr, array_keys($technicalPassedInfo));
                    }

                    $isOpen = $all_open_post_codes->contains($cadreCode);
                    if ($isOpen && $passed) {
                        $waiting[] = $cadreAbbr;
                    }
                }
            }*/
            

            // Decide status: if there are open higher choices waiting -> temporary, else final
            $status = (count($waiting) > 0) ? 'temporary' : 'final';

            if (!isset($solvedResult[$bestCadre])) $solvedResult[$bestCadre] = [];

            $solvedResult[$bestCadre][] = [
                'reg' => $reg,
                'candidate' => $candidate,
                'allocation_type' => $best['allocation_type'],
                'status' => $status,
                'waiting_cadres' => $waiting,
                'choice_index' => $bestChoiceIndex,
            ];

        }

        return $solvedResult;
    }

    /**
     * Persist solvedResult into DB.
     * Uses transactions and lockForUpdate on Post rows to avoid races.
     * Releases previous seat only if previousCadre is different from new one.
     */
    private function updateSolvedResultToDatabase($solvedResult)
    {
        DB::beginTransaction();

        try {
            foreach ($solvedResult as $cadreCode => $list) {
                foreach ($list as $cand) {
                    $reg = $cand['candidate']->reg;
                    $newCadre = $cadreCode;
                    $newStatus = $cand['status'];
                    $newAssignedStatus = $cand['allocation_type']; // MQ / CFF / EM / PHC

                    // Load candidate fresh
                    $candidateRow = Candidate::where('reg', $reg)->first();
                    $previousCadre = $candidateRow->assigned_cadre ?? null;
                    $previousAssignedStatus = $candidateRow->assigned_status ?? null;

                    // If previous exists and different, release previous seat back into DB and snapshot
                    if (!empty($previousCadre) && $previousCadre !== $newCadre) {
                        // Use lockForUpdate to safely modify the post row
                        $prevPost = Post::where('cadre_code', $previousCadre)->lockForUpdate()->first();
                        if ($prevPost) {
                            // increment the appropriate bucket and total_post_left, decrement allocated_post_count
                            $prevPost->increment('total_post_left');
                            if ($previousAssignedStatus === 'MQ') {
                                $prevPost->increment('mq_post_left');
                            } elseif ($previousAssignedStatus === 'CFF') {
                                $prevPost->increment('cff_post_left');
                            } elseif ($previousAssignedStatus === 'EM') {
                                $prevPost->increment('em_post_left');
                            } elseif ($previousAssignedStatus === 'PHC') {
                                $prevPost->increment('phc_post_left');
                            }
                            $prevPost->decrement('allocated_post_count');

                            // Also reflect in snapshot if present
                            if (isset($this->postsByCode[$previousCadre])) {
                                $this->postsByCode[$previousCadre]['total_post_left']++;
                                $this->postsByCode[$previousCadre]['allocated_post_count'] = max(0, $this->postsByCode[$previousCadre]['allocated_post_count'] - 1);
                                if ($previousAssignedStatus === 'MQ') $this->postsByCode[$previousCadre]['mq_post_left']++;
                                if ($previousAssignedStatus === 'CFF') $this->postsByCode[$previousCadre]['cff_post_left']++;
                                if ($previousAssignedStatus === 'EM') $this->postsByCode[$previousCadre]['em_post_left']++;
                                if ($previousAssignedStatus === 'PHC') $this->postsByCode[$previousCadre]['phc_post_left']++;
                            }
                        }
                    }

                    // Now assign the new cadre (only if candidate not previously assigned to same cadre with same status)
                    Candidate::where('reg', $reg)->update([
                        'assigned_cadre' => $newCadre,
                        'assigned_status' => $newAssignedStatus,
                        'allocation_status' => $newStatus,
                        'higher_choices' => implode(" ", $cand['waiting_cadres']),
                    ]);

                    // Update the Post row for the new cadre (decrement appropriate bucket)
                    $postRow = Post::where('cadre_code', $newCadre)->lockForUpdate()->first();
                    if ($postRow) {
                        // decrement totals safely only if there is at least one available (defensive)
                        if ($postRow->total_post_left > 0) {
                            $postRow->decrement('total_post_left');
                        } else {
                            // If DB snapshot inconsistent, allow negative to indicate problem (or skip)
                            $postRow->decrement('total_post_left');
                        }

                        if ($newAssignedStatus === 'MQ') {
                            $postRow->decrement('mq_post_left');
                        } elseif ($newAssignedStatus === 'CFF') {
                            $postRow->decrement('cff_post_left');
                        } elseif ($newAssignedStatus === 'EM') {
                            $postRow->decrement('em_post_left');
                        } elseif ($newAssignedStatus === 'PHC') {
                            $postRow->decrement('phc_post_left');
                        }

                        $postRow->increment('allocated_post_count');
                    }

                    // Update snapshot too (to keep in-memory consistent)
                    if (!isset($this->postsByCode[$newCadre])) {
                        // if not in snapshot, refresh it
                        $p = Post::where('cadre_code', $newCadre)->first();
                        if ($p) {
                            $this->postsByCode[$newCadre] = [
                                'cadre_code' => $p->cadre_code,
                                'total_post_left' => intval($p->total_post_left),
                                'mq_post_left' => intval($p->mq_post_left),
                                'cff_post_left' => intval($p->cff_post_left),
                                'em_post_left' => intval($p->em_post_left),
                                'phc_post_left' => intval($p->phc_post_left),
                                'allocated_post_count' => intval($p->allocated_post_count),
                            ];
                        }
                    } else {
                        // adjust snapshot according to the new assignment
                        if ($this->postsByCode[$newCadre]['total_post_left'] > 0) {
                            $this->postsByCode[$newCadre]['total_post_left']--;
                        } else {
                            $this->postsByCode[$newCadre]['total_post_left']--;
                        }
                        $this->postsByCode[$newCadre]['allocated_post_count']++;

                        if ($newAssignedStatus === 'MQ') $this->postsByCode[$newCadre]['mq_post_left']--;
                        if ($newAssignedStatus === 'CFF') $this->postsByCode[$newCadre]['cff_post_left']--;
                        if ($newAssignedStatus === 'EM') $this->postsByCode[$newCadre]['em_post_left']--;
                        if ($newAssignedStatus === 'PHC') $this->postsByCode[$newCadre]['phc_post_left']--;
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            // log error; rethrow or return false depending on your needs
            echo "Error updating DB: " . $e->getMessage() . "<br>";
            return false;
        }

    }

    // ---------------------------
    // Helper functions
    // ---------------------------

    private function is_iteration_index_lower_than_existing_allocation($existingCadreAbbr, $iterationCadreAbbr, $choices)
    {
        $existingIndex = array_search($existingCadreAbbr, $choices, true);
        $iterationIndex = array_search($iterationCadreAbbr, $choices, true);

        if ($existingIndex === false || $iterationIndex === false) {
            return false;
        }

        return $iterationIndex < $existingIndex;
    }

    /**
     * Prefer reading from snapshot if available to avoid repeated DB reads during an iteration.
     */
    private function get_remaining_posts_count($type, $cadreCode)
    {
        if (isset($this->postsByCode[$cadreCode])) {
            return intval($this->postsByCode[$cadreCode][$type] ?? 0);
        }

        $row = Post::where('cadre_code', $cadreCode)->first();
        if (!$row) return 0;
        return intval($row->$type ?? 0);
    }

    private function get_cadre_code_by_abbr($abbr)
    {
        return $this->cadresByAbbr[$abbr]['cadre_code'] ?? null;
    }

    private function get_cadre_type_by_code($code)
    {
        return $this->cadresByCode[$code]['cadre_type'] ?? null;
    }

    private function get_cadre_abbr_by_code($code)
    {
        return $this->cadresByCode[$code]['cadre_abbr'] ?? null;
    }

    private function check_if_open_for_allocation($code)
    {
        // Use snapshot (total_post_left) to determine if open
        if (isset($this->postsByCode[$code])) {
            return $this->postsByCode[$code]['total_post_left'];
        }

        $row = Post::where('cadre_code', $code)->first();
        return $row ? intval($row->total_post_left) : null;
    }

    /**
     * Return a collection of cadre_codes that currently have any posts left (snapshot).
     */
    private function get_all_open_posts_codes_collection()
    {
        // prefer snapshot values (fast)
        $codes = [];
        foreach ($this->postsByCode as $code => $vals) {
            if (($vals['total_post_left'] ?? 0) > 0) {
                $codes[] = $code;
            }
        }
        return collect($codes);
    }

    private function parse_choices_list(string $s)
    {
        if (trim($s) === '') return [];
        $parts = preg_split('/\s+/', trim($s));
        return array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== ''));
    }

    private function get_cadre_code_type($code)
    {
        return $this->cadresByCode[$code]['cadre_type'] ?? 'GG';
    }

    private function get_best_choice_abbr($foundIn, $rawChoiceList)
    {
        $bestPosition = null;
        $bestAbbr = null;

        foreach ($foundIn as $found) {
            $current_choice_abbr = $this->get_cadre_abbr_by_code($found);
            $position = array_search($current_choice_abbr, $rawChoiceList, true);
            if ($position === false) continue;
            if ($bestPosition === null || $position < $bestPosition) {
                $bestPosition = $position;
                $bestAbbr = $rawChoiceList[$bestPosition];
            }
        }

        return $bestAbbr;
    }

    private function get_best_choice_index($foundIn, $rawChoiceList)
    {
        $bestPosition = null;

        foreach ($foundIn as $found) {
            $current_choice_abbr = $this->get_cadre_abbr_by_code($found);
            $position = array_search($current_choice_abbr, $rawChoiceList, true);
            if ($position === false) continue;
            if ($bestPosition === null || $position < $bestPosition) {
                $bestPosition = $position;
            }
        }

        return $bestPosition; // null if not found
    }

    private function is_already_allocated($allocated, $reg)
    {
        foreach ($allocated as $cad => $list) {
            foreach ($list as $a) {
                if (($a['reg'] ?? ($a['reg_no'] ?? null)) === $reg) return true;
            }
        }
        return false;
    }

    private function is_already_allocated_in_this_cadre($allocated, $reg, $cadre)
    {
        foreach ($allocated as $cad => $list) {
            if ($cad == $cadre) {
                foreach ($list as $a) {
                    if (($a['reg'] ?? ($a['reg_no'] ?? null)) === $reg) return true;
                }
            }
        }
        return false;
    }
}
