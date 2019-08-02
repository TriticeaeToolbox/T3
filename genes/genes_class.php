<?php

namespace reports;

class Genes
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'displayQuery':
                $assembly = $_GET['assembly'];
                $this->displayQuery($assembly);
                break;
            case 'displayGenes':
                $assembly = $_GET['assembly'];
                $this->displayGenes($assembly);
                break;
            default:
                $this->step1();
                break;
        }
    }

    private function step1()
    {
        global $config;
        global $mysqli;
        include $config['root_dir'].'theme/admin_header2.php';

        echo "<h2>Search Gene Annotation</h2>";
        echo "The <b>Gene Id</b> link provides information on markers in T3 and links to JBrowse. The Gene Id link also provides external links to protein, KnetMiner, and pathway information.<br>\n";
        echo "The gene annotation information provided by <a href=\"http://plants.ensembl.org/biomart/martview\" target=\"_new\">EnsemblPlants</a> BioMart. The annotation is done with\n";
        echo "mostly automated tools.<br> To search for gene common names it is best to search <a href=protein>Protein Annotation</a> first then follow Gene Id link to the gene description.<br><br>\n";

        //get list of assemblies
        $sql = "select distinct(assemblies.assembly_uid), assemblies.assembly_name, data_public_flag, assemblies.description, assemblies.created_on
            from gene_annotations, assemblies
            where gene_annotations.assembly_name = assemblies.assembly_uid order by assemblies.assembly_uid";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            //pick latest assembly as default
            $uid = $row[0];
            $assembly = $uid;
            $assemblyList[$uid] = $row[1];
            $assemblyDesc[$uid] = $row[3];
        }

        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        }
        //display list of assemblies
        echo "<br><form><table><tr><td>Genome Assembly<td>Description";
        foreach ($assemblyList as $key => $ver) {
            if ($key == $assembly) {
                $selected = "checked";
            } else {
                $selected = "";
            }
            echo "<tr><td nowrap><input type=\"radio\" name=\"assembly\" id=\"assembly\" value=\"$key\" $selected> $ver<td>$assemblyDesc[$key]\n";
        }
        $sql = "select * from assemblies where data_public_flag = 0";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($result)) {
            if (!isset($_SESSION['username'])) {
                echo "<tr><td><a href=\"login.php\">Login</a><td>To access additional assemblies";
            } elseif (!authenticate(array(USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR))) {
                $type_name = $_SESSION['usertype_name'];
                echo "<tr><td><a href=\"feedback.php\">Request project participant status</a><td>To access additional assemblies";
                echo ", your current status is $type_name";
            }
        }
        ?>
        </table></form><br>
        <div id="step1">
        <?php
        $this->displayQuery($assembly);
        ?>
        </div>
        <div id="step2">
        <script type="text/javascript" src="genes/genes.js"></script>
        <?php
        $this->displayGenes($assembly);
        echo "</div></div>";
        include $config['root_dir'].'theme/footer.php';
    }

    private function displayQuery($assembly)
    {
        global $config;
        if (isset($_GET['search'])) {
            $query = $_GET['search'];
        } else {
            $query = "";
        }
        ?>
        Search Gene Id, Name, Description, or Functional annotation:<br>
        Example Queries:
        <?php
        echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=Maturase>Maturase</a>, ";
        echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=TraesCS3D02G273600>TraesCS3D02G273600</a>, ";
        //echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=HORVU2Hr1G069650>HORVU2Hr1G069650</a>, ";
        //echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=abp1>abp1</a>, ";
        echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=Dormancy>Dormancy</a><br><br>";
        ?>
        <form>
        <input type="hidden" name="assembly" value=<?php echo $assembly ?>>
        <input type="text" name="search" value=<?php echo $query ?>>
        <input type="submit" value="Search">
        </form>
        <?php
    }

    private function displayGenes($assembly)
    {
        global $config;
        global $mysqli;

        if (isset($_GET['query'])) {
            $query = $_GET['query'];
        } else {
            $query = "name";
        }

        if (isset($_GET['page'])) {
            $pageNum = intval($_GET['page']);
            if ($pageNum < 1) {
                $pageNum = 1;
            }
            $offset = ($pageNum - 1) * 10;
        } else {
            $pageNum = 1;
            $offset = 0;
        }

        //echo "Index to genes<br>\n";
        if (isset($_GET['search'])) {
            $query = $_GET['search'];
            $param = "%" . $query . "%";
            $sql = "select gene_annotation_uid, gene_id, transcript, type, gene_name, description, bin, uniprot, goa, function from gene_annotations
                where assembly_name = ? 
                and (description like ? OR gene_name like ? OR gene_id like ? OR function like ? OR goa like ?)";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("ssssss", $assembly, $param, $param, $param, $param, $param) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->store_result();
            $count_rows = $stmt->num_rows;
            $sql .= " limit 10 offset $offset";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("ssssss", $assembly, $param, $param, $param, $param, $param) or die(mysqli_error($mysqli));
            if ($count_rows > 10) {
                $next = $pageNum + 1;
                $prev = $pageNum -1;
                echo "<br><button type=\"button\" onclick=\"javascript: nextPage($prev)\">Prev Page</button>";
                echo "<button type=\"button\" onclick=\"javascript: nextPage($next)\">Next Page</button>";
                echo "    $count_rows found, Page = $pageNum <br>";
            }
   
            $count = 0;
            $stmt->execute();
            $stmt->bind_result($uid, $gene_id, $transcript, $type, $gene_name, $desc, $bin, $uniprot, $goa, $function);
            while ($stmt->fetch()) {
                if ($count == 0) {
                    echo "<table><tr><td>Gene Id<td>Transcript<td>Name<td>Bin<td>Description<td>UniProt<td nowrap>gene ontology<td>Interpro Description\n";
                }
                $count++;
                echo "<tr><td><a href=\"view.php?table=gene_annotations&uid=$uid\">$gene_id</a><td>$transcript<td>$gene_name<td>$bin<td>$desc<td>$uniprot<td>$goa<td>$function\n";
            }
            $stmt->close();
            if ($count > 0) {
                echo "</table><br>";
            } else {
                echo "No matches found<br>\n";
            }
        }
    }
}
