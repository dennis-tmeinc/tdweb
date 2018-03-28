<!DOCTYPE html>
<html>
<head>
<?php 
require 'sessionstart.php' ;

if( !empty($_SESSION['lastpage'])) {
	header( 'Location: '.$_SESSION['lastpage'] ) ;
}
else {
	header( 'Location: logon.php' ) ;
}
?>
<title>Touch Down Center</title>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
</head>
<body>
<p>Touch Down Center</p>
</body>
</html>
