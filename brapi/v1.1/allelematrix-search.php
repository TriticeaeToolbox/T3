<?php
require '../../includes/bootstrap.inc';
$mysqli = connecti();

$self = $_SERVER['PHP_SELF'];
$script = $_SERVER["SCRIPT_NAME"]."/";
$rest = str_replace($script, "", $self);
$rest = explode("/", $rest);

$results['metadata']['status'] = array();
$results['metadata']['datafiles'] = array();

if (isset($_REQUEST['pageSize'])) {
    $pageSize = $_REQUEST['pageSize'];
} else {
    $pageSize = 1000;
}
if (isset($_REQUEST['page'])) {
    $currentPage = $_REQUEST['page'];
} else {
    $currentPage = 0;
}
if (isset($_REQUEST['format'])) {
    $format = $_REQUEST['format'];
} else {
    $format = "json";
}
$logFile = "/tmp/tht/request-log-allelematrix-search.txt";

$profile_list = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fh = fopen($logFile, "a");
    fwrite($fh, "POST\n");
    $request = file_get_contents('php://input');
    fwrite($fh, $request);
    $request = json_decode($request, true);
    foreach ($request as $key => $val) {
        fwrite($fh, "request key=$key\nval=$val\n");
        if ($key == "markerprofileDbId") {
            if (is_array($val)) {
                $profile_str = implode(",", $val);
                $profile_list[] = $val;
            } else {
                $profile_str = $val;
                $profile_list[] = $val;
            }
        } elseif ($key == "format") {
            $format = $val;
        } elseif ($key == "page") {
            $currentPage = $val;
        } elseif ($key == "pageSize") {
            $pageSize = $val;
        }
    }
    foreach ($_POST as $key => $val) {
        fwrite($fh, "POST request key=$key\nval=$val\n");
        if ($key == "markerprofileDbId") {
            $profile_str = $val;
            $profile_list = explode(",", $val);
        }
        fwrite($fh, "key=$key\nval=$val\n");
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $fh = fopen($logFile, "a");
    fwrite($fh, "GET\n");
    foreach ($_GET as $key => $val) {
        fwrite($fh, "key=$key val=$val\n");
        if ($key == "markerprofileDbId") {
            $profile_str = $val;
            $profile_list = explode(",", $val);
        }
    }
    fwrite($fh, "$rest[0] $rest[1] $rest[2]\n");
} elseif ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header("Content-Type: application/json");
    die();
} else {
    dieNice("Error", "invalid request method");
}

function dieNice($code, $msg)
{
    global $results;
    $pageList = array( "pageSize" => 1000, "currentPage" => 0, "totalCount" => 1, "totalPages" => 1 );
    $results['metadata']['pagination'] = $pageList;
    $results['metadata']['status'][] = array("code" => $code, "message" => "$msg");
    $results['result']['data'] = array();
    $return = json_encode($results);
    header("Content-Type: application/json");
    die("$return");
}

