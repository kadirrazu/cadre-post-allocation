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

            <h4 class="">General Assignments</h4>

            <table id="tblGeneral" class="table table-striped table-bordered">
                <thead class="table-info">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Reg No</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Cadre</th>
                        <th class="text-center">Quota</th>
                        <th class="text-center">Gen Merit Position</th>
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
                            <?php echo $r['candidate']['general_merit_position']; ?>
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

                    <?php $i = 1; foreach( $final_technical as $r ): /*echo '<pre>'; var_dump($r);  echo '<pre>'; die;*/ ?>
                        
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
                            <?php echo $r['cadre'] . " - "; print( $r['candidate']['technical_merit_position'][$r['cadre']]) ?>
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
                        <th class="text-center">Merit Position (General)</th>
                        <th class="text-center">Merit Position (Tech)</th>
                        <th class="text-center">Quota</th>
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
                    <td class="text-center">
                        <?= htmlspecialchars($r['candidate']['general_merit_position'] ?? '-') ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            if(isset($r['candidate']['technical_merit_position']))
                            {
                                foreach( $r['candidate']['technical_merit_position'] as $key => $value ){
                                    print $key .'-'. $value . '<br>';
                                }
                            }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            if(isset($r['candidate']['quota']))
                            {
                                foreach( $r['candidate']['quota'] as $key => $value ){
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
                        <?= htmlspecialchars($r['candidate']['choice_list']) ?>
                    </td>
                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

        <div class="col-12">

            <h4  class="mx-3">Raw Candidate Table - Before Allocation</h4>

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
