<?php
/**
 * Display phenotype information for experiment
 *
 * PHP version 5.3
 *
 * @license  http://triticeaetoolbox.org/wheat/docs/LICENSE Berkeley-based
 * @link     http://triticeaetoolbox.org/wheat/display_phenotype.php
 *
 */

//A php script to dynamically read data related to a particular experiment from the database and to 
//display it in a nice table format. Utilizes the the tableclass Class by Manuel Lemos to display the 
//table.

session_start();
require 'config.php';
require $config['root_dir'].'includes/bootstrap.inc';
require $config['root_dir'].'theme/normal_header.php';
$delimiter = "\t";
$mysqli = connecti();
?>
<script type="text/javascript" src="compounds/display_metabolite.js"></script>
<?php

$trial_code=strip_tags($_GET['trial_code']);
// Display Header information about the experiment
$display_name=ucwords($trial_code); //used to display a beautiful name as the page header
        
// Restrict if private data.
if ($stmt = mysqli_prepare($mysqli, "SELECT data_public_flag FROM experiments WHERE trial_code = ?")) {
    mysqli_stmt_bind_param($stmt, "s", $trial_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $data_public_flag);
    if (mysqli_stmt_fetch($stmt)) {
        echo "<h1>Trial ".$display_name."</h1>";
    } else {
        mysqli_stmt_close($stmt);
        die("Error: trial not found\n");
    }
    mysqli_stmt_close($stmt);
} else {
    die("Error: bad sql statement\n");
}
if (($data_public_flag == 0) and
    (!authenticate(array(USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR)))) {
    echo "Results of this trial are restricted to project participants.";
} else {
    $sql="SELECT experiment_uid, experiment_set_uid, experiment_desc_name, experiment_year
          FROM experiments WHERE trial_code = ?";
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $trial_code);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $experiment_uid, $set_uid, $exptname, $year);
        if (!mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);
            die("Error: trial not found\n");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error: bad sql statement\n");
    }
    $datasets_exp_uid=$experiment_uid;
    if (!$experiment_uid) {
        die("Trial $trial_code not found.");
    }
    $query="SELECT * FROM metabolite_experiment_info WHERE experiment_uid='$experiment_uid'";
    $result_pei=mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));
    $row_pei=mysqli_fetch_array($result_pei);

    // Get Experiment (experiment_set) too.
    if ($set_uid) {
        $exptset = mysql_grab("SELECT experiment_set_name from experiment_set where experiment_set_uid=$set_uid");
    }
    // Get CAPdata_program too.
    $query="SELECT data_program_name, collaborator_name, program_type 
	  from CAPdata_programs, experiments
	  where experiment_uid = $experiment_uid
	  and experiments.CAPdata_programs_uid = CAPdata_programs.CAPdata_programs_uid";
    $result_cdp=mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));
    $row_cdp=mysqli_fetch_array($result_cdp);
    $dataprogram = $row_cdp['data_program_name'];
    $programType = ucfirst($row_cdp['program_type']);

    echo "<table>";
    if ($exptset) {
        echo "<tr> <td>Experiment</td><td>".$exptset."</td></tr>";
    }
    echo "<tr> <td>Trial Year</td><td>$year</td></tr>";
    if ($exptname) {
        echo "<tr> <td>Description</td><td>$exptname</td></tr>";
    }
    echo "<tr> <td>Platform</td><td>".$row_pei['platform']."</td></tr>";
    echo "<tr> <td>Software for feature detection</td><td>".$row_pei['feature_detection']."</td></tr>";
    echo "<tr> <td>Software for clustering features</td><td>".$row_pei['software_cluster']."</td></tr>";
    echo "<tr> <td>Software and database for annotations</td><td>".$row_pei['annotations']."</td></tr>";
    echo "<tr> <td>Comments</td><td>".$row_pei['comments']."</td></tr>";
    echo "<tr> <td>$programType Program</td><td>".$dataprogram."</td></tr>";
    echo "</table><p>";

    // get all line data for this experiment
    $sql="SELECT tht_base_uid, line_record_uid, check_line FROM tht_base WHERE experiment_uid='$experiment_uid'";
    $result_thtbase=mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        
    while ($row_thtbase=mysqli_fetch_array($result_thtbase)) {
         $thtbase_uid[] = $row_thtbase['tht_base_uid'];
         $linerecord_uid[] = $row_thtbase['line_record_uid'];
         $check_line[] = $row_thtbase['check_line'];
         //echo $row_thtbase['tht_base_uid']."  ".$row_thtbase['line_record_uid']."  ".$row_thtbase['check_line']."<br>";
    }
    $num_lines = count($linerecord_uid);

    $num_phenotypes = 0;
    $sql="SELECT spectra from spectra_merged_idx
          where experiment_uid = $experiment_uid";
    $result_thtbase=mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
    if ($row_thtbase=mysqli_fetch_array($result_thtbase)) {
        $header = json_decode($row_thtbase[0], true);
        $num_phenotypes = count($header);
    }

    //echo $num_lines."<br>";
    $titles=array('Line Name'); //stores the titles for the display table with units
    $titles[]="GRIN Accession";//add CAP Code column to titles

    if (!empty($thtbase_uid)) {
        $thtbasestring = implode(",", $thtbase_uid);

        $sql = "select compound_uid, compound_name from compounds";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($res)) {
            $comp_list[$row[0]] = $row[1];
        }
        
        $titles[]="Check"; //add the check column to the display table
   
        $all_rows=array(); //2D array that will hold the values in table format to be displayed
        $sum_rows=array(); //summary statistics
        $all_rows_long=array(); // For the full unrounded values
        $single_row=array(); //1D array which will hold each row values in the table format to be displayed
        $single_row_long=array();
        
        /* $dir ='./downloads/temp/';				 */
        $dir ='/tmp/tht/';
        if (! file_exists('/tmp/tht')) {
             mkdir('/tmp/tht');
        }

        // Clean up old files, older than 1 day.
        system("find $dir -mtime +1 -name 'THT_Phenotypes_*.txt' -delete");

        $stringData = implode($delimiter, $titles);
         
        //---------------------------------------------------------------------------------------------------------------
        //Go through lines to create a data table for display
        for ($lr_i=0; $lr_i<$num_lines; $lr_i++) {
            $thtbaseuid=$thtbase_uid[$lr_i];
            $linerecorduid=$linerecord_uid[$lr_i];
            //echo $linerecorduid."  ".$thtbaseuid."<br>";
            
            $sql_lnruid="SELECT line_record_name FROM line_records WHERE line_record_uid='$linerecorduid'";
            $result_lnruid=mysqli_query($mysqli, $sql_lnruid) or die(mysqli_error($mysqli));
            $row_lnruid=mysqli_fetch_assoc($result_lnruid);
            $lnrname=$row_lnruid['line_record_name'];
            $single_row[0]=$lnrname;
            $single_row_long[0]=$lnrname;

/* Use GRIN accession instead of Synonym */
/* // get the CAP code */
/* $sql_cc="SELECT line_synonym_name */
/* FROM line_synonyms */
/* WHERE line_synonyms.line_record_uid = '$linerecorduid'"; */
/* 	    $result_cc=mysql_query($sql_cc) or die(mysql_error()); */
/* 	    $row_cc=mysql_fetch_assoc($result_cc); */
/* 	    $single_row[1]=$row_cc['line_synonym_name']; */
/* 	    $single_row_long[1]=$row_cc['line_synonym_name']; */
            $sql_gr="select barley_ref_number
             from barley_pedigree_catalog bc, barley_pedigree_catalog_ref bcr
             where barley_pedigree_catalog_name = 'GRIN'
             and bc.barley_pedigree_catalog_uid = bcr.barley_pedigree_catalog_uid
             and bcr.line_record_uid = '$linerecorduid'";
            $result_gr=mysqli_query($mysqli, $sql_gr) or die(mysqli_error($mysqli));
            $row_gr=mysqli_fetch_assoc($result_gr);
            $single_row[1]=$row_gr['barley_ref_number'];
            $single_row_long[1]=$row_gr['barley_ref_number'];

            /*
            for ($i=0; $i<$num_phenotypes; $i++) {
                $puid=$phenotype_uid[$i];
                $sigdig=$unit_sigdigits[$i];
                $sql_val="SELECT value FROM phenotype_data
                    WHERE tht_base_uid='$thtbaseuid'
                    AND phenotype_uid = '$puid'";
                //echo $sql_val."<br>";
                /*$result_val=mysqli_query($mysqli, $sql_val);
                if (mysqli_num_rows($result_val) > 0) {
                    $row_val=mysqli_fetch_assoc($result_val);
                    $val=$row_val['value'];
                    $val_long=$val;
                    if ($sigdig >= 0) {
                        $val = floatval($val);
                        $val=number_format($val, $sigdig);
                    }
                } else {
                    $val = "--";
                    $val_long = "--";
                }
                if (empty($val)) {
                    $val = "--";
                    $val_long = "--";
                }
                $single_row[$i+2]=$val;
                $single_row_long[$i+2]=$val_long;
            }
            */
        //-----------------------------------------check line addition

            if ($check_line[$lr_i]=='yes') {
                $check=1;
            } else {
                $check=0;
            }
            //echo $check;
            $single_row[2]=$check;
            $single_row_long[2]=$check;
            //-----------------------------------------
            //var_dump($single_row_long);
            $stringData= implode($delimiter, $single_row_long);
            
            $all_rows[]=$single_row;
            $all_rows_long[]=$single_row_long;
        }
            //-----------------------------------------get statistics
 
        
        //-----------------------------------------
        $total_rows=count($all_rows); //used to determine the number of rows to be displayed in the result page
