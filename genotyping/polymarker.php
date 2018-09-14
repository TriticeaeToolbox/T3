<?php

/**
 * Display output from polymarker
 */

require 'config.php';
require $config['root_dir'].'includes/bootstrap.inc';
$mysqli = connecti();

include $config['root_dir'].'theme/admin_header.php';

echo "<h2>Polymarker design for 2017 WheatCAP project</h2>";

echo "A detailed description of the design process can be found <a href=genotyping/20180821_mapping_stats.pdf>here</a>.<br>";
echo "Genotype experiment description can be found <a href=display_genotype.php?trial_code=2017_WheatCAP>2017_WheatCAP</a> and";
echo " <a href=display_genotype.php?trial_code=2017_WheatCAP_UCD>2017_WheatCAP_UCD</a>.<br>";
echo "A description of the polymarker program can be found at <a href=\"https://academic.oup.com/bioinformatics/article/31/12/2038/213995\" target=\"_blank\">PolyMarker: A Fast polyploid primer design pipeline</a>.<br><br>";

if (isset($_GET['marker'])) {
    $marker = $_GET['marker'];
    $selected = explode(",", $marker);
} elseif (isset($_SESSION['clicked_buttons'])) {
    $selected = $_SESSION['clicked_buttons'];
} else {
    echo "<a href=\"genotyping/marker_selection.php\">Select a marker</a> using the \"Wheat CAP 2017\" map to see design results.<br>";
    echo "</div>";
    include $config['root_dir'].'theme/footer.php'; 
    die();
}
$count = count($selected);
if ($count > 10) {
    echo "Results limited to 10 markers<br>\n";
    $selected = array_slice($selected, 0, 10);
}

$count = 0;
foreach ($selected as $marker) {
    $sql = "select marker_name, results from marker_primers where marker_uid = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $marker);
    $stmt->bind_result($marker_name, $results); 
    if ($stmt->execute()) {
        if ($stmt->fetch()) {
            $count++;
            $line = explode(",", $results);
            $header = array("ID","SNP (position and changed)","Region Size (size of the aligned sequence)","chromosome","number of chromosomes where the marker hits","regions where contig is found","SNP_type","A Primer for first allele","B Primer for second allele","common primer","primer type","orientation of the first primer","A_TM, melting point of first primer","B_TM, melting point of second primer","melting point of common primer","selected from","product size","errors","is repetitive","hit_count, how many regions the marker maps");
            echo "<table>";
            echo "<tr><td>Marker Name<td>$marker_name\n";
            foreach ($line as $key => $val) {
                echo "<tr><td>$header[$key]<td>$val\n";
            }
            echo "</table><br>";
            $stmt->close();
        }
    } else {
        echo "Error: bad query";
    }
}
if ($count == 0) {
    echo "<font color=\"red\">Error: No designed primers found</font><br><br>";
    echo "<a href=\"genotyping/marker_selection.php\">Select a marker</a> using the \"Wheat CAP 2017\" map to see design results.<br>";
}
echo "</div>";
include $config['root_dir'].'theme/footer.php';
