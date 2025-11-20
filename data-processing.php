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

$technicalCadres = array_column($technical_cadres['TECHNICAL'], 'abbr');

$allocationQueues = [];
$finalAllocated = [];
$logs = [];

/**
 * Convert technical merit to readable format:
 *   [ 'EEE' => 54, 'CIV' => 83 ]
 */
function formatTechnicalMerit($candidate) {
    if (!isset($candidate['technical_merit_position'])) return [];

    $out = [];
    foreach ($candidate['technical_merit_position'] as $key => $val) {
        $out[$key] = $val;
    }
    return $out;
}

/**
 * Add log entry
 */
function addLog(&$logs, $reg_no, $message) {
    $logs[] = [
        'reg_no' => $reg_no,
        'message' => $message
    ];
}

/**
 * Create allocation queues
 */
foreach ($candidates as $candidate) {

    $rawChoiceList = $candidate['choice_list'];
    $choiceList = explode(" ", trim($candidate['choice_list']));
    $category = $candidate['cadre_category'];

    foreach ($choiceList as $choice) {

        $isTechnicalCadre = in_array($choice, $technicalCadres);

        // RULE 1 — GG → only assign to GENERAL cadets
        if ($category === 'GG') {
            if ($candidate['general_merit_position'] === null) {
                addLog($logs, $candidate['reg_no'], "Skipped $choice: GG candidate missing general merit.");
                continue;
            }
            if ($isTechnicalCadre) {
                addLog($logs, $candidate['reg_no'],
                    "Skipped $choice: GG candidate cannot enter technical cadre queue."
                );
                continue;
            }
        }

        // RULE 2 — TT → Only technical cadres where technical_merit_position exists
        if ($category === 'TT') {
            if (!$isTechnicalCadre) {
                addLog($logs, $candidate['reg_no'], "Skipped $choice: TT candidate cannot join general cadres.");
                continue;
            }

            $merits = formatTechnicalMerit($candidate);
            if (!isset($merits[$choice])) {
                addLog($logs, $candidate['reg_no'],
                    "Skipped $choice: No technical merit available for this cadre."
                );
                continue;
            }
        }

        // RULE 3 — GT → technical cadres must exist in technical merit list
        if ($category === 'GT' && $isTechnicalCadre) {
            $merits = formatTechnicalMerit($candidate);
            if (!isset($merits[$choice])) {
                addLog($logs, $candidate['reg_no'],
                    "Skipped $choice: GT candidate lacks technical merit for $choice."
                );
                continue;
            }
        }

        // ADD TO QUEUE
        $allocationQueues[$choice][] = [
            'candidate' => $candidate,
            'raw_choice_list' => $rawChoiceList
        ];

        addLog($logs, $candidate['reg_no'], "Added to $choice queue.");
    }
}

/**
 * Sort queues
 */
foreach ($allocationQueues as $cadre => &$queue) {

    $isTechnical = in_array($cadre, $technicalCadres);

    usort($queue, function ($a, $b) use ($isTechnical) {
        $A = $a['candidate'];
        $B = $b['candidate'];

        if (!$isTechnical) { // general sort
            return $A['general_merit_position'] <=> $B['general_merit_position'];
        }

        // technical sort
        $A_m = formatTechnicalMerit($A)[$cadre] ?? 999999;
        $B_m = formatTechnicalMerit($B)[$cadre] ?? 999999;
        return $A_m <=> $B_m;
    });

}

$allocation = [];

foreach ($allocationQueues as $cadre => &$queue) {

    if (!isset($post_available[$abbr_to_code[$cadre]])) continue;

    $remainingPosts = $post_available[$abbr_to_code[$cadre]]['total_post'];
    if ($remainingPosts <= 0) continue;

    foreach ($queue as $i => $entry) {

        if ($remainingPosts <= 0) break;

        $candidate = $entry['candidate'];

        if (isset($finalAllocated[$candidate['reg_no']])) {
            continue;
        }

        $chosenRank = array_search($cadre, explode(" ", $entry['raw_choice_list'])) + 1;

        $allocation[$cadre][] = [
            'reg_no' => $candidate['reg_no'],
            'user_id' => $candidate['user_id'],
            'cadre_code' => $cadre_list['code'][$cadre],
            'cadre_abbr' => $cadre,
            'cadre_name' => $cadre_list['name'][$cadre],
            'quota' => $candidate['quota'] ?? 'GEN',
            'choice_assigned_rank' => $chosenRank,
            'raw_choice_list' => $entry['raw_choice_list'],
            'technical_merit_positions' => formatTechnicalMerit($candidate)
        ];

        $post_available[$cadre]['total']--;
        $stillChanging = true;

        addLog($logs, $candidate['reg_no'], "Tentatively allocated to $cadre.");
    }

}

echo '<pre>';

var_dump( $allocation );

echo '</pre>';

die();

/**
 * MULTIPLE ASSIGNMENT RESOLUTION
 */
$foundMultiple = false;

