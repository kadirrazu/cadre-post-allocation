<?php
/**
 * Simplified BCS allocation engine
 * - Posts contain manual per-quota seat counts (merit, freedom_fighter, ethnic, pc)
 * - Two merit lists:
 *     - general_merit_rank  (global rank; lower is better)
 *     - technical_merit: map cadreKey => rank (lower is better)
 * - Candidates categories: 'general', 'technical', 'both'
 * - Allocation order:
 *     1) Merit: allocate general posts by general merit; allocate technical posts by technical merit.
 *     2) Quotas: allocate quota seats (freedom_fighter, ethnic, pc) using general merit ordering among quota-holders.
 * - After allocations we run an iterative "cascade" step so that when technical vacancies open,
 *   candidates who prefer technical higher than their current allocation and have improved technical position
 *   can be moved. We loop until no changes or iteration cap is hit.
 *
 * This script uses small sample data. Replace arrays with DB queries for production.
 */

/* ------------------------
   CONFIG: posts with manual per-quota seats
   Fill seats per quota manually here (you asked this).
   Keys are arbitrary identifiers for cadres.
   ------------------------ */
$posts = [
    // 'cadreKey' => ['name'=>..., 'type'=>'general'|'technical',
    //                 'seats' => ['merit'=>int, 'freedom_fighter'=>int, 'ethnic'=>int, 'pc'=>int]]
    'admin' => ['name' => 'Administrative Service', 'type' => 'general',
                'seats' => ['merit'=>3, 'freedom_fighter'=>0, 'ethnic'=>0, 'pc'=>0]],
    'police' => ['name' => 'Police Service', 'type' => 'general',
                'seats' => ['merit'=>2, 'freedom_fighter'=>0, 'ethnic'=>0, 'pc'=>0]],
    'roads' => ['name' => 'Roads & Highways', 'type' => 'technical',
                'seats' => ['merit'=>2, 'freedom_fighter'=>0, 'ethnic'=>0, 'pc'=>0]],
    'telecom' => ['name' => 'Telecommunication', 'type' => 'technical',
                'seats' => ['merit'=>1, 'freedom_fighter'=>0, 'ethnic'=>0, 'pc'=>0]],
];

/* ------------------------
   CONFIG: technical requirements
   Map technical cadre to required subject codes
   ------------------------ */
$technical_requirements = [
    'roads' => [201],   // 201 = Civil Engineering
    'telecom' => [202], // 202 = Telecommunication/ECE
];

/* ------------------------
   SAMPLE CANDIDATES
   Each candidate:
     reg_no, name, category (general|technical|both),
     subject_codes (array),
     preferences (ordered array of cadreKeys),
     general_merit_rank (int),
     technical_merit (assoc cadreKey => rank),
     quota_flags ['ff'=>bool, 'ethnic'=>bool, 'pc'=>bool]
   ------------------------ */
$candidates = [
    [
        'reg_no'=>'10000001','name'=>'A Rahman','category'=>'both','subject_codes'=>[201],
        'preferences'=>['roads','admin','police'],
        'general_merit_rank'=>10,'technical_merit'=>['roads'=>5],'quota_flags'=>['ff'=>false,'ethnic'=>false,'pc'=>false]
    ],
    [
        'reg_no'=>'10000002','name'=>'B Karim','category'=>'general','subject_codes'=>[],
        'preferences'=>['admin','police'],
        'general_merit_rank'=>2,'technical_merit'=>[],'quota_flags'=>['ff'=>false,'ethnic'=>false,'pc'=>false]
    ],
    [
        'reg_no'=>'10000003','name'=>'C Begum','category'=>'technical','subject_codes'=>[202],
        'preferences'=>['telecom','roads'],
        'general_merit_rank'=>20,'technical_merit'=>['telecom'=>1,'roads'=>30],'quota_flags'=>['ff'=>false,'ethnic'=>true,'pc'=>false]
    ],
    [
        'reg_no'=>'10000004','name'=>'D Khan','category'=>'both','subject_codes'=>[301],
        'preferences'=>['police','admin','roads'],
        'general_merit_rank'=>5,'technical_merit'=>['roads'=>4],'quota_flags'=>['ff'=>true,'ethnic'=>false,'pc'=>false]
    ],
    [
        'reg_no'=>'10000005','name'=>'E Ahmed','category'=>'general','subject_codes'=>[],
        'preferences'=>['police','admin'],
        'general_merit_rank'=>15,'technical_merit'=>[],'quota_flags'=>['ff'=>false,'ethnic'=>false,'pc'=>true]
    ],
];

