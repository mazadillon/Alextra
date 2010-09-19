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

//$conn=odbc_connect('alpro',"","61016622");
//$conn = odbc_connect('uniform','matt','silver') or die("Connection error");
$conn = odbc_connect('uniform','dairyland','TLZMnMAhn3I') or die("Connection error");

$result = odbc_tables($conn) or die(odbc_error());
while($row = odbc_fetch_array($result)){
	print_r($row);
	if($row['TABLE_TYPE'] == 'TABLE') {
		echo $row['TABLE_NAME'].'<br />';
		$cols = odbc_columns($conn, 'alpro', '', $row['TABLE_NAME']);
		while($col = odbc_fetch_array($cols)) {
			echo '-->'.$col['COLUMN_NAME'].'<br />';
		}
	}
}
odbc_close($conn);
?>

</body>
</html> 
