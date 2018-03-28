<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 

// remember recent page
session_save('dashboardpage', $_SERVER['REQUEST_URI'] );

if( strstr($_SESSION['dashboardpage'], 'dashboardmorning') ) {
	$day_title='Last Day';
	$title_type='Morning ' ;
}
else {
	$day_title='Today';
	$title_type='Live ' ;
}

?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script>
	<script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
	<script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
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

var btlive = <?php
	if( $title_type == 'Live ' ) {
		echo '1' ;
	}
	else {
		echo '0' ;
	}
?>;
if( btlive ) {
	$("#btlive").prop("checked",true);
}
else {
	$("#btmorning").prop("checked",true);
}

$(".btset input").change(function(){
   location=$(this).attr("href");
});				

$("#vehicle_list").jqGrid({        
    scroll: true,
	datatype: "local",
	height: 300,
	width: 750,
    colNames:['Vehicle','Last Check-In', 'Duration','#Clips','#M. Events', 'Alerts', 'Status'],
    colModel :[ 
      {name:'vehcile', index:'vehcile', width:180, sortable: true }, 
      {name:'checkin', index:'checkin', width:180, sortable: true }, 
      {name:'duration', index:'duration', width:80, sortable: false }, 
      {name:'clips', index:'clips', width:60, sortable: true, sorttype:"int" }, 
      {name:'mevents', index:'mevents', width:90, sortable: true, sorttype:"int"}, 
      {name:'alerts', index:'alerts', width:140, sortable: true }, 
      {name:'status', index:'status', width:80, sortable: true }
    ],
	rownumbers: true
});

function load_vehiclelist()
{
	$.getJSON("dashboardvehicles.php", function(resp){
		if( resp.res == 1 ) {
			$("#vehicle_list").jqGrid("clearGridData");
			var griddata = [] ;
			for(var i=0;i<resp.vehicles.length;i++) {
				griddata[i] = { vehcile: resp.vehicles[i][0],
						  checkin: resp.vehicles[i][1],
						  duration: resp.vehicles[i][2],
						  clips: resp.vehicles[i][3],
						  mevents: resp.vehicles[i][4],
						  alerts: resp.vehicles[i][5],
						  status: resp.vehicles[i][6] };
			}
			$("#vehicle_list").jqGrid('addRowData',1,griddata);		
		}
	});	
}

function load_dashboard()
{
	var url = ['dashboardreport.php', 'dashboardreportday.php'];
	for( var phase=0; phase<url.length; phase++) {
		$.getJSON(url[phase], function(resp){
			if( resp.res == 1 ) {
				// summary table
				for (x in resp.report) {
					$("td#"+x).text(resp.report[x]);
				}
			}
		});
	}
	load_vehiclelist();
}

load_dashboard();		
//setInterval(load_dashboard,300000);
	
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
	<li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li>
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
<input href="dashboardmorning.php" name="btset" id="btmorning"
<?php 
	if( $title_type != 'Live ' ) {
		echo ' checked="checked" ' ;
	}
?>
type="radio" /><label for="btmorning"> Morning Status Report </label> 
<input href="dashboardlive.php"    name="btset" id="btlive"  
<?php 
	if( $title_type == 'Live ' ) {
		echo ' checked="checked" ' ;
	}
?>
type="radio" /><label for="btlive"> Live Status Report </label> 
<input href="dashboardoption.php"  name="btset" id="btoption" type="radio" /><label for="btoption"> Dashboard Options </label>
</p>

<h4><strong><?php echo $title_type; ?>Status Report</strong></h4>

<div>
<table border="0" cellpadding="1" cellspacing="1" style="min-width: 600px;">
	<tbody>
		<tr>
			<td class="sum_circle sum_circle_green" id="Vehicles_In_Service">0</td>
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
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Operating_Hours_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Operating_Hours_avg" ></td>
			<td><span style="font-size:12px;">Connection Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Connection_Alerts_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Connection_Alerts_avg" ></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Distance Travelled</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Distance_Travelled_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Distance_Travelled_avg" ></td>
			<td><span style="font-size:12px;">Camera Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Camera_Alerts_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Camera_Alerts_avg" ></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Vehicles Checked-In</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Vehicles_Checkedin_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Vehicles_Checkedin_avg"></td>
			<td><span style="font-size:12px;">Recording Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Recording_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Recording_Alerts_avg"></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Vehicles Uploaded</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Vehicles_Uploaded_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Vehicles_Uploaded_avg"></td>
			<td><span style="font-size:12px;">Fan Filter Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Fan_Filter_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Fan_Filter_Alerts_avg"></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Hours Of Video</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Hours_Of_Video_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Hours_Of_Video_avg"></td>
			<td><span style="font-size:12px;">Idling Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Idling_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Idling_Alerts_avg"></td>
		</tr>
		<tr>
			<td><span style="font-size:12px;">Total Video Clips</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Total_Video_Clips_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Total_Video_Clips_avg"></td>
			<td><span style="font-size:12px;">G-Force Alerts</span></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="GForce_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="GForce_Alerts_avg"></td>
		</tr>
	</tbody>
</table>
</div>

<h4>Vehicle Status List</h4>
<div>
<table id="vehicle_list"></table> 
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