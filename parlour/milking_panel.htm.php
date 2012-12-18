<?php
ERROR_REPORTING('E_NONE');
include 'reload.htm';
//print_r($data);
if(is_array($data) AND (time() - strtotime($data[0]['id_'.$data[0]['milking']]) < 3600) AND !isset($_GET['clock'])) {
	echo $milking_status.' cows milked so far. ';
	echo $milking_speed['speed'].' seconds per cow. '.$milking_speed['cph'].' cows per hour. Rotation time '.$milking_speed['platform'];
	if(isset($sorted) && $sorted) echo '<div style="font-size:2em;border:1px dashed black;background-color:yellow;padding:5px;text-align:center;">Cow '.$sorted['cow'].' sorted, total of '.$sorted['total'].' this milking.</div>';
	echo '<table>';
	echo '<tr><th>#</th><th>Cow</th><th>Status</th><th>DIM</th><th>Name</th><th>Heat</th></tr>';
	foreach($data as $id =>	$row) {
		if(isset($row['info'])) {
			echo '<tr><td>';
			if($row['stall_'.$row['milking']] == '0') echo '&nbsp;';
			else echo $row['stall_'.$row['milking']];
			echo '</td>';
			echo '<td style="font-size: xx-large;"><a href="index.php?a=cowstatus&cow='.$row['info']['cow'].'">'.$row['info']["cow"].'</a></td>';
			echo '<td class="'.strtolower($row['info']['status']).'">'.$row['info']["status"].'</td>';
			echo '<td>'.$row['info']['dim'].'</td>';
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
} else {
	echo 'Data will appear when first cow is milked.';
	echo '<h1>Recent Milk Tests</h1>';
	echo '<table id="tests"><tr><th>Date</th><th>Cell Count</th><th>Bactoscan</th><th>Butterfat</th></tr>';
	$tests = $alpro->milkTests();
	foreach($tests as $test) {
		echo '<tr><td>'.$test['date'].'</td><td>'.$test['scc'].'</td><td>'.$test['bacto'].'</td><td>'.$test['butter']."</td></tr>\n";
	}
	echo '</table>';
	echo '<h1 class="clock">'.date('H:i').'</h1>';
}
?>