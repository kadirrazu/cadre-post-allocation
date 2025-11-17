<?php
// ----------------- ASSUMES these are defined above -----------------
// $general_cadres, $technical_cadres, $post_available, $candidates

// ----------------- PREP & NORMALIZATION -----------------

$logs = []; // push log strings for traceability

// index posts by short cadre -> numeric code
$postsByCadre = [];
foreach ($post_available as $code => $p) {
    $postsByCadre[$p['cadre']] = $code;
}

// ensure candidate fields are normalized
foreach ($candidates as &$candidate) {
    // quota normalization
    if (!isset($candidate['quota']) || !is_array($candidate['quota'])) {
        $candidate['quota'] = ['CFF'=>false,'EM'=>false,'PHC'=>false];
    } else {
        foreach (['CFF','EM','PHC'] as $q) if (!array_key_exists($q, $candidate['quota'])) $candidate['quota'][$q] = false;
    }
    // technical merit normalization
    if (!isset($candidate['technical_merit_position']) || !is_array($candidate['technical_merit_position'])) {
        $candidate['technical_merit_position'] = [];
    }
    // normalize choices to uppercase tokens array
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

// working copy of posts to mutate
$post_remaining = $post_available;

// keys
$general_keys = array_keys($general_cadres['GENERAL']);
$technical_keys = array_keys($technical_cadres['TECHNICAL']);

// helper: highest choice type for GT routing
function highest_choice_type($cand, $general_keys, $technical_keys) {
    foreach ($cand['choices'] as $c) {
        if (in_array($c, $general_keys, true)) return 'GENERAL';
        if (in_array($c, $technical_keys, true)) return 'TECHNICAL';
    }
    return null;
}

// helper: primary merit for global ordering (for NM filling)
function primary_merit_value($cand) {
    if (!empty($cand['general_merit_position'])) return $cand['general_merit_position'];
    if (!empty($cand['technical_merit_position']) && is_array($cand['technical_merit_position'])) {
        return min($cand['technical_merit_position']);
    }
    return PHP_INT_MAX;
}

// ----------------- FLOW ROUTING (based on GT first choice) -----------------

$gen_pool = [];   // GG + GT whose first/earliest recognized choice is GENERAL
$tech_pool = [];  // TT + GT whose first recognized choice is TECHNICAL

foreach ($candidates as $cand) {
    if ($cand['cadre_category'] === 'GG') {
        $gen_pool[] = $cand;
    } elseif ($cand['cadre_category'] === 'TT') {
        $tech_pool[] = $cand;
    } elseif ($cand['cadre_category'] === 'GT') {
        $hc = highest_choice_type($cand, $general_keys, $technical_keys);
        if ($hc === 'GENERAL') {
            $gen_pool[] = $cand;
        } elseif ($hc === 'TECHNICAL') {
            $tech_pool[] = $cand;
        } else {
            // fallback: general
            $gen_pool[] = $cand;
        }
    }
}

// ----------------- 1) GENERAL ALLOCATION (MERIT -> CFF -> EM -> PHC) -----------------

// create gen_choices per candidate
foreach ($gen_pool as &$g) {
    $g['gen_choices'] = array_values(array_filter($g['choices'], fn($c)=> in_array($c, $general_keys, true)));
}
unset($g);

// sort gen_pool by general merit ascending (null treated as big value)
usort($gen_pool, fn($a,$b)=> ($a['general_merit_position'] ?? PHP_INT_MAX) <=> ($b['general_merit_position'] ?? PHP_INT_MAX));

// outputs + trackers
$final_allocation = [];
$assigned_regno = []; // map reg_no => ['cadre'=>..., 'type'=>..., 'cadre_code'=>..., 'quota'=>...]
$general_waiting = []; // per-general-cadre waiting list entries for move-ups

foreach ($gen_pool as $cand) {
    $assigned = false;
    $reg = $cand['reg_no'];

    foreach ($cand['gen_choices'] as $choice) {
        if (!isset($postsByCadre[$choice])) continue;
        $postCode = $postsByCadre[$choice];

        // MERIT (only if candidate has general_merit_position)
        if (!empty($cand['general_merit_position']) && (($post_remaining[$postCode]['MQ'] ?? 0) > 0)) {
            $post_remaining[$postCode]['MQ']--;
            $assigned = true;
            $assigned_regno[$reg] = ['cadre'=>$choice,'type'=>'GENERAL','cadre_code'=>$postCode,'quota'=>'MERIT'];
            $final_allocation[] = ['candidate'=>$cand,'cadre'=>$choice,'cadre_code'=>$postCode,'quota'=>'MERIT','type'=>'GENERAL'];
            $logs[] = "[GENERAL-MERIT] {$reg} assigned to {$choice} (code {$postCode}) by MERIT";
            break;
        }

        // QUOTA order
        foreach (['CFF','EM','PHC'] as $q) {
            if (!empty($cand['quota'][$q]) && (($post_remaining[$postCode][$q] ?? 0) > 0)) {
                $post_remaining[$postCode][$q]--;
                $assigned = true;
                $assigned_regno[$reg] = ['cadre'=>$choice,'type'=>'GENERAL','cadre_code'=>$postCode,'quota'=>$q];
                $final_allocation[] = ['candidate'=>$cand,'cadre'=>$choice,'cadre_code'=>$postCode,'quota'=>$q,'type'=>'GENERAL'];
                $logs[] = "[GENERAL-QUOTA] {$reg} assigned to {$choice} (code {$postCode}) by quota {$q}";
                break 2;
            }
        }
    }

    if (!$assigned) {
        // add to waiting lists for each general choice
        foreach ($cand['gen_choices'] as $ci => $choice) {
            if (!isset($postsByCadre[$choice])) continue;
            $general_waiting[$choice][] = [
                'candidate' => $cand,
                'gen_merit' => $cand['general_merit_position'] ?? PHP_INT_MAX,
                'choice_priority' => $ci
            ];
        }
    }
}

// ----------------- 2) TECHNICAL ALLOCATION (per-cadre by tech merit) -----------------

// Build per-cadre applicant lists from tech_pool
$tech_applicants_by_cadre = [];

foreach ($tech_pool as $cand) {
    foreach ($cand['choices'] as $idx => $choice) {
        if (!isset($technical_cadres['TECHNICAL'][$choice])) continue;
        if (!isset($cand['technical_merit_position'][$choice])) continue; // must have tech merit
        $tech_applicants_by_cadre[$choice][] = [
            'candidate' => $cand,
            'tech_merit' => $cand['technical_merit_position'][$choice],
            'choice_priority' => $idx
        ];
    }
}

// Also include any TT/GT candidates not in tech_pool (but unassigned) who have technical merit entries
foreach ($candidates as $cand) {
    if (!in_array($cand['cadre_category'], ['TT','GT'], true)) continue;
    // skip if already assigned (by general flow)
    if (isset($assigned_regno[$cand['reg_no']])) continue;
    foreach ($cand['choices'] as $idx => $choice) {
        if (!isset($technical_cadres['TECHNICAL'][$choice])) continue;
        if (!isset($cand['technical_merit_position'][$choice])) continue;
        // avoid duplicates
        $exists = false;
        if (isset($tech_applicants_by_cadre[$choice])) {
            foreach ($tech_applicants_by_cadre[$choice] as $e) {
                if ($e['candidate']['reg_no'] === $cand['reg_no']) { $exists = true; break; }
            }
        }
        if (!$exists) $tech_applicants_by_cadre[$choice][] = ['candidate'=>$cand,'tech_merit'=>$cand['technical_merit_position'][$choice],'choice_priority'=>$idx];
    }
}

// sort each cadre applicants by technical merit asc, tie-breaker choice_priority
foreach ($tech_applicants_by_cadre as $cadre => &$list) {
    usort($list, function($a,$b){
        if ($a['tech_merit'] === $b['tech_merit']) return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
        return $a['tech_merit'] <=> $b['tech_merit'];
    });
}
unset($list);

// tech waiting lists for move-up
$tech_waiting = [];

// allocate tech per-cadre
foreach ($tech_applicants_by_cadre as $cadre => $applicants) {
    if (!isset($postsByCadre[$cadre])) continue;
    $postCode = $postsByCadre[$cadre];

    foreach ($applicants as $entry) {
        $cand = $entry['candidate'];
        $reg = $cand['reg_no'];
        if (isset($assigned_regno[$reg])) continue; // skip if already assigned (general flow)
        // MERIT
        if (($post_remaining[$postCode]['MQ'] ?? 0) > 0) {
            $post_remaining[$postCode]['MQ']--;
            $assigned_regno[$reg] = ['cadre'=>$cadre,'type'=>'TECHNICAL','cadre_code'=>$postCode,'quota'=>'MERIT','tech_merit'=>$entry['tech_merit']];
            $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadre,'cadre_code'=>$postCode,'quota'=>'MERIT','type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
            $logs[] = "[TECH-MERIT] {$reg} assigned to {$cadre} (code {$postCode}) by MERIT (tech={$entry['tech_merit']})";
            continue;
        }
        // quotas
        $got = false;
        foreach (['CFF','EM','PHC'] as $q) {
            if (!empty($cand['quota'][$q]) && (($post_remaining[$postCode][$q] ?? 0) > 0)) {
                $post_remaining[$postCode][$q]--;
                $assigned_regno[$reg] = ['cadre'=>$cadre,'type'=>'TECHNICAL','cadre_code'=>$postCode,'quota'=>$q,'tech_merit'=>$entry['tech_merit']];
                $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadre,'cadre_code'=>$postCode,'quota'=>$q,'type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
                $logs[] = "[TECH-QUOTA] {$reg} assigned to {$cadre} (code {$postCode}) by quota {$q} (tech={$entry['tech_merit']})";
                $got = true;
                break;
            }
        }
        if (!$got) {
            // add to tech waiting list
            $tech_waiting[$cadre][] = ['candidate'=>$cand,'tech_merit'=>$entry['tech_merit'],'choice_priority'=>$entry['choice_priority']];
        }
    }
}

// ----------------- 3) MOVE-UP / REBALANCING LOOP -----------------

$changed = true;
while ($changed) {
    $changed = false;

    // compute vacancy map: cadre_code => cadreShort for those with any seats left
    $vacantMap = [];
    foreach ($post_remaining as $code => $p) {
        $left = ($p['MQ'] ?? 0) + ($p['CFF'] ?? 0) + ($p['EM'] ?? 0) + ($p['PHC'] ?? 0);
        if ($left > 0) $vacantMap[$code] = $p['cadre'];
    }

    // Iterate through vacant cadres and try to pull waiting lists
    foreach ($vacantMap as $code => $cadreShort) {
        // GENERAL vacancy
        if (isset($general_cadres['GENERAL'][$cadreShort]) && !empty($general_waiting[$cadreShort])) {
            // sort waiting list by gen_merit asc then choice_priority
            usort($general_waiting[$cadreShort], function($a,$b){
                if ($a['gen_merit'] === $b['gen_merit']) return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
                return $a['gen_merit'] <=> $b['gen_merit'];
            });

            // iterate while seats left
            while ((($post_remaining[$code]['MQ'] ?? 0) + ($post_remaining[$code]['CFF'] ?? 0) + ($post_remaining[$code]['EM'] ?? 0) + ($post_remaining[$code]['PHC'] ?? 0) ) > 0 && !empty($general_waiting[$cadreShort])) {
                $entry = array_shift($general_waiting[$cadreShort]);
                $cand = $entry['candidate'];
                $reg = $cand['reg_no'];
                if (isset($assigned_regno[$reg])) continue;

                // MERIT
                if (!empty($cand['general_merit_position']) && ($post_remaining[$code]['MQ'] ?? 0) > 0) {
                    $post_remaining[$code]['MQ']--;
                    $assigned_regno[$reg] = ['cadre'=>$cadreShort,'type'=>'GENERAL','cadre_code'=>$code,'quota'=>'MERIT'];
                    $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$code,'quota'=>'MERIT','type'=>'GENERAL'];
                    $logs[] = "[MOVE-UP GENERAL MERIT] {$reg} pulled into {$cadreShort} (code {$code}) by MERIT";
                    $changed = true;
                    continue;
                }

                // quotas
                foreach (['CFF','EM','PHC'] as $q) {
                    if (!empty($cand['quota'][$q]) && (($post_remaining[$code][$q] ?? 0) > 0)) {
                        $post_remaining[$code][$q]--;
                        $assigned_regno[$reg] = ['cadre'=>$cadreShort,'type'=>'GENERAL','cadre_code'=>$code,'quota'=>$q];
                        $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$code,'quota'=>$q,'type'=>'GENERAL'];
                        $logs[] = "[MOVE-UP GENERAL QUOTA] {$reg} pulled into {$cadreShort} (code {$code}) by quota {$q}";
                        $changed = true;
                        break;
                    }
                }
            }
        }

        // TECH vacancy
        if (isset($technical_cadres['TECHNICAL'][$cadreShort]) && !empty($tech_waiting[$cadreShort])) {
            usort($tech_waiting[$cadreShort], function($a,$b){
                if ($a['tech_merit'] === $b['tech_merit']) return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
                return $a['tech_merit'] <=> $b['tech_merit'];
            });

            while ((($post_remaining[$code]['MQ'] ?? 0) + ($post_remaining[$code]['CFF'] ?? 0) + ($post_remaining[$code]['EM'] ?? 0) + ($post_remaining[$code]['PHC'] ?? 0) ) > 0 && !empty($tech_waiting[$cadreShort])) {
                $entry = array_shift($tech_waiting[$cadreShort]);
                $cand = $entry['candidate'];
                $reg = $cand['reg_no'];
                if (isset($assigned_regno[$reg])) continue;

                // MERIT
                if (($post_remaining[$code]['MQ'] ?? 0) > 0) {
                    $post_remaining[$code]['MQ']--;
                    $assigned_regno[$reg] = ['cadre'=>$cadreShort,'type'=>'TECHNICAL','cadre_code'=>$code,'quota'=>'MERIT','tech_merit'=>$entry['tech_merit']];
                    $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$code,'quota'=>'MERIT','type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
                    $logs[] = "[MOVE-UP TECH MERIT] {$reg} pulled into {$cadreShort} (code {$code}) by MERIT (tech={$entry['tech_merit']})";
                    $changed = true;
                    continue;
                }

                // quotas
                foreach (['CFF','EM','PHC'] as $q) {
                    if (!empty($cand['quota'][$q]) && (($post_remaining[$code][$q] ?? 0) > 0)) {
                        $post_remaining[$code][$q]--;
                        $assigned_regno[$reg] = ['cadre'=>$cadreShort,'type'=>'TECHNICAL','cadre_code'=>$code,'quota'=>$q,'tech_merit'=>$entry['tech_merit']];
                        $final_allocation[] = ['candidate'=>$cand,'cadre'=>$cadreShort,'cadre_code'=>$code,'quota'=>$q,'type'=>'TECHNICAL','tech_merit'=>$entry['tech_merit']];
                        $logs[] = "[MOVE-UP TECH QUOTA] {$reg} pulled into {$cadreShort} (code {$code}) by quota {$q}";
                        $changed = true;
                        break;
                    }
                }
            }
        }
    } // end foreach vacancies
} // end while changed

