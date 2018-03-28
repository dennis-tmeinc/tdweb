<!DOCTYPE html>
<html>
<head><?php 
require_once 'session.php'; 
require_once 'vfile.php' ;

// remember recent page
session_save('dashboardpage', $_SERVER['REQUEST_URI'] );

if( strpos($_SERVER["SCRIPT_NAME"], 'dashboardmorning.php')>0 ) {
	$reqdate->sub(new DateInterval('P1D'));
	$day_title='Last Day';
	$title_type='Morning ' ;
}
else {
	$day_title='Today';
	$title_type='Live ' ;
}

// MySQL connection
if( $logon ) {
	@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
	$sql = "select now();" ;
	$reqdate = new DateTime() ;
	// use mysql time instead if possible
	if($result=$conn->query($sql)) {
		if( $row=$result->fetch_array() ) {
			$reqdate = new DateTime( $row[0] );
		}
		$result->free();
	}
	if( strpos($_SERVER["SCRIPT_NAME"], 'dashboardmorning.php')>0 ) {
		$reqdate->sub(new DateInterval('P1D'));
		$day_title='Last Day';
		$title_type='Morning ' ;
	}
	else {
		$day_title='Today';
		$title_type='Live ' ;
	}
include 'dashboard_data_ph1.php' ;
}
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.4/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
.sum_circle
{
background-image:url('res/big_dashboard_circles.png');
background-repeat:no-repeat;
background-position:center;
height: 72px;
font-size:36px;
text-align: center;
min-width:150px;
}

.sum_circle_green
{
color:green;
}

.sum_circle_red
{
color:red;
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
min-width:750px;
}

#vehicle_list {
border-collapse:collapse;
min-width:750px;
}

table tr
{
min-height:22px ;
}
	</style>
	<script>
// start up 
        
$(document).ready(function(){
			
// update TouchDown alert
function touchdownalert()
{
	$.getJSON("td_alert.php", function(resp){
		if( resp.res == 1 ) { 
			$("#rt_msg").empty();
			var td_alert = resp.td_alert ;
			if( td_alert.length>0 ) {
				var txt="";
				for(var i=0;i<2&&i<td_alert.length;i++) {
					if( i>0 ) txt+="\n" ;
					txt+=td_alert[i].dvr_name + " : "+td_alert[i].description ;
				}
				$("#rt_msg").text(txt);
			}
			$("#servertime").text(resp.time);
			setTimeout(touchdownalert,60000);
		}
		else {
			window.location.assign("logout.php");
		}		
	});
}
touchdownalert();
			
$("button").button();	
$(".btset").buttonset();

$(".btset input").change(function(){
   location=$(this).attr("href");
});				

$(".listtable tbody tr").filter(':odd').addClass("alt");

$("#rcontainer").show('slow');            
		
});
        
	</script>
</head>
<body>
<div id="container">
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
<p class="btset"><input href="dashboardmorning.php" id="btmorning" type="radio" /><label for="btmorning"> Morning Status Report </label> <input href="dashboardlive.php" id="btlive"  checked="checked" type="radio" /><label for="btlive"> Live Status Report </label> <input href="dashboardoption.php" id="btoption" type="radio" /><label for="btoption"> Dashboard Options </label></p>

<h4><strong><?php echo $title_type; ?>Status Report</strong></h4>

<div>
<table border="0" cellpadding="1" cellspacing="1" style="min-width: 600px;">
	<tbody>
		<tr>
			<td class="sum_circle sum_circle_green" id="v_in_service">0</td>
			<td class="sum_circle sum_circle_green" id="Vehicles_Checkedin_day">0</td>
			<td class="sum_circle sum_circle_green" id="Vehicles_Uploaded_day">0</td>
			<td class="sum_circle sum_circle_red" id="marked_events">0</td>
			<td class="sum_circle sum_circle_red" id="system_alerts">0</td>
		</tr>
		<tr>
			<td class="sum_title">Veh. In-Service</td>
			<td class="sum_title">Veh. Checked-In</td>
			<td class="sum_title">Veh. Uploaded</td>
			<td class="sum_title">Marked Events</td>
			<td class="sum_title">System Alerts</td>
		</tr>
	</tbody>
</table>
</div>

<h4><?php echo $title_type; ?>Status Summary</h4>

