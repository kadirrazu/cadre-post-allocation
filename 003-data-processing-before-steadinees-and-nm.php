<?php
// ----------------- PRECONDITIONS -----------------
// Assumes these variables are defined above this script:
// $general_cadres, $technical_cadres, $post_available, $candidates

// ----------------- PREP -----------------

// Index posts by short cadre name => numeric code
$postsByCadre = [];
foreach ($post_available as $code => $p) {
    $postsByCadre[$p['cadre']] = $code;
}

// Normalize candidates: ensure quota and technical arrays and choices
foreach ($candidates as &$candidate) {
    if (!isset($candidate['quota']) || !is_array($candidate['quota'])) {
        $candidate['quota'] = ['CFF' => false, 'EM' => false, 'PHC' => false];
    } else {
        // ensure all keys exist
        foreach (['CFF','EM','PHC'] as $q) {
            if (!array_key_exists($q, $candidate['quota'])) $candidate['quota'][$q] = false;
        }
    }
    if (!isset($candidate['technical_merit_position']) || !is_array($candidate['technical_merit_position'])) {
        $candidate['technical_merit_position'] = [];
    }
    // normalize choices to uppercase tokens and trim empty tokens
    $candidate['choices'] = [];
    if (!empty($candidate['choice_list'])) {
        $tokens = array_map('trim', explode(' ', $candidate['choice_list']));
        foreach ($tokens as $t) {
            $u = strtoupper($t);
            if ($u !== '') $candidate['choices'][] = $u;
        }
    }
}
unset($candidate);

// Working copy of posts (remaining seats)
$post_remaining = $post_available;

// Helper: find whether a token is general or technical
$general_keys = array_keys($general_cadres['GENERAL']);
$technical_keys = array_keys($technical_cadres['TECHNICAL']);

// Helper: get candidate's highest-priority choice TYPE (GENERAL or TECHNICAL or null)
function highest_choice_type($cand, $general_keys, $technical_keys) {
    foreach ($cand['choices'] as $c) {
        if (in_array($c, $general_keys, true)) return 'GENERAL';
        if (in_array($c, $technical_keys, true)) return 'TECHNICAL';
    }
    return null;
}

// ----------------- 1) BUILD FLOWS BASED ON GT HIGHEST CHOICE -----------------

$gen_pool = [];  // GG + GT whose first choice is GENERAL
$tech_pool = []; // TT + GT whose first choice is TECHNICAL

foreach ($candidates as $cand) {
    if ($cand['cadre_category'] === 'GG') {
        // All GG candidates -> general pool
        $gen_pool[] = $cand;
    } elseif ($cand['cadre_category'] === 'TT') {
        // All TT -> technical pool
        $tech_pool[] = $cand;
    } elseif ($cand['cadre_category'] === 'GT') {
        // Decide by highest choice type
        $hc = highest_choice_type($cand, $general_keys, $technical_keys);
        if ($hc === 'GENERAL') $gen_pool[] = $cand;
        elseif ($hc === 'TECHNICAL') $tech_pool[] = $cand;
        else {
            // if no recognizable choices, put in general by default
            $gen_pool[] = $cand;
        }
    }
}

// ----------------- 2) GENERAL ALLOCATION (MERIT first, then quotas) -----------------

// Only consider general choices for this pool
foreach ($gen_pool as &$g) {
    $g['gen_choices'] = array_values(array_filter($g['choices'], fn($c)=> in_array($c, $general_keys, true)));
}
unset($g);

// sort general pool by general_merit_position (ascending; null => PHP_INT_MAX)
usort($gen_pool, function($a,$b){
    $pa = $a['general_merit_position'] ?? PHP_INT_MAX;
    $pb = $b['general_merit_position'] ?? PHP_INT_MAX;
    return $pa <=> $pb;
});

// arrays to collect results
$final_allocation = []; // combined allocations
$assigned_regno = [];   // reg_no => true when assigned

// waiting lists (for move-ups) per-general-cadre (cadreShort => [ entries ])
$general_waiting = [];

