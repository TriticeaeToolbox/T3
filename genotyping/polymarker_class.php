<?php

namespace T3;

class DownloadPrimers
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'download':
                $this->download();
                break;
            default:
                $this->display();
                break;
        }
    }

    private function display()
    {
        global $config;
        global $mysqli;
        if (isset($_GET['marker'])) {
            $marker = $_GET['marker'];
            $selected = explode(",", $marker);
        } elseif (isset($_SESSION['clicked_buttons'])) {
            $selected = $_SESSION['clicked_buttons'];
        }
        $count = count($selected);

        $header = array("ID","SNP (position and change in the SNP)","Region Size (size of the aligned sequence)","chromosome","number of chromosomes where the marker hits","regions where contig is found","SNP_type","A Primer for first allele","B Primer for second allele","common primer","primer type","orientation of the first primer","A_TM, melting point of first primer","B_TM, melting point of second primer","melting point of common primer","selected from","product size","errors","is repetitive","hit_count, how many regions the marker maps");
        $sql = "select marker_name, results from marker_primers where marker_uid = ?";
        $stmt = $mysqli->prepare($sql);
        include $config['root_dir'].'theme/admin_header2.php';
        echo "<h2>PolyMarker designed primers</h2>";
        echo "The PolyMarker program was used to design primers on all the markers in the <a href=display_genotype.php?trial_code=2017_WheatCAP>2017_WheatCAP</a> experiment. ";
        echo "<a href=\"genotyping/marker_selection.php\">Select a marker</a> using the \"Wheat CAP 2017\" map to see design results.";
        echo "<br>Description of the design process: <a href=genotyping/20180821_mapping_stats.pdf>PolyMarker for WheatCAP</a>, <a href=http://www.wheat-training.com/wp-content/uploads/TILLING/pdfs/Designing-genome-specific-primers.pdf target=_blank>Designing genome specific primers</a>";
        echo "<br>Visual interface for selecting primers: <a href=\"/jbrowse/?data=wheat2016&tracks=Primers 2017_WheatCAP\" target=\"_blank\">JBrowse</a>";
        echo "<br>Website for polymarker program: <a href=\"http://polymarker.tgac.ac.uk\">polymarker.tgac.ac.uk</a>";
        echo "<br>Description of the polymarker program: <a href=\"https://academic.oup.com/bioinformatics/article/31/12/2038/213995\" target=\"_blank\">PolyMarker: A Fast polyploid primer design pipeline</a><br>";
        echo "<br>Interpreting output:<ul>The <b>SNP type</b>, non-homoeologous SNPs are preferred  <li>homoeologous (polymorphic between the genomes) <li>non-homoeologous (monomorphic in all the genome)</ul>";
        echo "<ul>The <b>Primer type</b>, chromosome_specific primers are preferred <li>chromosome_specific</b>: genome specific to the target chromosome. <li>chromosome_semispecific</b>: discriminates between the target genome and one, but not both, of the other two genomes. <li>chromosome_nonspecific</b>: no variation between the target genome and non-target genome</ul>";
        if ($count < 2) {
            $found = 0;
            foreach ($selected as $marker) {
                $stmt->bind_param("s", $marker);
                $stmt->bind_result($marker_name, $results);
                if ($stmt->execute()) {
                    if ($stmt->fetch()) {
                        if ($found == 0) {
                            echo "<h3>Designed Primers</h3>";
                        }
                        $found++;
                        $line = explode(",", $results);
                        echo "<table>";
                        echo "<tr><td>Marker Name<td>$marker_name\n";
                        foreach ($line as $key => $val) {
                            echo "<tr><td>$header[$key]<td>$val\n";
                        }
                        echo "</table><br>";
                    }
                }
            }
            $stmt->close();
        } else {
            echo "<h3>Designed Primers</h3>";
            echo "<a href=" . $config['base_url'] . "/genotyping/polymarker.php?function=download>Download designed primers</a><br></div>\n";
        }
        echo "</div>";
        include $config['root_dir'].'theme/footer.php';
    }

    private function download()
    {
        global $mysqli;
        if (isset($_GET['marker'])) {
            $marker = $_GET['marker'];
            $selected = explode(",", $marker);
        } elseif (isset($_SESSION['clicked_buttons'])) {
            $selected = $_SESSION['clicked_buttons'];
        }

        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=Markers.csv");
        $header = array("ID","SNP (position and change in the SNP)","Region Size (size of the aligned sequence)","chromosome","number of chromosomes where the marker hits","regions where contig is found","SNP_type","A Primer for first allele","B Primer for second allele","common primer","primer type","orientation of the first primer","A_TM, melting point of first primer","B_TM, melting point of second primer","melting point of common primer","selected from","product size","errors","is repetitive","hit_count, how many regions the marker maps");
        $sql = "select marker_name, results from marker_primers where marker_uid = ?";
        $stmt = $mysqli->prepare($sql);
        $header = "\"" . implode("\",\"", $header) . "\"";
        echo "Marker Name,$header\n";
        foreach ($selected as $marker) {
            $stmt->bind_param("s", $marker);
            $stmt->bind_result($marker_name, $results);
            if ($stmt->execute()) {
                if ($stmt->fetch()) {
                    $count++;
                    $line = explode(",", $results);
                    echo "$marker_name,$results\n";
                }
            }
        }
        $stmt->close();
    }
}
