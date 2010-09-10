<html>
<head>
<style type="text/css">
body {
	font-size: 2em;
input {
	font-size: medium;}
</style>
</head>
<body>
<?php
require "class.php";

$mr = new MilkRecording(date('Y-m-d').'.csv');

if(isset($_POST['number']) && isset($_POST['stall'])) {
	if($mr->addEntry($_POST['stall'],$_POST['number'])) {
		//echo "Number Sent.<br />";
		$_POST['stall']++;
	}
	else echo "Error adding number - duplicate?<br />";
} elseif(isset($_GET['delete'])) {
	$mr->deleteEntry();
	echo 'Deleted<br />';
}

$mr->loadFile();
$list = @$mr->displayList(3);
if(is_array($list)) foreach($list as $line) echo $line['stall'].": ".$line['number']."<br />\n";

if(!isset($line['stall'])) $stall = 1;
else $stall = $line['stall'] + 1;
if($stall > 40) $stall = 1;

echo "<form action=\"input.php\" method=\"post\">";
echo "<input type=\"text\" name=\"stall\" size=\"3\" value=\"".$stall."\"> ";
echo "<input type=\"text\" name=\"number\" size=\"5\" value=\"\">";
echo "<input type=\"submit\" name=\"submit\" value=\"Add\">";
echo "</form>";
if(isset($mr->lines)) echo $mr->lines.' cows entered';
echo '. <a href="input.php?delete=true">Undo</a>';
?>
</body>
</html>