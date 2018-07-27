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
        echo "The <b>Gene Id</b> link provides information on markers in T3 and external links to protein, expression, and pathway information.<br>\n";

        //get list of assemblies
        $sql = "select distinct(assemblies.assembly_uid), assemblies.assembly_name, data_public_flag, assemblies.description, assemblies.created_on
            from gene_annotations, assemblies
            where gene_annotations.assembly_name = assemblies.assembly_uid order by assemblies.created_on";
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
        if (isset($_GET['search'])) {
            $query = $_GET['search'];
        } else {
            $query = "";
        }
        ?>
        <form>
        Search Gene Id, Name, or Description:
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
            $sql = "select gene_annotation_uid, gene_id, transcript, gene_name, description, bin from gene_annotations
                where assembly_name = ? 
                and (description like ? OR gene_name like ? or gene_id like ?)";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("ssss", $assembly, $param, $param, $param) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->store_result();
            $count_rows = $stmt->num_rows;
            $sql .= " limit 10 offset $offset";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("ssss", $assembly, $param, $param, $param) or die(mysqli_error($mysqli));
        } else {
            $sql = "select gene_annotation_uid, gene_id, transcript, gene_name, description, bin from gene_annotations
                where assembly_name = \"$assembly\"
                order by if(gene_name = '' or gene_name is null, 1, 0), gene_name";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->store_result();
            $count_rows = $stmt->num_rows;
            $sql .= " limit 10 offset $offset";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
        }
        if ($count_rows > 10) {
            $next = $pageNum + 1;
            $prev = $pageNum -1;
            echo "<br>$count_rows found, Page = $pageNum ";
            echo "<button type=\"button\" onclick=\"javascript: nextPage($prev)\">Prev Page</button>";
            echo "<button type=\"button\" onclick=\"javascript: nextPage($next)\">Next Page</button>";
            echo "<br>";
        }
   
        $count = 0;
        $stmt->execute();
        $stmt->bind_result($uid, $gene_id, $transcript, $gene_name, $desc, $bin);
        while ($stmt->fetch()) {
            if ($count == 0) {
                echo "<table><tr><td>Gene Id<td>Transcript<td>Name<td>Bin<td>Description\n";
            }
            $count++;
            echo "<tr><td><a href=\"view.php?table=gene_annotations&uid=$uid\">$gene_id</a><td>$transcript<td>$gene_name<td>$bin<td>$desc\n";
        }
        $stmt->close();
        if ($count > 0) {
            echo "</table><br>";
        } else {
            echo "No matches found<br>\n";
        }
    }
}