foreach ($allocation as $cadre => $list) {

    foreach ($list as $assigned) {

        $reg = $assigned['reg_no'];

        // Count total temporary assignments
        $count = 0;
        $foundIn = [];

        foreach ($allocation as $c2 => $l2) {
            foreach ($l2 as $item) {
                if ($item['reg_no'] === $reg) {
                    $count++;
                    $foundIn[] = $c2;
                }
            }
        }

        if ($count <= 1) continue; // no conflict

        $foundMultiple = true;

        $candidate = null;
        foreach ($candidates as $c) {
            if ($c['reg_no'] == $reg) {
                $candidate = $c;
                break;
            }
        }

        $choiceArray = explode(" ", $candidate['choice_list']);

        // choose highest preference
        $bestCadre = null;
        foreach ($choiceArray as $p) {
            if (in_array($p, $foundIn)) {
                $bestCadre = $p;
                break;
            }
        }

        // finalize if first preference
        if ($bestCadre === $choiceArray[0]) {
            $finalAllocated[$reg] = $bestCadre;
        }

        // remove from all but bestCadre
        foreach ($allocation as $cad2 => &$allocList) {
            foreach ($allocList as $i => $item) {
                if ($item['reg_no'] == $reg && $cad2 !== $bestCadre) {
                    unset($allocList[$i]);
                    $post_available[$cad2]['total']++; // return post
                }
            }
        }
    }
}

die();

/**
 * Multi-step allocation iteration
 */

$allocation = [];

$stillChanging = true;

while ($stillChanging) {
    $stillChanging = false;

    foreach ($allocationQueues as $cadre => &$queue) {

        if (!isset($post_available[$cadre])) continue;

        $remainingPosts = $post_available[$cadre]['total'];
        if ($remainingPosts <= 0) continue;

        print_r($queue);

        foreach ($queue as $i => $entry) {

            if ($remainingPosts <= 0) break;

            $candidate = $entry['candidate'];

            if (isset($finalAllocated[$candidate['reg_no']])) {
                addLog($logs, $candidate['reg_no'], "Already finalized elsewhere. Skipped in $cadre.");
                continue;
            }

            $chosenRank = array_search($cadre, explode(" ", $entry['raw_choice_list'])) + 1;

            $allocation[$cadre][] = [
                'reg_no' => $candidate['reg_no'],
                'user_id' => $candidate['user_id'],
                'cadre_code' => $cadre_list['code'][$cadre],
                'cadre_abbr' => $cadre,
                'cadre_name' => $cadre_list['name'][$cadre],
                'quota' => $candidate['quota'] ?? 'GEN',
                'choice_assigned_rank' => $chosenRank,
                'raw_choice_list' => $entry['raw_choice_list'],
                'technical_merit_positions' => formatTechnicalMerit($candidate)
            ];

            $post_available[$cadre]['total']--;
            $stillChanging = true;

            addLog($logs, $candidate['reg_no'], "Tentatively allocated to $cadre.");
        }
    }

    /**
     * MULTIPLE ASSIGNMENT RESOLUTION
     */
    $foundMultiple = false;

    foreach ($allocation as $cadre => $list) {

        foreach ($list as $assigned) {

            $reg = $assigned['reg_no'];

            // Count total temporary assignments
            $count = 0;
            $foundIn = [];

            foreach ($allocation as $c2 => $l2) {
                foreach ($l2 as $item) {
                    if ($item['reg_no'] === $reg) {
                        $count++;
                        $foundIn[] = $c2;
                    }
                }
            }

            if ($count <= 1) continue; // no conflict

            $foundMultiple = true;

            $candidate = null;
            foreach ($candidates as $c) {
                if ($c['reg_no'] == $reg) {
                    $candidate = $c;
                    break;
                }
            }

            $choiceArray = explode(" ", $candidate['choice_list']);

            // choose highest preference
            $bestCadre = null;
            foreach ($choiceArray as $p) {
                if (in_array($p, $foundIn)) {
                    $bestCadre = $p;
                    break;
                }
            }

            addLog($logs, $reg, "Multiple assignments found. Best cadre: $bestCadre.");

            // finalize if first preference
            if ($bestCadre === $choiceArray[0]) {
                $finalAllocated[$reg] = $bestCadre;
                addLog($logs, $reg, "Finalized because best cadre is 1st choice.");
            }

            // remove from all but bestCadre
            foreach ($allocation as $cad2 => &$allocList) {
                foreach ($allocList as $i => $item) {
                    if ($item['reg_no'] == $reg && $cad2 !== $bestCadre) {
                        unset($allocList[$i]);
                        $post_available[$cad2]['total']++; // return post

                        addLog($logs, $reg, "Removed from $cad2 (kept $bestCadre).");
                    }
                }
            }
        }
    }

    if (!$foundMultiple) $stillChanging = false;
}

/**
 * Build UNALLOCATED list
 */
$allocatedRegs = [];
foreach ($allocation as $cadre => $arr) {
    foreach ($arr as $entry) {
        $allocatedRegs[$entry['reg_no']] = true;
    }
}

$unallocated = [];

foreach ($candidates as $c) {
    if (!isset($allocatedRegs[$c['reg_no']])) {
        $unallocated[] = [
            'reg_no' => $c['reg_no'],
            'user_id' => $c['user_id'],
            'raw_choice_list' => $c['choice_list'],
            'technical_merit_positions' => formatTechnicalMerit($c),
            'message' => "No cadre allocated after all rounds."
        ];
        addLog($logs, $c['reg_no'], "Remained unallocated.");
    }
}

/*return [
    'allocation' => $allocation,
    'unallocated' => $unallocated,
    'logs' => $logs
];*/

echo '<pre>';

var_dump( $allocatedRegs );

echo '</pre>';

die();