?>
       
<!--Style sheet for better user interface-->
<style type="text/css">
    th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
    table {background: none; border-collapse: collapse}
    td {border: 1px solid #eee !important;}
    h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
</style>

<!-- Calculate the width of the table based on the number of columns. -->		
<?php $tablewidth = count($single_row) * 92 + 10;  ?>
 
<div style="width: <?php echo $tablewidth; ?>px">
<table>
    <tr> 
    <?php
    for ($i=0; $i<count($titles); $i++) {
    ?>
    <th><div style="width: 75px;">
    <?php echo $titles[$i]?>
    </div></th>
    <?php
    }
    ?>
    </tr>
</table>
</div>

<div style="padding: 0; width: <?php echo $tablewidth; ?>px; height: 400px; overflow: scroll; overflow-x: hidden; border: 1px solid #5b53a6; clear: both"> 
<table>
<?php
for ($i = 0; $i < count($all_rows); $i++) {
    ?>
    <tr>
    <?php
    for ($j = 0; $j < count($single_row); $j++) {
        ?>
        <!-- <td><div style="width: 75px; overflow-x: hidden;"> -->
        <td><div style="width: 75px; word-wrap: break-word">
        <?php echo $all_rows[$i][$j] ?>
        </div></td> 
        <?php
    }/* end of for j loop */
    ?>
    </tr>
    <?php
}/* end of for i loop */
?>
</table>
</div>
<!---div style="padding: 0; width: <?php echo $tablewidth; ?>px; border: 1px solid #5b53a6; clear: both">
<table>
    <?php
    for ($i = 0; $i < 4; $i++) {
        echo "<tr>";
        for ($j = 0; $j < count($single_row); $j++) {
            echo "<td><div style=\"width: 75px; word-wrap: break-word\">";
            echo $sum_rows[$i][$j];
            echo "</div></td>";
        }
    }
    ?>
</table>
</div--!>			
        
<?php
}
    echo "<br>";
    echo "<form>";
    echo "<input type='button' value='Download Trial Data' onclick=\"javascript:output_file_plot('$trial_code');\" />";
    echo "</form>";

