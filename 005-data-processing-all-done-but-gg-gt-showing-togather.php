<?php
// ====================== HELPER FUNCTIONS ======================

function get_primary_merit($candidate) {
    if (!empty($candidate['general_merit_position'])) return $candidate['general_merit_position'];
    if (!empty($candidate['technical_merit_position']) && is_array($candidate['technical_merit_position'])) {
        return min($candidate['technical_merit_position']);
    }
    return PHP_INT_MAX;
}

function get_technical_merit($candidate, $cadre) {
    return $candidate['technical_merit_position'][$cadre] ?? PHP_INT_MAX;
}

function eligible_for_quota($candidate, $quota) {
    return !empty($candidate['quota'][$quota]);
}

// ====================== PREPARE DATA ======================

// Map posts by cadre short name
$postsByCadre = [];
foreach ($post_available as $code => $data) {
    $postsByCadre[$data['cadre']] = $code;
}

// Normalize candidate choices
foreach ($candidates as &$c) {
    $c['choices'] = isset($c['choice_list']) ? array_map('strtoupper', array_filter(array_map('trim', explode(' ', $c['choice_list'])))) : [];
    $c['allocated'] = false;
    $c['assigned_post'] = null;
    $c['assigned_quota'] = null;
    $c['type'] = null;
}
unset($c);

// Copy of remaining posts
$post_remaining = $post_available;

// Separate general and technical candidates
$general_candidates = array_filter($candidates, fn($c) => in_array($c['cadre_category'], ['GG', 'GT']));
$technical_candidates = array_filter($candidates, fn($c) => $c['cadre_category'] === 'TT');

// Sort general candidates by general merit
usort($general_candidates, fn($a,$b) => ($a['general_merit_position'] ?? PHP_INT_MAX) <=> ($b['general_merit_position'] ?? PHP_INT_MAX));

// Sort technical candidates by **best merit among their technical choices**
usort($technical_candidates, function($a,$b){
    $a_merit = !empty($a['technical_merit_position']) ? min($a['technical_merit_position']) : PHP_INT_MAX;
    $b_merit = !empty($b['technical_merit_position']) ? min($b['technical_merit_position']) : PHP_INT_MAX;
    return $a_merit <=> $b_merit;
});

// ====================== ALLOCATION FUNCTION ======================

function allocate_candidates(&$candidates, &$post_remaining, $postsByCadre, $cadreType, &$final_allocation) {
    foreach ($candidates as &$c) {
        if ($c['allocated']) continue;

        foreach ($c['choices'] as $choice) {

            if ($cadreType === 'TECHNICAL' && empty($c['technical_merit_position'][$choice])) continue;
            if (!isset($postsByCadre[$choice])) continue;

            $postCode = $postsByCadre[$choice];

            // MERIT ALLOCATION
            if (($post_remaining[$postCode]['MQ'] ?? 0) > 0) {
                $post_remaining[$postCode]['MQ']--;
                $c['allocated'] = true;
                $c['assigned_post'] = $choice;
                $c['assigned_quota'] = 'MERIT';
                $c['type'] = $cadreType;

                $final_allocation[] = [
                    'candidate' => $c,
                    'cadre' => $choice,
                    'quota' => 'MERIT',
                    'type' => $cadreType
                ];
                break;
            }

            // QUOTA ALLOCATION
            foreach (['CFF','EM','PHC'] as $quota) {
                if (($post_remaining[$postCode][$quota] ?? 0) > 0 && eligible_for_quota($c, $quota)) {
                    $post_remaining[$postCode][$quota]--;
                    $c['allocated'] = true;
                    $c['assigned_post'] = $choice;
                    $c['assigned_quota'] = $quota;
                    $c['type'] = $cadreType;

                    $final_allocation[] = [
                        'candidate' => $c,
                        'cadre' => $choice,
                        'quota' => $quota,
                        'type' => $cadreType
                    ];
                    break 2;
                }
            }

        } // end choices loop

        // If still unallocated
        if (!$c['allocated']) {
            $final_allocation[] = [
                'candidate' => $c,
                'cadre' => null,
                'quota' => null,
                'type' => null
            ];
        }

    }
    unset($c);
}

// ====================== INITIAL ALLOCATION ======================

$final_allocation = [];
allocate_candidates($general_candidates, $post_remaining, $postsByCadre, 'GENERAL', $final_allocation);
allocate_candidates($technical_candidates, $post_remaining, $postsByCadre, 'TECHNICAL', $final_allocation);

