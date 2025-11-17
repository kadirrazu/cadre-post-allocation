<?php 

// ----------------- ASSUMES THESE ARE INCLUDED/DEFINED -----------------
// $general_cadres, $technical_cadres, $post_available, $candidates
// (You provided these in earlier messages)

// ----------------- PREP -----------------

// Index posts by short cadre name for O(1) lookup
$postsByCadre = [];
foreach ($post_available as $code => $p) {
    $postsByCadre[$p['cadre']] = $code;
}

// Normalize candidate choices and ensure structures exist
foreach ($candidates as &$candidate) {
    // ensure quota array exists
    if (!isset($candidate['quota']) || !is_array($candidate['quota'])) {
        $candidate['quota'] = ['CFF' => false, 'EM' => false, 'PHC' => false];
    }
    // ensure technical merit structure exists
    if (!isset($candidate['technical_merit_position']) || !is_array($candidate['technical_merit_position'])) {
        $candidate['technical_merit_position'] = [];
    }
    // normalize choices as uppercase tokens
    if (isset($candidate['choice_list'])) {
        $candidate['choices'] = array_values(array_filter(array_map('strtoupper', array_map('trim', explode(' ', $candidate['choice_list'])))));
    } else {
        $candidate['choices'] = [];
    }
}
unset($candidate);

// Copy working posts remaining
$post_remaining = $post_available;

// ----------------- 1) GENERAL ALLOCATION (GG + GT's general choices) -----------------

// Build list of candidates to consider for GENERAL allocation
$gen_candidates = [];
foreach ($candidates as $cand) {
    // Only GG and GT candidates are considered in general flow
    if ($cand['cadre_category'] === 'GG' || $cand['cadre_category'] === 'GT') {
        // collect only general choices (preserve original order)
        $gen_choices = [];
        foreach ($cand['choices'] as $ch) {
            if (isset($general_cadres['GENERAL'][$ch])) $gen_choices[] = $ch;
        }
        // keep candidates who have at least one general choice
        if (!empty($gen_choices)) {
            $copy = $cand;
            $copy['gen_choices'] = $gen_choices;
            $gen_candidates[] = $copy;
        }
    }
}

// Sort general candidates by general merit (ascending: 1 best)
usort($gen_candidates, function($a, $b) {
    $aPos = $a['general_merit_position'] ?? PHP_INT_MAX;
    $bPos = $b['general_merit_position'] ?? PHP_INT_MAX;
    return $aPos <=> $bPos;
});

// Prepare final arrays and assigned registry
$final_allocation = [];            // holds all allocations
$assigned_regno = [];              // map reg_no => true when someone gets a post (to prevent double-assign)
$not_assigned_after_general = [];  // to track GT/TT who still eligible for tech flow

// Process general candidates
foreach ($gen_candidates as $cand) {
    $assigned = false;

    foreach ($cand['gen_choices'] as $choice) {

        if (!isset($postsByCadre[$choice])) continue;
        $postCode = $postsByCadre[$choice];

        // MERIT seat: only if candidate has a general_merit_position (GG or GT will have it)
        $canUseMerit = (!empty($cand['general_merit_position'])) && (($post_remaining[$postCode]['MQ'] ?? 0) > 0);

        if ($canUseMerit) {
            $post_remaining[$postCode]['MQ']--;
            $final_allocation[] = ['candidate'=>$cand, 'cadre'=>$choice, 'quota'=>'MERIT', 'type'=>'GENERAL'];
            $assigned = true;
            $assigned_regno[$cand['reg_no']] = true;
            break;
        }

        // QUOTA (CFF -> EM -> PHC) - candidate may be eligible for one or multiple flags; check in order
        foreach (['CFF','EM','PHC'] as $q) {
            if (!empty($cand['quota'][$q]) && (($post_remaining[$postCode][$q] ?? 0) > 0)) {
                $post_remaining[$postCode][$q]--;
                $final_allocation[] = ['candidate'=>$cand, 'cadre'=>$choice, 'quota'=>$q, 'type'=>'GENERAL'];
                $assigned = true;
                $assigned_regno[$cand['reg_no']] = true;
                break 2; // assigned via quota, stop candidate's choice loop
            }
        }
        // else try next general choice
    }

    if (!$assigned) {
        // Keep candidate for possible technical allocation later (only if TT/GT had technical choices)
        $final_allocation[] = ['candidate'=>$cand, 'cadre'=>null, 'quota'=>null, 'type'=>null];
        // We'll let the tech flow decide if the candidate (GT) can be assigned later
    }
}

// ----------------- 2) TECHNICAL ALLOCATION (per-cadre, by tech merit) -----------------

// Build per-cadre applicant lists from TT candidates and GT candidates that still not assigned
$tech_applicants_by_cadre = [];

