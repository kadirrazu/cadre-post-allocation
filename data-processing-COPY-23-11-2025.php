<?php

//Start: Going Manually, Step by Step

//Build mappings for Quick Access
$abbr_to_code = [];
$code_to_abbr = [];
$code_to_name = [];

foreach( $post_available as $code => $info )
{

    $abbr = $info['cadre'];
    $abbr_to_code[$abbr] = $code;
    $code_to_abbr[$code] = $abbr;

    //Try to find a pretty name from $general_cadres if present
    //First check the code inside $general_cadres
    foreach( $general_cadres as $key => $data ) 
    {
        if( $data['code'] == $code ){
            $code_to_name[$code] = $general_cadres[$key]['name'];
            break;
        }
    }

    //Next, check the code inside $technical_cadres
    foreach( $technical_cadres as $key => $data ) 
    {
        if( $data['code'] == $code ){
            $code_to_name[$code] = $technical_cadres[$key]['name'];
            break;
        }
    }

    //If the full name is absent, then just return the ABBR form.
    if (!isset($code_to_name[$code])) {
        $code_to_name[$code] = $abbr;
    }

}

//Put all general and technical cadre ABBR's in respective arrays
$generalCadres = array_column($general_cadres, 'abbr');
$technicalCadres = array_column($technical_cadres, 'abbr');

//Utilities : Helper Functions
function parse_choices_list(string $s)
{
    if (trim($s) === '') return [];
    // split on one-or-more whitespace
    $parts = preg_split('/\s+/', trim($s));
    return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
}

//Convert technical_merit_position representation into a subject => position map
function technical_map( $candidate )
{
    // if already an assoc map like ['ROME' => 11], return as-is
    if( empty($candidate['technical_merit_position']) ) return [];

    $t = $candidate['technical_merit_position'];

    // else try to sanitize
    $out = [];

    foreach ($t as $k => $v) $out[strtoupper($k)] = intval($v);

    return $out;
}

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

//Get the best possible cadre's position or abbr
function get_best_choice($foundIn, $rawChoiceList, $abbr = true)
{
    $best = PHP_INT_MAX;
    $bestAbbr = null;
    foreach ($foundIn as $found) {
        $position = array_search($found, $rawChoiceList, true);
        if ($position === false) continue;
        if ($position < $best) {
            $best = $position;
            $bestAbbr = $rawChoiceList[$position];
        }
    }

    if ($abbr) {
        return $bestAbbr;
    }

    return ($best === PHP_INT_MAX) ? null : $best;
}

//Helper: to check if reg already allocated in $allocated for any cadre
function is_already_allocated($allocated, $reg) {
    foreach ($allocated as $cad => $list) {
        foreach ($list as $a) {
            if ($a['reg_no'] === $reg) return true;
        }
    }
    return false;
}

//Create cadre wise candidate_pools
//If cadre type is GENERAL, then, general merit position must be there. And type will be GG or GT
//If cadre type is TECHNICAL, then, global_tech_merit must be there. And type will be GT or TT

//First, empty queues for post_available cadre's only
$queues = []; 

foreach ($post_available as $code => $info) $queues[$code_to_abbr[$code]] = [];

//Now fill up each queue or pool by iterating candidate's choices

$candidate_index = []; // reg_no => full candidate stored for convenience

foreach( $candidates as $i => $candidate ) 
{
    $reg = $candidate['reg_no'];
    $candidate_index[$reg] = $candidate; // keep raw
    $raw_choice_string = $candidate['choice_list'] ?? '';
    $choices = parse_choices_list( $raw_choice_string );
    $choice_rank = 0;

    foreach( $choices as $choice_abbr )
    {

        $choice_rank++;

        if( !isset($abbr_to_code[$choice_abbr]) ) continue; // ignore unknown cadres

        $code = $abbr_to_code[$choice_abbr];

        //Eligibility checking before putting into the QUEUE
        $cadre_is_tech = in_array($choice_abbr, $technicalCadres);

        $eligible = false;

        $category = strtoupper($candidate['cadre_category'] ?? '');

        $inQueueSortingScore = null;

        if( $category === 'GG' )
        {
            if( !$cadre_is_tech && !empty($candidate['general_merit_position'])) $eligible = true;

            $inQueueSortingScore = intval($candidate['general_merit_position']) ?? null;
        } 
        elseif( $category === 'TT' )
        {
            if ($cadre_is_tech)
            {
                $tm = technical_map( $candidate );

                if (isset($tm[$choice_abbr])) $eligible = true;

                $inQueueSortingScore = intval($candidate['global_tech_merit']) ?? null;
            }
        }
        else
        {
            if( !$cadre_is_tech && !empty($candidate['general_merit_position']))
            {
                $eligible = true;

                $inQueueSortingScore = intval($candidate['general_merit_position']) ?? null;
            }

            if ($cadre_is_tech)
            {
                $tm = technical_map( $candidate );

                if (isset($tm[$choice_abbr])) $eligible = true;

                $inQueueSortingScore = intval($candidate['global_tech_merit']) ?? null;
            }
        }

        //If candidate is not eligible for this cadre queue, continue to next choice
        if (!$eligible) continue;

        $queues[$choice_abbr][] = [
            'reg_no' => $reg,
            'sorting_score' => intval($inQueueSortingScore),
            'choice_rank' => $choice_rank,
            'raw_choice_list' => $raw_choice_string,
            'candidate_index' => $i,
            'candidate' => $candidate
        ];

    }

}

