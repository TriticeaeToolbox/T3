<?php
/**
 * brapi/v1/germplasm.php, DEM jul 2014
 * Deliver Line names according to http://docs.breeding.apiary.io/
 */

require '../../includes/bootstrap.inc';
$mysqli = connecti();

$self = $_SERVER['PHP_SELF'];
$script = $_SERVER["SCRIPT_NAME"]."/";
$rest = str_replace($script, "", $self);
$rest = explode("/", $rest);
header("Content-Type: application/json");

$germplasmName = "";
$currentPage = 0;
if (!empty($rest[0])) {
    $lineuid = $rest[0];
} elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['germplasmDbId'])) {
        $lineuid = $_POST['germplasmDbId'];
    }
    if (isset($_POST['matchMethod'])) {
        $matchMethod = $_POST['matchMethod'];
    } else {
        $matchMethod = null;
    }
    if (isset($_POST['pageSize'])) {
        $pageSize = $_POST['pageSize'];
    } else {
        $pageSize = 1000;
    }
    if (isset($_POST['page'])) {
        $currentPage = $_POST['page'];
    }
    if (isset($_POST['germplasmName'])) {
        $germplasmName = $_POST['germplasmName'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['germplasmDbId'])) {
        $lineuid = $_GET['germplasmDbId'];
    }
    // Extract the URI's querystring, ie "name={name}".
    if (isset($_GET['matchMethod'])) {
        $matchMethod = $_GET['matchMethod'];
    } else {
        $matchMethod = null;
    }
    if (isset($_GET['pageSize'])) {
        $pageSize = $_GET['pageSize'];
    } else {
        $pageSize = 1000;
    }
    if (isset($_GET['page'])) {
        $currentPage = $_GET['page'];
    }
    if (isset($_GET['germplasmName'])) {
        $germplasmName = $_GET['germplasmName'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header("Content-Type: application/json");
    die();
} else {
    dieNice("Error", "invalid request method");
}


$r['metadata']['status'] = array();
$r['metadata']['datafiles'] = array();

function dieNice($msg)
{
    $linearray["metadata"]["pagination"] = null;
    $linearray["metadata"]["status"] = array("code" => 1, "message" => "SQL Error: $msg");
    $linearray["metadata"]["datafiles"] = array();
    $linearray["result"] = null;
    $return = json_encode($linearray);
    die("$return");
}

$sql = "select value from settings where name = \"species\"";
$res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
if ($row = mysqli_fetch_array($res)) {
    $species = $row[0];
} else {
    $species = null;
}

if ($rest[1] == "pedigree") {
    /* first get basic info */
    $sql = "select line_record_name, pedigree_string from line_records where line_record_uid = ?";
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $lineuid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $line_record_name, $pedigree);
        if (mysqli_stmt_fetch($stmt)) {
            $response["germplasmDbId"] = "$lineuidi";
            $response['defaultDisplayName'] = $line_record_name;
            if (preg_match("/[A-Za-z0-9]/", $pedigree)) {
                $response['pedigree'] = $pedigree;
            } else {
                $response['pedigree'] = "";
            }
        }
        mysqli_stmt_close($stmt);
    }
 
    /* then look in pedigree_relations*/
    $sql = "select line_record_name, parent_id from pedigree_relations, line_records
       where pedigree_relations.parent_id = line_records.line_record_uid
       and pedigree_relations.line_record_uid = ?";
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $lineuid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $line_record_name, $parent_id);
        while (mysqli_stmt_fetch($stmt)) {
            if (isset($response['parent1DbId'])) {
                $response['parent2DbId'] = "$parent_id";
                $response['parent2Name'] = $line_record_name;
            } else {
                $response['parent1DbId'] = "$parent_id";
                $response['parent1Name'] = $line_record_name;
            }
        }
        mysqli_stmt_close($stmt);
    }
    /* if not found in pedigree_relations then look in line_records */
    if (!isset($response['parent1DbId'])) {
        if (preg_match("/^([^\/]+)\/([^\/]+)/", $pedigree, $match)) {
            $response['parent1Name'] = trim($match[1]);
            $response['parent2Name'] = trim($match[2]);
        }
    }
    $r['metadata']['pagination']['pageSize'] = 1;
    $r['metadata']['pagination']['currentPage'] = $currentPage;
    $r['metadata']['pagination']['totalCount'] = 1;
    $r['metadata']['pagination']['totalPages'] = 1;
    $r['result'] = $response;
    echo json_encode($r);
} elseif ($rest[1] == "progeny") {
    /* first get basic info */
    $sql = "select line_record_name, pedigree_string from line_records where line_record_uid = ?";
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $lineuid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $line_record_name, $pedigree);
        if (mysqli_stmt_fetch($stmt)) {
            $response["germplasmDbId"] = $lineuid;
            $response['defaultDisplayName'] = $line_record_name;
        }
        mysqli_stmt_close($stmt);
    }

    $response['progeny'] = array();
    /* first look in pedigree_relations */
    $sql = "select pedigree_relations.line_record_uid, parent_id, line_record_name from pedigree_relations, line_records
       where pedigree_relations.line_record_uid = line_records.line_record_uid
       and pedigree_relations.parent_id = $lineuid";
    $res = mysqli_query($mysqli, $sql) or dieNice(mysqli_error($mysqli));
    while ($row = mysqli_fetch_row($res)) {
        $temp['germplasmDbId'] = $row[0];
        $temp['defaultDisplayName'] = $row[2];
        $temp['parentType'] = "";
        $response['progeny'][] = $temp;
    }
    
    $r['metadata']['pagination']['pageSize'] = 1;
    $r['metadata']['pagination']['currentPage'] = $currentPage;
    $r['metadata']['pagination']['totalCount'] = 1;
    $r['metadata']['pagination']['totalPages'] = 1;
    $r['result'] = $response;
    echo json_encode($r);
    // Is there a requiest for line_record_uid?
} elseif (isset($lineuid)) {
    $sql = "select line_record_name, pedigree_string from line_records where line_record_uid = ?";
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $lineuid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $line_record_name, $pedigree);
        if (mysqli_stmt_fetch($stmt)) {
            $response["germplasmDbId"] = $lineuid;
            $response['defaultDisplayName'] = $line_record_name;
            $response['accessionNumber'] = null;
            $response['germplasmName'] = $line_record_name;
            $response['germplasmPUI'] = null;
            $response['pedigree'] = null;
            $response['seedSource'] = null;
            $response['synonyms'] = null;
            $response['commonCropName'] = $species;
            $response['instituteCode'] = "";
        } else {
            $response = null;
            $r['metadata']['status'][] = array("code" => "not found", "message" => "germplasm id not found");
        }
        mysqli_stmt_close($stmt);
    }
    $sql = "select line_synonym_name from line_synonyms where line_record_uid = ?";
    $stmt = mysqli_prepare($mysqli, $sql);
    mysqli_stmt_bind_param($stmt, "i", $lineuid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $line_synonyms);
    while (mysqli_stmt_fetch($stmt)) {
        $response['synonyms'][] = $line_synonyms;
    }
    mysqli_stmt_close($stmt);
    $sql = "select barley_ref_number from barley_pedigree_catalog_ref where line_record_uid = ?";
    $stmt = mysqli_prepare($mysqli, $sql);
    mysqli_stmt_bind_param($stmt, "i", $lineuid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $barley_ref_number);
    if (mysqli_stmt_fetch($stmt)) {
        $response['accessionNumber'] = $barley_ref_number;
    }
    mysqli_stmt_close($stmt);
    $r['metadata']['pagination']['pageSize'] = 1;
    $r['metadata']['pagination']['currentPage'] = $currentPage;
    $r['metadata']['pagination']['totalCount'] = 1;
    $r['metadata']['pagination']['totalPages'] = 1;
    $r['result'] = $response;
    echo json_encode($r);
} elseif (preg_match("/[A-Za-z]/", $germplasmName)) {
    // "Germplasm ID by Name".  URI is germplasm?name={name}
    $linename = $germplasmName;
    if ($matchMethod == "wildcard") {
        $sql = "select line_record_uid, line_record_name, pedigree_string from line_records where line_record_name like ?";
        $linename = "%" . $germplasmName . "%";
    } else {
        $sql = "select line_record_uid, line_record_name, pedigree_string from line_records where line_record_name = ?";
    }

    //first query all data
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $linename);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $num_rows = mysqli_stmt_num_rows($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die(mysqli_error($mysqli));
    }
    if ($currentPage == 0) {
        $sql .= " limit $pageSize";
    } else {
        $offset = $currentPage * $pageSize;
        if ($offset < 0) {
            $offset = 0;
        }
        $sql .= " limit $offset, $pageSize";
    }
    //echo "$linename $sql\n";
    $response = null;
    $r['metadata']['status'] = null;
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $linename);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $lineuid, $line_record_name, $pedigree);
        while (mysqli_stmt_fetch($stmt)) {
            $temp['germplasmDbId'] = $lineuid;
            $temp['defaultDisplayName'] = $line_record_name;
            $temp['germplasmName'] = $line_record_name;
            $temp['accessionNumber'] = null;
            $temp['germplasmPUI'] = null;
            $temp['pedigree'] = null;
            $temp['seedSource'] = null;
            $temp['synonyms'] = array();
            $response[] = $temp;
        }
        mysqli_stmt_close($stmt);
        if (empty($response)) {
            $r['metadata']['status'][] = array("code" => "not found", "message" => "germplasm name not found");
        }
        foreach ($response as $key => $item) {
            $lineuid = $item['germplasmDbId'];
            $sql = "select line_synonym_name from line_synonyms where line_record_uid = $lineuid";
            $temp = array();
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            while ($row = mysqli_fetch_row($res)) {
                $response[$key]['synonyms'][] = $row[0];
            }

            $sql = "select barley_ref_number from barley_pedigree_catalog_ref where line_record_uid = $lineuid";
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            if ($row = mysqli_fetch_row($res)) {
                $response[$key]['accessionNumber'] = $row[0];
            }
        }
    }
    $r['metadata']['pagination']['pageSize'] = $pageSize;
    $r['metadata']['pagination']['currentPage'] = $currentPage;
    $r['metadata']['pagination']['totalCount'] = $num_rows;
    $r['metadata']['pagination']['totalPages'] = ceil($num_rows / $pageSize);
    $r['result']['data'] = $response;
    echo json_encode($r);
} else {
    $sql = "select line_record_uid, line_record_name, pedigree_string from line_records";
    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
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

    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
    while ($row = mysqli_fetch_array($res)) {
        $temp['germplasmDbId'] = $row[0];
        $temp['defaultDisplayName'] = $row[1];
        $temp['accessionNumber'] = null;
        $temp['germplasmName'] = $row[1];
        $temp['germplasmPUI'] = null;
        if (empty($row[2])) {
            $temp['pedigree'] = null;
        } else {
            $temp['pedigree'] = htmlentities($row[2]);
        }
        $temp['seedSource'] = null;
        $temp['synonyms'] = array();
        $temp['commonCropName'] = $species;
        $temp['instituteCode'] = "";
        $response[] = $temp;
    }
  
    foreach ($response as $key => $item) {
        $lineuid = $item['germplasmDbId'];
        $sql = "select line_synonym_name from line_synonyms where line_record_uid = $lineuid";
        //echo "$key $sql\n";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($res)) {
            $response[$key]['synonyms'][] = $row[0];
        }

        $sql = "select barley_ref_number from barley_pedigree_catalog_ref where line_record_uid = $lineuid";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($res)) {
            $response[$key]['accessionNumber'] = $row[0];
        }
    }
        
    $r['metadata']['pagination']['pageSize'] = $pageSize;
    $r['metadata']['pagination']['currentPage'] = $currentPage;
    $r['metadata']['pagination']['totalCount'] = $num_rows;
    $r['metadata']['pagination']['totalPages'] = ceil($num_rows / $pageSize);
    $r['result']['data'] = $response;
    echo json_encode($r);
}
