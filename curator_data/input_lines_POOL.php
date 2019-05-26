<?php
/**
 * 22feb2017 dem: Load POOL data, and the pedigree info into T3/Oat. 
 * 4apr2016 dem: Handle template for any of our crops
 * aug2014 dem: Allow a different Breeding Program for each Line.
 * 21feb2013 dem: Use line_properties table instead of schema-coded properties.
 */

require 'config.php';
include $config['root_dir'] . 'includes/bootstrap_curator.inc';
require_once "../lib/Excel/reader.php"; // Microsoft Excel library
// Connect to T3 database.
$mysqli = connecti();
loginTest();

$row = loadUser($_SESSION['username']);
ob_start();
authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();

include("/data/inc/POOL2_edit.inc");
// Connect to POOL database.
$cnx=DBopen();
$error = sanitize_vars();
if($error){print $error;die;}

/* The Excel file must have this string in cell B2.  Modify when a new template version is needed.*/
$TemplateVersions = array('Wheat' => '1Jul13',
  'Barley' => '11Dec14',
  'Oat' => '1Apr2017');
$cnt = 0;  // Count of errors

function die_nice($message = "") {
    //Actually don't die at all yet, just show the error message.
    global $cnt;
    if ($cnt == 0) {
        echo "<h3>Errors</h3>";
    }
    $cnt++;
    echo "<b>$cnt:</b> $message<br>";
    return false;
}

/* Show more informative messages when we get invalid data. */
function errmsg($sql, $err) {
    global $mysqli;
    if (preg_match('/^Data truncated/', $err)) {
        // Undefined value for an enum type
        $pieces = preg_split("/'/", $err);
        $column = $pieces[1];
        $msg = "Unallowed value for field <b>$column</b>. ";
        // Only works for table line_records.  Could pass table name as parameter.
        $r = mysqli_query($mysqli, "describe line_records $column");
        $columninfo = mysqli_fetch_row($r);
        $msg .= "Allowed values are: ".$columninfo[1];
        $msg .= "<br>Command: ".$sql."<br>";
        die_nice($msg);
    } elseif (preg_match('/^Duplicate entry/', $err)) {
        die_nice($err."<br>".$sql);
    } else {
        die_nice("MySQL error: ".$err."<br>The command was:<br>".$sql."<br>");
    }
}

/* Convert a line name to T3 required format. */
function t3ize($name) {
  $name = strtoupper($name);
  // If a <space> is between alpha and numeric, remove it.
  $name = preg_replace('/([A-Z]) ([0-9])/', '$1$2', $name);
  $name = preg_replace('/([0-9]) ([A-Z])/', '$1$2', $name);
  // Replace any other <space>s with "_";
  $name = preg_replace('/ /', '_', $name);
  return $name;
}

/* Shorthand MySQL query for a single value. Returns the value.
   Dies if there's more than one column in the result or a MySQL error. 
   No result is okay, returns empty string, ''.
*/
function pool_grab($querystring) {
  global $cnx;
  $query = $cnx->prepare($querystring);
  if ($query->execute()) {
    $vals = $query->fetch(PDO::FETCH_NUM);
    if (count($vals) > 1)
      $out = "<b>Programmer error</b>: Query result contains more than one column. How embarrassing!<br>";
  }
  else 
    $out = "Fatal mysql error in function pool_grab(). Query was:<br>$querystring";
  if (!empty($out)) 
    die($out);
  else
    return $vals[0];
}

new LineNames_Check($_GET['function']);

class LineNames_Check {
    // Using the class's constructor to decide which action to perform
    public function __construct($function = null)
    {
        switch ($function) {
            case 'typeDatabase':
                $this->type_Database(); /* update database */
                break;
            default:
                $this->typeLineNameCheck(); /* intial case*/
                break;
        }
    }

    private function typeLineNameCheck()
    {
        global $config;
        include $config['root_dir'] . 'theme/admin_header.php';
        echo "<h2>Line information: Validation</h2>";
        $this->type_Line_Name();
        $footer_div = 1;
        include $config['root_dir'].'theme/footer.php';
    }

