<?php

require_once 'config.php';
if (isset($mysqli)) {
} else {
    require $config['root_dir'].'includes/bootstrap.inc';
}
session_start();

?>
<h2>Quick Links </h2>
<ul>
<?php
if (isset($_SESSION['username']) && !isset($_REQUEST['logout'])) {
    $type_name = $_SESSION['usertype_name'];
    echo "$type_name\n";
    ?>
    <li>
    <a title="Logout" href="<?php echo $config['base_url']; ?>logout.php">Logout <span style="font-size: 10px">(<?php echo $_SESSION['username'] ?>)</span></a>
    <?php
} else {
    ?>
    <li>
    <a title="Login" href="<?php echo $config['base_url_ssl']; ?>login.php"><strong>Login/Register</strong></a>
    <?php
}
echo "<p><li><b>Current selections:</b>";
echo "<li><a href='".$config['base_url']."pedigree/line_properties.php'>Lines</a>: ";
if (isset($_SESSION['selected_lines'])) {
    echo count($_SESSION['selected_lines']);
}
echo "<li><a href='".$config['base_url']."genotyping/marker_selection.php'>Markers</a>: ";
if (isset($_SESSION['clicked_buttons'])) {
    echo count($_SESSION['clicked_buttons']);
} elseif (isset($_SESSION['geno_exps_cnt'])) {
    echo number_format($_SESSION['geno_exps_cnt']);
} else {
    echo "All";
}
echo "<li><a href='".$config['base_url']."phenotype/phenotype_selection.php'>Traits</a>: ";
if (isset($_SESSION['selected_traits'])) {
    echo count($_SESSION['selected_traits']);
} elseif (isset($_SESSION['phenotype'])) {
    echo count($_SESSION['phenotype']);
} else {
    echo "0";
}
if (isset($_SESSION['selected_trials'])) {
    $expr = $_SESSION['selected_trials'];
    $sql = "select experiment_type_name from experiments, experiment_types
              where experiments.experiment_type_uid = experiment_types.experiment_type_uid
              and experiment_uid = $expr[0]";
    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
    if ($row = mysqli_fetch_row($res)) {
        $type_name = ucfirst($row[0]);
        echo "<li><a href='".$config['base_url']."phenotype/phenotype_selection.php'>$type_name Trials</a>";
    }
    echo ": " . count($_SESSION['selected_trials']);
}
echo "<li><a href='".$config['base_url']."genotyping/genotype_selection.php'>Genotype Experiments</a>";
if (isset($_SESSION['geno_exps'])) {
    echo ": " . count($_SESSION['geno_exps']);
}
if (isset($_SESSION['selected_lines']) || isset($_SESSION['selected_traits']) || isset($_SESSION['selected_trials'])) {
    echo "<p><a href='downloads/clear_selection.php'>Clear Selection</a>";
}
?>
  <br><br><li>
  <form style="margin-bottom:3px" action="search.php" method="post">
  <input type="hidden" value="Search" >
  <input style="width:170px" type="text" name="keywords" value="Quick search..."
  title = "These regular expressions modify the search and the query will run slower
   [ ] - bracket expression
   ^ - beginning of string
   $ - end of string
   . - any single character
   * - zero or more instances of preceding element
   + - one or more instances of preceding element" onfocus="javascript:this.value=''" onblur="javascript:if(this.value==''){this.value='Quick search...';}" >
  </form>
  </ul>
  <br>
<?php include $config['root_dir'].'whatsnew.html'; ?>

