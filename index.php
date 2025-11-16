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
    101 => ['cadre' => 'ADMN', 'total_post' => 15, 'MQ' => 14, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
    102 => ['cadre' => 'POLC', 'total_post' => 7,  'MQ' => 7,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
    103 => ['cadre' => 'FRGN', 'total_post' => 12, 'MQ' => 11, 'CFF' => 1, 'EM' => 0, 'PHC' => 0],
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
    209 => ['cadre' => 'ENPH', 'total_post' => 3,  'MQ' => 3,  'CFF' => 0, 'EM' => 0, 'PHC' => 0],
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
        'user_id' => 'USR1A2B3C4',
        'cadre_category' => 'GG',
        'general_merit_position' => 1,
        'technical_merit_position' => null,
        'choice_list' => 'ADMN POLC FRGN',
    ],
    [
        'reg_no' => '20250002',
        'user_id' => 'USR5D6E7F8',
        'cadre_category' => 'TT',
        'general_merit_position' => 2,
        'technical_merit_position' => [
            'ROME' => 2,
            'ENPH' => 5,
        ],
        'choice_list' => 'ROME ENPH ENPW',
    ],
    [
        'reg_no' => '20250003',
        'user_id' => 'USR9G0H1I2',
        'cadre_category' => 'GT',
        'general_merit_position' => 3,
        'technical_merit_position' => [
            'HLTH' => 1,
            'EDUC' => 4,
        ],
        'choice_list' => 'HLTH FRGN EDUC ADMN FORE',
    ],
    [
        'reg_no' => '20250004',
        'user_id' => 'USR3J4K5L6',
        'cadre_category' => 'GG',
        'general_merit_position' => 4,
        'technical_merit_position' => null,
        'choice_list' => 'TAXN CUST POST',
    ],
    [
        'reg_no' => '20250005',
        'user_id' => 'USR7M8N9O0',
        'cadre_category' => 'TT',
        'general_merit_position' => 5,
        'technical_merit_position' => [
            'FDNT' => 1,
            'ANML' => 3,
        ],
        'choice_list' => 'FDNT ANML AGRI',
    ],
    [
        'reg_no' => '20250006',
        'user_id' => 'USR1P2Q3R4',
        'cadre_category' => 'GT',
        'general_merit_position' => 6,
        'technical_merit_position' => [
            'TXTL' => 2,
            'ROME' => 5,
        ],
        'choice_list' => 'TXTL FRGN ROME ADMN POLC',
    ],
    [
        'reg_no' => '20250007',
        'user_id' => 'USR5S6T7U8',
        'cadre_category' => 'GG',
        'general_merit_position' => 7,
        'technical_merit_position' => null,
        'choice_list' => 'COOP RAIL FOOD',
    ],
    [
        'reg_no' => '20250008',
        'user_id' => 'USR9V0W1X2',
        'cadre_category' => 'TT',
        'general_merit_position' => 8,
        'technical_merit_position' => [
            'ENPW' => 3,
            'ENWD' => 6,
        ],
        'choice_list' => 'ENPW ENWD HLTH',
    ],
    [
        'reg_no' => '20250009',
        'user_id' => 'USR3Y4Z5A6',
        'cadre_category' => 'GT',
        'general_merit_position' => 9,
        'technical_merit_position' => [
            'HLTH' => 2,
            'FORE' => 5,
        ],
        'choice_list' => 'HLTH FRGN FORE TAXN COOP',
    ],
    [
        'reg_no' => '20250010',
        'user_id' => 'USR7B8C9D0',
        'cadre_category' => 'GG',
        'general_merit_position' => 10,
        'technical_merit_position' => null,
        'choice_list' => 'INFO ANSA FAMP',
    ],
    [
        'reg_no' => '20250011',
        'user_id' => 'USR1E2F3G4',
        'cadre_category' => 'TT',
        'general_merit_position' => 11,
        'technical_merit_position' => [
            'ARCH' => 1,
            'ENPH' => 4,
        ],
        'choice_list' => 'ARCH ENPH EDUC',
    ],
    [
        'reg_no' => '20250012',
        'user_id' => 'USR5H6I7J8',
        'cadre_category' => 'GT',
        'general_merit_position' => 12,
        'technical_merit_position' => [
            'ROME' => 3,
            'TXTL' => 6,
        ],
        'choice_list' => 'ROME TRAD TXTL ADMN FRGN',
    ],
    [
        'reg_no' => '20250013',
        'user_id' => 'USR9K0L1M2',
        'cadre_category' => 'GG',
        'general_merit_position' => 13,
        'technical_merit_position' => null,
        'choice_list' => 'ACNT TAXN CUST',
    ],
    [
        'reg_no' => '20250014',
        'user_id' => 'USR3N4O5P6',
        'cadre_category' => 'TT',
        'general_merit_position' => 14,
        'technical_merit_position' => [
            'FDNT' => 2,
            'ANML' => 4,
        ],
        'choice_list' => 'FDNT ANML HLTH',
    ],
    [
        'reg_no' => '20250015',
        'user_id' => 'USR7Q8R9S0',
        'cadre_category' => 'GT',
        'general_merit_position' => 15,
        'technical_merit_position' => [
            'ENWD' => 2,
            'FORE' => 3,
        ],
        'choice_list' => 'ENWD FRGN FORE ADMN EDUC',
    ],
    [
        'reg_no' => '20250016',
        'user_id' => 'USR1T2U3V4',
        'cadre_category' => 'GG',
        'general_merit_position' => 16,
        'technical_merit_position' => null,
        'choice_list' => 'STAT ECON TRAD',
    ],
    [
        'reg_no' => '20250017',
        'user_id' => 'USR5W6X7Y8',
        'cadre_category' => 'TT',
        'general_merit_position' => 17,
        'technical_merit_position' => [
            'ENPH' => 1,
            'ENPW' => 5,
        ],
        'choice_list' => 'ENPH ENPW EDUC',
    ],
    [
        'reg_no' => '20250018',
        'user_id' => 'USR9Z0A1B2',
        'cadre_category' => 'GT',
        'general_merit_position' => 18,
        'technical_merit_position' => [
            'HLTH' => 3,
            'ROME' => 6,
        ],
        'choice_list' => 'HLTH ADMN ROME POLC EDUC',
    ],
    [
        'reg_no' => '20250019',
        'user_id' => 'USR3C4D5E6',
        'cadre_category' => 'GG',
        'general_merit_position' => 19,
        'technical_merit_position' => null,
        'choice_list' => 'FAMP COOP FOOD',
    ],
    [
        'reg_no' => '20250020',
        'user_id' => 'USR7F8G9H0',
        'cadre_category' => 'TT',
        'general_merit_position' => 20,
        'technical_merit_position' => [
            'TXTL' => 1,
            'ROME' => 4,
        ],
        'choice_list' => 'TXTL ROME ENWD',
    ],
];


