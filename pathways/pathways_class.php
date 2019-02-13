<?php

namespace reports;

function displayPathways()
{
    global $config;
    global $mysqli;
    include $config['root_dir'].'theme/admin_header2.php';

    if (isset($_GET['query'])) {
        $query = $_GET['query'];
    } else {
        $query = "name";
    }

    echo "<h2>Pathways</h2>";
    echo "Information provided by <a href=\"https://www.ncbi.nlm.nih.gov/pubmed/27799469\" target=\"_new\">";
    echo "Plant Reactome</a>: a resource for plant metabolic and regulatory pathways and comparative analysis. ";
    echo "Using curated rice pathways as a reference,<br> the Plant Reactome predicts ";
    echo "pathways in other plant species on the basis of Compara and Inparanoid super-cluster orthology.<br>";
    echo "The database uses the Feb 2019 (Gramene r60) release of Plant Reactome.<br><br>\n";

    echo "<div id=\"results\">\n";
    $sql = "select pathway_uid, pathway_reference, pathway_name, species from pathways order by pathway_name";
    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
    echo "<table><tr><td>Pathway Name<td><td>\n";
    while ($row = mysqli_fetch_array($res)) {
        $uid = $row[0];
        $path_ref = $row[1];
        $path_name = $row[2];
        $species = $row[3];
        $link2 = "<a href=\"view.php?table=pathways&uid=$uid\">Gene List</a>";
        echo "<tr><td>$path_name<td>$path_ref<td>$link2\n";
    }
}
