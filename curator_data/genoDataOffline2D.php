<?php
//*********************************************
// Genotype data importer - also contains various   
// pieces of import code by Julie's team @ iowaStateU  

// 10/25/2011  JLee   Ignore "cut" portion of input file 

// 10/17/2011 JLee  Add username and resubmission entry to input file log table
// 10/17/2011 JLee  Create of input file log entry
// 4/11/2011 JLee   Add ability to handle zipped data files

// Written By: John Lee
//*********************************************
error_reporting(E_ALL ^ E_NOTICE);
$progPath = realpath(dirname(__FILE__).'/../').'/';

include($progPath. 'includes/bootstrap_curator.inc');
include($progPath . 'curator_data/lineuid.php');
require_once $progPath . 'includes/email.inc';

ini_set (auto_detect_line_endings,1);

$num_args = $_SERVER["argc"];
$fnames = $_SERVER["argv"];
$lineTransFile = $fnames[1];
$gDataFile = $fnames[2];
$emailAddr = $fnames[3];
$urlPath = $fnames[4];
$userName = $fnames[5];
$filename = stristr ($gDataFile,basename ($gDataFile));

$error_flag = 0;
$lineExpHash = array ();
$lineDsHash = array ();
$curTrialCode = '';
$gName = '';

echo "Start time - ". date("m/d/y : H:i:s", time()) ."\n"; 
echo "Translate File - ". $lineTransFile. "\n";
echo "Genotype Data File - ". $gDataFile. "\n";
echo "URL - " . $urlPath . "\n";
echo "Email - ". $emailAddr."\n";

$linkID = connect(); 

$target_Path = substr($lineTransFile, 0, strrpos($lineTransFile, '/')+1);
$tPath = str_replace('./','',$target_Path);

$errorFile = $target_Path."importError.txt";
echo $errorFile."\n";
if (($errFile = fopen($errorFile, "w")) === FALSE) {
   echo "Unable to open the error log file.";
   exit(1);
}

// Testing for non-processing
//exit (1);
// ******* Email Stuff *********
//senders name
$Name = "Genotype Data Importer"; 
//senders e-mail adress
$sql ="SELECT value FROM  settings WHERE  name = 'capmail'";
$res = mysql_query($sql) or die("Database Error: setting lookup - ". mysql_error()."\n\n$sql");
$rdata = mysql_fetch_assoc($res);
$myEmail=$rdata['value'];
$mailheader = "From: ". $Name . " <" . $myEmail . ">\r\n"; //optional headerfields
$subject = "Genotype import results";

//Check inputs
 if ($lineTransFile == "") {
    exitFatal ($errFile,  "No Line Translation File Uploaded.");
}  
  
if ($gDataFile == "") {
    exitFatal ($errFile, "No Genotype Data Uploaded.");
}  

if ($emailAddr == "") {
    echo "No email address. \n";
    exit (1);
}  

// Check for zip file
if (strpos($gDataFile, ".zip") == TRUE) {
	echo "Unzipping the genotype data file...\n";
	$zip = new ZipArchive;
	$zip->open($gDataFile) || exitFatal ($errFile, "Unable to open zip file, please check zip format.");
	$gName = $zip->getNameIndex(0);
	$zip->extractTo($target_Path) || exitFatal ($errFile, "Failed to extract file from the zip file.");
	$zip->close()  || exitFatal ($errFile, "Failed to close zip file.");
	$gDataFile = $target_Path . $gName;
	echo "Genotype data unzipping done.\n";
}

/* Read the file */
if (($reader = fopen($lineTransFile, "r")) == FALSE) {
    exitFatal ($errFile, "Unable to access translate file.");
}
            
 // Check first line for header information
if (($line = fgets($reader)) == FALSE) {
    exitFatal ($errFile, "Unable to locate header names on first line of file.");
}     

echo "Processing line translation file...\n";

$header = str_getcsv($line,"\t");
 // Set up header column; all columns are required
$lineNameIdx = implode(find("Line Name", $header),"");
$trialCodeIdx = implode(find("Trial Code", $header),"");
echo "Using Line Name column = $lineNameIdx, Trial Code column = $trialCodeIdx\n";
            
if (($lineNameIdx == "")||($trialCodeIdx == "")) {
   exitFatal ($errFile,"ERROR: Missing one of the required columns in Line Translation file. Please correct it and try upload again.");
}
  
