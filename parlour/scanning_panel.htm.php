<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" type="text/css" href="../style-status.css" />
<body>
<a href="index.php"><img src="home.png" border="0"></a> <a href="/parlour/?a=scanning"><img src="round-again.png" border="0" /></a> 
<?php
ERROR_REPORTING('E_NONE');
if(is_array($data)) {
	echo $milking_status.' / '.$numberCowsInMilk['COUNT'].' cows milked so far. ';
	echo $milking_speed['speed'].' seconds per cow. '.$milking_speed['cph'].' cows per hour. Rotation time '.$milking_speed['platform'];
	if(isset($message) && $message) echo '<div style="font-size:2em;border:1px dashed black;background-color:yellow;padding:5px;text-align:center;">'.$message.'</div>';
	echo '<form action="index.php?a=scanning" method="post">';
	echo '<table>';
	echo '<tr><th>#</th><th>Cow</th><th>Status</th><th>DIM</th><th>Heat</th></tr>';
	$align = 'left';
	foreach($data as $id =>	$row) {
		if(isset($row['info'])) {
			echo '<tr><td>';
			if($row['stall_'.$row['milking']] == '0') echo '&nbsp;';
			else echo $row['stall_'.$row['milking']];
			echo '</td>';
			echo '<td style="font-size: xx-large;"><a href="index.php?a=cowstatus&cow='.$row['info']['cow'].'">'.$row['info']["cow"].'</a>';
			echo '</td>';
			echo '<td class="'.strtolower($row['info']['status']).'">'.$row['info']["status"].'</td>';
			echo '<td>'.$row['info']['dim'].'</td>';
			echo '<td style="text-align:'.$align.';">';
			if(strtolower($row['info']['status'])=='pregnant' or strtolower($row['info']['status'])=='inseminated' or strtolower($row['info']['status'])=='open' or strtolower($row['info']['status'])=='empty') {
				echo '<button type="submit" name="cow" onClick="clearTimeout(timer);" value="'.$row['info']['cow'].'#positive">Pregnant</button> ';
				if($row['info']['SinceHeat'] !== false) echo $row['info']['SinceHeat']." days ";
				echo '<button type="submit" name="cow" onClick="clearTimeout(timer);" value="'.$row['info']['cow'].'#negative">Empty</button>';
				if($align=='left') $align='center';
				elseif($align=='center') $align = 'right';
				else $align = 'left';
			}
			echo "</td></tr>";
		} else {
			echo '<tr><td>'.$row['stall_'.$row['milking']].'</td>';
			echo '<td colspan="5">Empty stall</td>';
			echo '</tr>';
		}
	}
	echo '</table>';
	echo '</form>';
	echo '<h1 class="clock">'.date('H:i').'</h1>';
} else include 'panel_recordings.htm.php';
?>