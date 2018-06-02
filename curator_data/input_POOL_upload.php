<?php
// 12/14/2010 JLee  Change to use curator bootstrap

require 'config.php';
/*
 * Logged in page initialization
 */
include $config['root_dir'] . 'includes/bootstrap_curator.inc';

$mysqli = connecti();
loginTest();

/* ******************************* */
$row = loadUser($_SESSION['username']);

////////////////////////////////////////////////////////////////////////////////
ob_start();
authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();

new Pedigree($_GET['function']);
class Pedigree {
    private $delimiter = "\t";
    // Using the class's constructor to decide which action to perform
    public function __construct($function = null)    {	
      switch($function)
	{
	default:
	  $this->typePedigree(); /* intial case*/
	  break;
	}	
    }

private function typePedigree()	{
  global $config;
  include($config['root_dir'] . 'theme/admin_header.php');
  echo "<h2>Add Line Information for POOL and T3/Oat </h2>"; 
  $this->type_Pedigree_Name();
  $footer_div = 1;
  include($config['root_dir'].'theme/footer.php');
}
	
private function type_Pedigree_Name()	{
?>
<style type="text/css">
			th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
			table {background: none; border-collapse: collapse}
			td {border: 0px solid #eee !important;}
			h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
		</style>
<form action="curator_data/input_lines_POOL.php" method="post" enctype="multipart/form-data">
	<p><strong>File:</strong> <input id="file" type="file" name="file" size="80%" /> &nbsp;&nbsp;&nbsp;   <a href="curator_data/examples/T3/LineSubmissionForm_POOL.xls">Example POOL input file</a></p>
	<p><input type="submit" value="Upload" /></p>
</form>
		
<?php
 
	} /* end of type_Pedigree_Name function*/
	
} /* end of class */

?>
