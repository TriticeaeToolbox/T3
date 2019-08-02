<?php

namespace reports;

class Protein
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

        echo "<h2>Search Protein Annotation</h2>";
        echo "The protein annotation is provided by UniProt and includes manualy curated (Swiss-Prot) and automatically annotated (TrEMBL) databases.<br>\n";
        echo "The <b>Protein Id</b> link provides a reference link to UniProt for additional function and biological knowledge.<br>\n";
        echo "The <b>Gene Id</b> link provides information on gene location.<br>";

        ?>
        </table></form><br>
        <div id="step1">
        <?php
        $this->displayQuery($assembly);
        ?>
        </div>
        <div id="step2">
        <script type="text/javascript" src="protein/genes.js"></script>
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
        Search Gene Id, Name, Description, or Functional<br>
        Example Queries:
        <?php
        echo "<a href=" . $config['base_url'] . "protein?assembly=" . $assembly . "&search=Maturase>Maturase</a>, ";
        //echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=TraesCS3D02G273600>TraesCS3D02G273600</a>, ";
        echo "<a href=" . $config['base_url'] . "protein/?assembly=" . $assembly . "&search=rust>rust</a>, ";
        //echo "<a href=" . $config['base_url'] . "genes/?assembly=" . $assembly . "&search=abp1>abp1</a>, ";
        echo "<a href=" . $config['base_url'] . "protein/?assembly=" . $assembly . "&search=Dormancy>Dormancy</a><br><br>";
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
            //get links to gene
            $sql = "select gene_annotation_uid, transcript from gene_annotations";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->bind_result($uid, $transcript);
            while ($stmt->fetch()) {
                $gene_link[$transcript] = $uid;
            }
            
            $query = $_GET['search'];
            $param = "%" . $query . "%";
            $sql = "select prot_annotation_uid, prot_id, prot_name, gene_name, gene_link, function, go_process, go_function from protein_annotations
                where (prot_id like ? OR prot_name like ? OR gene_name like ? OR function like ? OR go_function like ? OR go_process like ?)";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("ssssss", $param, $param, $param, $param, $param, $param) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->store_result();
            $count_rows = $stmt->num_rows;
            $sql .= " limit 10 offset $offset";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("ssssss", $param, $param, $param, $param, $param, $param) or die(mysqli_error($mysqli));
            if ($count_rows > 10) {
                $next = $pageNum + 1;
                $prev = $pageNum -1;
                echo "<br><button type=\"button\" onclick=\"javascript: nextPage($prev)\">Prev Page</button>";
                echo "<button type=\"button\" onclick=\"javascript: nextPage($next)\">Next Page</button>";
                echo "    $count_rows found, Page = $pageNum <br>";
            }
   
            $count = 0;
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($uid, $prot_id, $prot_name, $gene_name, $transcript, $function, $go_process, $go_function);
            while ($stmt->fetch()) {
                if ($count == 0) {
                    echo "<table><tr><td>Protein Id<td>Protein Name<td>Gene Name<td>Gene Id<td>Function<td>GO Process<td>GO Function\n";
                }
                $count++;
                if (isset($gene_link[$transcript])) {
                    $gene_uid = $gene_link[$transcript];
                } else {
                    $gene_uid = "";
                }
                echo "<tr><td><a href=\"view.php?table=protein_annotations&uid=$uid\">$prot_id</a><td>$prot_name<td>$gene_name";
                echo "<td><a href=\"view.php?table=gene_annotations&uid=$gene_uid\">$transcript</a><td>$function<td>$go_process<td>$go_function<td>$uniprot<td>$goa\n";
            }
            if ($count > 0) {
                echo "</table><br>";
            } else {
                echo "No matches found<br>\n";
            }
        }
    }
}
