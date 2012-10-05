<form action="index.php" method="get">
Cow Number: <input type="text" name="cow" />
<input type="hidden" name="a" value="cowstatus" />
<input type="submit" value="Lookup" /></form>
<?php
if($data) {
	echo '<table border="1">';
	foreach($data as $key => $value) {
		echo '<tr><th>'.$key.'</th><td>'.$value.'</td></tr>';
	}
	echo '</table>';
}
?>