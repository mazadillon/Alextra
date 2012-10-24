<?php
require '../alextra.class.php';
if(!isset($_GET['a'])) $_GET['a'] = false;

switch($_GET['a']) {

	case 'delete':
	$alpro->removeCow($_GET['cow'],$_GET['stamp']);
	header("Location: index.php?a=milkrecording");
	break;
	
	case 'insert':
	if(isset($_REQUEST['cow'])) {
		echo 'Inserting cow '.$_REQUEST['cow'].' before '.$_GET['stamp'];
		$alpro->insertCow($_REQUEST['cow'],$_GET['stamp']);
		header("Location: index.php?a=milkrecording");
	} else {
		$keys[] = 'stamp';
		$fields[] = 'cow';
		include 'keypad.php';
	}
	break;

	case 'edit':
	if(isset($_POST['stall'])) {
		$alpro->editStall($_POST['cow'],$_GET['stamp'],$_POST['stall']);
		header("Location: index.php?a=milkrecording");
	} else {
		$keys[] = 'stamp';
		$fields[] = 'stall';
		$fields[] = 'cow';
		include 'keypad.php';
	}
	break;
	
	case 'milkrecording':
	if(!isset($_GET['all'])) {
		include 'reload.htm';
		$data = $alpro->jogglerMilkRecording(false);
	} else $data = $alpro->jogglerMilkRecording(true);
	$total = $alpro->milkingTotal(date('a'));
	$milking_speed = $alpro->milkingSpeed();
	include 'milk-recording.htm.php';
	break;
	
	case "status":
	print_r($alpro->dataStatus());
	break;
	
	case 'milking':
	$milking_status = $alpro->milkingTotal($alpro->currentMilking());
	$milking_speed = $alpro->milkingSpeed();
	$data = $alpro->jogglerBasic();
	$sorted = $alpro->sortedRecent();
	include 'milking_panel.htm.php';
	break;
	
	case 'cowstatus':
	include '../templates/header.htm';
	if(isset($_GET['cow'])) $data =	$alpro->cowStatus($_GET['cow']);
	else $data = false;
	include 'cowstatus.htm.php';
	break;
	
	case 'serving':
	$milking_status = $alpro->milkingTotal($alpro->currentMilking());
	$milking_speed = $alpro->milkingSpeed();
	$data = $alpro->jogglerServing(20);
	include 'milking_panel.htm.php';
	break;
	
	case 'exit':
	$milking_status = $alpro->milkingTotal(date('a'));
	$milking_speed = $alpro->milkingSpeed();
	$data = $alpro->jogglerExit();
	include 'milking_panel.htm.php';
	break;
	
	case 'locomotion':
	$milking_status = $alpro->milkingTotal(date('a'));
	$milking_speed = $alpro->milkingSpeed();
	$data = $alpro->locomotionList(5);
	include 'locomotion.htm.php';
	break;

	default:
	echo '<html><head><style type="text/css">body {text-align: center;}table{text-align:center;width:100%;}td{padding:1em;}</style></head><body>';
	echo '<div style="text-align:left;width:100%;"><a href="/"><img src="home.png" /></a></div>';
	echo '<table><tr><td><a href="index.php?a=milking"><img src="cow.gif" /><h1>Milking</h1></td>';
	echo '<td><a href="index.php?a=serving"><img src="bull.gif" /><h1>Serving</h1></td>';
	echo '<td><a href="index.php?a=milkrecording"><img src="milk-meter.jpg" /><h1>Milk<br />Recording</h1></td>';
	echo '<td><a href="index.php?a=locomotion"><img src="hoof.png" /><h1>Locomotion<br />Scoring</h1></td>';
	echo '<td><a href="index.php?a=cowstatus"><img src="status.jpg" /><h1>Cow<br />Status</h1></td>';
	echo '</tr></table>';
	break;
}
?>