// Store individual records
$num = 0;
$linenumber = 0;
while(($line = fgets($reader)) !== FALSE) { 
  $linenumber++;
  $origline = $line;
    chop ($line, "\r");
    if (strlen($line) < 2) continue;
    if (feof($reader)) break;
    if (empty($line)) continue;
    if ((stripos($line, '- cut -') > 0 )) break;
                
    $data = str_getcsv($line,"\t");
                        
    //Check for junk line
    if (count($data) != 2) {
      //exitFatal ($errFile, "ERROR: Invalid entry in Line Translation file - '$line' ");
      $parsed = print_r($data, TRUE);
      exitFatal ($errFile, "ERROR: Invalid entry in line number $linenumber of Line Translation file.\n Text of line: '$origline'\nContents parsed as: $parsed");
    }
    $trialCodeStr = $data[$trialCodeIdx];
    $lineStr = $data[$lineNameIdx];
                
    //echo  $lineStr . " - ". $trialCodeStr. "<br>"; 
    // Trial Code processing
    if (($curTrialCode != $trialCodeStr) && ($trialCodeStr != '')) {
                     
        $res = mysql_query("SELECT experiment_uid FROM experiments WHERE trial_code = '$trialCodeStr'") 
            or exitFatal ($errFile, "Database Error: Experiment uid lookup - ".mysql_error());
        $exp_uid = implode(",",mysql_fetch_assoc($res));
                    
        $res = mysql_query("SELECT datasets_experiments_uid FROM datasets_experiments WHERE experiment_uid = '$exp_uid'")
            or exitFatal ($errFile, "Database Error: Dataset experiment uid lookup - ".mysql_error());
        $de_uid=implode(",",mysql_fetch_assoc($res));

        $curTrialCode = $trialCodeStr;
        $num++;
    }
    $lineExpHash[$lineStr] = $exp_uid;
    $lineDsHash[$lineStr] = $de_uid;
}    
fclose($reader);   
echo "Line translation file processing done. $num\n";

echo "Start genotyping record creation process...\n";
//Process Genotype data
/* start reading the input */
//echo "genotype file - " . $gDataFile . "<br>";

/* Read the file */
if (($reader = fopen($gDataFile, "r")) == FALSE) {
    exitFatal ($errFile, "Unable to access genotype data file.");
}
        
//Advance to data header area
while(!feof($reader))  {
    $line = fgets($reader);
    if (preg_match("/SNP/",$line)) {
      echo "Header line found\n";
      break;
    } else {
      exitFatal ($errFile, "Could not find header in $gDataFile $line.");    
    }
}
        
if (feof($reader)) {
    exitFatal ($errFile, "Unable to locate genotype header line.");
}

//Get column location  
$header = str_getcsv($line,"\t");
$num = count($header);
for ($x = 0; $x < $num; $x++) {
  switch ($header[$x] ) {
	case 'SNP':
	 	$nameIdx = $x;
		$dataIdx = $x + 1;
		break;
  }
}
                     
$rowNum = 0;
$line_name = "qwerty";
$errLines = 0;
$data = array();
    
