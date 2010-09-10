<?php
$info = explode("\n",`tasklist`);
$running = false;
foreach($info as $process) {
	$process = explode(" ",$process);
	if($process[0] == 'AlproWin.exe') $running = true;
}
if($running) echo 'AlproWin.exe is running';
else {
	echo 'AlproWin is not running';
	echo exec('psexec.exe /acceptEula -i -u Ford -p silver -d C:\Alpro\AlproWin.exe');
}
?>