<?php
require '../alextra.class.php';
if(!isset($_GET['a'])) $_GET['a'] = false;
switch($_GET['a']) {

	default:
	$data = $alpro->jogglerMilkRecording();
	echo '<table>';
	foreach($data as $cow) {
		echo '<tr><td>'.$cow['stall'].'</td><td>'.$cow['cow'].'</td><td></td></tr>';
	}
	echo '</table>';
	break;
}
?>
