<?php
/**
 * Simplified BCS-like allocation engine (PHP)
 *
 * Key features:
 *  - $posts contains manual quota seat counts for each post (FF, ethnic, PC, merit).
 *  - Two merit axes: general_merit_rank (global) and technical_merit (per technical cadre).
 *  - Phases:
 *      1) allocate technical seats using technical merit
 *      2) allocate general seats using general merit
 *      3) process quotas (FF -> ethnic -> PC) using general merit ordering for quota candidates.
 *         Quota assignment can displace previously assigned candidates; freed seats are immediately re-filled
 *         from the appropriate merit pool (this models the dynamic technical-merit re-ranking).
 *  - Result: $assignments map reg_no => assignment info; unassigned remaining are "Not Allocated".
 *
 * This code is intentionally verbose and commented to aid understanding.
 */

/* -----------------------------
 * POSTS — manual quota seat counts
 *  For each post:
 *     - name
 *     - type: 'general'|'technical'
 *     - seats: total seats (informative)
 *     - quota_seats: explicit numbers for each quota and merit (they must sum to seats)
 *
 * Replace with your real posts and quota numbers.
 * ----------------------------- */
$posts = [
    'admin' => [
        'name' => 'Administrative Service',
        'type' => 'general',
        'seats' => 5,
        'quota_seats' => [
            'freedom_fighter' => 0,
            'ethnic' => 0,
            'physically_challenged' => 0,
            'merit' => 5,
        ],
    ],
    'foreign' => [
        'name' => 'Foreign Service',
        'type' => 'general',
        'seats' => 2,
        'quota_seats' => [
            'freedom_fighter' => 0,
            'ethnic' => 0,
            'physically_challenged' => 0,
            'merit' => 2,
        ],
    ],
    'police' => [
        'name' => 'Police',
        'type' => 'general',
        'seats' => 3,
        'quota_seats' => [
            'freedom_fighter' => 0,
            'ethnic' => 0,
            'physically_challenged' => 0,
            'merit' => 3,
        ],
    ],
    'roads' => [
        'name' => 'Roads & Highways',
        'type' => 'technical',
        'seats' => 4,
        'quota_seats' => [
            'freedom_fighter' => 0,
            'ethnic' => 0,
            'physically_challenged' => 0,
            'merit' => 4,
        ],
        // technical requirement & labels (not used to compute seats, only eligibility)
        'required_subject_codes' => [201], // civil engineering example
    ],
    'telecom' => [
        'name' => 'Telecommunication',
        'type' => 'technical',
        'seats' => 2,
        'quota_seats' => [
            'freedom_fighter' => 0,
            'ethnic' => 0,
            'physically_challenged' => 0,
            'merit' => 2,
        ],
        'required_subject_codes' => [202],
    ],
    'medical' => [
        'name' => 'Medical',
        'type' => 'technical',
        'seats' => 2,
        'quota_seats' => [
            'freedom_fighter' => 0,
            'ethnic' => 0,
            'physically_challenged' => 0,
            'merit' => 2,
        ],
        'required_subject_codes' => [301],
    ],
];

/* -----------------------------
 * SAMPLE CANDIDATES
 *
 * Fields:
 *  - reg_no (string), name
 *  - category: 'general'|'technical'|'both'
 *  - subject_codes: array (for technical eligibility)
 *  - preferences: ordered array of post keys (e.g. ['roads','admin','police'])
 *  - general_merit_rank: integer (1 best)
 *  - technical_merit: assoc post_key => rank (only for technical posts they appear in)
 *  - quota_flags: ['ff'=>bool, 'ethnic'=>bool, 'pc'=>bool]
 *
 * Replace/add candidates as needed.
 * ----------------------------- */
