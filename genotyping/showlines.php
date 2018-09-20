<?php

require 'config.php';
/*
 * Logged in page initialization
 */
require $config['root_dir'] . 'includes/bootstrap.inc';
$mysqli = connecti();

require $config['root_dir'] . 'theme/admin_header2.php';
/*******************************/
?>

<div id="primaryContentContainer">
<div id="primaryContent">

<h2>Alleles for all lines</h2>

<?php
if (isset($_GET['marker']) && ($_GET['marker'] != "")) {
    $marker_uid = $_GET['marker'];
    $sql = "select marker_name from markers where marker_uid = $marker_uid";
    if ($stmt = mysqli_prepare($mysqli, "SELECT marker_name from markers where marker_uid = ?")) {
        mysqli_stmt_bind_param($stmt, "i", $marker_uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $markername);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_GET['markername']) && ($_GET['markername'] != "")) {
    $markername = $_GET['markername'];
    $markername = strip_tags($markername);
    $sql = "select marker_uid from markers where marker_name = ?";
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $markername);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $marker_uid);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
} else {
    $marker_uid = "";
}

echo "<h3>Marker $markername</h3>";

/* check for blind sql injection */
if (preg_match("/[^0-9,]/", $marker_uid)) {
} elseif (isset($_GET['sortby']) && isset($_GET['sorttype'])) {
    $sortby = $_GET['sortby'];
    $sorttype = $_GET['sorttype'];
    if (($sortby != "line_record_name") && ($sortby != "alleles") && ($sortby != "trial_code")) {
        return;
    }
    if (($sorttype != "DESC") && ($sorttype != "ASC")) {
        return;
    }
    $orderby = $_GET['sortby'] . " " . $_GET['sorttype'];
    showLineForMarker($marker_uid, $orderby);
} elseif ($marker_uid != "") {
    showLineForMarker($marker_uid);
}
?>

<div class="boxContent">

<form action="<?php echo $config['base_url']; ?>genotyping/showlines.php" method="get">
<p><strong>Marker: </strong>
<input type="text" name="markername" value="" />&nbsp;&nbsp;&nbsp; Example: 12_11047<br>
<input type="submit" value="Get Data" />
</form>
</div>
</div>
</div>
</div>

<?php
mysqli_close($mysqli);
require $config['root_dir'] . 'theme/footer.php';