while (!feof($reader))  {
    // If we have too many errors stop processing - something is wrong
    If ($errLines > 1000) {
       exitFatal ($errFile, "ERROR: Too many import lines have problem."); 
    }    
    $line = fgets($reader);
    if (strlen($line) < 2) next;
    if (empty($line)) next;
    if (feof($reader)) break;
    $data = str_getcsv($line,"\t");
    $marker = $data[$nameIdx];
    echo "working on marker $marker\n";
    $num = count($data);		// number of fields
    // Check line for missing column    
    if ($num < 96) { 
        $msg = "ERROR: Wrong number of entries  for marker - " . $marker;
        fwrite($errFile, $msg);
        $errLines++;
        next;
    } else {
 	echo "found $num of entries data $num\n";
    }   
    
    /* check if marker is EST synonym, if not found, then check name */
    $sql ="SELECT ms.marker_uid FROM  marker_synonyms AS ms WHERE ms.value='$marker'";
    $res = mysql_query($sql) or exitFatal ($errFile, "Database Error: Marker synonym lookup - ". mysql_error()."\n\n$sql");
    // fwrite($errFile,$sql);
    $rdata = mysql_fetch_assoc($res);
    $marker_uid=$rdata['marker_uid'];
    if (empty($marker_uid)) {
    	$sql = "SELECT m.marker_uid FROM  markers AS m WHERE m.marker_name ='$marker'";
    	$res = mysql_query($sql) or exitFatal ($errFile, "Database Error: Marker lookup - ". mysql_error()."\n\n$sql");
    	// fwrite($errFile,$sql);
    	if (mysql_num_rows($res) < 1) {
    		$markerflag = 1;
    		$msg = 'ERROR:  marker not found '.$marker.'\n';
    		fwrite($errFile, $msg);
    		$errLines++;
    		next;
    	} else {
    		$rdata = mysql_fetch_assoc($res);
    		$marker_uid=$rdata['marker_uid'];
    	}
    }
    
    $rowNum++;		// number of lines
    $markerflag = 0;        //flag for checking marker existence
    $data_pt = 0;
    for ($data_pt = $dataIdx; $data_pt < $num; $data_pt++) {
      $line_name = $header[$data_pt];

      if ($markerflag == 0) {
	  /* get line record ID */ 
	  //echo $line_name,"\n";
            $msg = "line name = " . $line_name. "\n";
	    // fwrite($errFile, $msg);
            $line_uid = get_lineuid ($line_name);
            if ($line_uid == FALSE) {
                $msg = $line_name . " cannot be found, upload stopped\n";
                exitFatal ($errFile, $msg);
            }
            $line_uid = implode(",",$line_uid);
            $exp_uid = $lineExpHash[$line_name];
            //$msg = "exp_uid = " . $exp_uid . "\n";
	    //fwrite($errFile, $msg);
            $de_uid = $lineDsHash[$line_name];
            //echo "de_uid = " . $exp_uid . "<br>";
//        }
				
        /* get thtbase_uid. If null, then we have to create this ID */
	    $sql = "SELECT tht_base_uid FROM tht_base WHERE experiment_uid= '$exp_uid' AND line_record_uid='$line_uid' ";
	    $rtht = mysql_query($sql) or exitFatal ($errFile, "Database Error: tht_base lookup - ". mysql_error() . ".\n\n$sql");
	    // fwrite($errFile,$sql);
	    $rqtht = mysql_fetch_assoc($rtht);
	    $tht_uid = $rqtht['tht_base_uid'];
				
	    if (empty($tht_uid)) {
            $sql ="INSERT INTO tht_base (line_record_uid, experiment_uid, datasets_experiments_uid, updated_on, created_on)
					VALUES ('$line_uid', $exp_uid, $de_uid, NOW(), NOW())" ;
            $res = mysql_query($sql) or exitFatal ($errFile, "Database Error: tht_base insert failed - ". mysql_error() . ".\n\n$sql");
            $sql = "SELECT tht_base_uid FROM tht_base WHERE experiment_uid = '$exp_uid' AND line_record_uid = '$line_uid'";
            $rtht=mysql_query($sql) or exitFatal ($errFile, "Database Error: post tht_base insert - ". mysql_error(). ".\n\n$sql");
            $rqtht=mysql_fetch_assoc($rtht);
            $tht_uid=$rqtht['tht_base_uid'];
        }
					
    	/* get the genotyping_data_uid */
    	$sql ="SELECT genotyping_data_uid FROM genotyping_data WHERE marker_uid=$marker_uid AND tht_base_uid=$tht_uid ";
    	$rgen=mysql_query($sql) or exitFatal ($errFile, "Database Error: genotype_data lookup - ". mysql_error(). ".\n\n$sql");
    	$rqgen=mysql_fetch_assoc($rgen);    
    	$gen_uid=$rqgen['genotyping_data_uid'];
				
    	if (empty($gen_uid)) {
    	    $sql="INSERT INTO genotyping_data (tht_base_uid, marker_uid, updated_on, created_on)
					VALUES ($tht_uid, $marker_uid, NOW(), NOW())" ;
            $res = mysql_query($sql) or exitFatal ($errFile, "Database Error: genotype_data insert - ". mysql_error() . ".\n\n$sql");
            $sql ="SELECT genotyping_data_uid FROM genotyping_data WHERE marker_uid = $marker_uid AND tht_base_uid=$tht_uid ";
            $rgen=mysql_query($sql) or exitFatal ($errFile, "Database Error: post genotype_data lookup - ". mysql_error(). ".\n\n$sql");
            $rqgen=mysql_fetch_assoc($rgen);
            $gen_uid=$rqgen['genotyping_data_uid'];
        }
		// echo "gen_uid".$gen_uid."\n";
		/* Read in the rest of the variables */
        $alleles = $data[$data_pt];
        $allele1 = substr($data[$data_pt],0,1);
	$allele2 = substr($data[$data_pt],1,1);
	if (($alleles == 'AA') || ($alleles == 'BB') || ($alleles == '--') || ($alleles == 'AB') || ($alleles == 'BA')) {
            $result =mysql_query("SELECT genotyping_data_uid FROM alleles WHERE genotyping_data_uid = $gen_uid");
	    $rgen=mysql_num_rows($result);
	    if ($rgen < 1) {
		      $sql = "INSERT INTO alleles (genotyping_data_uid,allele_1,allele_2,
						updated_on, created_on)
						VALUES ($gen_uid,'$allele1','$allele2', NOW(), NOW()) ";
            } else {
		      $sql = "UPDATE alleles
			  SET allele_1='$allele1',allele_2='$allele2',
			  updated_on=NOW() 
			  WHERE genotyping_data_uid = $gen_uid";
	    }
	    $res = mysql_query($sql) or exitFatal ($errFile, "Database Error: alleles processing - ". mysql_error() . ".\n\n$sql");
	    if ($res != 1) { 
                  $msg = "ERROR:  Allele not loaded! row = " . $rowNum ."\t" . $line;
                  fwrite($errFile, $msg);
                  $errLines++;
            }
 	} else {
 	    	echo "bad data at " . $line_name . " $data[$data_pt]\n";
 	}
      }
    }
} // End of while data 
fclose($reader);
echo "Genotyping record creation completed.\n";