/* ------------------------
   Helper structures
   We'll maintain:
     - $assignments[reg_no] = ['cadre'=>$cadreKey, 'source'=>'merit'|'quota', 'quota'=>'freedom_fighter'|... (if source==quota), 'pref_index'=>int]
     - $seats_remaining[cadreKey] = seats map (merit/quota...) updated as we allocate
   ------------------------ */
$seats_remaining = [];
foreach ($posts as $k => $p) {
    // copy seats so we can decrement
    $seats_remaining[$k] = $p['seats'];
}
$assignments = []; // reg_no => assignment info

/* ------------------------
   Helper functions
   ------------------------ */

/**
 * Check if candidate is eligible for a given cadre (subject-code check for technical)
 */
function eligible_for_cadre($candidate, $cadreKey, $posts, $technical_requirements) {
    if (!isset($posts[$cadreKey])) return false;
    $type = $posts[$cadreKey]['type'];
    if ($type === 'general') {
        // general cadre: candidate must be general or both
        return in_array($candidate['category'], ['general','both']);
    } else {
        // technical: candidate must be technical or both AND have required subject code
        if (!in_array($candidate['category'], ['technical','both'])) return false;
        if (!isset($technical_requirements[$cadreKey])) {
            return true; // no requirement set -> allow
        }
        return count(array_intersect($candidate['subject_codes'], $technical_requirements[$cadreKey])) > 0;
    }
}

/**
 * Utility: find candidate by reg_no in the $candidates array
 */
function &find_candidate(&$candidates, $reg_no) {
    foreach ($candidates as &$c) {
        if ($c['reg_no'] === $reg_no) return $c;
    }
    $null = null;
    return $null;
}

/* ------------------------
   1) MERIT ALLOCATION
   We'll do merit allocation in two substeps:
     A) General cadres by GENERAL merit: iterate global merit list (ascending rank),
        for each candidate assign to their highest-preference *general* cadre with available MERIT seat.
     B) Technical cadres by TECHNICAL merit per cadre: for each technical cadre, sort eligible candidates by that cadre's technical_merit.
        Assign top candidates to that cadre's MERIT seats; if a candidate is already assigned to a lower-preference cadre and they prefer this technical cadre higher,
        we will move them (cascade). We'll do cascading later in an iterative phase to keep this code simple.
   ------------------------ */

/* --- (A) General cadres by global general merit --- */
// build list of candidates sorted by general_merit_rank ascending
$by_general = $candidates;
usort($by_general, function($a,$b){ return $a['general_merit_rank'] <=> $b['general_merit_rank']; });

foreach ($by_general as $cand) {
    $reg = $cand['reg_no'];
    // if already assigned (via earlier step) skip (none assigned yet in this phase)
    if (isset($assignments[$reg])) continue;
    // walk their preferences left-to-right to find first GENERAL cadre with MERIT seat and eligibility
    foreach ($cand['preferences'] as $i => $prefCadre) {
        if (!isset($posts[$prefCadre])) continue;
        if ($posts[$prefCadre]['type'] !== 'general') continue; // only general cadres in this substep
        if (!eligible_for_cadre($cand, $prefCadre, $posts, $technical_requirements)) continue;
        if ($seats_remaining[$prefCadre]['merit'] > 0) {
            // assign here by general merit
            $assignments[$reg] = ['cadre'=>$prefCadre, 'source'=>'merit', 'quota'=>null, 'pref_index'=>$i];
            $seats_remaining[$prefCadre]['merit'] -= 1;
            break;
        }
    }
}