$candidates = [
    [
        'reg_no' => '10000001',
        'name' => 'A. Rahman',
        'category' => 'both',
        'subject_codes' => [201],
        'preferences' => ['roads', 'admin', 'police', 'telecom'],
        'general_merit_rank' => 10,
        'technical_merit' => ['roads' => 5],
        'quota_flags' => ['ff' => false, 'ethnic' => false, 'pc' => false],
    ],
    [
        'reg_no' => '10000002',
        'name' => 'B. Karim',
        'category' => 'general',
        'subject_codes' => [],
        'preferences' => ['admin', 'foreign', 'police'],
        'general_merit_rank' => 2,
        'technical_merit' => [],
        'quota_flags' => ['ff' => false, 'ethnic' => false, 'pc' => false],
    ],
    [
        'reg_no' => '10000003',
        'name' => 'C. Begum',
        'category' => 'technical',
        'subject_codes' => [202],
        'preferences' => ['telecom', 'roads'],
        'general_merit_rank' => 50,
        'technical_merit' => ['telecom' => 1, 'roads' => 30],
        'quota_flags' => ['ff' => false, 'ethnic' => true, 'pc' => false],
    ],
    [
        'reg_no' => '10000004',
        'name' => 'D. Khan',
        'category' => 'both',
        'subject_codes' => [301],
        'preferences' => ['medical', 'police', 'foreign', 'admin'],
        'general_merit_rank' => 5,
        'technical_merit' => ['medical' => 15],
        'quota_flags' => ['ff' => false, 'ethnic' => false, 'pc' => false],
    ],
    [
        'reg_no' => '10000005',
        'name' => 'H. Gain',
        'category' => 'both',
        'subject_codes' => [301],
        'preferences' => ['police', 'medical', 'foreign', 'admin'],
        'general_merit_rank' => 4,
        'technical_merit' => ['medical' => 12],
        'quota_flags' => ['ff' => false, 'ethnic' => false, 'pc' => false],
    ],
    [
        'reg_no' => '10000006',
        'name' => 'H. Gain',
        'category' => 'both',
        'subject_codes' => [301],
        'preferences' => ['medical', 'police', 'foreign', 'admin'],
        'general_merit_rank' => 6,
        'technical_merit' => ['medical' => 180],
        'quota_flags' => ['ff' => true, 'ethnic' => false, 'pc' => false],
    ],
];

/* -----------------------------
 * Working structures
 * ----------------------------- */

// which candidates are assigned: reg_no => ['post' => key, 'quota' => 'merit'|'freedom_fighter'|'ethnic'|'physically_challenged', 'pref_rank' => int]
$assignments = [];

// seats remaining for each post and each bucket
// We'll copy posts['quota_seats'] into a tracking map and decrement when we allocate
$seats_remaining = [];
foreach ($posts as $k => $p) {
    $seats_remaining[$k] = $p['quota_seats']; // clone
}

/* -----------------------------
 * Helper functions (small and explained)
 * ----------------------------- */

/**
 * Check if a candidate is eligible for a given post:
 *  - For general posts: candidate.category in ['general', 'both']
 *  - For technical posts: candidate.category in ['technical','both'] AND one of the candidate subject codes matches required_subject_codes
 */
function is_eligible_for_post($candidate, $postKey, $posts) {
    if (!isset($posts[$postKey])) return false;
    $post = $posts[$postKey];
    if ($post['type'] === 'general') {
        return in_array($candidate['category'], ['general', 'both']);
    } else { // technical
        if (!in_array($candidate['category'], ['technical', 'both'])) return false;
        if (empty($post['required_subject_codes'])) return true; // if no requirement set, allow
        return count(array_intersect($candidate['subject_codes'], $post['required_subject_codes'])) > 0;
    }
}

/**
 * Utility: find candidate by reg_no in $candidates array
 */
function find_candidate($reg_no, $candidates) {
    foreach ($candidates as $c) if ($c['reg_no'] === $reg_no) return $c;
    return null;
}

/* -----------------------------
 * Phase 1: Allocate technical posts using their technical merit lists
 *
 * For each technical post:
 *   - build list of candidates who are eligible for that technical post AND
 *     who have a technical merit rank for that post (only those candidates can compete on technical merit).
 *   - sort by technical_merit[postKey] ascending (1 = best)
 *   - for each candidate in that order: if they are not yet assigned, and the post has merit seats remaining,
 *       - check candidate's preference list: we only assign them to this post if this post appears in their preference list
 *         at some position (we respect candidate preference order for final placements by allowing only posts that are in their preferences).
 *       - assign and decrement merit seats for the post.
 *
 * This phase uses ONLY the post's 'merit' seats and only technical merit ordering.
 * ----------------------------- */
