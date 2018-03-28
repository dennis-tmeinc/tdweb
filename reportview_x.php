<!DOCTYPE html>
<html>
<head><?php 
require 'config.php' ; 
require 'session.php'; 
// MySQL connection
if( $logon ) {
	@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
}
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script><script src="picker.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
	</style>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script><script>
// start up 

$(document).ready(function(){
					
// update TouchDown alert
function touchdownalert()
{
	$.getJSON("td_alert.php", function(td_alert){
		$("#rt_msg").empty();
		if( td_alert.length>0 ) {
			var txt="";
			for(var i=0;i<2&&i<td_alert.length;i++) {
				if( i>0 ) txt+="\n" ;
				txt+=td_alert[i].dvr_name + " : "+td_alert[i].description ;
			}
			$("#rt_msg").text(txt);
		}
	});
}
touchdownalert();

$("button").button();
$(".btset").buttonset();

$(window).resize(function(){
	trigger_resize();
});

$("form").change(trigger_resize);

$(window).unload(function() {
	// just send it out
	$.getJSON("vlgridclean.php");
});

// to initialize map filter area
setTimeout(mapfilter,50);

// show up 
$('#rcontainer').show('slow', trigger_resize );

});

var resize_trigger = false ;
function trigger_resize()
{
	if( resize_trigger == false ) {
		resize_trigger = false ;
		setTimeout(function(){
			// resize scrollable table
			var floattr = $("tr#floattr th") ;
			
			var xtr = $("tr#xtr th");
//			var xtr = $("table#eventdetails tr td");
			if( xtr.length<1 ) {
				resize_trigger = false ;
			   return ;
			 }
			for( var i=0; i<floattr.length-1; i++) {
				$(floattr[i]).width( $(xtr[i]).width()); ;
			}
			
		   var tablebox = $(".tablebox") ;
			var nh = window.innerHeight - tablebox.offset().top -$("#footer").outerHeight() - 32 ;
			tablebox.css("max-height", nh+"px");
			if( tablebox[0].scrollHeight>nh ) {
				tablebox.addClass("listtablebottomline" );
			}
			else {
				tablebox.removeClass("listtablebottomline" );
			}

			resize_trigger = false ;
		},50);
	}
}

// return in degrees
function coor2dist( lat1, lon1, lat2, lon2 )
{
	var dx = (lon2-lon1)*Math.cos(lat1);
	var dy = (lat2-lat1);
	return Math.sqrt( dx*dx+dy*dy  ) ; 
}

function map_clear()
{
}

