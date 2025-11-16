<?php

$cadres = [

    'GENERAL' => [
        'ADMN' => ['code' => 101, 'name' => 'BCS (ADMINISTRATION)'],
        'POLC' => ['code' => 102, 'name' => 'BCS (POLICE)'],
        'FRGN' => ['code' => 103, 'name' => 'BCS (FOREIGN AFFAIRS)'],
        'ACNT' => ['code' => 104, 'name' => 'BCS (AUDIT & ACCOUNTS)'],
        'TAXN' => ['code' => 105, 'name' => 'BCS (TAXATION)'],
        'CUST' => ['code' => 106, 'name' => 'BCS (CUSTOMS & EXCISE)'],
        'POST' => ['code' => 107, 'name' => 'BCS (POSTS)'],
        'RAIL' => ['code' => 108, 'name' => 'BCS (RAILS)'],
        'COOP' => ['code' => 109, 'name' => 'BCS (COOPERATIVE)'],
        'FOOD' => ['code' => 110, 'name' => 'BCS (FOOD)'],
        'ANSA' => ['code' => 111, 'name' => 'BCS (ANSAR)'],
        'INFO' => ['code' => 112, 'name' => 'BCS (INFORMATION)'],
        'FAMP' => ['code' => 113, 'name' => 'BCS (FAMILY PLANNING)'],
        'STAT' => ['code' => 114, 'name' => 'BCS (STATISTICS)'],
        'ECON' => ['code' => 115, 'name' => 'BCS (ECONOMIC)'],
        'TRAD' => ['code' => 116, 'name' => 'BCS (TRADE)'],
        'LBWA' => ['code' => 117, 'name' => 'BCS (LIBERATION WAR AFFAIRS)'],
    ],

    'TECHNICAL' => [
        'HLTH' => ['code' => 201, 'name' => 'BCS (HEALTH)'],
        'AGRI' => ['code' => 202, 'name' => 'BCS (AGRICULTURE)'],
        'FISH' => ['code' => 203, 'name' => 'BCS (FISHERIES)'],
        'ANML' => ['code' => 204, 'name' => 'BCS (ANIMAL HUSBANDRY)'],
        'FDNT' => ['code' => 205, 'name' => 'BCS (FOOD & NUTRITION)'],
        'STTC' => ['code' => 206, 'name' => 'BCS (STATISTICAL)'],
        'EDUC' => ['code' => 207, 'name' => 'BCS (EDUCATION)'],
        'ROME' => ['code' => 208, 'name' => 'BCS (ENGINEERING: ROADS & HIGHWAYS)'],
        'ENPH' => ['code' => 209, 'name' => 'BCS (ENGINEERING: PUBLIC HEALTH)'],
        'ENPW' => ['code' => 210, 'name' => 'BCS (ENGINEERING: POWER)'],
        'ENWD' => ['code' => 211, 'name' => 'BCS (ENGINEERING: WATER DEVELOPMENT)'],
        'ARCH' => ['code' => 212, 'name' => 'BCS (ENGINEERING: ARCHITECTURE)'],
        'FORE' => ['code' => 213, 'name' => 'BCS (FORESTRY)'],
        'TXTL' => ['code' => 214, 'name' => 'BCS (TEXTILE)'],
    ],

];

