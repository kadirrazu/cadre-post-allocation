<?php
/**
 * BPSC Cadre Allocation - Iterative Cadre-by-Cadre Implementation
 *
 * Usage: place this file in the same directory as:
 *   /mnt/data/candidates.php   -> $candidates
 *   /mnt/data/cadre-list.php   -> $cadre_lists
 *   /mnt/data/posts.php        -> $post_available
 *
 * Run: php bpsc_cadre_allocation_iter.php
 */

//require_once __DIR__ . '/candidates.php';   // provides $candidates
//require_once __DIR__ . '/cadre-list.php';   // provides $cadre_lists
//require_once __DIR__ . '/posts.php';        // provides $post_available

// --- Helpers ---
function parse_choices($choice_str) {
    $parts = preg_split('/\s+/', trim($choice_str));
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = $p;
    }
    return $out;
}

// Build map from cadre abbreviation -> code and technical set from cadre_lists
$abbr_to_code = [];
$code_to_abbr = [];
$technical_set = []; // abbr => true

foreach ($post_available as $code => $info) {
    $abbr = $info['cadre'];
    $abbr_to_code[$abbr] = $code;
    $code_to_abbr[$code] = $abbr;
}

// Try to build technical_set from $cadre_lists (supports a few common structures)
if (!empty($cadre_lists) && is_array($cadre_lists)) {
    if (!empty($cadre_lists['technical']) && is_array($cadre_lists['technical'])) {
        foreach ($cadre_lists['technical'] as $k => $v) {
            if (is_array($v) && !empty($v['abbr'])) $technical_set[$v['abbr']] = true;
            elseif (is_string($k)) $technical_set[$k] = true;
        }
    } else {
        foreach ($cadre_lists as $group) {
            if (!is_array($group)) continue;
            foreach ($group as $abbr => $meta) {
                if (!is_string($abbr)) continue;
                if (is_array($meta) && (!empty($meta['type']) && strtoupper($meta['type']) === 'TECH' || !empty($meta['code']) && is_numeric($meta['code']))) {
                    $technical_set[$abbr] = true;
                }
            }
        }
    }
}

// Fallback: infer technical cadres from candidates' technical_merit_position if still empty
if (empty($technical_set)) {
    foreach ($candidates as $cand) {
        if (!empty($cand['technical_merit_position']) && is_array($cand['technical_merit_position'])) {
            foreach ($cand['technical_merit_position'] as $ab => $_) $technical_set[$ab] = true;
        }
    }
}

// --- Build candidate structures ---
$candidate_index = []; // by reg_no
foreach ($candidates as $i => $c) {
    $choices = parse_choices($c['choice_list'] ?? '');
    $choices_map = [];
    foreach ($choices as $ri => $abbr) $choices_map[$abbr] = $ri + 1; // 1-based rank

    $candidate_index[$c['reg_no']] = [
        'idx' => $i,
        'reg_no' => $c['reg_no'],
        'user_id' => $c['user_id'] ?? null,
        'cadre_category' => $c['cadre_category'] ?? null, // GG, TT, GT
        'general_merit_position' => isset($c['general_merit_position']) ? intval($c['general_merit_position']) : null,
        'global_tech_merit' => isset($c['global_tech_merit']) ? intval($c['global_tech_merit']) : null,
        'technical_merit_position' => $c['technical_merit_position'] ?? [],
        'quota' => $c['quota'] ?? [],
        'choices' => $choices,
        'choices_map' => $choices_map,
        // allocation tracking
        'allocations' => [], // array of ['cadre_code','quota_type','choice_rank']
        'final_allocated' => false,
        'final_allocation' => null, // when finalized
    ];
}

// --- Build initial queues per cadre ---
$queues = []; // code => array of reg_no entries with score and choice_rank
foreach ($post_available as $code => $_) $queues[$code] = [];

foreach ($candidate_index as $reg => $c) {
    foreach ($c['choices'] as $rank => $abbr) {
        if (!isset($abbr_to_code[$abbr])) continue; // unknown choice
        $code = $abbr_to_code[$abbr];

        // Eligibility by candidate category
        $eligible = false;
        if (($c['cadre_category'] ?? null) === 'GG') {
            // must be general cadre
            if (!isset($technical_set[$abbr]) && $c['general_merit_position'] !== null) $eligible = true;
        } elseif (($c['cadre_category'] ?? null) === 'TT') {
            // technical only and must have passed this tech cadre
            if (isset($technical_set[$abbr]) && isset($c['technical_merit_position'][$abbr])) $eligible = true;
        } elseif (($c['cadre_category'] ?? null) === 'GT') {
            // mix: if choice is technical -> need technical_merit_position; if general -> need general_merit_position
            if (isset($technical_set[$abbr])) {
                if (isset($c['technical_merit_position'][$abbr])) $eligible = true;
            } else {
                if ($c['general_merit_position'] !== null) $eligible = true;
            }
        }

        if (!$eligible) continue;

        // Determine score for sorting in queue
        if (isset($technical_set[$abbr])) {
            $score = $c['global_tech_merit'] ?? (isset($c['technical_merit_position'][$abbr]) ? intval($c['technical_merit_position'][$abbr]) : PHP_INT_MAX);
        } else {
            $score = $c['general_merit_position'] ?? PHP_INT_MAX;
        }

        $queues[$code][] = [
            'reg_no' => $reg,
            'score' => $score,
            'choice_rank' => $rank + 1,
        ];
    }
}

