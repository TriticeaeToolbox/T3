<?php
/**
 * Download phenotype information for experiment
 *
 */

namespace T3;

class Downloads
{
    public function __construct($function = null)
    {
        switch ($function) {
            case 'downloadPlot':
                $this->downloadPlot();
                break;
            default:
                echo "Error: bad option\n";
                break;
        }
    }

    private function downloadPlot()
    {
        global $mysqli;
        if (isset($_GET['trial_code'])) {
            $trial_code = $_GET['trial_code'];
        } else {
            echo "Error: experiment uid not set\n";
            return;
        }
        $sql = "select experiment_uid from experiments where trial_code = ?";
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $trial_code);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $uid);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        } else {
            echo "Error: bad query $trial_code\n";
        }
  
        $sql = "select compound_uid, compound_name from compounds";
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $phenotype_uid, $name);
            while (mysqli_stmt_fetch($stmt)) {
                $trait_list[$phenotype_uid] = $name;
            }
            mysqli_stmt_close($stmt);
        }

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="trial_data_plot.csv"');
        $sql = "select spectra from spectra_merged_idx where experiment_uid = ?";
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $uid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $index);
            if (mysqli_stmt_fetch($stmt)) {
                $header = "line, block, plot";
                $index = json_decode($index, true);
                foreach ($index as $cmp_uid) {
                    $header .= ",\"$trait_list[$cmp_uid]\"";
                }
                echo "$header\n";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Error: bad query $sql\n";
        }
        $sql = "select block, plot, line_records.line_record_name, spectra from spectra_merged, line_records
            where spectra_merged.line_record_uid = line_records.line_record_uid 
            and spectra_merged.experiment_uid = ?";
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $uid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $block, $plot, $line_record_name, $value);
            while (mysqli_stmt_fetch($stmt)) {
                echo "$line_record_name, $block, $plot, $value\n";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Error: bad query $sql $uid\n";
        }
    }
}
