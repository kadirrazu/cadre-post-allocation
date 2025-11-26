<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use App\Models\Post;
use App\Models\Cadre;
use App\Models\Candidate;

use Carbon\Carbon;

class AllocationController extends Controller
{
    
    // In-memory caches for speed
    private array $cadresByCode = [];
    private array $cadresByAbbr = [];
    private array $postsByCode = [];

    public function runAllocation()
    {
        
        $iterationContinue = true;

        $loopCount = 1;

        // Load static data once
        $this->loadCadresAndPosts();
        
        while( $iterationContinue )
        {

            $numOfAssignedCandidates = Candidate::where('assigned_cadre', '!=', NULL)->count();

            echo '= Candidate count at the starting phase of the iteration: ' . $numOfAssignedCandidates . '<br><br>';
        
            echo '= Starting Iteraton: '. $loopCount .' <br><br>';


            $queues = $this->generateAllocationQueues();

            
            $sortedQueues = $this->sortAllocationQueues( $queues );


            $allocationResult = $this->allocateCadre( $sortedQueues );


            $solvedResult = $this->solveMultipleAllocations( $allocationResult );


            $updated = $this->updateSolvedResultToDatabase( $solvedResult );

            echo '= Iteration '. $loopCount .' done successfully...<br><br>';

            $numOfAssignedCandidatesAfterIteration = Candidate::where('assigned_cadre', '!=', NULL)->count();

            echo '= Candidate count after the ending phase of the iteration: ' . $numOfAssignedCandidatesAfterIteration . '<br><br>';

            if( $numOfAssignedCandidates == $numOfAssignedCandidatesAfterIteration && $loopCount > 5){
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

        foreach ($this->cadresByCode as $code => $row)
        {
            $this->cadresByAbbr[$row['cadre_abbr']] = $row;
        }

        // use total_post_left and specific bucket fields (in-memory snapshot)
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

    } //End of loadCadresAndPosts()

    private function generateAllocationQueues()
    {
        $queues = [];

        $candidates = Candidate::orderBy('general_merit_position', 'ASC')->get();

        foreach( $candidates as $candidate )
        {
            if( $candidate->allocation_status !== 'final' )
            {
                $choices = $this->parse_choices_list( $candidate->choice_list );

                foreach( $choices as $choiceIndex => $choice )
                {
                    $cadre_code = $this->get_cadre_code_by_abbr( $choice );
                    $cadre_type = $this->get_cadre_type_by_code( $cadre_code );
                    $cadre_abbr = $this->get_cadre_abbr_by_code( $cadre_code );

                    $technicalPassedInfo = json_decode($candidate->technical_passed_cadres, true) ?? [];

                    //if tech candidate, check if he passed in the concerned cadre subject

                    //check if the current cadre is technical or not
                    if( $cadre_type == 'TT' )
                    {
                        
                        //if technical, then check his passed eligibility, otherwise, contine
                        if( !in_array( $cadre_abbr, array_keys($technicalPassedInfo) ) )
                        {
                            continue;
                        }
                    }

                    $currentlyAssigned = NULL;

                    //Check if already assigned or not; If assigned need to put in higher queue only
                    if( isset($candidate->assigned_cadre) && $candidate->assigned_cadre !== NULL )
                    {
                        $currentlyAssigned = true;

                        $existingCadreCode = $candidate->assigned_cadre;
                        $existingCadreAbbr = $this->get_cadre_abbr_by_code( $existingCadreCode );
                        $iterationCadreAbbr = $this->get_cadre_abbr_by_code( $choice );

                        if( $this->is_iteration_index_lower_than_existing_allocation($existingCadreAbbr, $iterationCadreAbbr, $choices) ){
                            continue;
                        }


                    }
                    
                    if( $cadre_code != null )
                    {
                        $open_for_allocation = $this->check_if_open_for_allocation( $cadre_code );

                        $candidateCadreCategory = $candidate->cadre_category;
                        $currentCadreCategory = $cadre_type;

                        /*
                        if( $candidateCadreCategory == 'TT' &&  $currentCadreCategory == 'GG' ){
                            continue;
                        }

                        if( $candidateCadreCategory == 'GG' &&  $currentCadreCategory == 'TT' ){
                            continue;
                        }*/

                        if( $open_for_allocation != null && $open_for_allocation > 0 )
                        {
                            
                            // Check whether this cadre has any posts left (use total_post_left)
                            $postSnapshot = $this->postsByCode[$cadre_code] ?? null;

                            if ($postSnapshot === null) continue;

                            if ($postSnapshot['total_post_left'] <= 0) {
                                // no remaining seats at all
                                continue;
                            }
                            
                            $queues[$cadre_code][] = [
                                'reg' => $candidate->reg,
                                'general_merit_position' => ( $candidate->general_merit_position == 0 || $candidate->general_merit_position == null) ? 99999 : intval($candidate->general_merit_position),
                                'technical_merit_position' => ( $candidate->technical_merit_position == 0 || $candidate->technical_merit_position == null) ? 99999 : intval($candidate->technical_merit_position),
                                'candidate' => $candidate,
                                'choice_index' => $choiceIndex,
                            ];
                        }
                        else
                        {
                            continue;
                        }
                    }

                }
            }
        }
        
        return $queues;
    }

    private function sortAllocationQueues( $queues )
    {
        $sortedQueues = [];

        foreach( $queues as $cadreCode => $queue )
        {
            $cadreCodeType = $this->get_cadre_code_type( $cadreCode );

            $queueAsCollection = collect( $queue );
            $sorted = $queueAsCollection;

            if( $cadreCodeType == 'GG' ){
                $sorted = $queueAsCollection->sortBy('general_merit_position');
            }
            else{
                $sorted = $queueAsCollection->sortBy('technical_merit_position');
            }

            $sortedQueues[$cadreCode] = $sorted;
        }

        return $sortedQueues;
    }

    private function allocateCadre( $sortedQueues )
    {
        $allocationResult = [];

        foreach( $sortedQueues as $cadreCode => $queue )
        {
            //Check post availabitlity for this $cadreCode
            $mq_posts_left = max(0, $this->get_remaining_posts_count('mq_post_left', $cadreCode));
            $cff_posts_left = max(0, $this->get_remaining_posts_count('cff_post_left', $cadreCode));
            $em_posts_left = max(0, $this->get_remaining_posts_count('em_post_left', $cadreCode));
            $phc_posts_left = max(0, $this->get_remaining_posts_count('phc_post_left', $cadreCode));

            $total_post = max(0, $this->get_remaining_posts_count('total_post_left', $cadreCode));
            $allocated_post = max(0, $this->get_remaining_posts_count('allocated_post_count', $cadreCode));

            $posts_left = max(0, $total_post);

            $allocationResult[$cadreCode] = [];

            if( $posts_left <= 0 )
            {
                continue; // no posts to fill for this cadre.
            }

            if( count($queue) <= 0 )
            {
                continue; // no candidates in the queue to allocate
            }

            foreach( $queue as $entry )
            {

                $reg = $entry['candidate']->reg;
                $candidate = $entry; //just keeping a raw copy, if found as reuire!

                // Decide seat: prefer MQ then quotas in order
                $allocated_quota = null;

                // try MQ first
                if( $mq_posts_left > 0 )
                {
                    $allocated_quota = 'MQ';
                } 
                else
                {
                    //check if the candidate has quota
                    if( $entry['candidate']->has_quota == 1 )
                    {
                        $quotaInfo = json_decode($entry['candidate']->quota_info, true);

                        if (!is_array($quotaInfo)) {
                            $quotaInfo = [];
                        }

                        // map posts-left by key for easy lookup
                        $postsLeft = [
                            'CFF' => $cff_posts_left ?? 0,
                            'EM'  => $em_posts_left  ?? 0,
                            'PHC' => $phc_posts_left ?? 0,
                        ];

                        // check in priority order; cast values to boolean to be robust
                        foreach (['CFF', 'EM', 'PHC'] as $key) {
                            // exists and truthy in the quota JSON?
                            $hasQuota = isset($quotaInfo[$key]) && (bool) $quotaInfo[$key];

                            if ($hasQuota && ($postsLeft[$key] > 0)) {
                                $allocated_quota = $key;
                                break; // stop at first valid allocation
                            }
                        }

                    }
                }

                if( $allocated_quota !== null )
                {
                    // Reduce available posts
                    if( $allocated_quota === 'MQ' ){
                        $mq_posts_left--;
                    }

                    if(  $allocated_quota === 'CFF' ){
                         $cff_posts_left--;
                    }

                    if(  $allocated_quota === 'EM' ){
                         $em_posts_left--;
                    }

                    if(  $allocated_quota === 'PHC' ){
                         $phc_posts_left--;
                    }

                    $allocated_post = intval($allocated_post) + 1;

                    // Allocate this candidate to this cadre
                    $allocationResult[$cadreCode][] = [
                        'reg'     => $entry['candidate']->reg,
                        'candidate'  => $entry['candidate'],
                        'allocation_type'      => $allocated_quota,
                        'choice_index'  => $entry['choice_index'] ?? null, // propagate index
                    ];

                }

            }

        }

        return $allocationResult;
    }

    private function solveMultipleAllocations( $allocationResult )
    {
        $solvedResult = [];
        $allocMap     = [];  // reg_no => list of allocations

        // 1) Build mapping reg_no => all allocations they appear in
        foreach ($allocationResult as $cadreCode => $allocList) {
            foreach ($allocList as $entry) {
                $reg = $entry['reg'];

                if (!isset($allocMap[$reg])) {
                    $allocMap[$reg] = [];
                }

                $allocMap[$reg][] = [
                    'cadre'         => $cadreCode,
                    'candidate'     => $entry['candidate'],
                    'choice_index'  => $entry['choice_index'] ?? PHP_INT_MAX,
                    'allocation_type' => $entry['allocation_type'],
                ];
            }
        }

        // 2) Solve for each candidate with multiple allocations
        foreach ($allocMap as $reg => $allocs) {

            // Sort allocations for this candidate by choice_index (ascending)
            usort($allocs, function ($a, $b) {
                return $a['choice_index'] <=> $b['choice_index'];
            });

            // The best allocation is the one with the lowest choice_index
            $best = $allocs[0];
            $bestCadre = $best['cadre'];
            $bestChoiceIndex = $best['choice_index'];
            $candidate = $best['candidate'];

            // Build waiting list: all cadres with higher choice_index (lower priority)
            $waiting = [];

            /*
            if (count($allocs) > 1) {
                foreach ($allocs as $item) {
                    if ($item['cadre'] !== $bestCadre) {
                        // These cadres were LOWER priority (higher choice_index)
                        $waiting[] = get_cadre_abbr_by_code( $item['cadre'] );
                    }
                }
            }
            */

            //check if higher cadre exists in post allocaiton table
            $all_open_posts_codes = $this->get_all_open_posts_abbr();


            $rawChoiceList = $this->parse_choices_list( $candidate['choice_list'] );

            $leftChoices = array_slice($rawChoiceList, 0, $bestChoiceIndex);

            if( count($leftChoices) > 0 )
            {
                foreach($leftChoices as $cadreAbbr)
                {
                    $is_tech = $this->get_cadre_type_by_code( $this->get_cadre_code_by_abbr($cadreAbbr) );

                    $passed = true;

                    $technicalPassedInfo = json_decode( $candidate->technical_passed_cadres, true ) ?? [];

                    if( $is_tech == 'TT' ){
                        $passed = in_array( $cadreAbbr, array_keys($technicalPassedInfo) );
                    }
                    
                    if( $all_open_posts_codes->contains('cadre_code', $this->get_cadre_code_by_abbr($cadreAbbr) )  && $passed )
                    {
                        $waiting[] = $cadreAbbr;
                    }
                }
            }

            $status = 'temporary';

            if( count($leftChoices) < 1 ){
                $status = 'final';
            }

            // Store into solvedResult
            if (!isset($solvedResult[$bestCadre])) {
                $solvedResult[$bestCadre] = [];
            }

            $solvedResult[$bestCadre][] = [
                'reg'               => $reg,
                'candidate'         => $candidate,
                'allocation_type'   => $best['allocation_type'],
                'status'            => $status,
                'waiting_cadres'    => $waiting,
                'choice_index'      => $bestChoiceIndex,
            ];
        }

        return $solvedResult;
    }


    private function updateSolvedResultToDatabase( $solvedResult )
    {
        foreach( $solvedResult as $cadreCode => $list )
        {
            foreach( $list as $cand )
            {
                $reg = $cand['candidate']->reg;

                //Release post if already in a lower cadre
                $alreadyExists = Candidate::where('reg', $reg)->where('assigned_cadre', '!=', NULL)->first();

                if( $alreadyExists ){
                    $previousCadre = $alreadyExists->assigned_cadre;
                    $cadreStatus = $alreadyExists->assigned_status;

                    if( $cadreStatus == 'MQ' ){
                         Post::where('cadre_code', $previousCadre)->increment('total_post_left');
                         Post::where('cadre_code', $previousCadre)->increment('mq_post_left');
                         Post::where('cadre_code', $previousCadre)->decrement('allocated_post_count');
                    }
                    else if( $cadreStatus == 'CFF' ){
                         Post::where('cadre_code', $previousCadre)->increment('total_post_left');
                         Post::where('cadre_code', $previousCadre)->increment('cff_post_left');
                         Post::where('cadre_code', $previousCadre)->decrement('allocated_post_count');
                    }
                    else if( $cadreStatus == 'EM' ){
                         Post::where('cadre_code', $previousCadre)->increment('total_post_left');
                         Post::where('cadre_code', $previousCadre)->increment('em_post_left');
                         Post::where('cadre_code', $previousCadre)->decrement('allocated_post_count');
                    }
                    else if( $cadreStatus == 'PHC' ){
                         Post::where('cadre_code', $previousCadre)->increment('total_post_left');
                         Post::where('cadre_code', $previousCadre)->increment('phc_post_left');
                         Post::where('cadre_code', $previousCadre)->decrement('allocated_post_count');
                    }
                }

                //Update Candidate
                Candidate::where('reg', $reg)->update([
                    'assigned_cadre' => $cadreCode,
                    'assigned_status' => $cand['allocation_type'],
                    'allocation_status' => $cand['status'],
                    'higher_choices' => implode(" ", $cand['waiting_cadres']),
                ]);

                $post_row = Post::where('cadre_code', $cadreCode)->first();

                //Update Post Availability
                Post::where('cadre_code', $cadreCode)->update([
                    'total_post_left' => intval($post_row->total_post_left - 1),
                    'mq_post_left' => ($cand['allocation_type'] == 'MQ') ? intval($post_row->mq_post_left - 1) : $post_row->mq_post_left,
                    'cff_post_left' => ($cand['allocation_type'] == 'CFF') ? intval($post_row->cff_post_left - 1) : $post_row->cff_post_left,
                    'em_post_left' => ($cand['allocation_type'] == 'EM') ? intval($post_row->em_post_left - 1) : $post_row->em_post_left,
                    'phc_post_left' => ($cand['allocation_type'] == 'PHC') ? intval($post_row->phc_post_left - 1) : $post_row->phc_post_left,
                    'allocated_post_count' => intval($post_row->allocated_post_count + 1),
                ]);
            }
        }
    }


    //Utilities : Helper Functions
    private function is_iteration_index_lower_than_existing_allocation($existingCadreAbbr, $iterationCadreAbbr, $choices)
    {
        $existingIndex = array_search($existingCadreAbbr, $choices, true);
        $iterationIndex = array_search($iterationCadreAbbr, $choices, true);

        if ($existingIndex === false || $iterationIndex === false) {
            // if either not found, choose a safe default (e.g., false)
            return false;
        }

        // true if iteration (current) is a LOWER index (i.e. higher priority) than existing
        return $iterationIndex < $existingIndex;
    }

    private function get_remaining_posts_count($type, $cadreCode)
    {
        $row = Post::where('cadre_code', $cadreCode)->first();
        if (!$row) return 0;
        return isset($row->$type) ? intval($row->$type) : 0;
    }

    private function get_cadre_code_by_abbr( $abbr )
    {
        //return Cadre::where('cadre_abbr', $abbr)->first()->cadre_code ?? null;
        return $this->cadresByAbbr[$abbr]['cadre_code'] ?? null;
    }

    private function get_cadre_type_by_code( $code )
    {
        //return Cadre::where('cadre_code', $code)->first()->cadre_type ?? null;
        return $this->cadresByCode[$code]['cadre_type'] ?? null;
    }

    private function get_cadre_abbr_by_code( $code )
    {
        //return Cadre::where('cadre_code', $code)->first()->cadre_abbr ?? null;
        return $this->cadresByCode[$code]['cadre_abbr'] ?? null;
    }

    private function check_if_open_for_allocation( $code )
    {
        return Post::where('cadre_code', $code)->first()->total_post_left ?? null;
    }

    private function get_all_open_posts_abbr()
    {
        return Post::select('cadre_code')->where('total_post', '>=', 0)->get() ?? null;
    }

    private function parse_choices_list(string $s)
    {
        if (trim($s) === '') return [];
        // split on one-or-more whitespace
        $parts = preg_split('/\s+/', trim($s));
        return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
    }

    private function get_cadre_code_type( $code )
    {
        //return Cadre::where('cadre_code', $code)->first()->cadre_type ?? 'GG';
        return $this->cadresByCode[$code]['cadre_type'] ?? 'GG';
    }

    //Get the best possible cadre's position or abbr
    private function get_best_choice_abbr($foundIn, $rawChoiceList)
    {
        $bestPosition = 99999;

        $bestAbbr = $rawChoiceList[0];

        foreach ($foundIn as $found) 
        {
            $current_choice_abbr = $this->get_cadre_abbr_by_code( $found );
            $position = array_search($current_choice_abbr, $rawChoiceList, true);

            if ($position === false) continue;

            if ($position < $bestPosition) {
                $bestPosition = $position;
                $bestAbbr = $rawChoiceList[$bestPosition];
            }
        }

        return $bestPosition === 99999 ? null : $bestAbbr;

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

    //Helper: to check if reg already allocated in $allocated for any cadre
    private function is_already_allocated($allocated, $reg) {
        foreach ($allocated as $cad => $list) {
            foreach ($list as $a) {
                if ($a['reg_no'] === $reg) return true;
            }
        }
        return false;
    }

    private function is_already_allocated_in_this_cadre($allocated, $reg, $cadre) {
        foreach ($allocated as $cad => $list) {
            if( $cad == $cadre ){
                foreach ($list as $a) {
                    if ($a['reg_no'] === $reg) return true;
                }
            }
        }
        return false;
    }

    //End of Helper Functions

} //End of the class
