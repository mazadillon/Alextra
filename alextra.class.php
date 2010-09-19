<?php
ERROR_REPOrting(false);
// Config & Defaults
/*
Interesting Fields in Various Tables:
	TblCow: CowNo, Transponder, Pregnant (Bool), BreedingState (int),
			DateBirth, DateHeat, DateCalving, Lactation, SeparateOnce,
			DriedOffDate, CowID, DateInsem, MilkTimeToday1, MilkTimeToday2
	TblCowB: CowNo, IDTimeTodayMM1, IDTimeTodayMM2, IDTimeYesterMM1, IDTimeYesterMM2,
			IDTimeTodaySS1, IDTimeTodaySS2, IDTimeYesterSS1, IDTimeYesterSS2,
			MilkTimeTodaySS1, MilkTimeTodaySS2, MilkTimeYesterSS1, MilkTimeYesterSS2
*/
//The different statuses used by Alpro in tblCow
$status = array('Open','Not Pregnant','Not Pregnant','Barren','Pregnant','Dry');

class alpro {
	function alpro() {
		$this->connect();
	}

	function connect() {
		$this->odbc = odbc_connect('alpro',"","61016622");
		if(!$this->odbc) exit("Connection Failed: " . $this->odbc);
		$this->mysql = mysql_connect('localhost','root','');
		if(!$this->mysql) exit("mySQL connection failed");
		mysql_select_db('alpro');
		return true;
	}
	
	function odbcFetchAll($query) {
		$rs=odbc_exec($this->odbc,$query);
		if(!$rs) exit("Error in SQL: ".odbc_errormsg());
		$return = false;
		while($row = odbc_fetch_array($rs)) {
			$return[] = $row;
		}
		if(count($return) == 1) return $return[0];
		else return $return;
	}
	
	function queryOne($query) {
		$result = mysql_query($query);
		if(!$result) die(mysql_error());
		if(mysql_num_rows($result) > 0)	return mysql_result($result,0);
		else return false;
	}
	
	function queryAll($query) {
		$result = mysql_query($query);
		if(!$result) die(mysql_error());
		if(mysql_num_rows($result) < 1) return false;
		else {
			while($row = mysql_fetch_assoc($result)) $return[] = $row;
			return $return;
		}
	}
	
	function queryRow($query) {
		$result = mysql_query($query);
		if(!$result) die(mysql_error());
		if(mysql_num_rows($result) > 0) return mysql_fetch_assoc($result);
		else return false;
	}
	
	//Inserts milking times or updates if data exists already for that day
	function insertMilkingTime($cow,$date,$am,$pm) {
		if($this->queryRow("SELECT * FROM alpro WHERE cow='".mysql_real_escape_string($cow)."' AND date='".mysql_real_escape_string($date)."'")) {
			mysql_query("UPDATE alpro SET `am`='".mysql_real_escape_string($am)."', `pm`='".mysql_real_escape_string($pm)."' WHERE cow='".mysql_real_escape_string($cow)."' AND date='".mysql_real_escape_string($date)."' LIMIT 1") or die(mysql_error());
			//echo 'Updated '.mysql_affected_rows().' cows<br />';
		} else mysql_query("INSERT INTO alpro (`cow`,`date`,`am`,`pm`) VALUES ('".mysql_real_escape_string($cow)."','".mysql_real_escape_string($date)."','".mysql_real_escape_string($am)."','".mysql_real_escape_string($pm)."')") or die(mysql_error());
		return true;
	}
	
	function fixtime($date,$seconds) {
		if(empty($date)) return '';
		else {
			$mins = substr($date,11,-2);
			return $mins.str_pad($seconds,2,'0',STR_PAD_LEFT);
		}
	}
	
	//Copy milking times from the alpro database to mySQL
	function copyMilkingTimesSince($time) {
		//echo 'Copying since '.$time.'.<br />';
		if(date('a',strtotime(date('Y-m-d ').$time)) == 'am') $sort = 'TblCow.MilkTimeToday1';
		else $sort =  'TblCow.MilkTimeToday2';
		$query = "SELECT TblCow.CowNo,TblCow.MilkTimeToday1,TblCow.MilkTimeToday2,TblCowB.MilkTimeTodaySS1,TblCowB.MilkTimeTodaySS2 FROM TblCow INNER JOIN TblCowB ON TblCow.CowNo = TblCowB.CowNo WHERE TblCow.CowNo <= 9999 AND ".$sort." >= #".$time."# ORDER BY ".$sort." DESC";		
		$data = $this->odbcFetchAll($query);
		if($data) {
			foreach($data as $cow) {
				$cow['am'] = $this->fixtime($cow['MilkTimeToday1'],$cow['MilkTimeTodaySS1']);
				$cow['pm'] = $this->fixtime($cow['MilkTimeToday2'],$cow['MilkTimeTodaySS2']);
				//echo $cow['CowNo'].' '.$cow['am'].' '.$cow['pm'].'<br />';
				$this->insertMilkingTime($cow['CowNo'],date('Y-m-d'),$cow['am'],$cow['pm']);
			}
		} else die("Error fetching data");
		if($sort == 'TblCow.MilkTimeToday1' && date('a') == 'pm') $this->copyMilkingTimesSince('13:00:00');
	}
	
