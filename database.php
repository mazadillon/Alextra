<html>
<body>
<?php
/*
Interesting Fields in Various Tables:
	TblCow: CowNo, Transponder, Pregnant (Bool), BreedingState (int),
			DateBirth, DateHeat, DateCalving, Lactation, SeparateOnce,
			DriedOffDate, CowID, DateInsem, MilkTimeToday1, MilkTimeToday2
	TblCowB: CowNo, IDTimeTodayMM1, IDTimeTodayMM2, IDTimeYesterMM1, IDTimeYesterMM2,
			IDTimeTodaySS1, IDTimeTodaySS2, IDTimeYesterSS1, IDTimeYesterSS2,
			MilkTimeTodaySS1, MilkTimeTodaySS2, MilkTimeYesterSS1, MilkTimeYesterSS2
*/

$conn=odbc_connect('alpro',"","61016622");
if (!$conn) exit("Connection Failed: " . $conn);
if(gmdate('a') == 'am') $sort = 'MilkTimeToday1';
else $sort = 'MilkTimeToday2';
$sql="SELECT Top 5 CowNo,MilkTimeToday1 as AM,MilkTimeToday2 as PM FROM TblCow WHERE ".$sort." IS NOT NULL ORDER BY ".$sort." DESC";
$rs=odbc_exec($conn,$sql);
if (!$rs) exit("Error in SQL");
echo "<table><tr>";
echo "<th>Cow</th>";
echo "<th>AM</th><th>PM</th></tr>";
while (odbc_fetch_row($rs))
  {
  echo '<tr><td>';
  print odbc_result($rs,"CowNo").'</td><td>';
  print substr(odbc_result($rs,"AM"),11).'</td><td>';
  print substr(odbc_result($rs,"PM"),11).'</td></tr>';
  }
odbc_close($conn);
echo "</table>";
?>

</body>
</html> 
