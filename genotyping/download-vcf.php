<?php

namespace T3;

require 'config.php';
require $config['root_dir'].'includes/bootstrap.inc';
require $config['root_dir'].'genotyping/download-vcf-class.php';

$mysqli = connecti();

new SelectMarkers($_GET['function']);
