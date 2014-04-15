<?php
error_reporting(E_ALL);
require_once 'alextra.class.php';
require_once 'uniform.class.php';
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
	$alpro->csvSearch('2');
	break;
	
	case 'neospora':
	$alpro->uniform->neosporaCows();
	break;
	
	case 'weightAnalysis':
	$alpro->uniform->weightAnalysis('2014-01-01');
	break;
	
	case 'estrotectCamLatest':
	if(isset($_GET['brand'])) $alpro->estrotectCamImage("latest-brand.jpg");
	else $alpro->estrotectCamImage();
	break;
	
	case 'cidrSync':
	$sync = $alpro->uniform->lookupHealthEvent('CIDR Synchronise');
	$cows = $alpro->uniform->healthReporting($sync['CODEZIEKTE'],'2012-09-23','2012-09-25');
	$numbers = array();
	foreach($cows as $cow) {
		$numbers[] = $cow['NUMMER'];
	}
	$cows = $numbers;
	sort($cows);
	include 'templates/sort.htm';
	break;

	case 'conception':
	echo '<h1>2013</h1>';
	$alpro->uniform->conceptionRate('2013-10-01','2013-11-22');
	echo '<h1>2013 Most Recent</h1>';
	$alpro->uniform->conceptionRate('2013-11-15','2013-11-22');
	break;
	
	case 'conceptionByDay':
	$alpro->uniform->conceptionRateByDay('2013-10-01','2013-12-31');
	$alpro->uniform->conceptionRateByDay('2012-10-01','2012-12-31');
	break;
	
	case 'importWeights':
	// Tru-Test Format:
	// VID,EID,Date,Time,Weight,Comment
	if(isset($_POST['sent'])) {
		$data = file($_FILES['csv']['tmp_name']);
		unset($data[0]);
		$count = 0;
		foreach($data as $line) {
			$row = explode(',',$line);
			if(substr($row[0],0,2) != 'UK') $row[0] = 'UK'.$row[0];
			if($alpro->uniform->importWeight($row[2],$row[3],$row[0],$row[4]) !== false) $count++;
		}
		echo $count.' weights imported';
	} else {
		echo '<h1>Upload Weights From Tru-Test</h1>';
		echo 'CSV Format: VID,EID,Date,Time,Weight,Comment<br />';
		echo 'Header row will be ignored<br />';
		echo '<form action="index.php?a=importWeights" method="post" enctype="multipart/form-data">';
		echo 'Tru-Test CSV File: <input type="file" name="csv" /> <input type="submit" name="sent" value="Upload" /></form>';
	}
	break;
	
	case 'kpi_blockStats':
	echo '<h1>Whole Herd</h1>';
	$blocks = array(2013,2012,2011,2010);
	echo '<table border="1"><tr><th>Year</th><th>Start</th><th>End</th><th>Count</th><th>1<sup>st</sup> Quartile</th><th>Half</th><th>4<sup>th</sup> Quartile</th></tr>';
	foreach($blocks as $block) {
		$data = $alpro->uniform->kpi_blockStats($block.'-06-01',$block.'-12-31');
		echo '<tr><td>'.$block.'</td>';
		foreach($data as $item) echo '<td>'.$item.'</td>';
		echo '</tr>';
	}
	echo '<tr><th>2014</th>';
	$prediction = $alpro->uniform->kpi_expectedBlockStats('2013-10-01','2013-12-31');
	foreach($prediction as $item) echo '<th>'.$item.'</th>';
	echo '</tr>';
	echo '</table>';
	
	echo '<h1>Heifers</h1>';
	$blocks = array(2013,2012,2011,2010);
	echo '<table border="1"><tr><th>Year</th><th>Start</th><th>End</th><th>Count</th><th>1<sup>st</sup> Quartile</th><th>Half</th><th>4<sup>th</sup> Quartile</th></tr>';
	foreach($blocks as $block) {
		$data = $alpro->uniform->kpi_blockStats($block.'-06-01',$block.'-12-31',1);
		echo '<tr><td>'.$block.'</td>';
		foreach($data as $item) echo '<td>'.$item.'</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '<h1>Second Calvers</h1>';
	$blocks = array(2013,2012,2011,2010);
	echo '<table border="1"><tr><th>Year</th><th>Start</th><th>End</th><th>Count</th><th>1<sup>st</sup> Quartile</th><th>Half</th><th>4<sup>th</sup> Quartile</th></tr>';
	foreach($blocks as $block) {
		$data = $alpro->uniform->kpi_blockStats($block.'-06-01',$block.'-12-31',2);
		echo '<tr><td>'.$block.'</td>';
		foreach($data as $item) echo '<td>'.$item.'</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '<h1>Cows (Excluding Heifers)</h1>';
	$blocks = array(2013,2012,2011,2010);
	echo '<table border="1"><tr><th>Year</th><th>Start</th><th>End</th><th>Count</th><th>1<sup>st</sup> Quartile</th><th>Half</th><th>4<sup>th</sup> Quartile</th></tr>';
	foreach($blocks as $block) {
		$data = $alpro->uniform->kpi_blockStats($block.'-06-01',$block.'-12-31',"cows");
		echo '<tr><td>'.$block.'</td>';
		foreach($data as $item) echo '<td>'.$item.'</td>';
		echo '</tr>';
	}
	echo '</table>';
	break;
	
	case 'kpi_served_by_day':
	$data_2013 = $alpro->uniform->kpi_served_by_day('2013-10-01','2013-12-31');
	$data_2012 = $alpro->uniform->kpi_served_by_day('2012-10-01','2012-12-31');
	echo '<table border="1"><tr><th>Date</th><th>Served 2012</th><th>Served 2013</th></tr>';
	foreach($data_2012 as $day => $count) {
		echo '<td>'.$day.'</td><td>'.$count.'</td><td>';
		$date = date('Y-m-d',strtotime($day. ' + 1 year'));
		if(isset($data_2013[$date])) echo $data_2013[$date];
		else echo '&nbsp;';
		echo "</td><tr>\n";
	}
	echo '</table>';
	break;
	
	case 'good_breeders':
	$alpro->uniform->goodBreeders();
	break;
	
	case 'test':
	$alpro->importDairyDataNML();
	break;
	
	case 'twins':
	echo 'The Following Cows Have Been PDed since 1st Oct 2012 with a comment containing "Twins"<br />';
	$twins = $alpro->uniform->findTwins();
	foreach($twins as $cow) echo $cow.'<br />';
	break;
	
	case 'notSeenBulling':
	$return = $alpro->uniform->notSeenBulling(date('Y-m-d'));
	echo '<h1>Not Seen Bulling</h1>';
	echo 'Anything not seen bulling out of the <b>'.$return['eligible'].'</b> eligible cows.';
	$cows = $return['cows'];
	include 'templates/sort.htm';
	break;
	
	case 'pregnantJohnes':
	echo '<h1>Pregnant Cows with Johnes High or Med</h1>';
	$cows = $alpro->uniform->pregnantJohnes();
	include 'templates/sort.htm';
	break;
	
	case 'calvesExpectedByWeek':
	echo '<h1>Calves Expected Each Week From Now</h1>';
	echo '<p>Only those confirmed PD+ are shown, assumes last bull served with has held (may not be true for all)</p>';
	$data = $alpro->uniform->calvesExpectedByWeek();
	include 'templates/calvesbyweek.htm';
	break;
	
	case 'importNML':
	if(isset($_POST['data'])) $alpro->importNML($_POST['data']);
	else include 'templates/importnml.htm';
	break;
	
	case 'checkdrys':
	if(isset($_POST['data'])) {
		$drys = explode("\n",$_POST['data']);
		$drys = $alpro->uniform->checkDryList($drys);
		foreach($drys as $dry) echo $dry."<br />\n";
	}
	else include 'templates/checkdrys.htm';
	break;
	
	case 'eligibleToServe':
	echo '<h1>Eligible For Service Today</h1>';
	echo '<p>Namely those not in calf, pregnant, barren or dry which are marked clean and over 40 days calved</p>';
	$cows = $alpro->uniform->eligibleToServe(date('Y-m-d'));
	include 'templates/sort.htm';
	break;
	
	case 'freshCows':
	if(!isset($_GET['limit'])) $limit = 60;
	else $limit = $_GET['limit'];
	echo '<h1>Fresh Cow Checks</h1>';
	echo '<p>Now shows cows which have not been vet checked as OK after 21 days</p>';
	echo '<form action="index.php" method="get"><input type="text" name="limit" value="'.$limit.'" />';
	echo '<input type="hidden" name="a" value="freshCows" /><input type="submit" value="Load" /></form>';
	$cows = $alpro->uniform->freshCowChecks($limit);
	include 'templates/sort.htm';
	break;
	
	case 'aborted':
	$data = $alpro->uniform->abortedCows();
	foreach($data as $abortion) {
		echo $abortion['NUMMER'].' '.$abortion['DATUMBEGIN']."<br />";
	}
	$alpro->uniform->abortedTree();
	break;
	
	case 'familyTree':
	$alpro->uniform->familyTree();
	break;
	
	case 'fertilityBreakdown':
	$data = $alpro->uniform->fertilityBreakdown();
	echo '<h1>Fertility Breakdown</h1>';
	echo '2011 Calvers: '.$data['round_year']['COUNT'].' ('.$data['round_year_served']['COUNT'].' Served)<br />';
	echo 'Spring 2012 Calvers: '.$data['round_spring']['COUNT'].' ('.$data['round_spring_served']['COUNT'].' Served)<br />';
	echo 'Summer 2012 Calvers: '.$data['summer']['COUNT'].' ('.$data['summer_served']['COUNT'].' Served)<br />';
	$total = $data['round_year']['COUNT'] + $data['round_spring']['COUNT'] + $data['summer']['COUNT'];
	echo '<b>Total Eligible Cows:</b> '. $total.'<br />';
	echo 'Bulling Heifers: '.$data['heifers']['COUNT'].' ('.$data['heifers_served']['COUNT'].' served)<br />';
	$plus = $total+$data['heifers']['COUNT'];
	echo '<b>Total to Serve:</b> '.$plus.'<br /><br />';
	echo 'Dry: '.$data['dry']['COUNT'].'<br />';
	echo 'Cows Due Spring 2013: '.$data['cows_pregnant_spring']['COUNT'].'<br />';
	echo 'Heifers Due Spring 2013: '.$data['heifers_pregnant_spring']['COUNT'].'<br />';
	echo 'Cows Due Summer 2013: '.$data['cows_pregnant_summer']['COUNT'].'<br />';
	echo 'Heifers Due Summer 2013: '.$data['heifers_pregnant_summer']['COUNT'].'<br />';
	echo 'Barren: '.$data['barren']['COUNT'].'<br />';
	break;
	
	case 'dueByWeek':
	if(!isset($_GET['start'])) {
		$data = $alpro->uniform->dueEachWeek();
		include 'templates/dueeachweek.htm';
	} else {
		$data = $alpro->uniform->dueByWeek($_GET['start']);
		include 'templates/duebyweek.htm';
	}
	break;
	
	case 'kpis':
	$start = microtime(true);
	$cull['2013'] = $alpro->uniform->kpi_cullage(2013);
	$cull['2012'] = $alpro->uniform->kpi_cullage(2012);
	$cull['2011'] = $alpro->uniform->kpi_cullage(2011);
	$section[0] = microtime(true);
	$sixweeks['2013'] = $alpro->uniform->kpi_preg6weeks('2013-10-01');
	$sixweeks['2012'] = $alpro->uniform->kpi_preg6weeks('2012-10-01');
	$sixweeks['2011'] = $alpro->uniform->kpi_preg6weeks('2011-10-01');
	$section[] = microtime(true);
	$scc = $alpro->uniform->kpi_scc();
	$section[] = microtime(true);
	//$sub = $alpro->uniform->kpi_submission('2013-10-01',12);
	$section[] = microtime(true);
	$loc['2011'] = $alpro->uniform->kpi_locomotion('2011-01-01','2011-12-31');
	$loc['2012'] = $alpro->uniform->kpi_locomotion('2012-01-01','2012-12-31');
	$section[] = microtime(true);
	$first['2011'] = $alpro->uniform->kpi_firstService('2011-10-01','2011-12-24');
	$first['2012'] = $alpro->uniform->kpi_firstService('2012-10-01','2012-12-24');
	$first['2013'] = $alpro->uniform->kpi_firstService('2013-10-01','2013-12-24');
	foreach($section as $time) {
		//echo $time - $start.'<br />';
		$start = $time;
	}
	include 'templates/kpis.htm';
	break;
	
	case 'kpi_submissionRates':
	$sub = $alpro->uniform->kpi_submission('2013-10-01',12);
	$sub_prev = $alpro->uniform->kpi_submission('2012-10-01',12);
	include 'templates/kpi_submissionRates.htm';
	break;
	
	case 'kpi_pregs_week':
	$start = microtime();
	$by_week['2013'] = $alpro->uniform->kpi_pregnant_by_week('2013-10-01','2013-12-31');
	$section[] = microtime(true);
	$by_week['2012'] = $alpro->uniform->kpi_pregnant_by_week('2012-10-01','2012-12-31');
	$section[] = microtime(true);
	$by_week['2013 heifers'] = $alpro->uniform->kpi_pregnant_by_week('2013-10-01','2013-12-31',true);
	$section[] = microtime(true);
	$by_week['2012 heifers'] = $alpro->uniform->kpi_pregnant_by_week('2012-10-01','2012-12-31',true);
	$section[] = microtime(true);
	foreach($section as $time) {
		//echo $time - $start.'<br />';
		$start = $time;
	}
	include 'templates/kpi_pregs_week.htm';
	break;
	
	case 'kpi_heifer_losses':
	for($i=2001;$i <= date('Y');$i++) {
		$data[$i] = $alpro->uniform->kpi_heifer_losses($i);
	}
	include 'templates/kpi_heifer_losses.htm';
	break;
	
	case 'kpi_mastitis_cases':
	for($i=2010;$i < date('Y');$i++) {
		$data[$i] = $alpro->uniform->kpi_mastitis_cases($i);
	}
	include 'templates/kpi_mastitis_cases.htm';
	break;
	
	case 'kpi_homebred':
	print_r($alpro->uniform->kpi_homebred());
	break;
	
	case 'missing_extra':
	if(!isset($_GET['date'])) $_GET['date'] = false;
	$data = $alpro->fetchMissingExtra($_GET['date']);
	$start = date('Y-m-d',strtotime(date('Y-m-').'01'));
	echo 'Missing Cows<br />';
	if(!empty($data['missing'])) foreach($data['missing'] as $cow) echo '<a href="filter.php?cow='.$cow['cow'].'&amp;start='.$start.'">'.$cow['cow'].'</a><br />';
	echo 'Extra Cows<br />';
	if(!empty($data['extra'])) foreach($data['extra'] as $cow) echo '<a href="filter.php?cow='.$cow['cow'].'&amp;start='.$start.'">'.$cow['cow'].'</a><br />';
	break;
	
	case 'locomotionTrim':
	$cows = $alpro->uniform->locomotionTrim('2012-03-14',30,100);
	include 'templates/sort.htm';
	break;
	
	case 'trimBeforeDry':
	echo 'Looking for cows not yet trimmed 80 days before drying off';
	$cows = $alpro->uniform->trimBeforeDry(80);
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
		foreach($_POST['cows'] as $cow) if($alpro->uniform->sortCow($cow,$_POST['wait'],$_POST['days'],$am,$pm)) $count++;
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
	if(!isset($_GET['date'])) $_GET['date'] = date('Y-m-d',strtotime('tomorrow'));
	$cows = $alpro->uniform->dueSort($_GET['date']);
	foreach($cows as $cow) echo $cow['NUMMER'].'<br />';
	break;
	
	case 'needsactmeter':
	include 'templates/header.htm';
	$data = $alpro->uniform->needsActMeter();
	echo '<h1>Going Round Again and Needing Activity Meters</h1>';
	echo 'Total: '.count($data['needs']).'. In Use: '.$data['inuse']['COUNT'].'<br />';
	foreach($data['needs'] as $cow) {
		echo $cow['NUMMER'].' '.$alpro->uniform->config['status'][$cow['STATUS']].'<br />';
		print_r($this->odbcFetchAll("SELECT * FROM TblCowRelActLvlHistory"));
		exit;
	}
	break;
	
	case 'reconcileActTags':
	$alpro->uniform->reconcileActTags();
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
		$alpro->uniform->importLocomotionScores($_POST['date'],$_POST['handler'],$cows);
	} else include 'templates/locomotionupload.htm';
	break;
	
	case 'importJohnes':
	if(isset($_POST['data'])) {
		$alpro->uniform->importJohnesTest($_POST['data']);
	} else include 'templates/importjohnes.htm';
	break;
	
	case 'johnes':
	$data = $alpro->uniform->johnesHerdwise();
	include 'templates/johnes_herdwise.htm.php';
	break;
	
	case 'johnes_old':
	$data = $alpro->uniform->johnesCows();
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
	
	case 'idtimes':
	include 'parlour/reload.htm';
	echo '<table border="1"><tr><td>';
	$data = $alpro->fetchRecent('pm',20);
	$buffer = 0;
	$flag = false;
	foreach($alpro->fetchIDTimes() as $time => $cow) {
		if($cow != $data[0]['cow'] && !$flag) $buffer++;
		else $flag = true;
		echo $cow.' '.$time.'<br />';
	}
	echo '</td><td valign="top">';
	$data = $alpro->fetchRecent('pm',20);
	for($i = 0;$i < $buffer;$i++) echo '<br />';
	foreach($data as $cow) echo $cow['cow'].' '.$cow['pm'].'<br />';
	//print_r($data);
	echo '</td></tr></table>';
	break;
	
	case 'calvingQsum':
	error_reporting(0);
	$conditions[] = $alpro->uniform->lookupHealthEvent('Milk Fever');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Dirty');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Clinical Mastitis');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Metritis');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Prolapse');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Retained Afterbirth');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Ketosis');
	$conditions[] = $alpro->uniform->lookupHealthEvent('LDA');
	$conditions[] = $alpro->uniform->lookupHealthEvent('Lame');
	$data = $alpro->uniform->calvingQsum('2013-06-01','2013-07-15',30,$conditions);
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
		echo $alpro->uniform->config['status'][$cow['STATUS']].'</td>';
		$calvedate = strtotime($cow['LAATSTEKALFDATUM']);
		$calvingweek = date('W',$calvedate);
		if(isset($calvingweeks[$calvingweek])) $calvingweeks[$calvingweek]['calved']++;
		else $calvingweeks[$calvingweek]['calved'] = 1;
		if($cow['STATUS'] < 5) $calvingweeks[$calvingweek]['minus']++;
		echo '<td>'.$cow['LAATSTEKALFDATUM'].'</td><td>'.$alpro->uniform->config['calvingease'][$cow['AFKALFVERLOOP_CODE']].'</td>';
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
	$condition = $alpro->uniform->lookupHealthEvent($_GET['condition']);
	if($condition) {
		echo '<h1>'.$condition['OMSCHRIJVING'].'</h1>';
		$data = $alpro->uniform->healthReporting($condition['CODEZIEKTE'],'2011-01-01','2012-03-01');
		foreach($data as $cow) {
			echo $cow['NUMMER'].' '.$cow['DIERID'].' '.$cow['DATUMZIEKTE'].'<br />';
		}
	} else die('Unknown Condition');
	break;

	case 'footTrimming':
	$recent = $alpro->uniform->recentFootTrimmings();
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
	if($routine) $data = $alpro->uniform->footTrimming($days,$routine_limit);
	else $data = array();
	$routine_count = count($data);
	if($loco) {
		$t = $alpro->uniform->locomotionTrim('2012-03-14',30,100);
		if($t)$loco_count = count($t);
		else $loco_count = 0;
		$data = array_merge($data,$t);
	} else $loco_count = 0;
	if($predry) {
		$t = $alpro->uniform->trimBeforeDry(80,$predry_limit);
		$predry_count = count($t);
		$data = array_merge($data,$t);
	} else $predry_count = 0;
	if($recheck) {
		$t = $alpro->uniform->footRechecks();
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
	$dodgy = $alpro->dodgyCollarsStatus(5);
	$start = date('Y-m-d',strtotime('-5 days'));
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
