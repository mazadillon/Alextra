<?php
$cow = false;
$count = 0;
$litres = 0;
$solids = 0;
echo '<table border="1">';
foreach($data as $recording) {
	if($recording['NUMMER'] != $cow) {
		if($cow!=false) {
			$avg_litres = $litres / $count;
			$avg_solids = round($solids / $count,1);
			if($fandp/$avg_solids < 0.85 OR $fandp/$avg_solids > 1.25) echo '<th style="background-color:red;">';
			else echo '<th>';
			echo $avg_solids.'</th><th>'.$avg_litres.'</th>';
			echo '<th>';
			if($avg_solids >= 2.8) echo '8';
			elseif($avg_solids >= 2.6) echo '7'; 
			elseif($avg_solids >= 2.3) echo '6'; 
			elseif($avg_solids >= 2.1) echo '5';
			elseif($avg_solids >= 1.9) echo '4';
			elseif($avg_solids >= 1.7) echo '3'; 
			elseif($avg_solids >= 1.5) echo '2';
			elseif($avg_solids >= 1.4) echo '1';
			else echo '0';
			echo '</th></tr>';
			$count = 0;
			$litres = 0;
			$solids = 0;			
		}
		echo '<tr><td>'.$recording['NUMMER'].'</td>';
	}
	$count = $count+1;
	$litres = $litres + $recording['HOEVEELHEIDMELK'];
	$fandp = $recording['HOEVEELHEIDMELK'] * (($recording['PCTVET']/100) + ($recording['PCTEIWIT']/100));
	$solids = $solids + $fandp;
	echo '<td>'.$recording['HOEVEELHEIDMELK'].'</td><td>'.$fandp.'</td>';
	$cow = $recording['NUMMER'];
}
$avg_litres = $litres / $count;
$avg_solids = round($solids / $count,1);
echo '<th>'.$avg_litres.'</th><th>'.$avg_solids.'</th></tr>';
?>