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
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.11.0/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
</head>
<body>
</body>
</html>