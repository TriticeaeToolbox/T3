<?php

namespace reports;

require 'config.php';
$pageTitle = "Gene Annotation";

require_once $config['root_dir'].'includes/bootstrap.inc';
require_once $config['root_dir'].'genes/genes_class.php';

// connect to database
$mysqli = connecti();

new Genes($_GET['function']);
