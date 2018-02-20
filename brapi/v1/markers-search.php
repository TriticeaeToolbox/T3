<?php

/**
 * supports GET and POST
 **/

require '../../includes/bootstrap.inc';
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request = json_decode(file_get_contents('php://input'), true);
    foreach ($request as $key => $val) {
        if ($key == "markerDbIds") {
            $markerDbIds = implode(",", $val);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
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
} else {
    dieNice("Error", "invalid request method");
}
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
    $pageList = array( "pageSize" => $pageSize, "currentPage" => 0, "totalCount" => $num_rows, "totalPages" => $tot_pag );
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
    echo "$return";
