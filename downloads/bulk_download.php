<?php

$pageTitle = "Bulk Downloads";

require 'config.php';
require_once $config['root_dir'].'includes/bootstrap.inc';

// connect to database
$mysqli = connecti();

if (isset($_GET['query'])) {
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment;Filename=LineRecords.csv");
    $query = $_GET['query'];
    if ($query == "lines") {
        $sql = "select line_record_uid, barley_ref_number from barley_pedigree_catalog_ref";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $lineuid = $row[0];
            $grin_names[$lineuid][] = $row[1];
        }
        $sql = "select pedigree_relations.line_record_uid, line_record_name, parent_id from pedigree_relations, line_records
        where pedigree_relations.parent_id = line_records.line_record_uid
        and pedigree_relations.line_record_uid";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $lineuid = $row[0];
            $line_name = $row[1];
            $parent_id = $row[2];
            if (isset($parent_list[$lineuid]['parent1DbId'])) {
                $parent_list[$lineuid]['parent2DbId'] = $parent_id;
                $parent_list[$lineuid]['parent2Name'] = $line_name;
            } else {
                $parent_list[$lineuid]['parent1DbId'] = $parent_id;
                $parent_list[$lineuid]['parent1Name'] = $line_name;
            }
        }
        $sql = "select line_record_uid, line_synonym_name from line_synonyms";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $uid = $row[0];
            $name = $row[1];
            if (isset($syn_names[$uid])) {
                $syn_names[$uid] .= ", $name";
            } else {
                $syn_names[$uid] = $name;
            }
        }
        $sql = "select line_record_uid, line_record_name, breeding_program_code, pedigree_string, description from line_records";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        echo "\"Name\",\"GRIN\",\"Synonym\",\"Breeding Program\",\"Parent1\",\"Parent2\",\"Pedigree\",\"Description\"\n";
        while ($row = mysqli_fetch_row($result)) {
            $lineuid = $row[0];
            if (is_array($grin_names[$lineuid])) {
                $gr = implode(', ', $grin_names[$lineuid]);
            } else {
                $gr = "";
            }
            if (isset($parent_list[$lineuid])) {
                $parent1 = $parent_list[$lineuid]['parent1Name'];
                $parent2 = $parent_list[$lineuid]['parent2Name'];
            } else {
                $parent1 = "";
                $parent2 = "";
            }
            if (isset($syn_names[$lineuid])) {
                $synonym = $syn_names[$lineuid];
            } else {
                $synonym = "";
            }
            echo "\"$row[1]\",\"$gr\",\"$synonym\",\"$row[2]\",\"$parent1\",\"$parent2\",\"$row[3]\",\"$row[4]\"\n";
        }
    } elseif ($query == "properties") {
        $sql = "select line_record_uid, line_record_name from line_records";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $uid = $row[0];
            $name = $row[1];
            $line_name_list[$uid] = $name;
        }
        $sql = "select properties_uid, name from properties";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $uid = $row[0];
            $name = $row[1];
            $property_name_list[$uid] = $name;
        }
        $sql = "select line_record_uid, value, property_uid from line_properties, property_values
        where line_properties.property_value_uid = property_values.property_values_uid order by line_record_uid";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        echo "\"Nae\",\"Property\",\"Value\"\n";
        while ($row = mysqli_fetch_row($result)) {
            $lineuid = $row[0];
            $value = $row[1];
            $property_uid = $row[2];
            $line_name = $line_name_list[$lineuid];
            $property_name = $property_name_list[$property_uid];
            echo "$line_name,\"$property_name\",\"$value\"\n";
        }
    }
} else {
    include $config['root_dir'].'theme/admin_header2.php';
    echo "<h2>Bulk Download</h2>";
    echo "Downloads all records in the database into CSV formatted file<br><br>";
    $result = mysql_grab("select database()");
    $url = $config['base_url'] . "downloads/bulk_download.php?query=lines";
    echo "<a href=\"$url\">Line Records</a>";
    if (preg_match("/Oat/i", $result)) {
        echo "<br><a href=\"/POOL/bulk_download.php\">Line Records</a> in <a href=/POOL><b>P</b>edigrees <b>Of</b> <b>O</b>at <b>L</b>ines</a></a>";
    }
    $url = $config['base_url'] . "downloads/bulk_download.php?query=properties";
    echo "<br><a href=\"$url\">Genetic characters</a>";
    echo "</div>";
    include $config['root_dir'].'theme/footer.php';
}
