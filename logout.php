<?php 
require 'session.php' ;
session_save( 'user', '' );
header("Location: logon.php");
?><!DOCTYPE html>
<html>
<head>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta http-equiv="REFRESH" content="0;url=logon.php">
	<link href="tdclayout.css" rel="stylesheet" type="text/css" />
	<style type="text/css">
	</style>
</head>
<body>
<p>
Logout in process, please wait!
</p>
</body>
</html>