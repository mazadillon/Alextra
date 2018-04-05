<?php
include 'reload.htm';
echo 'Data will appear when first cow is milked.';
echo '<h1>Recent Milk Tests</h1>';
echo '<p style="text-align:center;">Target cell count under 200 and bactoscan under 50</p>';
echo '<table id="tests"><tr><th>Date</th><th>Cell Count</th><th>Bactoscan</th><th>Butterfat</th><th>Protein</th></tr>';
$tests = $alpro->milkTests();
foreach($tests as $test) {
	echo '<tr><td>'.date("D jS M",strtotime($test['date'])).'</td>';
	if($test['scc'] <= 175) echo '<td style="background-color:green;color:white;">';
	elseif($test['scc'] > 175 AND $test['scc'] <= 200)  echo '<td style="background-color:yellow;color:blue;">';
	else echo '<td style="background-color:red;color:white;">';
	echo $test['scc'].'</td>';
	if($test['bacto'] <= 40) echo '<td style="background-color:green;color:white;">';
	elseif($test['bacto'] > 40 AND $test['bacto'] <= 50)  echo '<td style="background-color:yellow;color:blue;">';
	else echo '<td style="background-color:red;color:white;">';
	echo $test['bacto'].'</td><td>'.$test['butter']."</td>";
	echo '<td>'.$test['protein']."</td></tr>\n";
}
echo '</table>';
echo '<h1 class="clock">'.date('H:i').'</h1>';
?>