/* --- (B) Technical cadres by each cadre's technical merit --- */
/*
 For each technical cadre:
   - build list of candidates who are eligible_for_cadre and who have a technical_merit value for this cadre
   - sort them by that technical_merit (ascending)
   - iterate and assign to MERIT seats where available.
   - If candidate is already assigned to some other cadre:
       * If they already hold a cadre that they prefer MORE than this technical cadre, we DO NOT displace them.
       * If this technical cadre is higher preference than their current assignment, we will move them here and free the old seat.
 The 'cascade' (moving freed seat to others) will be resolved later by an iterative rebalancer.
*/
foreach ($posts as $cadreKey => $pdata) {
    if ($pdata['type'] !== 'technical') continue;
    // build candidate pool with technical_merit for this cadre
    $pool = [];
    foreach ($candidates as $cand) {
        if (!eligible_for_cadre($cand, $cadreKey, $posts, $technical_requirements)) continue;
        if (!isset($cand['technical_merit'][$cadreKey])) continue; // must have technical rank for this cadre
        $pool[] = $cand;
    }
    // sort by technical merit ascending (lower rank = better)
    usort($pool, function($a,$b) use ($cadreKey) {
        return $a['technical_merit'][$cadreKey] <=> $b['technical_merit'][$cadreKey];
    });
    // iterate pool and try to place into MERIT seats
    foreach ($pool as $cand) {
        if ($seats_remaining[$cadreKey]['merit'] <= 0) break;
        $reg = $cand['reg_no'];
        // If candidate unassigned => simply assign
        if (!isset($assignments[$reg])) {
            $assignments[$reg] = ['cadre'=>$cadreKey, 'source'=>'merit', 'quota'=>null, 'pref_index'=>array_search($cadreKey,$cand['preferences'])];
            $seats_remaining[$cadreKey]['merit'] -= 1;
            continue;
        }
        // Candidate already assigned somewhere: only reassign if this technical cadre is a *higher* preference than their current allocation
        $current = $assignments[$reg];
        $cur_pref_index = $current['pref_index'];
        $cand_pref_index = array_search($cadreKey, $cand['preferences']);
        // If candidate does not list this cadre in preferences (should rarely happen), prefer not to move
        if ($cand_pref_index === false) continue;
        if ($cand_pref_index < $cur_pref_index) {
            // They prefer this technical cadre more than their current assigned cadre.
            // Reassign: free previous seat and occupy this technical seat.
            // Freeing will be handled (we decrement current seat counts here and increment seat counts there)
            $oldCadre = $current['cadre'];
            // Free previous seat (note: the previous assignment's seat type may be merit or quota; here it was merit)
            if ($current['source'] === 'merit') {
                $seats_remaining[$oldCadre]['merit'] += 1;
            } elseif ($current['source'] === 'quota' && $current['quota']) {
                $seats_remaining[$oldCadre][$current['quota']] += 1;
            }
            // Assign candidate into this technical cadre (merit)
            $assignments[$reg] = ['cadre'=>$cadreKey, 'source'=>'merit', 'quota'=>null, 'pref_index'=>$cand_pref_index];
            $seats_remaining[$cadreKey]['merit'] -= 1;
            // Note: we freed a seat at $oldCadre which might be filled later in the cascade phases
        }
    }
}

/* ------------------------
   2) QUOTA ALLOCATION
   For each quota (ff, ethnic, pc), in that order, allocate to quota-holders ordered by GENERAL merit (asc).
   Quota allocation respects preferences left-to-right. For technical cadres, eligibility still requires subject codes.
   If a quota candidate is already assigned by merit, we DO NOT reassign them (they keep that seat).
   ------------------------ */

$quota_list = ['freedom_fighter','ethnic','pc'];
foreach ($quota_list as $quotaKey) {
    // build list of candidates who have this quota
    $pool = [];
    foreach ($candidates as $cand) {
        $has = false;
        if ($quotaKey === 'freedom_fighter' && !empty($cand['quota_flags']['ff'])) $has = true;
        if ($quotaKey === 'ethnic' && !empty($cand['quota_flags']['ethnic'])) $has = true;
        if ($quotaKey === 'pc' && !empty($cand['quota_flags']['pc'])) $has = true;
        if ($has) $pool[] = $cand;
    }
    // sort pool by general_merit_rank ascending (quota ordering uses general merit)
    usort($pool, function($a,$b){ return $a['general_merit_rank'] <=> $b['general_merit_rank']; });

    // allocate for each candidate in that order to their highest-preference available quota seat
    foreach ($pool as $cand) {
        $reg = $cand['reg_no'];
        // skip if already assigned (merit or earlier quota)
        if (isset($assignments[$reg])) continue;
        // scan preferences left to right
        foreach ($cand['preferences'] as $i => $prefCadre) {
            if (!isset($posts[$prefCadre])) continue;
            // must be eligible for cadre
            if (!eligible_for_cadre($cand, $prefCadre, $posts, $technical_requirements)) continue;
            // check if cadre has seat in this quota
            if ($seats_remaining[$prefCadre][$quotaKey] > 0) {
                // Assign by quota
                $assignments[$reg] = ['cadre'=>$prefCadre, 'source'=>'quota', 'quota'=>$quotaKey, 'pref_index'=>$i];
                $seats_remaining[$prefCadre][$quotaKey] -= 1;
                break;
            }
        }
    }
}