$sourcesql="SELECT input_data_file_name FROM experiments WHERE trial_code='$trial_code'";
$sourceres=mysqli_query($mysqli, $sourcesql) or die(mysqli_error($mysqli));
$sourcerow=mysqli_fetch_array($sourceres);
$sources=$sourcerow['input_data_file_name'];
if ($sources)
  echo "<p><b>Means file:</b> $sources";

echo "<p><b>Raw data files:</b> ";
$rawsql="SELECT name, directory from rawfiles where experiment_uid = $experiment_uid";
$rawres=mysqli_query($mysqli, $rawsql) or die(mysqli_error($mysqli));
while ($rawrow = mysqli_fetch_assoc($rawres)) {
  $rawfilename=$rawrow['name'];
  $rawdir = $rawrow['directory'];
  if ($rawdir)
    $rawfilename=$rawrow['directory']."/".$rawfilename;
  $rawfile="raw/phenotype/".$rawfilename;
  echo "<a href=".$config['base_url'].$rawfile.">".$rawrow['name']."</a><br>";
}
if (empty($rawfilename))  echo "none<br>";

echo "<p><b>Field Book:</b> ";
$rawsql="SELECT experiment_uid from fieldbook where experiment_uid = $experiment_uid";
$rawres=mysqli_query($mysqli, $rawsql) or die(mysqli_error($mysqli));
if ($rawrow = mysqli_fetch_assoc($rawres)) {
  $fieldbook="display_fieldbook.php?function=display&uid=$experiment_uid";
  echo "<a href=".$config['base_url'].$fieldbook.">$trial_code</a><br>\n";
}
if (empty($fieldbook)) echo "none";  
$pheno_str = "";
$rawsql="SELECT distinct(phenotypes_name) from phenotype_plot_data, phenotypes where phenotype_plot_data.phenotype_uid = phenotypes.phenotype_uid and experiment_uid = $experiment_uid";
$rawres=mysqli_query($mysqli, $rawsql) or die(mysqli_error($mysqli));
while ($rawrow = mysqli_fetch_assoc($rawres)) {
    if ($pheno_str == "") {
        $pheno_str = $rawrow['phenotypes_name'];
    } else {
        $pheno_str = $pheno_str . ", " . $rawrow['phenotypes_name'];
    }
}
if ($pheno_str != "") {
    echo "<b>Display Numeric map:</b> <a href=".$config['base_url']."display_map_exp.php?uid=$experiment_uid>$pheno_str</a><br>\n";
    echo "<b>Display Heat map:</b> <a href=".$config['base_url']."display_heatmap_exp.php?uid=$experiment_uid>$pheno_str</a><br>\n";
}

