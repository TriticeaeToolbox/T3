<?php

/**
 * only supports GET
 **/

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
if (isset($_GET['type'])) {
    $type = $_GET['type'];
} else {
    $type = "";
}
if (isset($_GET['name'])) {
    $name = $_GET['name'];
} else {
    $name = "";
}
if (isset($_GET['markerDbIds'])) {
    $markerDbIds = $_GET['markerDbIds'];
} else {
    $markerDbIds = "";
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
    if ($type != "") {
        $options = " and marker_type_name = \"$type\"";
    } else {
        $options = "";
    }
    if ($name != "") {
        $options .= " and marker_name like \"$name\"";
    }
    if ($markerDbIds != "") {
        $options .= " and marker_uid in ($markerDbIds)";
    }
    $sql .= $options;

    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    $num_rows = mysqli_num_rows($res);
    $tot_pag = ceil($num_rows / $pageSize);
    $pageList = array( "pageSize" => $pageSize, "currentPage" => $currentPage, "totalCount" => $num_rows, "totalPages" => $tot_pag );
    $linearray['metadata']['pagination'] = $pageList;

    //now get just those selected
    if ($currentPage == 0) {
        $options = " limit $pageSize";
    } else {
        $offset = $currentPage * $pageSize;
        if ($offset < 0) {
            $offset = 0;
        }
        $options = " limit $offset, $pageSize";
    }
    $sql .= $options;
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli) . "<br>$sql<br>");
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
    $linearray['metadata']['status'] = array();
    $linearray['metadata']['datafiles'] = array();
    $num_rows = 1;
    $tot_pag = 1;
    $pageList = array( "pageSize" => $pageSize, "currentPage" => 0, "totalCount" => $num_rows, "totalPages" => $tot_pag );
    $linearray['metadata']['pagination'] = $pageList;
    $sql = "select marker_uid, marker_name, marker_type_name from markers, marker_types
        where markers.marker_type_uid = marker_types.marker_type_uid
        and marker_uid = $uid";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    if ($row = mysqli_fetch_row($res)) {
        $data["markerDbId"] = $row[0];
        $data["defaultDisplayName"] = $row[1];
        $data["type"] = $row[2];
    } else {
        $results = null;
        $return = json_encode($results);
        header("Content-Type: application/json");
        echo "$return";
        die();
    }
    $linearray['result'] = $data;
    $return = json_encode($linearray);
    header("Content-Type: application/json");
    echo "$return";
} else {
    echo "Error: missing experiment id<br>\n";
}
