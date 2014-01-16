<?php
echo '<div>';
echo $total.' cows milked so far. ';
echo $milking_speed['speed'].' seconds per cow. '.$milking_speed['cph'].' cows per hour. Rotation time '.$milking_speed['platform'].' ';
if(isset($_GET['all'])) echo '<a href="index.php?a=newmilkrecording">Recent</a><br />';
else echo '<a href="index.php?a=newmilkrecording&all=true">Show All</a> OR totally <a href="output.php?reset_db_table">Reset</a> the milk recording<br />';
$today = strtotime('1am');
if($data) {
	echo '<table style="width:99%;">';
	foreach($data as $id => $row) {
		echo '<tr>';
		echo '<td style="font-size: xx-large;">'.$row['stall'].'.</td>';
		echo '<td style="font-size: xx-large;">'.$row['cow'].'</td>';
		echo '<td><a href="index.php?a=edit&amp;stamp='.$row['stamp'].'&amp;cow='.$row['cow'].'&amp;stall='.$row['stall'].'"><img src="edit.png" /></a></td>';
		echo '<td><a href="index.php?a=insert&amp;stamp='.$row['stamp'].'"><img src="add.png" /></a></td>';
		echo '<td><a href="index.php?a=delete&amp;stamp='.$row['stamp'].'&amp;cow='.$row['cow'].'"><img src="delete.png" /></a></td>';
		echo '<td><a href="index.php?a=move&amp;step=back&amp;stamp='.$row['stamp'].'&amp;stall='.$row['stall'].'&amp;cow='.$row['cow'].'"><img src="go-previous.png" /></a></td>';
		echo '<td><a href="index.php?a=move&amp;step=forward&amp;stamp='.$row['stamp'].'&amp;stall='.$row['stall'].'&amp;cow='.$row['cow'].'"><img src="go-next.png" /></a></td>';
		echo '</tr>';
	}
	echo '</table>';
} else echo '<br />Ready to start';
echo '</div>';
echo '<h1 class="clock">'.date('H:i').'</h1>';
?>