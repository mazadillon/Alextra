<?php

class alpro {

	function alpro($config) {
		$this->config = $config;
		$this->config['mysql']['handle'] = mysql_connect($this->config['mysql']['host'],$this->config['mysql']['user']);
		mysql_select_db($this->config['mysql']['db']);
	}
	
	function timeToUnix($time) {
		$time = explode(":",$time);
		return mktime($time[0],$time[1],$time[2]);
	}
	
	function parseCSV($path) {
		$file = file($path);
		$date = explode(',',$file[0]);
		$date = explode(' ',$date[2]);
		$date = explode('.',$date[2]);
		$data['date'] = '20'.$date[2].'-'.$date[1].'-'.$date[0];
		$data['data'] = array();
		for($i = 7;$i<count($file);$i++) {
			$data['data'][] = explode(',',$file[$i]);
		}
		return $data;
	}
	
	function parseDate($date) {
		if(!$date) $date = now();
		$path = $this->config['paths']['base'].'Milking Times\\Milking Times'.gmdate('dmy',$date).'.csv';
		if(file_exists($path)) {
			$times = $this->parseCSV($path);
			foreach($times['data'] as $cow) {
				$this->saveTimes($cow[0],$times['date'],$cow[1],$cow[2]);
			}
		} else echo $path.' does not exist';
		
		$path = $this->config['paths']['base'].'Activity Meters\\'.gmdate('dmy',$date).'.csv';
		if(file_exists($path)) {
			$activity = $this->parseCSV($path);
			foreach($activity['data'] as $cow) {
				if(trim($cow[4]) != '') $this->saveActivity($cow[0],$activity['date'],$cow[4]);
			}
		} else echo $path.' does not exist';
		return true;
	}
	
	function saveTimes($no,$date,$am,$pm) {
		return mysql_query("INSERT IGNORE INTO alpro (cow,date,am,pm) VALUES ('".$no."','".$date."','".$am."','".$pm."')") or die(mysql_error());
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
	
	function saveActivity($no,$date,$level) {
		$result = mysql_query("UPDATE alpro SET activity = '".$level."' WHERE date='".$date."' AND cow='".$no."'") or die(mysql_error());
		if(mysql_affected_rows() < 1) return false;
		else return true;
	}
}
?>