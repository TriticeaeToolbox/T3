<?php
/**
 * Brapi/v1/calls.php, DEM jul2016
 * Document the data formats and HTTP methods we support.
 * http://docs.brapi.apiary.io/
 * Change Log
 * CLB 4/10/2017 - added traits, use empty list instead of null for status
 * CLB 1/22/2018 - added crops
 * CLB 2/06/2018 - change parameter format to include curly braces
 */

require '../../includes/bootstrap.inc';
$mysqli = connecti();

// URI is something like /calls?call=allelematrix&datatype=tsv&pageSize=100&page=1
if (isset($_GET['call'])) {
    $call = $_GET['call'];
}
if (isset($_GET['datatype'])) {
    $datatype = $_GET['datatype'];
}
if (isset($_GET['pageSize'])) {
    $pageSize = $_GET['pageSize'];
} else {
    $pageSize = 100;
}
if (isset($_GET['page'])) {
    $currentPage = $_GET['page'];
} else {
    $currentPage = 0;
}

/* Array of our supported calls */
$ourcalls['allelematrices'] = ['datatypes' => ["flapjack"], 'methods' => ["GET"]];
$ourcalls['allelematrix-search'] = ['datatypes' => ["json", "flapjack"], 'methods' => ["GET", "POST"]];
$ourcalls['allelematrices/{studyDbId}'] = ['datatypes' => ["json"], 'methods' => ["GET"]];
$ourcalls['markerprofiles'] = ['datatypes' => ["json"], 'methods' => ["GET"]];
$ourcalls['markerprofiles/{markerprofileDbId}'] = ['datatypes' => ["json"], 'methods' => ["GET"]];
$ourcalls['markerprofiles/{germplasmDbId}'] = ['datatypes' => ["json"], 'methods' => ["GET"]];
$ourcalls['calls'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['germplasm-search'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['germplasm'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['studies-search'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['studies-search/{studyType}'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['studies'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['studies/{studyDbId}'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['trials'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['trials/{trialDbId'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['traits'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['maps'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['maps/{mapDbId}'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['maps/{mapDbId}/positions'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['crops'] = ['datatypes' => ['json'], 'methods' => ['GET']];
$ourcalls['locations'] = ['datatypes' => ['json'], 'methods' => ["GET"]];
$ourcalls['markers'] = ['datatypes' => ["json"], 'methods' => ["GET"]];
$ourcalls['markers/{markerDbId}'] = ['datatypes' => ["json"], 'methods' => ["GET"]];
$ourcalls['markers-search'] = ['datatypes' => ["json"], 'methods' => ["GET", "POST"]];
$ourcalls['markers-search/{markerDbIds}'] = ['datatypes' => ["json"], 'methods' => ["GET", "POST"]];

/* If no request parameters, list all calls supported. */
if (!$call && !$datatype) {
    foreach (array_keys($ourcalls) as $ourcall) {
        $data[] = ['call'=>$ourcall, 'datatypes'=>$ourcalls[$ourcall]['datatypes'],
        'methods'=>$ourcalls[$ourcall]['methods']];
    }
    respond($data);
}

/* If a call is requested, show only that one. */
if ($call) {
    $data[] = ['call'=>$call, 'datatypes'=>$ourcalls[$call]['datatypes'], 'methods'=>$ourcalls[$call]['methods']];
    respond($data);
}

/* If a datatype is requested, show all calls that support that datatype. */
if ($datatype) {
    foreach (array_keys($ourcalls) as $ourcall) {
        if (in_array($datatype, $ourcalls[$ourcall]['datatypes'])) {
            $data[] = ['call'=>$ourcall, 'datatypes'=>$ourcalls[$ourcall]['datatypes'],
            'methods'=>$ourcalls[$ourcall]['methods']];
        }
    }
    respond($data);
}


function respond($data)
{
    global $pageSize, $currentPage;
    $count = count($data);
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");
    $response['metadata']['pagination']['pageSize'] = $pageSize;
    $response['metadata']['pagination']['currentPage'] = $currentPage;
    $response['metadata']['pagination']['totalCount'] = $count;
    $response['metadata']['pagination']['totalPages'] = ceil($count / $pageSize);
    $response['metadata']['status'] = array();
    $response['metadata']['datafiles'] = array();
    // First page is 0, according to Apiary.
    for ($p = $currentPage * $pageSize; $p < ($currentPage + 1) * $pageSize; $p++) {
        if (isset($data[$p])) {
            $response['result']['data'][] = $data[$p];
        }
    }
    echo json_encode($response);
}

function dieNice($msg)
{
    $outarray['metadata']['pagination'] = null;
    $outarray['metadata']['status'] = array("code" => 1, "message" => "Error: $msg");
    $outarray['result'] = null;
    $return = json_encode($outarray);
    header("Content-Type: application/json");
    die("$return");
}
