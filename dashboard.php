<!DOCTYPE html>
<html>
<head><?php 

require 'session.php'; 
session_save('lastpage', $_SERVER['REQUEST_URI'] );
	
if( empty( $_SESSION['dashboardpage'] ) ) {
	header( 'Location: dashboardlive.php' ) ;
}
else {
	header( 'Location: '. $_SESSION['dashboardpage'] ) ;
}
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
</head>
<body>
</body>
</html>