<?php
include 'alextra.class.php';
if(!isset($_GET['high']) OR empty($_GET['high'])) $_GET['high'] = false;
if(!isset($_GET['cow']) OR empty($_GET['cow'])) $_GET['cow'] = false;
if(!isset($_GET['start']) OR empty($_GET['start'])) $_GET['start'] = date('Y-m-d');
if(!isset($_GET['end']) OR empty($_GET['end'])) $_GET['end'] = date('Y-m-d');
if(!isset($_GET['sort']) OR empty($_GET['sort'])) $_GET['sort'] = false;
$data = $alpro->filter($_GET['cow'],$_GET['high'],$_GET['start'],$_GET['end'],$_GET['sort']);
include 'templates/header.htm';
?>
<div id="header">
<form action="filter.php" method="get">
<table>
<tr>
<?php
echo '<td>Cow: <input type="text" name="cow" value="'.$_GET['cow'].'" /></td>';
echo '<td>Start Date: <input type="text" name="start" value="'.$_GET['start'].'" /></td>';
echo '<td>End Date: <input type="text" name="end" value="'.$_GET['end'].'" /></td>';
echo '<td>High Activity: <input type="checkbox" name="high" value="yes" ';
if(isset($_GET['high']) && $_GET['high'] == 'yes') echo 'checked="checked" ';
echo '/></td>';
echo '<td><select name="sort"><option value="cow">Sort By:</option>';
echo '<option value="cow">Cow Number</option>';
echo '<option value="date">Date</option>';
echo '<option value="am">Milking AM</option>';
echo '<option value="pm">Milking PM</option>';
echo '<option value="id_am">ID AM</option>';
echo '<option value="id_pm">ID PM</option>';
echo '<option value="sort_id_am">Sort ID AM</option>';
echo '<option value="sort_id_pm">Sort ID PM</option>';
echo '<option value="activity">Activity</option></select>';
?>
<td><button type="submit">Filter</button></td>
</tr>
</table>
</form>
</div>
<div id="main">
<table>
<tr><th>Cow</th><th>Date</th><th>Milking AM</th><th>Milking PM</th><th>ID AM</th><th>ID PM</th><th>Sort Gate ID AM</th><th>Sort Gate ID PM</th><th>Activity Level</th></tr>
<?php
for($i=0;$i<count($data);$i++) {
	if(is_int($i / 2)) echo '<tr class="highlight">';
	else echo '<tr>';
	echo '<td><a href="/parlour/index.php?a=cowstatus&cow='.$data[$i]['cow'].'">'.$data[$i]['cow'].'</a></td>';
	echo '<td>'.$data[$i]['date'].'</td>';
	echo '<td>'.$data[$i]['am'].'</td>';
	echo '<td>'.$data[$i]['pm'].'</td>';
	echo '<td>'.$data[$i]['id_am'].'</td>';
	echo '<td>'.$data[$i]['id_pm'].'</td>';
	echo '<td>'.$data[$i]['sort_id_am'].'</td>';
	echo '<td>'.$data[$i]['sort_id_pm'].'</td>';
	echo '<td>'.$data[$i]['activity'].'</td>';
	echo "</tr>\n";
}
if($data == false) {
	echo '<tr><td colspan="5">No Records</td></tr>';
}
?>
</table>
</div>
</html>