if ($rest[0] == "status") {
    $unqStr = $rest[1];
    $results['metadata']['pagination'] = null;
    $results['result'] = null;
    $base_url = "https://" . $_SERVER['HTTP_HOST'];
    $tmpFile = $base_url . "/tmp/tht/download_" . $unqStr . ".tsv";
    $statusFile = "/tmp/tht/status_" . $unqStr . ".txt";
    $results['metadata']['datafiles'] = array($tmpFile);
    if (file_exists($statusFile)) {
        if (filesize($statusFile) > 0) {
            $results['metadata']['status'][] = array("code" => "asyncstatus", "message" => "FAILED");
        } else {
            $results['metadata']['status'][] = array("code" => "asyncstatus", "message" => "FINISHED");
        }
    } else {
        $results['metadata']['status'][] = array("code" => "asyncstatus", "message" => "INPROCESS");
    }
    $return = json_encode($results);
    fwrite($fh, $return);
    header("Content-Type: application/json");
    die("$return");
} elseif ($profile_list != array()) {
    $uniqueStr = chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80));
    $errorFile = "/tmp/tht/error_" . $uniqueStr . ".txt";

    //first query all data
    foreach ($profile_list as $item) {
        if (preg_match("/(\d+)_(\d+)/", $item, $match)) {
            $exp_ary[] = $match[2];
        } else {
            $results['metadata']['status'][] = array("code" => "parm", "message" => "Error: invalid format of marker profile id $item");
            continue;
        }
    }

    $countExp = count($profile_list);
    if ($format != "json") {
        $cmd = "php allelematrix-search-offline.php $profile_str $uniqueStr $format > /dev/null 2> $errorFile";
        fwrite($fh, "$cmd\n");
        exec($cmd);
        dieNice("asynchid", "$uniqueStr");
    }
    $num_rows = 0;
    foreach ($profile_list as $item) {
        //echo "profile = $item\n";
        if (preg_match("/(\d+)_(\d+)/", $item, $match)) {
            $lineuid = $match[1];
            $expid = $match[2];
        } else {
            dieNice("Error", "invalid format of marker profile id $item");
        }

        //get marker_uid
        $sql = "select marker_index from allele_byline_expidx where experiment_uid = $expid";
        $res = mysqli_query($mysqli, $sql);
        if ($row = mysqli_fetch_row($res)) {
            $marker_index = $row[0];
            $marker_index = json_decode($marker_index, true);
            //$marker_index = explode(",", $marker_index);
        } else {
            dieNice("Error", "invalid experiment $expid");
        }

        //first query all data
        $sql = "select alleles from allele_byline_exp_ACTG where experiment_uid = $expid and line_record_uid = $lineuid";
        $res = mysqli_query($mysqli, $sql);
        $num_rows = mysqli_num_rows($res);
        if ($currentPage == 0) {
            $sql .= " limit $pageSize";
        } else {
            $offset = $currentPage * $pageSize;
            if ($offset < 0) {
                $offset = 0;
            }
            $sql .= " limit $offset, $pageSize";
        }

        //now get just those selected
        $found = 0;
        if ($res = mysqli_query($mysqli, $sql)) {
            while ($row = mysqli_fetch_row($res)) {
                $found = 1;
                $alleles = $row[0];
                $alleles_ary = explode(",", $alleles);
                foreach ($alleles_ary as $i => $v) {
                    if ($v[0] == $v[1]) {
                        $v = $v[0];
                    } else {
                        $v = $v[0] . "/" . $v[1];
                    }
                    $num_rows++;
                    $marker_uid = $marker_index[$i];
                    $dataList[] = array( "$marker_index[$i]", "$item", "$v");
                }
            }
        } else {
            dieNice("SQL", mysqli_error($mysqli));
        }
        if ($found == 0) {
            dieNice("Error", "marker profile not found $item $sql");
        }
        $resultProfile[] = $item;
    }
} elseif (isset($_REQUEST['matrixDbId'])) {
    $studyDbId = $_REQUEST['matrixDbId'];
    $uniqueStr = chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80));
    $errorFile = "/tmp/tht/error_" . $uniqueStr . ".txt";
    $cmd = "php allelematrix-offline.php \"$studyDbId\" \"$uniqueStr\" > /dev/null 2> $errorFile";
    exec($cmd);
    dieNice("asynchid", "$uniqueStr");
} else {
    //first query all data
    dieNice("Error", "need markerprofileDbId");
    $num_rows = 0;
    $profile_list = array();
    if ($currentPage == 0) {
        $offset = 0;
        $limit = $pageSize;
    } else {
        $offset = $currentPage * $pageSize;
        $limit = $offset + $pageSize;
    }
    $sql = "select experiment_uid, line_record_uid, count from allele_byline_exp";
    $res = mysqli_query($mysqli, $sql);
    while ($row = mysqli_fetch_row($res)) {
        $expid = $row[0];
        $lineuid = $row[1];
        $count = $row[2];
        $item = $lineuid . "_" . $expid;
        if (($num_rows == 0) && ($offset == 0)) {
            $profile_list[] = $item;
        } elseif ($num_rows > $offset) {
            if (empty($profile_list)) {
                $profile_list[] = $item;
            } elseif ($num_rows < $limit) {
                $profile_list[] = $item;
            }
        }
        $num_rows += $count;
    }
    
    foreach ($profile_list as $item) {
        if (preg_match("/(\d+)_(\d+)/", $item, $match)) {
            $lineuid = $match[1];
            $expid = $match[2];
        } else {
            $results['metadata']['status'][] = array("code" => "parm", "message" => "Error: invalid format of marker profile id $item");
            continue;
        }

        //now get just those selected
        $sql = "select marker_index from allele_byline_expidx
              where experiment_uid = $expid";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($res)) {
            $tmp = $row[0];
            $marker_index = json_explode($tmp, true);
            //$marker_index = explode(",", $tmp);
        }
        $sql = "select alleles from allele_byline_exp_ACTG
              where line_record_uid = $lineuid
              and experiment_uid = $expid";
        $res = mysqli_query($mysqli, $sql);
        if ($row = mysqli_fetch_row($res)) {
            $tmp = $row[0];
            $alleles = explode(",", $tmp);
            foreach ($alleles as $key => $val) {
                $dataList[$marker_index[$key]][] = $val;
            }
        }
        $resultProfile[] = $item;
    }
}

header("Content-Type: application/json");
$linearray['metadata']['pagination'] = "";
$linearray['metadata']['status'] = array();
$linearray['metadata']['datafiles'] = array();

$tot_pag = ceil($num_rows / $pageSize);
$pageList = array( "pageSize" => $pageSize, "currentPage" => $currentPage, "totalCount" => $num_rows, "totalPages" => $tot_pag );
$linearray['metadata']['pagination'] = $pageList;
$linearray['result']['data'] = $dataList;
$return = json_encode($linearray);
echo $return;