//Now we need to sort each allocation queue or pool
//Guess: sorting_score will be unique for all the candidates [=> merit position]
foreach ($queues as $abbr => &$q) {

    usort($q, function($a, $b) {
        // primary: sorting_score (smaller/better)
        if ($a['sorting_score'] !== $b['sorting_score']) return $a['sorting_score'] <=> $b['sorting_score'];
    });

}

unset( $q );

//Code is FULLY OKAY and WORKING upto this point => "QUEUE GENERATION and EACH QUEUE SORTING DONE"

//CREATE AVAILABLE POST'S CATALOG
$remaining = [];

foreach( $post_available as $code => $info )
{

    $remaining[$code_to_abbr[$code]] = [
        'MQ' => intval($info['MQ'] ?? 0),
        'CFF' => intval($info['CFF'] ?? 0),
        'EM' => intval($info['EM'] ?? 0),
        'PHC' => intval($info['PHC'] ?? 0),
        'total_post' => intval($info['total_post'] ?? ($info['MQ'] + $info['CFF'] + $info['EM'] + $info['PHC'])),
        'allocated' => 0,
    ];

}

//Now we have cadre wise sorted queues or pools
//Also, we have remaining posts catalog
//So, lets start cadre wise post distribution now.

//Cadre-Wise Allocation: 1st Round
//In this step we will fill all the possible cadre posts from its queue candidates.

$stillImbalanced = true;
$i = 0;
$allocated = [];

