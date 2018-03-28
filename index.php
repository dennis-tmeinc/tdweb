<?php 
$noredir = 1 ;
require 'session.php' ;

if( !empty($_SESSION['lastpage'])) {
	header( 'Location: '.$_SESSION['lastpage'] ) ;
}
else {
	header( 'Location: dashboard.php' ) ;
}
?><!DOCTYPE html>
<html>
<head>
<title>Touch Down Center</title>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
</head>
<body>
<p>Touch Down Center</p>
</body>
</html>
