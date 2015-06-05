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
			$avg_solids = $solids / $count;
			echo '<th>'.$avg_solids.'</th><th>'.$avg_litres.'</th></tr>';
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
$avg_solids = $solids / $count;
echo '<th>'.$avg_litres.'</th><th>'.$avg_solids.'</th></tr>';
?>