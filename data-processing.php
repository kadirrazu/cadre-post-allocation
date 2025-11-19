<?php

// --- Build mappings ---
$abbr_to_code = [];
$code_to_abbr = [];
$code_to_name = [];

foreach ($post_available as $code => $info) 
{

    $abbr = $info['cadre'];
    $abbr_to_code[$abbr] = $code;
    $code_to_abbr[$code] = $abbr;

    // try to find a pretty name from $general_cadres if present
    foreach ($general_cadres as $gkey => $list) 
    {
        if (isset($list[$abbr])) {
            $code_to_name[$code] = $list[$abbr]['name'];
            break;
        }
    }

    if (!isset($code_to_name[$code])) {
        $code_to_name[$code] = $abbr;
    }

}

// Detect which cadres are 'technical' by checking if they appear inside any candidate's technical_merit_position
$technical_abbrs = [];

foreach ($candidates as $cand)
{
    if (!empty($cand['technical_merit_position']) && is_array($cand['technical_merit_position']))
    {
        foreach ($cand['technical_merit_position'] as $tech_abbr => $_pos) {
            $technical_abbrs[$tech_abbr] = true;
        }
    }
}

// Create per-cadre queues (for visibility / debugging)
$queues = [];

foreach ($post_available as $code => $info) {
    $queues[$code] = [];
}

// Helper: parse choice list string into array of abbreviations
function parse_choices($choice_str) {
    $parts = preg_split('/\s+/', trim($choice_str));
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = $p;
    }
    return $out;
}

// Build overall candidate ordering key (for allocation order) and populate queues
$allocation_candidates = []; // will contain index => candidate copy + priority_score

foreach ($candidates as $idx => $cand)
{
    // Determine ordering score: prefer general_merit_position (if exists), otherwise use global_tech_merit
    $order_score = null;
    
    if (!empty($cand['general_merit_position'])) {
        $order_score = intval($cand['general_merit_position']);
    } elseif (!empty($cand['global_tech_merit'])) {
        // Note: smaller global_tech_merit is better? We'll treat smaller = better
        $order_score = intval($cand['global_tech_merit']);
    } else {
        // fallback: use a large number so they come last
        $order_score = PHP_INT_MAX - $idx;
    }

    $allocation_candidates[] = ['candidate' => $cand, 'order_score' => $order_score, 'orig_index' => $idx];

    // Now build per-cadre queues for each choice
    $choices = parse_choices($cand['choice_list']);
    $choice_rank = 0;
    foreach ($choices as $choice_abbr) {
        $choice_rank++;
        if (!isset($abbr_to_code[$choice_abbr])) continue; // choice not in posts
        $code = $abbr_to_code[$choice_abbr];
        // Eligibility checks
        $is_technical = isset($technical_abbrs[$choice_abbr]);
        $eligible = true;
        $score_for_sort = null;

        if ($is_technical) {
            // For technical cadres, the candidate must have passed (technical_merit_position must list this abbrev)
            if (empty($cand['technical_merit_position']) || !is_array($cand['technical_merit_position']) || !isset($cand['technical_merit_position'][$choice_abbr])) {
                $eligible = false;
            } else {
                // Use technical merit ordering; if candidate has 'global_tech_merit' use that as primary sort key
                $score_for_sort = isset($cand['global_tech_merit']) ? intval($cand['global_tech_merit']) : intval($cand['technical_merit_position'][$choice_abbr]);
            }
        } else {
            // General cadre requires general_merit_position
            if (empty($cand['general_merit_position'])) {
                $eligible = false;
            } else {
                $score_for_sort = intval($cand['general_merit_position']);
            }
        }

        if (!$eligible) continue;

        // Push into queue for this cadre
        $queues[$code][] = [
            'reg_no' => $cand['reg_no'],
            'user_id' => $cand['user_id'],
            'score' => $score_for_sort,
            'choice_rank' => $choice_rank,
            'cadre_abbr' => $choice_abbr,
            'cadre_code' => $code,
            'cand_index' => $idx,
            'quota' => $cand['quota'] ?? [],
            'cadre_category' => $cand['cadre_category'] ?? null,
        ];
    }
}

// Sort each queue by score ascending, then by choice_rank, then by reg_no
foreach ($queues as $code => &$q) {
    usort($q, function($a, $b) {
        if ($a['score'] != $b['score']) return ($a['score'] < $b['score']) ? -1 : 1;
        if ($a['choice_rank'] != $b['choice_rank']) return ($a['choice_rank'] < $b['choice_rank']) ? -1 : 1;
        return strcmp($a['reg_no'], $b['reg_no']);
    });
}
unset($q);

// --- Allocation ---
// Copy post availability to mutable structure
$remaining = [];
foreach ($post_available as $code => $info) {
    $remaining[$code] = [
        'MQ' => $info['MQ'],
        'CFF' => $info['CFF'],
        'EM' => $info['EM'],
        'PHC' => $info['PHC'],
        'total_post' => $info['total_post'],
        'allocated' => 0,
    ];
}

