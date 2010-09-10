<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" type="text/css" href="style-status.css" />
<script type="text/javascript"><!--
setTimeout('Redirect()',10000);
function Redirect()
{
  location.href = 'status.php';
}
// --></script>
<body>
<?php
include 'config.php';
/*
Interesting Fields in Various Tables:
	TblCow: CowNo, Transponder, Pregnant (Bool), BreedingState (int),
			DateBirth, DateHeat, DateCalving, Lactation, SeparateOnce,
			DriedOffDate, CowID, DateInsem, MilkTimeToday1, MilkTimeToday2
*/

$conn=odbc_connect('alpro',"","61016622");
if (!$conn) exit("Connection Failed: " . $conn);
if(gmdate('a') == 'am') $sort = 'MilkTimeToday1';
else $sort = 'MilkTimeToday2';
$sql="SELECT Top 5 CowNo,BreedingState,DateCalving,DriedOffDate,DateInsem FROM TblCow WHERE ".$sort." IS NOT NULL ORDER BY ".$sort." DESC";
$rs=odbc_exec($conn,$sql);
if (!$rs) exit("Error in SQL");
echo "<table><tr>";
echo "<th>Cow</th>";
echo "<th>Status</th></tr>";
while($row = odbc_fetch_array($rs))
{
	echo '<tr><td style="font-size: xx-large;">'.$row["CowNo"].'</td>';
	echo '<td class="'.strtolower($status[$row['BreedingState']]).'">'.$status[$row["BreedingState"]].'</td>';
	/*
	$calved = strtotime($row["DateCalving"]);
	$dim = round((date('U') - $calved) / (60 * 60 * 24));
	echo '<td>'.date('M Y',$calved).'</td><td>'.$dim.'</td>';
	$served = strtotime($row["DateInsem"]);
	if($served != 0) echo '<td>'.date('d/m/Y',strtotime($row["DateInsem"])).'</td></tr>'."\n";
	else echo "<td>&nbsp;</td>
	*/
	echo "</tr>\n";
}
odbc_close($conn);
echo "</table>";
?>

</body>
</html> 
