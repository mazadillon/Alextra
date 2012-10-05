<?php
include "alextra.class.php";
$data = $alpro->queryAll("SELECT * FROM status");
foreach($data as $row) {
	echo $row['cow'].','.json_encode($row)."\n";
}
?>