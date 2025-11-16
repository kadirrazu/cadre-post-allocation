<?php

include "cadre-list.php";
include "posts.php";
include "candidates.php";

// extract short name lists for quick checks
$general_keys = array_keys($general_cadres['GENERAL']);
$technical_keys = array_keys($technical_cadres['TECHNICAL']);

// Make a working copy of post availability
$post_remaining = $post_available;

// Helper functions for craft the application
function primary_merit($cand) {
    // If candidate has general merit (GG or GT) use that as primary
    if (!empty($cand['general_merit_position'])) return $cand['general_merit_position'];
    // else if technical merits exist, use the minimum (best) technical rank
    if (!empty($cand['technical_merit_position']) && is_array($cand['technical_merit_position'])) {
        return min($cand['technical_merit_position']);
    }
    return PHP_INT_MAX;
}

// Normalize a choice token (trim + uppercase)
function normChoice($token) {
    return strtoupper(trim($token));
}

// sort candidates by primary merit
usort($candidates, function($a, $b){
    return primary_merit($a) <=> primary_merit($b);
});

// SINGLE PASS ALLOCATION 
$final_alloc = [];

foreach ($candidates as $cand) 
{
    $assigned = false;
    $rawChoices = explode(' ', $cand['choice_list']);
    $choices = array_map('normChoice', $rawChoices);

    foreach ($choices as $ch) 
    {
        // skip invalid
        if ($ch === '') continue;

        // determine type (GENERAL or TECHNICAL)
        $type = null;

        if (in_array($ch, $general_keys, true)) $type = 'GENERAL';

        elseif (in_array($ch, $technical_keys, true)) $type = 'TECHNICAL';

        else continue; // unknown choice

        // For technical choices findout the candidate actually has a technical merit entry for that cadre
        if( $type === 'TECHNICAL' ) 
        {
            if( empty($cand['technical_merit_position']) || !is_array($cand['technical_merit_position']) || !isset($cand['technical_merit_position'][$ch]) ) 
            {
                // candidate is not eligible for this technical cadre (no tech merit) — skip
                continue;
            }
        }

        // Find the post code in $post_remaining that matches this short cadre name
        $foundPostCode = null;

        foreach( $post_remaining as $code => $post ) 
        {
            if ($post['cadre'] === $ch) { $foundPostCode = $code; break; }
        }

        if( $foundPostCode === null ) continue; // no post entry found

        // MERIT check
        $canUseMerit = false;

        if( $type === 'GENERAL' && !empty($cand['general_merit_position']) )
        {
            $canUseMerit = ($post_remaining[$foundPostCode]['MQ'] > 0);
        } 
        elseif( $type === 'TECHNICAL' ) 
        {
            // technical candidate must have a tech merit for this cadre, 
            // and MQ seat must exist
            $canUseMerit = ($post_remaining[$foundPostCode]['MQ'] > 0);
        }

        if( $canUseMerit ) 
        {
            $post_remaining[$foundPostCode]['MQ']--;

            $final_alloc[] = ['candidate'=>$cand, 'cadre'=>$ch, 'quota'=>'MERIT', 'type'=>$type];

            $assigned = true;

            break;
        }

        // QUOTA check (CFF -> EM -> PHC)
        if( !empty($cand['quota']) && is_array($cand['quota']) )
        {
            foreach( ['CFF','EM','PHC'] as $q )
            {
                if( !empty($cand['quota'][$q]) && !empty($post_remaining[$foundPostCode][$q]) && $post_remaining[$foundPostCode][$q] > 0 )
                {
                    // For technical cadre, still require tech eligibility
                    $post_remaining[$foundPostCode][$q]--;

                    $final_alloc[] = ['candidate'=>$cand, 'cadre'=>$ch, 'quota'=>$q, 'type'=>$type];

                    $assigned = true;

                    break 2; // assigned via quota; so stop checking choices
                }
            }
        }

        // otherwise, try next choice
    }

    if(!$assigned)
    {
        $final_alloc[] = ['candidate'=>$cand, 'cadre'=>null, 'quota'=>null, 'type'=>null];
    }
}

