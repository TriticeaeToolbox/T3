<?php
/**
 * Brapi/v1/calls.php, DEM jul2016
 * Document the data formats and HTTP methods we support.
 * http://docs.brapi.apiary.io/
 * Change Log
 * CLB 4/10/2017 - added traits, use empty list instead of null for status
 * CLB 1/22/2018 - added crops
 * CLB 2/06/2018 - change parameter format to include curly braces
 * CLB 7/02/2018 - removed status, added versions
 */

require '../../includes/bootstrap.inc';
$mysqli = connecti();

// URI is something like /calls?call=allelematrix&datatype=tsv&pageSize=100&page=1
if (isset($_GET['call'])) {
    $call = $_GET['call'];
}
if (isset($_GET['dataType'])) {
    $datatype = $_GET['dataType'];
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
$ourcalls['allelematrices'] = ['datatypes' => ["application/flapjack","text/csv"], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['allelematrix-search'] = ['datatypes' => ["application/flapjack", "application/json", "text/tsv"], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['allelematrix-search/status'] = ['datatypes' => ["application/json" ], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['allelematrices/{studyDbId}'] = ['datatypes' => ["application/json"], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['markerprofiles'] = ['datatypes' => ["application/json"], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['markerprofiles/{markerprofileDbId}'] = ['datatypes' => ["application/json"], 'methods' => ["GET"]];
$ourcalls['markerprofiles/{germplasmDbId}'] = ['datatypes' => ["application/json"], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['calls'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['germplasm-search'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['germplasm'] = ['datatypes' => ['application/json'], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['germplasm/{germplasmDbId}'] = ['datatypes' => ['application/json'], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['germplasm/{germplasmDbId}/pedigree'] = ['datatypes' => ['application/json'], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['germplasm/{germplasmDbId}/progeny'] = ['datatypes' => ['application/json'], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['studies-search'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['studies-search/{studyType}'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['studies'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['studies/{studyDbId}'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['trials'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['trials/{trialDbId}'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['traits'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['maps'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['maps/{mapDbId}'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['maps/{mapDbId}/positions'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['crops'] = ['datatypes' => ['application/json'], 'methods' => ['GET'], 'versions' => ["1.3"]];
$ourcalls['locations'] = ['datatypes' => ['application/json'], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['markers'] = ['datatypes' => ['application/json'], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['markers/{markerDbId}'] = ['datatypes' => ["application/json"], 'methods' => ["GET"], 'versions' => ["1.3"]];
$ourcalls['markers-search'] = ['datatypes' => ["application/json"], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['markers-search/{markerDbIds}'] = ['datatypes' => ["application/json"], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];
$ourcalls['observationunits'] = ['datatypes' => ["application/json"], 'methods' => ["GET", "POST"], 'versions' => ["1.3"]];

/* If no request parameters, list all calls supported. */
if (!$call && !$datatype) {
    foreach (array_keys($ourcalls) as $ourcall) {
        $data[] = ['call'=>$ourcall, 'dataTypes'=>$ourcalls[$ourcall]['dataTypes'],
        'methods'=>$ourcalls[$ourcall]['methods'],
        'versions'=>$ourcalls[$ourcall]['versions']];
    }
    respond($data);
}

/* If a call is requested, show only that one. */
if ($call) {
    $data[] = ['call'=>$call, 'dataTypes'=>$ourcalls[$call]['dataTypes'], 'methods'=>$ourcalls[$call]['methods']];
    respond($data);
}

/* If a datatype is requested, show all calls that support that datatype. */
if ($datatype) {
    foreach (array_keys($ourcalls) as $ourcall) {
        if (in_array($datatype, $ourcalls[$ourcall]['dataTypes'])) {
            $data[] = ['call'=>$ourcall, 'dataTypes'=>$ourcalls[$ourcall]['dataTypes'],
            'methods'=>$ourcalls[$ourcall]['methods']];
        }
    }
    respond($data);
}


function respond($data)
{
    global $pageSize, $currentPage;
    $count = count($data);
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