// Result of "Generate" button
//  parameter: 
//        mapevent: list of events
//        formdata: filter parameter
function map_generate(mapevent, formdata)
{
	// UPDATE MAP EVENT TABLE
	var summary=new Object ;
	summary.starttime="" ;
	summary.endtime="" ;
	summary.traveltime=0;
	summary.traveldistance=0; ;
	summary.stoptotal=0;
	summary.idletotal=0;
	summary.parkingtotal=0;
	summary.desstoptotal=0;
	summary.racingstart=0;
	summary.hardbrake=0;
	summary.hardturn=0;
	summary.bumpyrides=0;
	summary.frontimpacts=0;
	summary.rearimpacts=0;
	summary.sideimpacts=0;
	summary.hoursofvideo=0;
	summary.speedings=0;
	summary.events=0;
	summary.videoclips=0;
	
	if( mapevent.length>0 ) {
		summary.starttime=mapevent[0].vl_datetime ;
		summary.endtime=summary.starttime ;
	}

	var mapicons = {
	 1:'res/map_icons_stop.png',
	 2:'res/map_icons_route.png',
	 3:'res/map_icons_speed.png',		// extra speeding icon
	 4:'res/map_icons_idle.png',
	 16:'res/map_icons_g.svg',
	 17:'res/map_icons_desstop.png',
	 18:'res/map_icons_park.png',
	 23:'res/map_icons_mevent.png'
	} ;

	var html="";
//	$("table#eventdetails tbody").html(html);
	var speedLimit = formdata.speedLimit * 1.609334 ;			// convert to KMh
	for( var i=0; i<mapevent.length; i++) {
		var icon = mapicons[mapevent[i].vl_incident] ;
		if( !icon ) icon = mapicons[2] ;
		if( mapevent[i].vl_datetime < summary.starttime )
			summary.starttime=mapevent[i].vl_datetime ;
		if( mapevent[i].vl_datetime > summary.endtime )
			summary.endtime=mapevent[i].vl_datetime ;
		if( mapevent[i].vl_incident==1  ) summary.stoptotal++ ;
		if( mapevent[i].vl_incident==4  ) summary.idletotal++ ;
		if( mapevent[i].vl_incident==18 ) summary.parkingtotal++ ;
		if( mapevent[i].vl_incident==17 ) summary.desstoptotal++ ;
		if( mapevent[i].vl_incident==2 && mapevent[i].vl_speed > speedLimit ) {
			// speeding
			icon = mapicons[3] ;
			if( i>0 && mapevent[i-1].vl_speed <= speedLimit ) {
				summary.speedings++ ;
			}
		}
		if( mapevent[i].vl_incident==23 ) summary.events++ ;
		if( mapevent[i].vl_incident==16) {
			if( mapevent[i].vl_impact_x > Math.abs(formdata.gRearImpact) ) {
				summary.rearimpacts++ ;
			}
			else if(mapevent[i].vl_impact_x > Math.abs(formdata.gRacingStart) ){
				summary.racingstart++ ;
			}
			
			if( mapevent[i].vl_impact_x < -Math.abs(formdata.bFrontImpact) ) {
				summary.frontimpacts++ ;
			}
			else if(mapevent[i].vl_impact_x < -Math.abs(formdata.gHardBrake)){
				summary.hardbrake++ ;
			}

			if( Math.abs(mapevent[i].vl_impact_y) > Math.abs(formdata.gSideImpact) ) {
				summary.sideimpacts++ ;
			}
			else if(Math.abs(mapevent[i].vl_impact_y) > Math.abs(formdata.gHardTurn)){
				summary.hardturn++ ;
			}
			if( Math.abs(mapevent[i].vl_impact_z) > Math.abs(formdata.gBumpyRide) ) {
				summary.bumpyrides++ ;
			}
		}
	
		html+=
			'<tr class="'+(i%2==0?'odd':'alt')+'"><td>'+mapevent[i].vl_vehicle_name+
			"</td><td>"+mapevent[i].vl_driver_name+
			'</td><td><img width="18" height="18" src="'+icon+'" />'+
			"</td><td>"+mapevent[i].vl_datetime+
			"</td><td>"+mapevent[i].vl_time_len+
			"</td><td>"+(mapevent[i].vl_speed*0.6214).toFixed(1)+
			"</td><td>"+mapevent[i].vl_lat+","+mapevent[i].vl_lon+"</td></tr>" ;
	}
	
	// summary table
	for (x in summary) {
		$("td#"+x).text(summary[x]);
	}
	
//	$("table#eventdetails tbody").html(html);
	var tbody = $("table#eventdetails tbody")[0] ;
	try
	{
		tbody.innerHTML=html ;
	}
	catch(err)
	{
		// IE9 and older just don't work that way, let jquery deal with it (slower)
		$("table#eventdetails tbody").html(html);
	}
	$(".tablebox")[0].scrollTop=0;
	trigger_resize();
	
	// delayed for distance and duration calculation, it is slow 
	setTimeout(function(){
		var traveltime=0;
		var traveldistance=0; ;
		var pvehicle = "";
		var ptime ;
		var plat ;
		var	plon ;
		for( var i=0; i<mapevent.length; i++) {	
			// if( mapevent[i].vl_incident!=2 && mapevent[i].vl_incident!=4 ) continue ;

			var lat2 = parseFloat(mapevent[i].vl_lat);
			var lon2 = parseFloat(mapevent[i].vl_lon);
			var ntime = Date.parse(mapevent[i].vl_datetime.replace('-','/').replace('-','/'));	// Time format "2013/01/02 22:00:00"

			// calculate travel time and travel distance?
			if( pvehicle == mapevent[i].vl_vehicle_name ) {
				var dtime = ntime-ptime ;
				if( dtime < 30000 ) {	// if wait too long , consider stoped 
					traveltime+=dtime ;
					traveldistance+=coor2dist( plat, plon, lat2, lon2 );
				}
			}
			else {
				pvehicle = mapevent[i].vl_vehicle_name ;
			}
			ptime = ntime ;
			plat = lat2;
			plon = lon2;
		}
		// update data
		traveldistance = traveldistance*69.1706 ;	// convert to miles
		
		var h =Math.floor(traveltime/3600000);
		var m =Math.floor((traveltime/60000)%60)  ;
		if( m<10 ) m='0'+m ;
		var s = (traveltime/1000)%60 ;
		if( s<10 ) s='0'+s ;
		$("td#traveltime").text( ''+h+':'+m+':'+s );
		$("td#traveldistance").text(""+traveldistance.toFixed(1)+" miles");
	},500);

}


