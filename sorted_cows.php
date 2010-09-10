<?php
require 'alextra.class.php';
if(isset($_POST['date'])) {
	$cows = $alpro->listSortedCows($_POST['date']);
	foreach($cows as $cow) echo $cow['cow'].' '.$cow['time'].'<br />';
	//print_r($alpro->sortedCows($_POST['date']));
} else {
	echo '<form action="sorted_cows.php" method="post">';
	echo 'Date: <input type="text" name="date" /> <input type="submit" value="Go" /></form>';
}
?>
	