<div>
<table border="1" class="summarytable" id="summary_table">
	<thead>
		<tr>
			<th scope="col">&nbsp;</th>
			<th scope="col">
			<div><span style="font-size:14px;"><?php echo $day_title; ?></span></div>
			</th>
			<th scope="col">
			<div><span style="font-size:14px;">Average (day)</span></div>
			</th>
			<th scope="col">
			<div>&nbsp;</div>
			</th>
			<th scope="col">
			<div><span style="font-size:14px;"><?php echo $day_title; ?></span></div>
			</th>
			<th scope="col">
			<div><span style="font-size:14px;">Average (day)</span></div>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><span style="font-size:12px;">Operating Hours</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Operating_Hours_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Operating_Hours_avg; ?></td>
			<td><span style="font-size:12px;">Connection Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Connection_Alerts_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Connection_Alerts_avg; ?></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Distance Travelled</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Distance_Travelled_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Distance_Travelled_avg; ?></td>
			<td><span style="font-size:12px;">Camera Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Camera_Alerts_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Camera_Alerts_avg; ?></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Vehicles Checked-In</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Vehicles_Checkedin_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Vehicles_Checkedin_avg; ?></td>
			<td><span style="font-size:12px;">Recording Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Recording_Alerts_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Recording_Alerts_avg; ?></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Vehicles Uploaded</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Vehicles_Uploaded_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Vehicles_Uploaded_avg; ?></td>
			<td><span style="font-size:12px;">Fan Filter Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Fan_Filter_Alerts_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Fan_Filter_Alerts_avg; ?></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Hours Of Video</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Hours_Of_Video_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Hours_Of_Video_avg; ?></td>
			<td><span style="font-size:12px;">Idling Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Idling_Alerts_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Idling_Alerts_avg; ?></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Total Video Clips</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $Total_Video_Clips_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $Total_Video_Clips_avg; ?></td>
			<td><span style="font-size:12px;">G-Force Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);"><?php echo $GForce_Alerts_day; ?></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);"><?php echo $GForce_Alerts_avg; ?></td>
		</tr>
	</tbody>
</table>
</div>

<h4>Vehicle Status List</h4>

<div>
<table border="1" class="listtable" id="vehicle_list">
	<thead>
		<tr>
			<th><span style="font-size:14px;">Vehicle</span></th>
			<th><span style="font-size:14px;">Last Check-In</span></th>
			<th><span style="font-size:14px;">Duration</span></th>
			<th><span style="font-size:14px;">#Clips</span></th>
			<th><span style="font-size:14px;">#M.Events</span></th>
			<th><span style="font-size:14px;">Alerts</span></th>
			<th><span style="font-size:14px;">Status</span></th>
		</tr>
	</thead>
	<tbody><?php
     for( $i=0; $i<count($v_in_service) ; $i++ ) {
		echo "<td>".$v_in_service[$i]."</td>" ;
		// Last Check-in
		$sql = "SELECT de_datetime FROM dvr_event WHERE de_vehicle_name = '$v_in_service[$i]' AND de_event = 1 AND de_datetime BETWEEN '$date_begin' AND '$date_end' ORDER BY de_datetime DESC ;";
		$result = $conn->query($sql);
		$last_checkin='' ;
		if( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$last_checkin=$row[0];
		}
		echo "<td>$last_checkin</td>" ;

		// Video Clips Duration
		$sql= "SELECT sum(TimeStampDiff(SECOND, time_start, time_end)) FROM `videoclip` WHERE `vehicle_name` = '$v_in_service[$i]' AND `time_upload`  BETWEEN '$date_begin' AND '$date_end' ;";
		$result = $conn->query($sql);
		$v_duration=0;
		if( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$v_duration=$row[0];
		}
		// convert to time format
		$v_d_h = ($v_duration/3600)%48 ;
		$v_d_m = ($v_duration/60)%60 ;
		$v_d_s = $v_duration % 60 ;
		echo "<td>".$v_d_h.':'.$v_d_m.':'.$v_d_s."</td>" ;
		
	    // #Clips
		$sql= "SELECT count(*) FROM `videoclip` WHERE `vehicle_name` = '$v_in_service[$i]' AND `time_start`  BETWEEN '$date_begin' AND '$date_end' ;";
		$result = $conn->query($sql);
		$clips=0;
		if( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$clips=$row[0] ;
		}
		echo "<td>$clips</td>" ;	   
	   
		// M.Events
		$sql= "SELECT count(*) FROM `vl` WHERE vl_vehicle_name = '$v_in_service[$i]' AND vl_incident = '23' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		$result = $conn->query($sql);
		$mev=0;
		if( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$mev=$row[0] ;
		}
		echo "<td>$mev</td>" ;
       
		// Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$v_in_service[$i]' AND alert_code in (2,3,4,5) AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		$alerts=0;
		if( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$alerts=$row[0] ;
		}
		echo "<td>$alerts</td>" ;
		// Good or Bad?
		echo "<td>".($alerts>0?'<span style="color:#B22;font-size:14px;"><strong>Bad</strong></span>':'<span style="color:#0f0;font-size:14px;"><strong>Good</strong></span>')."</td>" ;
		echo "</tr>";
     }
?>
	</tbody>
</table>
</div>

<p>&nbsp;</p>

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