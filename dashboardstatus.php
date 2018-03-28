<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 

// remember recent page
session_save('dashboardpage', $_SERVER['REQUEST_URI'] );

if( strstr($_SERVER['REQUEST_URI'], 'dashboardmorning.php') ) {
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
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.11.0/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script>
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
<script src="td_alert.js"></script><script>
// start up 
        
$(document).ready(function(){
					
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
      {name:'duration', index:'duration', width:80, sortable: true }, 
      {name:'clips', index:'clips', width:60, sortable: true, sorttype:"int" }, 
      {name:'mevents', index:'mevents', width:90, sortable: true, sorttype:"int"}, 
      {name:'alerts', index:'alerts', width:140, sortable: true }, 
      {name:'status', index:'status', width:80, sortable: true }
    ],
	rownumbers: true,
	onSelectRow: function(id){ 
		var vname = $("#vehicle_list").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}
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
					if( x.substr( 0, 5 ) == "list_" ) {
						$("#"+x).jqGrid('addRowData',1,resp.report[x]);		
					}
					else {
						$("td#"+x).text(resp.report[x]);
					}
				}
			}
		});
	}
	load_vehiclelist();
}

load_dashboard();		
//setInterval(load_dashboard,300000);

$("#mss_status").jqGrid({        
    scroll: true,
	url:'dashboardmssgrid.php',
	datatype: "json",		
	height: 300,
	width: 750,
    colNames:['MSS id','Connection', 'HDD Status','SD1 Status','SD2 Status', 'Access Point Status'],
    colModel :[ 
      {name:'mss_id', index:'mss_id', width:180 }, 
      {name:'mss_connection', index:'mss_connection', width:120 }, 
      {name:'mss_hdd', index:'mss_hdd', width:120 }, 
      {name:'mss_sd1', index:'mss_sd1', width:120 }, 
      {name:'mss_sd2', index:'mss_sd2', width:120}, 
      {name:'mss_ap', index:'mss_ap', width:120 } 
    ],
	rownumbers: true,
});

$("#list_Vehicles_In_Service").jqGrid({        
	scroll: true,
	datatype: "local",
	height: 240,
	width: 600,
	colNames:['Vehicle Name'],
	colModel :[ 
	  {name:'vehicle_name', width:180, sortable: true }
	],
	rownumbers: true,
	onSelectRow: function(id){ 
		var vname = $("#list_Vehicles_In_Service").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}
});

$("#list_Vehicles_Checkedin_day").jqGrid({        
	scroll: 1,
	url:'dashboardreportcheckingrid.php',
	datatype: "json",
	gridview: true,
	height: 240,
	width: 600,
	colNames:['Vehicle Name','Last Check-In'],
	colModel :[ 
	  {name:'de_vehicle_name', width:180, sortable: true }, 
	  {name:'de_datetime', width:180, sortable: true }
	],
	sortname: "de_datetime",
	sortorder: "desc",
	rownumbers: true,
	rownumWidth: 50,
	onSelectRow: function(id){ 
		var vname = $("#list_Vehicles_Checkedin_day").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}	
});

$("#list_Vehicles_Uploaded_day").jqGrid({        
	scroll: true,
	datatype: "local",
	height: 240,
	width: 600,
	colNames:['Vehicle Name','Upload Time'],
	colModel :[ 
	  {name:'vehicle_name', width:180, sortable: true }, 
	  {name:'time_upload', width:180, sortable: true } 
	],
	rownumbers: true,
	onSelectRow: function(id){ 
		var vname = $("#list_Vehicles_Uploaded_day").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}
});

$("#list_marked_events").jqGrid({        
	scroll: true,
	datatype: "local",
	height: 240,
	width: 600,
	colNames:['Vehicle Name','Event Time'],
	colModel :[ 
	  {name:'vl_vehicle_name', width:180, sortable: true }, 
	  {name:'vl_datetime', width:180, sortable: true } 
	],
	rownumbers: true,
	onSelectRow: function(id){ 
		var vname = $("#list_marked_events").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}
});

$("#list_system_alerts").jqGrid({        
	scroll: true,
	datatype: "local",
	height: 240,
	width: 600,
	colNames:['Vehicle Name','Description', 'Alert Code', 'Alert Time'],
	colModel :[ 
	  {name:'dvr_name', width:180, sortable: true }, 
	  {name:'description', width:300, sortable: true }, 
	  {name:'alert_code', width:100, sortable: true }, 
	  {name:'date_time', width:180, sortable: true } 
	],
	rownumbers: true,
	onSelectRow: function(id){ 
		var vname = $("#list_system_alerts").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}
});	

