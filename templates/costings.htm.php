<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<style>
	body{text-align:center;}
	</style>
    <title>
      Milk Results
    </title>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load('visualization', '1', {packages: ['corechart']});
    </script>
    <script type="text/javascript">
      function drawVisualization() {
        // Some raw data (not necessarily accurate)
        var lVSc_data = google.visualization.arrayToDataTable([
          ['Date', 'Litres', 'Cake', 'CakeRatio']<?php
		  foreach($data as $day => $row) {
			if(!empty($row['litresSold'])) {
				echo ",\n['".date('jS M',strtotime($day))."', ".$row['litresSold'].", ".$row['cakeFed'].", ".round($row['cakeFed']/$row['litresSold'],4)."]";
			}
		}
		?>
        ]);

        var lVSc_options = {
          title : 'Litres Sold & Cake Fed',
		  hAxis: {title: "Date"},
          seriesType: "line",
		  series: {2: {type: "line",targetAxisIndex:1},3: {type: "line",targetAxisIndex:1},4: {type: "line",targetAxisIndex:2}},
		  vAxes: [{title: "Value"},{title: "Ratio"}]
        };

        var lVSc_chart = new google.visualization.ComboChart(document.getElementById('litresVScake'));
        lVSc_chart.draw(lVSc_data, lVSc_options);
		
		// Q-Sum of Milk Sold vs Budget
        var qMilk_data = google.visualization.arrayToDataTable([
          ['Date', 'LitresSold', 'LitresBudget']<?php
		  $running = 0;
		  foreach($data as $day => $row) {
			if(!empty($row['litresSold'])) {
				$running = $running + $row['litresSold'];
				echo ",\n['".date('jS M',strtotime($day))."', ".$running.", ".$budget[date('j',strtotime($day))]."]";
			}
		}
		?>
        ]);

        var qMilk_options = {
          title : 'Cumulative Milk Sold',
		  hAxis: {title: "Date"},
          seriesType: "line",
		  series: {2: {type: "line",targetAxisIndex:1},3: {type: "line",targetAxisIndex:1}},
		  vAxes: [{title: "Litres"}]
        };

        var qMilk_chart = new google.visualization.ComboChart(document.getElementById('qMilk'));
        qMilk_chart.draw(qMilk_data, qMilk_options);
		
	  	// Litres Per Cow In Milk
        var cowsVsLitres_data = google.visualization.arrayToDataTable([
          ['Date', 'CowsInMilk', 'LitresSold']<?php
		  foreach($data as $day => $row) {
			if(!empty($row['litresSold'])) {
				echo ",\n['".date('jS M',strtotime($day))."', ".$row['inMilk'].", ".round($row['litresSold']/$row['inMilk'],2)."]";
			}
		}
		?>
        ]);

        var cowsVsLitres_options = {
          title : 'Litres Sold Per Cow In Milk',
		  hAxis: {title: "Date"},
          seriesType: "line",
		  series: {1: {type: "line",targetAxisIndex:1}, 2: {type: "line",targetAxisIndex:2}},
		  vAxes: [{title: "Cows"},{title: "Litres Per Cow"}]
        };

        var cowsVsLitres_chart = new google.visualization.ComboChart(document.getElementById('cowsVsLitres'));
        cowsVsLitres_chart.draw(cowsVsLitres_data, cowsVsLitres_options);
		
      }
	  
      google.setOnLoadCallback(drawVisualization);
    </script>
  </head>
  <body>
    <div id="litresVScake" style="width: 900px; height: 500px;"></div>
	<div id="qMilk" style="width: 900px; height: 500px;"></div>
	<div id="cowsVsLitres" style="width: 900px; height: 500px;"></div>
</body>
</html>