// SPLIT RESULTS FOR USING IT DURING DISPLAYING DATA
$final_general = array_values(array_filter($final_alloc, fn($r)=> $r['type'] === 'GENERAL'));

$final_technical = array_values(array_filter($final_alloc, fn($r)=> $r['type'] === 'TECHNICAL'));

$final_unassigned = array_values(array_filter($final_alloc, fn($r)=> $r['type'] === null));

// Sort general by general merit for nicer output
usort($final_general, function($a,$b){
    return ($a['candidate']['general_merit_position'] ?? PHP_INT_MAX) <=> ($b['candidate']['general_merit_position'] ?? PHP_INT_MAX);
});

// HTML

?>

<!doctype html>
<html lang="en">
    <head>
    <meta charset="utf-8">
    <title>BCS Exam Cadre Allocation — Results</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body{background:#f7f7fb}
        .quota-MERIT{font-weight:600}
        .quota-CFF{color:#0d6efd}
        .quota-EM{color:#198754}
        .quota-PHC{color:#dc3545}
    </style>
    </head>
<body>
    <div class="container py-4">
    <h1 class="mb-3 text-center">Cadre Allocation Results</h1>

    <div class="row">
        <div class="col-12 mb-4 mt-2">

            <h4 class="">General Assignments</h4>

            <table id="tblGeneral" class="table table-striped table-bordered">
                <thead class="table-info">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Cadre</th>
                        <th class="text-center">Quota</th>
                        <th class="text-center">Gen Merit</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                    <?php $i = 1; foreach( $final_general as $r ): ?>

                    <tr>
                        <td class="text-center">
                            <?= $i++ ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['candidate']['reg_no']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['candidate']['cadre_category']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['cadre']) ?>
                        </td>
                        <td class="text-center quota-<?= htmlspecialchars($r['quota']) ?>">
                            <?= htmlspecialchars($r['quota']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['candidate']['general_merit_position'] ?? '-') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($r['candidate']['choice_list']) ?>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="col-12 mb-4">

            <h4 class="mb-3">Technical Assignments</h4>

            <table id="tblTech" class="table table-striped table-bordered">

                <thead class="table-success">
                    <tr>
                        <th class="text-center"h>#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Cadre</th>
                        <th class="text-center">Quota</th>
                        <th class="text-center">Tech Merit (for cadre)</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                    <?php $i = 1; foreach( $final_technical as $r ): ?>
                        
                    <tr>
                        <td class="text-center">
                            <?= $i++ ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['candidate']['reg_no']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['candidate']['cadre_category']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['cadre']) ?>
                        </td>
                        <td class="text-center quota-<?= htmlspecialchars($r['quota']) ?>">
                            <?= htmlspecialchars($r['quota']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($r['candidate']['technical_merit_position'][$r['cadre']] ?? '-') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($r['candidate']['choice_list']) ?>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <div class="col-12">

            <h4  class="mb-2">Unassigned Candidates</h4>

            <table id="tblUn" class="table table-striped table-bordered">

                <thead class="table-danger">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                <?php $i = 1; foreach( $final_unassigned as $r ): ?>

                <tr>
                    <td class="text-center">
                        <?= $i++ ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($r['candidate']['reg_no']) ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($r['candidate']['cadre_category']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($r['candidate']['choice_list']) ?>
                    </td>
                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

        <div class="col-12">

            <h4  class="mb-2">Raw Candidate Table - Before Allocation</h4>

            <table id="tblUn" class="table table-striped table-bordered">

                <thead class="table-danger">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                <?php $i = 1; foreach( $candidates as $r ): ?>

                <tr>
                    <td class="text-center">
                        <?= $i++ ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($r['reg_no']) ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($r['cadre_category']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($r['choice_list']) ?>
                    </td>
                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

    </div>

    <div class="mt-4">
        <h6>Post remaining snapshot (for debuging)</h6>
        <pre>
            <?php echo htmlspecialchars( json_encode($post_remaining, JSON_PRETTY_PRINT) ); ?>
        </pre>
    </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function(){
            $('#tblGeneral').DataTable({ "pageLength": 20 });

            $('#tblTech').DataTable({ "pageLength": 20 });

            $('#tblUn').DataTable({ "pageLength": 20 });

        });
    </script>
</body>
</html>
