<?php
 
$pageTitle = "Tutorials";
require 'config.php';
require $config['root_dir'].'includes/bootstrap.inc';
require $config['root_dir'].'theme/admin_header2.php';
?>

<h1>Tutorials</h1>
<ol>
<li><a href=tutorials/variant_effect.php>Variant Effects Report</a>
<li><a href=tutorials/blast.php>BLAST Analysis</a>
<li><a href=tutorials/Tutorial_TASSEL.pdf>Exporting data for TASSEL</a>
<li><a href=tutorials/Tutorial_Flapjack.pdf>Exporting data for Flapjack</a>
<li><a href=tutorials/Tutorial_RScript.pdf>Exporting data for R Scripts</a>
<li><a href=tutorials/How_to_translate_gene_names.pdf>How to translate gene names</a>
<?php
$database = mysql_grab("select database()");
if (preg_match("/wheat/", $database)) {
    ?>
    <li><a href=tutorials/TutorialEcoTILLING.pdf>Eco TILLING BLAST</a>
    <li><a href=tutorials/Tutorial_Designed_Primers.pdf>Designed Primers</a>
    <li><a href=tutorials/Tutorial_KnetMiner.pdf>KnetMiner</a>
    <li><a href=tutorials/SNP_PrimerDesignPipelineTutorial>SNP Primer Design Pipeline</a>
    <?php
}
?>
</ol>
<h1>Data Submission</h1>
<ol>
<?php
$files = scandir($config['root_dir'] . "curator_data/tutorial");
foreach ($files as $item) {
    if (preg_match("/(^[^_]+)_([^\s]+)/", $item, $match)) {
        $item_tag = $match[1];
        $item_des = $match[2];
    } else {
        $item_tag = $item;
        $item_des = $item;
    }
    if (preg_match('/(pdf|html|pptx)/', $item_des)) {
        $item_clean = preg_replace("/\.pdf/", "", $item_des);
        $item_clean = preg_replace("/\.html/", "", $item_clean);
        $item_clean = preg_replace("/\.pptx/", "", $item_clean);
        $item_clean = preg_replace("/_/", " ", $item_clean);
        echo "<li><a href=\"" . "curator_data/tutorial/" . "$item\">$item_clean</a>\n";
    }
}
?>
</ol>
</div>
<?php
require $config['root_dir'].'theme/footer.php';
