<?php
ERROR_REPORTING(E_ALL);
require_once('alextra.class.php');

class uniform {
	function uniform($alpro) {
		$this->config =& $alpro->config;
		$this->alpro = $alpro;
		$this->unidb = odbc_connect('uniform',$this->config['uniform']['user'],$this->config['uniform']['password']) or die(odbc_error());
		if(!$this->unidb) exit("Connection Failed: " . $this->unidb);
		$this->config['status'] = array(1=>'Youngstock',2=>'Open',3=>'Inseminated',4=>'Empty',
		5=>'Pregnant',6=>'Barren',7=>'Aborted',8=>'Dry',9=>'Sold',10=>'Died',''=>'Unknown');
		$this->config['tables'] = array(
		'cows'=>'DIER',
		'breeds'=>'RAS',
		'health'=>'ZIEKTE',
		'cowhealth'=>'DIER_ZIEKTE',
		'treatments'=>'BEHANDELING',
		'cowtreatments'=>'DIER_BEHANDELING');
		$this->config['calvingease'] = array('Unknown','No Problem','Slight Problem','Assistance Needed',
		'Considerable Force','Extreme Difficulty','Ceasarean','Breach');
		return true;
	}
	
	function odbcFetchAll($query) {
		$rs=odbc_exec($this->unidb,$query);
		if(!$rs) die("Error in SQL: ".odbc_errormsg());
		$return = false;
		while($row = odbc_fetch_array($rs)) {
			$return[] = $row;
		}
		return $return;
	}
	
	function odbcFetchRow($query) {
		$rs=odbc_exec($this->unidb,$query);
		if(!$rs) die("Error in SQL: ".odbc_errormsg());
		return odbc_fetch_array($rs);
	}
	
