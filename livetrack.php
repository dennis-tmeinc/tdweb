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
<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type="text/javascript" src="https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1"></script><script src="picker.js"></script>
<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
	select#webplay_camera {
	min-width: 100px ;
    border-radius: 4px;
    box-shadow: 1px 1px 5px #cfcfcf inset;
    border: 1px solid #cfcfcf;
    vertical-align: middle;
	}
</style>
<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script><script src="jq/live.js"></script>
<script src="td_alert.js"></script><script>
// start up 
var map  ;

$(document).ready(function(){

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

// load avl server
$.getJSON("avlload.php", function(resp){
	if( resp.res == 1 ) { 
		$("input#AVLServer").val(resp.avl.avlServer);
		$("input#avlpasswordlen").val(resp.avl.passlen);
	}
});

// init avl server dialog
$( ".tdcdialog#dialog_avlserver" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		$("#dialog_avlserver input[name='avlServer']").val( $("input#AVLServer").val() );
		var plen = $("input#avlpasswordlen").val() ;
		var pss = "" ;
		for( var i=0; i<plen; i++) {
			pss+="\u2022" ;
		}
		$("#dialog_avlserver input[name='avlPassword']").val(pss);
	},	
	buttons:{
		"OK": function() {
			var param=new Object ;
			param.avlServer = $("#dialog_avlserver input[name='avlServer']").val() ;
			// check if password changed
			var plen = $("input#avlpasswordlen").val() ;
			var pss = "" ;
			for( var i=0; i<plen; i++) {
				pss+="\u2022" ;
			}
			var pass = $("#dialog_avlserver input[name='avlPassword']").val() ;
			if( pass != pss ) {
				param.avlPassword = pass ;
			}
			$.getJSON("avlsave.php", param , function(resp){
				if( resp.res == 1 ) {
					$("input#AVLServer").val($("#dialog_avlserver input[name='avlServer']").val());
				}
				else {
					alert(resp.errormsg);
				}
			});
			$( this ).dialog( "close" );
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$( "button#btavlserver").click(function(){
	$( ".tdcdialog#dialog_avlserver" ).dialog("open");
});

// dvr list
var vltlist = {} ;

// avl detail dialog
$( ".tdcdialog#dialog_vehicle_detail" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
	},	
	buttons:{
		"OK": function() {
			$( this ).dialog( "close" );
		}
	}
});

// display vlt vehicle detail
$("select[name='vltvehicle']").dblclick(function(){
	var v = $("select[name='vltvehicle']").val();
	var dvrdetail = vltlist[v] ;
	if( dvrdetail ) {
		var dvrhtml = "" ;
		for ( var x in dvrdetail )
		{
			dvrhtml += "<tr><td style=\"text-align: right;\">" + x + " : </td><td>" + dvrdetail[x] + "</td></tr>" ;
		}
		$("#dvrinfo").html( dvrhtml );
		$( ".tdcdialog#dialog_vehicle_detail" ).dialog("open");
	}
});

// select vltpage ;
var hexch = "0123456789abcdefghijklmnopqrstuvwxyz" ;
var vltparam = {} ;
vltparam.vltserial = 100 ;
vltparam.vltpage = '' ;
for( var i=0; i<6; i++ ) {
  vltparam.vltpage += hexch.charAt(Math.random()*36);
}

// saved sensor names, to be used by sensor pop-up balloon
var vltsensor = [] ;
var vltsensor_reload = 0 ;
function load_sensors()
{
	$.getJSON("vltsensorload.php", function(resp){
		if( resp.res==1 ) {
			vltsensor = resp.vltsensor ; 
			vltsensor_reload = 1 ;
		}
	});
}
load_sensors();

var vlt_pins = {} ;

// avlp : position message, clean: clean same type of pushpin
function showpin( avlp, id, iconimg, clean ) 
{
	if( avlp.pos2 ) {
		avlp.pos = avlp.pos2;
		avlp.utc = true ;
	}
	if( avlp.pos.length>3 ) {

		var did = id.substring(4);	// dvrid
		
		// remove old pin/infobox
		if( vlt_pins[did] ) {
			if( vlt_pins[did].vpushpin ) {
				map.entities.remove(vlt_pins[did].vpushpin);
				delete vlt_pins[did].vpushpin ;
			}
			if( vlt_pins[did].vinfobox ) {
				map.entities.remove(vlt_pins[did].vinfobox);
				delete vlt_pins[did].vinfobox ;
			}
		}
			
		var e,i ;
		
		// insert gps location to map
		// resp.avlp.pos format: YYMMDDhhmmss,43.641988N079.672085W0.0D134.05
		var pvlp_pos = avlp.pos.split(',');
		var pos = new Object ;
		pos.lat = parseFloat( pvlp_pos[1].substr(0,9) );
		if( pvlp_pos[1].substr(9,1) == 'S' ) {
			pos.lat = - pos.lat ;
		}
		pos.lon = parseFloat( pvlp_pos[1].substr(10,10) );
		if( pvlp_pos[1].substr(20,1) == 'W' ) {
			pos.lon = - pos.lon ;
		}
		var sp_di = pvlp_pos[1].substr(21).split('D') ;
		pos.speed = parseFloat( sp_di[0] );
		if( iconimg == "route_icon.php" ) {
			if( pos.speed > 0.5 ) {
				pos.heading = parseFloat( sp_di[1] );
				var direction = ((parseInt( pos.heading )+5)/10).toFixed()*10;
				iconimg = "route_icon.php?deg="+direction ;
			}
			else {
				iconimg = "res/map_icons_stop.png" ;
				if( avlp.di ) {
					for( var idx = 0 ; idx < vltsensor.length; idx++ ) {
						if( vltsensor[idx].sensor_name && vltsensor[idx].sensor_name.toUpperCase() == "STOP ARM ON" ) {
							var di = parseInt( avlp.di, 16 )  & (1<<(idx/2)) ;
							var h = idx%2 ;
							if( ( di && h ) || ( di==0 && h==0 ) ) {
								iconimg = "res/map_icons_desstop.png" ;
							}
							break ;
						}
					}
				}
			}
		}
		
		var pinlocation = new Microsoft.Maps.Location( pos.lat, pos.lon );
		var newview = false ;
		var view = new Object ;

<?php if( !empty($livetrack_autozoomin) ) { ?>
		var xzoom = map.getZoom();
		var nzoom = <?php echo $livetrack_autozoomin; ?> ;
		if( avlp.cmd == 27 && nzoom > xzoom ) {
			view.zoom = nzoom ;
			view.center = pinlocation ;		
			newview = true ;			
		}
<?php  } ?>	
		if( !newview ) {
			var bounds=map.getBounds(); 
			bounds.width = bounds.width * 0.9 ;
			bounds.height = bounds.height * 0.9 ;
			if( !bounds.contains( pinlocation ) ) {
				view.center = pinlocation ;
				newview = true ;
			}
		}
		if( newview )
			map.setView( view );

		var pushpin= new Microsoft.Maps.Pushpin( pinlocation,
			{	icon:iconimg, width: 24, height: 24, 
				anchor: new Microsoft.Maps.Point(12,12)
			});
		map.entities.push(pushpin);
		
		var infobox = new Microsoft.Maps.Infobox( pushpin.getLocation(), 
			{ id: did, 
			showCloseButton: false, 
			title: did, 
			height: 30,
			width: 14 * (did.length) + 8  
			}
		);
		map.entities.push( infobox );
		
		vlt_pins[did] = { vavlp: avlp, vicon: iconimg, vpushpin: pushpin, vinfobox: infobox };
		
		function pushpin_info(e){
			var avlp ;
			if( vlt_pins[did] ) {
				avlp = vlt_pins[did].vavlp ;
			}
			else {
				return ;
			}

			// remove old infobox
			if( vlt_pins[did].vinfobox ) {
				if( vlt_pins[did].vinfobox.getHeight()>90 ) {		// full info already shown
				    if( !vlt_pins[did].vinfobox.getVisible() ) {
						vlt_pins[did].vinfobox.setOptions( {visible:true} );
					}
					return ;
				}
				map.entities.remove(vlt_pins[did].vinfobox);  
				delete vlt_pins[did].vinfobox ;
			}

			var infotitle =   '<img src="'+e.target.getIcon()+'" /> ' + did ;
			
			// date & time
			var dstr = '20'+avlp.pos.substr(0,2)+'-'+avlp.pos.substr(2,2)+'-'+avlp.pos.substr(4,2)+'T'+avlp.pos.substr(6,2)+':'+avlp.pos.substr(8,2)+':'+avlp.pos.substr(10,2)+'Z' ;
			var dt = new Date(dstr);
			var dyear = dt.getFullYear() ;
			var dmon = dt.getMonth() + 1 ;
			if( dmon<10 ) dmon = "0" + dmon ;
			var ddate = dt.getDate() ;
			if( ddate < 10 ) ddate = "0" + ddate ;
			var dhour = dt.getHours() ;
			if( dhour < 10 ) dhour = "0" + dhour ;
			var dmin = dt.getMinutes() ;
			if( dmin < 10 ) dmin = "0" + dmin ;
			var dsec = dt.getSeconds() ;
			if( dsec < 10 ) dsec = "0" + dsec ;
			var desc = dyear + "-" + dmon + "-" + ddate + ' ' + dhour + ":" + dmin + ":" + dsec ;
			if( avlp.utc ) {
				desc += " UTC" ;
			}
			var lines = 1 ;
			
			// speed
			var speed = parseFloat( avlp.pos.substr(34) ) ;
			if( speed > 0.5 ) {
				// convert km to mph
				speed = speed / 1.609344 ;
				
				desc += "<br/>Speed: "+speed.toFixed(1);
				lines++;
			}

			// di mask
			if( avlp.di && avlp.mask ) {
				var mask = parseInt( avlp.mask, 16 );
				var bm = 1 ;
				var idx = 0 ;
				for( idx = 0 ; idx<32; idx++ ) {
					if( bm&mask ) break;
					bm <<= 1 ;
				}
				idx *= 2 ;
				var v = bm & parseInt( avlp.di, 16 ) ;
				if( v ) {
					idx += 1 ;
				}
				if( idx < vltsensor.length ) {
					var sname = vltsensor[idx].sensor_name ;
					if( sname.length <= 0 ) {
						sname = vltsensor[i].sensor_index ;
					}
					desc += "<br/>Sensor: "+sname;
					lines ++ ;
				}
			}
			
			// temperature
			if( avlp.temp ) {
				// C to F
				var temperatureF = avlp.temp * 9 /5 + 32 ;
				desc += "<br/>Temperature: "+temperatureF.toFixed(0);
				lines ++ ;
			}

			if( avlp.idle ) {
				desc += "<br/>Idle: "+avlp.idle+" Seconds" ;
				lines ++ ;
			}
			
			if( avlp.fb && avlp.lr && avlp.ud ) {
				desc += "<br/>Impact: "+avlp.fb + " - " + avlp.lr + " - " + avlp.ud ;
				lines ++ ;
			}

			if( typeof avlp.ign != 'undefined' ) {
				desc += "<br/>Ignition: " + ( (avlp.ign==1)?"ON":"OFF") ;
				lines ++ ;
			}
			
			var iheight=92+18*lines  ;
			
			function removepin()
			{ 
				// remove infobox and this pushpin
				if( vlt_pins[did] ) {
					if( vlt_pins[did].vinfobox ) {
						map.entities.remove( vlt_pins[did].vinfobox );  
					}
					if( vlt_pins[did].vpushpin ) {
						map.entities.remove( vlt_pins[did].vpushpin );  
					}
					delete vlt_pins[did] ;
				}
			}
			var iaction = [{label: 'Remove', eventHandler: removepin}] ;
			vlt_pins[did].vinfobox = new Microsoft.Maps.Infobox(e.target.getLocation(), {
				showPointer:true,
				showCloseButton:true,
				id: did,
				title:infotitle, description: desc, height: iheight,  zIndex: 10, actions: iaction
				}) ;
			
			map.entities.push( vlt_pins[did].vinfobox );
		}		

		Microsoft.Maps.Events.addThrottledHandler(pushpin, 'mouseover', pushpin_info, 100 );  
		Microsoft.Maps.Events.addHandler(pushpin, 'click', pushpin_info);  
		
	}
}

// update vlt dvr list table
function update_dvrlist()
{
	var options='';
	for( var id in vltlist ) {
		var name = vltlist[id].dvrid ;
		if(  vltlist[id].status == 'standby'  ) {
			options += "<option style=\"color:gray;background-image:url(res/standby.png);background-repeat: no-repeat;background-position: right;\" >"+name+"</option>" ;
		}
		else {
			options += "<option>"+name+"</option>" ;
		}
	}
	$("select[name='vltvehicle']").html( options );	
}

function tdwebc_message( tdwebc )
{
	if( tdwebc instanceof Array )
	for( var i=0;i<tdwebc.length;i++) 
	if( tdwebc[i].avlp && tdwebc[i].command ) {
		var cmd = tdwebc[i].command ;
		var avlp = tdwebc[i].avlp ;
		avlp.cmd = cmd ;
		if( cmd == "23" ) {	//AVL_DVR_LIST(23)
			if( avlp.list && avlp.list.item ) {
				vltlist = {} ;
				var dvrlist = avlp.list.item ;
				if( dvrlist instanceof Array  ) {
					for( var i=0; i<dvrlist.length; i++ ) {
						if( dvrlist[i].dvrid ) {
							vltlist[dvrlist[i].dvrid] = dvrlist[i] ;
						}
					}
				}
				else if( dvrlist.dvrid ) {
					vltlist[dvrlist.dvrid] = dvrlist ;
				}
			}
			// update vlt list
			update_dvrlist();
		}
		else if( cmd == '20' ) {	// AVL_IP_REPORT(20)
			if( !avlp.ip || avlp.ip == '0.0.0.0' ) {
				delete vltlist[ avlp.dvrid ] ;
			}
			else {
				vltlist[ avlp.dvrid ] = avlp ;
			}
			// update vlt list
			update_dvrlist();
		}
		else if( cmd == '27' || cmd == '28' ) {	//AVL_CURRENT_DATA_QUERY(27) AVL_CURRENT_DATA_REPORT(28)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "CD__"+tdwebc[i].source.dvrs.dvr	, "route_icon.php", true );
			}
		}
		else if( cmd == "32" ) {      // AVL_DI_EVENT(32)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "DI__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_sensor.png", false );
			}
		}
		else if( cmd == "22" ) {      // AVL_EVENT_REPORT(22)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "EV__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_mevent.png", false );
			}		
		}	
		else if( cmd == "33" ) {      // AVL_SYSTEMP_EVENT(33) 
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "TM__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_temperature.png", false );
			}
		}
		else if( cmd == "34" ) {      // AVL_IDLE_EVENT(34)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "ID__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_idle.png", true );
			}
		}
		else if( cmd == "39" ) { 	// AVL_IGNITION_EVENT(39), icon missing, use sensor icon and park icon instead
			if( avlp.ign == 1 ) {
				showpin( avlp, "IG__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_ignon.png", false );
			}
			else {
				showpin( avlp, "IG__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_park.png", false );
			}		
		}
		else if( cmd == "43" ) { 	// DRIVEBY(43)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "DB__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_driveby.png", false );
			}		
		}		
		else if( cmd == "31" ) {      // AVL_GFORCE_EVENT(31)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "PB__"+tdwebc[i].source.dvrs.dvr	, "res/map_icons_gforce.png", false );
			}
		}
		else if( cmd == "38" ) {      // AAVL_GEOFENCE_RECT_EVENT(38)
			if( tdwebc[i].source.dvrs && tdwebc[i].source.dvrs.dvr ) {
				showpin( avlp, "GO__"+tdwebc[i].source.dvrs.dvr	, "route_icon.php", false );

				// to draw a rectangle also
				dir = 1, 2, 3
				var top = parseFloat(avlp.lat);
				var left = parseFloat(avlp.lon);
				var bottom = parseFloat(avlp.lat2);
				var right = parseFloat(avlp.lon2);
				var locs=[
					new Microsoft.Maps.Location( top, left ),
					new Microsoft.Maps.Location( top, right ),
					new Microsoft.Maps.Location( bottom, right ),
					new Microsoft.Maps.Location( bottom, left ),
					new Microsoft.Maps.Location( top, left )
				] ;
				var locrect=Microsoft.Maps.LocationRect.fromLocations( locs );
				map.setView({ bounds: locrect});
				var polycolor = geofence_color['Disable'] ;
				if( avlp.dir == 1 ) polycolor = geofence_color['In'] ;
				else if( avlp.dir == 2 ) polycolor = geofence_color['Out'] ;
				else if( avlp.dir == 3 ) polycolor = geofence_color['Both'] ;
				var options = { fillColor: polycolor, strokeColor: new Microsoft.Maps.Color( 31, 55, 61,200 ), strokeThickness: 5 }; 
				var polygon = new Microsoft.Maps.Polygon(locs,	options); 		
				map.entities.push(polygon);
			}	
		}		
				
		else {
			// alert( "Unknown message from AVL : "+ cmd );
		}
	}
}
			
