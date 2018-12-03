<?php

/**
 * supports GET and POST
 **/

require '../../includes/bootstrap.inc';
//ini_set('memory_limit', '4G');
$mysqli = connecti();

$self = $_SERVER['PHP_SELF'];
$script = $_SERVER["SCRIPT_NAME"]."/";
$rest = str_replace($script, "", $self);
$rest = explode("/", $rest);

if (isset($rest[1]) && ($rest[1] == "table")) {
    $outFormat = "table";
} else {
    $outFormat = "json";
}
$type = "";
$name = "";
$markerDbIds = "";
$pageSize = 1000;
$currentPage = 0;
$logFile = "/tmp/tht/request-log-marker-search.txt";
$fh = fopen($logFile, "a");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request = file_get_contents('php://input');
    fwrite($fh, "$request\n");
    $request = json_decode($request, true);
    foreach ($request as $key => $val) {
        fwrite($fh, "request key=$key  val=$val\n");
        if ($key == "type") {
            $type = $val;
        } elseif ($key == "name") {
            $name = $val;
        } elseif ($key == "markerDbIds") {
            $markerDbIds = implode(",", $val);
        } elseif ($key == "page") {
            $currentPage = $val;
        } elseif ($key == "pageSize") {
            $pageSize = $val;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    }
    if (isset($_GET['name'])) {
        $name = $_GET['name'];
    }
    if (isset($_GET['markerDbIds'])) {
        $markerDbIds = $_GET['markerDbIds'];
    }
    if (isset($_GET['pageSize'])) {
        $pageSize = $_GET['pageSize'];
    }
    if (isset($_GET['page'])) {
        $currentPage = $_GET['page'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header("Content-Type: application/json");
    die();
} else {
    dieNice("Error", "invalid request method");
}

function dieNice($msg)
{
    $linearray["metadata"]["pagination"] = null;
    $linearray["metadata"]["status"] = array("code" => 1, "message" => "SQL Error: $msg");
    $linearray["result"] = null;
    $return = json_encode($linearray);
    die("$return");
}

header("Content-Type: application/json");

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

$temp2 = array();
// get unique types for BrAPI genotyping Client
if ($pageSize == 10000) {
    $res = mysqli_query($mysqli, $sql, MYSQLI_USE_RESULT) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $type = $row[2];
        $markerDbId = $row[0];
        $data["markerDbId"] = $row[0];
        $data["defaultDisplayName"] = $row[1];
        $data["type"] = $row[2];
        if (isset($unique[$type])) {
        } else {
            $uniqueMarker[$markerDbId] = $data;
            $unique[$type] = 1;
            $temp2[] = $data;
        }
    }
    mysqli_free_result($res);
}

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
    $type = $row[2];
    $markerDbId = $row[0];
    $data["markerDbId"] = $row[0];
    $data["defaultDisplayName"] = $row[1];
    $data["type"] = $row[2];
    if (isset($uniqueMarker[$markerDbId])) {
    } else {
        $temp3[] = $data;
    }
}
$count1 = count($temp2);
$count2 = count($temp3);
fwrite($fh, "count1= $count1, count2= $count2\n");
$temp4 = array_merge($temp2, $temp3);

$linearray['result']['data'] = $temp4;
$return = json_encode($linearray);
echo "$return";