	function copyLatestMilkingTimes() {
		$latest = $this->queryRow("SELECT max( am ) as am, max( pm ) as pm FROM alpro WHERE date = '".date('Y-m-d')."'");
		if($latest['am'] == '') $latest = '01:00:00';
		elseif($latest['pm'] == '') $latest = $latest['am'];
		else $latest = $latest['pm'];
		$latest = explode(':',$latest);
		$time = mktime($latest[0],$latest[1],$latest[2]);
		$this->copyMilkingTimesSince(date('H:i:s',$time-60));
		return true;
	}
	
	function copyHistoricMilkingTimes() {
		$date = strtotime('yesterday');
		$latest = $this->queryRow("SELECT max( pm ) as pm FROM alpro WHERE date = '".date('Y-m-d',$date)."'");
		if($latest['pm'] == '') {
			echo 'Copying data from yesterday<br />';
			$data = $this->odbcFetchAll("SELECT TblCow.CowNo,TblCow.MilkTimeYesterd1,TblCow.MilkTimeYesterd2,TblCowB.MilkTimeYesterSS1,TblCowB.MilkTimeYesterSS2 FROM TblCow INNER JOIN TblCowB ON TblCow.CowNo = TblCowB.CowNo WHERE TblCow.CowNo <= 9999 ORDER BY TblCow.CowNo ASC");
			if($data) {
				foreach($data as $cow) {
					$cow['am'] = $this->fixtime($cow['MilkTimeYesterd1'],$cow['MilkTimeYesterSS1']);
					$cow['pm'] = $this->fixtime($cow['MilkTimeYesterd2'],$cow['MilkTimeYesterSS2']);
					echo $cow['CowNo'].' '.$cow['am'].' '.$cow['pm'].'<br />';
					$this->insertMilkingTime($cow['CowNo'],date('Y-m-d',$date),$cow['am'],$cow['pm']);
				}
			}
		}		
	}
	
	function copyActivityData() {
		$data = $this->odbcFetchAll("SELECT TblCowActLvlHistory.CowNo,TblCowActLvlHistory.ActLevel,TblCowActLvlHistory.ActDateTime FROM TblCowActLvlHistory ORDER BY CowNo ASC");
		$update = 0;
		if($data) {
			foreach($data as $date) {
				$level = '';
				for($i = 0;$i<$date['ActLevel'];$i++) $level .= '+';
				//echo $date['CowNo'].' '.$date['ActDateTime'].' '.$level.'<br />';
				mysql_query("UPDATE alpro SET activity = '".$level."' WHERE cow='".$date['CowNo']."' AND date='".date('Y-m-d',strtotime($date['ActDateTime']))."' LIMIT 1");
				$updated = $updated + mysql_affected_rows();
			}
		}
		echo $updated.' activity statuses inserted';
	}
	
	function importData() {
		$this->copyLatestMilkingTimes();
		$this->copyHistoricMilkingTimes();
		$this->copyActivityData();
		$this->sortedCows();
		if(date('d') == '01') $this->backup_database();
	}
	
	function fetchRecent($milking,$limit=1) {
		return $this->queryAll("SELECT * FROM alpro WHERE `date`='".gmdate('Y-m-d')."' ORDER BY ".$milking." DESC LIMIT ".$limit);
	}
	
	//Specify an offset in seconds
	function fetchOffset($milking,$offset=60,$limit=5) {
		if(date('I')==0) $offset = -3600 + $offset; 
		$offset = date('H:i:s',time()-$offset);
		//echo date('H:i:s',$offset).' '.date('I');
		return $this->queryAll("SELECT * FROM alpro WHERE `date`='".date('Y-m-d')."' and ".$milking."<='".$offset."' ORDER BY ".$milking." DESC LIMIT ".$limit);
	}
	
