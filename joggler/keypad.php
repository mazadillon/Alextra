<html>
<head>
<style type="text/css">
input {
	font-size: xx-large;}
input[type="button"] {
	padding: 0.5em;}
input[type="text"] {
	width: 6em;}
</style>
<script type="text/javascript">
var string;
string = "";

function keypress(key) {
	string = string + key;
	document.keypad.output.value = string;
}
function backspace() {
	string = string.substring(0,string.length - 1);
	document.keypad.output.value = string;
}
function sendcow() {
	string = document.form.cow.value;
	document.keypad.output.value = string;
}
function getcow() {
	document.form.cow.value = string;
}
function sendstall() {
	string = document.form.stall.value;
	document.keypad.output.value = string;
}
function getstall() {
	document.form.stall.value = string;
}

</script>
</head>
<body>
<table>
<tr><td>
<form action="<?php
	echo $_SERVER['PHP_SELF'].'?';
	echo 'a='.$_GET['a'].'&amp;';
	foreach($keys as $key) {
		echo $key.'='.$_GET[$key].'&amp;';
	}
?>
" method="post" name="form">
<?php
foreach($fields as $field) {
	echo ucwords($field).': <input type="text" name="'.$field.'" value="';
	if(isset($_GET[$field])) echo $_GET[$field];
	echo '" />
	<input type="button" value="<-" onClick="get'.$field.'()" />
	<input type="button" value="->" onClick="send'.$field.'()" /><Br />';
}
?>
<input type="submit" value="Go" /></form>
</td><td>
<form name="keypad">
<input type="text" name="output" /><br />
<input type="button" value="7" onClick="keypress('7')" />
<input type="button" value="8" onClick="keypress('8')" />
<input type="button" value="9" onClick="keypress('9')" /><br />
<input type="button" value="4" onClick="keypress('4')" />
<input type="button" value="5" onClick="keypress('5')" />
<input type="button" value="6" onClick="keypress('6')" /><br />
<input type="button" value="1" onClick="keypress('1')" />
<input type="button" value="2" onClick="keypress('2')" />
<input type="button" value="3" onClick="keypress('3')" /><br />
<input type="button" value="0" onClick="keypress('0')" />
<input type="button" value="<----" onClick="backspace()" />
</form>
</td></tr></table>
</body>
</html>