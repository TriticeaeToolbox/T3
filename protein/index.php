<?php

namespace reports;

require 'config.php';
$pageTitle = "Protein Annotation";

require_once $config['root_dir'].'includes/bootstrap.inc';
require_once $config['root_dir'].'protein/protein_class.php';

// connect to database
$mysqli = connecti();

new Protein($_GET['function']);
