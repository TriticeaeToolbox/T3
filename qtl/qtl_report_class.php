<?php

namespace T3;

class Downloads
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'step2phenotype':
                $this->step2Phenotype();
                break;
            case 'downloadQTL':
                $this->downloadQTL();
                break;
            case 'displayQTL':
                $this->displayQTL();
                break;
            case 'search':
                $this->displaySearch();
                break;
            case 'downloadDetailQTL':
                $this->downloadDetailQTL();
                break;
            case 'detail':
                $this->displayDetail();
                break;
            case 'refreshtitle':
                $this->refreshTitle();
                break;
            default:
                $this->type1Select();
                break;
        }
    }

    private function type1Select()
    {
        global $config;
        include $config['root_dir'].'theme/admin_header2.php';
        ?>
        <table>
        </table>
        <div id="title">
        <?php
        if (isset($_GET['pi'])) {
            $var = intval($_GET['pi']);
            $_SESSION['selected_traits'] = array($var);
            ?>
            <img alt="spinner" id="spinner" src="images/ajax-loader.gif" style="display:none;" />
            <?php
        }
        $this->refreshTitle();
        ?>
        </div>
        <div id="step1" style="float: left; margin-bottom: 1.5em;">
        <script type="text/javascript" src="qtl/menu15.js"></script><br>
        <?php
        if (isset($_SESSION['selected_traits']) || isset($_SESSION['selected_trials'])) {
            ?>
            <script type="text/javascript">
            if ( window.addEventListener ) {
                window.addEventListener( "load", display_qtl(), false );
            } else if ( window.onload ) {
                window.onload = "display_qtl()";
            }
            </script>
            <?php
        } else {
            $this->step1Phenotype();
        }
        ?>
        </div>
        <div id="step2" style="float: left; margin-bottom: 1.5em;"></div>
        <div id="setp2b" style="clear: both; margin-bottom: 1.5em;">
        <img alt="spinner" id="spinner" src="images/ajax-loader.gif" style="display:none;" /></div>
        <div id="step3" style="clear: both; margin-bottom: 1.5em;"></div>
        <div id="step4" style="float: left; margin-bottom: 1.5em;"></div>
        </div>
        <?php
        include $config['root_dir'].'theme/footer.php';
    }


    private function refreshTitle()
    {
        global $mysqli;
        global $config;
        if (isset($_GET['cmd'])) {
            $cmd = $_GET['cmd'];
        }
        ?>
        <h2>GWAS Results</h2>
        This analysis can be used to identify quantitative trait locus (QTL) by displaying associations between
        markers and traits for trials within the T3 database.
        If <a href='<?php echo $config['base_url']; ?>/phenotype/phenotype_selection.php'>traits and trials</a>
        are selected then only results for the selected trials are shown otherwise results are shown for all
        trials.
        <a href='<?php echo $config['base_url']; ?>/qtl/zbrowse.html'>ZBrowse instructions</a>. 
        Expression data is provided by "Wheat Expression Browser: expVIP" and "EMBL-EBI: Expression Atlas".
        <br><br>
        <b>Analysis Methods:</b> The analysis includes genotype and phenotype trials where there were more than 50
        germplasm lines in common.<br>
        1. phenotype trial - GWAS with no fixed effects.<br>
        2. phenotype experiment - GWAS on a set of related phenotype trials 
        (different location and same year or same location different year).
        Principle Components that accounted for more than 5% of the relationship matrix variance were included as fixed effects in the analysis.
        Each phenotype trial (if more than one) was included as a fixed effect.<br>
        <!---3. GWAS is done on each phenotype trial, no fixed effects. The genotype data is imputed with 1.2M SNP HapMap panel.
        Beagle version 4.0 was used for phasing and imputation.<br><br>-->
        <b>GWAS:</b> The analysis use rrBLUP GWAS package (Endleman, Jeffery, "Ridge Regression and Other Kernels for Genomic Selection with R package rrBLUP", The Plant Genome Vol 4 no. 3).
        The settings are: MAF > 0.05, P3D = TRUE (equivalent to EMMAX).
        The q-value is an estimate of significance given p-values from multiple comparisons using a false discovery rate of 0.05.<br>
        The Z-score is a average of the p-values, calculated as Z = (1/Trial Count) *  &#931 abs( qnorm( p-value / 2 ))<br>
        To view the p-value and q-value for each trial, select the trial count link.<br>
        <?php
        $trialCount = 0;
        $platforms = "";
        $sql = "select count(*) from qtl_raw";
        $res = mysqli_query($mysqli, $sql);
        if ($row = mysqli_fetch_array($res)) {
            $trialCount = $row[0];
        }
        $sql = "select distinct(platform_name) from qtl_raw, platform where qtl_raw.platform = platform.platform_uid";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_array($res)) {
            if ($platforms == "") {
                $platforms = $row[0];
            } else {
                $platforms .= ", $row[0]";
            }
        }
        echo "<b>Phenotype Trials:</b> $trialCount<br>\n";
        echo "<b>Genotype platforms:</b> $platforms<br>\n";

        //get list of assemblies
        $assembly = "";
        $sql = "select distinct(assemblies.assembly_uid), assemblies.assembly_name, assemblies.description, created_on
            from qtl_annotations, assemblies
            where qtl_annotations.assembly_name = assemblies.assembly_uid order by created_on";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        while ($row = mysqli_fetch_row($result)) {
            //pick latest assembly as default
            $uid = $row[0];
            $assembly = $uid;
            $assemblyList[$uid] = $row[1];
            $assemblyDesc[$uid] = $row[2];
        }
        if (empty($assembly)) { //if not genes then just get assemblies
            $sql = "select assembly_uid, assembly_name, description from assemblies";
            $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            while ($row = mysqli_fetch_row($result)) {
                $uid = $row[0];
                $assembly = $uid;
                $assemblyList[$uid] = $row[1];
                $assemblyDesc[$uid] = $row[2];
            }
        }

        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        }

        //display list of assemblies
        echo "<br><form><table><tr><td>Genome Assembly<td>Description";
        foreach ($assemblyList as $key => $ver) {
            if ($key == $assembly) {
                $selected = "checked";
                echo "<tr><td nowrap><input type=\"radio\" name=\"assembly\" value=\"$key\" $selected> $ver<td>$assemblyDesc[$key]<br>";
            }
        }
        $sql = "select * from assemblies where data_public_flag = 0";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        if ($row = mysqli_fetch_row($result)) {
            if (!authenticate(array(USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR))) {
                echo "<tr><td><a href=\"login.php\">Login</a><td>To access additional assemblies";
            }
        }
        echo "</table></form><br>";

        if ($cmd == "clear") {
            unset($GLOBALS[_SESSION]['selected_traits']);
            unset($GLOBALS[_SESSION]['selected_trials']);
        } elseif (isset($_SESSION['selected_traits'])) {
            $ntraits=count($_SESSION['selected_traits']);
            echo "<table>";
            echo "<tr><th>Currently selected traits</th><td><th>Currently selected trials</th>";
            print "<tr><td><select name=\"deselLines[]\" multiple=\"multiple\">";
            $phenotype_ary = $_SESSION['selected_traits'];
            foreach ($phenotype_ary as $uid) {
                $result=mysqli_query($mysqli, "select phenotypes_name from phenotypes where phenotype_uid=$uid") or die("invalid line uid\n");
                while ($row=mysqli_fetch_assoc($result)) {
                    $selval=$row['phenotypes_name'];
                    print "<option value=\"$uid\" >$selval</option>\n";
                }
            }
            print "</select>";
            echo "<td><td><select name=\"deseLines[]\" multiple=\"multiple\">";
            if (isset($_SESSION['selected_trials'])) {
                $trials_ary = $_SESSION['selected_trials'];
                foreach ($trials_ary as $uid) {
                    $result=mysqli_query($mysqli, "select trial_code from experiments where experiment_uid=$uid") or die("invalid line uid\n");
                    while ($row=mysqli_fetch_assoc($result)) {
                        $selval=$row['trial_code'];
                        print "<option value=\"$uid\" >$selval</option>\n";
                    }
                }
            }
            print "</select>";
            ?>
            </table>
            <input type="button" value="Deselect traits and trials" onclick="javascript:deselect();" />
            <?php
        }
    }

    private function step1Phenotype()
    {
        global $mysqli;
        ?>
        <table id="phenotypeSelTab" class="tableclass1">
            <tr>
            <th>Category</th>
            </tr>
            <tr><td>
            <select name="phenotype_categories" id="pheno_cat" multiple="multiple" style="height: 12em;" onchange="javascript: update_phenotype_categories(this.options)">
            <?php
            $sql = "SELECT distinct(phenotype_category.phenotype_category_uid) AS id, phenotype_category_name AS name from phenotype_category, phenotypes
                    where phenotype_category.phenotype_category_uid = phenotypes.phenotype_category_uid";
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
            while ($row = mysqli_fetch_assoc($res)) { ?>
                <option value="<?php echo $row['id'] ?>">
                <?php echo $row['name'] ?>
                </option>
                <?php
            }
            ?>
            </select>
            </td>
            </table>
            <?php
    }

    private function step2Phenotype()
    {
        global $mysqli;
        $phen_cat = $_GET['pc'];
        $lines_within = $_GET['lw'];
        if (isset($_SESSION['selected_lines'])) {
             $selectedlines= $_SESSION['selected_lines'];
             $selectedlines = implode(',', $selectedlines);
        }
        ?><br>
        <table id="phenotypeSelTab" class="tableclass1">
                <tr>
                        <th>Traits</th>
                </tr>
                <tr><td>
                <select id="pheno_itm" name="phenotype_items" multiple="multiple" style="height: 12em;" onClick="javascript: update_phenotype_items(this.options)">
                <?php

                if ($lines_within == "yes") {
                    $sql = "SELECT DISTINCT phenotypes.phenotype_uid AS id, phenotypes_name AS name from phenotypes, phenotype_category, phenotype_data, line_records, tht_base
                    where phenotypes.phenotype_uid = phenotype_data.phenotype_uid
                    AND phenotypes.phenotype_category_uid = phenotype_category.phenotype_category_uid
                    AND phenotype_data.tht_base_uid = tht_base.tht_base_uid 
                    AND line_records.line_record_uid = tht_base.line_record_uid 
                    AND phenotype_category.phenotype_category_uid in ($phen_cat)
                    AND line_records.line_record_uid IN ($selectedlines)
                    ORDER BY name";
                } else {
                    $sql = "SELECT phenotype_uid AS id, phenotypes_name AS name from phenotypes, phenotype_category
                    where phenotypes.phenotype_category_uid = phenotype_category.phenotype_category_uid
                    AND phenotype_category.phenotype_category_uid in ($phen_cat)
                    ORDER BY name";
                }
                $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
                while ($row = mysqli_fetch_assoc($res)) { ?>
                    <option value="<?php echo $row['id'] ?>">
                    <?php echo $row['name'] ?>
                    </option>
                    <?php
                }
                ?>
                </select>
                </table>
                <?php
    }

    private function displaySearch()
    {
        global $mysqli;
        global $config;
        include $config['root_dir'].'theme/admin_header2.php';
        echo "<h2>GWAS Results</h2>";

        $database = "qtl_raw";
        $marker_name = $_GET['marker'];

        if (preg_match("/([A-Za-z]+)\/[^\/]+\/[^\/]+$/", $_SERVER['PHP_SELF'], $match)) {
            $species = $match[1];
        } else {
            $species = "";
        }
        $target_url = $config['base_url'] . "raw/gwas_$species/single/";

        $sql = "select experiment_uid, trial_code from experiments";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $uid = $row[0];
            $trial_code = $row[1];
            $trial_list[$uid] = $trial_code;
        }

        $sql = "select phenotype_uid, phenotypes_name from phenotypes";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $uid = $row[0];
            $phenotype = $row[1];
            $pheno_list[$uid] = $phenotype;
        }

        $marker_safe = htmlspecialchars($marker_name);
        echo "marker name = $marker_safe<br>\n";
        echo "<table><tr><td>marker<td>phenotype<td>chrom<td>pos<td>z-score<td>q-value<td>p-value<td>phenotype trial<td>genotype trial<td>plot";
        $sql = "select phenotype_uid, genotype_exp, phenotype_exp, gwas from $database";
        $res = mysqli_query($mysqli, $sql, MYSQLI_USE_RESULT) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $puid = $row[0];
            $gexp = $row[1];
            $pexp = $row[2];
            $phenotype = $pheno_list[$puid];
            $gwas = json_decode($row[3]);
            foreach ($gwas as $val) {
                $marker = $val[0];
                $chrom = $val[1];
                $pos = $val[2];
                $pval = $val[5];
                if (($marker == $marker_name) && ($pval < 0.05)) {
                    $zvalue = number_format($val[3], 3);
                    $qvalue = number_format($val[4], 3);
                    $pvalue = number_format($val[5], 5);
                    $location = "$chrom $pos";
                    $link1 = "/jbrowse/?data=wheat&loc=$chrom:$pos";
                    if ($database == "qtl_imputed") {
                        $link2 = "<a target=\"_new\" href=\"$target_url" . $chrom . "THTdownload_gwa1_" . $gexp . "_" . $pexp . "_" . $puid . ".png\">Manhattan</a>";
                        $link3 = "<a target=\"_new\" href=\"$target_url" . $chrom . "THTdownload_gwa3_" . $gexp . "_" . $pexp . "_" . $puid . ".png\">Q-Q</a>";
                    } else {
                        $link2 = "<a target=\"_new\" href=\"$target_url" . "THTdownload_gwa1_" . $gexp . "_" . $pexp . "_" . $puid . ".png\">Manhattan</a>";
                        $link3 = "<a target=\"_new\" href=\"$target_url" . "THTdownload_gwa3_" . $gexp . "_" . $pexp . "_" . $puid . ".png\">Q-Q</a>";
                    }
                    if ($_GET['method'] == "set") {
                        $trial = $exp_list[$pexp];
                    } else {
                        $trial = "$trial_list[$pexp]";
                    }
                    echo "<tr><td>$marker<td>$phenotype<td>$chrom<td>$location<td>$zvalue<td>$qvalue<td>$pvalue<td>$trial<td>$trial_list[$gexp]<td>$link2 $link3\n";
                }
            }
        }
        mysqli_free_result($res);
        echo "</table>";
    }

    private function displayDetail()
    {
        global $mysqli;
        global $config;
        if (isset($_SESSION['selected_traits'])) {
            $phenotype_ary = $_SESSION['selected_traits'];
            $puid = $phenotype_ary[0];
        } elseif (isset($_GET['pi'])) {
            $puid = $_GET['pi'];
        } else {
            die("Error: no phenotypes selected\n");
        }
        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        } else {
            die("Error: select genome assembly\n");
        }
        $assembly_name = mysql_grab("select assembly_name from assemblies where assembly_uid = $assembly");
        if (preg_match("/([A-Za-z]+)\/[^\/]+\/[^\/]+$/", $_SERVER['PHP_SELF'], $match)) {
            $species = $match[1];
        } else {
            $species = "";
        }
        if (isset($_GET['method'])) {
            if ($_GET['method'] == 'set') {
                $database = "qtl_set";
                $select_set = "checked";
                $select_sig = "";
                $select_imput = "";
                $target_base = $config['root_dir'] . "raw/gwas_$species/set/";
                $target_url = $config['base_url'] . "raw/gwas_$species/set/";
            } elseif ($_GET['method'] == 'imput') {
                $database = 'qtl_imputed';
                $select_set = "";
                $select_sig = "";
                $select_imput = "checked";
                $target_base = $config['root_dir'] . "raw/gwas_$species/imput/";
                $target_url = $config['base_url'] . "raw/gwas_$species/imput/";
            } else {
                $database = "qtl_raw";
                $select_set = "";
                $select_sig = "checked";
                $select_imput = "";
                $target_base = $config['root_dir'] . "raw/gwas_$species/single/";
                $target_url = $config['base_url'] . "raw/gwas_$species/single/";
            }
        } else {
            $database = "qtl_raw";
            $select_set = "";
            $select_sig = "checked";
            $select_imput = "";
            $target_base = $config['root_dir'] . "raw/gwas_$species/single/";
            $target_url = $config['base_url'] . "raw/gwas_$species/single/";
        }

        $sql = "select experiment_uid, trial_code from experiments";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $uid = $row[0];
            $trial_code = $row[1];
            $trial_list[$uid] = $trial_code;
        }
        $sql = "select experiment_set_uid, experiment_set_name from experiment_set";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $uid = $row[0];
            $set_name = $row[1];
            $exp_list[$uid] = $set_name;
        }

        $sql = "select experiments.experiment_uid, platform_name from experiments, genotype_experiment_info, platform
            where experiments.experiment_uid = genotype_experiment_info.experiment_uid
            and genotype_experiment_info.platform_uid = platform.platform_uid";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $uid = $row[0];
            $platform = $row[1];
            $platform_list[$uid] = $platform;
        }

        $muid = "none";
        $guid = "none";
        if (isset($_GET['uid'])) {
            $muid = $_GET['uid'];
            echo "<h2>QTL results for marker = $muid</h2>\n";
            if (isset($annot_list1[$marker_name])) {
                $gene = $annot_list1[$marker_name];
                echo "gene = $gene<br>\n";
            }
        } elseif (isset($_GET['gene'])) {
            $guid = $_GET['gene'];
            echo "<h2>QTL results for gene = $guid</h2>\n";
        }

        $sql = "select experiment_uid, number_entries from phenotype_experiment_info";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $pheno_exp = $row[0];
            $count = $row[1];
            $linesInExp[$pheno_exp] = $row[1];
            //echo "$geno_exp $count<br>\n";
        }

        echo "<table><tr><td>marker<td>chrom<td>pos<td>z-score<td>q-value<td>p-value<td>phenotype trial (lines)<td>genotype trial<td>plot";
        $sql = "select genotype_exp, phenotype_exp, gwas from $database where phenotype_uid IN ($puid)";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $gexp = $row[0];
            $pexp = $row[1];
            $gwas = json_decode($row[2]);
            foreach ($gwas as $val) {
                $marker = $val[0];
                $chrom = $val[1];
                $pos = $val[2];
                if (($marker == $muid) || ($gene == $guid)) {
                    $zvalue = number_format($val[3], 3);
                    $qvalue = number_format($val[4], 3);
                    $pvalue = number_format($val[5], 5);
                    $sql = "select chrom, pos, bin from marker_report_reference where marker_name = \"$marker\" and assembly_name = \"$assembly_name\"";
                    $res2 = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
                    if ($row2 = mysqli_fetch_array($res2)) {
                        $chrom = $row2[0];
                        $pos = $row2[1];
                    } else {
                        $chrom = "";
                        $pos = "";
                    }
                    $link1 = "/jbrowse/?data=wheat&loc=$chrom:$pos";
                    $link2 = "<a target=\"_new\" href=\"$target_url" . "THTdownload_gwa1_" . $gexp . "_" . $pexp . "_" . $puid . ".png\">Manhattan</a>";
                    $link3 = "<a target=\"_new\" href=\"$target_url" . "THTdownload_gwa3_" . $gexp . "_" . $pexp . "_" . $puid . ".png\">Q-Q</a>";
                    if ($_GET['method'] == "set") {
                        $trial = $exp_list[$pexp];
                    } else {
                        $trial = "$trial_list[$pexp] ($linesInExp[$pexp])";
                    }
                    echo "<tr><td>$marker<td>$chrom<td>$pos<td>$zvalue<td>$qvalue<td>$pvalue<td>$trial<td>$trial_list[$gexp]<td>$link2 $link3\n";
                }
            }
        }
        echo "</table>";
    }

    private function downloadQTL()
    {
        global $mysqli;
        if (isset($_GET['pi'])) {
            $puid_list = explode(",", $_GET['pi']);
        }
        if (isset($_GET['method'])) {
            if ($_GET['method'] == 'set') {
                $database = "qtl_set";
            } elseif ($_GET['method'] == 'imput') {
                $database = 'qtl_imputed';
            } else {
                $database = "qtl_raw";
            }
        } else {
            $database = "qtl_raw";
        }
        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        } else {
            die("Error: select genome assembly\n");
        }

        $sql = "select marker_name, assembly_name, gene, description from qtl_annotations where assembly_name = 4";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $marker = $row[0];
            $gene = $row[2];
            $desc = $row[3];
            $annot_list[$marker] = $gene;
        }
        $sql = "select marker_name, assembly_name, gene, description from qtl_annotations where assembly_name = \"$assembly\"";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $marker = $row[0];
            $gene = $row[2];
            $desc = $row[3];
            $annot_list2[$marker] = $gene;
            $annot_list3[$marker] = $desc;
        }

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="qtl_meta.csv"');

        $sql = "select experiment_uid, number_entries from phenotype_experiment_info";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $pheno_exp = $row[0];
            $count = $row[1];
            $linesInExp[$pheno_exp] = $row[1];
            //echo "$geno_exp $count<br>\n";
        }

        foreach ($puid_list as $puid) {
            $sql = "select phenotype_exp, gwas from $database where phenotype_uid = ?";
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $puid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $pheno_exp, $tmp);
                while (mysqli_stmt_fetch($stmt)) {
                    $gwas = json_decode($tmp);
                    foreach ($gwas as $val) {
                        $marker_name = $val[0];
                        $zvalue = $val[3];
                        $qvalue = $val[4];
                        $zsum[$marker_name] += $zvalue;
                        $ztot[$marker_name]++;
                        if ($qvalue < 0.05) {
                            $goodList[$marker_name] = 1;
                        }
                    }
                }
                mysqli_stmt_close($stmt);
            }
            foreach ($zsum as $marker_name => $val) {
                $zmeta[$marker_name] = $zsum[$marker_name] / $ztot[$marker_name];
            }
        }

        echo "\"trait\",\"marker\",\"chromosome\",position,\"gene\",\"feature\",z-score\n";
        foreach ($puid_list as $puid) {
            $sql = "select phenotypes_name from phenotypes where phenotype_uid = ?";
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $puid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $desc);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }
            $sql = "select gwas from $database where phenotype_uid = ?";
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $puid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $tmp);
                while (mysqli_stmt_fetch($stmt)) {
                    $gwas = json_decode($tmp);
                    foreach ($gwas as $val) {
                        $marker = $val[0];
                        $chrom = $val[1];
                        $pos = $val[2];
                        $zvalue = $val[3];
                        $qvalue = $val[4];
                        $pvalue = $val[5];
                        if (preg_match("/scaff/", $chrom)) {
                            $chrom = "UNK";
                        } elseif (preg_match("/v44/", $chrom)) {
                            $chrom = "UNK";
                        }
                        if ($qvalue < 0.05) {
                            if (isset($annot_list2[$marker])) {
                                $gene = $annot_list2[$marker];
                                $feature = $annot_list3[$marker];
                            } else {
                                $gene = "";
                                $feature = "";
                            }
                            if (empty($pos)) {
                                $pos = 0;
                                $feature = "unknown location";
                            }
                            if (!isset($unique[$marker])) {
                                $unique[$marker] = 1;
                                $output_index[] = $zmeta[$marker];
                                $output_list[] = "\"$desc\",\"$marker\",\"$chrom\",$pos,\"$gene\",\"$feature\",$zmeta[$marker]";
                            }
                        }
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        arsort($output_index);
        $count = 1;
        foreach ($output_index as $key => $val) {
            $count++;
            echo "$output_list[$key]\n";
        }
    }

    private function downloadDetailQTL()
    {
        global $mysqli;
        if (isset($_GET['pi'])) {
            $puid_list = explode(",", $_GET['pi']);
        }
        if (isset($_SESSION['selected_traits'])) {
            $phenotype_ary = $_SESSION['selected_traits'];
            $puid = $phenotype_ary[0];
        } elseif (isset($_GET['pi'])) {
            $puid = $_GET['pi'];
        } else {
            die("Error: no phenotypes selected\n");
        }
        if (isset($_GET['method'])) {
            if ($_GET['method'] == 'set') {
                $database = "qtl_set";
            } elseif ($_GET['method'] == 'imput') {
                $database = 'qtl_imputed';
            } else {
                $database = "qtl_raw";
            }
        } else {
            $database = "qtl_raw";
        }
        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        } else {
            die("Error: select genome assembly\n");
        }

        $sql = "select marker_name, assembly_name, gene, description from qtl_annotations where assembly_name = \"$assembly\"";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $marker = $row[0];
            $gene = $row[2];
            $desc = $row[3];
            $annot_list2[$marker] = $gene;
            $annot_list3[$marker] = $desc;
        }

        $sql = "select experiment_uid, trial_code from experiments";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $uid = $row[0];
            $trial_code = $row[1];
            $trial_list[$uid] = $trial_code;
        }

        if (isset($trial_str)) {
            $sql = "select phenotype_exp, gwas from $database where phenotype_uid IN ($puid) and phenotype_exp IN ($trial_str)";
            echo "$sql\n";
        } else {
            $sql = "select phenotype_exp, gwas from $database  where phenotype_uid IN ($puid)";
        }
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        $zsum = array();
        while ($row = mysqli_fetch_array($res)) {
            $pheno_exp = $row[0];
            $gwas = json_decode($row[1]);
            if ($_GET['method'] == 'set') {
                foreach ($gwas as $val) {
                    $marker_name = $val[0];
                    $zvalue = $val[3];
                    $qvalue = $val[4];
                    $zsum[$marker_name] += $zvalue;
                    $ztot[$marker_name]++;
                    if ($qvalue < 0.05) {
                        $goodList[$marker_name] = 1;
                    }
                }
            } else {
                foreach ($gwas as $val) {
                    $marker_name = $val[0];
                    $zvalue = $val[3];
                    $qvalue = $val[4];
                    $zsum[$marker_name] += $zvalue;
                    $ztot[$marker_name]++;
                    if ($qvalue < 0.05) {
                        $goodList[$marker_name] = 1;
                    }
                }
            }
        }
        foreach ($zsum as $marker_name => $val) {
            if (isset($goodList[$marker_name])) {
                $zmeta[$marker_name] = $zsum[$marker_name] / $ztot[$marker_name];
            } else {
                $zmeta[$marker_name] = 0;
            }
        }

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="qtl_detail.csv"');

        echo "\"trait\",\"marker\",\"chromosome\",position,gene,z-score,q-value,p-value,\"phenotype/genotype trial\"\n";
        foreach ($puid_list as $puid) {
            $sql = "select phenotypes_name from phenotypes where phenotype_uid = ?";
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $puid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $name);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }
            $sql = "select genotype_exp, phenotype_exp, gwas from $database where phenotype_uid = ?";
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $puid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $gexp, $pexp, $tmp);
                while (mysqli_stmt_fetch($stmt)) {
                    $gwas = json_decode($tmp);
                    foreach ($gwas as $val) {
                        $marker = $val[0];
                        $chrom = $val[1];
                        $pos = $val[2];
                        $zvalue = $val[3];
                        $qvalue = $val[4];
                        $pvalue = $val[5];
                        if (preg_match("/scaff/", $chrom)) {
                            $chrom = "UNK";
                        } elseif (preg_match("/v44/", $chrom)) {
                            $chrom = "UNK";
                        }
                        if (isset($annot_list2[$marker])) {
                            $gene = $annot_list2[$marker];
                            $feature = $annot_list3[$marker];
                        } else {
                            $gene = "";
                            $feature = "";
                        }
                        if (empty($pos)) {
                            $pos = 0;
                        }
                        if (isset($goodList[$marker])) {
                            $output_index[] = $zmeta[$marker];
                            $output_list[] =  "\"$name\",\"$marker\",\"$chrom\",$pos,$gene,$zvalue,$qvalue,$pvalue,\"$trial_list[$gexp] $trial_list[$pexp]\"";
                        }
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        arsort($output_index);
        $count = 1;
        foreach ($output_index as $key => $val) {
            $count++;
            echo "$output_list[$key]\n";
        }
    }

 
    private function displayQTL()
    {
        global $mysqli;
        $browserLink['IWGSC1+popseq'] = "http://imar2016-plants.ensembl.org/Triticum_aestivum/Location/View?r=";
        $browserLink['Wheat_TGACv1'] = "http://plants.ensembl.org/Triticum_aestivum/Location/View?r=";
        $browserLink['RefSeq_v1'] = "https://triticeaetoolbox.org/jbrowse/?data=wheat2016&loc=";
        $browserLink['RefSeq1.1'] = "https://triticeaetoolbox.org/jbrowse/?data=wheat2016&loc=";
        $browserLink['Wheat_Pangenome'] = "https://triticeaetoolbox.org/jbrowse/?data=wheat2017&loc=";
        $browserLink['OatSeedRef90'] = "https://triticeaetoolbox.org/jbrowse/?data=oat&loc=";
        // get species
        if (preg_match("/([A-Za-z]+)\/[^\/]+\/[^\/]+$/", $_SERVER['PHP_SELF'], $match)) {
            $species = $match[1];
        } else {
            $species = "";
        }
        if (isset($_SESSION['selected_traits'])) {
            $phenotype_ary = $_SESSION['selected_traits'];
            $puid = $phenotype_ary[0];
        } elseif (isset($_GET['pi'])) {
            $puid = $_GET['pi'];
        } else {
            die("Error: no phenotypes selected\n");
        }
        if (isset($_SESSION['selected_trials'])) {
            $trial_ary = $_SESSION['selected_trials'];
            $trial_str = implode(",", $trial_ary);
        }
        if (isset($_GET['sortby'])) {
            $tmp = $_GET['sortby'];
            if ($tmp == "posit") {
                $select_posit = "checked";
                $select_score = "";
            } elseif ($tmp == "score") {
                $select_posit = "";
                $select_score = "checked";
            } else {
            }
        } else {
            $select_posit = "";
            $select_score = "checked";
        }
        if (isset($_GET['group'])) {
            $gb = $_GET['group'];
            if ($gb == "marker") {
                $opt2 = "group by marker_name";
                $select_m = "checked";
                $select_g = "";
            } elseif ($gb == "gene") {
                $opt2 = "group by gene";
                $select_m = "";
                $select_g = "checked";
            } else {
                $opt2 = "group by marker_name";
                $select_m = "checked";
                $select_g = "";
            }
        } else {
            $gb = "marker";
            $opt = "group by marker_name";
            $select_m = "checked";
            $select_g = "";
        }
        if (isset($_GET['method'])) {
            if ($_GET['method'] == 'set') {
                $database = "qtl_set";
                $select_set = "checked";
                $select_sig = "";
                $select_imput = "";
            } elseif ($_GET['method'] == 'imput') {
                $database = 'qtl_imputed';
                $select_set = "";
                $select_sig = "";
                $select_imput = "checked";
            } else {
                $database = "qtl_raw";
                $select_set = "";
                $select_sig = "checked";
                $select_imput = "";
            }
        } else {
            $database = "qtl_raw";
            $select_set = "";
            $select_sig = "checked";
            $select_imput = "";
        }
        if (isset($_GET['assembly'])) {
            $assembly = $_GET['assembly'];
        } else {
            die("Error: select genome assembly\n");
        }
        $assembly_name = mysql_grab("select assembly_name from assemblies where assembly_uid = $assembly");
        $sql = "select phenotypes_name, TO_number from phenotypes where phenotype_uid = $puid";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        if ($row = mysqli_fetch_array($res)) {
            $phenotype_name = $row[0];
            $TO = $row[1];
            if (preg_match("/TO:(\d+)/", $TO, $match)) {
                $TO = $match[1];
            } else {
                $TO = "";
            }
        }

        echo "<table><tr><td>Analysis Method<td>Group by<td>Sort by";
        echo "<tr><td>";
        echo "<input type=\"radio\" name=\"meth\" id=\"meth\" onclick=\"selectDb('single')\" $select_sig> phenotype trial<br>";
        echo "<input type=\"radio\" name=\"meth\" id=\"meth\" onclick=\"selectDb('set')\" $select_set> phenotype experiment (set of trials)<br>";
        //echo "<input type=\"radio\" name=\"meth\" id=\"meth\" onclick=\"selectDb('imput')\" $select_imput> phenotype trial, genotype imputed";
        echo "<td>";
        echo "<input type=\"radio\" name=\"group\" id=\"group\" onclick=\"group('marker')\" $select_m> marker<br>";
        echo "<input type=\"radio\" name=\"group\" id=\"group\" onclick=\"group('gene')\" $select_g> gene<br>";
        echo "<td>";
        echo "<input type=\"radio\" name=\"sort\" id=\"sort\" onclick=\"sort('score')\" $select_score> score<br>";
        echo "<input type=\"radio\" name=\"sort\" id=\"sort\" onclick=\"sort('posit')\" $select_posit> position<br>";
        echo "</table><br>";

        $sql = "select marker_name, gene, description from qtl_annotations where assembly_name = $assembly";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $marker = $row[0];
            $gene = $row[1];
            $desc = $row[2];
            $annot_gene[$marker] = $gene;
            $annot_desc[$marker] = $desc;
        }

        $sql = "select experiment_uid, number_entries from phenotype_experiment_info";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $pheno_exp = $row[0];
            $count = $row[1];
            $linesInExp[$pheno_exp] = $row[1];
        }

        //get z-stat
        //get count of significant qtls when grouping by marker_name
        $zsum = array();
        if ($gb == "marker") {
            if (isset($trial_str)) {
                $sql = "select phenotype_exp, gwas from $database where phenotype_uid IN ($puid) and phenotype_exp IN ($trial_str)";
                echo "$sql\n";
            } else {
                $sql = "select phenotype_exp, gwas from $database  where phenotype_uid IN ($puid)";
            }
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
            while ($row = mysqli_fetch_array($res)) {
                $pheno_exp = $row[0];
                $gwas = json_decode($row[1]);
                if ($_GET['method'] == 'set') {
                    foreach ($gwas as $val) {
                        $marker_name = $val[0];
                        $zvalue = $val[3];
                        $qvalue = $val[4];
                        $zsum[$marker_name] += $zvalue;
                        $ztot[$marker_name]++;
                        if ($qvalue < 0.05) {
                            $goodList[$marker_name] = 1;
                        }
                    }
                } else {
                    foreach ($gwas as $val) {
                        $marker_name = $val[0];
                        $zvalue = $val[3];
                        $qvalue = $val[4];
                        $zsum[$marker_name] += $zvalue;
                        $ztot[$marker_name]++;
                        if ($qvalue < 0.05) {
                            $goodList[$marker_name] = 1;
                        }
                    }
                }
            }
            foreach ($zsum as $marker_name => $val) {
                if (isset($goodList[$marker_name])) {
                    $zmeta[$marker_name] = $zsum[$marker_name] / $ztot[$marker_name];
                } else {
                    $zmeta[$marker_name] = 0;
                }
            }
        } else {
            //get count of significant qtls when groupinng by gene
            $sql = "select gwas from $database where phenotype_uid IN ($puid)";
            $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
            while ($row = mysqli_fetch_array($res)) {
                $gwas = json_decode($row[0]);
                foreach ($gwas as $val) {
                    $marker_name = $val[0];
                    $zvalue = $val[3];
                    if (isset($annot_gene[$marker_name])) {
                        $gene = $annot_gene[$marker_name];
                        $zsum[$gene] += $zvalue;
                        $ztot[$gene]++;
                    }
                }
            }
            foreach ($zsum as $gene => $val) {
                $zmeta[$gene] = $zsum[$gene] / $ztot[$gene];
            }
        }

        $count = 0;
        $sql = "select gwas from $database where phenotype_uid IN ($puid)";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_array($res)) {
            $gwas = json_decode($row[0]);
            foreach ($gwas as $val) {
                $marker = $val[0];
                $zvalue = $val[3];
                $qvalue = $val[4];
                $pvalue = $val[5];
                if ($qvalue < 0.05) {
                    $count++;
                    $sql = "select chrom, pos, bin from marker_report_reference where marker_name = \"$marker\" and assembly_name = \"$assembly_name\"";
                    $res2 = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli) . "<br>$sql");
                    if ($row2 = mysqli_fetch_array($res2)) {
                        $chrom = $row2[0];
                        $pos = $row2[1];
                        $bin = $row2[2];
                        $start = $pos - 1000;
                        if ($start < 0) {
                            $start = 0;
                        }
                        $stop = $pos + 1000;
                        //JBrowse Wheat currently requires "chr" chromosome
                        if (!preg_match("/chr/", $chrom)) {
                            $chrom = "chr" . $chrom;
                        }
                    } else {
                        $chrom = "";
                        $pos = "";
                        $start = "";
                        $stop = "";
                    }
                    if (isset($annot_gene[$marker])) {
                        $gene = $annot_gene[$marker];
                        $exp1 = "<a target=\"_new\" href=\"http://www.wheat-expression.com/genes/show?gene_set=$assembly_name&name=$gene&search_by=gene\">expVIP</a>";
                        $exp2 = "<a target=\"_new\" href=\"https://www.ebi.ac.uk/gxa/genes/$gene\">EMBL-EBI</a>";
                        $knetminer1 = "<a target=\"_new\" href=\"http://knetminer.rothamsted.ac.uk/wheatknet/genepage?keyword=" . urlencode($phenotype_name) . "&list=$gene\">keyword</a>";
                        if (empty($TO)) {
                            $knetminer2 = "";
                        } else {
                            $knetminer2 = "<a target=\"_new\" href=\"http://knetminer.rothamsted.ac.uk/wheatknet/genepage?keyword=$TO&list=$gene\">ontology</a>";
                        }
                    } else {
                        $gene = "";
                        $knetminer1 = "";
                        $knetminer2 = "";
                        $exp1 = "";
                        $exp2 = "";
                    }
                    if (isset($annot_desc[$marker])) {
                        $desc2 = $annot_desc[$marker];
                    } else {
                        $desc2 = "";
                    }
                    if (($chrom == "UNK") || ($chrom == "chrUn")) {
                        $chrom_num = 4;
                        $chrom_arm = "";
                    } else {
                        if (preg_match("/(\d+)(\w)/", $chrom, $match)) {
                            $chrom_num = $match[1];
                            $chrom_arm = $match[2];
                        } else {
                            $chrom_num = "";
                            $chrom_arm = "";
                        }
                    }
                    if (isset($_GET['sortby'])) {
                        $sort_type = $_GET['sortby'];
                        if ($sort_type == "posit") {
                            if ($chrom_arm == "A") {
                                $sort_index = (($chrom_num * 10) + 1) * 100000000 + $pos;
                            } elseif ($chrom_arm == "B") {
                                $sort_index = (($chrom_num * 10) + 2) * 100000000 + $pos;
                            } elseif ($chrom_arm == "D") {
                                $sort_index = (($chrom_num * 10) + 3) * 100000000 + $pos;
                            } else {
                                $sort_index = (($chrom_num * 10) + 4) * 100000000 + $pos;
                            }
                        } else {
                            $sort_type = "score";
                            if ($gb == "marker") {
                                $sort_index = $zmeta[$marker];
                            } else {
                                $sort_index = $zmeta[$gene];
                            }
                        }
                    } else {
                        $sort_type = "score";
                        if ($gb == "marker") {
                            $sort_index = $zmeta[$marker];
                        } else {
                            $sort_index = $zmeta[$gene];
                        }
                    }
                    if ($pos == "") {
                        $jbrowse = "";
                    } elseif (!isset($browserLink[$assembly_name])) {
                        $jbrowse = "$assembly_name not defined";
                    } elseif (preg_match("/RefSeq/", $assembly_name)) {
                        $jbrowse = "<a target=\"_new\" href=\"" . $browserLink[$assembly_name] . "$chrom:$start..$stop\">JBrowse</a>";
                    } elseif (preg_match("/TGAC/", $assembly_name)) {
                        $jbrowse = "<a target=\"_new\" href=\"" . $browserLink[$assembly_name] . "$bin:$start-$stop\">Ensembl Browser</a>";
                    } else {
                        $jbrowse = "<a target=\"_new\" href=\"" . $browserLink[$assembly_name] . "$chrom:$start-$stop\">JBrowse</a>";
                    }

                    if ($gb == "marker") {
                        if (isset($marker_list[$marker])) {
                        } else {
                            $marker_list[$marker] = 1;
                            $sql = "select marker_uid from markers where marker_name = \"$marker\"";
                            $res2 = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
                            $row2 = mysqli_fetch_row($res2);
                            $uid = $row2[0];
                            $marker_link = "<a href=\"$target_url" . "view.php?table=markers&uid=" . $uid . "\">$marker</a>";
                            if (empty($gene)) {
                                $gene_link = "";
                            } else {
                                $sql = "select gene_annotation_uid from gene_annotations where gene_id =\"$gene\" and assembly_name = $assembly";
                                $res2 = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
                                $row2 = mysqli_fetch_row($res2);
                                $gene_uid = $row2[0];
                                $gene_link = "<a href=\"view.php?table=gene_annotations&uid=" . $gene_uid . "\">$gene</a>";
                            }
                            $zvalue = number_format($zmeta[$marker], 3);
                            $detail_link = "$ztot[$marker]<td><a id=\"detail\" onclick=\"detailM('$marker')\">Trial details</a>";
                            $output_index[] = $sort_index;
                            $output_list[] = "<tr><td>$marker_link<td>$chrom<td>$pos<td>$gene_link<td>$desc2<td>$zvalue<td>$detail_link<td>$jbrowse<td>$exp1 $exp2<td>$knetminer1 $knetminer2";
                        }
                    } else {
                        if ($gene == "") {
                        } elseif (isset($gene_list[$gene])) {
                        } else {
                            $gene_list[$gene] = 1;
                            $zvalue = number_format($zmeta[$gene], 3);
                            $output_index[] = $sort_index;
                            $output_list[] = "<tr><td>$gene<td>$chrom<td>$desc2<td><a id=\"detail\" onclick=\"detailG('$gene')\">$zvalue</a><td>$ztot[$gene]<td>$jbrowse<td>$knetminer1 $knetminer2";
                        }
                    }
                }
            }
        }
        if ($count > 0) {
            echo "<a href=\"qtl/qtl_report.php?function=downloadQTL&pi=" . $puid . "&method=" . $_GET['method'] . "&assembly=" . $assembly ."\">Download meta data</a>, ";
            echo "<a href=\"qtl/qtl_report.php?function=downloadDetailQTL&pi=" . $puid . "&method=" . $_GET['method'] . "&assembly=" . $assembly . "\">Download detail data</a><br>";
            $count_display = 0;
            if ($gb == "marker") {
                echo "<table><tr><td>marker<td>chromosome<td>location";
                if ($_GET['method'] == 'set') {
                    echo "<td>gene<td>feature<td nowrap>Z-score<td>Experiment Count<td>Trial Details<td>Genome Browser<td>Expression<td>Knetminer";
                } else {
                    echo "<td>gene<td>feature<td nowrap>Z-score<td>Trial Count<td>Trial Details<td>Genome Browser<td>Expression<td>Knetminer";
                }
            } else {
                echo "<table><tr><td>gene<td>location";
                echo "<td>feature<td>Z-score<td>Count<td>Geneome Browser<td>Knetminer";
            }

            if ($sort_type == "score") {
                arsort($output_index);
                foreach ($output_index as $key => $val) {
                    if ($val > 0) {
                        echo "$output_list[$key]";
                    }
                }
            } else {
                asort($output_index);
                foreach ($output_index as $key => $val) {
                    echo "$output_list[$key]";
                }
            }
            echo "</table>";
        } else {
            echo "no significant QTLs found<br>$sql\n";
        }
    }
}
