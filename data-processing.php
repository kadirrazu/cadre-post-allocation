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

//Utilities
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

        if( $category === 'GG' )
        {
            if( !$cadre_is_tech && !empty($candidate['general_merit_position'])) $eligible = true;
        } 
        elseif( $category === 'TT' )
        {
            if ($cadre_is_tech)
            {
                $tm = technical_map( $candidate );

                if (isset($tm[$choice_abbr])) $eligible = true;
            }
        }
        else
        {
            if( !$cadre_is_tech && !empty($candidate['general_merit_position']))
            {
                $eligible = true;
            }

            if ($cadre_is_tech)
            {
                $tm = technical_map( $candidate );

                if (isset($tm[$choice_abbr])) $eligible = true;
            }
        }

        //If candidate is not eligible for this cadre queue, continue to next choice
        if (!$eligible) continue;

        $inQueueSortingScore = null;

        // score for sorting
        if($cadre_is_tech)
        {
            $inQueueSortingScore = $candidate['global_tech_merit'] ?? null;
        }
        else
        {
            $inQueueSortingScore = $cand['general_merit_position'] ?? null;
        }

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
//Sort each queue
foreach( $queues as $abbr => &$q )
{

    $isTech = in_array($abbr, $technicalCadres);

    usort($q, function($a, $b) use ($isTech) {
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



echo '<pre>';
var_dump( $remaining );
echo '</pre>';

die();