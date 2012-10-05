<?php
include 'reload.htm';
echo $milking_status.' cows milked so far.';
echo $milking_speed['speed'].' seconds per cow. '.$milking_speed['cph'].' cows per hour.<br />';
foreach($data as $row) {
	echo '<span style="font-size: 50pt;">'.$row['cow']."</span><br />\n";
}
?>