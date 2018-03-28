<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
session_save('lastpage', $_SERVER['REQUEST_URI'] );

// clear map filter
// session_save('mapfilter', array() );
	
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">		
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?> <script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script><script src="picker.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
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
//			var nh = window.innerHeight - $workarea.offset().top -$("#footer").outerHeight() ;
			var nh = window.innerHeight - $workarea.offset().top - 2 ;
//			var rh = $("#rpanel").height();
//			if( nh<rh ) nh = rh ;
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
	var tdsess = sessionStorage.getItem('tdsess');
	var localsession ;
	if( tdsess ) {
		localsession = JSON.parse(tdsess);
	}
	if( localsession && localsession.tdcmap ) {
		if( localsession.tdcmap.zoom ) {
			mapcenter = new Microsoft.Maps.Location(localsession.tdcmap.lat, localsession.tdcmap.lon);
			mapzoom = localsession.tdcmap.zoom ;
			mapinit=true ;
		}
	}
}

map = new Microsoft.Maps.Map(document.getElementById("tdcmap"),
{credentials: <?php echo "'$map_credentials'"; ?> ,
center: mapcenter,
zoom: mapzoom,
enableSearchLogo: false,
enableClickableLogo: false,
mapTypeId : Microsoft.Maps.MapTypeId.road
});

Microsoft.Maps.Events.addThrottledHandler( map, "viewchangeend", function(){
	loadvlmap();
	mapmove=false ;
}, 60 ) ;

Microsoft.Maps.Events.addHandler( map, "viewchangestart", function(){
	mapmove=true ;
}) ;

if( !mapinit ) {
	var map_area="<?php echo isset($map_area)?$map_area:''; ?>";
	if( map_area.length > 1 ) 
		$.ajax( {
			url : "http://dev.virtualearth.net/REST/v1/Locations",
			data : {q: map_area,o:"json",key:<?php echo "'$map_credentials'"; ?>},
			dataType : 'jsonp',	jsonp :'jsonp'
		}).done(function(location){
			if( location.statusCode == 200 && location.resourceSets[0].resources[0] && location.resourceSets[0].resources[0].confidence=="High" ) {
				var resource = location.resourceSets[0].resources[0] ;
				if( resource.geocodePoints[0].coordinates ) {
					var qzoom=11 ;		// city 
					if( resource.bbox && resource.bbox instanceof Array ) {
						var nb = Microsoft.Maps.LocationRect.fromLocations( [
							new Microsoft.Maps.Location( resource.bbox[0], resource.bbox[1] ),
							new Microsoft.Maps.Location( resource.bbox[2], resource.bbox[3] )
						] );
						var w = 60 ;
						for( var z=4; z<=18 ; z++ ) {
							if( w/2 < nb.width ) {
							    qzoom = z ;
								break;
							}
							w/=2 ;
						}
					}
					var point = location.resourceSets[0].resources[0].geocodePoints[0].coordinates ;
					map.setView({
					center: new Microsoft.Maps.Location(point[0], point[1]),
					zoom : qzoom });
				}
			}		
		});
	else 
		$.getJSON("http://freegeoip.net/json/", function(geo){
			if( geo.latitude && geo.longitude ) {
				map.setView({
					center: new Microsoft.Maps.Location(geo.latitude, geo.longitude),
					zoom : 11 });		
			}
		});
}	

function showsyncicon( mapevent )
{
	map.entities.clear();

	var pinicons = {
		1:"res/map_icons_stop.png",
		2:"route_icon.php?",
		4:"res/map_icons_idle.png",
		16:"res/map_icons_g.svg",
		17:"res/map_icons_desstop.png",
		18:"res/map_icons_park.png",
		23:"res/map_icons_mevent.png" ,
		40:"res/map_icons_driveby.png" ,
		100:"speed_icon.php?",
		101:"res/map_icons_fi.png" ,
		102:"res/map_icons_ri.png" ,
		103:"res/map_icons_si.png" ,
		104:"res/map_icons_hb.png" ,
		105:"res/map_icons_rs.png" ,
		106:"res/map_icons_ht.png" ,
		107:"res/map_icons_br.png" 
		};

	// mapevent: [id,icon,direction,lat,lon]
	// place pushpins
	var iconimg = pinicons[mapevent[1]];
	if( mapevent[1] == 2 || mapevent[1] == 100 ) {
		var direction = ((parseInt(mapevent[2])+5)/10).toFixed()*10;
		iconimg += "deg="+direction ;
	}

	var pushpinOptions = {icon:iconimg, width: 24, height: 24, anchor: new Microsoft.Maps.Point(12,12) }; 
	var pinlocation = new Microsoft.Maps.Location( mapevent[3], mapevent[4] );
	var pushpin= new Microsoft.Maps.Pushpin(pinlocation, pushpinOptions);
	map.entities.push(pushpin);
	
	map.setView( {center: pinlocation} );

}

