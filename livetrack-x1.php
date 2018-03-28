<!DOCTYPE html>
<html>
<head><?php 
require 'config.php' ; 
require 'session.php'; 
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-06-05">		
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?> <script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script><script src="picker.js"></script>
	<style type="text/css">
	</style>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script>
	<script>
// start up 
var map  ;

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

$("#workarea").height( 680 );
var timer_resize = null ;
function trigger_resize()
{
	if( timer_resize == null ) {
		timer_resize = setTimeout(function(){
			timer_resize = null ;
			$workarea = $("#workarea");
			var nh = window.innerHeight - $workarea.offset().top -$("#footer").outerHeight() ;
			var rh = $("#rpanel").height();
			if( nh<rh ) nh = rh ;
			if( nh != $workarea.height() ) {	// height changed
				$workarea.height( nh );
			}
		},50);
	}
}

$(window).resize(function(){
	trigger_resize();
});

var mapcenter = new Microsoft.Maps.Location(35, -100);
var mapzoom = 4 ;
var mapinit=false ;
if( sessionStorage ) {
	var imap=sessionStorage.getItem('tdc_map');
	if( imap ) {
		var mi=JSON.parse(imap);
		if( mi.zoom ) {
			mapcenter = new Microsoft.Maps.Location(mi.lat, mi.lon);
			mapzoom = mi.zoom ;
			mapinit=true ;
		}
	}
}

map = new Microsoft.Maps.Map(document.getElementById("tdcmap"),
{credentials: <?php echo '"'. $map_credentials . '"'; ?> ,
center: mapcenter,
zoom: mapzoom,
enableSearchLogo: false,
enableClickableLogo: false,
mapTypeId : Microsoft.Maps.MapTypeId.road
});

Microsoft.Maps.Events.addThrottledHandler( map, "viewchangeend", function(){
	if( sessionStorage ) {
		var center = map.getCenter();
		var zoom = map.getZoom();
		var mapinit=new Object ;
		mapinit.lat=center.latitude;
		mapinit.lon=center.longitude;
		mapinit.zoom=zoom;
		var istr=JSON.stringify(mapinit);
		sessionStorage.setItem('tdc_map', istr);
	}
	trigger_loadvl();
}, 60 ) ;

function trigger_loadvl()
{
}

// show up 
$('#rcontainer').show('slow', trigger_resize );

});

</script>
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
	<!-- <li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li> -->
	<li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">LIVE TRACK</span></strong></div>

<div id="rcontainer">

<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">

<form action="" id="livetrackform">
<div><strong><span style="font-size:14px;">Select One or More Vehicles:</span></strong></div>

<div><input name="timerange" type="radio" value="0" />By Vehicle <input name="timerange" type="radio" value="1" />By Group</div>

<div style="text-align: center;"><select name="vehicles" size="8" style="width:90%;"></select><select name="groups" size="8" style="display:none;width:90%;"></select></div>

<div>&nbsp;</div>

<div style="text-align: center;"><button value="Get Current Position" /></button></div>

<div>&nbsp;</div>
</form>

</div>

<div id="workarea">
<div id="tdcmap">Bing Maps</div>
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