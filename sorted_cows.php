<?php
require 'alextra.class.php';
if(!isset($_GET['sort']) OR empty($_GET['sort'])) $_GET['sort'] = 'cow';
if(!isset($_GET['date'])) $_GET['date'] = date('Y-m-d');
$alpro->sortedCows();
$cows = $alpro->listSortedCows($_GET['date'],$_GET['sort']);
include 'templates/header.htm';
?>
<form action="sorted_cows.php" method="get">
<table>
<tr>
<?php
echo '<td>Date: <input type="text" name="date" value="'.$_GET['date'].'" /></td>';
echo '<td><select name="sort"><option value="cow">Sort By:</option>';
echo '<option value="time">Time</option>';
echo '<option value="cow">Cow Number</option>';
echo '</select>';
?>
<td><button type="submit">Search</button></td>
</tr>
</table>
</form>
</div>
<div id="main">
<?php
echo count($cows).' cows sorted on '.date('d/m/Y',strtotime($_GET['date']));
?>
<table>
<tr><th>Cow</th><th>Time</th></tr>
<?php
//echo count($cows) .' animals shed on '.$_POST['date'].'<br />';
if($cows && !empty($cows)) {
	foreach($cows as $cow) {
		echo '<tr><td>'.$cow['cow'].'</td><td>'.$cow['time']."</td</tr>\n";
	}
} else echo '<tr><td colspan="2">No cows shed on this date</td></tr>';
?>
</table>
</body>
</html>
	