$("#list_solo_alerts").jqGrid({        
	scroll: true,
	url:'dashboardsoloalertsgrid.php',
	datatype: "json",	
	height: 240,
	width: 600,
	colNames:['Vehicle Name','Description', 'Alert Code', 'Alert Time'],
	colModel :[ 
	  {name:'dvr_name', width:120, sortable: false, }, 
	  {name:'description', width:300, sortable: false }, 
	  {name:'alert_code', width:130, sortable: false }, 
	  {name:'date_time', width:180, sortable: true } 
	],
	sortname: 'date_time',
    sortorder: 'desc',
	rownumbers: true,
	onSelectRow: function(id){ 
		var vname = $("#list_solo_alerts").jqGrid('getCell',id, 1) ;
		showHistory( vname );
	}
});	 

$("div#dialog_solo_alerts").dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		$("#list_solo_alerts").trigger("reloadGrid");
	},
	buttons:{
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

$("#list_alert_history").jqGrid({        
	scroll: true,
	url:'dashboardalerthistorygrid.php',
	datatype: "json",	
	height: 240,
	width: 600,
	colNames:['Vehicle Name','Description', 'Alert Code', 'Alert Time'],
	colModel :[ 
	  {name:'dvr_name', width:120, sortable: false, }, 
	  {name:'description', width:300, sortable: false }, 
	  {name:'alert_code', width:130, sortable: false }, 
	  {name:'date_time', width:180, sortable: true } 
	],
	sortname: 'date_time',
    sortorder: 'desc',
	rownumbers: true
});	  

$("div#diaglog_alert_history").dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		$("#list_alert_history").trigger("reloadGrid");
	},
	buttons:{
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

function showHistory( vname )
{
	$("#list_alert_history").setGridParam( {url: "dashboardalerthistorygrid.php?vehicle="+vname, page: 1} );
	$("div#diaglog_alert_history").dialog("option", {title:"Alert History : "+vname });
	$("div#diaglog_alert_history").dialog("open");
}


var dialogid = '';
var selectedvehicle = "";

$("div#diaglog_list").dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

$(".sum_circle").click(function(){
	dialogid = $(this).attr("id") ;
	$(".listgrid").hide();
	
	$("div.listgrid#"+dialogid).show();
	$("div#diaglog_list").dialog("option", {title:$("div.listgrid#"+dialogid).attr("title") });
	$("div#diaglog_list").dialog("open");
});

$("td.system_alert").css( 'cursor', 'pointer' );
$("td.sum_circle").css( 'cursor', 'pointer');

$("td.system_alert").click(function(e){
	$("#list_solo_alerts").setGridParam( {url: "dashboardsoloalertsgrid.php?alertcode="+$(e.target).attr("alertcode"), page: 1} );
	$("div#dialog_solo_alerts").dialog("option", {title: $(e.target).text() });
	$("div#dialog_solo_alerts").dialog("open");
});

$( "#status_type" ).buttonset();

$( "input[name='status_type']" ).change(function() {
	if( $( "input#status_simple" ).prop("checked") ) {
		$("div#summary_simple").show("slow");
		$("div#summary_mss").hide("slow");
		$("div#summary_advance").hide("slow");
	}
	else if( $( "input#status_mss" ).prop("checked") ) {
		$("div#summary_simple").hide("slow");
		$("div#summary_mss").show("slow");
		$("div#summary_advance").hide("slow");
	}	
	else if( $( "input#status_advanced" ).prop("checked") ){
		$("div#summary_simple").hide("slow");
		$("div#summary_mss").hide("slow");
		$("div#summary_advance").show("slow");
	}
}).change();

$("div#summary_mss").hide();
$("div#summary_advance").hide();
		
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
			<td class="sum_title">Vehicles In-Service</td>
			<td class="sum_title">Vehicles Checked-In</td>
			<td class="sum_title">Vehicles Uploaded</td>
			<td class="sum_title">Marked Events</td>
			<td class="sum_title">System Alerts</td>
		</tr>
	</tbody>
</table>
</div>

<div id="diaglog_list" >
<div  class="listgrid"  id="Vehicles_In_Service" title="Vehicles In-Service" >
<table id="list_Vehicles_In_Service"></table> 
</div>
<div class="listgrid" id="Vehicles_Checkedin_day" title="Vehicles Checked-In" >
<table id="list_Vehicles_Checkedin_day"></table> 
</div>
<div class="listgrid" id="Vehicles_Uploaded_day" title="Vehicles Uploaded" >
<table id="list_Vehicles_Uploaded_day"></table> 
</div>
<div class="listgrid" id="marked_events" title="Marked Events" >
<table id="list_marked_events"></table> 
</div>
<div class="listgrid"  id="system_alerts" title="System Alerts" >
<table id="list_system_alerts"></table> 
</div>
</div>
<div id="dialog_solo_alerts" title="Alerts" >
<table id="list_solo_alerts"></table> 
</div>
<div id="diaglog_alert_history">
<table id="list_alert_history"></table> 
</div>

<h4><?php echo $title_type; ?>Status Summary</h4> 

<form>
<div id="status_type">
<input type="radio" id="status_simple" name="status_type" checked="checked"/><label for="status_simple">Simple</label>
<input type="radio" id="status_mss" name="status_type" /><label for="status_mss">MSS</label>
<input type="radio" id="status_advanced" name="status_type" /><label for="status_advanced">Advanced</label>
</div>
</form>

<div id="summary_mss">
<table id="mss_status"></table> 
</div>

<div id="summary_simple">
<table border="1" class="summarytable" id="summary_table">
	<thead style="font-size:22px;">
		<tr>
			<th scope="col">&nbsp;</th>
			<th scope="col"><?php echo $day_title; ?></th>
			<th scope="col">Average (day)</th>
		</tr>
	</thead>
	<tbody style="font-size:20px;" >
		<tr>
			<td class="system_alert" alertcode="3"  >Connection Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Connection_Alerts_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Connection_Alerts_avg" ></td>
		</tr>
		<tr>
			<td class="system_alert" alertcode="4" >Camera Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Camera_Alerts_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Camera_Alerts_avg" ></td>
		</tr>
		<tr>
			<td class="system_alert" alertcode="5"  >Recording Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Recording_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Recording_Alerts_avg"></td>
		</tr>
		<tr>
			<td class="system_alert" alertcode="8" >System Reset Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="System_Reset_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="System_Reset_Alerts_avg"></td>
		</tr>
		<tr>
			<td class="system_alert" alertcode="7" >Partial Storage Failure</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Partial_Storage_Failure_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Partial_Storage_Failure_avg"></td>
		</tr>		
		<tr>
			<td class="system_alert" alertcode="11" >Panic Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Panic_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Panic_Alerts_avg"></td>
		</tr>		
	</tbody>
</table>
</div>

<div id="summary_advance">
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
			<td style="font-size:12px;">Operating Hours</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Operating_Hours_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Operating_Hours_avg" ></td>
			<td class="system_alert" alertcode="3" style="font-size:12px;">Connection Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Connection_Alerts_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Connection_Alerts_avg" ></td>
		</tr>
		<tr>
			<td style="font-size:12px;">Distance Travelled</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Distance_Travelled_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Distance_Travelled_avg" ></td>
			<td class="system_alert" alertcode="4" style="font-size:12px;">Camera Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Camera_Alerts_day" ></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Camera_Alerts_avg" ></td>
		</tr>
		<tr>
			<td style="font-size:12px;">Vehicles Checked-In</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Vehicles_Checkedin_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Vehicles_Checkedin_avg"></td>
			<td class="system_alert" alertcode="5" style="font-size:12px;">Recording Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Recording_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Recording_Alerts_avg"></td>
		</tr>
		<tr>
			<td style="font-size:12px;">Vehicles Uploaded</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Vehicles_Uploaded_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Vehicles_Uploaded_avg"></td>
			<td class="system_alert" alertcode="8" style="font-size:12px;">System Reset Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="System_Reset_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="System_Reset_Alerts_avg"></td>
		</tr>
		<tr>
			<td style="font-size:12px;">Hours Of Video</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Hours_Of_Video_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Hours_Of_Video_avg"></td>
			<td style="font-size:12px;">High Temperature Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Fan_Filter_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Fan_Filter_Alerts_avg"></td>
		</tr>
		<tr>
			<td style="font-size:12px;">Total Video Clips</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Total_Video_Clips_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Total_Video_Clips_avg"></td>
			<td style="font-size:12px;">Idling Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Idling_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Idling_Alerts_avg"></td>
		</tr>
		<tr>
			<td style="font-size:12px;">G-Force Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="GForce_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="GForce_Alerts_avg"></td>
			<td class="system_alert" alertcode="7" style="font-size:12px;">Partial Storage Failure</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Partial_Storage_Failure_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Partial_Storage_Failure_avg"></td>
		</tr>
		<tr>
			<td class="system_alert" alertcode="11" style="font-size:12px;">Panic Alerts</td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="Panic_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="Panic_Alerts_avg"></td>
			<td style="font-size:12px;"></td>
			<td style="text-align: right; background-color: rgb(204, 255, 255);" id="NNDEF_Alerts_day"></td>
			<td style="text-align: right; background-color: rgb(255, 204, 255);" id="UNDEF_Alerts_day"></td>
		</tr>		
	</tbody>
</table>

<h4>Vehicle Status List</h4>
<div>
<table id="vehicle_list"></table> 
</div>
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