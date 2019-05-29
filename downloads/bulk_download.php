<?php

$pageTitle = "Bulk Downloads";

require 'config.php';
require_once $config['root_dir'].'includes/bootstrap.inc';

// connect to database
$mysqli = connecti();

if (isset($_GET['query'])) {
    header("Content-type: application/vnd.ms-excel");
    $query = $_GET['query'];
    if ($query == "experiment_data") {
        header("Content-Disposition: attachment;Filename=PhenotypeExperiments.csv");
        echo "Trial,location,planting date,harvest date,latitude,longitude\n";
        $sql = "select trial_code, location, planting_date, harvest_date, latitude, longitude from experiments, phenotype_experiment_info
            where experiments.experiment_uid = phenotype_experiment_info.experiment_uid";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            echo "$row[0],\"$row[1]\", $row[2], $row[3]\n";
        }
    } elseif ($query == "phenotype_data") {
        header("Content-Disposition: attachment;Filename=PhenotypeData.csv");
        $sql = "select line_record_uid, line_record_name from line_records";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $line_list[$row[0]] = $row[1];
        }
        $sql = "select experiment_uid, trial_code from experiments";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $exp_list[$row[0]] = $row[1];
        }
        $sql = "select phenotype_uid, phenotypes_name from phenotypes";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $phen_list[$row[0]] = $row[1];
        }
        
        echo "Line name,Trial,Trait,Value\n";
        $sql = "select line_record_uid, experiment_uid, phenotype_uid, value from phenotype_data, tht_base
            where phenotype_data.tht_base_uid = tht_base.tht_base_uid";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $line_name = $line_list[$row[0]];
            $exp_name = $exp_list[$row[1]];
            $phen_name = $phen_list[$row[2]];
            $value = $row[3];
            echo "$line_name,$exp_name,$phen_name,$value\n";
        }
    } elseif ($query == "lines") {
        header("Content-Disposition: attachment;Filename=LineRecords.csv");
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
            $name = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $name);
            if (isset($syn_names[$uid])) {
                $syn_names[$uid] .= ", $name";
            } else {
                $syn_names[$uid] = $name;
            }
        }
        $sql = "select properties_uid, name from properties where name = \"Species\"";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($result)) {
            $species_uid = $row[0];
        } else {
            die("Error: could not find Species\n");
        }

        $sql = "select line_record_uid, value, property_uid from line_properties, property_values
        where line_properties.property_value_uid = property_values.property_values_uid and property_uid = $species_uid";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $uid = $row[0];
            $name = $row[1];
            $species_list[$uid] = $name;
        }

        $sql = "select line_record_uid, line_record_name, breeding_program_code, pedigree_string, description from line_records";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        echo "\"Name\",\"Species\",\"GRIN\",\"Synonym\",\"Breeding Program\",\"Parent1\",\"Parent2\",\"Pedigree\",\"Description\"\n";
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
                $parent1 = str_replace("\"", "_", $parent1);
                $parent2 = str_replace("\"", "_", $parent2);
            } else {
                $parent1 = "";
                $parent2 = "";
            }
            if (isset($syn_names[$lineuid])) {
                $synonym = $syn_names[$lineuid];
            } else {
                $synonym = "";
            }
            if (isset($species_list[$lineuid])) {
                $species = $species_list[$lineuid];
            } else {
                $species = "";
            }
            $pedigree_string = $row[3];
            $desc = $row[4];
            $desc = preg_replace('/[^a-zA-Z0-9-_\.]/', ' ', $desc);
            $pedigree_string = str_replace("\"", "_", $pedigree_string);
            echo "\"$row[1]\",\"$species\",\"$gr\",\"$synonym\",\"$row[2]\",\"$parent1\",\"$parent2\",\"$pedigree_string\",\"$desc\"\n";
        }
    } elseif ($query == "properties") {
        header("Content-Disposition: attachment;Filename=LineProperties.csv");
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
    $url = $config['base_url'] . "downloads/bulk_download.php?query=phenotype_data";
    echo "<br><a href=\"$url\">Phenotype data</a>";
    $url = $config['base_url'] . "downloads/bulk_download.php?query=experiment_data";
    echo "<br><a href=\"$url\">Phenotype trials</a>";
    echo "</div>";
    include $config['root_dir'].'theme/footer.php';
}
