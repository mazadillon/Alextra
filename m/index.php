<?php
require_once '../alextra.class.php';
require_once '../uniform.class.php';
$uni = new Uniform();
if(!isset($_GET['cow'])) $_GET['cow'] = false;
echo '<?xml version="1.0"?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN"
"http://www.wapforum.org/DTD/wml_1.1.xml">
<wml>';
echo '<a href="/"><img src="/parlour/home.png" border="0" /></a>';
echo '<h1>Cow Status</h1>';
echo '<form action="index.php" method="get"><input type="text" name="cow" value="'.$_GET['cow'].'" />';
echo '<input type="submit" value="Go" /></form>';
if($_GET['cow'] && ($data = $uni->panelStatus($_GET['cow'])) !== false) {
	foreach($data as $k => $v) echo '<b>'.$k.'</b> '.$v.'<br />';
}
echo '</wml>';
?>