// Candidates eligible for TECH flow: TT candidates + GT candidates that haven't been assigned in general
foreach ($candidates as $cand) {
    // skip candidates already assigned in general
    if (!empty($assigned_regno[$cand['reg_no']])) continue;

    // Only TT and GT matter for technical
    if (!in_array($cand['cadre_category'], ['TT','GT'], true)) continue;

    // For each technical choice of this candidate, if they have a technical merit entry for that cadre, add them
    foreach ($cand['choices'] as $idx => $choice) {

        if (!isset($technical_cadres['TECHNICAL'][$choice])) continue;

        // Candidate must have tech merit for that cadre
        if (!isset($cand['technical_merit_position'][$choice])) continue;

        // register applicant for this cadre
        $tech_applicants_by_cadre[$choice][] = [
            'candidate' => $cand,
            'tech_merit' => $cand['technical_merit_position'][$choice],
            'choice_priority' => $idx // optional: lower = higher priority in their list
        ];
    }
}

// Now for each technical cadre, sort its applicants by tech_merit ascending (1 best)
foreach ($tech_applicants_by_cadre as $cadreShort => &$appList) {
    usort($appList, function($a,$b){
        if ($a['tech_merit'] === $b['tech_merit']) {
            // tie-breaker: preserve candidate who placed that cadre earlier in their choices (lower index)
            return ($a['choice_priority'] ?? PHP_INT_MAX) <=> ($b['choice_priority'] ?? PHP_INT_MAX);
        }
        return $a['tech_merit'] <=> $b['tech_merit'];
    });
}
unset($appList);

// Now iterate per-cadre and allocate seats in tech post_remaining
foreach ($tech_applicants_by_cadre as $cadreShort => $applicants) {

    // get post code
    if (!isset($postsByCadre[$cadreShort])) continue;
    $postCode = $postsByCadre[$cadreShort];

    foreach ($applicants as $entry) {

        $cand = $entry['candidate'];
        $reg = $cand['reg_no'];

        // skip if candidate already assigned (could happen if they got general in earlier flow)
        if (!empty($assigned_regno[$reg])) continue;

        // MERIT seats first
        if (($post_remaining[$postCode]['MQ'] ?? 0) > 0) {
            $post_remaining[$postCode]['MQ']--;
            $final_allocation[] = ['candidate'=>$cand, 'cadre'=>$cadreShort, 'quota'=>'MERIT', 'type'=>'TECHNICAL', 'tech_merit'=>$entry['tech_merit']];
            $assigned_regno[$reg] = true;
            continue;
        }

        // Then quota seats (CFF -> EM -> PHC) but only if candidate eligible for that quota
        foreach (['CFF','EM','PHC'] as $q) {
            if (!empty($cand['quota'][$q]) && (($post_remaining[$postCode][$q] ?? 0) > 0)) {
                $post_remaining[$postCode][$q]--;
                $final_allocation[] = ['candidate'=>$cand, 'cadre'=>$cadreShort, 'quota'=>$q, 'type'=>'TECHNICAL', 'tech_merit'=>$entry['tech_merit']];
                $assigned_regno[$reg] = true;
                break; // assigned this candidate for this cadre
            }
        }

        // continue to next applicant
    }
}

// ----------------- 3) ANY REMAINING UNASSIGNED CANDIDATES MARKED -----------------
// We must mark candidates that remain unassigned in $final_allocation list too.
// To keep output structure simple, we'll build final arrays by looking at $assigned_regno.

$final_general = [];
$final_technical = [];
$final_unassigned = [];

// gather assigned entries from $final_allocation
foreach ($final_allocation as $entry) {
    if ($entry['type'] === 'GENERAL') $final_general[] = $entry;
    elseif ($entry['type'] === 'TECHNICAL') $final_technical[] = $entry;
}

// now find all candidates who were never assigned (not in $assigned_regno)
foreach ($candidates as $cand) {
    if (empty($assigned_regno[$cand['reg_no']])) {
        $final_unassigned[] = ['candidate'=>$cand, 'cadre'=>null, 'quota'=>null, 'type'=>null];
    }
}

// Sort for display
usort($final_general, fn($a,$b)=>($a['candidate']['general_merit_position'] ?? PHP_INT_MAX) <=> ($b['candidate']['general_merit_position'] ?? PHP_INT_MAX));
usort($final_technical, fn($a,$b)=>($a['tech_merit'] ?? PHP_INT_MAX) <=> ($b['tech_merit'] ?? PHP_INT_MAX));

// ----------------- DEBUG: (optional) show $post_remaining snapshot -----------------
// echo "<pre>"; print_r($post_remaining); echo "</pre>";

// ----------------- (You can output $final_general, $final_technical, $final_unassigned as before) -----------------

// Example: print arrays (or inject into your HTML tables)
/*echo "<h3>GENERAL</h3><pre>"; foreach($final_general as $r) { echo $r['candidate']['reg_no']." => ".$r['cadre']." (".$r['quota'].")\n"; } echo "</pre>";
echo "<h3>TECHNICAL</h3><pre>"; foreach($final_technical as $r) { echo $r['candidate']['reg_no']." => ".$r['cadre']." (".$r['quota']."; tech=".$r['tech_merit'].")\n"; } echo "</pre>";
echo "<h3>UNASSIGNED</h3><pre>"; foreach($final_unassigned as $r) { echo $r['candidate']['reg_no']."\n"; } echo "</pre>";*/