    private function type_Line_Name()
    {
        global $TemplateVersions;
        global $cnt;
        global $mysqli;
	global $cnx;
?>

<script type="text/javascript">
  function update_database(filepath, filename, username) {
  var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabase&linedata=' + filepath + '&file_name=' + filename + '&user_name=' + username;
  // Opens the url in the same window
  window.open(url, "_self");
  }
</script>

<style type="text/css">
  h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
  th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
  table {background: none; border-collapse: collapse}
  td {border: 1px solid #eee !important;}
  table.marker {background: none; border-collapse: collapse}
  th.marker { background: #5b53a6; color: #fff; padding: 2px 0; border: 1px solid; border-color: white }
  td.marker { padding: 2px 0; border: 0 !important; }
</style>

    <?php
    $row = loadUser($_SESSION['username']);
    $username=$row['name'];

$query = $cnx->prepare("select max(GID) from germplsm");
//$query->bindValue(":gid",$GID);
$query->execute() OR die("Fatal query error.");
$maxgid = $query->fetch(PDO::FETCH_BOTH);
$nextgid = $maxgid[0] + 1;
print "Next available GID is <b>$nextgid</b>.<p>";

    if ($_FILES['file']['name'] == "") {
        error(1, "No File Uploaded");
        print "<input type='Button' value='Return' onClick='history.go(-1); return;'>";
    } else {
        //$tmp_dir="uploads/tmpdir_".$username."_".rand();
        $tmp_dir="uploads/".str_replace(' ', '_', $username)."_".date('yMd_G:i');
        umask(0);
        if (!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0777);
        }
        $target_path=$tmp_dir."/";
        $uploadfile=$_FILES['file']['name'];
        $uftype=$_FILES['file']['type'];
        //if (strpos($uploadfile, ".xls") === FALSE) {
        if (preg_match('/\.xls$/', $uploadfile) == 0) {
            error(1, "Only xls format is accepted. <br>");
            print "<input type='Button' value='Return' onClick='history.go(-1); return;'>";
        } else {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path.$uploadfile)) {
                /* Read Excel worksheet 0 into $linedata[]. */
                $datafile = $target_path.$uploadfile;
                $reader = new Spreadsheet_Excel_Reader();
                $reader->setOutputEncoding('CP1251');
                $reader->read($datafile);
                $linedata = $reader->sheets[0];
                $cols = $reader->sheets[0]['numCols'];
                $rows = $reader->sheets[0]['numRows'];
                // Read the Template Version and check it.
                $crop = $linedata['cells'][3][2];
                if (!in_array($crop, array_keys($TemplateVersions))) {
                    $croplist = implode(", ", array_keys($TemplateVersions));
                    die("Cell B3: Crop must be one of <b>$croplist</b>.");
                }
                if ($linedata['cells'][2][2] != $TemplateVersions[$crop]) {
                    die("Incorrect Submission Form version for POOL.  Cell B2 must say \"" .$TemplateVersions[$crop]. "\".");
                }

                // Lookup all the Breeding Programs in the database.
                $sql = mysqli_query($mysqli, "SELECT distinct data_program_code from CAPdata_programs") or errmsg($sql, mysqli_error($mysqli));
                while ($row = mysqli_fetch_row($sql)) {
                    $bpcodes[] = $row[0];
                }

        /* The following code allows the curator to put the columns in any order. */
	// These are the standard columns. -1 means required, -2 means optional.
		$columnOffsets = array('action' => -1,
			       'preferred_name' => -1,
			       'other_name' => -2,
			       'reference_for_other_name' => -2,
			       'naming_date' => -2,
			       'species' => -1,
			       'parent1' => -2,
			       'parent2' => -2,
			       'cross_date' => -2,
			       'origin_location' => -2,
			       'breeding_program' => -1,
			       'generation' => -1,
			       'reference' => -2,
			       'comments' => -2 );

	// First, locate the header line and read it into $header[].
	$header = array();
	for ($irow = 1; $irow <=$rows; $irow++) {
	  $teststr= addcslashes(trim($linedata['cells'][$irow][1]),"\0..\37!@\177..\377");
	  if (!empty($teststr) AND strtolower($teststr) == "*action") {
	    $firstline = $irow;
	    // Read in the header line.
	    for ($icol = 1; $icol <= $cols; $icol++) {
	      $value = addcslashes(trim($linedata['cells'][$irow][$icol]),"\0..\37!@\177..\377");
	      $header[] = $value;
	    }
	  }
	}
	if (!$firstline)  
	  die("The header row must begin with '*Action'.");

	/* Attempt to find each required column */
	foreach ($header as $columnOffset => $columnName) { // Loop through the columns in the header row.
	  //Clean up column name so that it can be matched.
	  $columnName = strtolower($columnName);
	  $order = array("\n","\t"," ");
	  $replace = array(" ",'','');
	  $columnName = str_replace($order, $replace, $columnName);
	  // Determine the column offset of "*Action".
	  if (preg_match('/^\s*\*action\s*$/is', trim($columnName)))
	    $columnOffsets['action'] = $columnOffset+1;
	  // Determine the column offset of "*Preferred Name".
	  if (preg_match('/^\s*\*preferredname\s*$/is', trim($columnName)))
	    $columnOffsets['preferred_name'] = $columnOffset+1;
	  // Determine the column offset of "Other Name".
	  else if (preg_match('/^\s*othername\s*$/is', trim($columnName)))
	    $columnOffsets['othername'] = $columnOffset+1;
	  // Determine the column offset of "Reference for Other Name".
	  else if (preg_match('/^\s*referenceforothername\s*$/is', trim($columnName)))
	    $columnOffsets['refother'] = $columnOffset+1;
	  // Determine the column offset of "Naming Date".
	  else if (preg_match('/^\s*namingdate\s*$/is', trim($columnName)))
	    $columnOffsets['namingdate'] = $columnOffset+1;
	  // Determine the column offset of "Naming Location".
	  else if (preg_match('/^\s*naminglocation\s*$/is', trim($columnName)))
	    $columnOffsets['naminglocation'] = $columnOffset+1;
	  // Determine the column offset of "*Species".
	  else if (preg_match('/^\s*\*species\s*$/is', trim($columnName))) {
	    $columnOffsets['species'] = $columnOffset+1;
	    // Species is also a Genetic Character.
	    // Get this property's allowed values in T3/Oat.
	    $pr = "Species";
	    $propuid = mysql_grab("select properties_uid from properties where name = '$pr'");
	    $sql = "select value from property_values where property_uid = $propuid";
	    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli)."<br>Query was:<br>".$sql);
	    while ($r = mysqli_fetch_row($res)) 
	      $allowedvals[$pr][] = $r[0];
	    $columnOffsets[$pr] = $columnOffset+1;
	    $ourprops[] = $pr;
	  }
	  // Determine the column offset of "Parent1".
	  else if (preg_match('/^\s*parent1\s*$/is', trim($columnName)))
	    $columnOffsets['parent1'] = $columnOffset+1;
	  // Determine the column offset of "Parent2".
	  else if (preg_match('/^\s*parent2\s*$/is', trim($columnName)))
	    $columnOffsets['parent2'] = $columnOffset+1;
	  // Determine the column offset of "Cross Date".
	  else if (preg_match('/^\s*crossdate\s*$/is', trim($columnName)))
	    $columnOffsets['crossdate'] = $columnOffset+1;
	  // Determine the column offset of "Origin Location".
	  else if (preg_match('/^\s*originlocation\s*$/is', trim($columnName)))
	    $columnOffsets['originlocation'] = $columnOffset+1;
	  // Determine the column offset of "*Breeding Program".
	  else if (preg_match('/^\s*\*breedingprogram\s*$/is', trim($columnName)))
	    $columnOffsets['breeding_program'] = $columnOffset+1;
	  // Determine the column offset of "*Filial Generation".
	  else if (preg_match('/^\s*\*filialgeneration\s*$/is', trim($columnName)))
	    $columnOffsets['generation'] = $columnOffset+1;
	  // Determine the column offset of "Reference".
	  else if (preg_match('/^\s*reference\s*$/is', trim($columnName)))
	    $columnOffsets['reference'] = $columnOffset+1;
	  // Determine the column offset of "Comments".
	  else if (preg_match('/^\s*comments\s*$/is', trim($columnName)))
	    $columnOffsets['comments'] = $columnOffset+1;
	} // end foreach($header as $columnOffset => $columnName)

	/* Check if any required columns weren't found. */
	$reqd = array_keys($columnOffsets, -1);
	$ignores = array();
	$reqd = array_diff($reqd, $ignores);
	if (!empty($reqd)) {
	  foreach ($reqd as $r) 
	    echo "Column '$r' was not found. Please don't change the column labels in the header row.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	}

	// Initialize searching for lines that are new vs. to be updated.
	$line_inserts_str = "";
	$line_uid = "";
	$line_uids = "";
	$line_uids_multiple = "";
	$lines_seen = array();
	$syns_seen = array();
	$line_inserts = array();
	$gids = array();
	$demotionmsg = "<p>";

	// Read in the data cells and validate.
	for ($irow = $firstline+1; $irow <= $rows; $irow++)  {
	  //Ignore rows with empty second cell (Preferred Name).
	  if (!empty($linedata['cells'][$irow][2])) {
	    // Action
	    $action = strtolower(trim($linedata['cells'][$irow][$columnOffsets['action']]));
	    if ($action != 'ignore') {
	      // Preferred Name
	      $line = trim($linedata['cells'][$irow][$columnOffsets['preferred_name']]);
	      $preferredname = $line;
	      if (strlen($line) < 3)  echo "Warning: '$line' is a short name and may not be unique in T3.<br>";
	      // 8-bit ASCII characters break us in nasty ways.
	      if (preg_match("/[\x80-\xff]/" , $line) == 1) 
		die_nice("Line name '$line' contains an 8-bit character code, possibly invisible.");
	      // Format the name according to T3's requirements.
	      $line = t3ize($line);
	      // Other Name
	      $othername = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['othername']]),"\0..\37!@\177..\377");
	      // Reference for Other Name
	      $refother = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['refother']]),"\0..\37!@\177..\377");
	      // Naming Date
	      $namingdate = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['namingdate']]),"\0..\37!@\177..\377");
	      // Naming Location
	      $naminglocation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['naminglocation']]),"\0..\37!@\177..\377");
	      // Species
	      $species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
	      if ($species) {
		// Lookup the T3 name for the species.
		$t3species = array('Avena sativa L.' => 'sativa',
				   'Avena fatua L.' => 'fatua',
				   'Avena hybrid' => 'hybrid',
				   'Avena nuda L.' => 'nuda',
				   'Avena sterilis L.' => 'sterilis',
				   'Avena strigosa Schreb.' => 'strigosa');
		$poolspecies = $species;
		$species = $t3species[$species];
		// Test for allowed value.
		$pr = 'Species';
		if (!empty($poolspecies) AND !in_array($species, $allowedvals[$pr])) {
		  $alllist = implode(",", $allowedvals[$pr]);
		  die_nice("Row $irow: Allowed values of property $pr are only <b>$alllist</b>.");
		}
	      }
	      // Parent 1
	      $parent1 = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['parent1']]),"\0..\37!@\177..\377");
	      // Parent 2
	      $parent2 = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['parent2']]),"\0..\37!@\177..\377");
	      // Cross Date
	      $crossdate = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['crossdate']]),"\0..\37!@\177..\377");
	      // Origin Location
	      $originlocation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['originlocation']]),"\0..\37!@\177..\377");
	      // Breeding Program
	      $mybp = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['breeding_program']]),"\0..\37!@\177..\377");
	      // Filial Generation
	      $generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
	      // Reference
	      $reference = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['reference']]),"\0..\37!@\177..\377");
	      // Comments
	      $comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");

	      /* Validations */
	      if (!empty($line)) {
		// Validate Line Name in T3.
		// Check if line is in T3, as either a line name or synonym.
		$lid = mysql_grab("select line_record_uid from line_records where line_record_name = '$line'");
		if (!$lid) // If not a primary name, check for synonym.
		  $lid = mysql_grab("select distinct line_record_name from line_synonyms ls, line_records lr where line_synonym_name = '$line' and ls.line_record_uid = lr.line_record_uid");
		// Note: $line_uid is an array.
		$line_uid = array($lid);
		if (!$lid) {
		  if ($action == 'edit')
		    $warnings .= "Row $irow: $line is new to T3 and will be added.<br>";
		  // Required fields $species, $bp, $generation
		  // New, apr2017: Required fields are only required for new lines that aren't already in T3.
		  if (empty($species)) 
		    die_nice("Row $irow, Line $line: Species is required.");
		  if ( (in_array($mybp, $bpcodes) == FALSE) OR (strlen($mybp) == 0) ) 
		    die_nice("Line $line: Breeding Program <b>'$mybp'</b> is not in the database. <a href=\"".$config['base_url']."all_breed_css.php\">Show codes.</a>");
		  if ( (empty($generation)) OR ($generation != (int)$generation) OR ($generation < 1) OR ($generation > 9) )
		      die_nice("$line: Filial Generation (1-9) is required.");
		  // Mark new line for insertion into database.
		  $line_inserts[] = $line;
		  $line_inserts_str = implode("\t",$line_inserts);
		}
		elseif (count($line_uid) == 1) { 
		  // If it's listed as a synonym, don't make it a line name too.
		  $sql = "select distinct line_record_name from line_synonyms ls, line_records lr
		    where line_synonym_name = '$line' and ls.line_record_uid = lr.line_record_uid";
		  $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
		  if (mysqli_num_rows($res) > 0) {
		    $rn = mysqli_fetch_row($res);
		    $realname = $rn[0];
		    // It's okay for a synonym to be the same as the name except for UPPER/Mixed case.
		    if ($realname != $line)
                {
                // 30may18: Do we have to die? Can we just use $realname instead? Let's try it.
                /* die_nice("Line Name $line is a synonym for $realname in T3. Please use $realname instead."); */
                }
		  }
		  else {
		    // Mark the line record for updating.
		    $line_uids[] = implode(",",$line_uid);
		    // What??! $line_uids is a string, not an array.  And it contains exactly 1 uid.
		    if ($action == 'add')
		      $warnings .= "Row $irow: $line already exists in T3 and will be edited.<br>";
		  }
		}

		// Is this Preferred Name already in POOL? Case-sensitive.
		$query = $cnx->prepare("select GID from names where NVAL = BINARY :nm and NSTAT = 1");
		$query->bindvalue(":nm", $preferredname);
		$query->execute() OR die("Row $irow: Fatal query error.");   
		$res = $query->fetch(PDO::FETCH_BOTH);
		if ($res[0]) {
		  $gids[$preferredname] = $res[0];
		  $oldgids[] = $res[0];
		  if ($action == 'add')
		    $warnings .= "Row $irow: $preferredname already exists in POOL and will be edited.<br>";
		}
		// If Other Name was previously a Preferred Name, demote it for that GID.
		$renamegid = pool_grab("select GID from names where NVAL = BINARY '$othername' and NSTAT = 1");
		if ($renamegid) {
		  if (!$gids[$preferredname]) {
		    // It's a rename action.
		    if ($action != "rename")
		      die_nice("Row $irow: '$othername' exists in POOL as a Preferred Name, so this action must be 'rename'.");
		    $demotionmsg .= "Preferred Name '$othername', GID $renamegid, will be demoted to an Other Name for '$preferredname'.<br>";
		    // Note: If $preferredname already exists as a Preferred Name, retain its GID. Otherwise use $renamegid.
		    $gids[$preferredname] = $renamegid;
		  }
		  else {
		    // It's a merge action, Preferred Name already exists. Demote Other Name to Preferred Name's GID.
		    if ($preferredname != $othername) {
		      if ($action != "merge")
			die_nice("Row $irow: '$preferredname' and '$othername' both exist in POOL as Preferred Names, so this action must be 'merge'.");
		      $demotionmsg .= "Preferred Name '$othername', GID $renamegid, will be demoted to an Other Name for '$preferredname', GID <b>$gids[$preferredname]</b>.<br>";
		    }
		  }
		  $oldgids[] = $renamegid;
		}
		// Is it a brandnew name to be created?
		if (!$gids[$preferredname]) {
		  if ($action != 'add')
		    $warnings .= "Row $irow: $preferredname is new to POOL and will be added.<br>";
		  // If we haven't seen it before in this file, assign a new GID.
		  if (!in_array($preferredname, array_keys($gids))) {
		    $gids[$preferredname] = $nextgid;
		    $newgids[] = $nextgid;
		    $nextgid++;
		  }
		}

		// Validate the parents' names.
		if (!empty($parent1)) {
            $t3parent1 = t3ize($parent1);
            $lid = mysql_grab("select line_record_uid from line_records where line_record_name = '$t3parent1'");
            if (!$lid)
                // Is the name present as a Synonym?
                $lid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$t3parent1'");
            // Not in T3 and also not already seen in this file as a line to add?
            if (!$lid) 
                // UNKNOWN is a special case.  Ignore it.
                if (!in_array($t3parent1, $line_inserts) AND $t3parent1 != 'UNKNOWN')
                    die_nice("Row $irow: Parent $t3parent1 is not in T3.");
            $query = $cnx->prepare("select GID from names where NVAL = BINARY :nm and NSTAT = 1");
            $query->bindvalue(":nm", $parent1);
            $query->execute() OR die("Row $irow: Fatal query error.");   
            $res = $query->fetch(PDO::FETCH_BOTH);
            if (!$res[0])
                // Not in POOL and also not already seen in this file?
                if (!in_array($parent1, array_keys($gids)))
                    die_nice("Row $irow: Parent $parent1 is not in POOL as a Preferred Name.");
		}
		if (!empty($parent2)) {
            $t3parent2 = t3ize($parent2);
            $lid = mysql_grab("select line_record_uid from line_records where line_record_name = '$t3parent2'");
            if (!$lid)
                // Is the name present as a Synonym?
                $lid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$t3parent2'");
		    // Not in T3 and also not already seen in this file as a line to add?
            if (!$lid)
                if (!in_array($t3parent2, $line_inserts) AND $t3parent2 != 'UNKNOWN')
                    die_nice("Row $irow: Parent $t3parent2 is not in T3.");
            $query = $cnx->prepare("select GID from names where NVAL = BINARY :nm and NSTAT = 1");
            $query->bindvalue(":nm", $parent2);
            $query->execute() OR die("Row $irow: Fatal query error.");   
            $res = $query->fetch(PDO::FETCH_BOTH);
            if (!$res[0]) 
                // Not in POOL and also not already seen in this file?
                if (!in_array($parent2, array_keys($gids)))
                    die_nice("Row $irow: Parent $parent2 is not in POOL as a Preferred Name.");
            // 25may2019: Allow par1 = par2 for Charlene, e.g. for selections
            /* if ($t3parent1 == $t3parent2) */
            /*     die_nice("Row $irow: Parents 1 and 2 are the same."); */
		}
		// Validate references, $refother and $reference.
		if (!empty($refother)) {
		  $query = $cnx->prepare("select REFID from BIBREFS where ANALYT like :refname");
		  $query->bindvalue(":refname", $refother);
		  $query->execute() OR die("Row $irow: Fatal query error.");   
		  $res = $query->fetch(PDO::FETCH_BOTH);
		  if (!$res[0]) 
		    die_nice("Row $irow: Reference '$refother' is not in POOL.");
		  /* NOTE: if $refother = "PGRC, Canada", set names.link = 1. $othername must be "CN ...". */
		  if ($refother == 'PGRC, Canada') {
		    if (preg_match("/^CN /", $othername) == 0)
		      die_nice("Row $irow: Other Name must begin with 'CN ' if reference is 'PGRC, Canada'.");
		  }
		}
		if (!empty($reference)) {
		  $query = $cnx->prepare("select REFID from BIBREFS where ANALYT like :refname");
		  $query->bindvalue(":refname", $reference);
		  $query->execute() OR die("Row $irow: Fatal query error.");
		  $res = $query->fetch(PDO::FETCH_BOTH);
		  if (!$res[0]) 
		    die_nice("Row $irow: Reference '$reference' is not in POOL.");
		}
		// Validate Locations, $naminglocation and $originlocation.
		if (!empty($naminglocation)) {
		  $query = $cnx->prepare("select LOCID from LOCATION where LNAME like :loc");
		  $query->bindvalue(":loc", $naminglocation);
		  $query->execute() OR die("Row $irow: Fatal query error.");
		  $res = $query->fetch(PDO::FETCH_BOTH);
		  if (!$res[0]) 
		    die_nice("Row $irow: Location '$naminglocation' is not in POOL.");
		}
		if (!empty($originlocation)) {
		  $query = $cnx->prepare("select LOCID from LOCATION where LNAME like :loc");
		  $query->bindvalue(":loc", $originlocation);
		  $query->execute() OR die("Row $irow: Fatal query error.");
		  $res = $query->fetch(PDO::FETCH_BOTH);
		  if (!$res[0]) 
		    die_nice("Row $irow: Location '$originlocation' is not in POOL.");
		}
		if (!empty($namingdate)) {
		  if (!is_numeric($namingdate))
		    die_nice("Row $irow: Naming Date must be an integer.");
		}
		if (!empty($crossdate)) {
		  if (!is_numeric($crossdate))
		    die_nice("Row $irow: Cross Date must be an integer.");
		}
		// Validate $poolspecies.
		if (!empty($poolspecies)) {
		  $taxid = pool_grab("select TaxID from T_Taxonomy where TaxName = '$poolspecies'");
		  if (!$taxid)
		    die_nice("Row $irow: Species '$poolspecies' doesn't exist in POOL.");
		}
		// Validate Comments.
		if (!empty($comments)) {
		  $oldt3 = mysql_grab("select description from line_records where line_record_name = '$line'");
		  if (!empty($oldt3) AND $oldt3 != $comments)
		    $warnings .= "Row $irow: T3 Description will be overwritten, '$oldt3'.<br>";
		}
	      } /* end of if (!empty($line)) */
	    } /* end of if($action!='ignore') */
	  } /* end of if empty second cell */
	} /* end of for ($irow) */

	if (($line_uids) != "") {
	  // $line_uids is a string containing 1 uid.  
	  $line_updates =implode(",",$line_uids);
	  // Get line names.
	  $line_sql = mysqli_query($mysqli, "SELECT line_record_name as name
                        FROM line_records
                        WHERE line_record_uid IN ($line_updates)") 
	    or errmsg($sql, mysqli_error($mysqli));
	  while ($row = mysqli_fetch_array($line_sql, MYSQLI_ASSOC)) {
	    $line_update_names[] = $row["name"];
	  }
	  $line_update_data = $line_update_names;
	}
	else $line_update_data = array();
	$line_insert_data = explode("\t",$line_inserts_str);
	// $line_insert_data is a string containing a single line name.  

	if (!empty($warnings)) 
	  print "<h3>Warning</h3>".$warnings;
	print $demotionmsg;
	// If any errors, show what we read and stop.
	if ($cnt != 0) 
	  print "<h3>We saw the following data in the uploaded file.</h3>";
	else
	  print "<h3>The file is read as follows.</h3>\n";

	?>
	<table style="table-layout:fixed;"><tr>
	   <th style='width: 50px;' class='marker'>GID</th>
	   <th style='width: 100px;' class='marker'>Preferred Name</th>
	   <th style='width: 100px;' class='marker'>Other Name</th>
	   <th style='width: 90px;' class='marker'>Ref for Other Name</th>
	   <th style='width: 42px;' class='marker'>Date</th>
	   <th style='width: 64px;' class='marker'>Location</th>
	   <th style='width: 53px;' class='marker'>Species</th>
	   <th style='width: 98px;' class='marker'>Parent 1</th>
	   <th style='width: 98px;' class='marker'>Parent 2</th>
	   <th style='width: 42px;' class='marker'>Cross Date</th>
	   <th style='width: 64px;' class='marker'>Location</th>
	   <th style='width: 40px;' class='marker'>Breeding Program</th>
	   <th style='width: 40px;' class='marker'>Gener ation</th>
	   <th style='width: 90px;' class='marker'>Reference</th>
	   <th style='width: 156px;' class='marker'>Comments</th>
	  </tr>
	</table>
	
	<div id="test" style="padding: 0; height: 300px; width: 1155px;  overflow: scroll;border: 1px solid #5b53a6;">
	  <!-- break-word doesnt work unless table width is set. -->
	  <table style="table-layout:fixed; width: 940px; word-wrap: break-word">
	    <?php
	       //Extract and display data.
	       for ($irow = $firstline+1; $irow <=$rows; $irow++)  {
	      $preferredname = trim($linedata['cells'][$irow][$columnOffsets['preferred_name']]);
	      if (!empty($preferredname)) {
	      print "<tr><td style='width: 35px; text-align: right'><b>$gids[$preferredname]</b>";
	      print "<td style='width: 85px;'>$preferredname";
	      $othername = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['othername']]),"\0..\37!@\177..\377");
	      print "<td style='width: 85px;'>$othername";
	      $refother = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['refother']]),"\0..\37!@\177..\377");
	      print "<td style='width: 77px;'>$refother";
	      $namingdate = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['namingdate']]),"\0..\37!@\177..\377");
	      print "<td style='width: 28px;'>$namingdate";
	      $naminglocation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['naminglocation']]),"\0..\37!@\177..\377");
	      $excerpt = substr($naminglocation, 0, 50);
	      if ($excerpt != $naminglocation)
		$naminglocation = $excerpt . "...";
	      print "<td style='width: 51px;'>$naminglocation";
	      $species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
	      print "<td style='width: 38px;'>$t3species[$species]";
	      $parent1 = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['parent1']]),"\0..\37!@\177..\377");
	      print "<td style='width: 85px;'>$parent1";
	      $parent2 = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['parent2']]),"\0..\37!@\177..\377");
	      print "<td style='width: 85px;'>$parent2";
	      $crossdate = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['crossdate']]),"\0..\37!@\177..\377");
	      print "<td style='width: 28px;'>$crossdate";
	      $originlocation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['originlocation']]),"\0..\37!@\177..\377");
	      $excerpt = substr($originlocation, 0, 50);
	      if ($excerpt != $originlocation)
		$originlocation = $excerpt . "...";
	      print "<td style='width: 50px;'>$originlocation";
	      $breedprog = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['breeding_program']]),"\0..\37!@\177..\377");
	      print "<td style='width: 36px; text-align: center'>$breedprog";
	      $generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
	      print "<td style='width: 27px; text-align: center'>$generation";
	      $reference = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['reference']]),"\0..\37!@\177..\377");
	      print "<td style='width: 77px;'>$reference";
	      $comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");
	      $excerpt = substr($comments, 0, 100);
	      if ($excerpt != $comments)
		$comments = $excerpt . "...";
	      print "<td style='width: 140px;'>$comments</tr>";
	    } /* end of if (!empty($line)) */
	    } /* end of for loop */
	print "</table></div>";
	if ($cnt != 0) {
	      echo "<p>Please fix these errors and try again.<br/><br/>";
	      exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	    }
	else {
	      // $cnt == 0, validated
	      $lid = count($line_insert_data);
	      $lud = count($line_update_data);
	      ?>
   
	       <h3>The following lines will be added or edited in T3/Oat.</h3>
	       Please verify that<br>
	       1. The lines to be added are new ones and aren&apos;t already in the 
	       database, e.g. under a variant spelling.<br>
	       2. The lines to be edited are ones you wish to change the existing data for.<br>
	    <p>
	      <table><tr><td>
		    <table >
		      <tr>
			<th style="width: 140px;" class="marker">Lines to Add: <?php echo "$lid" ?></th>
			<th style="width: 150px;" class="marker" >Lines to Edit: <?php echo "$lud" ?></th>
		      </tr>
		    </table>
		    <div id="test" style="padding: 0; height: 200px; width: 290px;  overflow: scroll;border: 1px solid #5b53a6;">
		      <table>
			
			<?php
	       for ($i = 0; $i < max($lid, $lud); $i++) {
		 print "<tr><td style='width: 130px;'>$line_insert_data[$i]";
		 if($line_update_data !="") 
		   print "<td style='width: 160px;'>$line_update_data[$i]";
		 else 
		   print "<td style='width: 160px;'>No Updates";
	       }
	      ?>
	      </table>
		  </div>
		  </td>
		  <td style="width: 250px; text-align: left; vertical-align: top;">
		  <h3>Editing lines</h3>
		  To add or change information about a line, edit the file 
		  and reload, or load a new one.  Empty cells and unchanged 
		  cells will have no effect.  Cells with content will <b>replace</b>
		  the existing values. 
		  <P>Alternatively you can use the 
		  <a href="<?php echo $config['base_url'] ?>login/edit_line.php">
		  Edit Lines</a> form.
		  </td>
		  </tr></table>

		  <input type="Button" value="Accept" onclick="javascript: update_database('<?php echo $datafile?>','<?php echo $uploadfile ?>','<?php echo $username?>' )"/>
		  <input type="Button" value="Cancel" onclick="history.go(-1); return;"/>

		  <?php	
		  } /* end of $cnt == 0, validated */
	} /* end of if(move_uploaded_file) */
	    else 
	      error(1,"There was an error uploading the file, please try again!");
    } /* end of if .xls file */
    } /* end of if a file was uploaded */
} /* end of function type_Line_Name */
	
    /* Validation completed, now load the database. */
  private function type_Database() {
    global $config;
    global $mysqli;
    global $cnx;
    global $cnt;
    include $config['root_dir'] . 'theme/admin_header.php';
    $datafile = $_GET['linedata'];
    $filename = $_GET['file_name'];
    $username = $_GET['user_name'];

    $maxgid = pool_grab("select max(GID) from germplsm");
    $nextgid = $maxgid + 1;
	
    $reader = new Spreadsheet_Excel_Reader();
    $reader->setOutputEncoding('CP1251');
    $reader->read($datafile);
    $linedata = $reader->sheets[0];
    $cols = $reader->sheets[0]['numCols'];
    $rows = $reader->sheets[0]['numRows'];
	
    // First, locate the header line and read it into $header[].
    $header = array();
    for ($irow = 1; $irow <= $rows; $irow++) {
      $teststr= addcslashes(trim($linedata['cells'][$irow][1]),"\0..\37!@\177..\377");
      if (!empty($teststr) AND strtolower($teststr) == "*action") {
	$firstline = $irow;
	// Read in the header line.
	for ($icol = 1; $icol <= $cols; $icol++) {
	  $value = addcslashes(trim($linedata['cells'][$irow][$icol]),"\0..\37!@\177..\377");
	  $header[] = $value;
	}
      }
    }

    // Now read in the data cells.		
    foreach($header as $columnOffset => $columnName) { // Loop through the columns in the header row.
      // Require exact match for Property names.
      //Clean up column name so that it can be matched.
      $columnName = strtolower($columnName);
      $order = array("\n","\t"," ");
      $replace = array(" ",'','');
      $columnName = str_replace($order, $replace, $columnName);
      // Determine the column offset of "*Action".
      if (preg_match('/^\s*\*action\s*$/is', trim($columnName)))
	$columnOffsets['action'] = $columnOffset+1;
      // Determine the column offset of "*Preferred Name".
      if (preg_match('/^\s*\*preferredname\s*$/is', trim($columnName)))
	$columnOffsets['preferred_name'] = $columnOffset+1;
      // Determine the column offset of "Other Name".
      else if (preg_match('/^\s*othername\s*$/is', trim($columnName)))
	$columnOffsets['othername'] = $columnOffset+1;
      // Determine the column offset of "Reference for Other Name".
      else if (preg_match('/^\s*referenceforothername\s*$/is', trim($columnName)))
	$columnOffsets['refother'] = $columnOffset+1;
      // Determine the column offset of "Naming Date".
      else if (preg_match('/^\s*namingdate\s*$/is', trim($columnName)))
	$columnOffsets['namingdate'] = $columnOffset+1;
      // Determine the column offset of "Naming Location".
      else if (preg_match('/^\s*naminglocation\s*$/is', trim($columnName)))
	$columnOffsets['naminglocation'] = $columnOffset+1;
      // Determine the column offset of "*Species".
      else if (preg_match('/^\s*\*species\s*$/is', trim($columnName))) 
	$columnOffsets['species'] = $columnOffset+1;
      // Determine the column offset of "Parent1".
      else if (preg_match('/^\s*parent1\s*$/is', trim($columnName)))
	$columnOffsets['parent1'] = $columnOffset+1;
      // Determine the column offset of "Parent2".
      else if (preg_match('/^\s*parent2\s*$/is', trim($columnName)))
	$columnOffsets['parent2'] = $columnOffset+1;
      // Determine the column offset of "Cross Date".
      else if (preg_match('/^\s*crossdate\s*$/is', trim($columnName)))
	$columnOffsets['crossdate'] = $columnOffset+1;
      // Determine the column offset of "Origin Location".
      else if (preg_match('/^\s*originlocation\s*$/is', trim($columnName)))
	$columnOffsets['originlocation'] = $columnOffset+1;
      // Determine the column offset of "*Breeding Program".
      else if (preg_match('/^\s*\*breedingprogram\s*$/is', trim($columnName)))
	$columnOffsets['breeding_program'] = $columnOffset+1;
      // Determine the column offset of "*Filial Generation".
      else if (preg_match('/^\s*\*filialgeneration\s*$/is', trim($columnName)))
	$columnOffsets['generation'] = $columnOffset+1;
      // Determine the column offset of "Reference".
      else if (preg_match('/^\s*reference\s*$/is', trim($columnName)))
	$columnOffsets['reference'] = $columnOffset+1;
      // Determine the column offset of "Comments".
      else if (preg_match('/^\s*comments\s*$/is', trim($columnName)))
	$columnOffsets['comments'] = $columnOffset+1;
    } // end foreach($header as $columnOffset => $columnName)

      // Read in the data cells.
    for ($irow = $firstline+1; $irow <= $rows; $irow++)  {
      //Ignore rows with empty second cell, Preferred Name.
      if (!empty($linedata['cells'][$irow][2])) {
	// Action
	$action = strtolower(trim($linedata['cells'][$irow][$columnOffsets['action']]));
	if ($action != 'ignore') {
	  // Preferred Name
	  $line = trim($linedata['cells'][$irow][$columnOffsets['preferred_name']]);
	  $preferredname = $line;
	  // Format the name according to T3's requirements.
	  $line = t3ize($line);
	  // Other Name
	  $othername = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['othername']]),"\0..\37!@\177..\377");
	  // Reference for Other Name
	  $refother = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['refother']]),"\0..\37!@\177..\377");
	  // Naming Date
	  $namingdate = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['namingdate']]),"\0..\37!@\177..\377");
	  // Naming Location
	  $naminglocation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['naminglocation']]),"\0..\37!@\177..\377");
	  // Species
	  $species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
	  // Species is also a Genetic Character.
	  // Get this property's allowed values in T3/Oat.
	  $pr = "Species";
	  $propuid = mysql_grab("select properties_uid from properties where name = '$pr'");
	  $sql = "select value from property_values where property_uid = $propuid";
	  $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli)."<br>Query was:<br>".$sql);
	  while ($r = mysqli_fetch_row($res)) 
	    $allowedvals[$pr][] = $r[0];
	  $columnOffsets[$pr] = $columnOffset+1;
	  $ourprops[] = $pr;
	  // Lookup the T3 name for the species.
	  $t3species = array('Avena sativa L.' => 'sativa',
			     'Avena fatua L.' => 'fatua',
			     'Avena hybrid' => 'hybrid',
			     'Avena nuda L.' => 'nuda',
			     'Avena sterilis L.' => 'sterilis',
			     'Avena strigosa Schreb.' => 'strigosa');
	  $poolspecies = $species;
	  $species = $t3species[$species];
	  // Parent 1
	  $parent1 = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['parent1']]),"\0..\37!@\177..\377");
	  // Parent 2
	  $parent2 = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['parent2']]),"\0..\37!@\177..\377");
	  // Cross Date
	  $crossdate = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['crossdate']]),"\0..\37!@\177..\377");
	  // Origin Location
	  $originlocation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['originlocation']]),"\0..\37!@\177..\377");
	  // Breeding Program
	  $mybp = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['breeding_program']]),"\0..\37!@\177..\377");
	  // Filial Generation
	  $generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
	  // Reference
	  $reference = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['reference']]),"\0..\37!@\177..\377");
	  // Comments
	  $comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");

	  /* Write to the databases. First, T3: */
	  $line_uid = mysql_grab("select line_record_uid from line_records where line_record_name = '$line'");
	  if (!$line_uid) {
          // 1jun18: Check first if $line is a synonym, and if so use that $line_uid.
          $line_uid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$line'");
          if (!$line_uid) {
              // It's a new line. Insert it into T3.
              $sql_beg = "INSERT INTO line_records (line_record_name,";
              $sql_mid = "updated_on, created_on) VALUES('$line', ";
              $sql_end = "NOW(),NOW())";
              if (!empty($mybp)) {
                  $sql_beg .= "breeding_program_code,";
                  $mybp = mysqli_real_escape_string($mysqli, $mybp);
                  $sql_mid .= "'$mybp', ";
              }
              // For numbers, "0" is empty.
              if (isset($generation) AND $generation != "") {
                  $sql_beg .= "generation,";
                  $generation = mysqli_real_escape_string($mysqli, $generation);
                  $sql_mid .= "'$generation', ";
              }
              if (!empty($comments)) {
                  $sql_beg .= "description,";
                  $comments = mysqli_real_escape_string($mysqli, $comments);
                  $sql_mid .= "'$comments', ";
              }
              $sql = $sql_beg.$sql_mid.$sql_end;
              $rlinsyn=mysqli_query($mysqli, $sql) or $linesuccess = errmsg($sql, mysqli_error($mysqli));
              $line_uid = mysqli_insert_id($mysqli);
              // $line_uid is no longer = NULL, cf. above, it's an int. So we can use it to load the other tables.
              if (!empty($species) AND !empty($line_uid)) {
                  // Insert property 'Species' value in table line_properties.
                  $species = mysqli_real_escape_string($mysqli, $species);
                  $propuid = mysql_grab("select properties_uid from properties where name = 'Species'");
                  $propvaluid = mysql_grab("select property_values_uid from property_values 
                                          where property_uid = $propuid and value = '$species'");
                  $sql = "insert into line_properties (line_record_uid, property_value_uid) values ($line_uid, $propvaluid)";
                  $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
              }
              if (!empty($parent1)) {
                  $parent1t3 = t3ize(mysqli_real_escape_string($mysqli, $parent1));
                  if ($parent1t3 != "UNKNOWN") {
                      $parent_uid = mysql_grab("select line_record_uid from line_records where line_record_name = '$parent1t3'");
                      // Accept Synonyms too.
                      if (!$parent_uid)
                          $parent_uid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$parent1t3'");
                      $sql = "insert into pedigree_relations (line_record_uid,parent_id,contribution,updated_on, created_on) values ($line_uid,$parent_uid,'0.5',NOW(),NOW())";
                      $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
                  }
              }
              if (!empty($parent2)) {
                  $parent2t3 = t3ize(mysqli_real_escape_string($mysqli, $parent2));
                  if ($parent2t3 != "UNKNOWN") {
                      $parent_uid = mysql_grab("select line_record_uid from line_records where line_record_name = '$parent2t3'");
                      if (!$parent_uid) 
                          $parent_uid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$parent2t3'");
                      $sql = "insert into pedigree_relations (line_record_uid,parent_id,contribution,updated_on, created_on) values ($line_uid,$parent_uid,'0.5',NOW(),NOW())";
                      $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
                  }
              }
          }
	  } // end of if (!$line_uid) 

	  elseif (count($line_uid) == 1) { 	      
	    // A record already exists for this Line.  Update T3.
	    $sql_beg = "update line_records set ";
	    $sql_mid = "";
	    $sql_end = "updated_on=NOW() where line_record_uid = '$line_uid'";
	    if (!empty($mybp)) {
	      $mybp = mysqli_real_escape_string($mysqli, $mybp);
	      $sql_mid .= "breeding_program_code='$mybp',";
	    }
	    // For numbers, 0 is empty.
	    if (isset($generation) AND $generation != "") {
	      $generation = mysqli_real_escape_string($mysqli, $generation);
	      $sql_mid .= "generation = '$generation',";
	    }
	    if (!empty($comments)) {
	      // Note: Any existing comments are replaced!
	      $comments = mysqli_real_escape_string($mysqli, $comments);
	      $sql_mid .= "description = '$comments',";
	    }
	    $sql = $sql_beg.$sql_mid.$sql_end;
	    $rlinsyn=mysqli_query($mysqli, $sql) or $linesuccess = errmsg($sql, mysqli_error($mysqli));
	    if (!empty($species)) {
	      // Update property 'Species' value in table line_properties.
	      $species = mysqli_real_escape_string($mysqli, $species);
	      $propuid = mysql_grab("select properties_uid from properties where name = 'Species'");
	      $propvaluid = mysql_grab("select property_values_uid from property_values 
                                          where property_uid = $propuid and value = '$species'");
	      // Find the existing record in line_properties to replace.
	      $linepropuid = mysql_grab("select line_properties_uid
			      from line_properties lp, property_values pv
			      where line_record_uid = $line_uid and property_uid = $propuid
			      and lp.property_value_uid = pv.property_values_uid");
	      $sql = "update line_properties set property_value_uid = $propvaluid where line_properties_uid = $linepropuid";
	      mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
	    }
	    // There may be two existing records for this line in table pedigree_relations. Replace them?
	    $parentage = array();
	    $sql = "select pedigree_relation_uid from pedigree_relations where line_record_uid = $line_uid";
	    $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
	    while ($r = mysqli_fetch_row($res)) 
	      $parentage[] = $r[0];
	    if (!empty($parent2)) {
	      $parent2t3 = t3ize(mysqli_real_escape_string($mysqli, $parent2));
	      if ($parent2t3 != "UNKNOWN") {
              $parent_uid = mysql_grab("select line_record_uid from line_records where line_record_name = '$parent2t3'");
              if (!$parent_uid)
                  // Accept Synonyms too.
                  $parent_uid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$parent2t3'");
              if (!empty($parentage[1]))
                  $sql = "update pedigree_relations set parent_id = $parent_uid, contribution = '0.5', updated_on = NOW() where pedigree_relation_uid = $parentage[1]";
              else
                  $sql = "insert into pedigree_relations (line_record_uid,parent_id,contribution,updated_on, created_on) values ($line_uid,$parent_uid,'0.5',NOW(),NOW())";
              $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
	      }
	    }
	    if (!empty($parent1)) {
	      $parent1t3 = t3ize(mysqli_real_escape_string($mysqli, $parent1));
	      if ($parent1t3 != "UNKNOWN") {
		$parent_uid = mysql_grab("select line_record_uid from line_records where line_record_name = '$parent1t3'");
        if (!$parent_uid) 
            $parent_uid = mysql_grab("select line_record_uid from line_synonyms where line_synonym_name = '$parent1t3'");
		if (!empty($parentage[0]))
		  $sql = "update pedigree_relations set parent_id = $parent_uid, contribution = '0.5', updated_on = NOW() where pedigree_relation_uid = $parentage[0]";
		else
		  $sql = "insert into pedigree_relations (line_record_uid,parent_id,contribution,updated_on, created_on) values ($line_uid,$parent_uid,'0.5',NOW(),NOW())";
		$res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
	      }
	    }
	  }  /* end of elseif (count($line_uid) == 1) */

	  /* Second, write to POOL. */
	  $GID = pool_grab("select GID from names where NVAL = BINARY '$preferredname' and NSTAT = 1");
	  if (!$GID) {
	    // It's a new line. Insert it into POOL.
	    $GID = $nextgid;
	    $nextgid++;
	    // Other Name
	    if (!empty($othername)) {
	      // Was this Other Name a previously existing Preferred Name to demote?
	      $renamegid = pool_grab("select GID from names where NVAL = BINARY '$othername' and NSTAT = 1");
	      if ($renamegid) {
		// Move all data associated with the old $renamegid to the new Preferred Name.
		// Leave the old GID in place; just change the name.
		$devnull = pool_grab("update germplsm set Name = '$preferredname' where GID = $renamegid");
		$devnull = pool_grab("update names set NVAL = '$preferredname' where GID = $renamegid and NSTAT = 1");
		// Use the old $renamegid for other data, rather than $nextgid.
		$GID = $renamegid;
		$nextgid--;
	      }
	      // Add another row to table 'names' for the Other Name.
	      $sql_beg = "INSERT INTO names (GID,NVAL,NSTAT,";
	      $sql_mid = ") VALUES($GID,'$othername',0,";
	      $sql_end = ")";
	      if (!empty($namingdate) OR $namingdate == '0') {
		$sql_beg .= "NDATE,";
		$sql_mid .= "$namingdate,";
	      }
	      if (!empty($refother)) {
		$refid = pool_grab("select REFID from BIBREFS where ANALYT = '$refother'");
		$sql_beg .= "NREF,";
		$sql_mid .= "$refid,";
		$sql_beg .= "link,";
		if ($refother == "PGRC, Canada" OR $refother == 'T3/Oat') 
		  // Make a Gene Bank Link to the PGRC website.
		  $sql_mid .= "1,";
		else
		  $sql_mid .= "0,";
	      }
	      if (!empty($naminglocation)) {
		$locid = pool_grab("select LOCID from LOCATION where LNAME = '$naminglocation'");
		$sql_beg .= "NLOCN,";
		$sql_mid .= "$locid,";
	      }
	      $sql_beg = rtrim($sql_beg, ",");
	      $sql_mid = rtrim($sql_mid, ",");
	      $sql = $sql_beg.$sql_mid.$sql_end;
	      $query = $cnx->prepare($sql);
	      $query->execute() OR die("Row $irow: Fatal error inserting into POOL table 'names'. Query was:<br>$sql");
	    } /* end of if($othername) */

	    /* Don't insert into germplsm or names if we just demoted an old name. */
	    if (!$renamegid) {
	      //table 'germplsm'
	      $sql_beg = "INSERT INTO germplsm (GID,Name,";
	      // UPDATE is a MySQL reserved word so must be quoted.
	      $sql_mid = "`UPDATE`,date) VALUES($GID,'$preferredname',";
	      $sql_end = "NOW(),NOW())";
	      if (!empty($parent1)) {
		$gpid1 = pool_grab("select GID from names where NVAL = BINARY '$parent1' AND NSTAT = 1");
		$sql_beg .= "GPID1,";
		$sql_mid .= "$gpid1,";
	      }
	      if (!empty($parent2)) {
		$gpid2 = pool_grab("select GID from names where NVAL = BINARY '$parent2' AND NSTAT = 1");
		$sql_beg .= "GPID2,";
		$sql_mid .= "$gpid2,";
	      }
	      if (!empty($crossdate) OR $crossdate == '0') {
		$sql_beg .= "GDATE,";
		$sql_mid .= "$crossdate,";
	      }
	      if (!empty($reference)) {
		$refid = pool_grab("select REFID from BIBREFS where ANALYT = '$reference'");
		$sql_beg .= "GREF,";
		$sql_mid .= "$refid,";
	      }
	      if (!empty($originlocation)) {
		$locid = pool_grab("select LOCID from LOCATION where LNAME = '$originlocation'");
		$sql_beg .= "GLOCN,";
		$sql_mid .= "$locid,";
	      }
	      if (!empty($poolspecies)) {
		$taxid = pool_grab("select TaxID from T_Taxonomy where TaxName = '$poolspecies'");
		$sql_beg .= "TaxID,";
		$sql_mid .= "$taxid,";
	      }
              $sql = $sql_beg.$sql_mid.$sql_end;
	      $query = $cnx->prepare($sql);
	      $query->execute() OR die("Row $irow: Fatal error inserting into POOL table 'germplsm'. Query was:<br>$sql");

	      // table 'names'
	      $sql_beg = "INSERT INTO names (GID,NVAL,NSTAT,";
	      $sql_mid = ") VALUES($GID,'$preferredname',1,";
	      $sql_end = ")";
	      if (!empty($crossdate) OR $crossdate == '0') {
		$sql_beg .= "NDATE,";
		$sql_mid .= "$crossdate,";
	      }
	      if (!empty($reference)) {
		$refid = pool_grab("select REFID from BIBREFS where ANALYT = '$reference'");
		$sql_beg .= "NREF,";
		$sql_mid .= "$refid,";
	      }
	      if (!empty($originlocation)) {
		$locid = pool_grab("select LOCID from LOCATION where LNAME = '$originlocation'");
		$sql_beg .= "NLOCN,";
		$sql_mid .= "$locid,";
	      }
	      $sql_beg = rtrim($sql_beg, ",");
	      $sql_mid = rtrim($sql_mid, ",");
              $sql = $sql_beg.$sql_mid.$sql_end;
	      $query = $cnx->prepare($sql);
	      $query->execute() OR die("Row $irow: Fatal error inserting Preferred Name into POOL table 'names'. Query was:<br>$sql");
	    }
	    
	    // table 'T_Comments'
	    if (!empty($comments)) {
	      $sql = "insert into T_Comments (GID, Private, Comment) values ($GID, 0, '$comments')";
	      $query = $cnx->prepare($sql);
	      $query->execute() OR die("Row $irow: Fatal error inserting into POOL table 'T_Comments'. Query was:<br>$sql");
	    }
	  } /* end of if(!GID) */

	  else {
	    // $GID for Preferred Name already exists so we're editing it.
	    // First, update Other Name if present.  It could be a merge.
	    if (!empty($othername)) {
	      // Does this Other Name already exist as a Preferred Name, to be demoted?
	      $renamegid = pool_grab("select GID from names where NVAL = BINARY '$othername' and NSTAT = 1");
	      if ($renamegid) {
		// Yikes, don't do it if the names happen to be the same, e.g. we're only making an externaldb link.
		if ($preferredname != $othername) {
		  // Move all data associated with the old $renamegid to the new Preferred Name.
		  // Leave the old GID in place; just change the name.
		  $devnull = pool_grab("update germplsm set Name = '$preferredname' where GID = $renamegid");
		  $devnull = pool_grab("update names set NVAL = '$preferredname' where GID = $renamegid and NSTAT = 1");
		  // Update the parental GPID's that were $renamegid.
		  $devnull = pool_grab("update germplsm set GPID1 = $GID where GPID1 = $renamegid");
		  $devnull = pool_grab("update germplsm set GPID2 = $GID where GPID2 = $renamegid");
		  // Delete all the old data for Other Name since we're replacing it with Preferred Name.
		  $devnull = pool_grab("delete from germplsm where GID = $renamegid");
		  $devnull = pool_grab("delete from names where GID = $renamegid");
		  $devnull = pool_grab("delete from T_Comments where GID = $renamegid");
		}
	      }
	      // There's a value for Other Name. Insert or update the row in table 'names' for it.
	      // Does this Other Name already exist for this GID?
	      $NID = pool_grab("select NID from names where GID = $GID and NVAL = '$othername'");
	      if (empty($NID)) {
		// Insert a new one.
		$sql_beg = "INSERT INTO names (GID,NVAL,NSTAT,";
		$sql_mid = ") VALUES($GID,'$othername',0,";
		$sql_end = ")";
		if (!empty($namingdate) OR $namingdate == "0") {
		  $sql_beg .= "NDATE,";
		  $sql_mid .= "$namingdate,";
		}
		if (!empty($refother)) {
		  $refid = pool_grab("select REFID from BIBREFS where ANALYT = '$refother'");
		  $sql_beg .= "NREF,";
		  $sql_mid .= "$refid,";
		  $sql_beg .= "link,";
		  if ($refother == "PGRC, Canada" OR $refother == "T3/Oat") 
		    // Make a Gene Bank Link to the PGRC or T3 website.
		    $sql_mid .= "1,";
		  else
		    $sql_mid .= "0,";
		}
		if (!empty($naminglocation)) {
		  $locid = pool_grab("select LOCID from LOCATION where LNAME = '$naminglocation'");
		  $sql_beg .= "NLOCN,";
		  $sql_mid .= "$locid,";
		}
		$sql_beg = rtrim($sql_beg, ",");
		$sql_mid = rtrim($sql_mid, ",");
		$sql = $sql_beg.$sql_mid.$sql_end;
		$query = $cnx->prepare($sql);
		$query->execute() OR die("Row $irow: Fatal error inserting a new Other Name into POOL table 'names'. Query was:<br>$sql");
	      } /* end of if(empty($NID)) */
	      else {
		// Update the metadata about an existing Other Name.
		$sql_beg = "update names set ";
		$sql_mid = "";
		$sql_end = " where NID = $NID";
		if (!empty($namingdate) OR $namingdate == '0') {
		  $sql_mid .= "NDATE = $namingdate,";
		}
		if (!empty($refother)) {
		  $refid = pool_grab("select REFID from BIBREFS where ANALYT = '$refother'");
		  $sql_mid .= "NREF = $refid,";
		  if ($refother == "PGRC, Canada" OR $refother == "T3/Oat") 
		    $sql_mid .= "link = 1,";
		  else 
		    $sql_mid .= "link = 0,";
		}
		if (!empty($naminglocation)) {
		  $locid = pool_grab("select LOCID from LOCATION where LNAME = '$naminglocation'");
		  $sql_mid .= "NLOCN = $locid,";
		}
		$sql_mid = rtrim($sql_mid, ",");
		if (!empty($sql_mid)) {
		  $sql = $sql_beg.$sql_mid.$sql_end;
		  $query = $cnx->prepare($sql);
		  $query->execute() OR die("Row $irow: Fatal error updating an Other Name in POOL table 'names'. Query was:<br>$sql");
		}
	      } /* end of not empty $NID */
	    } /* end of if (!empty($othername)) */

	    // Update germplsm.
	    $sql_beg = "update germplsm set ";
	    $sql_mid = "";
	    $sql_end = "`UPDATE`=NOW(),date=NOW() where GID = $GID";
	    if (!empty($parent1)) {
	      $gpid1 = pool_grab("select GID from names where NVAL = BINARY '$parent1' AND NSTAT = 1");
	      $sql_mid .= "GPID1 = $gpid1, ";
	    }
	    if (!empty($parent2)) {
	      $gpid2 = pool_grab("select GID from names where NVAL = BINARY '$parent2' AND NSTAT = 1");
	      $sql_mid .= "GPID2 = $gpid2, ";
	    }
	    if (!empty($crossdate) OR $crossdate = '0') {
	      $sql_mid .= "GDATE = $crossdate, ";
	    }
	    if (!empty($reference)) {
	      $refid = pool_grab("select REFID from BIBREFS where ANALYT = '$reference'");
	      $sql_mid .= "GREF = $refid, ";
	    }
	    if (!empty($originlocation)) {
	      $locid = pool_grab("select LOCID from LOCATION where LNAME = '$originlocation'");
	      $sql_mid .= "GLOCN = $locid, ";
	    }
	    if (!empty($poolspecies)) {
	      $taxid = pool_grab("select TaxID from T_Taxonomy where TaxName = '$poolspecies'");
	      $sql_mid .= "TaxID = $taxid, ";
	    }
	    $sql = $sql_beg.$sql_mid.$sql_end;
	    $query = $cnx->prepare($sql);
	    $query->execute() OR die("Row $irow: Fatal error updating POOL table 'germplsm'. Query was:<br>$sql");

	    // Update table 'names'.
	    // Which row of 'names' has the Preferred Name for this GID?
	    $NID = pool_grab("select NID from names where GID = $GID and NSTAT = 1");
	    $sql_beg = "update names set ";
	    $sql_mid = "";
	    $sql_end = " where NID = $NID";
	    if (!empty($crossdate) OR $crossdate == "0") {
	      $sql_mid .= "NDATE = $crossdate,";
	    }
	    if (!empty($reference)) {
	      $refid = pool_grab("select REFID from BIBREFS where ANALYT = '$reference'");
	      $sql_mid .= "NREF = $refid,";
	    }
	    if (!empty($originlocation)) {
	      $locid = pool_grab("select LOCID from LOCATION where LNAME = '$originlocation'");
	      $sql_mid .= "NLOCN = $locid,";
	    }
	    $sql_mid = rtrim($sql_mid, ",");
	    if (!empty($sql_mid)) {
	      // Those fields may all be empty, in which case do nothing.
	      $sql = $sql_beg.$sql_mid.$sql_end;
	      $query = $cnx->prepare($sql);
	      /* $query->execute() OR die("Row $irow: Fatal error updating Preferred Name metadata in POOL table 'names'. Query was:<br>$sql"); */
	      $query->execute() OR die("Row $irow: Fatal error updating Preferred Name metadata in POOL table 'names'. Query was:<br>$sql<br>GID=$GID, preferredname=$preferredname");
	    }

	    // Update table 'T_Comments'
	    if (!empty($comments)) {
	      // If this comment already exists for this GID, don't duplicate it.
	      $already = pool_grab ("select Comment_ID from T_Comments where GID = $GID and Comment = '$comments'");
	      if (empty($already)) {
 		$sql = "insert into T_Comments (GID, Private, Comment) values ($GID, 0, '$comments')";
		$query = $cnx->prepare($sql);
		$query->execute() OR die("Row $irow: Fatal error inserting into POOL table 'T_Comments'. Query was:<br>$sql");
	      }
	    }
	  } /* end of editing an existing GID */

      /* Now make (or update) the link from T3 to POOL. */
      $id_POOL = mysql_grab("select barley_pedigree_catalog_uid from barley_pedigree_catalog where barley_pedigree_catalog_name = 'POOL GID'");
      // Is there already a link? Then update.
      $bpcr_uid = mysql_grab("select barley_pedigree_catalog_ref_uid from barley_pedigree_catalog_ref where line_record_uid = $line_uid and barley_pedigree_catalog_uid = $id_POOL");
      if ($bpcr_uid) {
          $sql = "update barley_pedigree_catalog_ref set barley_ref_number = $GID where barley_pedigree_catalog_ref_uid = $bpcr_uid";
          $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
      }
      else {
          $sql = "insert into barley_pedigree_catalog_ref (barley_pedigree_catalog_uid, line_record_uid, barley_ref_number) values ($id_POOL, $line_uid, $GID)";
          $res = mysqli_query($mysqli, $sql) or errmsg($sql, mysqli_error($mysqli));
      }
      
	} /* end of if($action != 'ignore') */
      } /* end of if(!empty($preferredname)) */
    } /* end of for each row of the table*/
    
    // Handle any MySQL errors.
    if ($cnt > 0) {
      // Cool.  Jump back _two_ pages!
      print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\">";
    }
    else {
      echo "<h3>Loaded</h3>";
      echo "The data was loaded successfully. You can check it with <a href='".$config['base_url']."search.php'>Quick search...</a>";
      // Timestamp, e.g. _28Jan12_23:01
      $ts = date("_jMy_H:i");
      $filename = $filename . $ts;
      $devnull = mysqli_query($mysqli, "INSERT INTO input_file_log (file_name,users_name) VALUES('$filename', '$username')") or die(mysqli_error($mysqli));
    }
    $footer_div = 1;
    include $config['root_dir'].'theme/footer.php';
  } /* end of function type_Database */
} /* end of class LineNames_Check */