$found = 0;
$sql="SELECT date_format(measure_date, '%m-%d-%Y'), date_format(start_time, '%H:%i'), spect_sys_uid, raw_file_name, measurement_uid from csr_measurement where experiment_uid = $experiment_uid order by measure_date";
$res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
while ($row = mysqli_fetch_array($res)) {
  if ($found == 0) {
    echo "<table><tr><td>Measured Date<td>CSR Annotation<td>CSR Data<td>Spectrometer<br>System<td>CSR Data\n";
    $found = 1;
  }
  $date = $row[0];
  $time = $row[1];
  $sys_uid = $row[2];
  $raw_file = $row[3];
  $measurement_uid = $row[4];
  $trial="display_csr_exp.php?function=display&uid=$measurement_uid";
  $tmp2 = $config['base_url'] . "raw/phenotype/" . $raw_file;
  echo "<tr><td>$date $time";
  echo "<td><a href=".$config['base_url'].$trial.">View</a>";
  echo "<td><a href=\"$tmp2\">Open File</a>";

  $sql="SELECT system_name from csr_system where system_uid = $sys_uid";
  $res2 = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
  if ($rawrow = mysqli_fetch_assoc($res2)) {
    $system_name = $rawrow["system_name"];
    $trial= $config['base_url'] . "display_csr_spe.php?function=display&uid=$sys_uid";
    echo "<td><a href=$trial>$system_name</a>";
  } else {
    echo "<td>missing";
  }
  $trial= $config['base_url'] . "curator_data/cal_index.php";
  echo "<td><a href=$trial>Calculate Index</a>";
}
echo "</table>";
}
  
    //-----------------------------------------------------------------------------------
    $footer_div = 1;
    require $config['root_dir'].'theme/footer.php';
    ?>
