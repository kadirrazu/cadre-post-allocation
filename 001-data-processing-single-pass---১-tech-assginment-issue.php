<?php 

//GET PREARED FOR THE SHOW!

//INDEX POSTS BY SHORT CADRE NAME
$postsByCadre = [];

foreach( $post_available as $code => $postData )
{

    $postsByCadre[$postData['cadre']] = $code;

}

//NORMALIZE CANDIDATES CHOICES
foreach( $candidates as &$candidate )
{

    if (isset($candidate['choice_list']))
    {
        $candidate['choices'] = array_map('strtoupper', array_filter(array_map('trim', explode(' ', $candidate['choice_list']))));
    } 
    else
    {
        $candidate['choices'] = [];
    }

}

unset( $candidate );

//COPY OF REMAINING POSTS
$post_remaining = $post_available;

//HELPER FUNCTION: PRIMARY MERIT FOR SORTING
function get_primary_merit( $candidate )
{

    if (!empty($candidate['general_merit_position'])) return $candidate['general_merit_position'];

    if (!empty($candidate['technical_merit_position']) && is_array($candidate['technical_merit_position']))
    {
        return min($candidate['technical_merit_position']);
    }

    return PHP_INT_MAX;

}

//SORT CANDIDATES ARRAY BASED ON MERIT RETURNED
usort( $candidates, fn($a,$b) => get_primary_merit($a) <=> get_primary_merit($b) );

//CADRE ALLOCATION
$final_allocation = [];

foreach( $candidates as $candidate )
{

    $assigned = false;

    foreach( $candidate['choices'] as $choice )
    {

        //FIND OUT THE CHOICE 'TYPE'
        if( isset($general_cadres['GENERAL'][$choice]) )
        {
            $type = 'GENERAL';
        }
        elseif( isset($technical_cadres['TECHNICAL'][$choice]) )
        {
            $type = 'TECHNICAL';
        }
        else
        {
            continue;
        }

        //CHECK FOR TECHNICAL MERIT ELIGIBILITY
        if( $type === 'TECHNICAL' )
        {

            if ( !isset($candidate['technical_merit_position'][$choice]) )
            {
                continue;
            }

        }

        //FIND OUT THE POST CODE
        if( !isset($postsByCadre[$choice]) )
        {
            continue;
        }

        $postCode = $postsByCadre[$choice];

        //MERIT ALLOCATION
        $canUseMerit = ($post_remaining[$postCode]['MQ'] ?? 0) > 0;

        if( $canUseMerit )
        {

            $post_remaining[$postCode]['MQ']--;

            $final_allocation[] = [
                'candidate'=>$candidate, 
                'cadre'=>$choice, 
                'quota'=>'MERIT', 
                'type'=>$type
            ];

            $assigned = true;

            break;

        }

        //QUOTA ALLOCATION
        if( !empty($candidate['quota']) && is_array($candidate['quota']) )
        {

            foreach ( ['CFF','EM','PHC'] as $quota )
            {
                if( !empty($candidate['quota'][$quota]) && ($post_remaining[$postCode][$quota] ?? 0) > 0 )
                {

                    $post_remaining[$postCode][$quota]--;

                    $final_allocation[] = [
                        'candidate'=>$candidate, 
                        'cadre'=>$choice, 
                        'quota'=>$quota, 
                        'type'=>$type
                    ];

                    $assigned = true;

                    break 2;

                }
            }

        }

    } //END OF INNER FOREACH, CHOICE LOOKUP. WE CHECKED FROM BEGINNING TO ASSIGNED OR LAST.
    

    //IF UN-ASSIGNED, THEN MARK THIS CANDIDATE
    if( !$assigned )
    {

        $final_allocation[] = [
            'candidate'=>$candidate, 
            'cadre'=>null, 
            'quota'=>null, 
            'type'=>null
        ];

    }

} //End of 1ST FOREACH, CANDIDATE LOOKUP

//SPLIT ALLOCATED CANDIDATES INTO THREE GOURP: General, Technical and Unassigned
$final_general = array_values(array_filter($final_allocation, fn($r)=> $r['type'] === 'GENERAL'));

$final_technical = array_values(array_filter($final_allocation, fn($r)=> $r['type'] === 'TECHNICAL'));

$final_unassigned = array_values(array_filter($final_allocation, fn($r)=> $r['type'] === null));

//SORT GENERAL and TECHNICAL CANDIDATES
usort($final_general, fn($a,$b)=>($a['candidate']['general_merit_position'] ?? PHP_INT_MAX) <=> ($b['candidate']['general_merit_position'] ?? PHP_INT_MAX));

usort($final_technical, fn($a,$b)=>($a['candidate']['technical_merit_position'][$a['cadre']] ?? PHP_INT_MAX) <=> ($b['candidate']['technical_merit_position'][$b['cadre']] ?? PHP_INT_MAX));