// persist query
function pquery()
{
	vltparam.vltserial++;
	$.ajax({ dataType: "json", url: "vltreport.php", data: vltparam, timeout: 3600000, success: function(resp){
		if( resp.res && resp.tdwebc ) {
			tdwebc_message( resp.tdwebc );
		}		
		if( resp.session && resp.session == 'e' ) {	// session ended
			// logout
			window.location.assign("logout.php");
		}
		else {
			pquery();
		}
	}});
}
			
// load vlt vehicle list
vltparam.vltserial++;
$.getJSON("vltdvrlist.php", vltparam, function(v){
	if( v.res ) {
		if( v.tdwebc ) {
			tdwebc_message( v.tdwebc );
		}
		pquery();
	}		
});

// getcurrentpos button
$( "button[name='getcurrentpos']" ).click(function(e){
	e.preventDefault();
	var v = $("select[name='vltvehicle']").val();
	if( v ) {
		var dvrdetail = vltlist[v] ;
		if( dvrdetail && dvrdetail.status == 'standby' ) {
			alert( "Vehicle " + dvrdetail.dvrid + " is in standby mode." );
			return ;
		}
		
		vltparam.vltserial++;
		var form = vltparam ;
		form['dvrid[]'] = v ;
		$.getJSON("vltgetlocation.php", form, function(resp){
			if( resp.res && resp.tdwebc ) {
				tdwebc_message( resp.tdwebc );
			}
		});
	}
});

