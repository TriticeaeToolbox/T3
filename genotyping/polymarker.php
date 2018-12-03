<?php

namespace T3;

require 'config.php';
$pageTitle = "Designed Primers";
require_once $config['root_dir'] . 'includes/bootstrap.inc';
require_once $config['root_dir'] . 'genotyping/polymarker_class.php';

$myaqli = connecti();
new DownloadPrimers($_GET['function']);
