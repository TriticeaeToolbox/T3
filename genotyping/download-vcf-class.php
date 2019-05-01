<?php

namespace T3;

class SelectMarkers
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'query':
                $this->displayChrom();
                break;
            case 'save':
                $this->save();
                break;
            default:
                $this->displayAll();
                break;
        }
    }

    public function save()
    {
        $count = count($_SESSION['selected_markers']);
        echo "Saved $count markers\n";
        $_SESSION['clicked_buttons'] = $_SESSION['selected_markers'];
    }

    public function findMarkers()
    {
        global $mysqli;
        global $config;

        echo "<h2>Download Genotype Experiment</h2>\n";
        echo "This tool allows you to quickly download a portion of a genotype experiment.<br>\n";
        echo "Select a genotype experiment, chromosome, and range.<br>\n";
        echo "The output format is Variant Call Format (VCF)<br><br>\n";

        if (isset($_GET['start']) && !empty($_GET['start'])) {
            $start = $_GET['start'];
        }
        if (isset($_GET['stop']) && !empty($_GET['stop'])) {
            $stop = $_GET['stop'];
        }
        if (isset($_GET['chrom']) && !empty($_GET['chrom'])) {
            $selected_chrom = $_GET['chrom'];
            //echo "chromosome = $selected_chrom<br>\n";
        }

        ?>
        <table>
        <tr><td>Genotype trial:<td><select id="trial">
            <option value="1kEC_genotype01222019">2019_Diversity_GBS</option>
            <option value="1kEC_genotype01222019f">2019_Diversity_GBS filtered</option>
            </select>
        <tr><td>Chromosome:<td><select id="chrom">
        <option>select</option>
        <?php
        $count = 0;

        $file_chr = "1kEC_chromosomes.txt";
        $fh = fopen($file_chr, "r") or die("Error: $file_chr not found");
        while (!feof($fh)) {
            $chrom = fgets($fh);
            $chrom = trim($chrom);
            if (!empty($chrom)) {
                if (preg_match("/$chrom/i", $selected_chrom)) {
                    $selected = "selected";
                } else {
                    $selected = $selected_chrom;
                }
                echo "<option value=$chrom $selected>$chrom</option>\n";
            }
        }
        echo "</select><br>\n";
        fclose($fh);

        $file_hdr = "1kEC_header.txt";
        $fh = fopen($file_hdr, "r") or die("Error: $file_hdr not found");
        $header = fgets($fh);
        fclose($fh);

        echo "<tr><td>Start:<td><input type=\"text\" id=\"start\" value=\"$start\"><td>$min\n";
        echo "<tr><td>Stop:<td><input type=\"text\" id=\"stop\" value=\"$stop\"><td>$max\n";
        echo "<tr><td><input type=\"button\" value=\"Query\" onclick=\"select_chrom()\"/>";
        echo "</table><br>";
        $geno_exp = $_SESSION['geno_exps'][0];
 
        $count = 0;
        $count_inpos = 0;
        $count_inmap = 0;
        $poly = array();
        $found_list = array();
        if (isset($_GET['function']) && !empty($_GET['function'])) {
            $trial = $_GET['trial'];
            $chrom = $_GET['chrom'];
            $start = $_GET['start'];
            $stop = $_GET['stop'];
            if (preg_match("/([A-Za-z0-9_]+)/", $trial, $match)) {
                $trial = $match[1];
            } else {
                die("Select a genotype trial");
            }
            $file = "/data/wheat/" . $trial . ".vcf.gz";
            $unique_str = chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80)).chr(rand(65, 80));
            $dir = "/tmp/tht/download_" . $unique_str;
            mkdir($dir);
            $filename1 = $dir . "/genotype.vcf";
            $filename2 = $dir . "/proc_error.txt";

            $cmd = "tabix $file $chrom:$start-$stop > $filename1 2> $filename2";
            //echo "$cmd<br>\n";
            exec($cmd);
            $header .= "\n" . file_get_contents($filename1);
            file_put_contents($filename1, $header);
        
            if (file_exists("$filename2")) {
                $fh = fopen($filename2, "r");
                while (!feof($fh)) {
                    $line = fgets($fh);
                    echo "$line<br>\n";
                }
            }

            ?>
            <input type="button" value="Download file of results"
                onclick="javascript:window.open('<?php echo $filename1 ?>');">
                <br><br>
            <?php
        }
    }
    private function displayAll()
    {
        global $config;
        include $config['root_dir'].'theme/admin_header2.php';
        ?>
        <script type="text/javascript" src="genotyping/download-vcf.js"></script>
        <div id="step2">
        <?php
      
        $this->findMarkers();
        echo "</div></div>";
        include $config['root_dir'].'theme/footer.php';
    }

    private function displayChrom()
    {
        global $config;
        $this->findMarkers();
    }
}
