<?php
$root = str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
$pos1 = strripos($root, '/', -2);
$parent_dir = substr($root, 0, $pos1+1);//realpath("$root../");
//$pos2 = strripos($root, '/', -1 * $pos1);
//$grandparent_dir = substr($root, 0, $pos2+1);//realpath("$root../../");
$root = 'https://'.$_SERVER['HTTP_HOST']/*.'/'*/.strtolower(str_replace('http://'.$_SERVER['HTTP_HOST'],'',$parent_dir));
$config['base_url'] = $root;//.'/';
$config['root_dir'] = realpath(dirname(__FILE__).'/../').'/';
