<?php
include 'alpro-history.php';

$config['mysql']['host'] = 'localhost';
$config['mysql']['user'] = 'root';
$config['mysql']['db'] = 'alpro';
$config['paths']['base'] = 'C:\\Documents and Settings\\Ford\\My Documents\\';

$alpro = new alpro($config);
?>