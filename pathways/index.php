<?php

namespace reports;

require 'config.php';
$pageTitle = "Gene Annotation";

require_once $config['root_dir'].'includes/bootstrap.inc';
require_once $config['root_dir'].'pathways/pathways_class.php';

// connect to database
$mysqli = connecti();

displayPathways();