// Assume you have these arrays already
// $candidates, $cadres

$GEN = [];
$TEC = [];

// Flatten short codes for quick lookup
$general_short_codes = array_keys($cadres['GENERAL']);
$technical_short_codes = array_keys($cadres['TECHNICAL']);

foreach ($candidates as $candidate) {
    // Split candidate's choice list by space
    $choices = explode(' ', $candidate['choice_list']);

    // Initialize arrays for separated choices
    $gen_choices = [];
    $tec_choices = [];

    // Loop through each choice
    foreach ($choices as $choice) {
        if (in_array($choice, $general_short_codes)) {
            $gen_choices[] = $choice;
        } elseif (in_array($choice, $technical_short_codes)) {
            $tec_choices[] = $choice;
        }
    }

    // Place candidate in GEN array if they have general choices
    if (!empty($gen_choices)) {
        $GEN[] = [
            'reg_no' => $candidate['reg_no'],
            'user_id' => $candidate['user_id'],
            'cadre_category' => $candidate['cadre_category'],
            'general_merit_position' => $candidate['general_merit_position'],
            'choice_list' => implode(' ', $gen_choices),
        ];
    }

    // Place candidate in TEC array if they have technical choices
    if (!empty($tec_choices)) {
        $TEC[] = [
            'reg_no' => $candidate['reg_no'],
            'user_id' => $candidate['user_id'],
            'cadre_category' => $candidate['cadre_category'],
            'general_merit_position' => $candidate['general_merit_position'],
            'technical_merit_position' => $candidate['technical_merit_position'] ?? null,
            'choice_list' => implode(' ', $tec_choices),
        ];
    }
}

// Sort GEN array by general_merit_position ascending
usort($GEN, function($a, $b) {
    return $a['general_merit_position'] <=> $b['general_merit_position'];
});

// Sort TEC array by general_merit_position ascending
usort($TEC, function($a, $b) {
    return $a['general_merit_position'] <=> $b['general_merit_position'];
});

// Optional: print result
echo '<pre>';
print_r($GEN);
echo '</pre>';

echo '<pre>';
print_r($TEC);
echo '</pre>';

