<?php

require "../includes/bootstrap.inc";
$mysqli = connecti();

/* process export table*/
if (isset($_POST['line_id'])) {
    $line_id = $_POST['line_id'];
    $query = "SELECT experiments.trial_code, marker_name, alleles
        FROM experiments, allele_cache
        WHERE allele_cache.line_record_uid = '$line_id'
        AND experiments.experiment_uid = allele_cache.experiment_uid";
} elseif (isset($_POST['marker_uid'])) {
    $marker_uid = $_POST['marker_uid'];
    $query = "SELECT experiments.trial_code, line_record_name, alleles
          FROM experiments, allele_cache
          WHERE allele_cache.marker_uid = $marker_uid 
          AND experiments.experiment_uid = allele_cache.experiment_uid";
} else {
    die("Invalid query\n");
}

$backup = "";
$result=mysqli_query($mysqli, $query) or die("Invalid query $query");
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
