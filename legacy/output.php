<html>
<head>
<style type="text/css">
body {
	font-size: 2em;
	margin-left: 10%;
	margin-right: 10%;}
table {
	padding: 0px;
	margin: 0px;}
input {
	font-size: medium;}
div.status {
	position:fixed;
	margin: 0px;
	top: 2em;
	left: auto;
	right: 2em;
	text-align:center;}
</style>
</head>
<body>
<?php
require "class.php";

$file = date('Y-m-d').'.csv';
if(!file_exists($file)) {
	echo "<pre><small>";
	echo `C:\windows\system32\ipconfig.exe`;
	echo "</small></pre>";
} else {
	$mr = new MilkRecording($file);
	if(isset($_GET['all'])) {
		echo "<div class=\"status\" style=\"background-color:red;color:white;link:white\"><b>All Entries</b><br /><br />(<a href=\"output.php\">Show recent entries</a>)</div>";
		$list = $mr->displayList(false);
	} else {
		echo "<div class=\"status\" style=\"background-color:green;color:white;link:white\"><b>Recent Entries</b><br /><br />(<a href=\"output.php?all=true\">Show all entries</a>)</div>";
			$list = $mr->displayList(12);
	}
	echo "Stall : Cow<br />";
	if(is_array($list)) foreach($list as $line) echo $line['stall']." : &nbsp; &nbsp; ".$line['number']."<br />\n";
}
echo "<meta http-equiv=\"refresh\" content=\"5\" />";
?>
