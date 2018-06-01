<?php

namespace T3;

require 'config.php';
require_once $config['root_dir'].'includes/bootstrap.inc';
require_once $config['root_dir'].'compounds/download_metabolite_class.php';

// connect to database
$mysqli = connecti();

new Downloads($_GET['function']);