// allocate general candidates in order
foreach ($gen_pool as $cand) {
    $assigned = false;

    foreach ($cand['gen_choices'] as $choice) {
        if (!isset($postsByCadre[$choice])) continue;
        $postCode = $postsByCadre[$choice];

        // MERIT only if candidate actually has general merit position (GG or GT with gmpos)
        if (!empty($cand['general_merit_position']) && ($post_remaining[$postCode]['MQ'] ?? 0) > 0) {
            $post_remaining[$postCode]['MQ']--;
            $final_allocation[] = ['candidate'=>$cand, 'cadre'=>$choice, 'cadre_code'=>$postCode, 'quota'=>'MERIT', 'type'=>'GENERAL'];
            $assigned = true;
            $assigned_regno[$cand['reg_no']] = true;
            break;
        }

        // QUOTAS (CFF -> EM -> PHC)
        foreach (['CFF','EM','PHC'] as $q) {
            if (!empty($cand['quota'][$q]) && (($post_remaining[$postCode][$q] ?? 0) > 0)) {
                $post_remaining[$postCode][$q]--;
                $final_allocation[] = ['candidate'=>$cand, 'cadre'=>$choice, 'cadre_code'=>$postCode, 'quota'=>$q, 'type'=>'GENERAL'];
                $assigned = true;
                $assigned_regno[$cand['reg_no']] = true;
                break 2;
            }
        }
    }

    if (!$assigned) {
        // Add candidate to waiting lists of each general choice (for move-up)
        foreach ($cand['gen_choices'] as $choice) {
            if (!isset($postsByCadre[$choice])) continue;
            $postCode = $postsByCadre[$choice];
            $general_waiting[$choice][] = [
                'candidate' => $cand,
                'gen_merit' => $cand['general_merit_position'] ?? PHP_INT_MAX,
                'choice_priority' => array_search($choice, $cand['gen_choices'], true) // lower => higher priority among their general choices
            ];
        }
    }
}

// ----------------- 3) TECHNICAL ALLOCATION (per-cadre, by technical merit) -----------------

// Build per-cadre applicant lists from tech_pool AND any GT/TT not yet assigned that also belong to technical
$tech_applicants_by_cadre = [];

// Note: include only candidates who have technical merit entry for that cadre
foreach ($tech_pool as $cand) {
    foreach ($cand['choices'] as $idx => $choice) {
        if (!isset($technical_cadres['TECHNICAL'][$choice])) continue;
        if (!isset($cand['technical_merit_position'][$choice])) continue;
        $tech_applicants_by_cadre[$choice][] = [
            'candidate' => $cand,
            'tech_merit' => $cand['technical_merit_position'][$choice],
            'choice_priority' => $idx
        ];
    }
}

// Also include any TT/GT candidates who weren't in tech_pool but remain unassigned and have technical choices
foreach ($candidates as $cand) {
    if (!empty($assigned_regno[$cand['reg_no']])) continue; // already assigned
    // if they are TT or GT but not in tech_pool, include them if they have tech choices
    if (!in_array($cand['cadre_category'], ['TT','GT'], true)) continue;
    foreach ($cand['choices'] as $idx => $choice) {
        if (!isset($technical_cadres['TECHNICAL'][$choice])) continue;
        if (!isset($cand['technical_merit_position'][$choice])) continue;
        // avoid duplicates: ensure not already present
        $exists = false;
        if (isset($tech_applicants_by_cadre[$choice])) {
            foreach ($tech_applicants_by_cadre[$choice] as $e) {
                if ($e['candidate']['reg_no'] === $cand['reg_no']) { $exists = true; break; }
            }
        }
        if (!$exists) {
            $tech_applicants_by_cadre[$choice][] = [
                'candidate' => $cand,
                'tech_merit' => $cand['technical_merit_position'][$choice],
                'choice_priority' => $idx
            ];
        }
    }
}

// Sort applicants for each technical cadre by tech merit ascending (tie-breaker: choice_priority)
foreach ($tech_applicants_by_cadre as $cadre => &$list) {
    usort($list, function($a,$b){
        if ($a['tech_merit'] === $b['tech_merit']) {
            return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
        }
        return $a['tech_merit'] <=> $b['tech_merit'];
    });
}
unset($list);

// temporary waiting list for tech (candidates that couldn't be assigned initially)
$tech_waiting = [];

// allocate technical cadre seats using their sorted lists
foreach ($tech_applicants_by_cadre as $cadre => $applicants) {
    if (!isset($postsByCadre[$cadre])) continue;
    $postCode = $postsByCadre[$cadre];

    foreach ($applicants as $entry) {
        $cand = $entry['candidate'];
        $reg = $cand['reg_no'];
        if (!empty($assigned_regno[$reg])) continue; // skip if already assigned in general

        // MERIT
        if (($post_remaining[$postCode]['MQ'] ?? 0) > 0) {
            $post_remaining[$postCode]['MQ']--;
            $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadre,'cadre_code'=>$postCode,'quota'=>'MERIT','type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
            $assigned_regno[$reg] = true;
            continue;
        }

        // QUOTAS
        $gotQuota = false;
        foreach (['CFF','EM','PHC'] as $q) {
            if (!empty($cand['quota'][$q]) && (($post_remaining[$postCode][$q] ?? 0) > 0)) {
                $post_remaining[$postCode][$q]--;
                $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadre,'cadre_code'=>$postCode,'quota'=>$q,'type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
                $assigned_regno[$reg] = true;
                $gotQuota = true;
                break;
            }
        }
        if (!$gotQuota) {
            // add to waiting list of this tech cadre
            $tech_waiting[$cadre][] = ['candidate'=>$cand,'tech_merit'=>$entry['tech_merit'],'choice_priority'=>$entry['choice_priority']];
        }
    }
}

