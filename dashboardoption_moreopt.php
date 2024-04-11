<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
$_SESSION['dashboardpage']=$_SERVER['REQUEST_URI'] ;
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><link rel="stylesheet" href="https://libs.cdnjs.net/jqueryui/<?php echo $jquiver; ?>/themes/<?php echo $jqtheme; ?>/jquery-ui.min.css"><script src="https://libs.cdnjs.net/jquery/<?php echo $jqver; ?>/jquery.min.js"></script><script src="https://libs.cdnjs.net/jqueryui/<?php echo $jquiver; ?>/jquery-ui.min.js"></script>
	<script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
	</style>
	<link rel="stylesheet" href="https://libs.cdnjs.net/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css"><script src="https://libs.cdnjs.net/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
	<script src="td_alert.js"></script><script>
// start up 
$(document).ready(function(){
	
$("button").button();	
$(".btset").controlgroup();


$(".btset input").change(function(){
   location=$(this).attr("href");
});				
   
// generic message box   
$( ".tdcdialog#dialog_message" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Ok": function() {
			$( this ).dialog( "close" );
		}
	}
});			

// options form
function dashboard_option_reload()
{
	$("form#dashboardoption")[0].reset();
	$.getJSON("dashboardoptionload.php", function(dashboardoption){
		// fill form fields
		for (var field in dashboardoption) {
			var elm=$("form#dashboardoption input[name='"+field+"']");
			if( elm.length>0 ) {
				if( elm.prop("type")=="checkbox" ) {
					elm.prop("checked", (dashboardoption[field]=='on'));
				}
				else {
					elm.val(dashboardoption[field]);
				}
			}
		}
	});
}
dashboard_option_reload();
$("button#dashboardoptionreset").click(dashboard_option_reload);
$("button#dashboardoptionsave").click(function(){
    $.getJSON("dashboardoptionsave.php", $('form#dashboardoption').serializeArray(), function(data){
		if( data.res == 1 ) {
			$( ".tdcdialog#dialog_message #message" ).text("Dashboard Options Saved!");
		}
		else if( data.errormsg ) {
			$( ".tdcdialog#dialog_message #message" ).text(data.errormsg);
		}
		else {
			$( ".tdcdialog#dialog_message #message" ).text("Error on saving data!");
		}
		$( ".tdcdialog#dialog_message" ).dialog("open");
	});
});
	
$("input#startofday").timepicker({
	showTime: false ,
	timeFormat: "h:mm tt",
	hourMin: 0,
	hourMax: 10,
});
$("input#averagefromdate").datepicker({
	dateFormat: "yy-mm-dd",
	yearRange: "2000:2030",
});

// add more init functions
$("#rcontainer").show(200);            

});
        
	</script>
</head>
<body>
<?php include 'header.php'; ?>
<div id="lpanel">
<?php if( !empty($support_viewtrack_logo) ){ ?>
	<img alt="index.php" src="res/side-VT-logo-clear.png" />
<?php } else if( !empty($support_fleetmonitor_logo) ){ ?>
	<img alt="index.php" src="res/side-FM-logo-clear.png" />
<?php } else { ?> 
	<img alt="index.php" src="res/side-TD-logo-clear.png" />
<?php } ?>
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><img src="res/side-dashboard-logo-green.png" /></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<?php if( !empty($enable_videos) ){ ?><li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li><?php } ?>
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<?php if( !empty($support_driveby) && ( $_SESSION['user_type'] == "operator" || $_SESSION['user'] == "admin" ) ){ ?>
	<li><a class="lmenu" href="driveby.php"><img onmouseout="this.src='res/side-driveby-logo-clear.png'" onmouseover="this.src='res/side-driveby-logo-fade.png'" src="res/side-driveby-logo-clear.png" /> </a></li>
	<?php } ?>	
	<?php if( !empty($support_emg) ) { ?>
	<li><a class="lmenu" href="emg.php"><img onmouseout="this.src='res/side-emg-logo-clear.png'" onmouseover="this.src='res/side-emg-logo-fade.png'" src="res/side-emg-logo-clear.png" /> </a></li>
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
<div class="ui-widget-content" id="userlist">
<form action="#" id="dashboardoption">
<p>Morning Report / Start Of Day: (Cut-Off Time) <input id="startofday" maxlength="8" name="startofday" type="text" value="3:00 am" /></p>

<p>Calculate Average Form Data: (YYYY-MM-DD) <input id="averagefromdate" maxlength="12" name="AverageFromDate" type="text" value="2013-01-01" /></p>

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
			<td><label><input name="stoppingalerts" type="checkbox" />Stopping Alerts</label></td>
			<td><label><input name="hardbrake" type="checkbox" />Hard Brake</label></td>
		</tr>
		<tr>
			<td><label><input name="vehiclescheckedin" type="checkbox" />Vehicles Checked-in</label></td>
			<td><label><input name="recordingalerts" type="checkbox" />Recording Alerts (V.Lost)</label></td>
			<td><label><input name="parkingalerts" type="checkbox" />Parking Alerts</label></td>
			<td><label><input name="hardturn" type="checkbox" />Hard Turn</label></td>
		</tr>
		<tr>
			<td><label><input name="vehiclesuploaded" type="checkbox" />Vehicles Uploaded</label></td>
			<td><label><input name="fanfilteralerts" type="checkbox" />High Temperature Alerts</label></td>
			<td><label><input name="speedingalerts" type="checkbox" />Speeding Alerts</label></td>
			<td><label><input name="frontimpact" type="checkbox" />Front Impact</label></td>
		</tr>
		<tr>
			<td><label><input name="hoursofvideo" type="checkbox" />Hours Of Video</label></td>
			<td><label><input name="temperaturealerts" type="checkbox" />Temperature Alerts</label></td>
			<td><label><input name="gforcealerts" type="checkbox" />G-Force Alerts (Combined)</label></td>
			<td><label><input name="rearimpact" type="checkbox" />Rear Impact</label></td>
		</tr>
		<tr>
			<td><label><input name="totalvideoclips" type="checkbox" />Total Video Clips</label></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><label><input name="sideimpact" type="checkbox" />Side Impact</label></td>
		</tr>
		<tr>
			<td><label><input name="markedvideoclips" type="checkbox" />Marked Video Clips</label></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><label><input name="bumpyride" type="checkbox" />Bumpy Ride</label></td>
		</tr>
	</tbody>
</table>
</form>

<p><button id="dashboardoptionsave">Apply Options</button> <button id="dashboardoptionreset">Reset</button></p>
</div>
</div>
<div class="tdcdialog" id="dialog_message" title="Message">
<p id="message">Are you ok?</p>

<p>&nbsp;</p>
</div>
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