/* ------------------------
   3) CASCADE / REBALANCE LOOP
   When we reassigned earlier (esp. technical stage) we freed seats. Freed merit seats (or quota seats)
   might be filled by other candidates who prefer that cadre AND whose merit/quota ordering allows it.
   We'll run an iterative loop to try to settle the system. This is a conservative approach:
     - Try to fill any empty MERIT seats by appropriate merit lists
     - Try to fill any empty QUOTA seats only from remaining quota-holders (by general merit)
   Repeat until no change or iteration cap hit.
   This also handles the special case you described: when someone moves out of a technical seat to general,
   the technical seat becomes free; the next-in-line technical candidate might prefer technical more than their current general seat,
   so we move them and continue.
   ------------------------ */

$changed = true;
$iteration = 0;
$max_iterations = 20; // safety cap
while ($changed && $iteration < $max_iterations) {
    $iteration++;
    $changed = false;

    // ---- A: Fill any merit seats in general cadres by global general merit among unassigned candidates ----
    // Build list of unassigned candidates sorted by general merit
    $unassigned_list = [];
    foreach ($candidates as $cand) {
        if (!isset($assignments[$cand['reg_no']])) $unassigned_list[] = $cand;
    }
    usort($unassigned_list, function($a,$b){ return $a['general_merit_rank'] <=> $b['general_merit_rank']; });

    foreach ($unassigned_list as $cand) {
        $reg = $cand['reg_no'];
        // try to find a general cadre MERIT seat in their preferences
        foreach ($cand['preferences'] as $i => $prefCadre) {
            if (!isset($posts[$prefCadre])) continue;
            if ($posts[$prefCadre]['type'] !== 'general') continue;
            if (!eligible_for_cadre($cand,$prefCadre,$posts,$technical_requirements)) continue;
            if ($seats_remaining[$prefCadre]['merit'] > 0) {
                // assign by merit
                $assignments[$reg] = ['cadre'=>$prefCadre,'source'=>'merit','quota'=>null,'pref_index'=>$i];
                $seats_remaining[$prefCadre]['merit'] -= 1;
                $changed = true;
                break;
            }
        }
    }

    // ---- B: Fill merit seats in technical cadres by technical merit among candidates (including currently assigned ones who might prefer technical more) ----
    foreach ($posts as $cadreKey => $pdata) {
        if ($pdata['type'] !== 'technical') continue;
        while ($seats_remaining[$cadreKey]['merit'] > 0) {
            // Build ordered list of candidates eligible for this cadre and not yet occupying this cadre
            // Sort by technical merit ascending
            $pool = [];
            foreach ($candidates as $cand) {
                if (!eligible_for_cadre($cand,$cadreKey,$posts,$technical_requirements)) continue;
                // candidate must have a technical rank for this cadre to be considered
                if (!isset($cand['technical_merit'][$cadreKey])) continue;
                $pool[] = $cand;
            }
            usort($pool, function($a,$b) use ($cadreKey) {
                return $a['technical_merit'][$cadreKey] <=> $b['technical_merit'][$cadreKey];
            });

            // find the best candidate from pool who can take this seat:
            // preference: an unassigned candidate, or a candidate assigned to a worse preference who prefers this cadre more than their current assigned cadre.
            $selected = null;
            foreach ($pool as $cand) {
                $reg = $cand['reg_no'];
                // if they already occupy this cadre, skip
                if (isset($assignments[$reg]) && $assignments[$reg]['cadre'] === $cadreKey) {
                    // someone already occupies this cadre (shouldn't happen because we check seats_remaining), but skip
                    continue;
                }
                $pref_index = array_search($cadreKey, $cand['preferences']);
                if ($pref_index === false) continue; // doesn't prefer it (ignore)
                if (!isset($assignments[$reg])) {
                    // unassigned candidate -> can take seat
                    $selected = ['cand'=>$cand,'action'=>'assign','pref_index'=>$pref_index];
                    break;
                } else {
                    // assigned somewhere: see if they prefer this cadre more
                    $cur = $assignments[$reg];
                    if ($pref_index < $cur['pref_index']) {
                        // they prefer this cadre more; allow moving
                        $selected = ['cand'=>$cand,'action'=>'move','pref_index'=>$pref_index];
                        break;
                    }
                }
            }

            if ($selected === null) {
                // nothing more to fill for this cadre's merit seats
                break;
            }

            // perform the assignment or move
            $cand = $selected['cand'];
            $reg = $cand['reg_no'];
            $pref_index = $selected['pref_index'];
            if (!isset($assignments[$reg])) {
                // assign
                $assignments[$reg] = ['cadre'=>$cadreKey,'source'=>'merit','quota'=>null,'pref_index'=>$pref_index];
                $seats_remaining[$cadreKey]['merit'] -= 1;
                $changed = true;
            } else {
                // move: free old seat and occupy this technical seat
                $old = $assignments[$reg];
                $oldCadre = $old['cadre'];
                // free old seat in its bucket
                if ($old['source'] === 'merit') {
                    $seats_remaining[$oldCadre]['merit'] += 1;
                } elseif ($old['source'] === 'quota' && $old['quota']) {
                    $seats_remaining[$oldCadre][$old['quota']] += 1;
                }
                // assign into technical cadre
                $assignments[$reg] = ['cadre'=>$cadreKey,'source'=>'merit','quota'=>null,'pref_index'=>$pref_index];
                $seats_remaining[$cadreKey]['merit'] -= 1;
                $changed = true;
                // freed seat in $oldCadre will be attempted to fill in the next loop iterations
            }
        } // end while seats_remaining for this technical cadre
    } // end foreach technical cadre

    // ---- C: Try filling remaining quota seats from still-unassigned quota-holders (by general merit) ----
    foreach ($quota_list as $quotaKey) {
        // build current list of quota-holders who are still unassigned
        $pool = [];
        foreach ($candidates as $cand) {
            $has = false;
            if ($quotaKey === 'freedom_fighter' && !empty($cand['quota_flags']['ff'])) $has = true;
            if ($quotaKey === 'ethnic' && !empty($cand['quota_flags']['ethnic'])) $has = true;
            if ($quotaKey === 'pc' && !empty($cand['quota_flags']['pc'])) $has = true;
            if (!$has) continue;
            if (isset($assignments[$cand['reg_no']])) continue; // skip already assigned
            $pool[] = $cand;
        }
        // order by general merit
        usort($pool, function($a,$b){ return $a['general_merit_rank'] <=> $b['general_merit_rank']; });

        foreach ($pool as $cand) {
            $reg = $cand['reg_no'];
            foreach ($cand['preferences'] as $i => $prefCadre) {
                if (!isset($posts[$prefCadre])) continue;
                if (!eligible_for_cadre($cand,$prefCadre,$posts,$technical_requirements)) continue;
                if ($seats_remaining[$prefCadre][$quotaKey] > 0) {
                    // assign by quota
                    $assignments[$reg] = ['cadre'=>$prefCadre,'source'=>'quota','quota'=>$quotaKey,'pref_index'=>$i];
                    $seats_remaining[$prefCadre][$quotaKey] -= 1;
                    $changed = true;
                    break;
                }
            }
        }
    }
    // loop will repeat if any seats freed/filled changed the state
} // end cascade loop

