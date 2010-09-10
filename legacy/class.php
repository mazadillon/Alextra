<?php

class MilkRecording {
	
	function MilkRecording($path) {
		$this->path = $path;
		$this->loadFile();
	}
	
	function loadFile() {
		if(file_exists($this->path)) {
			$this->file = file($this->path);
			$this->lines = count($this->file);
			return true;
		} else return false;			
	}
	
	function duplicateCatcher($input) {
		if(!isset($this->lines) OR $this->lines == 0) return false;
		if(is_numeric($input)) {
			for($i = 0;$i < $this->lines;$i++) {
				list($stall,$number) = explode(",",$this->file[$i]);
				if(trim($input) == trim($number)) return true;
			}
		}
		return false;
	}
	
	function displayList($limit) {
		if($this->lines == 0) return true;
		if($limit == false) $limit = $this->lines;
		$count = 1;
		$return = array();
		foreach($this->file as $line) {
			if($count >= $this->lines - $limit) {
				list($stall,$number) = explode(",",$line);
				$return[$count]['stall'] = $stall;
				$return[$count]['number'] = $number;
			}
			$count++;
		}
		return $return;
	}
	
	function addEntry($stall,$number) {
		if($this->duplicateCatcher($number)) return false;
		else {
			$fp = fopen($this->path,'a');
			fputs($fp,$stall.','.$number."\n");
			fclose($fp);
			return true;
		}
	}

	function deleteEntry() {
		$this->loadFile();
		unset($this->file[$this->lines-1]);
		file_put_contents($this->path,$this->file);
	}
}
?>