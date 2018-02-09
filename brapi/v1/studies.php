<?php
require '../../includes/bootstrap.inc';
$mysqli = connecti();

$self = $_SERVER['PHP_SELF'];
$script = $_SERVER["SCRIPT_NAME"]."/";
$rest = str_replace($script, "", $self);
$rest = explode("/", $rest);
if (is_numeric($rest[0])) {
    $uid = $rest[0];
} else {
    $action = "list";
}
if (isset($rest[1]) && ($rest[1] == "table")) {
    $outFormat = "table";
} else {
    $outFormat = "json";
}
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    //echo "cmd = $action<br>\n";
}
if (isset($_GET['uid'])) {
    $uid = $_REQUEST['uid'];
    //echo "uid = $uid<br>\n";
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
if (isset($_GET['studyType'])) {
    $studyType = $_GET['studyType'];
}
if (isset($_GET['trialDbId'])) {
    $trialDbId = $_GET['trialDbId'];
}
if (isset($_GET['seasonDbId'])) {
    $seasonDbId = $_GET['seasonDbId'];
}
if (isset($_GET['locationDbId'])) {
    $locationDbId = $_GET['locationDbId'];
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

//header("Content-Type: application/json");
if ($action == "list") {
    if (isset($_GET['program'])) {
        $cap_uid = $_GET['program'];
        $sql_opt = "and CAPdata_programs_uid = $cap_uid";
    } else {
        $sql_opt = "";
    }
    $linearray['metadata']['pagination'] = $pageList;
    $linearray['metadata']['status'] = array();
    $linearray['metadata']['datafiles'] = array();

    //first query all data
    $options = "";
    if (isset($studyType)) {
        $options = " and experiment_type_name = \"$studyType\"";
    }
    if (isset($trialDbId)) {
        $options .= " and experiment_set_uid = $trialDbId";
    }
    if (isset($seasonDbId)) {
        $options .= " and experiment_year = \"$seasonDbId\"";
    }
    if (isset($locationDbId)) {
        $options .= " and location = \"$locationDbId\"";
    }
    $sql = "select experiments.experiment_uid, experiment_set_uid, experiment_type_name, trial_code, CAPdata_programs_uid, experiment_year
        from experiments, experiment_types
        where experiments.experiment_type_uid = experiment_types.experiment_type_uid
        $options";
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
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $uid = $row[0];
        $set_uid = $row[1];
        $data["studyDbId"] = $row[0];
        if (preg_match("/[0-9]/", $set_uid)) {
            $data["trialDbId"] = $row[1];
        } else {
            $data["trialDbId"] = "";
        }
        $data["studyType"] = $row[2];
        $data["name"] = $row[3];
        $data["trialName"] = "";
        $CAP_uid = $row[4];
        $data["programDbId"] = $row[4];
            $sql = "select data_program_name from CAPdata_programs where CAPdata_programs_uid = $row[4]";
            $res2 = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli) . "<br>$sql");
            if ($row2 = mysqli_fetch_row($res2)) {
                $data["programName"] = $row2[0];
            }
        $data["seasons"] = array($row[5]);
        if (preg_match("/[0-9]/", $set_uid)) {
            $sql = "select experiment_set_name from experiment_set where experiment_set_uid = $set_uid";
            $res2 = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli) . "<br>$sql");
            if ($row2 = mysqli_fetch_row($res2)) {
                $data["trialName"] = $row2[0];
            }
        }
        if ($row[2] == "phenotype") {
            $sql = "select location, planting_date, harvest_date from phenotype_experiment_info where experiment_uid = $uid";
            $res2 = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
            if ($row2 = mysqli_fetch_row($res2)) {
                $data["location"]["locationDbId"] = $row2[0];
                if (isset($row2[0])) {
                    $data["location"]["name"] = $row2[0];
                } else {
                    $data["location"]["name"] = "";
                }
                $data["startDate"] = $row2[1];
                $data["endDate"] = $row2[2];
            } else {
                $data["location"]["locationDbId"] = "";
                $data["location"]["name"] = "";
            }
        } else {
            $data["location"]["locationDbId"] = "";
            $data["location"]["name"] = "";
        }
        $sql = "select data_program_name from CAPdata_programs where CAPdata_programs_uid = $CAP_uid";
        $res2 = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
        if ($row2 = mysqli_fetch_row($res2)) {
            $program = $row2[0];
        }
        $temp[] = $data;
    }
    $linearray['result']['data'] = $temp;
    $return = json_encode($linearray);
    header("Content-Type: application/json");
    echo "$return";
} elseif ($uid != "") {
    $linearray['metadata']['pagination'] = null;
    $linearray['metadata']['status'] = array();
    $linearray['metadata']['datafiles'] = array();

    $pageList = array( "pageSize" => $pageSize, "currentPage" => 0, "totalCount" => 1, "totalPages" => 1 );
    $linearray['metadata']['pagination'] = $pageList;

    $sql = "select line_record_uid, line_record_name from line_records";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
                $line_uid= $row[0];
                $line_record_name = $row[1];
                $name_list[$line_uid] = $line_record_name;
    }
    $sql = "select experiment_type_name, experiment_set_uid, trial_code
        from experiments, experiment_types
        where experiments.experiment_type_uid = experiment_types.experiment_type_uid
        and experiment_uid = $uid";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    if ($row = mysqli_fetch_row($res)) {
        $type = $row[0];
        $set = $row[1];
    } else {
        $set = "";
    }
    $results["studyDbId"] = $uid;
    $results["studyType"] = $type;
    $results["trialDbId"] = $set;
    $results["name"] = $row[2];
    $sql = "select trial_code, planting_date, harvest_date, collaborator, begin_weather_date, location, experiment_design
       from experiments, phenotype_experiment_info
       where phenotype_experiment_info.experiment_uid = experiments.experiment_uid
       and experiments.experiment_uid = $uid";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    if ($row = mysqli_fetch_row($res)) {
        $results["startDate"] = $row[1];
        $results["endDate"] = $row[2];
        $results["contacts"][] = $row[3];
        $results["startDate"] = $row[4];
        $results["location"]["locationDbId"] = "";
        $results["location"]["name"] = $row[5];
    }
    if ($type == "genotype") {
        $sql = "select platform_name
         from experiments, genotype_experiment_info, platform
         where experiments.experiment_uid = genotype_experiment_info.experiment_uid
         and experiments.experiment_uid = $uid";
        $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($res)) {
            $results["additionalInfo"] =  $row[0];
        }
        $results["location"]["locationDbId"] = "";
        $results["location"]["name"] = "";
    }
    if (isset($results["trialDbId"])) {
        $sql = "select experiment_set_name from experiment_set where experiment_set_uid = $uid";
        $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli) . "<br>$sql");
        if ($row = mysqli_fetch_row($res)) {
            $results["trialName"] = $row[0];
        }
    }

    $sql = "select plot_uid, plot, block, row_id, column_id, replication, check_id, line_uid from fieldbook
        where experiment_uid = $uid order by row_id, plot";
    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
    while ($row = mysqli_fetch_row($res)) {
        $temp["plotId"] = $row[0];
        $temp["plot"] = $row[1];
        $temp["blockId"] = $row[2];
        $temp["rowId"] = $row[3];
        $temp["columnId"] = $row[4];
        $temp["replication"] = $row[5];
        $temp["checkId"] = $row[6];
        $temp["germplasmId"] = $row[7];
        $temp["germplasmName"] = $name_list[$row[7]];
        if (!preg_match("/\d+/", $row_id)) {
              $error = 1;
        }
        if (!preg_match("/\d+/", $column_id)) {
              $error = 1;
        }
        $results["design"][] = $temp;
    }
    $linearray['result'] = $results;
    if ($outFormat == "json") {
        $return = json_encode($linearray);
        header("Content-Type: application/json");
    } else {
        $return = "";
        foreach ($results["design"] as $entry) {
            $return .= implode(",", $entry) . "\n";
        }
        header("Content-Type: text/csv");
    }
    echo "$return";
} else {
    echo "Error: missing experiment id<br>\n";
}