/* ------------------------
   Finalize results:
   - All candidates not in $assignments => Not Allocated
   ------------------------ */

$allocated = $assignments;
$not_allocated = [];
foreach ($candidates as $cand) {
    if (!isset($allocated[$cand['reg_no']])) $not_allocated[] = $cand;
}

/* ------------------------
   Print results (human-readable)
   ------------------------ */
echo "=== ALLOCATIONS ===\n";
foreach ($allocated as $reg => $info) {
    $cand = find_candidate($candidates, $reg);
    $cadreName = $posts[$info['cadre']]['name'] ?? $info['cadre'];
    $src = $info['source'] === 'quota' ? ("quota:".$info['quota']) : 'merit';
    $prefPos = $info['pref_index'] + 1;
    echo "{$reg} | {$cand['name']} => {$cadreName} ({$info['cadre']}) | {$src} | pref#{$prefPos}\n";
}

echo "\n=== NOT ALLOCATED ===\n";
foreach ($not_allocated as $cand) {
    echo "{$cand['reg_no']} | {$cand['name']} | general_rank: {$cand['general_merit_rank']} | prefs: ".implode(',', $cand['preferences'])."\n";
}

echo "\n=== SEATS REMAINING ===\n";
foreach ($seats_remaining as $cadre=>$smap) {
    echo "{$cadre} ({$posts[$cadre]['name']}): ";
    $parts = [];
    foreach ($smap as $q=>$n) $parts[] = "{$q}={$n}";
    echo implode(', ', $parts)."\n";
}