// ----------------- 4) NM FILLING (Fill any remaining seats with unassigned candidates by primary merit) -----------------

// build list of remaining seats as array of [code, cadreShort, seat_type] where seat_type is 'MQ' or quota key
$remainingSeats = [];
foreach ($post_remaining as $code => $p) {
    if (($p['MQ'] ?? 0) > 0) {
        for ($i=0;$i<$p['MQ'];$i++) $remainingSeats[] = ['code'=>$code,'cadre'=>$p['cadre'],'seat'=>'MQ'];
    }
    foreach (['CFF','EM','PHC'] as $q) {
        if (($p[$q] ?? 0) > 0) {
            for ($i=0;$i<$p[$q];$i++) $remainingSeats[] = ['code'=>$code,'cadre'=>$p['cadre'],'seat'=>$q];
        }
    }
}

// if there are seats left, pull from non-assigned candidates by primary merit
if (!empty($remainingSeats)) {
    // build unassigned candidates list
    $unassigned = [];
    foreach ($candidates as $cand) {
        if (!isset($assigned_regno[$cand['reg_no']])) {
            $unassigned[] = $cand;
        }
    }

    // sort unassigned by primary merit ascending (lower = better)
    usort($unassigned, function($a,$b){
        $pa = primary_merit_value($a);
        $pb = primary_merit_value($b);
        return $pa <=> $pb;
    });

    // iterate seats in any order but try to respect candidate's choice order:
    // for each candidate, try their choices from top to bottom, if a seat of any type exists, fill it and mark NM
    foreach ($unassigned as $cand) {
        if (empty($remainingSeats)) break;
        $reg = $cand['reg_no'];

        // go through candidate's choices in order
        foreach ($cand['choices'] as $choice) {
            // must map choice -> code
            if (!isset($postsByCadre[$choice])) continue;
            $code = $postsByCadre[$choice];

            // check if there's any remaining seat record for this code
            $foundIndex = null;
            foreach ($remainingSeats as $idx => $seat) {
                if ($seat['code'] === $code) { $foundIndex = $idx; break; }
            }

            if ($foundIndex === null) continue;

            // if this is a TECH choice, candidate must have technical merit entry for that cadre
            if (isset($technical_cadres['TECHNICAL'][$choice])) {
                if (!isset($cand['technical_merit_position'][$choice])) continue;
            }

            // Assign NM: consume that seat and record as quota = 'NM'
            $seat = $remainingSeats[$foundIndex];
            // remove seat from remainingSeats
            array_splice($remainingSeats, $foundIndex, 1);

            // decrement actual post_remaining bucket accordingly (so snapshot stays accurate)
            if ($seat['seat'] === 'MQ') {
                $post_remaining[$seat['code']]['MQ'] = max(0, ($post_remaining[$seat['code']]['MQ'] ?? 0) - 1);
            } else {
                $post_remaining[$seat['code']][$seat['seat']] = max(0, ($post_remaining[$seat['code']][$seat['seat']] ?? 0) - 1);
            }

            // record assignment
            // decide type by cadre presence in technical/general
            $type = isset($technical_cadres['TECHNICAL'][$choice]) ? 'TECHNICAL' : 'GENERAL';
            $assigned_regno[$reg] = ['cadre'=>$choice,'type'=>$type,'cadre_code'=>$code,'quota'=>'NM'];
            // tech_merit if available
            $entry = ['candidate'=>$cand,'cadre'=>$choice,'cadre_code'=>$code,'quota'=>'NM','type'=>$type];
            if ($type === 'TECHNICAL') $entry['tech_merit'] = $cand['technical_merit_position'][$choice] ?? null;
            $final_allocation[] = $entry;

            $logs[] = "[NM ASSIGN] {$reg} assigned to {$choice} (code {$code}) as NM seat ({$seat['seat']})";

            // candidate assigned -> break to next unassigned candidate
            break;
        }
    }
}

