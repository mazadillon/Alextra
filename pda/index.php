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
	include 'milk-recording.htm.php';
	break;
	
	case 'milking':
	include 'reload.htm';
	echo $alpro->milkingTotal(date('a')).' cows milked so far.<br />';
	echo '<table>';
	echo '<tr><th>Cow</th><th>Status</th><th>Calved</th><th>DIM</th><th>Served</th></tr>';
	$data = $alpro->jogglerBasic();
	foreach($data as $row) {
		echo '<tr><td style="font-size: xx-large;">'.$row["cow"].'</td>';
		echo '<td class="'.strtolower($status[$row['info']['BreedingState']]).'">'.$status[$row['info']["BreedingState"]].'</td>';
		
		$calved = strtotime($row['info']["DateCalving"]);
		$dim = round((date('U') - $calved) / (60 * 60 * 24));
		echo '<td>'.date('M Y',$calved).'</td><td>'.$dim.'</td>';
		$served = strtotime($row['info']["DateInsem"]);
		if($served != 0) echo '<td>'.date('d/m/Y',strtotime($row['info']["DateInsem"])).'</td></tr>'."\n";
		else echo "<td>&nbsp;</td>";
		echo "</tr>\n";
	}
	break;

	default:
	echo '<html><head><style type="text/css">body {text-align: center;}table{text-align:center;width:100%;}td{padding:1em;}</style></head><body>';
	echo '<table><tr><td><a href="index.php?a=milking"><img src="cow.gif" /><h1>Milking</h1></td>';
	echo '<td><a href="index.php?a=milkrecording"><img src="milk-meter.jpg" /><h1>Milk<br />Recording</h1></td></tr></table>';
	break;
}
?>
