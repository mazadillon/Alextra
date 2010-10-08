<?php
$host = 'localhost:C:/Documents and Settings/All Users/Documents/UNIFORM/{D9E8CF05-25CF-42DB-9904-C3D1E9619D0B}/Data/limeend.fdb';
// C:\Documents and Settings\All Users\Documents\UNIFORM\{D9E8CF05-25CF-42DB-9904-C3D1E9619D0B}\Data\limeend.fdb

//$dbh = ibase_connect($host, 'dairyland', 'TLZMnMAhn3I');
$dbh = ibase_connect($host, 'dairyland', 'open');
print_r($dbh);