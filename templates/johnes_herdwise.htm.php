<h1>Johnes Cows</h1>
<table border="1">
<tr>
<th>Cow</th>
<?php
foreach($data['dates'] as $date) echo '<th>'.$date.'</th>';
echo '<th>Group</th></tr>';
foreach($data['cows'] as $cow => $tests) {
	echo '<tr><td>'.$cow.'</td>';
	foreach($data['dates'] as $date) {
		echo '<td>';
		if(in_array($date,$tests)) echo $tests[$date];
		else echo '&nbsp;';
		echo '</td>';
	}
	echo '<td>&nbsp;</td></tr>';
}
?>
</table>