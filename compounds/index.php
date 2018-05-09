<?php

namespace reports;

require 'config.php';
$pageTitle = "Compounds";

require_once $config['root_dir'].'includes/bootstrap.inc';
require_once $config['root_dir'].'compounds/compounds_class.php';

// connect to database
$mysqli = connecti();

new Compounds($_GET['function']);
