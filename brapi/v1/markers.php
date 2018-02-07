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
}
if (isset($_GET['uid'])) {
    $uid = $_REQUEST['uid'];
}
if (isset($_GET['type'])) {
    $type = $_GET['uid'];
} else {
    $type = "";
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

function dieNice($msg)
{
    $linearray["metadata"]["pagination"] = null;
    $linearray["metadata"]["status"] = array("code" => 1, "message" => "SQL Error: $msg");
    $linearray["result"] = null;
    $return = json_encode($linearray);
    die("$return");
}

//header("Content-Type: application/json");
if ($action == "list") {
    $linearray['metadata']['pagination'] = $pageList;
    $linearray['metadata']['status'] = array();
    $linearray['metadata']['datafiles'] = array();

    //first query all data
    $sql = "select marker_uid, marker_name, marker_type_name from markers, marker_types
        where markers.marker_type_uid = marker_types.marker_type_uid";
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
        $data["markerDbId"] = $row[0];
        $data["defaultDisplayName"] = $row[1];
        $data["type"] = $row[2];
        $temp[] = $data;
    }
    $linearray['result']['data'] = $temp;
    $return = json_encode($linearray);
    header("Content-Type: application/json");
    echo "$return";
} elseif ($type != "") {
    $linearray['metadata']['pagination'] = $pageList;
    $linearray['metadata']['status'] = array();
    $linearray['metadata']['datafiles'] = array();

    //first query all data
    $sql = "select marker_uid, marker_name, marker_type_name from markers, marker_types
        where markers.marker_type_uid = marker_types.marker_type_uid
        and marker_type_name = \"$type\"";
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
        $data["markerDbId"] = $row[0];
        $data["defaultDisplayName"] = $row[1];
        $data["type"] = $row[2];
        $temp[] = $data;
    }
    $linearray['result']['data'] = $temp;
    $return = json_encode($linearray);
    header("Content-Type: application/json");
    echo "$return";
} elseif ($uid != "") {
    $sql = "select marker_uid, marker_name, marker_type_name from markers, marker_types
        where markers.marker_type_uid = marker_types.marker_type_uid
        and marker_uid = $uid";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    if ($row = mysqli_fetch_row($res)) {
        $data["markerDbId"] = $row[0];
        $data["defaultDisplayName"] = $row[1];
        $data["type"] = $row[2];
        $temp[] = $data;
    } else {
        $results = null;
        $return = json_encode($results);
        header("Content-Type: application/json");
        echo "$return";
        die();
    }
    $temp[] = $data;
    $return = json_encode($results);
    header("Content-Type: application/json");
    echo "$return";
} else {
    echo "Error: missing experiment id<br>\n";
}