foreach ($posts as $postKey => $post) {
    if ($post['type'] !== 'technical') continue;
    // Build candidate pool who have technical_merit rank for this post and who are eligible
    $pool = [];
    foreach ($candidates as $cand) {
        if (!is_eligible_for_post($cand, $postKey, $posts)) continue;
        if (!isset($cand['technical_merit'][$postKey])) continue; // needs a technical merit rank to compete
        // Also ensure candidate actually listed this post in preferences (otherwise they don't want it)
        if (!in_array($postKey, $cand['preferences'])) continue;
        $pool[] = $cand;
    }
    // Sort by technical merit ascending (1 best). Tie-break by reg_no.
    usort($pool, function($a, $b) use ($postKey) {
        $ar = $a['technical_merit'][$postKey];
        $br = $b['technical_merit'][$postKey];
        if ($ar != $br) return ($ar < $br) ? -1 : 1;
        return strcmp($a['reg_no'], $b['reg_no']);
    });
    // Fill up to merit seats available for this technical post
    $open = isset($seats_remaining[$postKey]['merit']) ? $seats_remaining[$postKey]['merit'] : 0;
    foreach ($pool as $cand) {
        if ($open <= 0) break;
        $r = $cand['reg_no'];
        if (isset($assignments[$r])) continue; // already has an assignment
        // assign
        $assignments[$r] = ['post' => $postKey, 'quota' => 'merit', 'pref_rank' => array_search($postKey, $cand['preferences']) + 1];
        $open--;
    }
    $seats_remaining[$postKey]['merit'] = $open;
}

/* -----------------------------
 * Phase 2: Allocate general posts using general merit
 *
 * - Iterate candidates ordered by general_merit_rank ascending.
 * - For each candidate not yet assigned, try to allocate their highest preference that:
 *     a) is a general post with a merit seat available OR
 *     b) is a technical post with a 'merit' seat available AND candidate is eligible (technical candidates/both with subject)
 * - When assigned, decrement that post's merit seats.
 *
 * Note: Because technical merit is a separate ordering, this phase will only take technical posts if merit seats remain
 * and the candidate is eligible and had that technical post in preferences. This matches rule: there are two separate merit lists.
 * ----------------------------- */

// Order all candidates by general_merit_rank ascending
$byGeneral = $candidates;
usort($byGeneral, function($a, $b) {
    if ($a['general_merit_rank'] != $b['general_merit_rank']) return ($a['general_merit_rank'] < $b['general_merit_rank']) ? -1 : 1;
    return strcmp($a['reg_no'], $b['reg_no']);
});

foreach ($byGeneral as $cand) {
    $r = $cand['reg_no'];
    if (isset($assignments[$r])) continue; // skip already assigned by technical phase
    // iterate preferences in order; pick the first one that has a merit seat and for which the candidate is eligible
    foreach ($cand['preferences'] as $idx => $pkey) {
        if (!isset($posts[$pkey])) continue;
        if (!is_eligible_for_post($cand, $pkey, $posts)) continue;
        $open = isset($seats_remaining[$pkey]['merit']) ? $seats_remaining[$pkey]['merit'] : 0;
        if ($open <= 0) continue; // no merit seats left here
        // assign candidate here on merit seat
        $assignments[$r] = ['post' => $pkey, 'quota' => 'merit', 'pref_rank' => $idx + 1];
        $seats_remaining[$pkey]['merit'] = $open - 1;
        break;
    }
}

/* -----------------------------
 * Phase 3: Process quotas in order — freedom_fighter, ethnic, physically_challenged
 *
 * For each quota:
 *   - Build list of candidates who have that quota flag (and are not already assigned to that quota)
 *   - Sort them by general_merit_rank (user's requirement: quota ordering uses general merit)
 *   - For each candidate in that order:
 *       - Try to give them the highest preference post (left-to-right) that has a seat in this quota and for which they are eligible.
 *       - If they get assigned via quota and they were previously assigned somewhere else:
 *           * free that old seat (increase seats_remaining for the old post in the bucket from which they came)
 *           * immediately try to fill the freed seat using the appropriate merit pool (technical or general).
 *             - If freed seat is a technical post => find the best unassigned candidate by technical merit for that post (who lists that post in prefs and is eligible)
 *             - If freed seat is a general post => find the best unassigned candidate by general merit (who lists that post in prefs and is eligible)
 *           * repeat cascading fills as needed until no more auto-fill is possible for that freed seat.
 *
 * This models the dynamic re-ranking/promotion behavior you described.
 * ----------------------------- */

$quota_order = ['freedom_fighter', 'ethnic', 'physically_challenged'];

