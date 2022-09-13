<?php
/**
 * Header and Menu
 *
 * PHP version 5.3
 *
 * @author   Clay Birkett <clb343@cornell.edu>
 * @license  http://triticeaetoolbox.org/wheat/docs/LICENSE Berkeley-based
 * @link     http://triticeaetoolbox.org/wheat/theme/admin_header.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="copyright" content="Copyright (C) 2008 Iowa State University. All rights reserved." >
<meta name="expires" content="<?php echo date("D, d M Y H:i:s", time()+6*60*60); ?> GMT">
<meta name="keywords" content="hordeum,toolbox,barley,tht,database" >
<meta name="revisit-After" content="1 days" >
<meta name="viewport" content="width=device-width, initial-scale=1">

<base href="<?php echo $config['base_url']; ?>">
<script type="text/javascript" src="includes/core.js"></script>
<script type="text/javascript" src="theme/new.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="//code.jquery.com/jquery-3.6.1.min.js"></script>
<script src="//code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script src="theme/jquery.smartmenus.min.js" type="text/javascript"></script>
<script src='https://www.google.com/recaptcha/api.js'></script>
<!--script type="text/javascript" src="js/site.js"></script-->

<link href="theme/sm-core-css.css" rel="stylesheet" type="text/css">
<link href="theme/sm-cleant3.css" rel="stylesheet" type="text/css">
<script>
jQuery.noConflict();  //by default jQuery uses $ as shortcut for jQuery. To avoid conflict with prototype.js
jQuery( document ).ready(function( $ ) {
    $('#main-menu').smartmenus();
});
</script>

<?php
global $mysqli;

// clear session if it contains variables from another database
$database = mysql_grab("select database()");
if ($_SESSION['database'] != $database) {
    session_unset();
}
$_SESSION['database'] = $database;
// Create <title> for browser to show.
$title = mysql_grab("select value from settings where name='title'");
if (isset($pageTitle)) {
    $title .= " - $pageTitle";
}
if (empty($title)) {
    $title = "The Triticeae Toolbox";
}
echo "<title>$title</title>";
require_once $config['root_dir'].'includes/analyticstracking.php';

?>
</head>
<div id="container">
<div id="barleyimg"><h1 style="color: white; text-shadow: 2px 2px 5px black; font-size: 400%;"></h1>
  </div>
  <div id="util">
  <div id="utilright">
  </div>
  <a href="./feedback.php">Contact Us</a>
  </div>
  <h1 style="color: white; text-shadow: 2px 2px 5px black; font-size: 400%;">&nbsp;&nbsp;<?php echo $title; ?></h1>

<?php
//The navigation tab menus
//Tooltips:
$lang = array(
    "desc_sc1" => "Search by germplasm and phenotype information",
    "desc_sc2" => "Credits, data status ... ",
    "desc_sc3" => "Search by genotyping information",
    "desc_sc4" => "Search by Expression Related information.",
    "desc_sc5" => "Database administration",
    "desc_sc6" => "Visualization tools",
);
?>
<div id="nav">
  <ul id="main-menu" class="sm sm-clean">
    <li>
      <a href="">Home</a>
    <li><a href="">Select</a>
      <ul>
      <li>
      <a href="<?php echo $config['base_url']; ?>downloads/select_all.php" title="Lines and Phenotypes">
        Wizard (Lines, Traits, Trials)</a>
      <a href="<?php echo $config['base_url']; ?>pedigree/line_properties.php" title="Select by name, source, or simply-inherited characters">
        Lines by Properties</a>
      <li>
      <a href="<?php echo $config['base_url']; ?>phenotype/compare.php" title="Select within a range of trait values">
        Lines by Phenotype</a>
      <li><a href="<?php echo $config['base_url']; ?>haplotype_search.php" title="Select desired alleles for a set of markers">
        Lines by Haplotype</a>
      <li><a href="<?php echo $config['base_url']; ?>downloads/select_genotype.php" title="Select by Genotype Experiment">
        Lines by Genotype Experiment</a>
    <?php
    /* if( authenticate( array(USER_TYPE_PUBLIC, USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR ) ) ):  */
    /* Everybody is USER_TYPE_PUBLIC.  Require he be signed in (therefore registered). */
    if (loginTest2()) : ?>
        <li><a href="<?php echo $config['base_url']; ?>myown/panels.php" title="Panels I created"><font color=yellow>My Line Panels</font></a>
        <li><a href="<?php echo $config['base_url']; ?>genotyping/panels.php" title="Panels I created"><font color=yellow>My Marker Panels</font></a>
        <?php
    endif
    ?>
    <li>
    <a href="<?php echo $config['base_url']; ?>phenotype/phenotype_selection.php" title='"Phenotype" = a Trait value in a particular Trial'>
        Traits and Trials</a>
    <li>
    <a href="<?php echo $config['base_url']; ?>genotyping/marker_selection.php" title="Select by name or map position">
        Markers</a>
    <li>
    <a href="<?php echo $config['base_url']; ?>genotyping/pop-poly.php" title="Select by population variants">Subset by Marker Polymorphisms</a>
    <li>
    <a href="<?php echo $config['base_url']; ?>maps/select_map.php" title="Select genetic map">Genetic Map</a>
    <li>
    <a href="<?php echo $config['base_url']; ?>downloads/clear_selection.php" title="Clear selection">Clear selection</a>
    </ul>
    <li><a href="" title="<?php echo $lang["desc_sc6"]; ?>">Analyze</a>
      <ul>
        <li><a href="" title="Cluster">Cluster</a>
        <ul>
        <li><a href="<?php echo $config['base_url']; ?>analyze/cluster_lines.php" title="Genetic structure">Cluster Lines by Genotype</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/cluster_lines3d.php" title="Genetic structure">Cluster Lines 3D (pam)</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/cluster_lines4d.php" title="Genetic structure">Cluster Lines 3D (hclust)</a>
        </ul>
        <li><a href="<?php echo $config['base_url']; ?>analyze/outlier.php" title="Filter outliers">Filter outliers</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/training.php" title="Optimize training set">Optimize training set</a>
        <li><a href="<?php echo $config['base_url']; ?>Index/traits.php" title="Combination of traits">Selection Index</a>
        <li><a href="" title="Traits and Trials statistics">Traits and Trials statistics</a>
        <ul>
        <li><a href="<?php echo $config['base_url']; ?>analyze/histo.php" title="Histogram">Traits and Trials Histogram</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/boxplot.php" title="Boxplot">Traits and Trials Boxplot</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/table.php" title="Boxplot">Traits and Trials Table</a>
        </ul>
        <li><a href="<?php echo $config['base_url']; ?>curator_data/cal_index.php" title="Canopy Spectral Reflectance">Canopy Spectral Reflectance</a>
        <!--li><a href="<?php echo $config['base_url']; ?>gensel2.php" title="GWAS">Genome-wide Associations Studies</a-->
        <li><a href="<?php echo $config['base_url']; ?>gensel.php" title="Genomic selection">Genomic Association and Prediction</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/compare_trials.php" title="Compare Trait value for 2 Trials">Compare Trials</a>
        <li>
          <a href="<?php echo $config['base_url']; ?>pedigree/pedigree_tree.php" title="Show pedigree annotated with alleles of selected markers ">
          Track Alleles through Pedigree</a>
        <li><a href="<?php echo $config['base_url']; ?>pedigree/parse_pedigree.php" title="Parse a pedigree string in Purdy notation">Parse Purdy Pedigrees</a>
        <li><a href="<?php echo $config['base_url']; ?>genotyping/sum_lines.php" title="Disagreements among repeated genotyping experiments">Allele Data Conflicts</a>
        <li><a href="<?php echo $config['base_url']; ?>viroblast" title="Find mapped sequences similar to yours">
          BLAST</a>
        <li><a href="<?php echo $config['base_url']; ?>pedigree/pedigree_markers.php" title="Show haplotype and phenotype for selected lines and markers">Haplotype Data</a>
      </ul>
    <li><a href="" title="">Download</a>
      <ul>
      <li><a href="<?php echo $config['base_url']; ?>downloads/downloads.php" title="Tassel format">
            Genotype and Phenotype Data</a>
      <li><a href="<?php echo $config['base_url']; ?>snps.php" title="Context sequences and A/B => nucleotide translation">
            SNP Alleles and Sequences</a> 
      <li><a href="<?php echo $config['base_url']; ?>downloads/marker_annotation.php">Marker Annotation</a>
      <li><a href="<?php echo $config['base_url']; ?>genotyping/download-vcf.php" title="Download portion of VCF files">VCF Download</a>
      <li><a href="<?php echo $config['base_url']; ?>downloads/bulk_download.php" title="Download all records from database">Bulk Download</a>
      <li><a href="<?php echo $config['base_url']; ?>downloads/tablet_export.php" title="Tablet export">
            Android Field Book</a>
      <li><a href="<?php echo $config['base_url']; ?>maps/maps.php" title="Genetic Maps">Genetic Maps</a>
      </ul>
    <li><a href="" title="">Search</a><ul>
    <?php
    $results = mysql_grab("SHOW tables like 'gene_annotations'");
    if ($results == "gene_annotations") {
        ?>
        <li><a href="<?php echo $config['base_url']; ?>genes" title="Browse Genes">Genes</a>
        <?php
    }
    ?>
    <li><a href="<?php echo $config['base_url']; ?>protein" title="Browse Proteins">Proteins</a>
    <?php
    $results = mysql_grab("SHOW tables like 'pathways'");
    if ($results == "pathways") {
        ?>
        <li><a href="<?php echo $config['base_url']; ?>pathways" title="Browse Pathways">Pathways</a>
        <?php
    }
    $results = mysql_grab("SHOW tables like 'compounds'");
    if ($results == "compounds") {
        ?>
        <li><a href="<?php echo $config['base_url']; ?>compounds" title="Browse Compounds">Compounds</a>
        <?php
    }
    echo "</ul>";
    ?>
    <li><a href="" title="">Reports</a>
      <ul>
        <?php
        $results = mysql_grab("SHOW tables like 'qtl_raw'");
        if ($results == "qtl_raw") {
            ?>
            <li><a href="<?php echo $config['base_url']; ?>qtl/qtl_report.php" title="GWAS Results">GWAS Results</a>
            <?php
        }
        $results = mysql_grab("SHOW tables like 'vep_annotations'");
        if ($results == "vep_annotations") {
            ?>
            <li><a href="<?php echo $config['base_url']; ?>genotyping/variations.php" title="Variant Effects">Variant Effects</a>
            <?php
        }
        $results = mysql_grab("SHOW tables like 'marker_primers'");
        if ($results == "marker_primers") {
            ?>
            <li><a href="<?php echo $config['base_url']; ?>genotyping/polymarker.php" title="Designed Markers">PolyMarker Designed Primers</a>
            <?php
        }
        $results = mysql_grab("SHOW tables like 'marker_report_reference'");
        if ($results == "marker_report_reference") {
            ?>
            <li><a href="<?php echo $config['base_url']; ?>genotyping/marker_report_ref.php" title="BLAST Markers against genome assembly">Marker Alignment to Reference</a>
            <?php
        }
        $results = mysql_grab("SHOW tables like 'marker_report_synonyms'");
        if ($results == "marker_report_synonyms") {
            ?>
            <li><a href="<?php echo $config['base_url']; ?>genotyping/marker_report_syn.php" title="BLAST Markers against themselves">Marker Synonyms by Experiment</a>
            <?php
        }
        ?>
        <li><a href="" title="">JBrowse - Genome Browser</a>
        <?php
        $sql = "select value from settings where name like 'assembly%'";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        $rowCnt = mysqli_num_rows($res);
        if ($rowCnt > 0) {
            echo "<ul>";
            while ($row = mysqli_fetch_array($res)) {
                $result = explode(",", $row[0]);
                ?>
                <li><a href="<?php echo $result[0] ?>" target="_blank" title="JBrowse"><?php echo $result[1] ?></a>
                <?php
            }
            echo "</ul>";
        }
        ?>
      </ul>
   
    <?php
    if (authenticate(array(USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR))) {
        ?> 
      <li> <a href="" title="Add, edit or delete data">Curate</a>
      <ul>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_line_names.php" title="Must precede loading data about the lines">
      Lines</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_pedigree_router.php" title="Pedigree information about the lines, optional">
      Pedigrees</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_annotations_upload_router.php" title="Descriptions of phenotype experiments, must precede loading results">
      Phenotype Trials</a></li>
      <!-- <li><a href="<?php echo $config['base_url']; ?>curator_data/input_experiments_upload_router.php" title="Phenotype data"> -->
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_experiments_upload_excel.php" title="Phenotype data">
      Phenotype Results</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_csr_router.php" title="Phenotype CSR data">
      CSR Data</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/delete_experiment.php" title="Careful!">
      Delete Trials and Experiments</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_trait_router.php" title="Must precede loading data about the traits">
      Traits and Genetic Characters</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/genotype_annotations_upload.php" title="Add Genotype Annotations Data">
      Genotype Experiments</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/genotype_data_upload.php" title="Add Genotyping Result Data">
      Genotype Results </a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_map_upload.php" title="Genetic maps of the markers">
      Maps</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/markers_upload.php" title="Must precede loading data about the markers">
      Markers</a></li>
      <li><a href="<?php echo $config['base_url']; ?>login/edit_programs.php">
      Contributing Data Programs</a></li>
      <li><a href="<?php echo $config['base_url']; ?>login/edit_whatsnew.php">
      "What's New"</a>
      <li><a href="<?php echo $config['base_url']; ?>login/edit_toronto.php">
      Data Policy dataset descriptions</a>
      <!-- Too dangerous. -->
      <!-- <li><a href="<?php echo $config['base_url']; ?>login/edit_anything.php"> -->
      <!-- Anything!</a></li> -->
      </ul> <?php
    } ?>
      <li> <a href="" title="Manage">Manage</a>
        <ul>
        <li><a href="<?php echo $config['base_url']; ?>curator_data/exp_design.php" title="Experiment Design">
            Phenotype Trials</a>
        </ul>
    <?php
    if (authenticate(array( USER_TYPE_ADMINISTRATOR))) { ?>
    <li>
    <a href="" title="<?php echo $lang["desc_sc5"]; ?>">Administer</a>
    <ul>
      <li><a href="<?php echo $config['base_url']; ?>login/edit_users.php" title="No deletion yet">Edit Users</a>
      <li><a href="<?php echo $config['base_url']; ?>dbtest/" title="Table Status">Table Status</a>
      <li><a href="<?php echo $config['base_url']; ?>login/input_gateway.php" title="Data Input Gateway">Data Input Gateway</a>
      <li><a href="<?php echo $config['base_url']; ?>login/export_gateway.php" title="Data Export Gateway">Data Export Gateway</a>
      <li><a href="<?php echo $config['base_url']; ?>login/cleanup_temporary_dir.php" title="Clean up temporary files">Clean up temporary files</a>
      <li><a href="http://thehordeumtoolbox.org/webalizer/" title="Webalizer old" target="_blank">Usage, wheat.pw.usda.gov</a>
      <li><a href="http://triticeaetoolbox.org/webalizer/" title="Webalizer new" target="_blank">Usage, tcap</a>
      <li><a href="http://google.com/analytics/web/?hl=en#home/a37631546w66043588p67910931/" title="Google Analytics, if you're permitted" target="_blank">Usage Analytics</a>
    </ul>
    </li>
        <?php
    }

    ?>

  <li>
  <a href="" title="<?php echo $lang["desc_sc2"]; ?>">Resources</a>
  <ul>
    <?php
    $results = mysql_grab("SHOW tables like 'marker_primers'");
    if ($results == "marker_primers") {
        ?>
        <li><a href="https://galaxy.triticeaetoolbox.org" target="_blank">SNP Primer Design, Galaxy Platform</a>
        <li><a href="http://shiny.triticeaetoolbox.org/primer_filter" target="_blank">KASP Primer Design, R Shiny</a>
        <?php
    }
    ?>
    <li><a href="<?php echo $config['base_url']; ?>about.php" title="Description, contributors">Overview</a>
    <li><a href="<?php echo $config['base_url']; ?>tutorials" title="Tutorials">Tutorials</a>
    <li><a href="<?php echo $config['base_url']; ?>t3_report.php" title="Current summary of data loaded">Content Status</a>
    <li><a href="<?php echo $config['base_url']; ?>curator_data/instructions.php" title="Submit Data to T3">Data Submission</a>
    <li><a href="<?php echo $config['base_url']; ?>traits.php" title="Traits and units used">Trait Descriptions</a>
    <li><a href="<?php echo $config['base_url']; ?>properties.php" title="Environment-independent line properties">Genetic Character Descriptions</a>
    <li><a href="<?php echo $config['base_url']; ?>resources/all_breed_css.php" title="Sources of the data">Contributing Data Programs</a>
    <li><a href="<?php echo $config['base_url']; ?>toronto.php" title="Toronto Statement">Data Usage Policy</a>
    <!-- <li><a href="<?php echo $config['base_url']; ?>acknowledge.php" title="Contributions from other projects">Acknowledgments</a> -->
    <!-- <li><a href="<?php echo $config['base_url']; ?>termsofuse.php" title="Restrictions on free use of the data">Terms of Use</a> -->
  </ul>

</ul>
</div>
<div id="quicklinks">
<?php
include $config['root_dir'] . 'side_menu.php';
?>
</div>
<div id="main">
