<?php
ERROR_REPORTING('E_NONE');
include 'reload.htm';
if((is_array($data) AND (time() - strtotime($latestid) < 3600)) OR $_GET['force']) {
	echo $milking_status.' / '.$numberCowsInMilk['COUNT'].' cows milked so far. ';
	echo $milking_speed['speed'].' seconds per cow. '.$milking_speed['cph'].' cows per hour. Rotation time '.$milking_speed['platform'];
	if(isset($sorted) && $sorted) echo '<div style="font-size:2em;border:1px dashed black;background-color:yellow;padding:5px;text-align:center;">Cow '.$sorted['cow'].' sorted, total of '.$sorted['total'].' this milking.</div>';
	if(isset($cowsLeftToMilk)) {
		echo '<div style="font-size:1.5em;border:1px dashed black;background-color:yellow;padding:5px;text-align:center;">';
		if(empty($cowsLeftToMilk)) echo '&#9786; Congratulations, you have finished milking! &#9786;';
		else {
			echo 'Left To Milk: ';
			foreach($cowsLeftToMilk as $cow) echo $cow['NUMMER'].' ';
		}
		echo '</div>';
	}
	echo '<table>';
	echo '<tr><th>#</th><th>Cow</th><th>Status</th><th>DIM</th><th>Name</th><th>Heat</th></tr>';
	foreach($data as $id =>	$row) {
		if(isset($row['info'])) {
			echo '<tr><td>';
			if($row['stall_'.$row['milking']] == '0') echo '&nbsp;';
			else echo $row['stall_'.$row['milking']];
			echo '</td>';
			echo '<td style="font-size: xx-large;"><a href="index.php?a=cowstatus&cow='.$row['info']['cow'].'">'.$row['info']["cow"].'</a>';
			if(isset($row['info']['johnes']) && $row['info']['johnes']) echo '<span style="color:red;"> JOHNES</span>';
			echo '</td>';
			echo '<td class="'.strtolower($row['info']['status']).'">'.$row['info']["status"].'</td>';
			echo '<td>'.$row['info']['dim'];
			if(isset($row['info']['SelectiveDCT'])) {
				if($row['info']['SelectiveDCT']) echo '<span style="color:white;background-color:green;padding:2px;"> Teat Sealant Only </span>';
				else echo '<span style="background-color:red;color:white;padding:2px;"> Dry Cow Tubed </span>';
			}
			echo '</td>';
			echo '<td>'.$row['info']['name'].'</td>';
			if(17 < $row['info']['SinceHeat'] && $row['info']['SinceHeat'] < 23) echo '<td style="background-color:red;color:white;">';
			else echo '<td>&nbsp;';
			if($row['info']['SinceHeat'] !== false) echo date('d/m/Y',strtotime($row['info']["heat"])).' '.$row['info']['SinceHeat']." days ago. ";
			if($row['info']['bull']) echo $row['info']['bull'];
			echo "</td></tr>";
		} else {
			echo '<tr><td>'.$row['stall_'.$row['milking']].'</td>';
			echo '<td colspan="5">Empty stall</td>';
			echo '</tr>';
		}
	}
	echo '</table>';
	echo '<h1 class="clock">'.date('H:i').'</h1>';
} else include 'panel_recordings.htm.php';
?>