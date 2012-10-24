<?php
ERROR_REPORTING('E_NONE');
include 'reload.htm';
//print_r($data);
if(is_array($data) AND (time() - strtotime($data[0][$data[0]['milking']]) < 7200)) {
	echo $milking_status.' cows milked so far. ';
	echo $milking_speed['speed'].' seconds per cow. '.$milking_speed['cph'].' cows per hour. Rotation time '.$milking_speed['platform'];
	if(isset($sorted) && $sorted) echo '<div style="font-size:2em;border:1px dashed black;background-color:yellow;padding:5px;text-align:center;">Cow '.$sorted['cow'].' sorted, total of '.$sorted['total'].' this milking.</div>';
	echo '<table>';
	echo '<tr><th>Cow</th><th>Status</th><th>DIM</th><th>Name</th><th>Heat</th></tr>';
	foreach($data as $id =>	$row) {
		echo '<tr><td style="font-size: xx-large;"><a href="index.php?a=cowstatus&cow='.$row['info']['cow'].'">'.$row['info']["cow"].'</a></td>';
		echo '<td class="'.strtolower($row['info']['status']).'">'.$row['info']["status"].'</td>';
		echo '<td>'.$row['info']['dim'].'</td>';
		echo '<td>'.$row['info']['name'].'</td>';
		if(17 < $row['info']['SinceHeat'] && $row['info']['SinceHeat'] < 23) echo '<td style="background-color:red;color:white;">';
		else echo '<td>&nbsp;';
		if($row['info']['SinceHeat'] != false) echo date('d/m/Y',strtotime($row['info']["heat"])).' '.$row['info']['SinceHeat']." days ago. ";
		if($row['info']['bull']) echo $row['info']['bull'];
		echo "</td>";
	}
	echo '</table>';
	echo '<h1 class="clock">'.date('H:i').'</h1>';
} else echo 'Data will appear when first cow is milked.<h1 class="big_clock">'.date('H:i').'</h1>';
?>