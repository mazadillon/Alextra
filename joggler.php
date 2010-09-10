<?php
require 'config.php';
$alpro->copyLatest('pm');
$data = $alpro->fetchRecent('pm',5);
foreach($data as $cow) {
	echo $cow['cow'].' '.$cow['pm'].'<br />';
}
?>