function playsync()
{
	if( playerinsync ) {
		$.getJSON("playsync.php", function(resp){
			if( resp && resp.res && playerinsync ) {
				showsyncicon( resp.mapevent ) ;
				setTimeout( playsync, 3500 );
			}
			else {
				playerinsync=0 ;
				$("button#playsync").button( "option", "label", syncLabel1 );
			}
		});
	}
}

// player synchronizing
if( navigator.platform == 'Win32' || navigator.platform == 'Win64' ) {
	playerinsync=0 ;
	$("button#playsync").button( "option", "label", syncLabel1 );
	
	$("button#playsync").click( function(){ 
		map_clear() ;
		if( playerinsync ) {
			playerinsync=0 ;
			$("button#playsync").button( "option", "label", syncLabel1 );
		}
		else {
			playerinsync=1 ;
			$("button#playsync").button( "option", "label", syncLabel0 );
			playsync();
		}
	}) ;
<?php if( empty( $_SESSION['playsync'] ) ) { ?>
	$("button#playsync").hide();	
<?php } ?>
<?php if( !empty($_REQUEST['sync']) ) { ?>
	setTimeout( function(){
		$("button#playsync").click();
	}, 3000 ) ;
<?php } ?>	
}
else {
	$("button#playsync").hide();
}

// show up 
$('#rcontainer').show('slow', trigger_resize );

});

// player sync variable
var playerinsync=0 ;
var syncLabel0 = "Stop Sync" ;
var syncLabel1 = "Player Sync" ;

// zone changed
function map_zonechanged( zone )
{
	if( zone == 'User Define' || zone == 'Current Map' || zone == 'No Restriction' ) {
		return ;
	}
	else if( zone == "Default Area" ) {
		$.getJSON("mapquery.php", function(resp){
			if( resp.res && resp.map.bbox.length >= 4 ) {
				map.setView({ bounds: Microsoft.Maps.LocationRect.fromLocations( [
					new Microsoft.Maps.Location( resp.map.bbox[0], resp.map.bbox[1] ),
					new Microsoft.Maps.Location( resp.map.bbox[2], resp.map.bbox[3] )
				] )});
			}
		});
	}
	else 
		$.getJSON("zonelist.php?name="+zone, function(resp){
			if( resp.res && resp.zonelist.length>0) {
				if( resp.zonelist[0].top==resp.zonelist[0].bottom || resp.zonelist[0].right==resp.zonelist[0].left ) {
					return ;
				}
				map.setView({ bounds: Microsoft.Maps.LocationRect.fromLocations( [
					new Microsoft.Maps.Location( resp.zonelist[0].top, resp.zonelist[0].left ),
					new Microsoft.Maps.Location( resp.zonelist[0].bottom, resp.zonelist[0].right )
				] )});
			}
		});
}

var map_search = false ;
function map_clear()
{
	map_search = false ;
	map.entities.clear();
}

var mapmove = false ;
var gendelay=40;

function map_generate(mapevent, formdata)
{
	// stop player sync (v3.0)
	playerinsync=0 ;
	$("button#playsync").button( "option", "label", syncLabel1 );
			
	if( mapevent.res==1 && mapevent.zone && mapevent.zone.north != null ) {
		if( formdata.zoneName != "Current Map" ) {	// don't change view on 'User Defined Zone'
			if( mapevent.zone.north == mapevent.zone.south ) {
				mapevent.zone.north=parseFloat(mapevent.zone.north)+0.003 ;
				mapevent.zone.south=parseFloat(mapevent.zone.south)-0.003 ;
			}
			map.setView({ bounds: Microsoft.Maps.LocationRect.fromLocations( [
				new Microsoft.Maps.Location( mapevent.zone.north, mapevent.zone.west ),
				new Microsoft.Maps.Location( mapevent.zone.south, mapevent.zone.east  )
			] )});
		}
		map_search = formdata ;
		setTimeout(function(){
			if( !mapmove ) {
				loadvlmap();
			}
		},gendelay);
	}
	else {
		map_clear();
	}
}

var vlmap_serial = 1;