// ----------------- 5) COLLATE FINAL ARRAYS & SORT BY CADRE CODE ASC -----------------

$final_general = [];
$final_technical = [];
$final_unassigned = [];

// Aggregate final_allocation by type
foreach ($final_allocation as $entry) {
    if ($entry['type'] === 'GENERAL') $final_general[] = $entry;
    elseif ($entry['type'] === 'TECHNICAL') $final_technical[] = $entry;
}

// Unassigned are those not in assigned_regno
foreach ($candidates as $cand) {
    if (!isset($assigned_regno[$cand['reg_no']])) $final_unassigned[] = ['candidate'=>$cand,'cadre'=>null,'quota'=>null,'type'=>null];
}

// Sorting helpers: group by cadre_code then sort groups by code ascending, then by merit inside
function sort_by_cadre_code_then_merit(&$arr, $isTech = false) {
    $groups = [];
    foreach ($arr as $e) {
        $code = $e['cadre_code'] ?? PHP_INT_MAX;
        $groups[$code][] = $e;
    }
    ksort($groups, SORT_NUMERIC);
    $out = [];
    foreach ($groups as $code => $entries) {
        usort($entries, function($a,$b) use ($isTech) {
            if ($isTech) {
                $va = $a['tech_merit'] ?? ($a['candidate']['technical_merit_position'][$a['cadre']] ?? PHP_INT_MAX);
                $vb = $b['tech_merit'] ?? ($b['candidate']['technical_merit_position'][$b['cadre']] ?? PHP_INT_MAX);
                return $va <=> $vb;
            } else {
                $va = $a['candidate']['general_merit_position'] ?? PHP_INT_MAX;
                $vb = $b['candidate']['general_merit_position'] ?? PHP_INT_MAX;
                return $va <=> $vb;
            }
        });
        foreach ($entries as $ent) $out[] = $ent;
    }
    $arr = $out;
}