// ====================== SWAP OPTIMIZATION ======================

function swap_optimization(&$final_allocation, $postsByCadre, &$post_remaining) {
    $swapped = true;
    $iteration = 0;
    $max_iterations = 5;

    while($swapped && $iteration++ < $max_iterations) {
        $swapped = false;

        foreach ($final_allocation as &$c1) {
            if (empty($c1['cadre'])) continue;
            $current_post = $c1['cadre'];
            foreach ($c1['candidate']['choices'] as $better_choice) {
                if ($better_choice === $current_post) break;
                $better_code = $postsByCadre[$better_choice] ?? null;
                if (!$better_code) continue;

                $mq_left = $post_remaining[$better_code]['MQ'] ?? 0;
                $quota_left = array_sum(array_intersect_key($post_remaining[$better_code], array_flip(['CFF','EM','PHC'])));
                if ($mq_left + $quota_left > 0) continue;

                foreach ($final_allocation as &$c2) {
                    if ($c2['cadre'] !== $better_choice) continue;

                    $merit_c1 = $c1['type'] === 'TECHNICAL' ? get_technical_merit($c1['candidate'],$better_choice) : ($c1['candidate']['general_merit_position'] ?? PHP_INT_MAX);
                    $merit_c2 = $c2['type'] === 'TECHNICAL' ? get_technical_merit($c2['candidate'],$better_choice) : ($c2['candidate']['general_merit_position'] ?? PHP_INT_MAX);

                    if ($merit_c1 < $merit_c2) {
                        $temp_post = $c1['cadre']; $temp_quota = $c1['quota'];
                        $c1['cadre'] = $c2['cadre']; $c1['quota'] = $c2['quota'];
                        $c2['cadre'] = $temp_post; $c2['quota'] = $temp_quota;
                        $swapped = true;
                        break;
                    }
                }
                unset($c2);
                if ($swapped) break;
            }
            if ($swapped) break;
        }
        unset($c1);
    }
}

swap_optimization($final_allocation, $postsByCadre, $post_remaining);

// ====================== NATIONAL MERIT (NM) FILL ======================

foreach ($final_allocation as &$c) {
    if ($c['cadre'] !== null) continue;
    foreach ($c['candidate']['choices'] as $choice) {
        $postCode = $postsByCadre[$choice] ?? null;
        if (!$postCode) continue;

        if (($post_remaining[$postCode]['MQ'] ?? 0) > 0) {
            $post_remaining[$postCode]['MQ']--;
            $c['cadre'] = $choice;
            $c['quota'] = 'NM';
            $c['type'] = 'NM';
            break;
        }

        foreach (['CFF','EM','PHC'] as $quota) {
            if (($post_remaining[$postCode][$quota] ?? 0) > 0) {
                $post_remaining[$postCode][$quota]--;
                $c['cadre'] = $choice;
                $c['quota'] = 'NM';
                $c['type'] = 'NM';
                break 2;
            }
        }
    }
}
unset($c);

// ====================== FINAL SORT BY CADRE CODE ======================

usort($final_allocation, function($a,$b) use ($general_cadres,$technical_cadres) {
    $cadreA = $a['cadre'] ?? '';
    $cadreB = $b['cadre'] ?? '';
    $codeA = $general_cadres['GENERAL'][$cadreA]['code'] ?? ($technical_cadres['TECHNICAL'][$cadreA]['code'] ?? PHP_INT_MAX);
    $codeB = $general_cadres['GENERAL'][$cadreB]['code'] ?? ($technical_cadres['TECHNICAL'][$cadreB]['code'] ?? PHP_INT_MAX);
    return $codeA <=> $codeB;
});

// $final_allocation is now fully ready and compatible with your previous code

//return $final_allocation;





// ============================
// SPLIT RESULTS
// ============================

$final_general = array_values(array_filter($final_allocation, fn($r)=> $r['type'] === 'GENERAL'));
$final_technical = array_values(array_filter($final_allocation, fn($r)=> $r['type'] === 'TECHNICAL'));
$final_unassigned = array_values(array_filter($final_allocation, fn($r)=> $r['type'] === null));

// ============================
// DEBUG: LOGS
// ============================
/*echo "<pre>Allocation Logs:\n";
foreach ($allocation_logs as $log) {
    echo $log . "\n";
}
echo "</pre>";*/
