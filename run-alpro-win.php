<?php
/*

This script checks if alprowin.exe is running,
if it's not then it starts it.

*/
$info = explode("\n",`tasklist`);
$running = false;
foreach($info as $process) {
	$process = explode(" ",$process);
	if($process[0] == 'AlproWin.exe') $running = true;
}
if($running) echo 'AlproWin.exe is running';
else {
	echo 'AlproWin is not running, starting';
	pclose(popen("start /B C:\\Alpro\AlproWin.exe", "r"));
}
?>