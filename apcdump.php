<!DOCTYPE html>
<html>
<head>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">		
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?> <script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script><script src="picker.js"></script>
	<style type="text/css">
	</style>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script>
	<style type="text/css">
	</style>
</head>
<body>
<div id="container">
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<li><img src="res/side-livetrack-logo-green.png" /></li>
	<li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">APC INFO</span></strong>
</div>

<div id="rcontainer">

<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">
</div>

<div id="workarea">


<pre> 

<?php

	if( apc_compile_file('apcconfig.php') ) {
		echo "Compile_file done.\n";
		
apc_compile_file('config.php');
apc_compile_file('avlsave.php');
		
	$a = apc_cache_info();
	print_r($a);
		
		
		
		$files = array();
		$files[] = "D:\\TouchDownCenter\\www\\tdc\\apcconfig.php" ;
		$d = apc_bin_dump();
		
		echo bin2hex($d);

		apc_clear_cache();
		
		apc_bin_load($d);

	$a = apc_cache_info();
	print_r($a);
	
		echo "var:".$apcvar."\n" ;
	}
	
	
?>

</pre>


</div>

</div>
<!-- mcontainer --></div>
<div id="push"></div>
</div>
<div id="footer">
<hr />
<div id="footerline" style="padding-left:24px;padding-right:24px">
<div style="float:left"><span  id="servertime" style="color:#800080;font-size: 11px;"><?php
echo date("Y-m-d H:i") ;
?> </span></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>