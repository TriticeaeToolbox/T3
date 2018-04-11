<?php
/**
 * Generates tsv and flapjack formated files
 * all entries must be from same experiment
 *
 **/
require '../../includes/bootstrap.inc';
$mysqli = connecti();

$num_args = $_SERVER["argc"];

$markerProfile = $_SERVER['argv'][1];
$unqStr = $_SERVER['argv'][2];
$format = $_SERVER['argv'][3];

$tmpFile = "/tmp/tht/download_" . $unqStr . ".tsv";
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
    die("Error can not open file $tmpFile");
}

$previd = "";
if (preg_match("/,/", $markerProfile)) {
    $profile_list = explode(",", $markerProfile);
    $countExp = count($profile_list);
    $num_rows = 0;
    foreach ($profile_list as $item) {
        $num_rows++;
        if (preg_match("/(\d+)_(\d+)/", $item, $match)) {
            $lineuid = $match[1];
            $expid = $match[2];
        } else {
            dieNice("Error", "invalid format of marker profile id $item");
        }
        // for the first record output header line //
        if ($previd == "") {
            $previd = $expid;
            $found = 0;
            if ($format == "tsv") {
                $sql1 = "select line_name_index from allele_bymarker_expidx where experiment_uid = $expid";
                $res = mysqli_query($mysqli, $sql1) or die("Error mysqli_error($mysqli)\n");
                if ($row = mysqli_fetch_row($res)) {
                    $line_index = json_decode($row[0], true);
                    $line_index = implode("\t", $line_index);
                    fwrite($fh, "markerprofilesDbIds\t$line_index\n");
                }
            } elseif ($format == "flapjack") {
                $sql1 = "select marker_name_index from allele_byline_expidx where experiment_uid = $expid";
                $res = mysqli_query($mysqli, $sql1) or die("Error mysqli_error($mysqli)\n");
                if ($row = mysqli_fetch_row($res)) {
                    $marker_index = json_decode($row[0], true);
                    $marker_index = implode("\t", $marker_index);
                    if ($num_rows == 1) {
                        //fwrite($fh, "# fjFile = GENOTYPE\n");
                        fwrite($fh, "\t$marker_index\n");
                    }
                } else {
                    die("Error: $expid not found");
                }
            } else {
                fwrite($fh, "Error: bad format $format\n");
                die("Error: bad format\n");
            }
        } elseif ($previd != $expid) {
            dieNice("Error", "all marker profiles must have same experiment");
        }
        $sql = "select line_record_name, alleles from allele_byline_exp_ACTG where experiment_uid = $expid
            and line_record_uid = $lineuid";
        if ($res = mysqli_query($mysqli, $sql)) {
            while ($row = mysqli_fetch_row($res)) {
                $found++;
                $name = $row[0];
                $alleles = $row[1];
                $alleles_ary = explode(",", $alleles);
                $alleles_fmt = "";
                foreach ($alleles_ary as $i => $v) {
                    if ($v[0] == $v[1]) {
                        $v = $v[0];
                    } else {
                        $v = $v[0] . "/" . $v[1];
                    }
                    if ($alleles_fmt == "") {
                         $alleles_fmt = $v;
                    } else {
                         $alleles_fmt .= "\t$v";
                    }
                }
                if ($format = "tsv") {
                    fwrite($fh, "$item\t$alleles_fmt\n");
                } else {
                    fwrite($fh, "$name\t$alleles_fmt\n");
                }
            }
        } else {
            fwrite($fh, "mysqli_error($mysqli)");
        }
        if ($found == 0) {
            fwrite($fh, "Error", "marker profile not found $item");
        }
        $resultProfile[] = $item;
    }
}
//fwrite($fh, json_encode($results, JSON_UNESCAPED_SLASHES));
fclose($fh);
$fh = fopen($statusFile, "w") or die("Error can not open file $tmpFile");
fclose($fh);
