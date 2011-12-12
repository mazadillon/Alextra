<?php
require_once 'alextra.class.php';
switch($_GET['a']) {
	case 'backup':
	$alpro->backup_database();
	break;
	
	case 'uniform':
	$alpro->importFromUniform();
	echo 'Imported data from Uniform Agri';
	break;
	
	case 'missing_extra':
	if(!isset($_GET['date'])) $_GET['date'] = false;
	$data = $alpro->fetchMissingExtra($_GET['date']);
	$start = date('Y-m-d',strtotime(date('Y-m-').'01'));
	echo 'Missing Cows<br />';
	foreach($data['missing'] as $cow) echo '<a href="filter.php?cow='.$cow['cow'].'&amp;start='.$start.'">'.$cow['cow'].'</a><br />';
	echo 'Extra Cows<br />';
	foreach($data['extra'] as $cow) echo '<a href="filter.php?cow='.$cow['cow'].'&amp;start='.$start.'">'.$cow['cow'].'</a><br />';
	break;
	
	case 'alproFields':
	$alpro->alproFields();
	break;
	
	case 'reset':
	$alpro->resetTimesToday();
	break;
	
	case 'sorted':
	if(!isset($_GET['sort']) OR empty($_GET['sort'])) $_GET['sort'] = 'cow';
	if(!isset($_GET['date'])) $_GET['date'] = date('Y-m-d');
	$cows = $alpro->listSortedCows($_GET['date'],$_GET['sort']);
	include 'templates/header.htm';
	include 'templates/sorted.htm';
	break;
	
	case 'dodgycollars':
	$dodgy = $alpro->dodgyCollarsStatus(21);
	$start = date('Y-m-d',strtotime('-21 days'));
	include 'templates/header.htm';
	echo '</div><table><tr><th>Cow</th><th>Missed Milkings</th></tr>';
	foreach($dodgy as $cow => $misses) {
		echo '<tr><td><a href="filter.php?start='.$start.'&amp;cow='.$cow.'">'.$cow.'</a></td><td>'.$misses.'</td></tr>';
	}
	echo '</table>';
	break;
	
	case 'milking_summaries':
	$data = $alpro->milkingSummaries();
	include 'templates/header.htm';
	include 'templates/milking_summaries.htm';
	break;
	
	default:
	include 'templates/header.htm';
	$data = $alpro->dashboard();
	include 'templates/dashboard.htm';
}
?>