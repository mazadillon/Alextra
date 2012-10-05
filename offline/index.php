<?php
require '../alextra.class.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Local Storage</title>
    <link rel="stylesheet" href="jquery.mobile-1.0rc1.min.css" />
    <script type="text/javascript" src="jquery-1.5.1.min.js"></script>
	<script src="http://code.jquery.com/jquery-1.6.4.min.js"></script>
    <script type="text/javascript" src="jquery.mobile-1.0rc1.min.js"></script>
</head>
<body>
 
<div data-role="page" id="page">
    <div data-role="header">
        <h1>Alextra Offline</h1>
    </div><!-- /header -->
 
    <div data-role="content">
	<ul data-role="listview" data-filter="true">
	<?php
	$data = $alpro->listStock();
	foreach($data as $line) {
		$theme = 'd';
		if($line['status'] == 'Open' && $line['heat'] != '0000-00-00') {
			$status = 'Open, Last heat '.$line['heat'];
			$theme = 'b';
		} elseif($line['status'] == 'Open') {
			$status='Calved '.$line['calved'].' NSB';
			$theme = 'b';
		} elseif($line['status'] == 'Pregnant') $status = 'Pregnant, Due '.date('Y-m-d',strtotime($line['served']) + 24192000);
		elseif($line['status'] == 'Dry') $status = 'Dry Since '.$line['dry'];
		elseif($line['status'] == 'Youngstock') $status = 'Born '.$line['dob'];
		elseif($line['status'] == 'Inseminated') {
			$status = 'Served '.$line['served'];
			$theme = 'e';
		} elseif($line['status'] == 'Empty') $status = 'Empty, calved '.$line['calved'];
		elseif($line['status'] == 'Barren') $theme = 'a';
		else $status = $line['status'];
		echo '<li data-theme="'.$theme.'">'.$line['cow'].' '.$status.'</li>';
	}
	echo '</ul>';
	?>
    </div><!-- /content -->
 
</div><!-- /page -->
 
</body>
</html>