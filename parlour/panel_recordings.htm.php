<?php
include 'reload.htm';
echo 'Data will appear when first cow is milked.';
echo '<h1>Recent Milk Tests</h1>';
echo '<table id="tests"><tr><th>Date</th><th>Cell Count</th><th>Bactoscan</th><th>Butterfat</th><th>Protein</th></tr>';
$tests = $alpro->milkTests();
foreach($tests as $test) {
	echo '<tr><td>'.$test['date'].'</td><td>'.$test['scc'].'</td><td>'.$test['bacto'].'</td><td>'.$test['butter'].'</td><td>'.$test['protein']."</td></tr>\n";
}
echo '</table>';
echo '<h1 class="clock">'.date('H:i').'</h1>';
?>