// ----------------- 4) MOVE-UP / REBALANCING LOOP (fill vacancies from waiting lists) -----------------

$changed = true;
while ($changed) {
    $changed = false;

    // Build list of cadres that currently have vacancies (by cadre code)
    $vacant_cadres = [];
    foreach ($post_remaining as $code => $p) {
        // compute total seats left (MQ + CFF + EM + PHC)
        $left = 0;
        $left += ($p['MQ'] ?? 0);
        $left += ($p['CFF'] ?? 0);
        $left += ($p['EM'] ?? 0);
        $left += ($p['PHC'] ?? 0);
        if ($left > 0) {
            // Need to know the cadre short name for this code
            $vacant_cadres[$code] = $p['cadre'];
        }
    }

    // Try filling general vacancies first (pull from general_waiting)
    foreach ($vacant_cadres as $cadreCode => $cadreShort) {
        // If this cadre is general
        if (isset($general_cadres['GENERAL'][$cadreShort])) {

            // Sort waiting list for this cadre by gen_merit ASC then by choice_priority
            if (!empty($general_waiting[$cadreShort])) {
                usort($general_waiting[$cadreShort], function($a,$b){
                    if ($a['gen_merit'] === $b['gen_merit']) {
                        return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
                    }
                    return $a['gen_merit'] <=> $b['gen_merit'];
                });

                // find the first waiting candidate who is still unassigned
                while (!empty($general_waiting[$cadreShort]) && ($post_remaining[$cadreCode]['MQ'] ?? 0) + ($post_remaining[$cadreCode]['CFF'] ?? 0) + ($post_remaining[$cadreCode]['EM'] ?? 0) + ($post_remaining[$cadreCode]['PHC'] ?? 0) > 0) {
                    $entry = array_shift($general_waiting[$cadreShort]);
                    $cand = $entry['candidate'];
                    $reg = $cand['reg_no'];
                    if (!empty($assigned_regno[$reg])) continue; // skip already assigned

                    // Check MERIT first if candidate has general_merit_position and MQ remains
                    if (!empty($cand['general_merit_position']) && ($post_remaining[$cadreCode]['MQ'] ?? 0) > 0) {
                        $post_remaining[$cadreCode]['MQ']--;
                        $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$cadreCode,'quota'=>'MERIT','type'=>'GENERAL'];
                        $assigned_regno[$reg] = true;
                        $changed = true;
                        continue;
                    }

                    // otherwise try candidate's eligible quotas in order
                    foreach (['CFF','EM','PHC'] as $q) {
                        if (!empty($cand['quota'][$q]) && (($post_remaining[$cadreCode][$q] ?? 0) > 0)) {
                            $post_remaining[$cadreCode][$q]--;
                            $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$cadreCode,'quota'=>$q,'type'=>'GENERAL'];
                            $assigned_regno[$reg] = true;
                            $changed = true;
                            break;
                        }
                    }
                }
            }
        }

        // If this cadre is technical
        if (isset($technical_cadres['TECHNICAL'][$cadreShort])) {

            // Sort tech waiting by tech_merit ascending (tie-breaker: choice_priority)
            if (!empty($tech_waiting[$cadreShort])) {
                usort($tech_waiting[$cadreShort], function($a,$b){
                    if ($a['tech_merit'] === $b['tech_merit']) {
                        return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
                    }
                    return $a['tech_merit'] <=> $b['tech_merit'];
                });

                while (!empty($tech_waiting[$cadreShort]) && ($post_remaining[$cadreCode]['MQ'] ?? 0) + ($post_remaining[$cadreCode]['CFF'] ?? 0) + ($post_remaining[$cadreCode]['EM'] ?? 0) + ($post_remaining[$cadreCode]['PHC'] ?? 0) > 0) {
                    $entry = array_shift($tech_waiting[$cadreShort]);
                    $cand = $entry['candidate'];
                    $reg = $cand['reg_no'];
                    if (!empty($assigned_regno[$reg])) continue;

                    // MERIT seat
                    if (($post_remaining[$cadreCode]['MQ'] ?? 0) > 0) {
                        $post_remaining[$cadreCode]['MQ']--;
                        $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$cadreCode,'quota'=>'MERIT','type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
                        $assigned_regno[$reg] = true;
                        $changed = true;
                        continue;
                    }

                    // quotas
                    foreach (['CFF','EM','PHC'] as $q) {
                        if (!empty($cand['quota'][$q]) && (($post_remaining[$cadreCode][$q] ?? 0) > 0)) {
                            $post_remaining[$cadreCode][$q]--;
                            $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$cadreCode,'quota'=>$q,'type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
                            $assigned_regno[$reg] = true;
                            $changed = true;
                            break;
                        }
                    }
                }
            }
        }
    } // end foreach vacant cadres
} // end while changed

