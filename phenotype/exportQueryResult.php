<?php

require "../includes/bootstrap.inc";
$mysqli = connecti();

/* process export table*/
if (isset($_POST['inTheseTrials'])) {
    $in_these_trials = $_POST['inTheseTrials'];
    $in_these_trials = "AND e.experiment_uid IN (" . $in_these_trials . ")";
} else {
    $in_these_trials = "";
}
$in_these_lines = "";
if ((is_array($_SESSION['selected_lines'])) && (count($_SESSION['selected_lines']) > 0) && ($_REQUEST['selectWithin'] == "Yes")) {
        $in_these_lines = "AND lr.line_record_uid IN (" . implode(",", $_SESSION['selected_lines']) . ")";
}
if (isset($_POST['phenotype'])) {
    $phenotype = $_POST['phenotype'];
} else {
    die("Error: missing phenotype");
}
if (isset($_POST['searchVal'])) {
    $searchVal = $_POST['searchVal'];
} else {
    die("Error: missing searchVal");
}
$query = "SELECT lr.line_record_uid, lr.line_record_name Line, lr.breeding_program_code Breeding_Program, pd.value, e.trial_code Trial
          FROM line_records as lr, tht_base, phenotype_data as pd, phenotypes as p, experiments as e
          WHERE e.experiment_uid = tht_base.experiment_uid
          AND lr.line_record_uid = tht_base.line_record_uid
          AND tht_base.tht_base_uid = pd.tht_base_uid
          AND pd.value $searchVal
          AND pd.phenotype_uid = p.phenotype_uid
          AND p.phenotype_uid = '$phenotype'
          $in_these_lines
          $in_these_trials";
 
if (! preg_match('/^\s*select/i', $query)) {
     die("Only works with query commands start with select\n");
}
$backup = "";
$result=mysqli_query($mysqli, $query) or die("Invalid query");
$count=0;
ob_start();
while ($row=mysqli_fetch_assoc($result)) {
    $rkeys=array_keys($row);
    if ($count==0) {
        $count++;
        for ($i=0; $i<count($rkeys); $i++) {
            print mysqli_escape_string($mysqli, $rkeys[$i]);
            if ($i!=count($rkeys)-1) {
                print ", ";
            }
        }
        print "\n";
    }
    for ($i=0; $i<count($rkeys); $i++) {
        print mysqli_escape_string($mysqli, $row[$rkeys[$i]]);
        if ($i!=count($rkeys)-1) {
            print ", ";
        }
    }
    print "\n";
}
    $backup.=ob_get_contents();
    ob_end_clean();
    $date = date("m-d-Y-H:i:s");
    $name = "THT-query-$date.txt";
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=$name");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $backup;
