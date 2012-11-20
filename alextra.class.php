<?php
ERROR_REPORTING(E_ALL);
date_default_timezone_set('Europe/London');
include_once 'uniform.class.php';
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
		include 'alextra_config.php';
		$this->config = $config;
		$this->connect();
		$this->copyLatestMilkingTimes();
		$this->sortedCows();
		$this->checkFlags();
		$this->uniform = new uniform();
	}

	function connect() {
		$this->odbc = odbc_connect('alpro',$this->config['alpro']['user'],$this->config['alpro']['password']);
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
	
	// Open DB in MS Access under exlusive use, then unset password
	// Use mdbtocsv.exe to convert
	function csvSearch($search) {
		$path = 'C:\Documents and Settings\Ford\My Documents\alpro-mdb-csv';
		$files = scandir($path);
		foreach($files as $file) {
			if($file != '.' && $file != '..') {
				$data = file_get_contents($path."\\".$file);
				preg_match_all('/'.$search.'/',$data,$matches);
				if(!empty($matches[0])) {
					echo $file;
					print_r($matches);
				}
			}
		}		
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
	
	function scrapeNML() {
		$data = file_get_contents('http://www.herdcompanion.co.uk/mqm/MilkQualityMonitorTable.aspx?BackURL=~/MilkQualityMonitorChart.aspx&NmrHerdNumber='.$this->config['alpro']['herdno'].'&MilkMetric=1&MyscFirstYear=2005&MyscLastYear='.date('Y').'&MyscStartMonth='.date('m').'&MyscStartYear='.date('Y',strtotime('-1 Year')).'&MyscStopMonth='.date('m').'&MyscStopYear='.date('Y').'&NmrHerdType='.$this->config['alpro']['NmrHerdType']);
		list($junk,$data) = explode('<td>Urea (%)</td>',$data,2);
		$data = explode('</tr><tr>',$data);
		unset($data[0]);
		foreach($data as $test) {
			$test = explode('</td>',$test);
			foreach($test as $id => $value) $test[$id] = trim(strip_tags($value));
			$date = DateTime::createFromFormat('d/m/y', $test[0]);
			$test[0] = $date->format('Y-m-d');
			mysql_query("INSERT IGNORE into milktests (date,scc,bacto,butter,protein,urea) VALUES ('".$test[0]."','".$test[1]."','".$test[2]."','".$test[3]."','".$test[5]."','".$test[7]."')") or die(mysql_error());
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
		if($cow > 0) {
			if(trim($am) != '' OR trim($pm) != '') {
				if($this->queryRow("SELECT * FROM alpro WHERE cow='".mysql_real_escape_string($cow)."' AND date='".mysql_real_escape_string($date)."'")) {
					mysql_query("UPDATE alpro SET `am`='".mysql_real_escape_string($am)."', `pm`='".mysql_real_escape_string($pm)."' WHERE cow='".mysql_real_escape_string($cow)."' AND date='".mysql_real_escape_string($date)."' LIMIT 1") or die(mysql_error());
					//echo 'Updated '.mysql_affected_rows().' cows<br />';
				} else mysql_query("INSERT INTO alpro (`cow`,`date`,`am`,`pm`) VALUES ('".mysql_real_escape_string($cow)."','".mysql_real_escape_string($date)."','".mysql_real_escape_string($am)."','".mysql_real_escape_string($pm)."')") or die(mysql_error());
				return true;
			}
		} else return false;
	}
	
	function actTag($cow) {
		return $this->odbcFetchAll("SELECT * FROM TblCowAct WHERE CowNo=".$cow);
	}
	
	function fetchCutIDTimes() {
		$times = $this->odbcFetchAll("SELECT cowNo,LastCutIDTime,LastCutIDTimeSS FROM TblCowB");
		foreach($times as $cow) {
			if(empty($cow['LastCutIDTime'])) $time = false;
			else $time = substr($cow['LastCutIDTime'],11,5).':'.str_pad((int) $cow['LastCutIDTimeSS'],2,"0",STR_PAD_LEFT);
			if($time) {
				if(!$this->queryRow("SELECT * FROM alpro WHERE cow='".mysql_real_escape_string($cow['cowNo'])."' AND date='".mysql_real_escape_string(date('Y-m-d'))."'")) {
					mysql_query("INSERT INTO alpro (cow,date) VALUES ('".mysql_real_escape_string($cow['cowNo'])."','".mysql_real_escape_string(date('Y-m-d'))."')") or die(mysql_error());
				}			
				if(substr($time,0,2) < 13) $apm = 'am';
				else $apm = 'pm';
				echo mysql_query("UPDATE `alpro` SET sort_id_".$apm."='".mysql_real_escape_string($time)."' WHERE cow=".mysql_real_escape_string($cow['cowNo'])." AND date='".mysql_real_escape_string(date('Y-m-d'))."'",$this->mysql) or die(mysql_error());	
			}
		}
	}
	
	function fedYesterday() {
		$data = $this->odbcFetchAll("SELECT UsedYday FROM TblBin WHERE BinNo=1");
		return round($data['UsedYday']);
	}
	
	function fedToday() {
		$data = $this->odbcFetchAll("SELECT sum(ConsTodayTotal) as fed FROM QryCowFeeding");
		return round($data['fed']);
	}
	
	function alproFields() {
		$result = odbc_tables($this->odbc) or die(odbc_error());
		while($row = odbc_fetch_array($result)){
			//print_r($row);
			if($row['TABLE_TYPE'] != 'SYSTEM TABLE') {
				echo $row['TABLE_NAME'].'<br />';
				$cols = odbc_columns($this->odbc, 'alpro', "", $row['TABLE_NAME']) or die(odbc_errormsg());
				while($col = odbc_fetch_array($cols)) {
					echo '-->'.$col['COLUMN_NAME'].' ('.$col['TYPE_NAME'].')<br />';
				}
			}
		}
	}
	
	function alproSearch($type='DATETIME',$value=false) {
		if($type == 'DATETIME') $value = '#'.$value.'#';
		$result = odbc_tables($this->odbc) or die(odbc_error());
		while($row = odbc_fetch_array($result)){
			//print_r($row);
			if($row['TABLE_TYPE'] != 'SYSTEM TABLE') {
			/*
				echo $row['TABLE_NAME'].'<br />';
				$cols = odbc_columns($this->odbc, 'alpro', '', $row['TABLE_NAME']);
				while($col = odbc_fetch_array($cols)) {
					if($col['TYPE_NAME'] == $type) {
						$data = $this->odbcFetchAll("SELECT ".$col['COLUMN_NAME']." FROM ".$row['TABLE_NAME']." WHERE ".$col['COLUMN_NAME']."=".$value."");
						if($data) echo $col['COLUMN_NAME'].' = '.$data[$col['COLUMN_NAME']].'<br />';
					}
				}
			*/
			} else {
				print_r($row);
			}
		}
	}
	
	function fixtime($date,$seconds) {
		if(empty($date)) return '';
		else {
			$mins = substr($date,11,-2);
			return $mins.str_pad($seconds,2,'0',STR_PAD_LEFT);
		}
	}
	
	function resetTimesToday() {
		mysql_query("UPDATE alpro SET am='', PM='' WHERE date='".date('Y-m-d')."'");
		$this->copyMilkingTimes();
		$this->fetchCutIDTimes();
	}
	
	//Copy milking times from the alpro database to mySQL
	function copyMilkingTimesSince($time) {
		$this->copyMilkingTimes();
		/*
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
				if(time() - strtotime($cow['am']) > 3700 AND time() - strtotime($cow['pm']) > 3700) {
					$this->insertMilkingTime($cow['CowNo'],date('Y-m-d'),$cow['am'],$cow['pm']);
				}
			}
		} //else die("Error fetching data");
		if($sort == 'TblCow.MilkTimeToday1' && date('a') == 'pm') $this->copyMilkingTimesSince('13:00:00');
		*/
	}
	
	function copyMilkingTimes() {
		$query = "SELECT TblCow.CowNo,TblCowB.IDTimeTodayMM1,TblCowB.IDTimeTodaySS1,TblCowB.IDTimeTodayMM2,TblCowB.IDTimeTodaySS2,TblCow.MilkTimeToday1,TblCow.MilkTimeToday2,TblCowB.MilkTimeTodaySS1,TblCowB.MilkTimeTodaySS2 FROM TblCow INNER JOIN TblCowB ON TblCow.CowNo = TblCowB.CowNo";		
		$data = $this->odbcFetchAll($query);
		if($data) {
			foreach($data as $cow) {
				$cow['am'] = $this->fixtime($cow['MilkTimeToday1'],$cow['MilkTimeTodaySS1']);
				$cow['pm'] = $this->fixtime($cow['MilkTimeToday2'],$cow['MilkTimeTodaySS2']);
				$cow['id_am'] = $this->fixtime($cow['IDTimeTodayMM1'],$cow['IDTimeTodaySS1']);
				$cow['id_pm'] = $this->fixtime($cow['IDTimeTodayMM2'],$cow['IDTimeTodaySS2']);
				if($cow['am'] == '' && $cow['id_am'] != '') $cow['am'] = $cow['id_am'];
				if($cow['pm'] == '' && $cow['id_pm'] != '') $cow['pm'] = $cow['id_pm'];
				//echo $cow['CowNo'].' '.$cow['am'].' '.$cow['pm'].'<br />';
				if(date('U') - strtotime($cow['am']) < 3700 AND $cow['pm'] == '') {
					$this->insertMilkingTime($cow['CowNo'],date('Y-m-d'),$cow['am'],$cow['pm']);
				} elseif(strtotime($cow['pm']) < date('U')) {
					$this->insertMilkingTime($cow['CowNo'],date('Y-m-d'),$cow['am'],$cow['pm']);
				}
			}
		}
	}
	
	// Convert date from 25/10/2010 to 20101025
	function convertDate($date) {
		$date = explode('/',$date);
		return $date[2].$date[1].$date[0];
	}
	
	function sortedRecent($mins=3) {
		$cow = $this->queryOne("SELECT cow FROM shedding WHERE date = '".date('Y-m-d')."' AND time > '".date('H:i',strtotime('-'.$mins.' mins'))."' ORDER BY time DESC LIMIT 1");
		if($cow) {
			$total = $this->queryOne("SELECT count(*) FROM shedding WHERE date = '".date('Y-m-d')."' AND time > '".date('H:i',strtotime('1'.$this->currentMilking()))."'");
			return array('cow'=>$cow,'total'=>$total);
		} else return false;		
	}
		
	function extraCows() {
		$extra = $this->queryAll("SELECT status.cow FROM alpro left join status on status.cow = alpro.cow where status='dry' and date='".date('Y-m-d')."'");
		if($extra) {
			foreach($extra as $cow) {
				$this->logMissingExtra($cow['cow'],'extra');
			}
		} else $extra = array();
		$extra2 = $this->queryAll("SELECT alpro.cow,status.status FROM alpro left join status on status.cow = alpro.cow where date='".date('Y-m-d')."' and status is null");
		if($extra2) {
			foreach($extra2 as $cow) {
				$this->logMissingExtra($cow['cow'],'extra');
			}
		} else $extra2 = array();
		return array_merge($extra,$extra2);
	}
	
	function listStock() {
		return $this->queryAll("SELECT * FROM status ORDER BY cow ASC");
	}
	
	function cullsToAlpro() {
		odbc_exec($this->odbc,"UPDATE TblCow SET Cull=FALSE");
		$barren = $this->queryAll("SELECT * FROM status WHERE status='Barren'");
		foreach($barren as $cow) {
			echo $cow['cow'].' marked as barren<br />';
			odbc_exec($this->odbc,"UPDATE TblCow SET Cull=TRUE WHERE CowNo=".$cow['cow']);
		}
	}
	
	// Find missing cows (and extra cows);
	function missingCows() {
		$cows = $this->queryAll("SELECT cow FROM `status` WHERE status != 'Dry' AND calved!='0000-00-00'");
		$errors = array();
		foreach($cows as $cow) {
			$times = $this->queryRow("SELECT * FROM alpro WHERE cow='".$cow['cow']."' AND date='".date('Y-m-d')."'");
			if(!$times) {
				$errors[] = $cow['cow'];
				$this->logMissingExtra($cow['cow'],'missing');
			}
		}
		return $errors;
	}
	
	function logMissingExtra($cow,$status) {
		if(mysql_query("INSERT INTO missing_extra (date,cow,status) VALUES ('".date('Y-m-d')."','".$cow."','".$status."')")) return true;
		else return false;
	}
	
	function fetchMissingExtra($date) {
		if(!$date) $date = date('Y-m-d',strtotime('Yesterday'));
		$this->missingCows();
		$this->extraCows();
		$data['date'] = $date;
		$data['missing'] = $this->queryAll("SELECT * FRom missing_extra WHERE date='".$date."' AND status='missing'");
		$data['extra'] = $this->queryAll("SELECT * FRom missing_extra WHERE date='".$date."' AND status='extra'");
		return $data;
	}
	
	function mailMissingExtraCows() {
		$errors = $this->missingCows();
		$extra = $this->extraCows();
		if(count($errors) > 0) {
			$message = "The following cows appear not to have been milked today (lost collars?):\n";
			foreach($errors as $cow) $message .= $cow."\n";
		}
		if($extra != false) {
			$message .= "One or more cows came through the parlour despite being dry:\n";
			foreach($extra as $cow) $message .= $cow['cow']."\n";
		}
		if($message != '') {
			echo "Email sent to office@fordpartners.co.uk containing the following: \n".$message;
			mail("office@fordpartners.co.uk",'Missing or Extra Cows During Today\'s Milking',$message);
		}
	}
	
	function milkTests($limit = 5) {
		return $this->queryAll("SELECT * FROM milktests ORDER BY date DESC LIMIT ".$limit);
	}
	
	function oldParseUniformReport() {
		// NOW REDUNDANT
		// Generate a report in Uniform with the following fields in this order:
		// Number, Date of birth, Last Calved date, Status, Last heat, DIM
		// Last insemination, Last milk recording yield, dry off date,
		// %Fat LMT, Last SCC,Pregnancy status, Sex
		
		// Use automate to set the report to print via PDF Creator which should
		// be set to automatically convert to text files, give files a name and
		// place them in the folder specified below. This function reads the data
		// and stores it in a mysql database
		
		$path = 'C:\documents and settings\ford\my documents\export';
		$mod = 0;
		foreach(scandir($path) as $file) {
			if($file != '.' && $file != '..') {
				$filemtime = filemtime($path.'\\'.$file);
				if($filemtime > $mod) {
					$target = $path.'\\'.$file;
					$mod = $filemtime;
				}
			}
		}		
		$file = str_replace("\x00",'',substr(file_get_contents($target),37));
		$file = str_replace("Not pregnant",'Empty',$file);
		$file = str_replace("Young stock",'Youngstock',$file);
		$file = explode('Sex',$file);
		$target = '';
		unset($file[0]);
		foreach($file as $data) {
			$data = explode('%%[Page: ',$data);
			$target .= $data[0];
		}
		$data = preg_split("/Female/",trim($target));
		$cow = 0;
		foreach($data as $line) {
			$line = preg_split("/[\s]+/",trim($line));
			if(!empty($line[0])) $field = 0;
			else $field = 1;
			$cows[$cow]['number'] = $line[$field];
			$field++;
			$cows[$cow]['dob'] = $this->convertDate($line[$field]);
			$field++;
			if(strpos($line[$field],'/') !== false) {
				$cows[$cow]['calved'] = $this->convertDate($line[$field]);
				$field++;
				$cows[$cow]['status'] = $line[$field];
				$field++;
			} else {
				$cows[$cow]['calved'] = false;
				$cows[$cow]['status'] = 'Youngstock';
				$field++;
			}
			if(strpos($line[$field],'/') !== false) {
				$cows[$cow]['heat'] = $this->convertDate($line[$field]);
				$field = $field + 2;
			} else {
				$cows[$cow]['heat'] = false;
				$field++;
			}
			if(strpos($line[$field],'/') !== false) {
				$cows[$cow]['service'] = $this->convertDate($line[$field]);
				$field++;
			} else {
				$cows[$cow]['service'] = false;
			}
			if(is_numeric($line[$field])) {
				$cows[$cow]['milk'] = $line[$field];
				$field++;
			} else $cows[$cow]['milk'] = false;
			if(strpos($line[$field],'/') !== false) {
				$cows[$cow]['dry'] = $this->convertDate($line[$field]);
				$field++;
			} else {
				$cows[$cow]['dry'] = false;
			}
			if(is_numeric($line[$field])) {
				$cows[$cow]['fat'] = $line[$field];
				$field++;
			} else $cows[$cow]['fat'] = false;
			if(is_numeric($line[$field])) {
				$cows[$cow]['scc'] = $line[$field];
				$field++;
			} else $cows[$cow]['scc'] = false;
			$cows[$cow]['pd'] = $line[$field];
			$cow++;
		}
	}
	
	function importFromUniform() {
		$cows = $this->uniform->allStatus();
		if($cows) {
			mysql_query("TRUNCATE TABLE `status`");
			$count = 0;
			foreach($cows as $cow) {
				if($cow['number'] > 0) {
					@mysql_query("INSERT INTO `status` (cow,dob,calved,status,heat,served,milk,dry,fat,scc,pd) VALUES ('".$cow['number']."','".$cow['dob']."','".$cow['calved']."','".$cow['status']."','".$cow['heat']."','".$cow['served']."','".$cow['milk']."','".$cow['dry']."','".$cow['fat']."','".$cow['scc']."','".$cow['pd']."')") or die(mysql_error());
					$count++;
				}
			}
			$this->logImport('uniform',$count);
		} else die("Error obtaining data");
	}
	
	function logImport($type,$count) {
		if($count > 0) mysql_query("INSERT INTO logs (stamp,type,count) VALUES ('".date('U')."','".$type."','".$count."')") or die(mysql_error());
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
	
	function statusSummary() {
		$data = $this->queryAll("SELECT status,count(*) as cows FROM status GROUP BY status ORDER BY cows DESC");
		$total = 0;
		foreach($data as $row) {
			$return[$row['status']] = $row['cows'];
			$total = $total + $row['cows'];
		}
		$return['Total'] = $total;
		return $return;
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
				$this->logImport('historic',count($data));
			}
		}		
	}
	
	function checkFlags() {
		$flagged = $this->queryAll("SELECT * FROM alpro JOIN status ON alpro.cow=status.cow WHERE date='".date('Y-m-d')."' AND ".$this->currentMilking()." != '' AND flag=1");
		$text = "The following flagged cows have been milked:\n";
		if($flagged) {
			foreach($flagged as $cow) {
				$text .= $cow['cow'].' '.$cow[$this->currentMilking()]."\n";
				mysql_query("UPDATE status SET flag=0 WHERE cow='".$cow['cow']."'");
			}
			mail('matt@mattford.net','Flagged Cows',$text);
		}
	}
	
	function copyActivityData() {
		$data = $this->odbcFetchAll("SELECT TblCowActLvlHistory.CowNo,TblCowActLvlHistory.ActLevel,TblCowActLvlHistory.ActDateTime FROM TblCowActLvlHistory ORDER BY CowNo ASC");
		$updated = 0;
		if($data) {
			foreach($data as $date) {
				$level = '';
				for($i = 0;$i<$date['ActLevel'];$i++) $level .= '+';
				//echo $date['CowNo'].' '.$date['ActDateTime'].' '.$level.'<br />';
				mysql_query("UPDATE alpro SET activity = '".$level."' WHERE cow='".$date['CowNo']."' AND date='".date('Y-m-d',strtotime($date['ActDateTime']))."' LIMIT 1");
				$updated = $updated + mysql_affected_rows();
			}
		}
		$this->logImport('activity',$updated);
		echo $updated.' activity statuses inserted';
	}
	
	function copyAlproBackups() {
		$path = date('ymd',strtotime('Yesterday')).'00';
		$cab = 'C:\\Alpro\\Backup\\'.$path.'.cab';
		$sb = 'C:\\Alpro\\Backup\\SB'.$path.'S0.crd';
		if(file_exists($cab)) copy($cab,'D:\\backup\\alpro\\'.$path.'.cab');
		if(file_exists($sb)) copy($sb,'D:\\backup\\alpro\\SB'.$path.'S0.crd');
	}
	
	function fetchHighAct($date) {
		if(!$date) $date = date('Y-m-d');
		return $this->queryAll("SELECT * FROM alpro WHERE date='".$date."' AND activity IS NOT NULL");
	}
	
	function importData() {
		$this->importFromUniform();
		$this->copyHistoricMilkingTimes();
		$this->copyActivityData();
		$this->fetchCutIDTimes();
		if(date('a') == 'pm' && date('H') == '22') {
			$this->cullsToAlpro();
			$this->copyAlproBackups();
			$this->mailMissingExtraCows();
			$this->importCake();
			$this->scrapeNML();
			if(date('w') == '1') {
				$this->backup_database();
				$this->cakeReport();
			}
		}
		if(date('H') < 3) $this->resetTimesToday();
	}
	
	function fetchRecent($milking,$limit=1) {
		return $this->queryAll("SELECT * FROM alpro WHERE `date`='".gmdate('Y-m-d')."' AND ".$milking." != '' ORDER BY ".$milking." DESC LIMIT ".$limit);
	}
	
	function cakeReport() {
		$data = $this->queryAll("SELECT * FROM totalcake ORDER BY date DESC LIMIT 7");
		$message = "Hi,\n\nHere is the cake that's been fed each day according to the computer. ";
		$message .= "We're assuming it's calibrated correctly, the actual amount fed might be slightly different ";
		$message .= "but this should be a fairly good guide.\n\n";
		foreach($data as $day) {
			$date = strtotime($day['date']);
			$message .= date('D jS',$date).' '.$day['cake']."kg\n";
		}
		$message .= "\nHope that's helpful,\n\nRegards\nMatt Ford";
		mail("office@fordpartners.co.uk, sfuller@countrywidefarmers.co.uk","Cake Fed At Lime End Farm",$message,"From: Ford Partners <office@fordpartners.co.uk>");
	}
	
	function importCake() {
		mysql_query("INSERT IGNORE INTO totalcake(date,cake) VALUES('".gmdate('Y-m-d',time()-72000)."','".$this->fedYesterday()."')");
		return true;
	}
	
	//Specify an offset in seconds
	function fetchOffset($milking,$offset=60,$limit=5) {
		if(date('I')==0) $offset = -3600 + $offset; 
		$offset = date('H:i:s',time()-$offset);
		//echo date('H:i:s',$offset).' '.date('I');
		return $this->queryAll("SELECT * FROM alpro WHERE `date`='".date('Y-m-d')."' and ".$milking."<='".$offset."' ORDER BY ".$milking." DESC LIMIT ".$limit);
	}
	
	function fetchStallOffset($milking,$offset=3,$limit=8) {
		$return = $this->queryAll("SELECT * FROM alpro WHERE `date`='".date('Y-m-d')."' ORDER BY ".$milking." DESC LIMIT ".$offset.",".$limit);
		if(!$return) $return = $this->fetchOffset($milking);
		return $return;
	}
	
	function dataStatus() {
		$return['status'] = $this->queryOne("SELECT count(*) FROM status");
		$return['am'] = $this->queryOne("SELECT count(*) FROM alpro WHERE am!= '' and date='".date('Y-m-d')."'");
		$return['pm'] = $this->queryOne("SELECT count(pm) FROM alpro WHERE pm!='' and date='".date('Y-m-d')."'");
		return $return;
	}
	
	function cowStatus($cow) {
		$data = $this->queryRow("SELECT * FROM status WHERE cow='".$cow."'");
		if(!$data) return false;
		else {
			$data['dim'] = round((time() - strtotime($data['calved'])) / 60 / 60 / 24,0);
			return $data;
		}
	}
	
	function milkingSpeed() {
		$milking = $this->currentMilking();
		$data = $this->fetchRecent($milking,11);
		$prev = false;
		$total = 0;
		$true_total = 0;
		$true_count = 0;
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
				if($diff > 3) {
					$true_total = $true_total + $diff;
					$true_count++;
				}
			}
			$prev = $time;
		}
		$seconds = ($total / $count) * 40;
		$return['platform'] = floor($seconds/60) . ":" . $seconds % 60; 
		$return['speed'] = round($true_total / $true_count - 1,0);
		$return['cph'] = round(3600 / $return['speed'],0);
		return $return;
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
				//Is cow already listed for today?
				$exists = $this->queryRow("SELECT count(*) as count FROM milkrecording WHERE cow = '".$row['cow']."' AND stamp > ".strtotime('1am'));
				if($row['cow'] != $latest['cow'] && $exists['count'] == 0) {
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
		//$info = $this->odbcFetchAll("SELECT CowNo, BreedingState, DateBirth, DateHeat, DateCalving, Lactation, DateInsem From TblCow WHERE CowNo = ".$cow);
		$info = $this->queryRow("Select * FROM status WHERE cow = '".$cow."'");
		if(!$info) return false;
		else {
			$info['SinceHeat'] = $this->daysSince($info["heat"]);
			$info['SinceCalving']  = $this->daysSince($info["calved"]);
			$info['SinceInsem']  = $this->daysSince($info["served"]);
			return $info;
		}
	}
	
	function daysSince($date) {
			$unix_timestamp = strtotime($date);
			if($unix_timestamp != 0) return round((date('U',strtotime('1am')) - $unix_timestamp) / 86400,0);
			else return false;
	}
	
	function milkingSummaries() {
		$am_data = $this->queryAll("SELECT * FROM alpro WHERE date > '".date('Y-m-d',strtotime('-21 days'))."' AND am != '' AND cow!=0 ORDER BY date DESC,am ASC");
		$prev = false;
		$gaps = 0;
		$stops = 0;
		$milked = 0;
		$rows = count($am_data);
		foreach($am_data as $id => $cow) {
			$milked++;
			$cow['am_epoch'] = strtotime(date('Y-m-d').' '.$cow['am']);
			if(!$prev) $prev = $cow;
			if(!isset($data[$cow['date']])) {
				//print_r($data);
				$data[$cow['date']] = $cow;
				$data[$cow['date']]['am_start'] = $cow['am'];
				$data[$prev['date']]['am_end'] = $prev['am'];
				$data[$prev['date']]['am_gaps'] = $gaps;
				$data[$prev['date']]['am_stops'] = $stops;
				$data[$prev['date']]['am_milked'] = $milked;
				$data[$prev['date']]['milked'] = $this->queryOne("select count(*) as milked FROM alpro where date = '".$prev['date']."'");
				$data[$prev['date']]['am_missed'] = $data[$prev['date']]['milked'] - $milked;
				$gaps = 0;
				$stops = 0;
				$milked = 0;
				//print_r($data);
			}
			$diff = $cow['am_epoch'] - $prev['am_epoch'];
			if($diff > 120) {
				$gaps = $gaps + $diff;
				$stops++;
			} else {
				if(!isset($data[$cow['date']]['diff_am'][$diff])) $data[$cow['date']]['diff_am'][$diff] = 1;
				else $data[$cow['date']]['diff_am'][$diff]++;
			}
			$prev=$cow;
		}
		$pm_data = $this->queryAll("SELECT * FROM alpro WHERE date > '".date('Y-m-d',strtotime('-21 days'))."' AND pm != '' AND cow!=0 ORDER BY date DESC,pm ASC");
		$prev = false;
		$milked = 0;
		$gaps = 0;
		$stops = 0;
		foreach($pm_data as $id => $cow) {
			$milked++;
			$cow['pm_epoch'] = strtotime(date('Y-m-d').' '.$cow['pm']);
			if(!$prev) $prev = $cow;
			if(!isset($data[$cow['date']]['pm_start'])) {
				//print_r($data);
				$data[$cow['date']]['pm_start'] = $cow['pm'];
				$data[$prev['date']]['pm_end'] = $prev['pm'];
				$data[$prev['date']]['pm_gaps'] = $gaps;
				$data[$prev['date']]['pm_stops'] = $stops;
				$data[$prev['date']]['pm_milked'] = $milked;
				$data[$prev['date']]['milked'] = $this->queryOne("select count(*) as milked FROM alpro where date = '".$prev['date']."'");
				$data[$prev['date']]['pm_missed'] = $data[$prev['date']]['milked'] - $milked;
				$gaps = 0;
				$stops = 0;
				$milked = 0;
			}
			$diff = $cow['pm_epoch'] - $prev['pm_epoch'];
			if($diff > 120) {
				$gaps = $gaps + $diff;
				$stops++;
			} elseif($diff > 0) {
				if(!isset($data[$cow['date']]['diff_pm'][$diff])) $data[$cow['date']]['diff_pm'][$diff] = 1;
				else $data[$cow['date']]['diff_pm'][$diff]++;
			}
			arsort($data[$cow['date']]['diff_am']);
			arsort($data[$cow['date']]['diff_pm']);
			$prev=$cow;
			//print_r($data);
		}
		return $data;
	}
	
	function jogglerBasic() {
		$milking = $this->currentMilking();
		$data = $this->fetchRecent($milking,10);
		if($data) {
			foreach($data as $id => $cow) {
				$data[$id]['info'] = $this->uniform->panelStatus($cow['cow']);
				$data[$id]['milking'] = $milking;
			}
			return $data;
		} else return false;
	}
	
	function jogglerServing($delay=120) {
		$milking = $this->currentMilking();
		$data = $this->fetchStallOffset($milking);
		foreach($data as $id => $cow) {
			$data[$id]['info'] = $this->uniform->panelStatus($cow['cow']);
			$data[$id]['milking'] = $milking;
		}
		return $data;
	}
	
	function fetchIDTimes() {
		$data = $this->odbcFetchAll("SELECT CowNo,IDTimeTodayMM2,IDTimeTodaySS2 FROM TblCowB ORDER BY IDTimeTodayMM2 DESC");
		foreach($data as $cow) {
			$cows[$this->fixtime($cow['IDTimeTodayMM2'],$cow['IDTimeTodaySS2'])] = $cow['CowNo'];
		}
		krsort($cows);
		return $cows;
	}

	function jogglerExit() {
		$data = $this->fetchStallOffset(date('a'),30);
		foreach($data as $id => $cow) {
			$data[$id]['info'] = $this->cowInfo($cow['cow']);
		}
		return $data;
	}
	
	function jogglerMilkRecording($all=false) {
		if(date('a') == 'am') $start = strtotime(date('Y-m-d').' 01:00');
		else $start = strtotime(date('Y-m-d').' 13:00');
		$this->recordStalls();
		if($all) {
			$data['current'] = $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".$start." ORDER BY stamp DESC");
			$data['prev'] = $data['current'];
		} else {
			$data['current'] = $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".$start." ORDER BY stamp DESC LIMIT 10");
			if($data['current']) {
				$round = $this->queryOne("SELECT stamp FROM milkrecording WHERE stall='".$data['current'][0]['stall']."' AND stamp < ".$data['current'][0]['stamp']." ORDER BY stamp DESC LIMIT 1");
				$data['prev'] = $this->queryAll("SELECT * FRom milkrecording WHERE stamp <= ".$round." ORDER BY stamp DESC LIMIT 10"); 
			}
		}
		return $data;
	}
	
	function currentMilking() {
		$pm = $this->queryOne("SELECT max(pm) FROM alpro WHERE date='".date('Y-m-d')."'");
		if(!$pm OR $pm=='') return 'am';
		else return 'pm';
	}
	
	function milkingTotal($milking = false) {
		if(!$milking) $milking = $this->currentMilking();
		return $this->queryOne("SELECT count(*) FROM alpro WHERE date='".date('Y-m-d')."' AND ".$milking." != ''");
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
		$data = $this->odbcFetchAll("SELECT TblCow.CowNo,QryCowSorting2.CutReasonText,QryAreaName.CutAreaIdName,TblCow.LastCutTime,TblCow.LastCutDate FROM ((TblCow LEFT JOIN QryCowSorting2 ON (TblCow.CowNo = QryCowSorting2.CowNo AND TblCow.HerdNo = QryCowSorting2.HerdNo)) LEFT JOIN QryAreaName ON (TblCow.CowNo = QryAreaName.CowNo AND TblCow.HerdNo = QryAreaName.HerdNo)) WHERE ( TblCow.GroupNo > 0 )");
		foreach($data as $cow) {
			// Separate reason is based on current time and date
			mysql_query("INSERT IGNORE INTO shedding (cow, date, time, reason,area) VALUES ('".$cow['CowNo']."','".$cow['LastCutDate']."','".substr($cow['LastCutTime'],11,5)."','".trim($cow['CutReasonText'])."','".substr(trim($cow['CutAreaIdName']),-1)."')") or die(mysql_error());
		}
		//$this->logImport('sorting',count($data));
		return true;
	}
	
	// List of the most recent cows through the shedding gate
	// For all cows force shedding gate to sort everything then manually disable gate
	function locomotionList($limit) {
		$data = $this->queryAll("SELECT * FROM shedding WHERE date='".gmdate('Y-m-d')."' ORDER BY time DESC LIMIT ".$limit);
		return $data;
	}
	
	function listSortedCows($date,$sort='time') {
		return $this->queryAll("SELECT * FROM shedding WHERE date='".$date."' ORDER BY ".mysql_real_escape_string($sort)." ASC");
	}
	
	function milkRecordingDisplay($limit) {
		if($limit && is_numeric($limit)) return $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".strtotime('1am')." ORDER BY stamp DESC LIMIT ".$limit);
		else return $this->queryAll("SELECT * FROM milkrecording WHERE stamp > ".strtotime('1am')." ORDER BY stamp DESC");
	}
	
	function backup_database() {
		$data = shell_exec('C:\wamp\bin\mysql\mysql5.1.36\bin\mysqldump -u root alpro --skip-opt');
		file_put_contents('D:/alextra/'.date('Y-m-d').'.sql',$data);
	}
	
	function dodgyCollarsStatus($days) {
		$data = $this->queryAll("SELECT * FROM status ORDER BY cow ASC");
		foreach($data as $cow) {
			$times = $this->queryAll("SELECT * FROM alpro WHERE date > '".date('Y-m-d',strtotime('-'.$days.' days'))."' AND cow='".$cow['cow']."' ORDER BY date ASC");
			$count = 0;
			$date = false;
			//echo $cow['cow'].'<br />';
			if($times) {
				foreach($times as $day) {
					if($date != false){
						$diff = round((strtotime($day['date']) - $date) / 86400,0);
						//echo ' Diff '.$diff.'<br />';
						if($diff != 1) $count = $count + (2 * $diff);
					}
					if($day['am'] == '') $count++;
					if($day['pm'] == '') $count++;
					$date = strtotime($day['date']);
				}
				if($count > 1) $dodgy[$cow['cow']] = $count;
			}
		}
		arsort($dodgy);
		return($dodgy);
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
	
	function fetchLastUpdate() {
		$data = $this->queryAll("SELECT max(stamp) as stamp,type FROM logs GROUP BY type ORDER BY type ASC");
		foreach($data as $type) {
			$return[$type['type']] = $type['stamp'];
		}
		return $return;
	}
		
	function dashboard() {
		// Basic overview of key data
		$data['status'] = $this->dataStatus();
		$data['status_summary'] = $this->statusSummary();
		$data['in_milk'] = $this->uniform->odbcFetchAll("SELECT count(*) FROM DIER WHERE LACTATIENUMMER > 0 AND STATUS < 8");
		$data['in_milk'] = $data['in_milk']['COUNT'];
		$sorted = $this->listSortedCows(date('Y-m-d'));
		if($sorted) $data['sorted'] = count($sorted);
		else $data['sorted'] = 0;
		$data['missing_extra'] = $this->fetchMissingExtra(date('Y-m-d',strtotime('Yesterday')));
		$data['updates'] = $this->fetchLastUpdate();
		$data['activity'] = $this->fetchHighAct(false);
		return $data;
	}
}
$alpro = new alpro();
?>
