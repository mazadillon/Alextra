<?php
include 'reload.htm';
echo 'Data will appear when first cow is milked.';
echo '<h1>Recent Milk Tests</h1>';
echo '<table id="tests"><tr><th>Date</th><th>Cell Count</th><th>Bactoscan</th><th>Butterfat</th><th>Protein</th></tr>';
$tests = $alpro->milkTests();
foreach($tests as $test) {
	echo '<tr><td>'.date("D jS M",strtotime($test['date'])).'</td>';
	if($test['scc'] <= 200) echo '<td style="background-color:green;color:white;">';
	elseif($test['scc'] > 200 AND $test['scc'] <= 250)  echo '<td style="background-color:yellow;color:blue;">';
	else echo '<td style="background-color:red;color:white;">';
	echo $test['scc'].'</td>';
	if($test['bacto'] <= 25) echo '<td style="background-color:green;color:white;">';
	elseif($test['bacto'] > 25 AND $test['bacto'] <= 30)  echo '<td style="background-color:yellow;color:blue;">';
	else echo '<td style="background-color:red;color:white;">';
	echo $test['bacto'].'</td><td>'.$test['butter']."</td>";
	echo '<td>'.$test['protein']."</td></tr>\n";
}
echo '</table>';
echo '<h1 class="clock">'.date('H:i').'</h1>';
?>