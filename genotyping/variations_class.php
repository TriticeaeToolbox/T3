<?php

namespace reports;

class Variations
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'displayVariations':
                $assembly = $_GET['assembly'];
                $this->displayVariations($assembly);
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

        echo "<h2>Variant Effects</h2>\n";
        echo "This page provides links to Sorting Intolerant From Tolerant (SIFT) and Variant Effect Predictor (VEP) to predict whether an amino acid substitution affects protein function.<br>";
        echo "SIFT missense predictions for genomes: <a target=\"_new\" href=\"http://www.nature.com/nprot/journal/v11/n1/abs/nprot.2015.123.html\">Nature Protocols 2016; 11:1-9</a>. ";
        echo "The Ensembl Variant Effect Predictor: Genome Biology Jun 6;17(1):122. (2016) <a target=\"_new\" href=\"https://genomebiology.biomedcentral.com/articles/10.1186/s13059-016-0974-4\">doi:10.1186/s13059-016-0974-4</a>.<br><br>";

        if (isset($_SESSION['clicked_buttons'])) {
            $selected_markers = $_SESSION['clicked_buttons'];
        } elseif (isset($_SESSION['geno_exps'])) {
            $geno_exp = $_SESSION['geno_exps'][0];
            $sql = "select marker_index from allele_byline_expidx where experiment_uid = $geno_exp";
            $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            if ($row = mysqli_fetch_row($result)) {
                $selected_markers = json_decode($row[0], true);
                $selected_markers_count = count($selected_markers);
                if ($selected_markers_count > 1000) {
                    echo "<br>Warning: $selected_markers_count markers selected. Truncating to 1000 markers.<br>\n";
                    $selected_markers = array_slice($selected_markers, 0, 1000);
                } else {
                    echo "<br>Found: $selected_markers_count markers in genotype experiment.<br>\n";
                }
            } else {
                die("Genotype experiment not found\n");
            }
        } else {
            echo "<br>Please select one or more <a href = \"genotyping/marker_selection.php\">markers</a><br></div>\n";
            require $config['root_dir'].'theme/footer.php';
            die();
        }
     
        //get list of assemblies
        $sql = "select distinct(assemblies.assembly_uid), assemblies.assembly_name,  data_public_flag, assemblies.description, assemblies.created_on
        from vep_annotations, assemblies
        where vep_annotations.assembly_name = assemblies.assembly_uid order by created_on";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            //pick latest assembly as default
            $uid = $row[0];
            $assembly = $uid;
            $assemblyList[$uid] = $row[1];
            $assemblyDesc[$uid] = $row[3];
        }
        //get assembly from genotype experiment if available
        if (isset($_SESSION['geno_exps'])) {
            $geno_exp = $_SESSION['geno_exps'][0];
            $sql = "select assembly_name from genotype_experiment_info where experiment_uid = $geno_exp";
            $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql<br>");
            if ($row = mysqli_fetch_row($result)) {
                if (preg_match("/[A-Z0-9]/", $row[0])) {
                    $assembly = $row[0];
                }
            }
        }

        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        } elseif (isset($_SESSION['assembly'])) {
            $assembly = $_SESSION['assembly'];
        }

        //display list of assemblies
        //echo "<br>Genome Assembly <select id=\"assembly\" onchange=\"reload()\">\n";
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
                echo "  To access additional assemblies <a href=\"login.php\">Login</a>.<br>";
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
        ?>
        </div>
        <div id="step2">
        <script type="text/javascript" src="genotyping/variations.js"></script>
        <?php
        $this->displayVariations($assembly);
        echo "</div></div>";
        include $config['root_dir'].'theme/footer.php';
    }

    private function displayVariations($assembly)
    {
        global $config;
        global $mysqli;
        global $varLink;
        global $browserLink;

        $assembly_name = mysql_grab("select assembly_name from assemblies where assembly_uid = $assembly");
        if (isset($_SESSION['clicked_buttons'])) {
            $selected_markers = $_SESSION['clicked_buttons'];
        } elseif (isset($_SESSION['geno_exps'])) {
            $geno_exp = $_SESSION['geno_exps'][0];
            $sql = "select marker_index from allele_byline_expidx where experiment_uid = $geno_exp";
            $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            if ($row = mysqli_fetch_row($result)) {
                $selected_markers = json_decode($row[0], true);
                $selected_markers_count = count($selected_markers);
                if ($selected_markers_count > 1000) {
                    echo "<br>Warning: $selected_markers_count markers selected. Truncating to 1000 markers.<br>\n";
                    $selected_markers = array_slice($selected_markers, 0, 1000);
                } else {
                    echo "<br>Found: $selected_markers_count markers in genotype experiment.<br>\n";
                }
            } else {
                die("Genotype experiment not found\n");
            }
        }

        $sql = "select marker_name, gene from qtl_annotations where assembly_name = \"$assembly\"";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $marker_name = $row[0];
            $gene = $row[1];
            $geneFound[$marker_name] = $gene;
        }

        $sql = "select gene_id, description from gene_annotations where assembly_name = \"$assembly\"";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            $gene = $row[0];
            $desc = $row[1];
            $geneDesc[$gene] = $desc;
        }

        $count_sel = count($selected_markers);
        if ($count_sel == 0) {
            echo "Warning: no markers selected<br>\n";
        }

        $linkOutIdx = array();
        $vepList = array();
        //echo "using assembly $assembly<br>\n";
        /* check in loaded file first if not found then check marker_report_reference */
        foreach ($selected_markers as $marker_uid) {
            //echo "marker = $marker_uid<br>\n";
            $found = 0;
            $geno_exp = $_SESSION['geno_exps'][0];
            $sql = "select marker_name from markers where marker_uid = $marker_uid";
            $sql = "select marker_name, feature, consequence, impact from vep_annotations where marker_uid = $marker_uid and assembly_name = \"$assembly\"";
            $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            if ($row = mysqli_fetch_row($result)) {
                $found = 1;
                $marker_name = $row[0];
                $feature = $row[1];
                $consequence = $row[2];
                $impact = $row[3];
                $jbrowse = "";
                $linkOut = "<tr><td><a href=\"" . $config['base_url'] . "view.php?table=markers&name=$marker_name\">$marker_name</a><td>$jbrowse";
                //$linkOutSort[] = "$linkOut<td>$link<td>$desc<td>$feature<td>$consequence<td>$impact";
                //$linkOutIndx[] = $chrom . $pos;
            }

                $sql = "select markers.marker_name, chrom, bin, pos, A_allele, B_allele, strand from marker_report_reference, markers
                where marker_report_reference.marker_uid = markers.marker_uid
                and assembly_name = \"$assembly_name\"
                and marker_report_reference.marker_uid = $marker_uid";
                //echo "$sql<br>\n";
                $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli . "<br>$sql<br>"));
                if ($row = mysqli_fetch_row($result)) {
                    $marker_name = $row[0];
                    $chrom = $row[1];
                    $bin = $row[2];
                    $pos = $row[3];
                    $strand = $row[6];
                    $start = $pos - 1000;
                    if ($start < 0) {
                        $start = 0;
                    }
                    $stop = $pos + 1000;
                    if ($strand == "F") {
                        $strand = "+";
                    } elseif ($strand == "R") {
                        $strand = "-";
                    }
                    if (empty($bin)) {
                        $bin = $chrom;
                    }
                    if (isset($browserLink[$assembly_name])) {
                        $link = $browserLink[$assembly_name];
                        if (preg_match("/ensemb/", $link)) {
                            $jbrowse = "<a target=\"_new\" href=\"" . $browserLink[$assembly_name] . "$bin:$start-$stop\">$bin:$pos</a>";
                        } else {
                            $jbrowse = "<a target=\"_new\" href=\"" . $browserLink[$assembly_name] . "$chrom:$start..$stop\">$chrom:$pos</a>";
                        }
                    } else {
                        $jbrowse = "$chrom:$pos";
                    }
                    $linkOut = "<tr><td><a href=\"" . $config['base_url'] . "view.php?table=markers&name=$marker_name\">$marker_name</a><td>$jbrowse";
                    if (isset($geneFound[$marker_name])) {
                        $gene = $geneFound[$marker_name];
                        $desc = $geneDesc[$gene];
                        $link = "<a target=\"_new\" href=" . $varLink[$assembly_name] . "?g=$gene>$gene</a>";
                    } else {
                        $gene = "";
                        $desc = "";
                        $link = "";
                    }
                    if ($found) {
                        $linkOutSort[] = "$linkOut<td>$link<td>$desc<td>$feature<td>$consequence<td>$impact";
                        $linkOutIndx[] = $chrom . $pos;
                    } else {
                        $linkOutSort[] = "$linkOut<td>$link<td>$desc";
                        $linkOutIndx[] = $chrom . $pos;
                    }
                } else {
                    $notFound .= "$marker_name<br>\n";
                }
            
        }

        if ($count_vep_list > 0) {
            $header = "<tr><td>marker<td>region<td>gene<td>description<td>feature<td>consequence<td>impact";
        } else {
            $header = "<tr><td>marker<td>region<td>gene<td>description";
        }

        $count = count($linkOutIndx);
        //echo "$count matches found<br><br>\n";
        $unique_str = chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80));
        if ($count > 0) {
            asort($linkOutIndx);
            echo "The links in the region column show known variations in a genome browser and their effects. The region is 1000 bases to either side of marker.<br>";
            echo "The links in the gene column show a table with known variations, consequence type, and SIFT score.<br>\n";
            if ($selected_markers_count > 1000) {
                $dir = "/tmp/tht/";
                $filename = $dir . "ensembl_links_" . $unique_str . ".html";
                ?>
                <input type="button" value="Open Annotation File"
                onclick="javascript:window.open('<?php echo $filename ?>');"><br><br>
                <?php
                $h = fopen($filename, "w");
                fwrite($h, "<html lang=\"en\"><table>$header\n");
                foreach ($linkOutIndx as $key => $val) {
                    fwrite($h, $linkOutSort[$key]);
                }
                fwrite($h, "</table>");
                fclose($h);
            } else {
                echo "<table>$header\n";
                foreach ($linkOutIndx as $key => $val) {
                    echo "$linkOutSort[$key]\n";
                }
                echo "</table>\n";
            }
        }
    }
}