function loadvlmap()
{
    if( !map_search ) return ;
    var lrect = map.getBounds();
	var fdata = new Object ;
	fdata.east = lrect.getEast().toFixed(6);
	fdata.west = lrect.getWest().toFixed(6);
	fdata.north = lrect.getNorth().toFixed(6);
	fdata.south = lrect.getSouth().toFixed(6);
	fdata.width = map.getWidth();
	fdata.height = map.getHeight();
	fdata.ts=new Date().getTime();
	fdata.serial=++vlmap_serial;
	$.getJSON("vlmap.php", fdata, function(resp){
		if( resp.res == 1 && resp.serial>=vlmap_serial ) {
			// map events (icons) successfully loaded
			mapevent = resp.mapevent ;
			map.entities.clear();
			var i;
			var len=mapevent.length ;
			if(len<1)
				return ;

			var speedLimit = map_search.speedLimit * 1.609334 ;			// convert to KMh
			var save = new Object ;		// saved value for reducing displayed event ;
			var eventInfobox = new Microsoft.Maps.Infobox(map.getCenter(), {visible:false,showPointer:true,showCloseButton:true} );    
			map.entities.push(eventInfobox);
			var pinicons = {
				1:"res/map_icons_stop.png",
				2:"route_icon.php?",
				4:"res/map_icons_idle.png",
				16:"res/map_icons_g.svg",
				17:"res/map_icons_desstop.png",
				18:"res/map_icons_park.png",
				23:"res/map_icons_mevent.png" ,
				40:"res/map_icons_driveby.png" ,
				100:"speed_icon.php?",
				101:"res/map_icons_fi.png" ,
				102:"res/map_icons_ri.png" ,
				103:"res/map_icons_si.png" ,
				104:"res/map_icons_hb.png" ,
				105:"res/map_icons_rs.png" ,
				106:"res/map_icons_ht.png" ,
				107:"res/map_icons_br.png" 
				};
	
			for(i=0;i<len;i++) {
				// mapevent: [id,icon,direction,lat,lon]
				// place pushpins
				var iconimg = pinicons[mapevent[i][1]];
				if( mapevent[i][1] == 2 || mapevent[i][1] == 100 ) {
					var direction = ((parseInt(mapevent[i][2])+5)/10).toFixed()*10;
					iconimg += "deg="+direction ;
				}

				var pushpinOptions = {icon:iconimg, width: 24, height: 24, anchor: new Microsoft.Maps.Point(12,12), text: mapevent[i][0], textOffset: new Microsoft.Maps.Point(50, 50) }; 
				var pinlocation = new Microsoft.Maps.Location( mapevent[i][3], mapevent[i][4] );
				var pushpin= new Microsoft.Maps.Pushpin(pinlocation, pushpinOptions);
				Microsoft.Maps.Events.addThrottledHandler(pushpin, 'mouseover', function(e){
					var vl_id=parseInt(e.target.getText()); 
					// e.target.setOptions( {zIndex: 10 } );		// to prevent Infobox flashing
					var loc=e.target.getLocation() ;
					eventInfobox.setLocation( loc );
					var icon = e.target.getIcon() ;
					eventInfobox.setOptions( { title:'', description:"Loading...", actions: [], visible: true,  zIndex: 10 } );
					$.getJSON("vllist.php?vl_id="+vl_id, function(v){
						if( v.res == 1 && v.vl.vl_id == vl_id ) {
							var ititle = '<img src="'+icon+'" /> '+v.vl.vl_vehicle_name ;
 							var desc = 'Event Time: '+v.vl.vl_datetime ;
							if( v.vl.vl_speed>0 ) {
								desc += "<br/>Speed: "+(v.vl.vl_speed/1.609334).toFixed(1) ;
							}
							if( v.vl.vl_time_len>0 ) {
								var h = Math.floor(v.vl.vl_time_len/3600);
								var m = Math.floor(v.vl.vl_time_len%3600/60) ;
								if( m<10 ) m='0'+m ;
								var s = Math.floor(v.vl.vl_time_len%60) ;
								if( s<10 ) s='0'+s ;
								desc += "<br/>Duration: "+h+':'+m+':'+s;
							}
							var iheight=130 ;
							if( v.vl.vl_impact_x != 0 || v.vl.vl_impact_y != 0 || v.vl.vl_impact_z != 0 ) {
								desc += "<br/>X: "+v.vl.vl_impact_x +
										" Y: "+v.vl.vl_impact_y +
										" Z: "+v.vl.vl_impact_z ;
								iheight = 145 ;
							}
							function iplayvideo() 
							{ 
								$("#formplayvideo input[name='vehicle_name']").val(v.vl.vl_vehicle_name);
								$("#formplayvideo input[name='playtime']").val(v.vl.vl_datetime);
								$('#formplayvideo').submit();
								$("button#playsync").show();	
							} 
							var iaction = [] ;
							if( v.vl.video == 1 && (navigator.platform == 'Win32' || navigator.platform == 'Win64') ) {
								iaction = [{label: 'Play Video', eventHandler: iplayvideo}] ;
							}
							eventInfobox.setOptions( { title:ititle, description: desc, actions: iaction, height: iheight,  zIndex: 10 } );
						}
					});
				}, 100 );  

				map.entities.push(pushpin);
			}
		}
	});
}

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
	<li><img src="res/side-mapview-logo-green.png" /></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">MAP VIEW</span></strong>

<button id="playsync">Player Sync</button>

</div>

<div id="rcontainer">

<?php include "mapfilter.php" ; ?>

<div id="workarea">
<div id="tdcmap">Bing Maps</div>
</div>

<form id="formplayvideo" enctype="application/x-www-form-urlencoded" method="get" action="playvideo.php" target="_blank" >
<input name="vehicle_name" type="hidden"  />
<input name="playtime" type="hidden"  />
</form>

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