fclose($errFile);

// Send out status email
if (filesize($errorFile)  > 0) {
    $body = "There was a problem during the offline importing process.\n".
        "Please have the curator review the error file at " . $urlPath.'curator_data/'.$tPath . "\n";
    echo "Genotype Data Import processing encountered some errors, check error file ". $errorFile , " for more information\n";
    
} else {
    $body = "The offline genotype data import completed successfully.\n".
			"Genotyping data import completed at - ". date("m/d/y : H:i:s", time()). "\n\n".
            "Additional information can be found at ".$urlPath.'curator_data/'.$tPath."genoProc.out\n";
    echo "Genotype Data Import Processing Successfully Completed\n";
}
mail($emailAddr, $subject, $body, $mailheader);

echo "Genotype Data Import Done\n";
echo "Finish time - ". date("m/d/y : H:i:s", time()). "\n";

$sql = "SELECT input_file_log_uid from input_file_log 
	WHERE file_name = '$filename'";
$res = mysql_query($sql) or die("Database Error: input_file lookup  - ". mysql_error() ."<br>".$sql);
$rdata = mysql_fetch_assoc($res);
$input_uid = $rdata['input_file_log_uid'];
        
if (empty($input_uid)) {
	$sql = "INSERT INTO input_file_log (file_name,users_name, created_on)
		VALUES('$filename', '$userName', NOW())";
} else {
	$sql = "UPDATE input_file_log SET users_name = '$userName', created_on = NOW()
		WHERE input_file_log_uid = '$input_uid'"; 
}
mysql_query($sql) or die("Database Error: Input file log entry creation failed - " . mysql_error() . "\n\n$sql");

$filename = stristr ($lineTransFile,basename ($lineTransFile));
$sql = "SELECT input_file_log_uid from input_file_log 
        WHERE file_name = '$filename'";
$res = mysql_query($sql) or die("Database Error: input_file lookup  - ". mysql_error() ."<br>".$sql);
$rdata = mysql_fetch_assoc($res);
$input_uid = $rdata['input_file_log_uid'];

if (empty($input_uid)) {
        $sql = "INSERT INTO input_file_log (file_name,users_name, created_on)
                VALUES('$filename', '$userName', NOW())";
} else {
        $sql = "UPDATE input_file_log SET users_name = '$userName', created_on = NOW()
                WHERE input_file_log_uid = '$input_uid'";
}
mysql_query($sql) or die("Database Error: Input file log entry creation failed - " . mysql_error() . "\n\n$sql");

exit(0);

//********************************************************
function exitFatal ($handle, $msg) {

    global $emailAddr;
    global $mailheader;
    global $tPath; 
	global $urlPath; 
    
    // Send to stdout
    echo $msg;
    // send to error log
    fwrite($handle, $msg);
    fclose ($handle);
    // Send email
    $subject = 'Fatal Import Error';
    $body = "There was a fatal problem during the offline importing process.\n". $msg. "\n\n" .
        "Additional information can be found at ".$urlPath.'curator_data/'.$tPath. "\n";      
    mail($emailAddr, $subject, $body, $mailheader);
    exit(1);
}

?>
