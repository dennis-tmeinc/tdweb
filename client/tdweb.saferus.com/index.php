<?php 
$noredir = 1 ;
chdir( '../../' );
include_once "session.php" ;
$d = dirname( $_SERVER['REQUEST_URI']."x" );
$_SESSION['clientid'] = basename( $d );
session_write();
header( 'Location: '.dirname( dirname( $d ) ).'/logon.php' ) ;
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