	function milkingSpeed() {
		$milking = $this->currentMilking();
		$data = $this->fetchRecent($milking,11);
		$prev = false;
		$total = 0;
		$count = 0;
		foreach($data as $cow) {
			//print $cow[$milking].'<Br />';
			$time = strtotime(date('Y-m-d').' '.$cow[$milking]);
			if($prev!=false) {
				$diff = $prev - $time;
				//echo $diff.' ';
				if($diff > 3 && $diff < 60) {
					$total = $total + $diff;
					$count++;
				}
			}
			$prev = $time;
		}
		$speed = round($total / $count - 1,0);
		$cph = round(3600 / $speed,0);
		echo 'Speed: '.$speed.' seconds per cow. '.$cph.' cows/hour';
	}
	
	
	// Copy all updated cow information from alpro table to milk recording table
	// and guess stall numbers
	function recordStalls() {
		$latest = $this->queryRow("SELECT * FROM milkrecording order by stamp DESC limit 1");
		if(date('z') != date('z',$latest['stamp'])) {
			$latest['stamp'] = mktime(0,0,0);
			$latest['stall'] = 0;
			$latest['cow'] = '0';
		}
		$date = date('H:i:s',$latest['stamp']);
		$stall = $latest['stall'];
		$result = mysql_query("SELECT * FROM alpro WHERE `date`='".date('Y-m-d')."' and ".date('a')." >= '".$date."' order by ".date('a')." ASC") or die(mysql_error());
		if(mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_assoc($result)) {
				if($row['cow'] != $latest['cow']) {
					$stall = $this->adjustStall($stall);
					$stamp = strtotime($row['date'].' '.$row[date('a')]);
					mysql_query("INSERT INTO milkrecording (stamp,cow,stall) VALUES ('".$stamp."','".$row['cow']."','".$stall."')");
				}
			}
		}
	}
	
	function adjustStall($stall,$adjust = 1) {
		$stall = $stall + $adjust;
		if($stall > 40) $stall = 1;
		return $stall;
	}
	
	// Insert a cow before the provided stamp
	// calculates the previous stamp and adds 5 seconds
	function insertCow($cow,$stamp) {
		// Identify previous cow
		$previous = $this->queryRow("SELECT * FROM milkrecording WHERE stamp < '".$stamp."' order by stamp DESC limit 1");
		$newstamp = $previous['stamp'] + 5;
		$stall = $this->adjustStall($previous['stall']);
		mysql_query("INSERT INTO milkrecording (stamp,cow,stall) VALUES ('".$newstamp."','".$cow."','".$stall."')");
		
		//Now bump up all following stall numbers
		$data = $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".$newstamp." ORDER BY stamp ASC");
		if($data) {
			foreach($data as $row) {
				mysql_query("UPDATE milkrecording SET stall = '".$this->adjustStall($row['stall'])."' WHERE stamp='".$row['stamp']."' AND cow='".$row['cow']."'");
			}
		}
	}
	
	function removeCow($cow,$stamp) {
		mysql_query("DELETE FROM milkrecording WHERE cow='".$cow."' AND stamp='".$stamp."'");
		//Now bump down all following stall numbers
		$data = $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".$stamp." ORDER BY STAMP ASC");
		if($data) {
			foreach($data as $row) {
				mysql_query("UPDATE milkrecording SET stall = '".$this->adjustStall($row['stall'],-1)."' WHERE stamp='".$row['stamp']."' AND cow='".$row['cow']."'");
			}
		}
	}
	
	function editStall($cow,$stamp,$stall) {
		mysql_query("UPDATE milkrecording SET cow='".$cow."' WHERE stamp='".$stamp."'");
		$query = $this->queryAll("SELECT * FROM milkrecording WHERE stamp>='".$stamp."' Order by stamp asc");
		foreach($query as $row) {
			mysql_query("UPDATE milkrecording SET stall='".$stall."' WHERE stamp='".$row['stamp']."' and cow='".$row['cow']."'");
			$stall = $this->adjustStall($stall);
		}
	}
	
	function cowInfo($cow) {
		return $this->odbcFetchAll("SELECT CowNo, BreedingState, DateBirth, DateHeat, DateCalving, Lactation, DateInsem From TblCow WHERE CowNo = ".$cow);
	}
	
	function jogglerBasic() {
		$milking = date('a');
		$this->copyLatestMilkingTimes();
		$data = $this->fetchRecent($milking,5);
		foreach($data as $id => $cow) {
			$data[$id]['info'] = $this->cowInfo($cow['cow']);
		}
		return $data;
	}
	
	function jogglerServing() {
		$this->copyLatestMilkingTimes();
		$data = $this->fetchOffset(date('a'),120,8);
		foreach($data as $id => $cow) {
			$data[$id]['info'] = $this->cowInfo($cow['cow']);
		}
		return $data;
	}	
	
	function jogglerMilkRecording($all) {
		$milking = date('a');
		$this->copyLatestMilkingTimes();
		$this->recordStalls();
		if($all) {
			if($milking == 'am') $start = strtotime('1am');
			else $start = strtotime('1pm');
			$data['current'] = $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".$start." ORDER BY stamp DESC");
			$data['prev'] = $data['current'];
		} else {
			$data['current'] = $this->queryAll("SELECT * FROM milkrecording ORDER BY stamp DESC LIMIT 5");
			$round = $this->queryOne("SELECT stamp FROM milkrecording WHERE stall='".$data['current'][0]['stall']."' AND stamp < ".$data['current'][0]['stamp']." ORDER BY stamp DESC LIMIT 1");
			$data['prev'] = $this->queryAll("SELECT * FRom milkrecording WHERE stamp <= ".$round." ORDER BY stamp DESC LIMIT 5"); 
		}
		return $data;
	}
	
	function currentMilking() {
		$pm = $this->queryOne("SELECT max(pm) FROM alpro WHERE date='".date('Y-m-d')."'");
		if($pm=='') return 'am';
		else return 'pm';
	}
	
	function milkingTotal() {
		return $this->queryOne("SELECT count(*) FROM alpro WHERE date='".date('Y-m-d')."' AND ".$this->currentMilking()." != ''");
	}
	
	function filter($cow=false, $activity=false, $start=false, $end=false,$sort=false) {
		if(!$sort) $sort = 'cow';
		$query = 'SELECT * FROM alpro WHERE ';
		if($cow) $query .= "cow='".mysql_real_escape_string($cow)."' AND ";
		if($activity) $query .= "activity IS NOT NULL AND ";
		$query .= "date >= '".$start."' AND date <= '".$end."' ORDER BY ".mysql_real_escape_string($sort).",date ASC";
		$result = mysql_query($query) or die(mysql_error());
		if(mysql_num_rows($result) < 1) return false;
		else {
			while($row = mysql_fetch_assoc($result)) {
				$return[] = $row;
			}
			return $return;
		}
	}
	
	function sortedCows() {
		$data = $this->odbcFetchAll("SELECT CowNo, LastCutTime, LastCutDate From TblCow ORDER BY CowNo ASC");
		foreach($data as $cow) {
			mysql_query("INSERT IGNORE INTO shedding (cow, date, time) VALUES ('".$cow['CowNo']."','".$cow['LastCutDate']."','".substr($cow['LastCutTime'],11,5)."')") or die(mysql_error());
		}
		return true;
	}
	
	function listSortedCows($date) {
		return $this->queryAll("SELECT * FROM shedding WHERE date='".$date."' ORDER BY time ASC");
	}
	
	function milkRecordingDisplay($limit) {
		if($limit && is_numeric($limit)) return $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".strtotime('1am')." ORDER BY stamp DESC LIMIT ".$limit);
		else return $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".strtotime('1am')." ORDER BY stamp DESC");
	}
	
	function backup_database() {
		$data = shell_exec('C:\wamp\bin\mysql\mysql5.1.36\bin\mysqldump -u root alpro --skip-opt');
		file_put_contents('D:/alextra/'.date('Y-m-d').'.sql',$data);
	}
	
	function dodgyCollars($days) {
		$query = "SELECT * FROM alpro WHERE date > '".date('Y-m-d',strtotime('-'.$days.' days'))."' ORDER BY cow ASC, date ASC";
		$collars = $this->queryAll($query);
		$cow = 0;
		$rows = 0;
		$missed = 0;
		$output = array('movements' => array(),'lost' => array(),'fresh' => array(),'dodgy' =>array());
		foreach($collars as $row) {
			if($row['cow'] != $cow) {
				if($rows != $days - 1 && $rows != 0) $output['movements'][] = $cow;
				elseif($missed > 1 && $missed != ($days * 2) - 4) $output['dodgy'][] = $cow;
				$rows = 0;
				$missed = 0;
			} else {
				if(!isset($cows[$row['cow']])) $cows[$row['cow']] = '';
				if(trim($row['am']) == '-') {
					$cows[$row['cow']] .= '-';
					$missed++;
				} else $cows[$row['cow']] .= '+';
				if(trim($row['pm']) == '-') {
					$cows[$row['cow']] .='-';
					$missed++;
				} else $cows[$row['cow']].= '+';
			}
			$cow = $row['cow'];
			$rows++;
		}
		echo '<pre>';
		foreach($output['dodgy'] as $cow) {
			$number = $cow;
			$len = strlen($cow);
			while(strlen($number) < 4) $number = ' '.$number;
			$dry = explode('+-',$cows[$cow]);
			$calved = explode('-+',$cows[$cow]);
			//print_r($dry);
			//print_r($calved);
			if(substr_count($cows[$cow],'+-') == 1 AND substr_count($cows[$cow],'-+') < 1) {
				echo $number.' '.$cows[$cow].' (Dry or Lost Collar)<br />';
			} elseif(substr_count($cows[$cow],'-+') == 1 AND substr_count($cows[$cow],'+-') < 1) {
				//echo $cow.' '.$cows[$cow].' (Calved)<br />';
			} else {
				echo $number.' '.$cows[$cow].'<br />';
			}
		}
	}
}
$alpro = new alpro();
?>
