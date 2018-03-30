<?php

namespace reports;

function displayGenes()
{
    global $config;
    global $mysqli;
    include $config['root_dir'].'theme/admin_header2.php';

    if (isset($_GET['query'])) {
        $query = $_GET['query'];
    } else {
        $query = "name";
    }

    echo "<h2>Gene Annotation</h2>";
    echo "Information provided by Ensembl Plants - Triticum_aestivum.TGACv1.38.gff3<br>";
    echo "The link to the Gene Id provides a description, location, Uniprot, Expression, and Pathway information.<br><br>";
    /**
    echo "<form action=\"\">";
    echo "<input type=\"radio\" name=\"query\" value=\"name\" onclick=\"queryDb(this.value)\" $checkName>genes with assigned name indexed by name<br>";
    echo "<input type=\"radio\" name=\"query\" value=\"prot\" onclick=\"queryDb(this.value)\"$checkProt>genes with UniProtKB indexed by description<br>";
    echo "</form><br>";
    **/
    echo "Query type<br>";
    echo "<a href=\"genes/?query=name\">genes with name<br></a>";
    echo "<a href=\"genes/?query=prot\">genes with UniProtKB</a><br><br>";

    //echo "Index to genes<br>\n";
    echo "<div id=\"results\">\n";
    if ($query == "prot") {
        $sql = "select gene_annotation_uid, gene_id, gene_name, description, bin from gene_annotations
            where description like \"%UniProt%\" order by description";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_array($res)) {
            $gene = substr($row[3], 0, 1);
            $gene = strtoupper($gene);
            if ($gene != $prev) {
                //echo "<a href=\"genes\?query=prot#$gene\">$gene</a> ";
                $prev = $gene;
            }
        }
    } else {
        $sql = "select gene_annotation_uid, gene_id, gene_name, description, bin from gene_annotations
            where gene_name regexp '^[a-z]' and type = \"gene\" order by gene_name";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_array($res)) {
            $gene = substr($row[2], 0, 1);
            $gene = strtoupper($gene);
            if ($gene != $prev) {
                //echo "<a href=\"genes\?query=name#$gene\">$gene</a> ";
                $prev = $gene;
            }
        }
    }
    //echo "<br><br>\n";
    
    $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
    echo "<table><tr><td>Gene Id<td>Name<td>Bin<td>Description\n";
    while ($row = mysqli_fetch_array($res)) {
        $uid = $row[0];
        $gene_id = $row[1];
        $gene_name = $row[2];
        $desc = urldecode($row[3]);
        $bin = $row[4];
        $anchor = substr($row[2], 0, 1);
        $anchor = strtoupper($anchor);
        if ($anchor != $prev) {
            echo "<a id=\"$anchor\"></a> ";
            $prev = $anchor;
        }
        echo "<tr><td><a href=\"view.php?table=gene_annotations&uid=$uid\">$gene_id</a><td>$gene_name<td>$bin<td>$desc\n";
    }
}
