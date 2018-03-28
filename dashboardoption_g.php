<!DOCTYPE html>
<html>
<head>
	<?php 
	require 'config.php' ; 
	require 'session.php'; 
	$_SESSION['dashboardpage']=$_SERVER['REQUEST_URI'] ;
	?>	
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">
	<link href="tdclayout.css" rel="stylesheet" type="text/css" />
	<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
	<link href="http://code.jquery.com/ui/1.10.4/themes/<?php echo $jqueryui_theme ?>/jquery-ui.css" rel="stylesheet" /><script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
	<script src="timepicker.js"></script>
	<script>
        // start up 
        
$(document).ready(function(){
			
$("button").button();	
$(".btset").buttonset();

$(".btset input").change(function(){
   location=$(this).attr("href");
});				
            // add more init functions
            
		});
        
	</script>
	<style type="text/css">.sum_circle
{
background-image:url('res/big_dashboard_circles.png');
background-repeat:no-repeat;
background-position:center;
height: 72px;
font-size:36px;
text-align: center;
}
.sum_title
{
height: 1em;
font-size:11px;
font-weight:bold;
text-align: center;
}

#summary_table
{
border-collapse:collapse;
}
#summary_table td, #summary_table th 
{
font-size:1em;
border:1px solid #98bf21;
padding:3px 7px 2px 7px;
}
#summary_table th 
{
font-size:1.1em;
text-align:left;
padding-top:5px;
padding-bottom:4px;
background-color:#A7C942;
color:#ffffff;
}
#summary_table tr.alt td 
{
color:#000000;
background-color:#EAF2D3;
}

#vehicle_list
{
border-collapse:collapse;
min-width:600px ;
}
#vehicle_list td, #vehicle_list th 
{
font-size:1em;
border:1px solid #98bf21;
padding:3px 7px 2px 7px;
}
#vehicle_list th 
{
font-size:1.1em;
text-align:left;
padding-top:5px;
padding-bottom:4px;
background-color:#A7C942;
color:#ffffff;
}
#vehicle_list tr.alt td 
{
color:#000000;
background-color:#EAF2D3;
}
	</style>
</head>
<body>
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><img src="res/side-dashboard-logo-green.png" /></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<!--	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?> -->
	<?php if(  $_SESSION['user_type'] == "operator"  ){ ?>
	<li><a class="lmenu" href="driveby.php"><img onmouseout="this.src='res/side-driveby-logo-clear.png'" onmouseover="this.src='res/side-driveby-logo-fade.png'" src="res/side-driveby-logo-clear.png" /> </a></li>
	<?php } ?>	
	<li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">DASHBOARD</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">
<p class="btset"><input href="dashboardmorning.php" id="btmorning" type="radio" /><label for="btmorning"> Morning Status Report </label> <input href="dashboardlive.php" id="btlive" type="radio" /><label for="btlive"> Live Status Report </label> <input checked="checked" href="dashboardoption.php" id="btoption" type="radio" /><label for="btoption"> Dashboard Options </label></p>
<h4><strong>Dashboard Options</strong></h4>


<select id="timepicker" size="8" style="position:absolute;display:none;"><option></option> </select>

<div class="ui-widget ui-widget-content ui-corner-all">
<div id="userlist" class="ui-widget-content" >

<form action="#" name="dashboardoption">
<p>Morning Report / Start Of Day: (Cut-Off Time, Default 3:00AM)&nbsp; <input id="startofday" maxlength="8" name="startofday" type="text" value="3:00AM" /></p>

<p>Calculate Average Form Data: (DD/MM/YYYY) <input id="averagefromdate" maxlength="12" name="AverageFromDate" type="text" value="01/01/2013" /></p>

<p>Status Summary: (Check Items To Display)</p>


<table border="0" cellpadding="0" cellspacing="0" style="margin-left: 50px;">
	<tbody>
		<tr>
			<td><label><input name="operatinghours" type="checkbox" />Operating Hours</label></td>
			<td><label><input name="connectionalerts" type="checkbox" />Connection Alerts</label></td>
			<td><label><input name="idlingalerts" type="checkbox" />Idling Alerts</label></td>
			<td><label><input name="racingstarts" type="checkbox" />Racing Starts</label></td>
		</tr>
		<tr>
			<td><label><input name="distancetravelled" type="checkbox" />Distance Travelled</label></td>
			<td><label><input name="cameraalerts" type="checkbox" />Camera Alerts</label></td>
			<td><label><input name="cameraalerts" type="checkbox" />Camera Alerts</label></td>
			<td><label><input name="cameraalerts" type="checkbox" />Camera Alerts</label></td>
		</tr>
		<tr>
			<td><label><input name="vehiclescheckedin" type="checkbox" />Vehicles Checked-in</label></td>
			<td><label><input name="recordingalerts" type="checkbox" />Recording Alerts (V.Lost)</label></td>
			<td><label><input name="parkingalerts" type="checkbox" />Parking Alerts</label></td>
			<td><label><input name="parkingalerts" type="checkbox" />Parking Alerts</label></td>
		</tr>
		<tr>
			<td><label><input name="vehiclesuploaded" type="checkbox" />Vehicles Uploaded</label></td>
			<td><label><input name="fanfilteralerts" type="checkbox" />Fan Filter Alerts</label></td>
			<td><label><input name="vehiclesuploaded" type="checkbox" />Vehicles Uploaded</label></td>
			<td><label><input name="fanfilteralerts" type="checkbox" />Fan Filter Alerts</label></td>
		</tr>
		<tr>
			<td><label><input name="hoursofvideo" type="checkbox" />Hours Of Video</label></td>
			<td><label><input name="temperaturealerts" type="checkbox" />Temperature Alerts</label></td>
			<td><label><input name="hoursofvideo" type="checkbox" />Hours Of Video</label></td>
			<td><label><input name="temperaturealerts" type="checkbox" />Temperature Alerts</label></td>
		</tr>
		<tr>
			<td><label><input name="totalvideoclips" type="checkbox" />Total Video Clips</label></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><label><input name="temperaturealerts" type="checkbox" />Temperature Alerts</label></td>
		</tr>
		<tr>
			<td><label><input name="markedvideoclips" type="checkbox" />Marked Video Clips</label></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><label><input name="markedvideoclips" type="checkbox" />Marked Video Clips</label></td>
		</tr>
	</tbody>
</table>
</form>
<p><button id="applyoption">Apply Options</button></p>
</div></div>
</div>
<!-- workarea --></div>
<!-- mcontainer --></div>

<div id="footer">
<hr />
<div id="footerline" style="padding-left:24px;padding-right:24px">
<div style="float:left"><span style="color:#800080;font-size: 11px;"><?php
echo date("Y-m-d H:i:s") ;
?> </span></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>