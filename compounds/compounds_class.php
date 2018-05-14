<?php

namespace reports;

class Compounds
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'displayQuery':
                $assembly = $_GET['assembly'];
                $this->displayQuery($assembly);
                break;
            case 'displayCompounds':
                $assembly = $_GET['assembly'];
                $this->displayComp();
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

        echo "<h2>Compounds</h2>";
        echo "The compounds listed on this page were measured using LC-MS Phenyl-Hexyl Analysis or GC-MS Non-targeted Analysis. ";
        echo "GC-MS profiling analyses uses extracts that are chemically converted, i.e. derivatized into less polar and volatile compounds, to analytes. ";
        echo "The link in the <b>Analyte Name</b> shows experiments in which this analyte has been measured. ";
        echo "The link in the <b>Analyte Reference</b> shows more details of the analyte including links to the corresponding metabolite. ";
        echo "For more information see <a href=\"http://gmd.mpimp-golm.mpg.de/dataentities.aspx\" target = \"_new\">Golm data entities</a>.<br><br>";
        ?>
        <div id="step1">
        <?php
        $this->displayQuery();
        ?>
        </div>
        <div id="step2">
        <script type="text/javascript" src="compounds/compounds.js"></script>
        <?php
        $this->displayComp();
        echo "</div></div>";
        include $config['root_dir'].'theme/footer.php';
    }

    private function displayQuery()
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


    private function displayComp()
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
            $offset = ($pageNum - 1) * 10;
        } else {
            $pageNum = 1;
            $offset = 0;
        }

        //echo "Index to genes<br>\n";
        if (isset($_GET['search'])) {
            $query = $_GET['search'];
            $param = "%" . $query . "%";
            $sql = "select compound_uid, compound_name, retention, compound_reference, formula from compounds
                where compound_name like ?";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("s", $param) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->store_result();
            $count_rows = $stmt->num_rows;
            $sql .= " limit 10 offset $offset";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->bind_param("s", $param) or die(mysqli_error($mysqli));
        } else {
            $sql = "select compound_uid, compound_name, retention, compound_reference, formula from compounds order by compound_name";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
            $stmt->execute();
            $stmt->store_result();
            $count_rows = $stmt->num_rows;
            $sql .= " limit 10 offset $offset";
            $stmt = $mysqli->prepare($sql) or die(mysqli_error($mysqli));
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
        $stmt->execute();
        $stmt->bind_result($uid, $compound_name, $retention, $ref, $form);
        echo "<table><tr><td>Analyte Name<td>Retention Time<td>Analyte Reference<td>Formula\n";
        while ($stmt->fetch()) {
            $count++;
            echo "<tr><td><a href=\"view.php?table=compounds&uid=$uid\">$compound_name</a><td>$retention<td>$ref<td>$form\n";
        }
        $stmt->close();
        if ($count > 0) {
            echo "</table><br>";
        } else {
            echo "No matches found<br>\n";
        }
    }
}