while( $stillImbalanced )
{

    $currentIterationAllocationResult = [];   // Final per-cadre allocations

    foreach( $queues as $abbr => $queue ) 
    {
        $mq_posts_left = $remaining[$abbr]['MQ'];
        $cff_posts_left = $remaining[$abbr]['CFF'];
        $em_posts_left = $remaining[$abbr]['EM'];
        $phc_posts_left = $remaining[$abbr]['PHC'];

        $total_post = $remaining[$abbr]['total_post'];
        $allocated_post = $remaining[$abbr]['allocated'];

        // integer number of posts left to fill
        $posts_left = max(0, $total_post - $allocated_post);

        $currentIterationAllocationResult[$abbr] = [];

        if( $posts_left <= 0 )
        {
            continue; // no posts to fill
        }

        if( count($queue) <= 0 )
        {
            continue; // no candidates in the queue
        }

        foreach( $queue as $entry )
        {
            if ($posts_left <= 0) break; // cadre is fully allocated

            //*** MISSING CHECK ***
            if (is_already_allocated($allocated, $entry['reg_no'])) continue;

            $reg = $entry['reg_no'];
            $candidate = $entry['candidate'];

            //Decide seat: prefer MQ then quotas in order
            $allocated_quota = null;

            //try MQ first
            if( $remaining[$abbr]['MQ'] > 0 )
            {
                $remaining[$abbr]['MQ'] -= 1;
                $allocated_quota = 'MQ';
            } 
            else
            {
                //check candidate-specific quotas
                $cand = $candidate_index[$reg];
                $qinfo = $cand['quota'] ?? [];

                foreach( ['CFF','EM','PHC'] as $qtype )
                {
                    if( $qinfo[$qtype] == 1 && $remaining[$abbr][$qtype] > 0 )
                    {
                        $remaining[$abbr][$qtype] -= 1;
                        $allocated_quota = $qtype;
                        break;
                    }
                }
            }

            if( $allocated_quota !== null )
            {
                //Allocate this candidate to this cadre
                $currentIterationAllocationResult[$abbr][] = [
                    'reg_no'        => $reg,
                    'candidate'     => $candidate,
                    'choice_rank'   => $entry['choice_rank'],
                    'sorting_score' => $candidate['general_merit_position'],
                    'quota'         => $allocated_quota,
                ];

                // Reduce available posts
                $remaining[$abbr]['allocated']++;
                $posts_left--;

            }
            else
            {
                //
            }

        }

    } //END: CADRE-WISE ALLOCATION STEP - Code is OKAY upto this POINT.


    //Now we need to solve multiple cadre allocation issue
    //We will go through each allocation, will terminate if choice_order is 1, and post as permanent allocation
    //Otherwise, we will post as temporary allocation, will keep him in upper choice queues.

    //MULTIPLE ASSIGNMENT RESOLUTION

    $foundMultiple = false;

    foreach( $currentIterationAllocationResult as $cadre => $list )
    {

        foreach( $list as $assigned )
        {

            $reg = $assigned['reg_no'];
            $rawChoiceList = parse_choices_list( $assigned['candidate']['choice_list'] );

            //Count total temporary assignments
            $count = 0;
            $foundIn = [];

            foreach( $currentIterationAllocationResult as $c2 => $l2 )
            {
                foreach( $l2 as $item )
                {
                    if ($item['reg_no'] === $reg) {
                        $count++;
                        $foundIn[] = $c2;
                    }
                }
            }

            //if ($count <= 1) continue; // no conflict

            // If there's no conflict (only allocated in one cadre this round),
            // record it immediately (final for this round).
            if ($count <= 1)
            {
                // avoid double-adding if already added earlier
                if (!is_already_allocated($allocated, $reg))
                {
                    $allocated[$cadre][] = [
                        'reg_no' => $reg,
                        'allocation_status' => 'final',
                        'candidate' => $assigned
                    ];
                }

                continue; // nothing more to do for non-conflicts

            }

            $foundMultiple = true;

            $candidate = null;

            foreach( $candidates as $c )
            {
                if( $c['reg_no'] == $reg )
                {
                    $candidate = $c;
                    break;
                }
            }

            $choiceListArray = $rawChoiceList;
            $choiceListArrayLenght = count( $choiceListArray );

            $bestChoicePositionAbbr = get_best_choice($foundIn, $choiceListArray, true);
            $bestChoicePositionIndex = get_best_choice($foundIn, $choiceListArray, false);

            if (strtoupper($cadre) == strtoupper($bestChoicePositionAbbr))
            {
                //avoid double-adding
                if (!is_already_allocated($allocated, $reg)) {
                    $allocated[$cadre][] = [
                        'reg_no'            => $reg,
                        'allocation_status' => ($bestChoicePositionIndex == 0 ? 'final' : 'temporary'),
                        'candidate'         => $assigned
                    ];
                }
            }
            else
            {
                if(!empty($assigned['quota']) && isset($remaining[$cadre][$assigned['quota']]))
                {
                    $remaining[$cadre][$assigned['quota']]++;
                    $remaining[$cadre]['allocated']--;
                }
                else
                {
                    // fallback: restore MQ because every cadre must have a correct seat type
                    $remaining[$cadre]['MQ']++;

                    if( $remaining[$cadre]['allocated'] > 0 ) 
                    {
                        $remaining[$cadre]['allocated']--;
                    }
                }
            }

            //Choose highest preference
            $upperCadresWaitingList = [];

            if( $bestChoicePositionIndex > 0 )
            {
                $upperCadresWaitingList = array_slice($choiceListArray, 0, $bestChoicePositionIndex);
            }

            //Remove from all but upperCadresWaitingList
            foreach( $queues as $cad => &$allocList )
            {
                foreach ( $allocList as $i => $item )
                {

                    if ($item['reg_no'] == $reg && !in_array($cad, $upperCadresWaitingList) )
                    {
                        unset($allocList[$i]);
                    }
                    else if( $item['reg_no'] == $reg && $bestChoicePositionIndex == 0 )
                    {
                        unset($allocList[$i]);
                        $allocList[$i]['round_status'] = 'Done';
                    }
                    else
                    {
                        $allocList[$i]['round_status'] = 'In Queue';
                    }

                }

                $allocList = array_values($allocList);

            }

            unset($allocList);

            $currentCadre = null;
            $foundFirstChoice = false;
            
        }

    }

    if( $i == 10 ) $stillImbalanced = false;

    $i++;

} //end of while loop

//Code upto this point is OKAY. 
//Solved multiple cadre assignments, Kept in Best Possible Cadre
//If best_possible was the 1st choice, finalized that. Otherwise put in the upper choices queues.

//Now we need to loop through the queues to fill up the rest posts.


$allocation = $allocated;


$unallocated = [];

foreach ($candidates as $c)
{
    if (!is_already_allocated($allocated, $c['reg_no'])) 
    {
        $unallocated[] = [
            'reg_no' => $c['reg_no'],
            'user_id' => $c['user_id'],
            'raw_choice_list' => $c['choice_list'],
            'general_merit_position' => $c['general_merit_position'],
            'global_tech_merit' => $c['global_tech_merit'] ?? null,
            'technical_merit_position' => formatTechnicalMerit($c),
            'cadre_category' => $c['cadre_category'] ?? null,
        ];
    }
}