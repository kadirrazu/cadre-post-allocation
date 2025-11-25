<?php

include "cadre-list.php";
include "posts.php";
include "candidates.php";
include "data-processing.php";

?>

<!doctype html>
<html lang="en">
    <head>
    <meta charset="utf-8">
    <title>BCS Exam Cadre Allocation — Results - Single Candidate Single Pass</title>
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

            <h4 class="text-center text-info">Cadre Wise Assignments</h4>

            <?php 
                $j = 1; 
                $totalAllocationCount = 0; 
                foreach( $allocation as $cadreAbbr => $allocated ): 
            ?>

            <h5>
                <?php 
                    echo $cadreAbbr . ' - ' . count($allocated); 
                    $j++;
                    $totalAllocationCount += count($allocated);
                ?>
            </h5>

            <table id="tblGeneral" class="table table-striped table-bordered">
                <thead class="table-info">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Allocation Type</th>
                        <th class="text-center">Avl. Quota</th>
                        <th class="text-center">Gen Merit</th>
                        <th class="text-center">Tech Merit</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                    <?php $i = 1; foreach( $allocated as $candidate ): ?>

                    <tr>
                        <td class="text-center">
                            <?= $i++ ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($candidate['reg_no']) ?>
                        </td>
                        <td class="text-center">
                            <?= htmlspecialchars($candidate['candidate']['candidate']['cadre_category']) ?>
                        </td>
                        <td class="text-center">
                            <?php echo $candidate['candidate']['quota']; ?>
                        </td>
                        <td class="text-center">
                            <?php 
                                if( isset($candidate['candidate']['candidate']['quota']) )
                                {
                                    foreach( $candidate['candidate']['candidate']['quota'] as $key => $value ){
                                        if($key == 'CFF' && $value == 1){
                                            echo '-' . $key;
                                        }
                                        else if($key == 'EM' && $value == 1){
                                        echo '-' . $key; 
                                        }
                                        else if($key == 'PHC' && $value == 1){
                                            echo '-' . $key;
                                        }
                                    }
                                }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php echo $candidate['candidate']['candidate']['general_merit_position']; ?>
                        </td>
                        </td>
                        <td class="text-center">
                            <?php echo $candidate['candidate']['candidate']['global_tech_merit'] ?? ''; ?>
                            <br>
                            <?php 
                                if(isset($candidate['candidate']['candidate']['technical_merit_position']))
                                {
                                    foreach( $candidate['candidate']['candidate']['technical_merit_position'] as $key => $value ){
                                        print $key .'-'. $value . '<br>';
                                    }
                                }
                            ?>
                        </td>
                        <td>
                            <?php 
                                if( isset( $candidate['candidate']['candidate']['raw_choice_list'] ) )
                                {
                                    echo $candidate['candidate']['candidate']['raw_choice_list'];
                                }
                                else
                                {
                                    echo $candidate['candidate']['candidate']['choice_list'];
                                }
                            ?>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <?php 
        
                endforeach; 
                
                echo '<span class="fw-bold text-primary">Total Allocation Count: ' . $totalAllocationCount . '</span>';
            ?>

        </div>

        <div class="col-12">

            <h4 class="text-center text-danger">Non-Assigned Candidates</h4>
            <p class="text-primary text-center">Count: <?php echo count( $unallocated ); ?></p>

            <table id="tblUn" class="table table-striped table-bordered">

                <thead class="table-danger">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Gen Merit</th>
                        <th class="text-center">Tech Merit</th>
                        <th class="text-center">Quota</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                <?php $i = 1; foreach( $unallocated as $unCand ): ?>

                <tr>
                    <td class="text-center">
                        <?= $i++ ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($unCand['reg_no']) ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($unCand['cadre_category']) ?>
                    </td>
                    <td class="text-center">
                        <?php echo $unCand['general_merit_position']; ?>
                    </td>
                    </td>
                    <td class="text-center">
                        <?php echo $unCand['global_tech_merit'] ?? ''; ?>
                        <br>
                        <?php 
                            if(isset($unCand['technical_merit_position']))
                            {
                                foreach( $unCand['technical_merit_position'] as $key => $value ){
                                    print $key .'-'. $value . '<br>';
                                }
                            }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            if(isset($unCand['quota']))
                            {
                                foreach( $unCand['quota'] as $key => $value ){
                                    if($key == 'CFF' && $value == 1){
                                        echo '-' . $key;
                                    }
                                    else if($key == 'EM' && $value == 1){
                                    echo '-' . $key; 
                                    }
                                    else if($key == 'PHC' && $value == 1){
                                        echo '-' . $key;
                                    }
                                }
                            }
                        ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($unCand['raw_choice_list'] ?? $unCand['choice_list']) ?>
                    </td>
                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

        <div class="col-12">

            <h4  class="mx-3 text-success text-center">Raw Candidate List - Before Allocation</h4>

            <table id="tblUn" class="table table-striped table-bordered">

                <thead class="table-danger">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Merit Position (General)</th>
                        <th class="text-center">Merit Position (Tech)</th>
                        <th class="text-center">Quota</th>
                        <th class="text-center">Choices</th>
                    </tr>
                </thead>
                <tbody>

                <?php $i = 1; foreach( $candidates as $r ):  ?>

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
                    <td class="text-center">
                        <?= htmlspecialchars($r['general_merit_position'] ?? '-') ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            if(isset($r['technical_merit_position']))
                            {
                                foreach( $r['technical_merit_position'] as $key => $value ){
                                    print $key .'-'. $value . '<br>';
                                }
                            }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            if(isset($r['quota']))
                            {
                                foreach( $r['quota'] as $key => $value ){
                                    if($key == 'CFF' && $value == 1){
                                        echo $key . '<br>';
                                    }
                                    else if($key == 'EM' && $value == 1){
                                       echo $key . '<br>'; 
                                    }
                                    else if($key == 'PHC' && $value == 1){
                                        echo $key . '<br>';
                                    }
                                }
                            }
                        ?>
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
        <!--<h6>Post remaining snapshot (for debuging)</h6>
        <pre>
            <?php //echo htmlspecialchars( json_encode($post_remaining, JSON_PRETTY_PRINT) ); ?>
        </pre>-->
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
