<?php
echo $total.' cows milked so far. ';
if(isset($_GET['all'])) echo '<a href="index.php?a=milkrecording">Recent</a><br />';
else echo '<a href="index.php?a=milkrecording&all=true">Show All</a><br />';
echo '<table>';
$today = strtotime('1am');
foreach($data['current'] as $id => $row) {
	echo '<tr>';
	echo '<td style="font-size: xx-large;">'.$row['stall'].'.</td>';
	echo '<td style="font-size: xx-large;">'.$row['cow'].'</td>';
	echo '<td><a href="index.php?a=edit&amp;stamp='.$row['stamp'].'&amp;cow='.$row['cow'].'&amp;stall='.$row['stall'].'"><img src="edit.png" /></a></td>';
	echo '<td><a href="index.php?a=insert&stamp='.$row['stamp'].'"><img src="add.png" /></a></td>';
	echo '<td><a href="index.php?a=delete&stamp='.$row['stamp'].'&amp;cow='.$row['cow'].'"><img src="delete.png" /></a></td>';
	if($data['prev'][$id]['stamp'] > $today) echo '<td><a href="index.php?a=insert&amp;cow='.$data['prev'][$id]['cow'].'+round+again&amp;stamp='.$row['stamp'].'"><img src="round-again.png" />'.$data['prev'][$id]['cow'].' round again</a></td>';
	else echo '<td>&nbsp;</td>';
	echo '</tr>';
}
echo '</table>';
?>