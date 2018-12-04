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
    echo "pathways in other plant species on the basis of Compara and Inparanoid super-cluster orthology. ";
    echo "The database uses the Oct 2018 (59) release of Plant Reactome.<br>\n";
    echo "The Plant Reactome Id links to the \"Pathway Browser\" at plantreactome.gramene.org.<br>";
    echo "The Pathway Name links to a list of genes contained in the given pathway.<br><br>";

    echo "<div id=\"results\">\n";
    $sql = "select pathway_uid, pathway_reference, pathway_name, species from pathways order by pathway_name";
    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
    echo "<table><tr><td>Pathway Reference<td>Pathway Name<td>Species\n";
    while ($row = mysqli_fetch_array($res)) {
        $uid = $row[0];
        $path_ref = $row[1];
        $gene_name = $row[2];
        $species = $row[3];
        $link2 = "<a href=\"view.php?table=pathways&uid=$uid\">$gene_name</a>";
        echo "<tr><td>$path_ref<td>$link2<td>$species\n";
    }
}
