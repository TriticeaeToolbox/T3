<?php

namespace reports;

class Genes
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'displayGenes':
                $this->displayGenes();
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
        $sql = "select distinct(assemblies.assembly_name), data_public_flag, assemblies.description, created_on from gene_annotations, assemblies
            where gene_annotations.assembly_name = assemblies.assembly_name  order by created_on";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            //pick latest assembly as default
            if ($row[1] == 1) {
                $assembly = $row[0];
                $assembly = $row[0];
                $assemblyList[] = $row[0];
                $assemblyDesc[] = $row[2];
                // do not show ones that are private
            } elseif (($row[1] == 0) && authenticate(array(USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR))) {
                $assembly = $row[0];
                $assemblyList[] = $row[0];
                $assemblyDesc[] = $row[2];
            }
        }

        //display list of assemblies
        echo "<br><form><table><tr><td>Genome Assembly<td>Description";
        foreach ($assemblyList as $key => $ver) {
            if ($ver == $assembly) {
                $selected = "checked";
            } else {
                $selected = "";
            }
            echo "<tr><td nowrap><input type=\"radio\" name=\"assembly\" id=\"assembly\" value=\"$ver\" $selected> $ver<td>$assemblyDesc[$key]\n";
        }
        $sql = "select * from assemblies where data_public_flag = 0";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($result)) {
            if (!authenticate(array(USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR))) {
                echo "<tr><td><a href=\"login.php\">Login</a><td>To access additional assemblies";
            }
        }
        if (isset($_GET['search'])) {
            $query = $_GET['search'];
        } else {
            $query = "";
        }
        ?>
        </table></form><br>
        <form>
        Search Gene Id, Name, or Description:
        <input type="text" name="search" value=<?php echo $query ?>>
        <input type="submit" value="Search">
        </form>
        <div id="step2">
        <script type="text/javascript" src="genes/genes.js"></script>
        <?php
        $this->displayGenes($assembly);
        echo "</div></div>";
        include $config['root_dir'].'theme/footer.php';
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

        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        }

        if (isset($_GET['page'])) {
            $pageNum = intval($_GET['page']);
            $offset = ($pageNum - 1) * 10;
        } else {
            $pageNum = 1;
            $offset = 0;
        }

        //echo "Index to genes<br>\n";
        if (isset($_GET['search'])) {
            $query = $_GET['search'];
            $sql = "select gene_annotation_uid, gene_id, type, gene_name, description, bin from gene_annotations
                where assembly_name = \"$assembly\"
                and (description like \"%$query%\" OR gene_name like \"%$query%\" or gene_id like \"%$query%\")";
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
            $count_rows = mysqli_num_rows($res);
        } else {
            $sql = "select gene_annotation_uid, gene_id, type, gene_name, description, bin from gene_annotations
                where assembly_name = \"$assembly\"
                and type = \"gene\" order by if(gene_name = '' or gene_name is null, 1, 0), gene_name";
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            $count_rows = mysqli_num_rows($res);
        }
        echo "<br>$count_rows found\n";
        if ($count_rows > 10) {
            $next = $pageNum + 1;
            $prev = $pageNum -1;
            echo ", Page = $pageNum";
            if ($pageNum == 1) {
                echo " <button type=\"button\" onclick=\"javascript: nextPage($next)\">Next Page</button><br>";
            } else {
                echo " <button type=\"button\" onclick=\"javascript: nextPage($prev)\">Prev Page</button>";
                echo " <button type=\"button\" onclick=\"javascript: nextPage($next)\">Next Page</button><br>";
            }
        }
   
        $count = 0;
        $sql .= " limit 10 offset $offset";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            if ($count == 0) {
                echo "<table><tr><td>Gene Id<td>Type<td>Name<td>Bin<td>Description\n";
            }
            $count++;
            $uid = $row[0];
            $gene_id = $row[1];
            $type = $row[2];
            $gene_name = $row[3];
            $desc = urldecode($row[4]);
            $bin = $row[5];
            $anchor = substr($row[3], 0, 1);
            $anchor = strtoupper($anchor);
            if ($anchor != $prev) {
                echo "<a id=\"$anchor\"></a> ";
                $prev = $anchor;
            }
            echo "<tr><td><a href=\"view.php?table=gene_annotations&uid=$uid\">$gene_id</a><td>$type<td>$gene_name<td>$bin<td>$desc\n";
        }
        if ($count > 0) {
            echo "</table><br>";
        } else {
            echo "No matches found<br>$sql\n";
        }
    }
}