// Clear All Icons button
$( "button[name='clearallicons']" ).click(function(e){
	map.entities.clear();
	vlt_pins = {} ;
});

// Sensor Config Dialog
$( ".tdcdialog#dialog_sensorconfig" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		var tb = "";
		for( var i=0 ; i<vltsensor.length ; i++ ) {
			tb += '<tr><td>'+vltsensor[i].sensor_index+'</td><td><input name="' + vltsensor[i].sensor_index + '" value="' + vltsensor[i].sensor_name + '" type="text"/></td></tr>' ;
		}
		$("table#vltsensortable").html(tb);
	},	
	buttons:{
		"OK": function() {
			$.getJSON("vltsensorsave.php", $( "form[name='sensorconfig']" ).serializeArray(), function(resp){
				if( resp.res==1 ) {
					$( ".tdcdialog#dialog_sensorconfig" ).dialog( "close" );				
					load_sensors();	
				}
				else {
					$( ".tdcdialog#dialog_sensorconfig" ).dialog( "close" );				
				}
			});
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$( "button[name='sensorconfig']" ).click(function(){
	$( ".tdcdialog#dialog_sensorconfig" ).dialog("open");
});

$( "form#vltreportconfig input[name='vlt_geo']" ).change(function() {
alert( "Handler for .change() called." );
});

// Report Configuration Dialog
$( ".tdcdialog#dialog_reportconfig" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	create: function( event, ui ) {
		// prevent submit the form
		$("form#vltreportconfig").submit( function(evt){ evt.preventDefault(); });
	
		$(".tdcdialog#dialog_reportconfig input[name='vlt_select_type']").change(function(e){
			$("form#vltreportconfig select#vlt_vehicle_list").html("");		
			if( $("form#vltreportconfig input#vlt_select_type1").prop("checked") ) {
				// vehicle , copy from live page
				$("form#vltreportconfig select#vlt_vehicle_list").html(
					$("select[name='vltvehicle']").html() );
			}
			else {
				// group, load
				$.getJSON("grouplist.php?nameonly=y", function(resp){
					var gs="";
					for(var i=0; i<resp.length; i++) {
						gs+="<option>"+resp[i].name+"</option>";
					}
					$("form#vltreportconfig select#vlt_vehicle_list").html(gs);
				});
			}
		});
	
		//save default button
		$(".tdcdialog#dialog_reportconfig button[name='vltsavedefault']").click(function(){
			var param = $("form#vltreportconfig").serializeArray();
			$.getJSON("vltdefaultsave.php?default="+$(this).val(), param, function(resp){
				if( resp.res == 1 ) {
					alert("Configuration saved!");
				}
			});
		});

		// load default button
		$(".tdcdialog#dialog_reportconfig button[name='vltloaddefault']").click(function(){
			$.getJSON("vltdefaultload.php?default="+$(this).val(), function(resp){
				// save veh selection
				var vg = $("form#vltreportconfig input#vlt_select_type2").prop("checked");
				var vs = $("form#vltreportconfig select#vlt_vehicle_list").val();
				$("form#vltreportconfig")[0].reset();
				// restore veh selection
				$("form#vltreportconfig input#vlt_select_type2").prop("checked",vg);
				$("form#vltreportconfig select#vlt_vehicle_list").val(vs);
				if( resp.res == 1 ) {
					for (var field in resp.vltconfig ) {
						var elm=$("form#vltreportconfig [name='"+field+"']");
						if( elm.length>0 ) {
							if( elm.prop("type")=="checkbox" ) {
								elm.prop("checked", (resp.vltconfig[field]=='1')||(resp.vltconfig[field]=='y'));
							}
							else if( elm.prop("type")=="radio" ) {
								elm.filter("[value='"+resp.vltconfig[field]+"']").prop("checked",true);
							}
							else {
								elm.val(resp.vltconfig[field]);
							}
						}
					}				
				}
			});
		});
		
		// dbl click vehicle to load applied cfg
		$(".tdcdialog#dialog_reportconfig select#vlt_vehicle_list").dblclick(function(){
			var v = $(".tdcdialog#dialog_reportconfig select#vlt_vehicle_list").val();
			if( v.length>0 && $("input#vlt_select_type1").prop("checked") ) {		
				var form = vltparam ;
				form.vltvehicle = v[0] ;
				$.getJSON("vltreportconfigload.php", form, function(resp){
					if( resp.res == 1 ) {
						for (var field in resp.vltconfig ) {
							var elm=$("form#vltreportconfig [name='"+field+"']");
							if( elm.length>0 ) {
								if( elm.prop("type")=="checkbox" ) {
									elm.prop("checked", (resp.vltconfig[field]=='1')||(resp.vltconfig[field]=='y'));
								}
								else if( elm.prop("type")=="radio" ) {
									elm.filter("[value='"+resp.vltconfig[field]+"']").prop("checked",true);
								}
								else {
									elm.val(resp.vltconfig[field]);
								}
							}
						}				
					}
				});
			}
		});
		
	},
	open: function( event, ui ) {
//		$("form#vltreportconfig")[0].reset();
//		$("form#vltreportconfig input[name='vlt_geo']").val("");

		// vehicle , copy from live page
		$("form#vltreportconfig select#vlt_vehicle_list").html(
			$("select[name='vltvehicle']").html() );
		
		// load Alarm (sensor) list
		if( vltsensor_reload ) {
			var selectalarm = "";
			for( var i=0 ; i<vltsensor.length ; i++ ) {
				if( vltsensor[i].sensor_name.length > 0 ) {
					selectalarm += '<input name="vlt_gpio_' + i + '" type="checkbox" /> ' + vltsensor[i].sensor_name + ' <br />' ;
				}
			}
			$("div#selectalarm").html(selectalarm);
			vltsensor_reload = 0 ;
		}
	},
	buttons:{
		"Geo Fence Define...": function() {
			$( ".tdcdialog#dialog_geofence" ).dialog("open");
		},
		"Apply": function() {
			// apply settings
			var param = $("form#vltreportconfig").serializeArray();
			vltparam.vltserial++ ;
			param[param.length] = {
				'name': 'vltserial',
				'value': vltparam.vltserial };
			param[param.length] = {
				'name': 'vltpage',
				'value': vltparam.vltpage };
			$.getJSON("vltreportconfigapply.php", param, function(resp){
				if( resp.res==1 ) {
					vlt_autoreport(1);
				}
			});
			
			//$( this ).dialog( "close" );
		},
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

var geofencemap = null ;
var geofence_toparea = null ;
var geofence_color = {
	"In": new Microsoft.Maps.Color( 80, 17, 238, 50),
	"Out": new Microsoft.Maps.Color( 80, 248, 80, 7),
	"Both": new Microsoft.Maps.Color( 128, 182, 41, 69),
	"Disable": new Microsoft.Maps.Color( 192, 70, 72, 94)
};

function geofence_select( area )
{
	if (area instanceof Microsoft.Maps.Polygon){
		geofence_toparea = area ;
		
		// get geo fence type
		var ac = area.getFillColor();
		if( ac.b == geofence_color.In.b ) {
			$(".tdcdialog#dialog_geofence input[value='In']").prop("checked",true);
		}
		else if( ac.b == geofence_color.Out.b ) {
			$(".tdcdialog#dialog_geofence input[value='Out']").prop("checked",true);
		}
		else if( ac.b == geofence_color.Both.b ) {
			$(".tdcdialog#dialog_geofence input[value='Both']").prop("checked",true);
		}
		else {
			$(".tdcdialog#dialog_geofence input[value='Disable']").prop("checked",true);
		}
	
		// remove old pushpin
		for(var i=geofencemap.entities.getLength()-1;i>=0;i--) {
			var pushpin = geofencemap.entities.get(i); 
			if (pushpin instanceof Microsoft.Maps.Pushpin) { 
				geofencemap.entities.removeAt(i);  
			}
		}
		
		var locs = area.getLocations();
		var locrect=Microsoft.Maps.LocationRect.fromLocations( locs );
		var loccenter = locrect.center ;
		var maprect = geofencemap.getBounds() ;
		if( locrect.height/maprect.height < 0.1 && locrect.width/maprect.width < 0.1 ) {
			geofencemap.setView({ bounds: locrect });
		}
		else if ( !locrect.intersects( maprect ) ) {
			geofencemap.setView({ center: loccenter });
		}
	
		var pushpinOptions = {
			icon:'res/pin_24.png', 
			height: 24,
			width: 24,
			anchor: new Microsoft.Maps.Point(12,12),
			draggable: true,
		}; 

		var pins=[
			new Microsoft.Maps.Pushpin(locs[0], pushpinOptions),
			new Microsoft.Maps.Pushpin(locs[1], pushpinOptions),
			new Microsoft.Maps.Pushpin(locs[2], pushpinOptions),
			new Microsoft.Maps.Pushpin(locs[3], pushpinOptions),
			new Microsoft.Maps.Pushpin(loccenter, pushpinOptions)
			];
		
		var ondragcorner = function(e){
			var thispin=e.entity ;
			var ipin ;
			for( ipin=0; ipin<4; ipin++) {
				if( pins[ipin] == e.entity ) {
					break;
				}
			}
	<?php
		if( !empty($zone_mode) && $zone_mode == 'cross' ) {
	?>	
			var loc0=pins[ipin].getLocation();
			var loc2=pins[(ipin+2)%4].getLocation();
			pins[(ipin+1)%4].setLocation( new Microsoft.Maps.Location( loc0.latitude, loc2.longitude ) );
			pins[(ipin+3)%4].setLocation( new Microsoft.Maps.Location( loc2.latitude, loc0.longitude ) );
			var locrect = Microsoft.Maps.LocationRect.fromLocations( loc0, loc2 );
			loccenter = locrect.center ;
			pins[4].setLocation( loccenter );
	<?php } else { ?>				
			var loc = pins[ipin].getLocation();
			var loccenter = pins[4].getLocation();
			var h = 2*(loccenter.latitude - loc.latitude);
			var w = 2*(loccenter.longitude - loc.longitude);
			pins[(ipin+1)%4].setLocation( new Microsoft.Maps.Location( loc.latitude+h, loc.longitude ) );
			pins[(ipin+2)%4].setLocation( new Microsoft.Maps.Location( loc.latitude+h, loc.longitude+w ) );
			pins[(ipin+3)%4].setLocation( new Microsoft.Maps.Location( loc.latitude,   loc.longitude+w ) );
	<?php  }  ?>		
			area.setLocations([
				pins[0].getLocation(),
				pins[1].getLocation(),
				pins[2].getLocation(),
				pins[3].getLocation(),
				pins[0].getLocation()
				]); 
		};

		var ondragmove = function(e){
			var nloc = e.entity.getLocation();
			var mvlat = nloc.latitude - loccenter.latitude ;
			var mvlon = nloc.longitude - loccenter.longitude;
			loccenter=nloc ;
			for(var i=0;i<4;i++) {
				nloc=pins[i].getLocation();
				nloc.latitude+=mvlat ;
				nloc.longitude+=mvlon ;
				pins[i].setLocation( nloc );
			}
			area.setLocations([
				pins[0].getLocation(),
				pins[1].getLocation(),
				pins[2].getLocation(),
				pins[3].getLocation(),
				pins[0].getLocation()
				]);
		};
		
		Microsoft.Maps.Events.addHandler(pins[0], 'drag', ondragcorner);  
		Microsoft.Maps.Events.addHandler(pins[1], 'drag', ondragcorner);  
		Microsoft.Maps.Events.addHandler(pins[2], 'drag', ondragcorner);  
		Microsoft.Maps.Events.addHandler(pins[3], 'drag', ondragcorner);  
		Microsoft.Maps.Events.addHandler(pins[4], 'drag', ondragmove); 
		
		geofencemap.entities.push(pins[0]);
		geofencemap.entities.push(pins[1]);
		geofencemap.entities.push(pins[2]);
		geofencemap.entities.push(pins[3]);
		geofencemap.entities.push(pins[4]);	
	}
}

function addgeofence(zone, select)
{
	var a = zone.split(",");
	if( a[0] == a[2] || a[1] == a[3] ) {
		// invalid zone 
		return null ;
	}
	var top = parseFloat(a[0]);
	var bottom = parseFloat(a[2]);
	var left = parseFloat(a[1]);
	var right = parseFloat(a[3]);
	var locs=[
		new Microsoft.Maps.Location( top, left ),
		new Microsoft.Maps.Location( top, right ),
		new Microsoft.Maps.Location( bottom, right ),
		new Microsoft.Maps.Location( bottom, left ),
		new Microsoft.Maps.Location( top, left )
	] ;	
		
	var options = { fillColor: geofence_color[a[4]], strokeColor: new Microsoft.Maps.Color( 31, 55, 61,200 ), strokeThickness: 5 }; 
	var polygon = new Microsoft.Maps.Polygon(locs,	options); 		
	geofencemap.entities.push(polygon);
	Microsoft.Maps.Events.addHandler(polygon, 'click', function(e){
		geofence_select( e.target ) ;
	});  
	if( select ) {
		geofence_toparea = polygon ;
		geofence_select( polygon ) ;
	}	
	return polygon ;
}

function geofencezone(){
	// get geofence zone rect
	var i ;
	var geof = "" ;
	for( i=geofencemap.entities.getLength()-1; i>=0; i-- ) {
		var polygon=geofencemap.entities.get(i);
		if (polygon instanceof Microsoft.Maps.Polygon) { 
			var locations = polygon.getLocations(); 
			if( locations.length>=4 ) {
				if( geof.length>1 ) {
					geof+=";";
				}
				var locrect=Microsoft.Maps.LocationRect.fromLocations( locations );
				geof += locrect.getNorth().toFixed(6)
					 + ","+locrect.getWest().toFixed(6)
					 + ","+locrect.getSouth().toFixed(6)
					 + ","+locrect.getEast().toFixed(6) ;
				var b = polygon.getFillColor().b ;
				if( b == geofence_color.In.b ) {
					geof+=",In" ;
				}
				else if( b == geofence_color.Out.b ) {
					geof+=",Out" ;
				}
				else if( b == geofence_color.Both.b ) {
					geof+=",Both" ;
				}
				else {
					geof+=",Disable" ;
				}				
			}
		}
	}
	return geof ;
}

function geofence_load()
{
	geofence_toparea = null ;
	geofencemap.entities.clear(); 
	var geo = $("form#vltreportconfig input[name='vlt_geo']").val();
	var geo_a = geo.split(";");
	for( var i=0; i<geo_a.length; i++ ) {
		addgeofence( geo_a[i], i==0?1:0 );
	}

	// set map location
	var locs=new Array() ;
	for(var i=geofencemap.entities.getLength()-1;i>=0;i--) {
		var entity = geofencemap.entities.get(i); 
		if (entity instanceof Microsoft.Maps.Polygon) { 
			locs = locs.concat( entity.getLocations() ); 
		}
	}
	if( locs.length>0 ) {
		var locrect=Microsoft.Maps.LocationRect.fromLocations( locs );
		geofencemap.setView({ bounds: locrect});
	}
}

// initialize geo-fence dialog
$( ".tdcdialog#dialog_geofence" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	create: function() {
		$("button#geofenceloadzone").click(function(){
			var zone = $('#zonelist').val();
			if( $('#zonelist')[0].selectedIndex<0 || zone==0 ) {
				return ;
			}
			else{
			$.getJSON("zonelist.php?name="+zone, function(resp){
				if( resp.res ) {
					if( resp.zonelist.length>0) {
						var zone=""+resp.zonelist[0].top+","+resp.zonelist[0].left+","+resp.zonelist[0].bottom+","+resp.zonelist[0].right+",In";
						addgeofence(zone, 1);
					}
				}
			});	
			}
		});
		
		// geo fence type change
		$(".tdcdialog#dialog_geofence input[name='geofencetype']").change(function(e){
			if( geofence_toparea ) {
				var t = $(".tdcdialog#dialog_geofence input[name='geofencetype']:checked").val();
				geofence_toparea.setOptions( { fillColor: geofence_color[t] } );
			}
		});

		// Button New
		$(".tdcdialog#dialog_geofence button#newgeofence").click(function(){
			var lrect = geofencemap.getBounds();
			lrect.height *= 0.75 ;
			lrect.width *= 0.75 ;
			var zone=""+lrect.getNorth()+","+lrect.getWest()+","+lrect.getSouth()+","+lrect.getEast()+",In";
			addgeofence(zone, 1);
		});

		// Button Delete
		$(".tdcdialog#dialog_geofence button#deletegeofence").click(function(){
			if( geofence_toparea ) {
				geofencemap.entities.remove(geofence_toparea); 
				// select another polygon
				for(var i=geofencemap.entities.getLength()-1;i>=0;i--) {
					var entity = geofencemap.entities.get(i); 
					if (entity instanceof Microsoft.Maps.Pushpin) { 
						geofencemap.entities.removeAt(i);  
					}
					else if (entity instanceof Microsoft.Maps.Polygon) { 
						geofence_select( entity );
						break;
					}
				}				
			}
		});

		// Set geo type radio color
		var rcolor =  "rgb(" + geofence_color.In.r + ", " + geofence_color.In.g + ", " + geofence_color.In.b + ")";
		$("div#geoin")[0].style.backgroundColor=rcolor;
		rcolor =  "rgb(" + geofence_color.Out.r + ", " + geofence_color.Out.g + ", " + geofence_color.Out.b + ")";
		$("div#geoout")[0].style.backgroundColor=rcolor;
		rcolor =  "rgb(" + geofence_color.Both.r + ", " + geofence_color.Both.g + ", " + geofence_color.Both.b + ")";
		$("div#geoboth")[0].style.backgroundColor=rcolor;
		rcolor =  "rgb(" + geofence_color.Disable.r + ", " + geofence_color.Disable.g + ", " + geofence_color.Disable.b + ")";
		$("div#geodisable")[0].style.backgroundColor=rcolor;

	},
	open: function( event, ui ) {

		if( geofencemap==null) {
			geofencemap = new Microsoft.Maps.Map(document.getElementById("geofencemap"),
			{credentials: <?php echo "'$map_credentials'"; ?> ,
			center: map.getCenter(),
			zoom: map.getZoom(),
			enableSearchLogo: false,
			enableClickableLogo: false,
			mapTypeId : Microsoft.Maps.MapTypeId.road
			});
		}

		// pre-def zone list
		$('#zonelist').empty();
		$.getJSON("zonelist.php", function(resp){
			if( resp.res == 1 ) {
				var zlist = '' ;
				for( var i=0; i<resp.zonelist.length; i++ ) {
					zlist += '<option>' + resp.zonelist[i].name + '</option>' ;
				}
				$('#zonelist').html(zlist);
			}
		});	
		
		// init map size
		if( !$( "div#geofencemaparea" ).data("wdif") ) {
			var wdif = $( "div#dialog_geofence" ).width() - $( "div#geofencemaparea" ).width() ;
			var hdif = $( "div#dialog_geofence" ).height() - $( "div#geofencemaparea" ).height() ;
			$( "div#geofencemaparea" ).data("wdif", wdif );
			$( "div#geofencemaparea" ).data("hdif", hdif );
		}
		geofence_load();
	},	
	close: function( event, ui ) {
		geofencemap = null;
	},	
	resize: function( event, ui ) {
		$( "div#geofencemaparea" ).width($( "div#dialog_geofence" ).width()-$( "div#geofencemaparea" ).data("wdif"));
		$( "div#geofencemaparea" ).height($( "div#dialog_geofence" ).height()-$( "div#geofencemaparea" ).data("hdif"));
	},
	buttons:{
		"OK": function() {
			$("form#vltreportconfig input[name='vlt_geo']").val( geofencezone() );
			$( this ).dialog( "close" );
		},
		"Reset": function() {
			geofence_load() ;
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$( "button[name='reportconfiguration']" ).click(function(){
	$( ".tdcdialog#dialog_reportconfig" ).dialog("open");
});

// auto report,
function vlt_autoreport( start )
{
	vltparam.vltserial++ ;
	var param = vltparam ;
	if( start )
		param.start = start ;
	else 
		param.start = 0 ;
	$.getJSON("vltautoreport.php", param, function(resp){
		if( resp.res ) {
			if( resp.stop ) {
				alert("Auto-report stopped!");
			}
		}
	});	
}

// start report
$( "button[name='startautoreort']" ).click(function(){
	vlt_autoreport(1);
});
// stop report
$( "button[name='stopautoreport']" ).click(function(){
	vlt_autoreport(0);
});

// live view button
$( "button[name='liveview']" ).click(function(e){
	e.preventDefault();
	var vehicle = $("select[name='vltvehicle']").val();
	if( vehicle ) {
		if( vehicle instanceof Array) {
			vehicle = vehicle[0] ;
		}
		var dvrdetail = vltlist[vehicle] ;
		
		// for live view button:
		dvrdetail['support_playback'] = 0 ;
		dvrdetail['support_live'] = 1 ;
		dvrdetail['playmode'] = "live" ;
			
		$("form#liveviewform input[name='info']").val( JSON.stringify( dvrdetail ) );
		
		// unset those new properties
		delete dvrdetail['support_playback'] ;
		delete dvrdetail['support_live'] ;
		delete dvrdetail['playmode'] ;
		
		$('form#liveviewform').submit();
	}
});

// playback button
$( "button[name='playback']" ).click(function(e){
	e.preventDefault();
	var vehicle = $("select[name='vltvehicle']").val();
	if( vehicle ) {
		if( vehicle instanceof Array) {
			vehicle = vehicle[0] ;
		}
		var dvrdetail = vltlist[vehicle] ;
		
		// for live view button:
		dvrdetail['support_playback'] = 1 ;
		dvrdetail['support_live'] = 0 ;
		dvrdetail['playmode'] = "playback" ;
			
		$("form#liveviewform input[name='info']").val( JSON.stringify( dvrdetail ) );
		
		// unset those new properties
		delete dvrdetail['support_playback'] ;
		delete dvrdetail['support_live'] ;
		delete dvrdetail['playmode'] ;
		
		$('form#liveviewform').submit();
	}
});


// live setup button
$( "button[name='setupdvr']" ).click(function(e){
	e.preventDefault();
	var vehicle = $("select[name='vltvehicle']").val();
	if( vehicle ) {
		if( vehicle instanceof Array) {
			vehicle = vehicle[0] ;
		}
		var dvrdetail = vltlist[vehicle] ;
		var win ;
		if( dvrdetail.ip ) {
			win=window.open("http://"+dvrdetail.ip+"/", '_blank');
		}
		else {
			win=window.open("livesetup.php/"+dvrdetail.phone+"/system.html", '_blank');
		}
		win.focus();
	}
});

var live_vehicle = "";
var live_phone = "" ;
// live testing
var livetest=1 ;

// live preview dialog
$( ".tdcdialog#dialog_webplay" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	beforeClose: function( event, ui ) {
		dashstop( $( "video#webplay" )[0] );		
	},
	resize: function( event, ui ) {
		$( "video#webplay" )[0].width=$( "div#dialog_webplay" ).width() - $( ".tdcdialog#dialog_webplay" ).data("wdif") - 4;
		$( "video#webplay" )[0].height=$( "div#dialog_webplay" ).height() - $( ".tdcdialog#dialog_webplay" ).data("hdif") - 4;
	},	
	open: function( event, ui ) {
		var wdif = $( "div#dialog_webplay" ).width() - $( "video#webplay" )[0].width ;
		var hdif = $( "div#dialog_webplay" ).height() - $( "video#webplay" )[0].height ;
		$( ".tdcdialog#dialog_webplay" ).data("wdif", wdif );
		$( ".tdcdialog#dialog_webplay" ).data("hdif", hdif );
		$( ".tdcdialog#dialog_webplay" ).dialog("option", "title", "Live Preview - " + live_vehicle );
		$("select#webplay_camera").change();
	},
	create: function( event, ui ) {
		$("button#webplay_reload").click(function(){
			$("select#webplay_camera").change();
		});
		$("button#webplay_close").click(function(){
			$( ".tdcdialog#dialog_webplay" ).dialog( "close" );
		});
		$("select#webplay_camera").change(function(){
			var camera = $("select#webplay_camera").val();
			dashplay( $( "video#webplay" )[0], live_phone, camera ) ;
		});
	}
});

if( window.MediaSource && MediaSource.isTypeSupported("video/mp4; codecs=\"avc3.640028,mp4a.40.2\"") )  {
	// live preview button
	$( "button[name='livepreview']" ).click(function(e){
		e.preventDefault();
		var vehicle = $("select[name='vltvehicle']").val();
		if( !vehicle && livetest ) {
			vehicle = "livetest" ;
			vltlist[vehicle] = { phone: "99999"};
		}
		if( vehicle ) {
			if( vehicle instanceof Array) {
				vehicle = vehicle[0] ;
			}
			live_vehicle = vehicle ;
			if( vltlist[live_vehicle] && vltlist[live_vehicle].phone ) {
				live_phone = vltlist[live_vehicle].phone ;
				$.getJSON("livechinfo.php?phone="+live_phone, function(data){
					if( data.res && data.channels && data.channels.length>0 ) {
						chhtml='' ;
						var i ;
						for( i=0; i<data.channels.length; i++ ) {
							chhtml += "<option value=\"" + data.channels[i].camera +"\">" + data.channels[i].name + "</option>" ;
						}
						$("select#webplay_camera").html(chhtml);
						$( ".tdcdialog#dialog_webplay" ).dialog("open");
					}
				});

				var video = $( "video#webplay" )[0] ;
				if( !video.src ) {
					video.play();			// chrome mobile hack, play() can only be called in user gesture
				}
			}
		}
	});
}
else {
	$( "button[name='livepreview']" ).hide();
}

$( window ).unload( function(){
	// save map position
	if( sessionStorage ) {
		var localsession = {} ;
		var tdsess = sessionStorage.getItem('tdsess');
		if( tdsess ) {
			localsession = JSON.parse(tdsess);
		}
		var center = map.getCenter();
		var zoom = map.getZoom();
		localsession.tdcmap={ lat:center.latitude,lon:center.longitude,zoom:zoom };
		sessionStorage.setItem('tdsess', JSON.stringify(localsession));
	}
	// finish vlt session
	vltparam.vltserial++ ;
	$.ajax( {
		url: "vltunload.php",
		data: vltparam ,
		timeout: 100
	});
});

$("select[name='vehicle']").change(function(e){
	var x=this.value ;
});

function showup()
{
	trigger_resize();

	// load bing theme
	Microsoft.Maps.loadModule('Microsoft.Maps.Themes.BingTheme', { callback: themesModuleLoaded });
	function themesModuleLoaded() {
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
		{	credentials: <?php echo "'$map_credentials'"; ?> ,
			center: mapcenter,
			zoom: mapzoom,
			enableSearchLogo: false,
			enableClickableLogo: false,
			mapTypeId : Microsoft.Maps.MapTypeId.road
		});

		if( !mapinit ) {
			$.getJSON("mapquery.php", function(resp){
				if( resp.res && resp.map && resp.map.bbox && resp.map.bbox.length>=4) {
					setTimeout( function(){
						var nbounds = Microsoft.Maps.LocationRect.fromLocations( [
							new Microsoft.Maps.Location( resp.map.bbox[0], resp.map.bbox[1] ),
							new Microsoft.Maps.Location( resp.map.bbox[2], resp.map.bbox[3] )
							] );
						map.setView({bounds:nbounds});
					}, 1000 ) ;
				}
			});
		}		
	}
}

// load bing theme
Microsoft.Maps.loadModule('Microsoft.Maps.Themes.BingTheme');

// show up 
$('#rcontainer').show('slow', showup );

});


</script>
	<style type="text/css">
	</style>
</head>
<body>
<div id="container">
<?php include 'header.php'; ?>
<div id="lpanel"><?php if( !empty($support_viewtrack_logo) ){ ?>
	<img alt="index.php" src="res/side-VT-logo-clear.png" />
<?php } else if( !empty($support_fleetmonitor_logo) ){ ?>
	<img alt="index.php" src="res/side-FM-logo-clear.png" />
<?php } else { ?> 
	<img alt="index.php" src="res/side-TD-logo-clear.png" />
<?php } ?>
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<?php if( !empty($enable_videos) ){ ?><li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li><?php } ?>
	<li><img src="res/side-livetrack-logo-green.png" /></li>
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
<strong><span style="font-size:26px;">LIVE TRACK</span></strong>
</div>

<div id="rcontainer">

<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">

<!-- 
<fieldset>
<legend>AVL Server</legend>
<p><input id="AVLServer" readonly/></p>
<input id="avlpasswordlen" type="hidden"/>
<?php if( $_SESSION['user_type'] == "admin" ) {	?>
<button id="btavlserver">Change...</button>
<?php } ?>
</fieldset> 
-->

<h3 style="text-align: center;">Select Vehicle</h3>
<select multiple name="vltvehicle" size="10" style="min-width:13em;margin-left:10px;margin-right:10px"  > 
</select>

<div style="text-align: center;"><button style="min-width:14em;" name="getcurrentpos">Get Current Pos</button></div>
<div style="text-align: center;"><button style="min-width:14em;" name="clearallicons">Clear All Icons</button></div>
<?php if( $_SESSION['user_type'] == "admin" ) {	?>
<div style="text-align: center;"><button style="min-width:14em;" name="sensorconfig">Sensor Config...</button></div>
<?php } ?>
<div style="text-align: center;"><button style="min-width:14em;" name="reportconfiguration">Report Configuration...</button></div>
<div style="text-align: center;"><button style="min-width:14em;" name="startautoreort">Start Auto Report</button></div>
<div style="text-align: center;"><button style="min-width:14em;" name="stopautoreport">Stop Auto Report</button></div>
<div style="text-align: center;"><button style="min-width:14em;" name="liveview">Live View</button></div>
<div style="text-align: center;"><button style="min-width:14em;" name="playback">Play Back</button></div>
<?php if( $_SESSION['user_type'] == "admin" ) { ?>
<div style="text-align: center;"><button style="min-width:14em;" name="setupdvr">Setup DVR</button></div>
<?php } ?>
<?php if( !empty($support_livepreview) ){ ?>
<div style="text-align: center;"><button style="min-width:14em;" id="livepreview" name="livepreview">Live Preview</button></div>
<?php } ?>
<form id="liveviewform" enctype="application/x-www-form-urlencoded" method="get" action="vltliveview.php" >
<input type="hidden" name="info"/>
</form>

</div>

<!-- AVL Server Dialog -->
<div class="tdcdialog" title="AVL Server" id="dialog_avlserver">
<p>Please Input New AVL Server IP Address:<br/><input name="avlServer"/></p>
<p>Password To Login:<br/><input name="avlPassword" type="password" /></p>
</div>

<!-- AVL vehicle detail -->
<div class="tdcdialog" title="Live Vehicle Detail" id="dialog_vehicle_detail">
<table id="dvrinfo" >
</table>
</div>

<!-- Sensor Config Dialog -->
<div class="tdcdialog" title="Sensor Config" id="dialog_sensorconfig">
<p>Please Give Sensor a Meaningful Name:</p>

<form name="sensorconfig">
<table border="0" cellpadding="0" cellspacing="1" style="width: 100%;">
	<thead>
<tr>
	<th style="width:100px" >Sensor Index</th>
	<th>Sensor Name</th>
</tr>
	</thead>
</table>	
<div style="overflow: auto;width:400px;max-height:300px;">
<table id="vltsensortable" border="0" cellpadding="0" cellspacing="1" style="width: 100%;">
</table>
</div>
</form>

</div>

<!-- Report Config Dialog -->
<div class="tdcdialog" title="Report Configuration" id="dialog_reportconfig">
<form id="vltreportconfig"> 
<table border="0" cellpadding="0" cellspacing="10" style="width: 100%;">
<tr>
<td>
	<fieldset>
	<legend>Select Vehicle</legend>
	<p><input name="vlt_select_type" id="vlt_select_type1" value="1" type="radio" checked /> By Vehicle <input name="vlt_select_type" id="vlt_select_type2" value="2" type="radio" /> By Group </p>
	<select id="vlt_vehicle_list" name="vlt_vehicle[]" multiple size="8" style="width:15em">
	</select>
	</fieldset>

	<fieldset>
	<legend>Select Alarm</legend>
	<div id="selectalarm" style="height:160px;overflow:auto;">
	</div>
	</fieldset>

</td>
<td>
	<fieldset>
	<legend>Impact Threshold (between 2~16g)</legend>
	<table>
		<tr>
		<td style="text-align: right;">Front Impact:</td><td><input style="width:5em;" name="vlt_impact_front"/> g</td>
		</tr>
		<tr>
		<td style="text-align: right;">Rear Impact:</td><td><input style="width:5em;" name="vlt_impact_rear"/> g</td>
		</tr>
		<tr>
		<td style="text-align: right;">Side Impact:</td><td><input style="width:5em;" name="vlt_impact_side"/> g</td>
		</tr>
		<tr>
		<td style="text-align: right;">Bumpy Ride:</td><td><input style="width:5em;" name="vlt_impact_bumpy"/> g</td>
		</tr>
	</table>
	</fieldset>

	<table>
		<tr>
		<td style="text-align: right;">Report On Over Speed:</td><td><input style="width:5em;" name="vlt_speed"/> mph</td>
		</tr>
		<tr>
		<td style="text-align: right;">Report On Time Interval:</td><td><input style="width:5em;" name="vlt_time_interval"/> s</td>
		</tr>
		<tr>
		<td style="text-align: right;">Report On Distance Interval:</td><td><input style="width:5em;" name="vlt_dist_interval"/> ft</td>
		</tr>
		<tr>
		<td style="text-align: right;">Max Count:</td><td><input style="width:5em;" name="vlt_maxcount"/> </td>
		</tr>
		<tr>
		<td style="text-align: right;">Max Bytes:</td><td><input style="width:5em;" name="vlt_maxbytes"/> KB</td>
		</tr>
		<tr>
		<td style="text-align: right;">Temperature:</td><td><input style="width:5em;" name="vlt_temperature"/> &deg;F</td>
		</tr>
		<tr>  
		<td style="text-align: right;">Idling:</td><td><input style="width:5em;" name="vlt_idling" /> s</td>
		</tr>
	</table>
	
</td>
<td>
	 <p><button name="vltsavedefault" value="1">Save As Default 1</button></p>
	 <p><button name="vltsavedefault" value="2">Save As Default 2</button></p>
	 <p><button name="vltsavedefault" value="3">Save As Default 3</button></p>
	 <br/>
	 <p><button name="vltloaddefault" value="1">Load Default 1</button></p>
	 <p><button name="vltloaddefault" value="2">Load Default 2</button></p>
	 <p><button name="vltloaddefault" value="3">Load Default 3</button></p>
</td>
</tr>
</table>
<input type="hidden" name="vlt_geo" value="" />
</form>
</div>

<!-- Video Live Preview Dialog -->
<div class="tdcdialog" title="Live Preview" id="dialog_webplay">
<video id="webplay" poster="res/247logo.jpg" width="640" height="400" type="video/mp4" >
Your browser does not support the video tag.
</video>
<hr />
<p style="text-align: right;">
<label for="webplay_camera"> Select camera:</label> 
<select id="webplay_camera"></select>
&nbsp;&nbsp;&nbsp;
<button id="webplay_reload">Reload</button> <button id="webplay_close">Close</button></p>
</div>

<!-- Geo Fence Dialog -->
<div class="tdcdialog" title="Geo Fence" id="dialog_geofence">
<table>
<tr>
<td>
<p>
<fieldset>
<legend>Geo Fence Type</legend>
<div id="geoin" style="width:100px;">
<input name="geofencetype" value="In" type="radio" checked /> In 
</div>
<div id="geoout" style="width:100px;">
<input name="geofencetype" value="Out" type="radio" /> Out <br />
</div>
<div id="geoboth" style="width:100px;">
<input name="geofencetype" value="Both" type="radio" /> Both <br />
</div>
<div id="geodisable" style="width:100px;">
<input name="geofencetype" value="Disable" type="radio" /> Disable 
</div></fieldset>
<p>
<button id="newgeofence"><img src="res/button_add.png" style="width: 20px; height: 20px;" />New</button>
</p>
<p>
<button id="deletegeofence"><img src="res/button_delete.png" style="width: 20px; height: 20px;" />Delete</button>
</p>	
</td>
<td>
Pre-Defined Zone:
<select id="zonelist" name="zonelist" style="min-width: 12em;">
</select> 
<button id="geofenceloadzone">Add</button>
<div id="geofencemaparea" style="position: relative; height:380px; width:500px;">
<div id="geofencemap">Geo Fence Map</div>
</div>
</td>
</tr>
</table>
</div>

<div id="workarea">
<div id="tdcmap">Maps</div>
</div>

</div>
<!-- mcontainer --></div>
<div id="push"></div>
</div>
<div id="footer">
<hr />
<div id="footerline" style="padding-left:24px;padding-right:24px">
<div style="float:left"></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>