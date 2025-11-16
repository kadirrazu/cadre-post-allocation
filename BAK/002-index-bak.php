<?php

// ===================== DATA =====================

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
        'choice_list' => 'EDUC ROME ADMN CUST',
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
        'choice_list' => 'FISH AGRI FORE',
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


// ===================== STEP 1: SPLIT CANDIDATES =====================
$GEN=[]; $TEC=[];

foreach($candidates as $c){
    $choices = array_map('strtoupper', explode(" ", $c['choice_list']));
    $gen_choices = array_values(array_intersect($choices, array_keys($cadres['GENERAL'])));
    $tec_choices = array_values(array_intersect($choices, array_keys($cadres['TECHNICAL'])));

    if($c['cadre_category']=='GG') $GEN[] = ['candidate'=>$c,'choices'=>$gen_choices];
    elseif($c['cadre_category']=='TT') $TEC[] = ['candidate'=>$c,'choices'=>$tec_choices];
    elseif($c['cadre_category']=='GT'){
        if($gen_choices) $GEN[] = ['candidate'=>$c,'choices'=>$gen_choices];
        if($tec_choices) $TEC[] = ['candidate'=>$c,'choices'=>$tec_choices];
    }
}

// ===================== STEP 2: SORT BY MERIT =====================

// General: single number
usort($GEN, fn($a,$b)=>($a['candidate']['general_merit_position']??PHP_INT_MAX) <=> ($b['candidate']['general_merit_position']??PHP_INT_MAX));

// Technical: use minimum merit among choices
usort($TEC, function($a,$b){
    $a_merit = $a['candidate']['technical_merit_position'] ?? PHP_INT_MAX;
    $b_merit = $b['candidate']['technical_merit_position'] ?? PHP_INT_MAX;

    $a_min = is_array($a_merit) ? min($a_merit) : $a_merit;
    $b_min = is_array($b_merit) ? min($b_merit) : $b_merit;

    return $a_min <=> $b_min;
});

// ===================== STEP 3: ASSIGN CANDIDATES =====================
function assignCandidates($list, $post_available){
    $result=[];
    foreach($list as $entry){
        $c = $entry['candidate'];
        $assigned=false;
        foreach($entry['choices'] as $choice){
            foreach($post_available as $code=>$post){
                if($post['cadre']==$choice){
                    // Check MERIT
                    $merit_ok = false;
                    if(isset($c['general_merit_position'])) $merit_ok = $post_available[$code]['MQ']>0;
                    elseif(isset($c['technical_merit_position'][$choice])) $merit_ok = $post_available[$code]['MQ']>0;

                    if($merit_ok){
                        $post_available[$code]['MQ']--;
                        $assigned=true;
                        $result[]=['candidate'=>$c,'cadre'=>$choice,'quota'=>'MERIT'];
                        break 2;
                    }

                    // Check quota
                    if(!empty($c['quota'])){
                        foreach(['CFF','EM','PHC'] as $q){
                            if(!empty($c['quota'][$q]) && $post_available[$code][$q]>0){
                                $post_available[$code][$q]--;
                                $assigned=true;
                                $result[]=['candidate'=>$c,'cadre'=>$choice,'quota'=>$q];
                                break 3;
                            }
                        }
                    }
                }
            }
        }
        if(!$assigned) $result[]=['candidate'=>$c,'cadre'=>null,'quota'=>null];
    }
    return $result;
}

$general_result = assignCandidates($GEN, $post_available);
$technical_result = assignCandidates($TEC, $post_available);

// ===================== STEP 4: OUTPUT =====================
/*echo "<pre>GENERAL RESULTS:\n"; print_r($general_result); echo "</pre>";
echo "<pre>TECHNICAL RESULTS:\n"; print_r($technical_result); echo "</pre>";*/

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cadre Allocation Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">Cadre Allocation Results</h1>

    <h3>General Cadres</h3>
    <table id="generalTable" class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Reg No</th>
                <th>User ID</th>
                <th>Choice Assigned</th>
                <th>Quota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($general_result as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['candidate']['reg_no']) ?></td>
                    <td><?= htmlspecialchars($r['candidate']['user_id']) ?></td>
                    <td><?= htmlspecialchars($r['cadre'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['quota'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 class="mt-5">Technical Cadres</h3>
    <table id="technicalTable" class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Reg No</th>
                <th>User ID</th>
                <th>Choice Assigned</th>
                <th>Quota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($technical_result as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['candidate']['reg_no']) ?></td>
                    <td><?= htmlspecialchars($r['candidate']['user_id']) ?></td>
                    <td><?= htmlspecialchars($r['cadre'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['quota'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#generalTable').DataTable();
        $('#technicalTable').DataTable();
    });
</script>
</body>
</html>