// Sort each queue appropriately
foreach ($queues as $code => &$q) {
    usort($q, function($a, $b) {
        if ($a['score'] != $b['score']) return ($a['score'] < $b['score']) ? -1 : 1;
        if ($a['choice_rank'] != $b['choice_rank']) return $a['choice_rank'] <=> $b['choice_rank'];
        return strcmp($a['reg_no'], $b['reg_no']);
    });
}
unset($q);

// --- Mutable post state ---
$remaining = [];
foreach ($post_available as $code => $info) {
    $remaining[$code] = [
        'MQ' => intval($info['MQ']),
        'CFF' => intval($info['CFF']),
        'EM' => intval($info['EM']),
        'PHC' => intval($info['PHC']),
        'total_post' => intval($info['total_post']),
        'allocated' => 0,
    ];
}

// Helper to allocate a specific seat type for a candidate on a cadre (mutates $remaining and $candidate_index)
function allocate_seat(&$remaining, $code, &$candidate_index, $reg_no, $quota_type, $choice_rank) {
    if ($quota_type === 'MQ') {
        if ($remaining[$code]['MQ'] <= 0) return false;
        $remaining[$code]['MQ'] -= 1;
    } elseif (in_array($quota_type, ['CFF','EM','PHC'])) {
        if ($remaining[$code][$quota_type] <= 0) return false;
        $remaining[$code][$quota_type] -= 1;
    } else {
        return false;
    }
    $remaining[$code]['allocated'] += 1;
    $candidate_index[$reg_no]['allocations'][] = ['cadre_code' => $code, 'quota_type' => $quota_type, 'choice_rank' => $choice_rank];
    return true;
}

// Helper to deallocate a previously allocated seat (reverse)
function deallocate_seat(&$remaining, $code, &$candidate_index, $reg_no, $quota_type) {
    $found_idx = null;
    foreach ($candidate_index[$reg_no]['allocations'] as $i => $alloc) {
        if ($alloc['cadre_code'] === $code && $alloc['quota_type'] === $quota_type) { $found_idx = $i; break; }
    }
    if ($found_idx === null) return false;
    array_splice($candidate_index[$reg_no]['allocations'], $found_idx, 1);
    $remaining[$code]['allocated'] -= 1;
    if ($quota_type === 'MQ') {
        $remaining[$code]['MQ'] += 1;
    } else {
        $remaining[$code][$quota_type] += 1;
    }
    return true;
}

// --- Iterative allocation loop ---
$iteration = 0;
$changed = true;
$max_iterations = 50; // safeguard

