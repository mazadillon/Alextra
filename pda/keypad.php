<?xml version="1.0"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN"
"http://www.wapforum.org/DTD/wml_1.1.xml">
<wml>
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
	echo '" /><Br />';
}
?>
<input type="submit" value="Go" /></form>
</td></tr></table>
</wml>