	// Identify cows without an activity meter who are eligible for serving
	// Status not youngstock, pregnant, barren, dry or sold.
	function needsActMeter() {
		$data['inuse'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE ACT_TRANSPONDER IS NOT NULL AND STATUS < 9");
		$data['needs'] = $this->odbcFetchAll("SELECT NUMMER,STATUS FROM DIER WHERE ACT_TRANSPONDER IS NULL AND LACTATIENUMMER >= 1 AND STATUS != 5 AND STATUS != 6 AND STATUS < 8 AND LAATSTEKALFDATUM < '2012-01-01' ORDER BY NUMMER ASC");
		return $data;
	}
	
	function cowInfo($cow) {
		return $this->odbcFetchRow("SELECT * FROM DIER WHERE nummer = ".$cow." AND STATUS < 9");
	}

	function reconcileActTags() {
		$alpro_act = $this->alpro->odbcFetchAll("SELECT * FROM TblCowAct WHERE ActivityTagNo IS NOT NULL");
		foreach($alpro_act as $cow) {
			$uni = $this->odbcFetchRow("SELECT DIERID,NUMMER,ACT_TRANSPONDER FROM DIER WHERE STATUS < 9 AND NUMMER = '".$cow['CowNo']."'");
			if($cow['ActivityTagNo'] != $uni['ACT_TRANSPONDER']) {
				echo $cow['CowNo'].' has the wrong activity tag number in uniform currently '.$uni['ACT_TRANSPONDER'].' should be '.$cow['ActivityTagNo'].'<br />';
				$uni_tag = $this->odbcFetchRow("SELECT NUMMER,DIERID FROM DIER WHERE ACT_TRANSPONDER = '".$cow['ActivityTagNo']."'");
				if($uni_tag && $uni_tag['NUMMER']) {
					echo 'Tag currently on cow '.$uni_tag['NUMMER'].' in uniform.<br />';
					//if(odbc_exec($uniform,"UPDATE DIER SET ACT_TRANSPONDER='' WHERE DIERID= '".$uni_tag['DIERID']."'")) echo 'Updated '.$uni_tag['DIERID'].'<br />';
				}
				//if(odbc_exec($uniform,"UPDATE DIER SET ACT_TRANSPONDER='".$cow['ActivityTagNo']."' WHERE DIERID = '".$uni['DIERID']."'")) echo 'Updated '.$uni['DIERID'].'<br />';
			}
		}
		$uni_act = $this->odbcFetchAll("SELECT DIERID,NUMMER,ACT_TRANSPONDER FROM DIER WHERE STATUS < 9 AND ACT_TRANSPONDER IS NOT NULL");
		foreach($uni_act as $cow) {
			$alpro = $this->alpro->odbcFetchRow("SELECT * FROM TblCowAct WHERE CowNo = ".$cow['NUMMER']);
			if($alpro['ActivityTagNo'] != $cow['ACT_TRANSPONDER']) {
				echo $cow['NUMMER'].' has the wrong activity tag number in alpro currently '.$alpro['ActivityTagNo'].' should be '.$cow['ACT_TRANSPONDER'].'<br />';
				$alpro_tag = $this->alpro->odbcFetchRow("SELECT * FROM TblCowAct WHERE ActivityTagNo = ".$cow['ACT_TRANSPONDER']);
				if($alpro_tag && $alpro_tag['CowNo']) {
					echo 'Tag currently on cow '.$alpro_tag['CowNo'].' in alpro.<br />';
					//if(odbc_exec($this->alpro->odbc,"UPDATE TblCowAct SET ActivityTagNo=NULL WHERE CowNo= ".$alpro_tag['CowNo'])) echo 'Updated '.$alpro_tag['CowNo'].'<br />';
				}
				//if(odbc_exec($this->alpro->odbc,"UPDATE TblCowAct SET ActivityTagNo=".$cow['ACT_TRANSPONDER']." WHERE CowNo= ".$cow['NUMMER'])) echo 'Updated '.$cow['NUMMER'].'<br />';
			}
		}
	}
	
	// $date = Date of score Y-m-d
	// $handler = Handler code - Jon Clarke = 75
	// $data is an array with a list of rows containing:
	//  cow  = Number of cow (assuming most recent cow to use that line number)
	//  score = Score
	function importLocomotionScores($date,$handler,$data) {
		foreach($data as $cow) {
			$dierid = $this->dierid($cow['cow']);
			$count = 0;
			if($dierid) {
				$query = "INSERT INTO DIER_LOCOMOTIE (DIERID,DATUM,BEHANDELAAR,SCORE,SCORETYPE,SOURCEID) VALUES ('".$dierid."','".$date."','".$handler."','".$cow['score']."','1','100102')";
				odbc_exec($this->unidb,$query);
				echo 'Inserted score of '.$cow['score'].' for cow '.$cow['cow'].'<br />';
				$count++;
			}
		}
		echo $count.' imported.';
	}
	
	
	function fertilityBreakdown() {
		$data['round_year'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE (status=2 OR status=3 OR status=4 OR status=7) AND LAATSTEKALFDATUM < '2012-01-01'");
		$data['round_year_served'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE status=3 AND LAATSTEKALFDATUM < '2012-01-01'");
		$data['round_spring'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE (status=2 OR status=3 OR status=4 OR status=7) AND LAATSTEKALFDATUM >= '2012-01-01' AND LAATSTEKALFDATUM < '2012-07-01'");
		$data['round_spring_served'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE status=3 AND LAATSTEKALFDATUM >= '2012-01-01' AND LAATSTEKALFDATUM < '2012-07-01'");
		$data['summer'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE (status=2 OR status=3 OR status=4 OR status=7)AND LACTATIENUMMER > 0 AND LAATSTEKALFDATUM >= '2012-07-01'");
		$data['summer_served'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE status=3 AND LACTATIENUMMER > 0 AND LAATSTEKALFDATUM >= '2012-07-01'");
		$data['heifers'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE (status=1 OR status=3 OR status=4) AND LACTATIENUMMER IS NULL AND GEBOORTEDATUM <= '2011-11-01'");
		$data['heifers_served'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE status=3 AND LACTATIENUMMER IS NULL AND GEBOORTEDATUM <= '2011-11-01'");
		$data['barren'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE status=6 AND LACTATIENUMMER > 0");
		$data['cows_pregnant_spring'] = $this->odbcFetchRow("SELECT count(due.maxdate) FROM (SELECT max(DATUMBEGIN) as maxdate FROM DIER JOIN DIER_VOORTPLANTING ON DIER.DIERID = DIER_VOORTPLANTING.DIERID WHERE DIER_VOORTPLANTING.VOORTPLANTINGCODE > 1 AND DIER_VOORTPLANTING.VOORTPLANTINGCODE < 6 AND DIER_VOORTPLANTING.INS_OK = 1 AND DATUMBEGIN < '2012-10-01' AND DATUMBEGIN >= '2012-03-01' AND DIER.STATUS < 9 AND DIER.LACTATIENUMMER > 0 GROUP BY DIER.DIERID) as due");
		$data['heifers_pregnant_spring'] = $this->odbcFetchRow("SELECT count(due.maxdate) FROM (SELECT max(DATUMBEGIN) as maxdate FROM DIER JOIN DIER_VOORTPLANTING ON DIER.DIERID = DIER_VOORTPLANTING.DIERID WHERE DIER_VOORTPLANTING.VOORTPLANTINGCODE > 1 AND DIER_VOORTPLANTING.VOORTPLANTINGCODE < 6 AND DIER_VOORTPLANTING.INS_OK = 1 AND DATUMBEGIN < '2012-10-01' AND DATUMBEGIN >= '2012-03-01' AND DIER.STATUS < 9 AND DIER.LACTATIENUMMER IS NULL GROUP BY DIER.DIERID) as due");
		$data['cows_pregnant_summer'] = $this->odbcFetchRow("SELECT count(due.maxdate) FROM (SELECT max(DATUMBEGIN) as maxdate FROM DIER JOIN DIER_VOORTPLANTING ON DIER.DIERID = DIER_VOORTPLANTING.DIERID WHERE DIER_VOORTPLANTING.VOORTPLANTINGCODE > 1 AND DIER_VOORTPLANTING.VOORTPLANTINGCODE < 6 AND DIER_VOORTPLANTING.INS_OK = 1 AND DATUMBEGIN < '2012-12-31' AND DATUMBEGIN >= '2012-10-01' AND DIER.STATUS < 9 AND DIER.LACTATIENUMMER > 0 GROUP BY DIER.DIERID) as due");
		$data['heifers_pregnant_summer'] = $this->odbcFetchRow("SELECT count(due.maxdate) FROM (SELECT max(DATUMBEGIN) as maxdate FROM DIER JOIN DIER_VOORTPLANTING ON DIER.DIERID = DIER_VOORTPLANTING.DIERID WHERE DIER_VOORTPLANTING.VOORTPLANTINGCODE > 1 AND DIER_VOORTPLANTING.VOORTPLANTINGCODE < 6 AND DIER_VOORTPLANTING.INS_OK = 1 AND DATUMBEGIN < '2012-12-31' AND DATUMBEGIN >= '2012-10-01' AND DIER.STATUS < 9 AND DIER.LACTATIENUMMER IS NULL GROUP BY DIER.DIERID) as due");
		$data['dry'] = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE status=8 AND LACTATIENUMMER > 0");
		return $data;
	}
	
	function checkFeed() {
		$cake_alloc = array();
		// Load cake target feeds into array
		$cake = $this->alpro->odbcFetchAll("SELECT CowNo,TargetFeed1 FROM TblCowFeed");
		foreach($cake as $cow) $cake_alloc[$cow['CowNo']] = $cow['TargetFeed1']; 
		// Load all cows calved in last month which are still in herd
		$fresh_cows = $this->odbcFetchAll("SELECT * FROM DIER WHERE LAATSTEKALFDATUM >= '".date('Y-m-d',strtotime('-1 month'))."' AND STATUS < 8");
		if($fresh_cows) {
			$flagged = array();
			// Check each cows cake allocation against protocol for heifers/cows
			foreach($fresh_cows as $cow) {
				if(array_key_exists($cow['NUMMER'],$cake_alloc)) {
					if($cow['LACTATIENUMMER'] == 1 && $cake_alloc[$cow['NUMMER']]['TargetFeed1'] != 7) $flagged[] = $cow['NUMMER'];
					elseif($cow['LACTATIENUMMER'] > 1 && $cake_alloc[$cow['NUMMER']] != 8) $flagged[] = $cow['NUMMER'];
				} else $flagged = $cow['NUMMER'];
			}
			// Send email if cows flagged up
			if(!empty($flagged)) {
				$text = "The following fresh cows do have the standard cake set as a target, i.e. 7kg for heifers and 8kg for cows:\n\n";
				sort($flagged);
				foreach($flagged as $cow) $text .= $cow."\n";
				mail($this->config['email'],"Fresh Cow Rations",$text);
			}
		}
	}
	
	function dueByWeek($start) {
		$start = strtotime($start);
		$end = date('Y-m-d',$start + 518400);
		$start = date('Y-m-d',$start);
		$data['start'] = $start;
		$data['end'] = $end;
		$data['cows'] = $this->odbcFetchAll("SELECT * FROM DIER WHERE VERWACHTEKALFDATUM >= '".$start."' AND VERWACHTEKALFDATUM <= '".$end."' ORDER BY VERWACHTEKALFDATUM ASC,NUMMER ASC");
		return $data;
	}
	
	// Takes an array of dry cows by line number
	// Check if low SCC
	function checkDryList($drys) {
		foreach($drys as $i => $dry) {
			$dry = trim($dry);
			// Mark Johnes Cows
			if($this->cowJohnesStatus($dry)) $drys[$i] = $dry.' Johnes';
			else {
				/*
				// Look for SCC above 100 or case of mastitis in lactation
				// Johnes cows are excluded as precaution
				$dierid = $this->dierid($dry);
				$info = $this->cowInfo($dry);
				$milk = $this->odbcFetchAll("SELECT * FROM DIER_MELKGIFT_ETMAAL WHERE DIERID=".$dierid." AND DATUM > '".$info['LAATSTEKALFDATUM']."'");
				if(is_array($milk) && count($milk) > 2) {
					$flag = false;
					foreach($milk as $test) {
						if($test['AANTALCELLEN'] > 120) $flag = true;
					}
				} else $flag = true;
				$mastitis = $this->lookupHealthEvent('Clinical Mastitis');
				if($this->cowHealth($mastitis['CODEZIEKTE'],$dierid,$info['LAATSTEKALFDATUM'],date('Y-m-d'))) $flag = true;
				if($flag==false) $drys[$i] = $dry.' Low SCC';
				*/
			}						
		}
		return $drys;
	}
	
	function dueEachWeek($start='2014-01-01') {
		$start = strtotime($start);
		// Go forward one year
		$end = $start + 31536000;
		$weeks = array();
		while($start < $end) {
			$t_end = date('Y-m-d',$start + 604800);
			$query = "SELECT COUNT(*) FROM (SELECT DIERID FROM DIER WHERE VERWACHTEKALFDATUM >= '".date('Y-m-d',$start)."' AND VERWACHTEKALFDATUM < '".$t_end."')";
			$count = $this->odbcFetchRow($query);
			$weeks[date('Y-m-d',$start)] = $count['COUNT'];
			$start+=604800;
		}
		return $weeks;
	}
	
	function weightAnalysis($min_date) {
		$targets = array(2=>76,3=>110,4=>127,6=>180,12=>340,15=>420,16=>440,18=>490,21=>545,22=>586);
		$prev = 0;
		foreach($targets as $month => $weight) {
			if($month - 1 != $prev && $prev != 0) {
				$count = $month - $prev;
				$range = $weight - $targets[$prev];
				$increment = round($range / $count,0);
				for($i=1;$i<$count;$i++) {
					$new = $prev + $i;
					$old = $prev + $i - 1;
					$targets[$new] = $targets[$old] + $increment;
				}
			}
			ksort($targets);
			$prev = $month;
		}
		$data = $this->odbcFetchAll("SELECT a.*,DIER.NUMMER,DIER.GEBOORTEDATUM FROM DIER_GEWICHT a JOIN DIER ON a.DIERID=DIER.DIERID WHERE a.DATUM >= '".$min_date."'");
		$count_on=0;
		$count_off=0;
		$output = "['Age','Weight']";
		foreach($data as $cow) {
			$dob = strtotime($cow['GEBOORTEDATUM']);
			$age = round((strtotime($cow['DATUM']) - $dob) / 2592000,0);
			//echo $cow['NUMMER']. ' '.$cow['GEWICHT'].' at '.$age.' months ';
			//echo $cow['NUMMER'].','.$cow['GEWICHT'].','.$age.'<br />';
			$output .= ",\n[ ".$age.' , '.$cow['GEWICHT']." ]";
			if(isset($targets[$age])) {
				//echo 'vs '.$targets[$age].' ';
				if($cow['GEWICHT'] >= 0.95*$targets[$age]) {
					//echo 'ON<br />';
					$count_on++;
				} else {
					//echo 'BEHIND<br />';
					$count_off++;
				}
			}// else echo '<br />';
		}
		//echo $count_on.' on target, '.$count_off.' behind target';
		include 'templates/weightAnalysisGraph.htm';
	}
	
	function calvesExpectedByWeek() {
		$date = 0;
		$bulls = array();
		$weeks = array();
		$bull_ids = array();
		$data = $this->odbcFetchAll("SELECT * FROM DIER WHERE VERWACHTEKALFDATUM >= '".date('Y-m-d')."' AND VERWACHTEKALFDATUM <= '2013-11-01' AND (STATUS = 5 OR status = 8)");
		foreach($data as $calf) {
			if(!isset($bull_ids[$calf['LAATSTEINSID']])) {
				$bull = $this->odbcFetchRow("SELECT NAAM FROM DIER WHERE DIERID = ".$calf['LAATSTEINSID']);
				$bull_ids[$calf['LAATSTEINSID']] = $bull['NAAM'];
				$bulls[$bull['NAAM']] = array();
				$bull = $bull['NAAM'];
			} else $bull = $bull_ids[$calf['LAATSTEINSID']];
			if($calf['LACTATIENUMMER'] == 0) $array = 'heifers';
			else $array = 'cows';
			$week = date('W',strtotime($calf['VERWACHTEKALFDATUM']));
			if(!isset($weeks[$week])) $weeks[$week] = 0;
			if(!isset($bulls[$bull][$week])) $bulls[$bull][$week] = array('cows'=>0,'heifers'=>0);
			$bulls[$bull][$week][$array]++;
			$weeks[$week]++;
		}
		ksort($weeks);
		return array('bulls'=>$bulls,'weeks'=>$weeks);
	}
	
	// Import Johnes test from NML spreadsheet
	// $data is csv text in following format:
	// cow, date, score (numeric)
	//
	// Update existing treatments if found
	function importJohnesTest($data) {
		$data = explode("\n",$data);
		$condition = $this->lookupHealthEvent('Johnes Test');
		if($condition) {
			foreach($data as $line) {
				$line = explode(",",$line);
				$cow = $this->dierid($line[0]);
				$date = explode('/',$line[1]);
				$date = $date[2].'-'.$date[1].'-'.$date[0];
				if($cow) {
					$query = "SELECT DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING,DIER_ZIEKTE.CODEZIEKTE FROM DIER_ZIEKTE JOIN DIER ON DIER_ZIEKTE.DIERID=DIER.DIERID WHERE CODEZIEKTE=".$condition['CODEZIEKTE']." AND DIER_ZIEKTE.DIERID=".$cow." AND DATUMZIEKTE = '".$date."' AND STATUS < 9";
					$exists = $this->odbcFetchRow($query);
					echo $line[0];
					if($exists) {
						// Check and update
						if(strpos($exists['TOELICHTING'],'Under') !== false OR $exists['TOELICHTING'] != $line[2]) {
							echo ' Updating '.$exists['TOELICHTING'].' to '.$line[2].'<br />';
							$query = "UPDATE DIER_ZIEKTE SET TOELICHTING='".$line[2]."' WHERE DIERID='".$cow."' AND DATUMZIEKTE='".$date."' AND CODEZIEKTE=".$condition['CODEZIEKTE'];
							odbc_exec($this->unidb,$query);
						} else echo ' Leaving as '.$exists['TOELICHTING'].'<br />';
					} else {
						// Insert
						$query = "INSERT INTO DIER_ZIEKTE (DIERID,CODEZIEKTE,DATUMZIEKTE,TOELICHTING) VALUES ('".$cow."','".$condition['CODEZIEKTE']."','".$date."','".$line[2]."')";
						odbc_exec($this->unidb,$query);
						echo ' Inserting '.$line[2].' in to DB<br />'.$query.'<br />';
					}
				}
			}
		}
	}
	
	// Extract the list of cows who are Johnes Positive
	// Bands are:
	// Low  <20
	// Med  20-30
	// High >30
	function johnesCows() {
		$condition = $this->lookupHealthEvent('Johnes Test');
		$data = $this->odbcFetchAll("SELECT DIER.NUMMER,DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING FROM DIER JOIN DIER_ZIEKTE ON DIER.DIERID = DIER_ZIEKTE.DIERID WHERE CODEZIEKTE =".$condition['CODEZIEKTE']." AND DIER.STATUS < 9");
		$johnes = array();
		foreach($data as $test) {
			if($test['TOELICHTING'] >= 20) {
				if(!in_array($test['NUMMER'],$johnes)) $johnes[] = $test['NUMMER'];
			}
		}
		sort($johnes);
		return $johnes;
	}
	
	function cowJohnesStatus($cow) {
		$condition = $this->lookupHealthEvent('Johnes Test');
		$dierid = $this->dierid($cow);
		$johnes = $this->odbcFetchAll("SELECT DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING FROM DIER_ZIEKTE WHERE DIERID='".$dierid."' AND CODEZIEKTE =".$condition['CODEZIEKTE']." ORDER BY DATUMZIEKTE ASC");
		if($johnes) {
			foreach($johnes as $test) {
				if($test['TOELICHTING'] >= 20) return true;
			}
		}
		return false;
	}
	
	function johnesHerdwise() {
		$condition = $this->lookupHealthEvent('Johnes Test');
		$data = $this->odbcFetchAll("SELECT * FROM DIER WHERE STATUS < 9 AND STATUS > 1 AND LACTATIENUMMER >= 1");
		$dates = array();
		$cows = array();
		foreach($data as $cow) {
			$group = 1;
			$johnes = $this->odbcFetchAll("SELECT DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING FROM DIER_ZIEKTE WHERE CODEZIEKTE =".$condition['CODEZIEKTE']." AND DIERID = ".$cow['DIERID']." ORDER BY DATUMZIEKTE ASC");
			if($johnes) {
				foreach($johnes as $test) {
					$cows[$cow['NUMMER']]['tests'][$test['DATUMZIEKTE']] = $test['TOELICHTING'];
					//echo $cow['NUMMER'].' '.$test['DATUMZIEKTE'].' '.$test['TOELICHTING'].'<br />';
					if(!in_array($test['DATUMZIEKTE'],$dates)) $dates[] = $test['DATUMZIEKTE'];
				}
				// test if in one of the high groups
				if($test['TOELICHTING'] >= 20) {
					// test if any previous tests were also positive
					foreach($johnes as $test) {
						if($test['TOELICHTING'] >= 20) $group = 5;
					}
					// if not then group is 4
					if($group == 1) $group = 4;
				} else {
					// Check previous tests for positive result
					foreach($johnes as $id => $test) {
						if($id > count($johnes) - 4 && $test['TOELICHTING'] >= 20) $group = 2;
					}
					// if no positives found and more than one test negative it's group 0
					if($group == 1 && count($johnes) > 1) $group = 0;
				}
				$groups[$cow['NUMMER']] = $group;
			} else {
				$cows[$cow['NUMMER']] = array();
				$groups[$cow['NUMMER']] = 6;
			}
		}
		$return['cows'] = $cows;
		sort($dates);
		ksort($cows);
		arsort($groups);
		$return['groups'] = $groups;
		$return['dates'] = $dates;
		$return['simple'] = $this->johnesCows();
		return $return;
	}
	
	// Look up cows from a locomotion scoring that haven't been trimmed recently
	// $score = Date of locomotion scoring Y-m-d
	// $before = Ignore cows trimmed this number of days before scoring
	// $after = Ignore cows trimmed this number of days after scoring	
	function locomotionTrim($score,$before,$after) {
		$lame = $this->odbcFetchAll("SELECT DIER_LOCOMOTIE.DIERID, DIER_LOCOMOTIE.DATUM, DIER_LOCOMOTIE.SCORE, DIER.NUMMER FROM DIER_LOCOMOTIE JOIN DIER ON DIER_LOCOMOTIE.DIERID=DIER.DIERID WHERE DIER.STATUS < 9 AND DIER_LOCOMOTIE.DATUM='".$score."' AND SCORE > 1.0");
		$time = strtotime($score);
		$start = $time - ($before * 86400);
		$start = date('Y-m-d',$start);
		$end = $time+($after*86400);
		$end = date('Y-m-d',$end);
		$trim = $this->lookupHealthEvent('Foot Trimming');
		$trim = $trim['CODEZIEKTE'];
		$cows = array();
		foreach($lame as $cow) {
			$trimmed = $this->odbcFetchAll("SELECT DIER.NUMMER,DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING,DIER_ZIEKTE.CODEZIEKTE FROM DIER_ZIEKTE JOIN DIER ON DIER_ZIEKTE.DIERID=DIER.DIERID WHERE DIER.DIERID='".$cow['DIERID']."' AND CODEZIEKTE=".$trim." AND DATUMZIEKTE > '".$start."' AND DATUMZIEKTE < '".$end."' AND STATUS < 9");
			if(!$trimmed) $cows[] = $cow['NUMMER'];
		}
		sort($cows);
		return $cows;
	}
	
	function freshCowChecks($limit=60) {
		$looked = $this->lookupHealthEvent('Fresh Cow < 21 Days');
		$looked = $looked['CODEZIEKTE'];
		$dirty = $this->lookupHealthEvent('Dirty');
		$dirty = $dirty['CODEZIEKTE'];
		$clean = $this->lookupTreatment('Vet Check OK');
		$clean = $clean['CODEBEHANDELING'];
		$cows = array();
		$to_check = $this->odbcFetchAll("SELECT * FROM DIER WHERE (STATUS = 2 OR STATUS = 3) AND LAATSTEKALFDATUM < '".date('Y-m-d',strtotime('-21 days'))."' ORDER BY LAATSTEKALFDATUM ASC");
		foreach($to_check as $cow) {
			if(count($cows) < $limit) {
				$dim = (time() - strtotime($cow['LAATSTEKALFDATUM'])) / 86400;
				$check_21 = $this->healthDIM($looked,$cow['DIERID'],round($dim,0));
				$check_clean = $this->cowTreatment($clean,$cow['DIERID'],date('Y-m-d',strtotime($cow['LAATSTEKALFDATUM'])+(60*60*24*21)),date('Y-m-d',strtotime("+1 day")));
				$check_dirty = $this->healthDIM($dirty,$cow['DIERID'],round($dim,0));
				//if(!$check_21 AND !$check_clean AND !$check_dirty) $cows[] = $cow['NUMMER'];
				if(!$check_clean AND !$check_dirty) $cows[] = $cow['NUMMER'];
			}
		}
		sort($cows);
		return array_unique($cows);
	}
	
	function eligibleToServe($date,$vwp = 40,$include_served = true) {
		$clean = $this->lookupTreatment('Vet Check OK');
		$clean = $clean['CODEBEHANDELING'];
		$start = strtotime($date) - ($vwp * 60 * 60 * 24);
		$to_check = $this->odbcFetchAll("SELECT * FROM DIER WHERE (STATUS = 2 OR STATUS = 3 OR STATUS = 4 OR STATUS = 7) AND LAATSTEKALFDATUM < '".date('Y-m-d',$start)."' ORDER BY LAATSTEKALFDATUM ASC");
		$cows = array();
		foreach($to_check as $cow) {
			$check_clean = $this->cowTreatment($clean,$cow['DIERID'],$cow['LAATSTEKALFDATUM'],date('Y-m-d'));
			if($check_clean && $include_served) $cows[] = $cow['NUMMER'];
			elseif($check_clean && $cow['STATUS'] != 3) $cows[] = $cow['NUMMER'];
		}
		sort($cows);
		return $cows;
	}
	
	function notSeenBulling($date,$days=23) {
		$start = $this->eligibleToServe($date,40,false); // VWP is int
		$before = date('Y-m-d',strtotime($date) - ($days * 86400));
		$return['eligible'] = count($start);
		$return['cows'] = array();
		$cidr_sync = $this->lookupHealthEvent('CIDR Synchronise');
		$cidr_sync = $cidr_sync['CODEZIEKTE'];
		$cystic = $this->lookupHealthEvent('Cystic Ovaries');
		$cystic = $cystic['CODEZIEKTE'];
		foreach($start as $cow) {
			$dierid = $this->dierid($cow);
			$check = $this->odbcFetchAll("SELECT * FROM DIER WHERE NUMMER=".$cow." AND LAATSTETOCHTDATUM >= '".$before."'");
			$cidr_before = date('Y-m-d',strtotime('-14 days'));
			$cidr_in = $this->cowHealth($cidr_sync,$dierid,$cidr_before,date('Y-m-d'));
			$is_cystic = $this->cowHealth($cystic,$dierid,$cidr_before,date('Y-m-d'));
			if(!$check && !$cidr_in && !$is_cystic) $return['cows'][] = $cow;
		}
		return $return;
	}
	
	function recentFootTrimmings() {
		$trim = $this->lookupHealthEvent('Foot Trimming');
		$trim = $trim['CODEZIEKTE'];
		$trimmed = $this->odbcFetchAll("SELECT FIRST 3 DIER_ZIEKTE.DATUMZIEKTE,COUNT(*) FROM DIER_ZIEKTE WHERE CODEZIEKTE=".$trim." GROUP BY DATUMZIEKTE ORDER BY DATUMZIEKTE DESC");
		$dates = array();
		foreach($trimmed as $date) $dates[$date['DATUMZIEKTE']] = $date['COUNT'];
		return $dates;
	}
	
	function footRechecks() {
		$recheck = $this->lookupHealthEvent('Recheck Feet');
		$recheck = $recheck['CODEZIEKTE'];
		$trim = $this->lookupHealthEvent('Foot Trimming');
		$trim = $trim['CODEZIEKTE'];
		$block = $this->lookupTreatment('Block');
		$block = $block['CODEBEHANDELING'];
		$shoe = $this->lookupTreatment('Hoof Shoe');
		$shoe = $shoe['CODEBEHANDELING'];
		$cows = array();
		$due = $this->odbcFetchAll("SELECT DIER.NUMMER,DIER.DIERID,DIER_BEHANDELING.DATUMZIEKTE FROM DIER_BEHANDELING JOIN DIER ON DIER_BEHANDELING.DIERID=DIER.DIERID WHERE (CODEBEHANDELING=".$block." OR CODEBEHANDELING=".$shoe.") AND DATUMZIEKTE < '".date('Y/m/d',strtotime('-5 weeks'))."' AND DIER.STATUS < 8");
		if($due) {
			foreach($due as $cow) {
				$trimmed = $this->odbcFetchAll("SELECT DIER.NUMMER,DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING,DIER_ZIEKTE.CODEZIEKTE FROM DIER_ZIEKTE JOIN DIER ON DIER_ZIEKTE.DIERID=DIER.DIERID WHERE DIER.DIERID='".$cow['DIERID']."' AND CODEZIEKTE=".$trim." AND DATUMZIEKTE > '".$cow['DATUMZIEKTE']."'");
				if(!$trimmed) $cows[] = $cow['NUMMER'];
			}
		}
		$cows = array_unique($cows);
		$due = $this->odbcFetchAll("SELECT DIER.NUMMER,DIER.DIERID,DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING,DIER_ZIEKTE.CODEZIEKTE FROM DIER_ZIEKTE JOIN DIER ON DIER_ZIEKTE.DIERID=DIER.DIERID WHERE CODEZIEKTE=".$recheck." AND DATUMZIEKTE > '".date('Y/m/d',strtotime('-1 year'))."' AND DIER.STATUS < 8 ORDER BY DIERID ASC,DATUMZIEKTE DESC");
		$last = false;
		if($due) {
			foreach($due as $cow) {
				if($last!=$cow['DIERID']) {
					if(ctype_digit($cow['TOELICHTING'])) $multi = $cow['TOELICHTING'] - 1;
					else $multi = 3;
					$trimafter = strtotime($cow['DATUMZIEKTE']) + ($multi * 604800);
					if($trimafter < time()) {
						$trimafter = date('Y-m-d',$trimafter);
						$trimmed = $this->odbcFetchAll("SELECT DIER.NUMMER,DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING,DIER_ZIEKTE.CODEZIEKTE FROM DIER_ZIEKTE JOIN DIER ON DIER_ZIEKTE.DIERID=DIER.DIERID WHERE DIER.DIERID='".$cow['DIERID']."' AND CODEZIEKTE=".$trim." AND DATUMZIEKTE >= '".$trimafter."'");
						if(!$trimmed) $cows[] = $cow['NUMMER'];
					} else {
						// If cow has been added for a block recheck but the chosen
						// recheck date is later - remove here
						$key = array_search($cow['NUMMER'],$cows);
						if($key) unset($cows[$key]);
					}
				}
				$last = $cow['DIERID'];
			}
		}
		return array_unique($cows);
	}
	
	function allStatus() {
		$data = $this->odbcFetchAll("SELECT * FROM DIER WHERE DIER.STATUS < 9");
		if($data) {
			foreach($data as $id=> $cow) {
				$cows[$id]['number'] = $cow['NUMMER'];
				$cows[$id]['dob'] = $cow['GEBOORTEDATUM'];
				$cows[$id]['calved'] = $cow['LAATSTEKALFDATUM'];
				$cows[$id]['status'] = $this->config['status'][$cow['STATUS']];
				$cows[$id]['heat'] = $cow['LAATSTETOCHTDATUM'];
				$cows[$id]['served'] = $cow['LAATSTETOCHTINSDATUM'];
				$cows[$id]['dry'] = $cow['LAATSTEDROOGDATUM'];
				$milk = $this->odbcFetchRow("SELECT FIRST 1 * FROM DIER_MELKGIFT_ETMAAL WHERE DIERID=".$cow['DIERID']." ORDER BY DATUM DESC");
				if($milk) {
					$cows[$id]['milk'] = $milk['HOEVEELHEIDMELK'];
					$cows[$id]['fat'] = $milk['PCTVET'];
					$cows[$id]['scc'] = $milk['AANTALCELLEN'];
				}
			}
			return $cows;
		} else return false;
		//cow,dob,calved,status,heat,served,milk,dry,fat,scc,pd
	}
	
	function panelStatus($number) {
		$data = $this->odbcFetchRow("SELECT * FROM DIER WHERE DIER.STATUS < 9 AND NUMMER = ".$number);
		if($data) {
			$cow['cow'] = $data['NUMMER'];
			$cow['name'] = $data['NAAM'];
			$cow['status'] = $this->config['status'][$data['STATUS']];
			$cow['dim'] = round((time() - strtotime($data['LAATSTEKALFDATUM'])) / 86400,0);
			$cow['johnes'] = $this->cowJohnesStatus($number);
			if(strtotime($data['LAATSTEINSDATUM']) > strtotime($data['LAATSTETOCHTDATUM'])) {
				$cow['heat'] = $data['LAATSTEINSDATUM'];
				$sire_name = $this->odbcFetchRow("SELECT NAAM FROM DIER WHERE DIERID = ".$data['LAATSTEINSID']);
				if($sire_name) $cow['bull'] = $sire_name['NAAM'];
				else $sire_name = false;
			} else {
				$cow['heat'] = $data['LAATSTETOCHTDATUM'];
				$cow['bull'] = false;
			}
			if(strtotime($cow['heat']) != 0) $cow['SinceHeat'] = round((strtotime('1am') - strtotime($cow['heat'].' 1am')) / 86400,0);
			else $cow['SinceHeat'] = false;
		} else $cow['cow'] = $number;
		return $cow;
	}
	
	function dierid($cow) {
		$data = $this->odbcFetchRow("SELECT DIERID FROM DIER WHERE DIER.STATUS < 9 AND NUMMER = ".$cow);
		if($data) return $data['DIERID'];
		else return false;
	}
	
	function earNumberFromDierID($dierid) {
		$dier = $this->odbcFetchRow("SELECT LEVENSNUMMER FROM DIER WHERE DIERID='".$dierid."'");
		if($dier) return $dier['LEVENSNUMMER'];
		else return false;
	}
	
	// Table DIER_MUTATIES
	// AANAFVOERDOOD:
	// 3 Died
	// 2 Sold
	// 1 Purchased
	// 0 Born
	// Table DIER_MELKGIFT_LACTATIE
	// DATUMWERKELIJKEDROOGZET Dry Off Date
	function cowsInMilk($start,$end) {
		// Select cows whose lactations started before date and ended after
		$data = $this->odbcFetchAll("SELECT * FROM DIER_MELKGIFT_LACTATIE WHERE DATUMAFKALVEN <= '".$start."' AND (DATUMWERKELIJKEDROOGZET >= '".$end."' OR DATUMWERKELIJKEDROOGZET IS NULL)");
		$cows = array();
		foreach($data as $cow) {
			// Check not sold or died during period
			if(!$this->odbcFetchAll("SELECT * FROM DIER_MUTATIES WHERE DIERID='".$cow['DIERID']."' AND AANAFVOERDOOD > 1 AND DATUM <='".$end."'")) {
				$cows[] = $cow['DIERID'];
			}
		}
		return $cows;
	}
	
	function eligibleForService($start, $end) {
		$eligible = array();
		foreach($this->cowsInMilk($start,$end) as $cow) {
			$calvingdate = $this->odbcFetchRow("SELECT FIRST 1 DATUMAFKALVEN FROM DIER_MELKGIFT_LACTATIE WHERE DIERID=".$cow." AND DATUMAFKALVEN < '".$start."' ORDER BY DATUMAFKALVEN DESC");
			// Check not marked as barren before end of period OR pregnant before start
			if($calvingdate) {
				if(!$this->odbcFetchAll("SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE = 10 AND DIERID = '".$cow."' AND DATUMBEGIN < '".$end."'")
				&& !$this->odbcFetchAll("SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE = 7 AND DIERID = '".$cow."' AND DATUMBEGIN < '".$start."' AND DATUMBEGIN > '".$calvingdate['DATUMAFKALVEN']."'")
				&& !$this->odbcFetchAll("SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND DIERID = '".$cow."' AND DATUMBEGIN < '".$start."' AND DATUMBEGIN > '".$calvingdate['DATUMAFKALVEN']."' AND INS_OK = 1")) {
					$eligible[] = $cow;
				}
			}
		}
		return $eligible;
	}
	
	function goodBreeders() {
		echo '<h1>Good Breeders</h1>';
		echo '<p>These cows have always held to first service, for older cows they have held to first service every year since 2010</p>';
		$cows = $this->odbcFetchAll("SELECT * FROM DIER WHERE LAATSTEKALFDATUM >= '2013-07-01' AND LAATSTEKALFDATUM <= '2013-08-01' AND STATUS=2 AND LACTATIENUMMER > 2 ORDER BY NUMMER ASC");
		foreach($cows as $id => $cow) {
			$services = $this->odbcFetchAll("SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND DIERID = '".$cow['DIERID']."' AND DATUMBEGIN >= '2010-01-01' ORDER BY DATUMBEGIN ASC");
			$prev = false;
			foreach($services as $service) {
				//echo $cow['NUMMER'].' '.$service['DATUMBEGIN'].' '.$service['INS_OK'].'<br />';
				//If service didn't hold, save it for max one more service
				if($service['INS_OK'] != 1) {
					// If there is a previous failed service, cut from list
					if($prev != false) {
						unset($cows[$id]);
						break;
						// Otherwise save previous and continue
					} else $prev = $service;
				} else {
					// If there has been a previous service which didn't hold and wasn't the day before then cut cow from list
					if($prev != false) {
						$diff = (strtotime($service['DATUMBEGIN']) - strtotime($prev['DATUMBEGIN']))/60/60/24;
						//echo $cow['NUMMER'].' '.$service['DATUMBEGIN'].' '.$service['DATUMBEGIN'].' '.$diff.'<br />';
						if($diff > 7) {
							unset($cows[$id]);
							break;
						} else $prev = false;
					}
				}
			}
			if($prev != false) {
				unset($cows[$id]);
			}
			unset($service);
		}
		foreach($cows as $cow) echo $cow['NUMMER'].'<br />';
	}
	
	function pregnantJohnes() {
		$johnes = $this->JohnesCows();
		$cows = array();
		foreach($johnes['high'] as $cow => $info) {
			if($this->odbcFetchAll("SELECT * FROM DIER WHERE status = 5 AND DIERID = ".$this->dierid($cow))) $cows[] = $cow;
		}
		foreach($johnes['med'] as $cow => $info) {
			if($this->odbcFetchAll("SELECT * FROM DIER WHERE status = 5 AND DIERID = ".$this->dierid($cow))) $cows[] = $cow;
		}
		return $cows;
	}
	
	function abortedCows() {
		return $this->odbcFetchAll("SELECT * FROM DIER JOIN DIER_VOORTPLANTING ON DIER.DIERID = DIER_VOORTPLANTING.DIERID AND VOORTPLANTINGCODE=11 ORDER BY DATUMBEGIN DESC");
	}
	
	function abortedTree() {
		$data = $this->abortedCows();
		$id = array();
		foreach($data as $cow) {
			if(in_array($cow['DIERID'],$id)) echo $cow['LEVENSNUMMER'].' has had multiple abortions<br />';
			$id[] = $cow['DIERID'];
		}
		foreach($data as $cow) {
			if(in_array($cow['DIERIDMOEDER'],$id)) echo $cow['LEVENSNUMMER']. ' has mother which has aborted ('.$this->earNumberFromDierID($cow['DIERIDMOEDER']).')<br />';
			if(!empty($cow['DIERIDMOEDER'])) $grandam = $this->odbcFetchAll("SELECT * FROM DIER WHERE DIERID=".$cow['DIERIDMOEDER']);
			if($grandam && in_array($grandam['DIERIDMOEDER'],$id)) $cow['LEVENSNUMMER'].' has grandmother which has aborted ('.$grandam['LEVENSNUMMER'].')<br />';
		}
	}
	
	function familyTree() {
		$cows = $this->odbcFetchAll("SELECT * FROM DIER WHERE STATUS <9");
		foreach($cows as $cow) {
			$i = 0;
			$dam = $cow['DIERIDMOEDER'];
			while($dam) {
				$damid = $this->odbcFetchAll("SELECT DIERIDMOEDER FROM DIER WHERE DIERID=".$dam);
				$dam = $damid['DIERIDMOEDER'];
				$i++;
			}
			if($i > 6) echo $cow['NUMMER'].' has '.$i.' generations<br />';
		}
	}
	
	function conceptionRate($start,$end) {
		echo '<p>Services during period '.$start.' to '.$end.'</p>';
		$inseminators = array("John","Roly","Matt");
		$totals = array();
		$services = $this->odbcFetchAll("SELECT DIER_VOORTPLANTING.*,DIER.NAAM FROM DIER_VOORTPLANTING JOIN DIER ON DIER_VOORTPLANTING.DIERID_INSEMINATIE = DIER.DIERID WHERE VOORTPLANTINGCODE = 3 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' AND DATUMBEGIN <= '".date('Y-m-d',strtotime($end))."'");
		foreach($services as $service) {
			if(!isset($bulls[$service['NAAM']])) {
				$bulls[$service['NAAM']] = array();
			}
			if(in_array($service['INSEMINATOR_CODE'],$inseminators)) {
				if(!isset($totals[$service['INSEMINATOR_CODE']])) {
					$totals[$service['INSEMINATOR_CODE']] = array("served" => 0,"pregnant"=>0);
				}
				if(!isset($bulls[$service['NAAM']][$service['INSEMINATOR_CODE']])) {
					$bulls[$service['NAAM']][$service['INSEMINATOR_CODE']] = array("served" => 0,"pregnant"=>0);
				}
				$bulls[$service['NAAM']][$service['INSEMINATOR_CODE']]['served']++;
				$totals[$service['INSEMINATOR_CODE']]['served']++;
				if($service['INS_OK']==1) {
					$bulls[$service['NAAM']][$service['INSEMINATOR_CODE']]['pregnant']++;
					$totals[$service['INSEMINATOR_CODE']]['pregnant']++;
				}
			}
		}
		foreach($bulls as $name => $data) {
			if(!empty($data)) {
				echo '<h3>'.$name.'</h3>';
				foreach($data as $inseminator=>$stats) {
					echo $inseminator.' '.$stats['pregnant'].' / '.$stats['served'].' = '.round($stats['pregnant']/$stats['served']*100,1).'%<br />';
				}
			}
		}
		echo '<h3>Totals</h3>';
		$total=array('served'=>0,'pregnant'=>0);
		foreach($totals as $name => $stats) {
			echo $name.' '.$stats['pregnant'].' / '.$stats['served'].' = '.round($stats['pregnant']/$stats['served']*100,1).'%<br />';
			$total['served'] = $total['served'] + $stats['served'];
			$total['pregnant'] = $total['pregnant'] + $stats['pregnant'];
		}
		echo '<br />'.$total['pregnant'].' / '.$total['served'].' = '.round($total['pregnant']/$total['served']*100,1).'%<br />';
	}
	
	function conceptionRateByDay($start,$end) {
		echo '<p>Services during period '.$start.' to '.$end.'</p>';
		$inseminators = array("John","Roly","Matt");
		$totals = array();
		$services = $this->odbcFetchAll("SELECT DIER_VOORTPLANTING.*,DIER.NAAM FROM DIER_VOORTPLANTING JOIN DIER ON DIER_VOORTPLANTING.DIERID_INSEMINATIE = DIER.DIERID WHERE VOORTPLANTINGCODE = 3 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' AND DATUMBEGIN <= '".date('Y-m-d',strtotime($end))."'");
		foreach($services as $service) {
			if(!isset($dates[$service['DATUMBEGIN']])) {
				$dates[$service['DATUMBEGIN']] = array();
			}
			if(in_array($service['INSEMINATOR_CODE'],$inseminators)) {
				if(!isset($dates[$service['DATUMBEGIN']][$service['INSEMINATOR_CODE']])) {
					$dates[$service['DATUMBEGIN']][$service['INSEMINATOR_CODE']] = array("served" => 0,"pregnant"=>0);
				}
				$dates[$service['DATUMBEGIN']][$service['INSEMINATOR_CODE']]['served']++;
				if($service['INS_OK']==1) {
					$dates[$service['DATUMBEGIN']][$service['INSEMINATOR_CODE']]['pregnant']++;
				}
			}
		}
		ksort($dates);
		$week = array();
		$days = 0;
		$weeks = 0;
		echo '<table border="1"><tr><th>Date</th>';
		foreach($inseminators as $bloke) echo '<td>'.$bloke.'</td>';
		echo '</tr>';
		foreach($dates as $name => $data) {
			$days++;
			if(!empty($data)) {
				echo '<tr><td>'.$name.' '.date('D',strtotime($name)).'</td>';
				foreach($inseminators as $bloke) {
					if(!isset($week[$bloke])) $week[$bloke] = array("served" => 0,"pregnant"=>0);
					echo '<td>';
					if(isset($data[$bloke])) {
						$week[$bloke]['served'] = $week[$bloke]['served'] + $data[$bloke]['served'];
						$week[$bloke]['pregnant'] = $week[$bloke]['pregnant'] + $data[$bloke]['pregnant'];
						echo $data[$bloke]['pregnant'].' / '.$data[$bloke]['served'].' = '.round($data[$bloke]['pregnant']/$data[$bloke]['served']*100,1).'%</td>';
					} else echo '&nbsp;</td>';
				}
				echo '</tr>';
			}
			if($days >= 7) {
				$weeks++;
				echo '<tr><th>Week '.$weeks.'</th>';
				foreach($inseminators as $bloke) {
					echo '<th>';
					if(isset($week[$bloke]) && $week[$bloke]['served']> 0) echo $week[$bloke]['pregnant'].' / '.$week[$bloke]['served'].' = '.round($week[$bloke]['pregnant']/$week[$bloke]['served']*100,1).'%</td>';
					else echo '&nbsp;</th>';
				}
				echo '</tr>';
				$days = 0;
				$week = array();
			}
		}
		echo '</table>';
	}

	
	// Table DIER_VOORTPLANTING
	// VOORTPLANTINGCODE:
	// 11 Aborted
	// 10 Barren
	// 9 PD-
	// 8 Uncertain PD
	// 7 PD+
	// 5 With Bull
	// 4 Natural Service
	// 3 DIY AI
	// 2 Service AI
	// 1 Heat
	// INS_OK 1=Held to service
	function kpi_preg6weeks($start) {
		$end = date('Y-m-d',strtotime($start)+3628800); // Plus 6 weeks
		$eligible = $this->eligibleForService($start,$end);
		$preg = 0;
		foreach($eligible as $cow) {
			if($this->odbcFetchAll("SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DIERID = ".$cow." AND DATUMBEGIN <= '".$end."' AND DATUMBEGIN >= '".$start."'")) {
				$preg++;
			}
		}
		$return['start'] = $start;
		$return['end'] = $end;
		$return['eligible'] = count($eligible);
		$return['pregnant'] = $preg;
		return $return;
	}
	
	function kpi_blockStats($start,$end,$lactation = "all") {
		if($lactation == "all") $lact = '';
		elseif($lactation == "cows") $lact = 'LACTATIENR > 1 AND ';
		else $lact = 'LACTATIENR = '.round($lactation,0).' AND ';
		$first_calving = $this->odbcFetchRow("SELECT FIRST 1 * FROM DIER_MELKGIFT_LACTATIE WHERE ".$lact."DATUMAFKALVEN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMAFKALVEN ASC");
		$last_calving = $this->odbcFetchRow("SELECT FIRST 1 * FROM DIER_MELKGIFT_LACTATIE WHERE ".$lact."DATUMAFKALVEN <= '".date('Y-m-d',strtotime($end))."' ORDER BY DATUMAFKALVEN DESC");
		$count = $this->odbcFetchRow("SELECT COUNT(*) calvings FROM DIER_MELKGIFT_LACTATIE WHERE ".$lact."DATUMAFKALVEN <= '".date('Y-m-d',strtotime($end))."' AND DATUMAFKALVEN >= '".date('Y-m-d',strtotime($start))."'");
		$half = round($count['CALVINGS'] / 2,0);
		$first_quart = round($count['CALVINGS'] / 4,0);
		$last_quart = round($count['CALVINGS'] * 0.75,0);
		$mid_calving = $this->odbcFetchRow("SELECT FIRST 1 SKIP ".$half." * FROM DIER_MELKGIFT_LACTATIE WHERE ".$lact."DATUMAFKALVEN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMAFKALVEN ASC");
		$first_quart_calving = $this->odbcFetchRow("SELECT FIRST 1 SKIP ".$first_quart." * FROM DIER_MELKGIFT_LACTATIE WHERE ".$lact."DATUMAFKALVEN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMAFKALVEN ASC");
		$last_quart_calving = $this->odbcFetchRow("SELECT FIRST 1 SKIP ".$last_quart." * FROM DIER_MELKGIFT_LACTATIE WHERE ".$lact."DATUMAFKALVEN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMAFKALVEN ASC");
		return array("start"=>$first_calving['DATUMAFKALVEN'],"end"=>$last_calving['DATUMAFKALVEN'],"count"=>$count['CALVINGS'],"first_quart"=>$first_quart_calving['DATUMAFKALVEN'],"half"=>$mid_calving['DATUMAFKALVEN'],"last_quart"=>$last_quart_calving['DATUMAFKALVEN'],);
	}
	
	function kpi_expectedBlockStats($start,$end) {
		$count = $this->odbcFetchRow("SELECT count(*) CALVINGS FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DATUMBEGIN <= '".date('Y-m-d',strtotime($end))."' AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."'");
		$half = round($count['CALVINGS'] / 2,0);
		$first_quart = round($count['CALVINGS'] / 4,0);
		$last_quart = round($count['CALVINGS'] * 0.75,0);
		$first_calving = $this->odbcFetchRow("SELECT FIRST 1 * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMBEGIN ASC");
		$last_calving = $this->odbcFetchRow("SELECT FIRST 1 * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMBEGIN DESC");
		$mid_calving = $this->odbcFetchRow("SELECT FIRST 1 SKIP ".$half." * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMBEGIN ASC");
		$first_quart_calving = $this->odbcFetchRow("SELECT FIRST 1 SKIP ".$first_quart." * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMBEGIN ASC");
		$last_quart_calving = $this->odbcFetchRow("SELECT FIRST 1 SKIP ".$last_quart." * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND INS_OK = 1 AND DATUMBEGIN >= '".date('Y-m-d',strtotime($start))."' ORDER BY DATUMBEGIN ASC");
		return array(
		"start"=>date('Y-m-d',strtotime($first_calving['DATUMBEGIN'])+280*60*60*24),
		"end"=>date('Y-m-d',strtotime($last_calving['DATUMBEGIN'])+280*60*60*24),
		"count"=>$count['CALVINGS'],
		"first_quart"=>date('Y-m-d',strtotime($first_quart_calving['DATUMBEGIN'])+280*60*60*24),
		"half"=>date('Y-m-d',strtotime($mid_calving['DATUMBEGIN'])+280*60*60*24),
		"last_quart"=>date('Y-m-d',strtotime($last_quart_calving['DATUMBEGIN'])+280*60*60*24));
	}
	
	function kpi_homebred() {
		$data = $this->odbcFetchAll("SELECT * FROM DIER WHERE STATUS < 9");
		foreach($data as $cow) {
			$yob = date('Y',strtotime($cow['GEBOORTEDATUM']));
			$lact = $cow['LACTATIENUMMER'];
			if(strpos($cow['LEVENSNUMMER'],'UK262483') !== false OR strpos($cow['LEVENSNUMMER'],'UKSO0611') !== false ) $homebred = 'home';
			else $homebred = 'bought';
			if(!isset($return[$yob])) {
				$return[$yob]['home'] = array('count'=>0,'total_lactations'=>0);
				$return[$yob]['bought'] = array('count'=>0,'total_lactations'=>0);
			}
			$return[$yob][$homebred]['count']++;
			$return[$yob][$homebred]['total_lactations'] = $lact + $return[$yob][$homebred]['total_lactations'];
		}
		foreach($return as $year => $data) {
			$return[$year]['home']['average_lactations'] = $data['home']['total_lactations'] / $data['home']['count'];
			$return[$year]['bought']['average_lactations'] = $data['bought']['total_lactations'] / $data['bought']['count'];
		}
		ksort($return);
		return $return;
	}
	
		
	function kpi_pregnant_by_week($start,$end,$heifers=false) {
		set_time_limit(150);
		$start = strtotime($start);
		$end = strtotime($end);
		$return = array();
		while($end > $start) {
			$this_end = $start + 604800;
			$offset_start = date('Y',$start) - 2;
			if($heifers) $preg = $this->odbcFetchRow("SELECT count(*) FROM DIER_VOORTPLANTING WHERE DATUMBEGIN < '".date('Y-m-d',$this_end)."' AND DATUMBEGIN >= '".date('Y-m-d',$start)."' AND INS_OK = 1 AND VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 7 AND DIERID NOT IN (SELECT a.DIERID FROM DIER_MELKGIFT_LACTATIE a WHERE DATUMAFKALVEN < '".date('Y-m-d',$end)."' AND  DATUMAFKALVEN > '".date('Y-m-d',$offset_start)."' GROUP BY a.DIERID)");
			else $preg = $this->odbcFetchRow("SELECT count(*) FROM DIER_VOORTPLANTING WHERE DATUMBEGIN < '".date('Y-m-d',$this_end)."' AND DATUMBEGIN >= '".date('Y-m-d',$start)."' AND INS_OK = 1 AND VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 7 AND DIERID IN (SELECT a.DIERID FROM DIER_MELKGIFT_LACTATIE a WHERE DATUMAFKALVEN < '".date('Y-m-d',$end)."' AND  DATUMAFKALVEN > '".date('Y-m-d',$offset_start)."' GROUP BY a.DIERID)");
			$return[date('Y-m-d',$start)] = $preg['COUNT'];
			$start = $this_end;
		}
		return $return;
	}
	
	//SELECT count(*) FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 7 AND INS_OK = 1 AND DATUMBEGIN < '2012-10-08' AND DATUMBEGIN >= '2012-10-01' AND DIERID NOT IN (SELECT a.DIERID FROM DIER_MELKGIFT_LACTATIE a WHERE DATUMAFKALVEN < '2012-10-01' AND DATUMAFKALVEN > '2010-01-01' GROUP BY a.DIERID)
	function kpi_calved_by_week($start,$end,$heifers=false) {
		$start = strtotime($start)+24192000;
		$end = strtotime($end)+24192000;
		$return = array();
		while($end > $start) {
			$this_end = $start + 604800;
			if($heifers) $preg = $this->odbcFetchRow("SELECT COUNT(*) FROM DIER_MELKGIFT_LACTATIE WHERE LACTATIENR = 1 AND DATUMAFKALVEN >= '".date('Y-m-d',$start)."' AND DATUMAFKALVEN < '".date('Y-m-d',$this_end)."'");
			else $preg = $this->odbcFetchRow("SELECT COUNT(*) FROM DIER_MELKGIFT_LACTATIE WHERE LACTATIENR > 1 AND DATUMAFKALVEN >= '".date('Y-m-d',$start)."' AND DATUMAFKALVEN < '".date('Y-m-d',$this_end)."'");
			$return[date('Y-m-d',$start)] = $preg['COUNT'];
			$start = $this_end;
		}
		return $return;
	}
	
	function import_EID_Bucket() {
		//DIER.ISO_TRANSPONDER
	}
	
	function importWeight($date,$time,$earnumber,$weight) {
		$dier = $this->odbcFetchRow("SELECT DIERID FROM DIER WHERE LEVENSNUMMER='".$earnumber."'");
		if($dier) $dierid = $dier['DIERID'];
		else return false;
		if($weight > 0 AND $weight < 2000) {
			if(odbc_exec($this->unidb,"INSERT INTO DIER_GEWICHT (DIERID,DATUM,TIJDSTIPMETING,GEWICHT,SOURCEID) VALUES (".$dierid.",'".date('Y-m-d',strtotime($date))."','".$time."',".$weight.",'100102')")!== false) return true;
			else return false;
		}
	}
	
	// Returns the number of cows per day which have had at least one service
	// by that given date. Only includes services within the period specified.
	function kpi_served_by_day($start,$end) {
		$start = strtotime($start);
		$end = strtotime($end);
		$served = $this->odbcFetchAll("SELECT DIERID,MIN(DATUMBEGIN) first FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND DATUMBEGIN < '".date('Y-m-d',$end)."' AND DATUMBEGIN >= '".date('Y-m-d',$start)."' GROUP BY DIERID ORDER BY first ASC");
		$day = false;
		$count = 0;
		$totals = array();
		$dierids = array();
		foreach($served as $row) {
			$count++;
			if($row['FIRST'] != $day && $day != false) $totals[$day] = $count;
			$day = $row['FIRST'];
		}
		$totals[$day] = $count;
		return $totals;
	}
	
	// Analyse submission rate by week
	function kpi_submission($start,$weeks=10) {
		$return = array();
		for($i=0;$i<$weeks;$i++) { // Loop through weeks
			$begin = date('Y-m-d',strtotime($start)+(604800 * $i)); // Plus $i weeks
			$end = date('Y-m-d',strtotime($start)+(604800 * ($i+1))); // Plus $i+1 weeks
			$eligible = $this->eligibleForService($begin,$end);
			$return[$begin]['eligible'] = count($eligible);
			$return[$begin]['served'] = 0;
			$served = $this->odbcFetchAll("SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND DATUMBEGIN <= '".$end."' AND DATUMBEGIN >= '".$begin."'");
			foreach($eligible as $cow) {
				foreach($served as $id => $served_cow) {
					if($cow == $served_cow['DIERID']) {
						unset($served[$id]);
						$return[$begin]['served']++;
						break;
					}
				}
			}
		}
		return $return;
	}
	
	function kpi_scc() {
		$sccs = $this->odbcFetchAll("SELECT DATUM, AANTALCELLEN, HOEVEELHEIDMELK FROM DIER_MELKGIFT_ETMAAL WHERE DATUM > '".date('Y-m',strtotime('-2 years'))."-01' AND AANTALCELLEN IS NOT NULL ORDER BY DATUM DESC");
		$recording = false;
		$recordings = array();
		foreach($sccs as $scc) {
			if($scc['DATUM'] != $recording) {
				if($recording && $i['count'] > 10) {
					$i['date'] = $recording;
					$i['average'] = $i['total'] / $i['count'];
					$i['weighted'] = $i['wtotal'] / $i['litres'];
					$recordings[$recording] = $i;
				}
				$i['count'] = 0;
				$i['litres'] = 0;
				$i['total'] = 0;
				$i['wtotal'] = 0;
				$i['over'] = 0;
			}
			$i['count']++;
			$i['total'] = $i['total'] + $scc['AANTALCELLEN'];
			$i['wtotal'] = $i['wtotal'] + ($scc['HOEVEELHEIDMELK'] * $scc['AANTALCELLEN']);
			$i['litres'] = $i['litres'] + $scc['HOEVEELHEIDMELK'];
			if($scc['AANTALCELLEN'] > 200) $i['over']++;
			$recording = $scc['DATUM'];
		}
		return $recordings;
	}
	
	function kpi_locomotion($start,$end) {
		$info = $this->odbcFetchRow("SELECT count(*) FROM DIER_LOCOMOTIE WHERE DIER_LOCOMOTIE.DATUM >= '".$start."' AND DIER_LOCOMOTIE.DATUM <= '".$end."' AND SCORE IS NOT NULL");
		$return['all'] = $info['COUNT'];
		$info = $this->odbcFetchRow("SELECT count(*) FROM DIER_LOCOMOTIE WHERE DIER_LOCOMOTIE.DATUM >= '".$start."' AND DIER_LOCOMOTIE.DATUM <= '".$end."' AND SCORE > 1.0 AND SCORE < 3.0");
		$return['medium'] = $info['COUNT'];
		$info = $this->odbcFetchRow("SELECT count(*) FROM DIER_LOCOMOTIE WHERE DIER_LOCOMOTIE.DATUM >= '".$start."' AND DIER_LOCOMOTIE.DATUM <= '".$end."' AND SCORE > 2.0");
		$return['high'] = $info['COUNT'];
		return $return;
	}
	
	// Excludes bulls, only DIY AI and Service AI
	function kpi_firstService($start,$end) {
		$query = "SELECT * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 4 AND DATUMBEGIN <= '".$end."' AND DATUMBEGIN >= '".$start."' ORDER BY DIERID ASC,DATUMBEGIN ASC";
		$services = $this->odbcFetchAll($query);
		$return['preg'] = 0;
		$return['all'] = 0;
		$return['h_preg'] = 0;
		$return['h_all'] = 0;
		$dier = false;
		foreach($services as $cow) {
			if($dier != $cow['DIERID']) {
				$heifer = $this->odbcFetchAll("SELECT LACTATIENR FROM DIER_MELKGIFT_LACTATIE WHERE DATUMAFKALVEN <= '".$start."' AND DIERID='".$cow['DIERID']."'");
				if(!$heifer) {
					if($cow['INS_OK'] == 1) $return['h_preg']++;
					$return['h_all']++;
				} else {
					if($cow['INS_OK'] == 1) $return['preg']++;
					$return['all']++;
				}
			}
			$dier = $cow['DIERID'];
		}
		return $return;
	}
	
	function kpi_cullage($year = false) {
		if(!$year) $year = date('Y');
		$cows = $this->odbcFetchAll("SELECT * FROM DIER WHERE STATUS > 8 AND LACTATIENUMMER > 0");
		$soldage = 0;
		$soldlact = 0;
		$soldcount = 0;
		$diedage = 0;
		$diedlact = 0;
		$diedcount = 0;
		foreach($cows as $cow) {
			$left = $this->odbcFetchAll("SELECT * FROM DIER_MUTATIES WHERE DIERID=".$cow['DIERID']." AND AANAFVOERDOOD > 1 AND DATUM >= '".$year."-01-01' AND DATUM <= '".$year."-12-31'");
			if($left) {
				$left = $left[0];
				$ageleft = (strtotime($left['DATUM']) - strtotime($cow['GEBOORTEDATUM'])) / 86400;
				// Was it sold?
				if($left['AANAFVOERDOOD'] == 2) {
					$soldage += $ageleft;
					$soldlact += $cow['LACTATIENUMMER'];
					$soldcount++;
				} else {
					$diedage += $ageleft;
					$diedlact += $cow['LACTATIENUMMER'];
					$diedcount++;
				}
			}
		}
		$return['sold_count'] = $soldcount;
		$return['sold_age'] = $soldage/$soldcount;
		$return['sold_lact'] = $soldlact/$soldcount;
		$return['died_count'] = $diedcount;
		$return['died_lact'] = $diedlact/$diedcount;
		$return['died_age'] = $diedage/$diedcount;
		return $return;
	}
	
	function kpi_heifer_losses($year) {
		$born = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE GESLACHT_CODE > 1 AND GEBOORTEDATUM >= '".$year."-01-01' AND GEBOORTEDATUM <= '".$year."-12-31' AND DIERSOORT=1 AND (LEVENSNUMMER LIKE 'UK262483%' OR LEVENSNUMMER LIKE '262483%')");
		$calved_once = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE GESLACHT_CODE > 1 AND GEBOORTEDATUM >= '".$year."-01-01' AND GEBOORTEDATUM <= '".$year."-12-31' AND LACTATIENUMMER > 0 AND (LEVENSNUMMER LIKE 'UK262483%' OR LEVENSNUMMER LIKE '262483%')");
		$calved_twice = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE GESLACHT_CODE > 1 AND GEBOORTEDATUM >= '".$year."-01-01' AND GEBOORTEDATUM <= '".$year."-12-31' AND LACTATIENUMMER > 1 AND (LEVENSNUMMER LIKE 'UK262483%' OR LEVENSNUMMER LIKE '262483%')");
		$survived = $this->odbcFetchRow("SELECT count(*) FROM DIER WHERE GESLACHT_CODE > 1 AND GEBOORTEDATUM >= '".$year."-01-01' AND GEBOORTEDATUM <= '".$year."-12-31' AND STATUS < 9 AND (LEVENSNUMMER LIKE 'UK262483%' OR LEVENSNUMMER LIKE '262483%')");
		$return['year'] = $year;
		$return['born'] = $born['COUNT'];
		$return['still_here'] = $survived['COUNT'];
		$return['calved_once'] = $calved_once['COUNT'];
		$return['calved_twice'] = $calved_twice['COUNT'];
		return $return;
	}
	
	function kpi_mastitis_cases($year) {
		$mastitis = $this->lookupHealthEvent("Clinical Mastitis");
		$months=array();
		$return['milking'] = 0;
		for($i = 1;$i <13;$i++) {
			$mid_month = strtotime($year.'-'.$i.'-15');
			$start = date('Y-m-d',$mid_month - 2592000);
			$end = date('Y-m-d',$mid_month + 2592000);
			$months[$i]['milking'] = count($this->cowsInMilk($start,$end));
			$return['milking'] += $months[$i]['milking'];
			$months[$i]['cases'] = count($this->healthReporting($mastitis['CODEZIEKTE'],$start,$end));
			$months[$i]['kpi'] = round($months[$i]['cases'] / $months[$i]['milking'] * 100/12,1);
		}
		$return['cases'] = count($this->healthReporting($mastitis['CODEZIEKTE'],$year.'-01-01',$year.'-12-31'));
		$return['months'] = $months;
		$return['year'] = $year;
		$return['milking'] = $return['milking'] / 12;
		$return['kpi'] = round($return['cases'] / $return['milking'] * 100);
		return $return;
	}
	
	function sortCow($cow,$wait=0,$days=0,$am=false,$pm=false) {
		if($cow > 9999 OR $cow < 0) $cow = false;
		if($wait > 15 OR $wait < 0) $wait = 0;
		if($days > 15 OR $days < 0) $days = 0;
		if($am != true) $am = 0;
		else $am = 1;
		if($pm != true) $pm = 0;
		else $pm = 1;
		if($cow && $days > 0) {
			$date = date('Y-m-d 00:00:00',strtotime('+'.$wait.' days'));
			$query = "UPDATE DIER_SEPARATIE SET DATUM = '".$date."',DAGEN=".$days.",MELKBEURT_1=".$am.",MELKBEURT_2=".$pm." WHERE DIERID=".$this->dierid($cow);
			odbc_exec($this->unidb,$query) or die(odbc_errormsg());
			return true;
		} else return false;
	}

	
	// Excludes dry cows and anything calved in the last 50 days
	function footTrimming($days=150,$max=false) {
		$data = $this->odbcFetchAll("SELECT * FROM DIER WHERE status < 8 AND DIER.LACTATIENUMMER > 0 AND LAATSTEKALFDATUM < '".date('Y-m-d',strtotime('-50 days'))."' AND DIER.DIERID NOT IN (SELECT DIERID FROM DIER_BEHANDELING WHERE CODEBEHANDELING = '7' AND DATUMBEHANDELING > '".date('Y-m-d',strtotime('-'.$days.' days'))."') ORDER BY NUMMER ASC");
		$cows = array();
		foreach($data as $cow) {
			$date = $this->odbcFetchRow("SELECT FIRST 1 DATUMBEHANDELING FROM DIER_BEHANDELING WHERE CODEBEHANDELING = '7' AND DIERID = ".$cow['DIERID']." ORDER BY DATUMBEHANDELING DESC");
			if($date) $dates[] = strtotime($date['DATUMBEHANDELING']);
			else $dates[] = 0;
			$cows[] = $cow['NUMMER'];
		}
		if($max > 0) {
			$return = array();
			asort($dates);
			foreach($dates as $i => $k) {
				if(count($return) < $max) {
					$return[] = $cows[$i];
					//echo $cows[$i].' '.date('Y-m-d',$k).'<br />';
				}
			}
			$cows = $return;
		}
		return $cows;
	}
	
	// Check if cows have been trimmed in the last $days before dry off
	function trimBeforeDry($days=80,$max=false) {
		$trim = array();
		$dates = array();
		// Get all milkers
		$data = $this->odbcFetchAll("SELECT DIER.DIERID,DIER.NUMMER FROM DIER WHERE DIER.STATUS = 5 AND DIER.LACTATIENUMMER > 0 ");
		// Gestation - dry length - trim before
		$trimafterservice = 86400 * (280 - 56 - $days);
		foreach($data as $cow) {
			// Check if due to dry off
			$preg = $this->odbcFetchRow("SELECT FIRST 1 * FROM DIER_VOORTPLANTING WHERE VOORTPLANTINGCODE > 1 AND VOORTPLANTINGCODE < 6 AND DIERID = ".$cow['DIERID']." AND DATUMBEGIN >= '".date('Y-m-d',strtotime('-1 Year'))."' AND INS_OK = 1 ORDER BY DATUMBEGIN DESC");
			if($preg) {
				// Work out date cow must be trimmed after
				$date = strtotime($preg['DATUMBEGIN']) + $trimafterservice;
				// Is date in the future?
				if($date < time()) {
					$date = date('Y-m-d',$date);
					// Has it been trimmed since the start of that date range?
					$trimmed = $this->odbcFetchAll("SELECT DIERID FROM DIER_BEHANDELING WHERE DIERID = ".$cow['DIERID']." AND CODEBEHANDELING = '7' AND DATUMBEHANDELING >= '".$date."'");
					if(!$trimmed) {
						$trim[] = $cow['NUMMER'];
						$dates[] = $date;
					}
				}
			}
		}
		// If there's a limit, sort by the cut off date
		if($max > 0) {
			$return = array();
			asort($dates);
			foreach($dates as $i => $k) {
				if(count($return) < $max) {
					$return[] = $trim[$i];
				}
			}
			$trim = $return;
		}
		sort($trim);
		return $trim;
	}
	
	function dueSort($date=false) {
		if(!$date) return $this->odbcFetchAll("SELECT DATUM,COUNT(*) FROM DIER_SEPARATIE WHERE DATUM > '".date('Y-m-d')."' GROUP BY DATUM ORDER BY DATUM ASC");
		else return $this->odbcFetchAll("SELECT DIER.NUMMER FROM DIER_SEPARATIE JOIN DIER ON DIER.DIERID=DIER_SEPARATIE.DIERID WHERE DATUM = '".$date."' ORDER BY DIER.NUMMER ASC");
	}
	
	// Lookup a health condition for a specific cow within a certain few days in milk
	// $condition = condition ID (ZIEKTE.CODEZIEKTE)
	// $cow = cow ID (DIER.DIERID)
	// $dim = days in milk
	function healthDIM($condition,$cow,$dim) {
		$calvingdate = $this->odbcFetchRow("SELECT LAATSTEKALFDATUM FROM DIER WHERE DIERID=".$cow);
		$start = date('Y-m-d',strtotime($calvingdate['LAATSTEKALFDATUM']));
		$end = date('Y-m-d',strtotime($calvingdate['LAATSTEKALFDATUM'])+(86400*$dim));
		$date = $this->odbcFetchAll("SELECT DATUMZIEKTE,TOELICHTING FROM DIER_ZIEKTE WHERE DIERID=".$cow." AND CODEZIEKTE=".$condition." AND DATUMZIEKTE >= '".$start."' AND DATUMZIEKTE <= '".$end."'");
		if(!$date) return false;
		else return true;
	}
	
	function cowTreatment($treatment,$cow,$start,$end) {
		return $this->odbcFetchAll("SELECT * FROM DIER_BEHANDELING WHERE DATUMZIEKTE > '".$start."' AND DATUMZIEKTE < '".$end."' AND DIERID = ".$cow." AND CODEBEHANDELING = ".$treatment);
	}
	
	function cowHealth($condition,$cow,$start,$end) {
		return $this->odbcFetchAll("SELECT * FROM DIER_ZIEKTE WHERE DATUMZIEKTE > '".$start."' AND DATUMZIEKTE < '".$end."' AND DIERID = ".$cow." AND CODEZIEKTE = ".$condition);
	}
	
	function neosporaCows() {
		$cond = $this->lookupHealthEvent("Neospora");
		$cows = $this->healthReporting($cond['CODEZIEKTE'],'2000-01-01',date('Y-m-d'));
		foreach($cows as $cow) {
			echo $cow['NUMMER'].'<br />';
		}
	}
	
	// Load all the info on animals treated with a given condition within the time period
	// $condition = condition ID (ZIEKTE.CODEZIEKTE)
	// $start = Period start date Y-m-d
	// $end = Period end date Y-m-d
	function healthReporting($condition,$start,$end) {
		return $this->odbcFetchAll("SELECT DIER.*,DIER_ZIEKTE.DATUMZIEKTE,DIER_ZIEKTE.TOELICHTING,DIER_ZIEKTE.CODEZIEKTE FROM DIER_ZIEKTE JOIN DIER ON DIER_ZIEKTE.DIERID=DIER.DIERID WHERE CODEZIEKTE=".$condition." AND DATUMZIEKTE > '".$start."' AND DATUMZIEKTE < '".$end."' AND STATUS < 9");
	}
	
	// Calving QSum, look for specific health conditions affecting cows calving between given dates
	// $start = Calving period start date Y-m-d
	// $end = Calving period end date Y-m-d
	// $dim = Days in milk to look
	// $conditions = array() of records from ZIEKTE table
	function calvingQsum($start,$end,$dim,$conditions) {
		$cows = $this->odbcFetchAll("SELECT NUMMER,DIER.DIERID,STATUS,LAATSTEKALFDATUM,AFKALFVERLOOP_CODE FROM DIER JOIN DIER_MELKGIFT_LACTATIE ON DIER.DIERID=DIER_MELKGIFT_LACTATIE.DIERID WHERE LAATSTEKALFDATUM=DIER_MELKGIFT_LACTATIE.DATUMAFKALVEN AND LAATSTEKALFDATUM > '".$start."' AND LAATSTEKALFDATUM < '".$end."' AND STATUS < 9 ORDER BY LAATSTEKALFDATUM ASC");
		foreach($cows as $id => $cow) {
			foreach($conditions as $condition) {
				$cows[$id][$condition['OMSCHRIJVING']] = $this->healthDIM($condition['CODEZIEKTE'],$cow['DIERID'],$dim);
			}
			// Average cell count during serving period
			$scc = $this->odbcFetchAll("SELECT AVG(AANTALCELLEN) AS SCC FROM DIER_MELKGIFT_ETMAAL WHERE DATUM >= '2011-10-01' AND DATUM <= '2011-12-31' AND DIERID=".$cow['DIERID']);
			$cows[$id]['scc'] = $scc['SCC'];
		}
		return $cows;
	}
	
	// Search for a health condition (ZIEKTE)
	// based on condition name
	function lookupHealthEvent($condition) {
		return $this->odbcFetchRow("SELECT * FROM ZIEKTE WHERE OMSCHRIJVING = '".$condition."'");
	}
	
	// Search for a treatment (BEHANDELING)
	// based on treatment name
	function lookupTreatment($treatment) {
		return $this->odbcFetchRow("SELECT * FROM BEHANDELING WHERE OMSCHRIJVING = '".$treatment."'");
	}
	
	function findTwins() {
		$cows = array();
		$twins = $this->odbcFetchAll("SELECT DIER_VOORTPLANTING.*,DIER.NUMMER FROM DIER_VOORTPLANTING JOIN DIER ON DIER_VOORTPLANTING.DIERID=DIER.DIERID WHERE DIER_VOORTPLANTING.DATUMBEGIN >= '2013-04-01' AND DIER_VOORTPLANTING.VOORTPLANTINGCODE=7 AND lower(DIER_VOORTPLANTING.OPMERKING) LIKE '%twins%'");
		foreach($twins as $twin) if(!in_array($twin['NUMMER'],$cows)) $cows[] = $twin['NUMMER'];
		sort($cows);
		return $cows;
	}
}
//$data=odbc_exec($uniform,'SELECT * FROM DIER JOIN DIER_ZIEKTE on DIER.DIERID=DIER_ZIEKTE.DIERID JOIN ZIEKTE on ZIEKTE.CODEZIEKTE=DIER_ZIEKTE.CODEZIEKTE WHERE NUMMER=5 ORDER BY DATUMZIEKTE') or die(odbc_errormsg());

