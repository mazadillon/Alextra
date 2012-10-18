<h1>Johnes Cows</h1>
<?php
foreach($data['simple'] as $cow) {
 echo $cow.' ';
}
?>
<table border="1">
<tr>
<th>Cow</th>
<?php
foreach($data['dates'] as $date) echo '<th>'.$date.'</th>';
echo '<th>Group</th></tr>';
foreach($data['groups'] as $cow => $group) {
	$info = $data['cows'][$cow];
	echo '<tr><td>'.$cow.'</td>';
	foreach($data['dates'] as $date) {
		if(isset($info['tests'][$date])) {
			if($info['tests'][$date] >= 20) echo '<td style="background-color: red;">';
			else echo '<td>';
			echo $info['tests'][$date];
		} else echo '<td>&nbsp;';
		echo '</td>';
	}
	if($group > 4) echo '<td style="background-color: red;">';
	elseif($group < 3) echo '<td>';
	else echo '<td style="background-color: yellow;">';
	echo $group.'</td></tr>';
}
?>
</table>