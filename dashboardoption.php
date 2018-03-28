<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 

// remember recent page
session_save('dashboardpage', $_SERVER['REQUEST_URI'] );
	
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">	
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><?php echo "<link href=\"https://code.jquery.com/ui/1.11.0/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?> <script src="https://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type="text/javascript" src="https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1"></script><script src="picker.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
	</style>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script>
	<script src="td_alert.js"></script><script>
// start up 
$(document).ready(function(){
			
$("button").button();	
$(".btset").buttonset();

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
//	$("form#dashboardoption")[0].reset();
	$.getJSON("dashboardoptionload.php", function(dashboardoption){
		// fill form fields
		var set = 0 ;
		for (var field in dashboardoption) {
			var elm=$("form#dashboardoption input[name='"+field+"']");
			if( elm.length>0 ) {
				if( elm.prop("type")=="checkbox" ) {
					elm.prop("checked", (dashboardoption[field]=='1'));
				}
				else {
					elm.val(dashboardoption[field]);
				}
				set = 1 ;
			}
		}
		if( set==0 ) {
			$("form#dashboardoption input[type='checkbox']").prop("checked",true);
		}
	});
}
dashboard_option_reload();
$("button#dashboardoptionreset").click(dashboard_option_reload);
$("button#dashboardoptionsave").click(function(){
	var fdata = $('form#dashboardoption').serializeArray() ;
	var form = new Object ;
	for( var i=0; i<fdata.length; i++ ) {
		form[ fdata[i].name ] = fdata[i].value ;
	}	
	// checkbox value ;
	$('form#dashboardoption input[type="checkbox"]').each(function(index){
		var elm=$(this);
		if( elm.prop("checked") ) {
			form[ elm.attr("name") ] = 1;
		}
		else {
			form[ elm.attr("name") ] = 0;
		}
	});
    $.getJSON("dashboardoptionsave.php", form, function(data){
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
	timeFormat: "H:mm",
	hourMin: 0,
	hourMax: 10,
});

$(".spinner").spinner();
   
// add more init functions
$("#rcontainer").show(200);            

});
        
	</script>
</head>
<body>
<div id="container"><div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel">
<?php if( empty($support_viewtrack_logo) ){ ?>
	<img alt="index.php" src="res/side-TD-logo-clear.png" />
<?php } else { ?> 
	<img alt="index.php" src="res/side-VT-logo-clear.png" />
<?php } ?>
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><img src="res/side-dashboard-logo-green.png" /></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
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

<p class="btset">
<input href="dashboardmorning.php" name="btset" id="btmorning" type="radio" /><label for="btmorning"> Morning Status Report </label> 
<input href="dashboardlive.php"    name="btset" id="btlive"    type="radio" /><label for="btlive"> Live Status Report </label> 
<input href="dashboardoption.php"  name="btset" id="btoption" checked="checked" type="radio" /><label for="btoption"> Dashboard Options </label>
</p>

<h4><strong>Dashboard Options</strong></h4>
<select id="timepicker" size="8" style="position:absolute;display:none;"><option></option> </select>

<div class="ui-widget ui-widget-content ui-corner-all">
<div class="ui-widget-content" id="userlist">
<form action="#" id="dashboardoption">
<p>Morning Report / Start Of Day: (Cut-Off Time) <input maxlength="8" id="startofday" name="tmStartOfDay" type="text" value="3:00" /></p>

<p>Calculate Average From: <input name="nAverageDuration" type="number" value="90" min="1" max="366" /> Days</p>

<p style="display:none;">Status Summary: (Check Items To Display)</p>

<table border="0" cellpadding="0" cellspacing="0" style="margin-left: 50px; display:none;">
	<tbody>
		<tr>
			<td><label><input name="bOperatingHours" type="checkbox" />Operating Hours</label></td>
			<td><label><input name="bConnectionAlerts" type="checkbox" />Connection Alerts</label></td>
			<td><label><input name="bIdlingAlerts" type="checkbox" />Idling Alerts</label></td>
		</tr>
		<tr>
			<td><label><input name="bDistanceTravelled" type="checkbox" />Distance Travelled</label></td>
			<td><label><input name="bCameraAlerts" type="checkbox" />Camera Alerts (V.Lost)</label></td>
			<td><label><input name="bGForceAlerts" type="checkbox" />G-Force Alerts (Combined)</label></td>
		</tr>
		<tr>
			<td><label><input name="bVehiclesCheckedIn" type="checkbox" />Vehicles Checked-in</label></td>
			<td><label><input name="bRecordingAlerts" type="checkbox" />Recording Alerts</label></td>
			<td><label><input name="bHoursOfVideo" type="checkbox" />Hours Of Video</label></td>
		</tr>
		<tr>
			<td><label><input name="bVehiclesUploaded" type="checkbox" />Vehicles Uploaded</label></td>
			<td><label><input name="bFanFilterAlerts" type="checkbox" />High Temperature Alerts</label></td>
			<td><label><input name="bTotalVideoClips" type="checkbox" />Total Video Clips</label></td>
		</tr>
	</tbody>
</table>
</form>

<p><button id="dashboardoptionsave">Apply Options</button><button id="dashboardoptionreset">Cancel</button></p>
</div>
</div>

<div class="tdcdialog" id="dialog_message" title="Message">
<p id="message">Are you ok?</p>

<p>&nbsp;</p>
</div>
</div>
<!-- workarea --></div>
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