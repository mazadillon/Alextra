<?php
error_reporting(E_ALL);
require_once 'alextra.class.php';
require_once 'uniform.class.php';
$uni = new Uniform();
if(!isset($_GET['a'])) $_GET['a'] = false;
switch($_GET['a']) {
	case 'backup':
	$alpro->backup_database();
	break;
	
	case 'uniform':
	$alpro->importFromUniform();
	echo 'Imported data from Uniform Agri';
	break;
	
	case 'csvSearch':
	$alpro->csvSearch('1');
	break;
	
	case 'cidrSync':
	$sync = $uni->lookupHealthEvent('CIDR Synchronise');
	$cows = $uni->healthReporting($sync['CODEZIEKTE'],'2012-09-23','2012-09-25');
	$numbers = array();
	foreach($cows as $cow) {
		$numbers[] = $cow['NUMMER'];
	}
	$cows = $numbers;
	sort($cows);
	include 'templates/sort.htm';
	break;
	
	case 'notSeenBulling':
	$return = $uni->notSeenBulling(date('Y-m-d'));
	echo '<h1>Not Seen Bulling</h1>';
	echo 'Anything not seen bulling out of the <b>'.$return['eligible'].'</b> eligible cows.';
	$cows = $return['cows'];
	include 'templates/sort.htm';
	break;
	
	case 'pregnantJohnes':
	echo '<h1>Pregnant Cows with Johnes High or Med</h1>';
	$cows = $uni->pregnantJohnes();
	include 'templates/sort.htm';
	break;
	
	case 'eligibleToServe':
	echo '<h1>Eligible For Service Today</h1>';
	echo '<p>Namely those not in calf, pregnant, barren or dry which are marked clean and over 40 days calved</p>';
	$cows = $uni->eligibleToServe(date('Y-m-d'));
	include 'templates/sort.htm';
	break;
	
	case 'freshCows':
	if(!isset($_GET['limit'])) $limit = 60;
	else $limit = $_GET['limit'];
	echo '<h1>Fresh Cow Checks</h1>';
	echo '<form action="index.php" method="get"><input type="text" name="limit" value="'.$limit.'" />';
	echo '<input type="hidden" name="a" value="freshCows" /><input type="submit" value="Load" /></form>';
	$cows = $uni->freshCowChecks($limit);
	include 'templates/sort.htm';
	break;
	
	case 'aborted':
	$data = $uni->abortedCows();
	foreach($data as $abortion) {
		echo $abortion['NUMMER'].' '.$abortion['DATUMBEGIN']."<br />";
	}
	$uni->abortedTree();
	break;
	
	case 'familyTree':
	$uni->familyTree();
	break;
	
	case 'fertilityBreakdown':
	$data = $uni->fertilityBreakdown();
	echo '<h1>Fertility Breakdown</h1>';
	echo '2011 Calvers: '.$data['round_year']['COUNT'].'<br />';
	echo 'Spring 2012 Calvers: '.$data['round_spring']['COUNT'].'<br />';
	echo 'Summer 2012 Calvers: '.$data['summer']['COUNT'].'<br />';
	$total = $data['round_year']['COUNT'] + $data['round_spring']['COUNT'] + $data['summer']['COUNT'];
	echo '<b>Total Eligible Cows:</b> '. $total.'<br />';
	echo 'Bulling Heifers: '.$data['heifers']['COUNT'].'<br />';
	$plus = $total+$data['heifers']['COUNT'];
	echo '<b>Total to Serve:</b> '.$plus.'<br /><br />';
	echo 'Dry: '.$data['dry']['COUNT'].'<br />';
	echo 'Due Spring 2013: '.$data['pregnant']['COUNT'].'<br />';
	echo 'Barren: '.$data['barren']['COUNT'].'<br />';
	break;
	
	case 'dueByWeek':
	if(!isset($_GET['start'])) {
		$data = $uni->dueEachWeek();
		include 'templates/dueeachweek.htm';
	} else {
		$data = $uni->dueByWeek($_GET['start']);
		include 'templates/duebyweek.htm';
	}
	break;
	
	case 'kpis':
	$cull['2012'] = $uni->kpi_cullage(2012);
	$cull['2011'] = $uni->kpi_cullage(2011);
	$cull['2010'] = $uni->kpi_cullage(2010);
	$cull['2009'] = $uni->kpi_cullage(2009);
	$cull['2008'] = $uni->kpi_cullage(2008);
	$sixweeks['2011'] = $uni->kpi_preg6weeks('2011-10-01');
	$sixweeks['2010'] = $uni->kpi_preg6weeks('2010-10-01');
	$sixweeks['2009'] = $uni->kpi_preg6weeks('2009-10-01');
	$sixweeks['2008'] = $uni->kpi_preg6weeks('2008-10-01');
	$scc = $uni->kpi_scc();
	$sub = $uni->kpi_submission('2011-10-01',12);
	$loc['2011'] = $uni->kpi_locomotion('2011-01-01','2011-12-31');
	$loc['2012'] = $uni->kpi_locomotion('2012-01-01','2012-12-31');
	$first['2010'] = $uni->kpi_firstService('2010-10-01','2010-12-24');
	$first['2011'] = $uni->kpi_firstService('2011-10-01','2011-12-24');
	include 'templates/kpis.htm';
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
	
	case 'locomotionTrim':
	$cows = $uni->locomotionTrim('2012-03-14',30,100);
	include 'templates/sort.htm';
	break;
	
	case 'trimBeforeDry':
	echo 'Looking for cows not yet trimmed 80 days before drying off';
	$cows = $uni->trimBeforeDry(80);
	include 'templates/sort.htm';
	break;
	
	case 'fedYest':
	print_r($alpro->fedYesterday());
	break;
	
	case 'sort':
	if(isset($_POST['days'])) {
		if($_POST['milking'] == 'am') $am = true;
		else $am = false;
		if($am) $pm = false;
		else $pm = true;
		$count = 0;
		foreach($_POST['cows'] as $cow) if($uni->sortCow($cow,$_POST['wait'],$_POST['days'],$am,$pm)) $count++;
		echo $count.' sorted in uniform.<br /><h2>Transfer to Processor</h2>';
		echo '<ol><li>Open up Uniform Agri</li>';
		echo '<li>Go to Links and then Feed / Milk Interface</li>';
		echo '<li>Transfer from Management to Process</li>';
		echo '<li>Tick Seperation</li>';
		echo '<li>Choose next</li>';
		echo '<li>Click finish</li>';
		echo '<li>Once the progress bar gets to 100% click Yes to transfer to processor</li></lo>';
	} else echo 'No cows found to sort';
	break;
	
	case 'dueSort':
	if(isset($_GET['date'])) {
		print_r($uni->dueSort($_GET['date']));
	} else print_r($uni->dueSort(false));
	break;
	
	case 'needsactmeter':
	include 'templates/header.htm';
	$data = $uni->needsActMeter();
	echo '<h1>Going Round Again and Needing Activity Meters</h1>';
	echo 'Total: '.count($data['needs']).'. In Use: '.$data['inuse']['COUNT'].'<br />';
	foreach($data['needs'] as $cow) {
		echo $cow['NUMMER'].' '.$uni->config['status'][$cow['STATUS']].'<br />';
		print_r($this->odbcFetchAll("SELECT * FROM TblCowRelActLvlHistory"));
		exit;
	}
	break;
	
	case 'reconcileActTags':
	$uni->reconcileActTags();
	echo 'Done';
	break;
	
	case 'cullsToAlpro':
	$alpro->cullsToAlpro();
	break;
	
	case 'importLocomotion':
	if(isset($_POST['date'])) {
		$data = file_get_contents($_FILES['csv']['tmp_name']);
		$data = explode("\n",$data);
		$cows = array();
		foreach($data as $cow) {
			$cow = explode(',',$cow);
			$cows[] = array('cow'=>$cow[0],'score'=>$cow[1]);
		}
		$uni->importLocomotionScores($_POST['date'],$_POST['handler'],$cows);
	} else include 'templates/locomotionupload.htm';
	break;
	
	case 'importJohnes':
	if(isset($_POST['data'])) {
		$uni->importJohnesTest($_POST['data']);
	} else include 'templates/importjohnes.htm';
	break;
	
	case 'johnes':
	$data = $uni->johnesHerdwise();
	include 'templates/johnes_herdwise.htm.php';
	break;
	
	case 'johnes_old':
	$data = $uni->johnesCows();
	echo '<h1>High '.count($data['high']).'</h1>';
	foreach($data['high'] as $cow => $count) {
		echo '<b>'.$cow.'</b> '.$count.' high';
		if(isset($data['med'][$cow])) {
			echo ' '.$data['med'][$cow].' medium';
			unset($data['med'][$cow]);
		}
		if(isset($data['low'][$cow])) {
			echo ' '.$data['low'][$cow].' low';
			unset($data['low'][$cow]);
		}
		echo '<br />';
	}
	echo '<h2>Medium '.count($data['med']).'</h2>';
	foreach($data['med'] as $cow => $count) {
		echo '<b>'.$cow.'</b> '.$count.' medium';
		if(isset($data['low'][$cow])) {
			echo ' '.$data['low'][$cow].' low';
			unset($data['low'][$cow]);
		}
		echo '<br />';
	}
	echo '<h1>Raw</h1>';
	echo 'High:<br />';
	foreach($data['high'] as $cow => $count) echo $cow.', ';
	echo '<br />Medium:<br />';
	foreach($data['med'] as $cow => $count) echo $cow.', ';
	break;
	
	case 'calvingQsum':
	error_reporting(0);
	$conditions[] = $uni->lookupHealthEvent('Milk Fever');
	$conditions[] = $uni->lookupHealthEvent('Dirty');
	$conditions[] = $uni->lookupHealthEvent('Clinical Mastitis');
	$conditions[] = $uni->lookupHealthEvent('Metritis');
	$conditions[] = $uni->lookupHealthEvent('Prolapse');
	$conditions[] = $uni->lookupHealthEvent('Retained Afterbirth');
	$conditions[] = $uni->lookupHealthEvent('Ketosis');
	$conditions[] = $uni->lookupHealthEvent('LDA');
	$conditions[] = $uni->lookupHealthEvent('Lame');
	$data = $uni->calvingQsum('2012-06-01','2012-10-01',30,$conditions);
	echo '<table border="1"><tr><th>Cow</th><th>Status</th><th>Calving Date</th><th>Calving Ease</th>';
	foreach($conditions as $cond) {
		echo '<th>'.$cond['OMSCHRIJVING'].'</th>';
	}
	echo '<th>Average SCC During Serving</th>';
	echo '</tr>';
	foreach($data as $cow) {
		echo '<tr><td>'.$cow['NUMMER'].'</td>';
		if($cow['STATUS'] < 5) echo '<td style="background-color: red;">';
		else echo '<td>';
		echo $uni->config['status'][$cow['STATUS']].'</td>';
		$calvedate = strtotime($cow['LAATSTEKALFDATUM']);
		$calvingweek = date('W',$calvedate);
		if(isset($calvingweeks[$calvingweek])) $calvingweeks[$calvingweek]['calved']++;
		else $calvingweeks[$calvingweek]['calved'] = 1;
		if($cow['STATUS'] < 5) $calvingweeks[$calvingweek]['minus']++;
		echo '<td>'.$cow['LAATSTEKALFDATUM'].'</td><td>'.$uni->config['calvingease'][$cow['AFKALFVERLOOP_CODE']].'</td>';
		if($cow['AFKALFVERLOOP_CODE'] > 1) {
			$calvcount++;
			if($cow['STATUS'] < 5) $minuscalvcount++;
		}
		if($cow['STATUS'] < 5) $minuscount++;
		foreach($conditions as $id => $cond) {
			echo '<td>';
			if($cow[$cond['OMSCHRIJVING']]) {
				if($cow[$cond['OMSCHRIJVING']] !== true) echo $cow[$cond['OMSCHRIJVING']];
				else echo 'Yes';
				if($cow['STATUS'] < 5) $conditions[$id]['minuscount']++;
				$conditions[$id]['count']++;
			} else echo '&nbsp;';
			echo '</td>';
		}
		
		if($cow['scc'] > 200) {
			$scccount++;
			if($cow['STATUS'] < 5) {
				$sccminuscount++;
				echo '<td style="background-color: red;">';
			} else echo '<td style="background-color: orange;">';
		} else echo '<td>';
		echo $cow['scc'].'</td>';
		echo '</tr>';
	}
	echo '<tr><th>Totals</th><th>'.count($data).'</td><td>&nbsp;</td>';
	echo '<td>'.$calvcount.' ('.round($calvcount/count($data)*100,0).'%)</td>';
	foreach($conditions as $cond) {
		echo '<td>'.$cond['count'].' ('.round($cond['count']/count($data)*100,0).'%)';
		echo '</td>';
	}
	echo '<td>'.$scccount.' ('.round($scccount/count($data)*100,0).'%)</td>';
	echo '</tr>';
	echo '<tr><td>PD-</td><td>';
	echo $minuscount.' ('.round($minuscount/count($data)*100,0).'%)</td><td>&nbsp;</td>';
	echo '<td>'.$minuscalvcount.' ('.round($minuscalvcount/$minuscount*100,0).'%)</td>';
	foreach($conditions as $cond) {
		echo '<td>'.$cond['minuscount'];
		echo ' ('.round($cond['minuscount']/$minuscount*100,0).'%)</td>';
	}
	echo '<td>'.$sccminuscount.' ('.round($sccminuscount/$minuscount*100,0).'%)</td>';
	echo '</tr>';
	echo '</table>';
	ksort($calvingweeks);
	foreach($calvingweeks as $id => $week) {
		echo $id.' '.$week['minus'].'/'.$week['calved'].' '.round($week['minus']/$week['calved']*100,0).'%<br />';
	}
	break;
	
	case 'healthReporting':
	$condition = $uni->lookupHealthEvent($_GET['condition']);
	if($condition) {
		echo '<h1>'.$condition['OMSCHRIJVING'].'</h1>';
		$data = $uni->healthReporting($condition['CODEZIEKTE'],'2011-01-01','2012-03-01');
		foreach($data as $cow) {
			echo $cow['NUMMER'].' '.$cow['DIERID'].' '.$cow['DATUMZIEKTE'].'<br />';
		}
	} else die('Unknown Condition');
	break;

	case 'footTrimming':
	if(!isset($_POST['routine']) OR $_POST['routine']!='checked') $routine = false;
	else $routine = true;
	if(!isset($_POST['days'])) $days = 180;
	else $days = $_POST['days'];
	if(!isset($_POST['routine_limit'])) $routine_limit = false;
	else $routine_limit = $_POST['routine_limit'];
	if(!isset($_POST['loco']) OR $_POST['loco']!='checked') $loco = false;
	else $loco = true;
	if(!isset($_POST['predry']) OR $_POST['predry']!='checked') $predry = false;
	else $predry = true;
	if(!isset($_POST['recheck']) OR $_POST['recheck']!='checked') $recheck = false;
	else $recheck = true;
	if(!isset($_POST['predry_limit'])) $predry_limit = false;
	else $predry_limit = $_POST['predry_limit'];
	if($routine) $data = $uni->footTrimming($days,$routine_limit);
	else $data = array();
	$routine_count = count($data);
	if($loco) {
		$t = $uni->locomotionTrim('2012-03-14',30,100);
		if($t)$loco_count = count($t);
		else $loco_count = 0;
		$data = array_merge($data,$t);
	} else $loco_count = 0;
	if($predry) {
		$t = $uni->trimBeforeDry(80,$predry_limit);
		$predry_count = count($t);
		$data = array_merge($data,$t);
	} else $predry_count = 0;
	if($recheck) {
		$t = $uni->footRechecks();
		$recheck_count = count($t);
		$data = array_merge($data,$t);
	} else $recheck_count = 0;
	$data = array_unique($data);
	sort($data);
	include 'templates/foottrimming.htm';
	break;

	case 'alproFields':
	$alpro->alproFields();
	break;
	
	case 'alproSearch':
	$alpro->alproSearch('VARCHAR','once');
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
	$dodgy = $alpro->dodgyCollarsStatus(10);
	$start = date('Y-m-d',strtotime('-10 days'));
	include 'templates/header.htm';
	echo '</div>Missed milkings since '.$start.'<table><tr><th>Cow</th><th>Missed Milkings</th></tr>';
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