foreach ($quota_order as $quota) {
    // gather all quota-holders (even those already assigned — they might be improved if they can get a quota seat higher in their choices)
    $quota_holders = [];
    foreach ($candidates as $cand) {
        $has = false;
        if ($quota === 'freedom_fighter' && !empty($cand['quota_flags']['ff'])) $has = true;
        if ($quota === 'ethnic' && !empty($cand['quota_flags']['ethnic'])) $has = true;
        if ($quota === 'physically_challenged' && !empty($cand['quota_flags']['pc'])) $has = true;
        if ($has) $quota_holders[] = $cand;
    }
    // sort quota holders by general merit
    usort($quota_holders, function($a, $b) {
        if ($a['general_merit_rank'] != $b['general_merit_rank']) return ($a['general_merit_rank'] < $b['general_merit_rank']) ? -1 : 1;
        return strcmp($a['reg_no'], $b['reg_no']);
    });

    // try to allocate each quota-holder to their highest preference that has this quota seat
    foreach ($quota_holders as $cand) {
        $r = $cand['reg_no'];

        // If this candidate already holds a seat in this same quota for the post, skip
        if (isset($assignments[$r]) && $assignments[$r]['quota'] === $quota) continue;

        // try preferences left-to-right
        foreach ($cand['preferences'] as $idx => $pkey) {
            if (!isset($posts[$pkey])) continue;
            if (!is_eligible_for_post($cand, $pkey, $posts)) continue;
            $open = isset($seats_remaining[$pkey][$quota]) ? $seats_remaining[$pkey][$quota] : 0;
            if ($open <= 0) continue; // no quota seat here
            // allocate this quota seat to candidate r
            $previous_assignment = isset($assignments[$r]) ? $assignments[$r] : null;
            $assignments[$r] = ['post' => $pkey, 'quota' => $quota, 'pref_rank' => $idx + 1];
            $seats_remaining[$pkey][$quota] = $open - 1;

            // if they had a previous assignment, free that seat and attempt to refill it
            if ($previous_assignment) {
                $oldPost = $previous_assignment['post'];
                $oldQuota = $previous_assignment['quota'];
                // free the old seat (increment its bucket)
                if (!isset($seats_remaining[$oldPost][$oldQuota])) $seats_remaining[$oldPost][$oldQuota] = 0;
                $seats_remaining[$oldPost][$oldQuota] += 1;
                // remove the candidate's old assignment (they are now assigned to the quota post)
                // (we already overwrote $assignments[$r] above)

                // Attempt to refill the freed seat: we will try to fill only the bucket type that was freed (oldQuota)
                // If oldQuota === 'merit' -> fill with best candidate from appropriate merit pool
                // If oldQuota is a reserved bucket -> typically reserved seats are filled only by reserved candidates;
                // here we will try to fill freed reserved bucket by same quota (simple policy).
                attempt_refill_seat($oldPost, $oldQuota, $posts, $candidates, $assignments, $seats_remaining);
            }

            // Once this candidate gets any quota seat (their highest available), we stop checking lower preferences
            break;
        }
    }
}

/* -----------------------------
 * Final reporting
 * ----------------------------- */

echo "=== ASSIGNMENTS ===<br>";
foreach ($assignments as $reg => $info) {
    $cand = find_candidate($reg, $candidates);
    $cname = $cand ? $cand['name'] : 'Unknown';
    echo "{$reg} | {$cname} => {$posts[$info['post']]['name']} (post: {$info['post']})"
        . " [quota: {$info['quota']}] [pref_rank: {$info['pref_rank']}]<br>";
}

echo "<br>=== NOT ALLOCATED ===<br>";
foreach ($candidates as $cand) {
    if (!isset($assignments[$cand['reg_no']])) {
        echo "{$cand['reg_no']} | {$cand['name']} | prefs: " . implode(',', $cand['preferences']) . " | general_rank: {$cand['general_merit_rank']}<br>";
    }
}

echo "<br>=== SEATS REMAINING ===<br>";
foreach ($seats_remaining as $postKey => $buckets) {
    echo "{$postKey} ({$posts[$postKey]['name']}): ";
    $parts = [];
    foreach ($buckets as $k => $v) $parts[] = "{$k}={$v}";
    echo implode(', ', $parts) . "<br>";
}

/* -----------------------------
 * FUNCTIONS used by quotas refill — kept at bottom to improve reading order
 * ----------------------------- */

