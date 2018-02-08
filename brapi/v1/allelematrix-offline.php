<?php
include('../../includes/bootstrap.inc');
$mysqli = connecti();

$num_args = $_SERVER["argc"];

$studyDbId = $_SERVER['argv'][1];
$unqStr = $_SERVER['argv'][2];

$tmpFile = "/tmp/tht/download_" . $unqStr . ".txt";
$statusFile = "/tmp/tht/status_" . $unqStr . ".txt";
$fh = fopen($tmpFile, "w") or die("Error can not open file $tmpFile");

function dieNice($code, $msg)
{
    global $fh;
    global $statusFile;
    $results['metadata']['pagination'] = null;
    $results['metadata']['status'][] = array("code" => $code, "message" => "$msg");
    $results['result'] = null;
    fwrite($fh, json_encode($results));
    fclose($fh);
    $fh = fopen($statusFile, "w") or die("Error can not open file $tmpFile");
    fwrite($fh, "$code: $msg");
    fclose($fh);
    die();
}

    $results = "";
        //get marker_uid
        $sql = "select marker_index from allele_byline_expidx where experiment_uid = $studyDbId";
        $res = mysqli_query($mysqli, $sql) or die("Error mysqli_error($mysqli)\n");
        if ($row = mysqli_fetch_row($res)) {
            $marker_index = $row[0];
            $marker_index = explode(",", $marker_index);
            $results = "";
            foreach ($marker_index as $marker_uid) {
                $sql = "select marker_name from markers where marker_uid = $marker_uid";
                $res2 = mysqli_query($mysqli, $sql) or die("Error mysqli_error($mysqli)\n");
                if ($row2 = mysqli_fetch_row($res2)) {
                    $results .= "\t" . $row2[0];
                } else {
                    $rusults .= "\t" . "unknown";
                }
            }
        } else {
            dieNice("Error", "invalid experiment $expid");
        }

        //now get just those selected
        $sql = "select line_record_name, alleles from allele_byline_exp where experiment_uid = $studyDbId";
        $found = 0;
        if ($res = mysqli_query($mysqli, $sql)) {
            while ($row = mysqli_fetch_row($res)) {
                $found = 1;
                $line_record_name = $row[0];
                $alleles = $row[1];
                $results .= "$line_record_name\t$alleles\n";
            }
        } else {
            dieNice("SQL", "mysqli_error($mysqli)");
        }
        if ($found == 0) {
            dieNice("Error", "marker profile not found $item");
        }

$tot_pag = null;
fwrite($fh, $results);
fclose($fh);
$fh = fopen($statusFile, "w") or die("Error can not open file $tmpFile");
fclose($fh);