$post_available = [

    // GENERAL CADRES
    101 => ['cadre' => 'ADMN', 'total_post' => 5, 'MQ' => 3, 'CFF' => 1, 'EM' => 1, 'PHC' => 0],
    102 => ['cadre' => 'POLC', 'total_post' => 7,  'MQ' => 4,  'CFF' => 1, 'EM' => 1, 'PHC' => 1],
    103 => ['cadre' => 'FRGN', 'total_post' => 3, 'MQ' => 2, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    104 => ['cadre' => 'ACNT', 'total_post' => 9,  'MQ' => 8,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    105 => ['cadre' => 'TAXN', 'total_post' => 6,  'MQ' => 6,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    106 => ['cadre' => 'CUST', 'total_post' => 14, 'MQ' => 13, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    107 => ['cadre' => 'POST', 'total_post' => 8,  'MQ' => 7,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    108 => ['cadre' => 'RAIL', 'total_post' => 5,  'MQ' => 5,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    109 => ['cadre' => 'COOP', 'total_post' => 13, 'MQ' => 12, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    110 => ['cadre' => 'FOOD', 'total_post' => 10, 'MQ' => 9,  'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    111 => ['cadre' => 'ANSA', 'total_post' => 4,  'MQ' => 4,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    112 => ['cadre' => 'INFO', 'total_post' => 11, 'MQ' => 10, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    113 => ['cadre' => 'FAMP', 'total_post' => 16, 'MQ' => 15, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    114 => ['cadre' => 'STAT', 'total_post' => 7,  'MQ' => 7,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    115 => ['cadre' => 'ECON', 'total_post' => 3,  'MQ' => 3,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    116 => ['cadre' => 'TRAD', 'total_post' => 12, 'MQ' => 11, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    117 => ['cadre' => 'LBWA', 'total_post' => 5,  'MQ' => 5,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],


    // TECHNICAL CADRES
    201 => ['cadre' => 'HLTH', 'total_post' => 14, 'MQ' => 13, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    202 => ['cadre' => 'AGRI', 'total_post' => 10, 'MQ' => 9,  'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    203 => ['cadre' => 'FISH', 'total_post' => 7,  'MQ' => 7,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    204 => ['cadre' => 'ANML', 'total_post' => 11, 'MQ' => 10, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    205 => ['cadre' => 'FDNT', 'total_post' => 16, 'MQ' => 15, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    206 => ['cadre' => 'STTC', 'total_post' => 5,  'MQ' => 5,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    207 => ['cadre' => 'EDUC', 'total_post' => 19, 'MQ' => 18, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    208 => ['cadre' => 'ROME', 'total_post' => 12, 'MQ' => 11, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    209 => ['cadre' => 'ENPH', 'total_post' => 2,  'MQ' => 1,  'CFF' => 0, 'EM' => 1, 'PHC' => 0],
    210 => ['cadre' => 'ENPW', 'total_post' => 14, 'MQ' => 13, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    211 => ['cadre' => 'ENWD', 'total_post' => 8,  'MQ' => 7,  'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    212 => ['cadre' => 'ARCH', 'total_post' => 6,  'MQ' => 6,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    213 => ['cadre' => 'FORE', 'total_post' => 17, 'MQ' => 16, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    214 => ['cadre' => 'TXTL', 'total_post' => 9,  'MQ' => 8,  'CFF' => 1, 'EM' => 0, 'PHC' => 0],

];

$technical_subject_mapping = [

    // Health
    'HLTH' => [
        101 => 'MBBS',
        102 => 'BDS',
        103 => 'Nursing',
    ],

    // Agriculture
    'AGRI' => [
        201 => 'Agronomy',
        202 => 'Horticulture',
        203 => 'Soil Science',
        204 => 'Plant Pathology',
    ],

    // Fisheries
    'FISH' => [
        301 => 'Aquaculture',
        302 => 'Fisheries Biology',
        303 => 'Fisheries Technology',
    ],

    // Animal Husbandry
    'ANML' => [
        401 => 'Veterinary Science',
        402 => 'Animal Genetics',
        403 => 'Animal Nutrition',
    ],

    // Food & Nutrition
    'FDNT' => [
        501 => 'Food Technology',
        502 => 'Nutrition & Dietetics',
    ],

    // Statistical
    'STTC' => [
        601 => 'Statistics',
        602 => 'Applied Statistics',
    ],

    // Education
    'EDUC' => [
        701 => 'Education',
        702 => 'Educational Psychology',
    ],

    // Engineering: Roads & Highways (Mechanical)
    'ROME' => [
        801 => 'Mechanical Engineering',
        802 => 'Automobile Engineering',
        803 => 'Industrial & Production Engineering',
    ],

    // Engineering: Public Health (Civil)
    'ENPH' => [
        901 => 'Civil Engineering',
        902 => 'Environmental Engineering',
        903 => 'Water Resources Engineering',
    ],

    // Engineering: Power (Electrical)
    'ENPW' => [
        1001 => 'Electrical Engineering',
        1002 => 'Power Systems',
        1003 => 'Energy Engineering',
    ],

    // Engineering: Water Development (Civil)
    'ENWD' => [
        1101 => 'Civil Engineering',
        1102 => 'Hydraulic Engineering',
    ],

    // Engineering: Architecture
    'ARCH' => [
        1201 => 'Architecture',
        1202 => 'Urban Planning',
    ],

    // Forestry
    'FORE' => [
        1301 => 'Forestry',
        1302 => 'Environmental Science',
    ],

    // Textile
    'TXTL' => [
        1401 => 'Textile Engineering',
        1402 => 'Industrial Textile Technology',
    ],

];


$candidates = [
    [
        'reg_no' => '20250001',
        'user_id' => 'USR1A2BC34',
        'cadre_category' => 'GG',
        'general_merit_position' => 5,
        'technical_merit_position' => null,
        'choice_list' => 'FRGN ADMN CUST COOP FAMP',
        'quota' => [
            'CFF' => true,
            'EM' => false,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250002',
        'user_id' => 'USR9X8Y7Z6',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['HLTH' => 3, 'ROME' => 7],
        'choice_list' => 'HLTH ROME EDUC',
        'quota' => [
            'CFF' => true,
            'EM' => false,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250003',
        'user_id' => 'USR7K6L5M4',
        'cadre_category' => 'GT',
        'general_merit_position' => 12,
        'technical_merit_position' => ['ENPH' => 5],
        'choice_list' => 'ADMN ENPH FRGN',
        'quota' => [
            'CFF' => false,
            'EM' => true,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250004',
        'user_id' => 'USR4P3Q2R1',
        'cadre_category' => 'GG',
        'general_merit_position' => 2,
        'technical_merit_position' => null,
        'choice_list' => 'ADMN FRGN ECON CUST',
        'quota' => [
            'CFF' => false,
            'EM' => false,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250005',
        'user_id' => 'USR5T6U7V8',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['TXTL' => 4, 'ENPW' => 9],
        'choice_list' => 'TXTL ENPW FORE',
        'quota' => [
            'CFF' => true,
            'EM' => true,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250006',
        'user_id' => 'USR3A4B5C6',
        'cadre_category' => 'GT',
        'general_merit_position' => 18,
        'technical_merit_position' => ['ROME' => 6, 'EDUC' => 10],
        'choice_list' => 'ROME ADMN EDUC CUST',
        'quota' => [
            'CFF' => false,
            'EM' => true,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250007',
        'user_id' => 'USR1Z2Y3X4',
        'cadre_category' => 'GG',
        'general_merit_position' => 9,
        'technical_merit_position' => null,
        'choice_list' => 'ADMN COOP TRAD INFO',
        'quota' => null
    ],
    [
        'reg_no' => '20250008',
        'user_id' => 'USR9Q8W7E6',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['ENPH' => 2],
        'choice_list' => 'ENPH EDUC HLTH',
        'quota' => [
            'CFF' => false,
            'EM' => false,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250009',
        'user_id' => 'USR8L7M6N5',
        'cadre_category' => 'GT',
        'general_merit_position' => 14,
        'technical_merit_position' => ['HLTH' => 8],
        'choice_list' => 'FRGN HLTH ADMN',
        'quota' => [
            'CFF' => true,
            'EM' => false,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250010',
        'user_id' => 'USR5C4D3E2',
        'cadre_category' => 'GG',
        'general_merit_position' => 1,
        'technical_merit_position' => null,
        'choice_list' => 'FRGN ADMN TAXN',
        'quota' => null
    ],
    [
        'reg_no' => '20250011',
        'user_id' => 'USR1G2H3I4',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['AGRI' => 5, 'FISH' => 11],
        'choice_list' => 'AGRI FISH FORE',
        'quota' => [
            'CFF' => true,
            'EM' => false,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250012',
        'user_id' => 'USR9B8C7D6',
        'cadre_category' => 'GT',
        'general_merit_position' => 6,
        'technical_merit_position' => ['ENPW' => 7],
        'choice_list' => 'ENPW ADMN RAIL',
        'quota' => [
            'CFF' => false,
            'EM' => true,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250013',
        'user_id' => 'USR7R6S5T4',
        'cadre_category' => 'GG',
        'general_merit_position' => 10,
        'technical_merit_position' => null,
        'choice_list' => 'ADMN INFO TRAD',
        'quota' => [
            'CFF' => false,
            'EM' => false,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250014',
        'user_id' => 'USR2M3N4O5',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['EDUC' => 12],
        'choice_list' => 'EDUC HLTH ENPH',
        'quota' => null
    ],
    [
        'reg_no' => '20250015',
        'user_id' => 'USR4W3E2R1',
        'cadre_category' => 'GT',
        'general_merit_position' => 22,
        'technical_merit_position' => ['TXTL' => 9],
        'choice_list' => 'TXTL ADMN COOP',
        'quota' => [
            'CFF' => false,
            'EM' => true,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250016',
        'user_id' => 'USR6H5J4K3',
        'cadre_category' => 'GG',
        'general_merit_position' => 4,
        'technical_merit_position' => null,
        'choice_list' => 'FRGN ADMN RAil',
        'quota' => [
            'CFF' => true,
            'EM' => false,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250017',
        'user_id' => 'USR8Q7W6E5',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['ROME' => 11],
        'choice_list' => 'ROME ENPW ENWD',
        'quota' => [
            'CFF' => false,
            'EM' => true,
            'PHC' => false
        ]
    ],
    [
        'reg_no' => '20250018',
        'user_id' => 'USR2V3B4N5',
        'cadre_category' => 'GT',
        'general_merit_position' => 11,
        'technical_merit_position' => ['FORE' => 4],
        'choice_list' => 'FORE ADMN FAMP',
        'quota' => [
            'CFF' => false,
            'EM' => false,
            'PHC' => true
        ]
    ],
    [
        'reg_no' => '20250019',
        'user_id' => 'USR1C2V3B4',
        'cadre_category' => 'GG',
        'general_merit_position' => 20,
        'technical_merit_position' => null,
        'choice_list' => 'ADMN TRAD INFO',
        'quota' => null
    ],
    [
        'reg_no' => '20250020',
        'user_id' => 'USR9P8O7I6',
        'cadre_category' => 'TT',
        'general_merit_position' => null,
        'technical_merit_position' => ['ARCH' => 3],
        'choice_list' => 'ARCH ENWD ENPW',
        'quota' => [
            'CFF' => true,
            'EM' => false,
            'PHC' => true
        ]
    ],
];



// Assume you have these arrays already
// $candidates, $cadres

$GEN = [];  
$TEC = [];  

$general_cadres  = array_keys($cadres['GENERAL']);
$technical_cadres = array_keys($cadres['TECHNICAL']);


// MAIN LOOP — SPLIT CANDIDATES
foreach ($candidates as $candidate) {

    $choices = explode(" ", trim($candidate['choice_list']));

    $general_choices = [];
    $technical_choices = [];

    foreach ($choices as $c) {
        if (in_array($c, $general_cadres)) {
            $general_choices[] = $c;
        } elseif (in_array($c, $technical_cadres)) {
            $technical_choices[] = $c;
        }
    }

    // GG → Goes to GEN only
    if ($candidate['cadre_category'] === 'GG') {
        $GEN[] = [
            'candidate' => $candidate,
            'choices'   => $general_choices,
            'quota'     => $candidate['quota'] ?? []
        ];
        continue;
    }

    // TT → Goes to TEC only
    if ($candidate['cadre_category'] === 'TT') {
        $TEC[] = [
            'candidate' => $candidate,
            'choices'   => $technical_choices,
            'quota'     => $candidate['quota'] ?? []
        ];
        continue;
    }

    // GT → Split into GEN + TEC entries
    if ($candidate['cadre_category'] === 'GT') {

        if (!empty($general_choices)) {
            $GEN[] = [
                'candidate' => $candidate,
                'choices'   => $general_choices,
                'quota'     => $candidate['quota'] ?? []
            ];
        }

        if (!empty($technical_choices)) {
            $TEC[] = [
                'candidate' => $candidate,
                'choices'   => $technical_choices,
                'quota'     => $candidate['quota'] ?? []
            ];
        }
    }
}



// SORTING GEN → by general_merit_position
usort($GEN, function($a, $b) {
    return $a['candidate']['general_merit_position'] <=> $b['candidate']['general_merit_position'];
});



// SORTING TEC → by technical merit → fallback general merit
usort($TEC, function($a, $b) {

    $a_bestTech = !empty($a['candidate']['technical_merit_position'])
        ? min($a['candidate']['technical_merit_position'])
        : null;

    $b_bestTech = !empty($b['candidate']['technical_merit_position'])
        ? min($b['candidate']['technical_merit_position'])
        : null;

    if ($a_bestTech !== null && $b_bestTech !== null) {
        return $a_bestTech <=> $b_bestTech;
    }

    return $a['candidate']['general_merit_position'] <=> $b['candidate']['general_merit_position'];
});


// Optional: print result
echo "<h2>General Separation:</h2><br>";
echo '<pre>';
print_r($GEN);
echo '</pre>';

echo "<br><br><h2>Technical Separation:</h2><br>";
echo '<pre>';
print_r($TEC);
echo '</pre>';


$general_placement_result = [];
$post_remaining = $post_available; // local working copy

foreach ($GEN as $entry) {

    $candidate = $entry['candidate'];
    $choices   = $entry['choices'];
    $quotaList = $entry['quota'];  // ordered: CFF → EM → PHC

    $assigned = false;

    foreach ($choices as $cadreShort) {

        // Resolve cadre short → code
        $cadreCode = null;
        foreach ($cadres['GENERAL'] as $short => $info) {
            if ($short === $cadreShort) {
                $cadreCode = $info['code'];
                break;
            }
        }
        if (!$cadreCode) continue;

        /*
        ===============================================================
        STEP 1 — MERIT FIRST (MQ)
        ===============================================================
        */
        if ($post_remaining[$cadreCode]['MQ'] > 0) {
            
            // MERIT seat available → assign immediately
            $post_remaining[$cadreCode]['MQ']--;
            $assigned = true;

            $general_placement_result[] = [
                'candidate' => $candidate,
                'cadre'     => $cadreShort,
                'quota'     => 'MERIT'
            ];

            break; // stop processing candidate
        }

        /*
        ===============================================================
        STEP 2 — TRY QUOTA (CFF → EM → PHC)
        Only executed when MQ seat is NOT available
        ===============================================================
        */
        foreach (['CFF', 'EM', 'PHC'] as $quotaType) {

            if (empty($quotaList[$quotaType])) {
                continue; // candidate not eligible for this quota
            }

            // CFF
            if ($quotaType === 'CFF' && $post_remaining[$cadreCode]['CFF'] > 0) {
                $post_remaining[$cadreCode]['CFF']--;
                $assigned = true;
                $general_placement_result[] = [
                    'candidate' => $candidate,
                    'cadre'     => $cadreShort,
                    'quota'     => 'CFF'
                ];
                break 2;
            }

            // EM
            if ($quotaType === 'EM' && $post_remaining[$cadreCode]['EM'] > 0) {
                $post_remaining[$cadreCode]['EM']--;
                $assigned = true;
                $general_placement_result[] = [
                    'candidate' => $candidate,
                    'cadre'     => $cadreShort,
                    'quota'     => 'EM'
                ];
                break 2;
            }

            // PHC
            if ($quotaType === 'PHC' && $post_remaining[$cadreCode]['PHC'] > 0) {
                $post_remaining[$cadreCode]['PHC']--;
                $assigned = true;
                $general_placement_result[] = [
                    'candidate' => $candidate,
                    'cadre'     => $cadreShort,
                    'quota'     => 'PHC'
                ];
                break 2;
            }
        }


        // If neither MQ nor quota works → try next cadre choice
    }

    /*
    ===============================================================
    STEP 3 — NOT ASSIGNED
    ===============================================================
    */
    if (!$assigned) {
        $general_placement_result[] = [
            'candidate' => $candidate,
            'cadre'     => null,
            'quota'     => null,
            'status'    => 'NOT_ASSIGNED'
        ];
    }
}


echo "<br><br><h2>General Placement:</h2><br>";
echo '<pre>';
print_r($general_placement_result);
echo '</pre>';


$technical_placement_result = [];
$tech_post_remaining = $post_available;   // local working copy

foreach ($TEC as $entry) {

    $candidate   = $entry['candidate'];
    $choices     = $entry['choices'];     // technical cadre short codes
    $quotaList   = $entry['quota'];       // {CFF=>true|false,...}
    $techMerit   = $candidate['technical_merit_position'];

    $assigned = false;

    // Sort technical choices by candidate's technical merit ranking (ascending)
    usort($choices, function($a, $b) use ($techMerit) {
        $rankA = $techMerit[$a] ?? PHP_INT_MAX;
        $rankB = $techMerit[$b] ?? PHP_INT_MAX;
        return $rankA <=> $rankB;
    });

    foreach ($choices as $cadreShort) {

        /*
        ==========================================================
        Resolve Technical Cadre Short → Cadre Code
        ==========================================================
        */
        $cadreCode = null;
        foreach ($cadres['TECHNICAL'] as $short => $info) {
            if ($short === $cadreShort) {
                $cadreCode = $info['code'];
                break;
            }
        }
        if (!$cadreCode) continue;

        /*
        ==========================================================
        STEP 1 — DIRECT TECHNICAL MERIT (equivalent to MQ)
        ==========================================================
        */

        // If candidate has a rank for this cadre and MQ available
        if (isset($techMerit[$cadreShort]) && $tech_post_remaining[$cadreCode]['MQ'] > 0) {

            $tech_post_remaining[$cadreCode]['MQ']--;
            $assigned = true;

            $technical_placement_result[] = [
                'candidate' => $candidate,
                'cadre'     => $cadreShort,
                'quota'     => 'MERIT'
            ];

            break; // stop processing this candidate
        }

        /*
        ==========================================================
        STEP 2 — If MQ full, try QUOTA (CFF → EM → PHC)
        ==========================================================
        */
        foreach (['CFF', 'EM', 'PHC'] as $quotaType) {

            // Candidate must be eligible for the quota
            if (empty($quotaList[$quotaType])) {
                continue;
            }

            if ($quotaType === 'CFF' && $tech_post_remaining[$cadreCode]['CFF'] > 0) {
                $tech_post_remaining[$cadreCode]['CFF']--;
                $assigned = true;
                $technical_placement_result[] = [
                    'candidate' => $candidate,
                    'cadre'     => $cadreShort,
                    'quota'     => 'CFF'
                ];
                break 2;
            }

            if ($quotaType === 'EM' && $tech_post_remaining[$cadreCode]['EM'] > 0) {
                $tech_post_remaining[$cadreCode]['EM']--;
                $assigned = true;
                $technical_placement_result[] = [
                    'candidate' => $candidate,
                    'cadre'     => $cadreShort,
                    'quota'     => 'EM'
                ];
                break 2;
            }

            if ($quotaType === 'PHC' && $tech_post_remaining[$cadreCode]['PHC'] > 0) {
                $tech_post_remaining[$cadreCode]['PHC']--;
                $assigned = true;
                $technical_placement_result[] = [
                    'candidate' => $candidate,
                    'cadre'     => $cadreShort,
                    'quota'     => 'PHC'
                ];
                break 2;
            }
        }

        // If not assigned, try next technical choice
    }

    /*
    ==========================================================
    STEP 3 — NOT ASSIGNED
    ==========================================================
    */
    if (!$assigned) {
        $technical_placement_result[] = [
            'candidate' => $candidate,
            'cadre'     => null,
            'quota'     => null,
            'status'    => 'NOT_ASSIGNED'
        ];
    }
}

echo "<br><br><h2>Technical Placement:</h2><br>";
echo '<pre>';
print_r($technical_placement_result);
echo '</pre>';