/**
 * attempt_refill_seat($postKey, $bucket, ...)
 *
 * Try to fill a single freed seat (postKey, bucket). This function will:
 *  - If bucket === 'merit' and post is technical:
 *       pick the best unassigned candidate for that post by technical merit (who lists it in prefs)
 *  - If bucket === 'merit' and post is general:
 *       pick the best unassigned candidate by general merit (who lists it in prefs)
 *  - If bucket is a quota (ff/ethnic/pc), pick the best unassigned candidate who belongs to that quota
 *    and is eligible and who listed that post in prefs, ordered by general merit (per your requirement).
 *
 * If a chosen candidate was previously assigned elsewhere, free that old seat and recursively refill it.
 *
 * NOTE: This simple implementation prioritizes immediate refill of a single freed seat and allows cascade.
 */
function attempt_refill_seat($postKey, $bucket, $posts, $candidates, &$assignments, &$seats_remaining) {
    // Find best candidate for this post/bucket among currently unassigned people (or those assigned elsewhere but eligible to be moved).
    // We'll consider only currently unassigned candidates (if we moved someone in, they would have been removed before calling this).
    // Build candidate list:
    $pool = [];
    foreach ($candidates as $cand) {
        $r = $cand['reg_no'];
        if (isset($assignments[$r])) continue; // we only pick from truly unassigned
        if (!in_array($postKey, $cand['preferences'])) continue; // candidate must have the post in their list
        if (!is_eligible_for_post($cand, $postKey, $posts)) continue;
        // filter by bucket/quota membership
        if ($bucket === 'merit') {
            // if post is technical, they must have technical_merit for this post to be considered by technical ranking later
            if ($posts[$postKey]['type'] === 'technical') {
                if (!isset($cand['technical_merit'][$postKey])) continue;
            }
            $pool[] = $cand;
        } else {
            // bucket is some quota; quota ordering uses general merit and candidate must belong to quota
            $has = false;
            if ($bucket === 'freedom_fighter' && !empty($cand['quota_flags']['ff'])) $has = true;
            if ($bucket === 'ethnic' && !empty($cand['quota_flags']['ethnic'])) $has = true;
            if ($bucket === 'physically_challenged' && !empty($cand['quota_flags']['pc'])) $has = true;
            if (!$has) continue;
            $pool[] = $cand;
        }
    }

    if (empty($pool)) {
        // nothing to fill right now
        return;
    }

    // Sort pool appropriately
    if ($bucket === 'merit') {
        if ($posts[$postKey]['type'] === 'technical') {
            // technical merit ordering
            usort($pool, function($a, $b) use ($postKey) {
                $ar = $a['technical_merit'][$postKey];
                $br = $b['technical_merit'][$postKey];
                if ($ar != $br) return ($ar < $br) ? -1 : 1;
                return strcmp($a['reg_no'], $b['reg_no']);
            });
        } else {
            // general merit ordering
            usort($pool, function($a, $b) {
                if ($a['general_merit_rank'] != $b['general_merit_rank']) return ($a['general_merit_rank'] < $b['general_merit_rank']) ? -1 : 1;
                return strcmp($a['reg_no'], $b['reg_no']);
            });
        }
    } else {
        // quota bucket: order by general merit
        usort($pool, function($a, $b) {
            if ($a['general_merit_rank'] != $b['general_merit_rank']) return ($a['general_merit_rank'] < $b['general_merit_rank']) ? -1 : 1;
            return strcmp($a['reg_no'], $b['reg_no']);
        });
    }

    // pick the top candidate
    $pick = $pool[0];
    $r = $pick['reg_no'];

    // assign them to this post in this bucket
    $assignments[$r] = ['post' => $postKey, 'quota' => $bucket, 'pref_rank' => array_search($postKey, $pick['preferences']) + 1];
    // decrement the seat we are filling
    if (!isset($seats_remaining[$postKey][$bucket])) $seats_remaining[$postKey][$bucket] = 0;
    $seats_remaining[$postKey][$bucket] -= 1;

    // If this candidate had a previous assignment (we only looked at unassigned so they won't), we would free it and recursively refill.
    // In this function we only filled truly unassigned candidates (caller freed seat previously), so no recursion needed here.
    // However, if you modify to allow moving already-assigned people here, you'd need to handle cascade similarly.
}

