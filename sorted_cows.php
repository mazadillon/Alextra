<?php
require 'alextra.class.php';
if(isset($_POST['date'])) {
	$alpro->sortedCows();
	$cows = $alpro->listSortedCows($_POST['date']);
	echo count($cows) .' animals shed on '.$_POST['date'].'<br />';
	foreach($cows as $cow) echo $cow['cow'].' '.$cow['time'].'<br />';
	//print_r($alpro->sortedCows($_POST['date']));
} else {
	echo '<form action="sorted_cows.php" method="post">';
	echo 'Date: <input type="text" name="date" value="'.date('Y-m-d').'" /> <input type="submit" value="Go" /></form>';
}
?>
	