while ($changed && $iteration < $max_iterations) {
    $iteration++;
    $changed = false;

    // 1) Fill each cadre's available seats from its queue top-down
    foreach ($queues as $code => &$q) {
        // Drop entries for candidates already finalized
        $newq = [];
        foreach ($q as $entry) {
            $reg = $entry['reg_no'];
            if ($candidate_index[$reg]['final_allocated']) continue;
            $newq[] = $entry;
        }
        $q = $newq;

        // While seats available and queue not empty
        while ((($remaining[$code]['MQ'] + $remaining[$code]['CFF'] + $remaining[$code]['EM'] + $remaining[$code]['PHC']) > 0) && count($q) > 0) {
            $entry = array_shift($q);
            $reg = $entry['reg_no'];
            if ($candidate_index[$reg]['final_allocated']) continue;

            $allocated = false;
            // Prefer MQ for merit
            if ($remaining[$code]['MQ'] > 0) {
                $allocated = allocate_seat($remaining, $code, $candidate_index, $reg, 'MQ', $entry['choice_rank']);
            }
            // If MQ not available, try quotas (CFF->EM->PHC) only if candidate has that quota
            if (!$allocated) {
                foreach (['CFF','EM','PHC'] as $qtype) {
                    if (!empty($candidate_index[$reg]['quota'][$qtype]) && $remaining[$code][$qtype] > 0) {
                        $allocated = allocate_seat($remaining, $code, $candidate_index, $reg, $qtype, $entry['choice_rank']);
                        break;
                    }
                }
            }

            if ($allocated) $changed = true;
            // if not allocated, just skip and continue to next candidate in queue
        }
    }
    unset($q);

    // 2) Resolve conflicts: candidates allocated in multiple cadres
    $multi_allocs = [];
    foreach ($candidate_index as $reg => $c) {
        if (count($c['allocations']) > 1) $multi_allocs[$reg] = $c['allocations'];
    }

    if (empty($multi_allocs)) {
        // nothing to do
        continue;
    }

    foreach ($multi_allocs as $reg => $allocs) {
        // pick best (lowest choice_rank)
        usort($allocs, function($a,$b){ return $a['choice_rank'] <=> $b['choice_rank']; });
        $best = $allocs[0];
        $best_code = $best['cadre_code'];
        $best_rank = $best['choice_rank'];
        $best_quota = $best['quota_type'];

        if ($best_rank === 1) {
            // finalize in best and remove others
            foreach ($candidate_index[$reg]['allocations'] as $a) {
                if ($a['cadre_code'] === $best_code && $a['choice_rank'] === $best_rank && $a['quota_type'] === $best_quota) continue;
                deallocate_seat($remaining, $a['cadre_code'], $candidate_index, $reg, $a['quota_type']);
                $changed = true;
            }
            $candidate_index[$reg]['final_allocated'] = true;
            $candidate_index[$reg]['final_allocation'] = $best;
            // remove from all queues
            foreach ($queues as $code => &$q2) {
                $q2 = array_filter($q2, function($e) use ($reg) { return $e['reg_no'] !== $reg; });
            }
            unset($q2);
            continue;
        }

        // best_rank > 1 => keep best allocation, free allocations for right-side choices (choice_rank > best_rank)
        foreach ($candidate_index[$reg]['allocations'] as $a) {
            if ($a['choice_rank'] > $best_rank) {
                deallocate_seat($remaining, $a['cadre_code'], $candidate_index, $reg, $a['quota_type']);
                $changed = true;
                // remove from that cadre's queue if present
                $queues[$a['cadre_code']] = array_filter($queues[$a['cadre_code']], function($e) use ($reg) { return $e['reg_no'] !== $reg; });
            }
        }

        // ensure candidate is present in left-side queues (rank < best_rank) so they can move up later
        $choices = $candidate_index[$reg]['choices'];
        for ($r = 1; $r < $best_rank; $r++) {
            if (!isset($choices[$r-1])) continue;
            $abbr = $choices[$r-1];
            if (!isset($abbr_to_code[$abbr])) continue;
            $code = $abbr_to_code[$abbr];
            if ($candidate_index[$reg]['final_allocated']) continue;
            $already_in_queue = false;
            foreach ($queues[$code] as $e) if ($e['reg_no'] === $reg) { $already_in_queue = true; break; }
            if (!$already_in_queue) {
                $score = isset($technical_set[$abbr]) ? ($candidate_index[$reg]['global_tech_merit'] ?? PHP_INT_MAX) : ($candidate_index[$reg]['general_merit_position'] ?? PHP_INT_MAX);
                $queues[$code][] = ['reg_no' => $reg, 'score' => $score, 'choice_rank' => $r];
                usort($queues[$code], function($a,$b){ if ($a['score']!=$b['score']) return ($a['score']<$b['score'])?-1:1; if ($a['choice_rank']!=$b['choice_rank']) return $a['choice_rank']<=>$b['choice_rank']; return strcmp($a['reg_no'],$b['reg_no']); });
            }
        }
    }
}

// Finalize single-allocation candidates
foreach ($candidate_index as $reg => &$c) {
    if ($c['final_allocated']) continue;
    if (count($c['allocations']) === 1) {
        $c['final_allocated'] = true;
        $c['final_allocation'] = $c['allocations'][0];
    }
}
unset($c);

// Build output
$final_allocations = [];
foreach ($candidate_index as $reg => $c) {
    if ($c['final_allocated'] && !empty($c['final_allocation'])) {
        $a = $c['final_allocation'];
        $final_allocations[] = [
            'reg_no' => $reg,
            'user_id' => $c['user_id'],
            'cadre_code' => $a['cadre_code'],
            'cadre_abbr' => $code_to_abbr[$a['cadre_code']] ?? null,
            'quota' => $a['quota_type'],
            'choice_rank' => $a['choice_rank'],
        ];
    }
}

echo '<pre>';
print_r( $final_allocations );
echo '<pre>';

// Print summary
/*

echo "Iterations run: $iteration\<br>";
echo "Total candidates: " . count($candidate_index) . "\<br>";
echo "Total finalized allocations: " . count($final_allocations) . "\<br>\<br>";
foreach ($final_allocations as $fa) {
    echo "Reg: {$fa['reg_no']} | User: {$fa['user_id']} | Cadre: {$fa['cadre_abbr']} ({$fa['cadre_code']}) | Quota: {$fa['quota']} | Choice#: {$fa['choice_rank']}n";
}

echo "Remaining posts by cadre code:";

foreach ($remaining as $code => $rem) {
    $abbr = $code_to_abbr[$code] ?? $code;
    echo "{$abbr} ({$code}): MQ={$rem['MQ']}, CFF={$rem['CFF']}, EM={$rem['EM']}, PHC={$rem['PHC']}, allocated={$rem['allocated']}<br>";
}
file_put_contents(__DIR__ . '/allocation_result.json', json_encode($final_allocations, JSON_PRETTY_PRINT));
echo "Saved allocation_result.json";

*/

die();