// ----------------- 5) BUILD FINAL ARRAYS (GENERAL, TECHNICAL, UNASSIGNED) -----------------

$final_general = [];
$final_technical = [];
$final_unassigned = [];

// Aggregate assigned allocations by type
foreach ($final_allocation as $entry) {
    if ($entry['type'] === 'GENERAL') $final_general[] = $entry;
    elseif ($entry['type'] === 'TECHNICAL') $final_technical[] = $entry;
}

// Add unassigned candidates
foreach ($candidates as $cand) {
    if (empty($assigned_regno[$cand['reg_no']])) {
        $final_unassigned[] = ['candidate'=>$cand,'cadre'=>null,'quota'=>null,'type'=>null];
    }
}

// ----------------- 6) FINAL SORT: By cadre CODE ascending, then by merit within each cadre -----------------

// helper: group by cadre_code then sort
function sort_by_cadre_code_then_merit(&$arr, $isTechnical = false) {
    // group
    $groups = [];
    foreach ($arr as $entry) {
        $code = $entry['cadre_code'] ?? PHP_INT_MAX;
        $groups[$code][] = $entry;
    }
    // sort groups by code asc
    ksort($groups, SORT_NUMERIC);
    $out = [];
    foreach ($groups as $code => $entries) {
        // sort within by appropriate merit
        usort($entries, function($a,$b) use ($isTechnical) {
            if ($isTechnical) {
                $va = $a['tech_merit'] ?? PHP_INT_MAX;
                $vb = $b['tech_merit'] ?? PHP_INT_MAX;
                return $va <=> $vb;
            } else {
                $va = $a['candidate']['general_merit_position'] ?? PHP_INT_MAX;
                $vb = $b['candidate']['general_merit_position'] ?? PHP_INT_MAX;
                return $va <=> $vb;
            }
        });
        foreach ($entries as $e) $out[] = $e;
    }
    $arr = $out;
}

sort_by_cadre_code_then_merit($final_general, false);
sort_by_cadre_code_then_merit($final_technical, true);

// ----------------- 7) OUTPUT (simple textual debug output) -----------------
/*echo "<h3>--- FINAL GENERAL ASSIGNMENTS (sorted by cadre code asc) ---</h3><pre>";
foreach ($final_general as $r) {
    $cand = $r['candidate'];
    $code = $r['cadre_code'] ?? '-';
    $quota = $r['quota'] ?? $r['quota'] ?? 'MERIT';
    $gm = $cand['general_merit_position'] ?? '-';
    echo "{$code} | {$r['cadre']} | {$cand['reg_no']} | quota: {$quota} | gen_merit: {$gm}\n";
}
echo "</pre>";

echo "<h3>--- FINAL TECHNICAL ASSIGNMENTS (sorted by cadre code asc) ---</h3><pre>";
foreach ($final_technical as $r) {
    $cand = $r['candidate'];
    $code = $r['cadre_code'] ?? '-';
    $quota = $r['quota'] ?? 'MERIT';
    $tm = $r['tech_merit'] ?? ($cand['technical_merit_position'][$r['cadre']] ?? '-');
    echo "{$code} | {$r['cadre']} | {$cand['reg_no']} | quota: {$quota} | tech_merit: {$tm}\n";
}
echo "</pre>";

echo "<h3>--- UNASSIGNED CANDIDATES ---</h3><pre>";
foreach ($final_unassigned as $r) echo $r['candidate']['reg_no']."\n";
echo "</pre>";

// Optional: debug: remaining posts snapshot
echo "<h3>--- POST REMAINING SNAPSHOT ---</h3><pre>";
echo htmlspecialchars(json_encode($post_remaining, JSON_PRETTY_PRINT));
echo "</pre>";*/