sort_by_cadre_code_then_merit($final_general, false);
sort_by_cadre_code_then_merit($final_technical, true);

// ----------------- 6) OUTPUT (simple HTML debug + logs) -----------------

/*echo "<!doctype html><html><head><meta charset='utf-8'><title>Allocation Debug</title>";
echo "<style>body{font-family:Arial,Helvetica,sans-serif}pre{background:#f4f4f4;padding:8px;border-radius:6px}</style>";
echo "</head><body>";
echo "<h2>Final General Assignments (by cadre code asc)</h2><pre>";
foreach ($final_general as $r) {
    $cand = $r['candidate'];
    $code = $r['cadre_code'] ?? '-';
    $quota = $r['quota'] ?? 'MERIT';
    $gm = $cand['general_merit_position'] ?? '-';
    echo "{$code} | {$r['cadre']} | {$cand['reg_no']} | quota: {$quota} | gen_merit: {$gm}\n";
}
echo "</pre>";

echo "<h2>Final Technical Assignments (by cadre code asc)</h2><pre>";
foreach ($final_technical as $r) {
    $cand = $r['candidate'];
    $code = $r['cadre_code'] ?? '-';
    $quota = $r['quota'] ?? 'MERIT';
    $tm = $r['tech_merit'] ?? ($cand['technical_merit_position'][$r['cadre']] ?? '-');
    echo "{$code} | {$r['cadre']} | {$cand['reg_no']} | quota: {$quota} | tech_merit: {$tm}\n";
}
echo "</pre>";

echo "<h2>Unassigned Candidates</h2><pre>";
foreach ($final_unassigned as $r) {
    echo $r['candidate']['reg_no']."\n";
}
echo "</pre>";

echo "<h2>Logs (trace)</h2><pre>";
foreach ($logs as $L) echo htmlspecialchars($L) . "\n";
echo "</pre>";

echo "<h2>Post remaining snapshot</h2><pre>" . htmlspecialchars(json_encode($post_remaining, JSON_PRETTY_PRINT)) . "</pre>";

echo "</body></html>";*/
