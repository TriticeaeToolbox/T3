<?php
require '../../includes/bootstrap.inc';
$mysqli = connecti();

$self = $_SERVER['PHP_SELF'];
$script = $_SERVER["SCRIPT_NAME"]."/";
$rest = str_replace($script, "", $self);
$rest = explode("/", $rest);

if (isset($_GET['uid'])) {
    $uid = $_REQUEST['uid'];
}
if (isset($_GET['pageSize'])) {
    $pageSize = $_GET['pageSize'];
} else {
    $pageSize = 1000;
}
if (isset($_GET['page'])) {
    $currentPage = $_GET['page'];
} else {
    $currentPage = 0;
}
$fh = fopen("/tmp/tht/request_log.txt", "w");
//* the brapi filter app does not support Brapi v1 which uses POST JSON format
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tmp = file_get_contents('php://input');
    $request = json_decode($tmp, true);
    fwrite($fh, $tmp);
    foreach ($request as $key => $val) {
        if ($key == "germplasmDbIds") {
            $germplasmDbId = implode(",", $val);
        } elseif ($key == "observationVariableDbIds") {
            //$phenotypeDbId = implode(",", $val);
            $phenotypeDbId = $val;
        } elseif ($key == "studyDbIds") {
            //$studyDbId = $implode(",", $val);
            $studyDbId = $val[0];
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['germplasmDbId'])) {
        $germplasmDbId = $_REQUEST['germplasmDbId'];
    }
    if (isset($_GET['observationVariableDbId'])) {
        $phenotypeDbId = $_REQUEST['observationVariableDbId'];
    }
    if (isset($_GET['studyDbId'])) {
        $studyDbId = $_REQUEST['studyDbId'];
    }
}

function dieNice($msg)
{
    $linearray["metadata"]["pagination"] = null;
    $linearray["metadata"]["status"] = array("code" => 1, "message" => "SQL Error: $msg");
    $linearray["metadata"]["datafiles"] = array();
    $linearray["result"] = null;
    $return = json_encode($linearray);
    die("$return");
}

header("Content-Type: application/json");
if (isset($studyDbId)) {
    $sql_opt = "and experiment_uid = $studyDbId";
} else {
    $sql_opt = "";
}
    $linearray['metadata']['pagination'] = $pageList;
    $linearray['metadata']['status'] = array();
    $linearray['metadata']['datafiles'] = array();

    $sql = "select experiment_uid, trial_code from experiments";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $exp_list[$row[0]] = $row[1];
    }
    $sql = "select line_record_uid, line_record_name from line_records";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $line_list[$row[0]] = $row[1];
    }
    $sql = "select phenotype_uid, phenotypes_name, TO_number from phenotypes";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $pheno_list[$row[0]] = $row[1];
        if (preg_match("/[a-zA-Z0-9]/", $row[2])) {
            $ontol_list[$row[0]] = $row[2];
        } else {
            $ontol_list[$row[0]] = $row[1];
        }
    }
    $sql = "select experiment_uid, location from phenotype_experiment_info";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $locat_list[$row[0]] = $row[1];
    }
 
    $sql = "select phenotype_uid, value, line_record_uid, experiment_uid from phenotype_data, tht_base
        where phenotype_data.tht_base_uid = tht_base.tht_base_uid
        $sql_opt order by line_record_uid";
    //echo "$sql\n";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    $num_rows = mysqli_num_rows($res);
    $tot_pag = ceil($num_rows / $pageSize);
    $pageList = array( "pageSize" => $pageSize, "currentPage" => 0, "totalCount" => $num_rows, "totalPages" => $tot_pag );
    $linearray['metadata']['pagination'] = $pageList;

    //now get just those selected
    if ($currentPage == 0) {
        $sql .= " limit $pageSize";
    } else {
        $offset = $currentPage * $pageSize;
        if ($offset < 0) {
            $offset = 0;
        }
        $sql .= " limit $offset, $pageSize";
    }
    $count = 1;
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $phenotypeDbId = $row[0];
        $line_uid = $row[2];
        if ($count == 1) {
            $data['observationUnitDbId'] = $exp_list[$row[3]];
            $data['germplasmDbId'] = $row[2];
            $data['germplasmName'] = $line_list[$row[2]];
            $data['studyDbId'] = $row[3];
            $data['studyName'] = $exp_list[$row[3]];
            $data['studyLocationDbId'] = $locat_list[$row[3]];
            $prev_uid = $line_uid;
            $count++;
        } elseif ($line_uid != $prev_uid) {
            $temp[] = $data;
            $prev_uid = $line_uid;
            $data['observations'] = "";
        } else {
            $data['observationUnitDbId'] = $exp_list[$row[3]];
            $data['germplasmDbId'] = $row[2];
            $data['germplasmName'] = $line_list[$row[2]];
            $data['studyDbId'] = $row[3];
            $data['studyName'] = $exp_list[$row[3]];
            $data['studyLocationDbId'] = $locat_list[$row[3]];
        }
        $obsDbId = $ontol_list[$phenotypeDbId];
        $obsName = $pheno_list[$phenotypeDbId];
        $obs = array("observationDbId" => "$phenotypeDbId", "observationVariableDbId" => "$obsDbId", "observationVariableName" => "$obsName", "value" => "$row[1]");
        $data['observations'][] = $obs;
    }
    $linearray['result']['data'] = $temp;
    $return = json_encode($linearray);
    echo "$return";
