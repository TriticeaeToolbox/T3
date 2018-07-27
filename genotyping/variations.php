<?php

namespace reports;

require 'config.php';
$pageTitle = "Variant Effects";

require $config['root_dir'].'includes/bootstrap.inc';
require $config['root_dir'].'genotyping/variations_class.php';

$mysqli = connecti();

new Variations($_GET['function']);