$allocations = []; // reg_no => allocation details

// Sort global candidate order by order_score ascending
usort($allocation_candidates, function($a,$b){
    if ($a['order_score'] != $b['order_score']) return ($a['order_score'] < $b['order_score']) ? -1 : 1;
    return $a['orig_index'] <=> $b['orig_index'];
});

// Helper to check eligibility for a given candidate and cadre_abbr
function candidate_is_eligible_for($candidate, $cadre_abbr, $abbr_to_code, $technical_abbrs) {
    if (!isset($abbr_to_code[$cadre_abbr])) return false;
    $code = $abbr_to_code[$cadre_abbr];
    $is_technical = isset($technical_abbrs[$cadre_abbr]);
    if ($is_technical) {
        if (empty($candidate['technical_merit_position']) || !is_array($candidate['technical_merit_position'])) return false;
        return isset($candidate['technical_merit_position'][$cadre_abbr]);
    } else {
        return !empty($candidate['general_merit_position']);
    }
}

// For each candidate in merit order, try to allocate
foreach ($allocation_candidates as $entry) {
    $cand = $entry['candidate'];
    $reg = $cand['reg_no'];
    if (isset($allocations[$reg])) continue; // already allocated

    $choices = parse_choices($cand['choice_list']);
    foreach ($choices as $choice_abbr) {
        if (!isset($abbr_to_code[$choice_abbr])) continue;
        $code = $abbr_to_code[$choice_abbr];

        // Quickly skip if no seats left at all
        $total_left = $remaining[$code]['MQ'] + $remaining[$code]['CFF'] + $remaining[$code]['EM'] + $remaining[$code]['PHC'];
        if ($total_left <= 0) continue;

        // Check eligibility
        $is_technical = isset($technical_abbrs[$choice_abbr]);
        $eligible = true;
        if ($is_technical) {
            if (empty($cand['technical_merit_position']) || !isset($cand['technical_merit_position'][$choice_abbr])) $eligible = false;
        } else {
            if (empty($cand['general_merit_position'])) $eligible = false;
        }
        if (!$eligible) continue;

        // If candidate has a quota, try to allocate in that quota for this cadre if seats remain.
        // Quota preference order: CFF -> EM -> PHC -> MQ
        $quota_allocated = null;
        if (!empty($cand['quota']) && is_array($cand['quota'])) {
            if (!empty($cand['quota']['CFF']) && $remaining[$code]['CFF'] > 0) {
                $remaining[$code]['CFF'] -= 1;
                $quota_allocated = 'CFF';
            } elseif (!empty($cand['quota']['EM']) && $remaining[$code]['EM'] > 0) {
                $remaining[$code]['EM'] -= 1;
                $quota_allocated = 'EM';
            } elseif (!empty($cand['quota']['PHC']) && $remaining[$code]['PHC'] > 0) {
                $remaining[$code]['PHC'] -= 1;
                $quota_allocated = 'PHC';
            }
        }

        // If no quota seat allocated above, allocate MQ if available
        if ($quota_allocated === null && $remaining[$code]['MQ'] > 0) {
            $remaining[$code]['MQ'] -= 1;
            $quota_allocated = 'MQ';
        }

        if ($quota_allocated !== null) {
            // allocate
            $remaining[$code]['allocated'] += 1;
            $allocations[$reg] = [
                'reg_no' => $reg,
                'user_id' => $cand['user_id'],
                'cadre_code' => $code,
                'cadre_abbr' => $choice_abbr,
                'cadre_name' => $code_to_name[$code] ?? $choice_abbr,
                'quota' => $quota_allocated,
                'choice_assigned_rank' => array_search($choice_abbr, parse_choices($cand['choice_list'])) + 1,
            ];
            break; // stop at first successful allocation (highest priority choice available)
        }
        // else try next choice
    }
}

// --- Output / Summary ---
// Build readable allocation list
$alloc_list = array_values($allocations);

// Print a summary to stdout (CLI/web)
echo "--- Allocation Summary ---<br>";
echo "Total candidates: " . count($candidates) . "<br>";
echo "Total allocated: " . count($alloc_list) . "<br><br>";

foreach ($alloc_list as $a) {
    echo "Reg: {$a['reg_no']} | User: {$a['user_id']} | Cadre: {$a['cadre_abbr']} ({$a['cadre_name']}) | Quota: {$a['quota']} | Choice#: {$a['choice_assigned_rank']}<br>";
}

echo "<br>--- Remaining posts by cadre code ---<br>";
foreach ($remaining as $code => $rem) {
    $abbr = $code_to_abbr[$code];
    echo "{$abbr} ({$code}): MQ={$rem['MQ']}, CFF={$rem['CFF']}, EM={$rem['EM']}, PHC={$rem['PHC']}, allocated={$rem['allocated']}<br>";
}

// Optionally: dump queues for inspection (commented out)
// var_export($queues);

// End of implementation



echo '<pre>';
print_r( $alloc_list );
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