</script>
</head>
<body>
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><img src="res/side-reportview-logo-green.png" /></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<!--	<li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li> -->
	<li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">REPORT VIEW</span></strong></div>

<div id="rcontainer"><?php include "mapfilter.php" ; ?>
<div id="workarea" style="width:auto;">
<h4>Events Summary</h4>

<table align="center" cellpadding="1" cellspacing="0" class="summarytable" width="95%">
	<colgroup>
		<col style="white-space: nowrap; text-align: right;" />
		<col class="altcol" style="min-width:100px" />
		<col style="white-space: nowrap; text-align: right;" />
		<col class="altcol" style="min-width:50px" />
		<col style="white-space: nowrap; text-align: right;" />
		<col class="altcol" style="min-width:50px" />
		<col style="white-space: nowrap; text-align: right;" />
		<col class="altcol" style="min-width:50px" />
	</colgroup>
	<tbody>
		<tr>
			<td style="text-align: right;">Start Date-Time</td>
			<td id="starttime">&nbsp;</td>
			<td style="text-align: right;">Stopping Total:</td>
			<td id="stoptotal">&nbsp;</td>
			<td style="text-align: right;">Racing Starts:</td>
			<td id="racingstart">&nbsp;</td>
			<td style="text-align: right;">Front Impacts:</td>
			<td id="frontimpacts">&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align: right;">End Date-Time:</td>
			<td id="endtime">&nbsp;</td>
			<td style="text-align: right;">Idling Total:</td>
			<td id="idletotal">&nbsp;</td>
			<td style="text-align: right;">Hard Braking:</td>
			<td id="hardbrake">&nbsp;</td>
			<td style="text-align: right;">Rear Impacts:</td>
			<td id="rearimpacts">&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align: right;">Travel Time:</td>
			<td id="traveltime">&nbsp;</td>
			<td style="text-align: right;">Parking Total</td>
			<td id="parkingtotal">&nbsp;</td>
			<td style="text-align: right;">Hard Turns:</td>
			<td id="hardturn">&nbsp;</td>
			<td style="text-align: right;">Side Impacts:</td>
			<td id="sideimpacts">&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align: right;">Travel Distance:</td>
			<td id="traveldistance">&nbsp;</td>
			<td style="text-align: right;">Designated Stops:</td>
			<td id="desstoptotal">&nbsp;</td>
			<td style="text-align: right;">Bumpy rides:</td>
			<td id="bumpyrides">&nbsp;</td>
			<td style="text-align: right;">Hours of Video:</td>
			<td id="hoursofvideo">&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align: right;">&nbsp;</td>
			<td>&nbsp;</td>
			<td style="text-align: right;">Speeding Total:</td>
			<td id="speedings">&nbsp;</td>
			<td style="text-align: right;">Marked Events:</td>
			<td id="events">&nbsp;</td>
			<td style="text-align: right;">Number of Video Clips:</td>
			<td id="videoclips">&nbsp;</td>
		</tr>
	</tbody>
</table>

<h4>Activity/Event Details</h4>

<div id="tablecontainer" style="margin-right:20px; margin-left:20px;">
<div id="table_header" style="overflow-x:hidden;">
<table border="1" cellpadding="2" cellspacing="0" class="listtable" id="floathdr" width="100%">
	<tbody>
		<tr id="floattr">
			<th>Vehicle</th>
			<th>Driver</th>
			<th>Activity</th>
			<th>Date-Time</th>
			<th>Duration</th>
			<th>Speed</th>
			<th>Coordinates</th>
		</tr>
	</tbody>
</table>
</div>

<div class="tablebox" style="overflow-x:hidden;overflow-y:auto;">
<table border="1" cellpadding="2" cellspacing="0" class="listtable" id="eventdetails" width="100%">
	<tfoot>
		<tr id="xtr" style="overflow-x:hidden; visibility:hidden;">
			<th>Vehicle</th>
			<th>Driver</th>
			<th>Activity</th>
			<th>Date-Time</th>
			<th>Duration</th>
			<th>Speed</th>
			<th>Coordinates</th>
		</tr>
	</tfoot>
	<tbody>
	</tbody>
</table>
</div>
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