<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
?>
	<title>Drive By Event</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type="text/javascript" src="https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1"></script><script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css">
		#rcontainer { display:none }
	</style>	
<script>
// start up

$(document).ready(function(){
$("button").button();	

$(".btset").buttonset();
$(".btset input").change(function(){
   location=$(this).attr("href");
});

$( ".xsel" ).selectmenu();

var selectedtag = "" ;
var tagchannels = {};

var static_name = 1 ;

// Event Tag list
$("#tag_list").jqGrid({
//    scroll: true,
	datatype: "json",
	url:'emggrid.php',
	height: 380,
	width: 1024,
	caption: 'Emergency Events',
    colNames:['Date/Time', 'Client ID', 'Vehicle ID', 'Sensor Status', 'Event Type' ],
    colModel :[
      {name:'Date_Time', index:'Date_Time', width:180, sortable: true },
      {name:'Client_Id', index:'Client_Id', width:180, sortable: true }, 
      {name:'Bus_Id', index:'Bus_Id', width:180, sortable: true }, 
      {name:'Sensor_Status', index:'Sensor_Status', width:180 }, 
      {name:'Event_Code', index:'Event_Code', width:180, sortable: true }
    ],
   	sortname: 'Date_Time',
    sortorder: "desc",
	pager: '#tag_list_pager',
	editurl: "drivebyedit.php",
    viewrecords: true,
	rowNum: 20 ,
    rowList:[20, 50, 100, 200],	
//	gridComplete: display_summary,
	ondblClickRow: function(id) {
		$("#tag_list").jqGrid('editRow',id,true);
	}
});
jQuery("#tag_list").jqGrid('filterToolbar',{searchOnEnter : false});

var emgvideo_wdif=0 ;
var emgvideo_hdif=0 ;

// Display Video Dialog
$( ".dialog#dialog_emgvideo" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	resize: function( event, ui ) {
		// adjust video size
		if( emgvideo_wdif == 0 ) {
			emgvideo_wdif = $( "div#dialog_emgvideo" ).width() - $( "video#emgvideo" )[0].width ;
			emgvideo_hdif = $( "div#dialog_emgvideo" ).height() - $( "video#emgvideo" )[0].height ;
		}
		else {
			$( "video#emgvideo" )[0].width=$( "div#dialog_emgvideo" ).width() - emgvideo_wdif;
			$( "video#emgvideo" )[0].height=$( "div#dialog_emgvideo" ).height() - emgvideo_hdif;
		}
	},	
	open: function( event, ui ) {
		var sel = $("#tag_list").jqGrid( 'getGridParam', 'selrow' );
		if( sel ) {
			var param = {} ;
			param.id = sel ;
			$.getJSON("emgload.php", param, function(resp){
				if( resp.res == 1 ) {
					// setup camera select
					var camopts = [] ;
					var camopt = "" ;
					if( resp.tag.channels.channel ) {
						if( resp.tag.channels.channel.length ) {
							for( var ch = 0 ; ch < resp.tag.channels.channel.length; ch++ ) {
								// set lpr1
								var name = "camera"+(ch+1) ;
								if( resp.tag.channels.channel[ch].name ) {
									name = resp.tag.channels.channel[ch].name ;
								}
								camopts[ch] = name ;
								camopt += "<option value=\"" + ch +"\">"+name+"</option>" ;
							}
						}
						else if(resp.tag.channels.channel.video){
							var name = "camera1" ;
							if( resp.tag.channels.channel.name ) {
								name = resp.tag.channels.channel.name ;
							}
							camopts[0] = name ;
							camopt += "<option value=\"0\">"+name+"</option>" ;
						}
					}
					$("select[name='emgvideocam']").html( camopt ); 
					$("select[name='emgvideocam']").data("tag", resp.id );
					$("select[name='emgvideocam']").change(); 
				}
				else {
					alert("Can not load this event!");
				}
			});	
		}
		else {
			$( this ).dialog( "close" );
		}
	},
	close: function( event, ui ) {
		$("video#emgvideo" )[0].pause();
		$("video#emgvideo").attr("src", "");
	},
	buttons:{
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

$("select[name='emgvideocam']").change(function(){
	var mp4 = "emgvideo.php?tag=" + $("select[name='emgvideocam']").data("tag") +"&channel="+$("select[name='emgvideocam']").val();
	$("video#emgvideo").attr("src", mp4);
	$("video#emgvideo" )[0].play();
});

$("button#Video").click( function() {
	$( ".dialog#dialog_emgvideo" ).dialog("open");
} );

// delete report dialog
$( ".dialog#dialog_delevent" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		var tag = $("#tag_list").jqGrid('getRowData', selectedtag);	
		$("span#dr_clientid").text( tag.Client_Id );
		$("span#dr_busid").text( tag.Bus_Id );
		$("span#dr_eventtime").text( tag.Date_Time );
	},
	buttons:{
		"Delete": function() {
			var param = {} ;
			param.tag = selectedtag ;
			$.getJSON("drivebydeleteevent.php", param, function(resp){
				if( resp.res == 1 ) {
					$("#tag_list").jqGrid('delRowData', resp.tag);	
					selectedtag = "" ;
					alert( "Reports deleted!" );
				}
				else {
					alert( "Delete Event failed!" );
				}
				$( ".dialog#dialog_delevent" ).dialog("close");
			});
		},
		"Cancel": function() {
			$( this ).dialog( "close" );
		}
	}
});

// map dialog
var map ;
$( ".dialog#dialog_map" ).dialog({
	autoOpen: false,
	width:"680",
	height: "520" ,
	modal: true,
	open: function( event, ui ) {
		$( ".dialog#dialog_map" ).dialog({title: "Event Location" });
		var sel = $("#tag_list").jqGrid( 'getGridParam', 'selrow' );
		if( sel ) {
			var param = {} ;
			param.id = sel ;
			$.getJSON("emgload.php", param, function(resp){
				if( resp.res == 1 ) {
					var loc = new Microsoft.Maps.Location(resp.tag.Lat, resp.tag.Lon ) ;
					if( !map ) {
						map =  new Microsoft.Maps.Map(document.getElementById("emgmap"), 
							{credentials: <?php echo '"'. $map_credentials . '"'; ?> ,
							zoom: 14,
							center: loc,
							enableSearchLogo: false,
							enableClickableLogo: false,
						});
					}
					else {
						map.setView({center: loc});
					}
					map.entities.clear();
					var pin=new Microsoft.Maps.Pushpin(loc, {draggable: false});
					map.entities.push(pin);
				}
				else {
					alert("Can not load this event!");
				}
				
				$.ajax({
					url : "https://dev.virtualearth.net/REST/v1/Locations/"+resp.tag.Lat+","+resp.tag.Lon ,
					data : {o:"json",key:<?php echo "'$map_credentials'"; ?>},
					dataType : 'jsonp',	jsonp :'jsonp'
				}).done(function(location){
					if( location.resourceSets[0].resources[0] && location.resourceSets[0].resources[0].address && location.resourceSets[0].resources[0].address.formattedAddress ) {
						$( ".dialog#dialog_map" ).dialog({title: location.resourceSets[0].resources[0].address.formattedAddress});
					}
				
				});
			
			});	
			
		}
		else {
			$( ".dialog#dialog_map" ).dialog("close");
		}
	},
	buttons:{
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

$("button#showmap").click(function(event){
	event.preventDefault();
	$( ".dialog#dialog_map" ).dialog("open");
});

var x ;
$("button#showPanic").click(function(event){
	event.preventDefault();

	
});

$("button#showCrash").click(function(event){
	event.preventDefault();

	
});

// current zoom level
var mapzoom = 15 ;
var mapcoor = "37.778297,-122.417297" ;

function setmap() 
{
	var mapimg = "https://dev.virtualearth.net/REST/v1/Imagery/Map/Road/"+mapcoor+"/"+mapzoom+"?pp="+mapcoor+";0&ms=360,200&key="+
			<?php echo "'$map_credentials'"; ?>	;
	$("img#mapimage").prop("src", mapimg );
	$("#mapaddress").text( "" );
	$("#coordinate").text( "" );
	$.ajax({
		url : "https://dev.virtualearth.net/REST/v1/Locations/"+mapcoor ,
		data : {o:"json",key:<?php echo "'$map_credentials'"; ?>},
		dataType : 'jsonp',	jsonp :'jsonp'
	}).done(function(location){
		if( location.resourceSets[0].resources[0] && location.resourceSets[0].resources[0].address && location.resourceSets[0].resources[0].address.formattedAddress ) {
			$("#mapaddress").text(location.resourceSets[0].resources[0].address.formattedAddress);
			$("#coordinate").text( mapcoor );
		}
	
	});

}

$("input#zoomlevel").spinner({
max: 18, 
min: 2,
create: function( event, ui ) {
	mapzoom = $("input#zoomlevel").spinner("value") ;
	setmap( );
},
change: function( event, ui ) {
	mapzoom = $("input#zoomlevel").spinner("value") ;
	setmap( );
},
spin: function( event, ui ) {
	mapzoom = ui.value;
	setmap();
},
});
		

$("select.cam").change(function(){
	var name = $(this).attr("name") ;
	var cam = name.substr( 0, name.length - 3 );
	
	var s = $("select[name='"+cam+"cam']") ;
	var len = 20 ;
	var v = s.val();
	for( var ch = 0 ; ch < tagchannels.length; ch++ ) {
		if( v == tagchannels[ch].name ) {
			len = tagchannels[ch].videolen ;
			break;
		}
	}

	var tagname = selectedtag ;
	var channel = s.val();
	var mp4 = "drivebyvideo.php?tag=" + tagname +"&channel="+channel;
	$("video[name='"+ name +"']").attr("src", mp4);
	$("video[name='"+ name +"']")[0].oncanplay=function(){
		var video = $("video[name='"+ name +"']")[0] ;
		video.oncanplay=null ;
		video.currentTime = len/2 ;
	}
});
	
$("select[name='vidcam']").change(function(){
	var tagname = selectedtag ;
	var channel = $("select[name='vidcam']").val();
	var mp4 = "drivebyvideo.php?tag=" + tagname +"&channel="+channel;
	$("#avivideo").attr("src", mp4);
});

$("#rcontainer").show('slow');

});

</script>
</head>
<body>
<div id="container">
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span><?php echo $product_name . "  " .  $_SESSION['release']; ?></span></span></span></div>

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
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<?php if( !empty($support_driveby) && ( $_SESSION['user_type'] == "operator" || $_SESSION['user'] == "admin" ) ){ ?>
	<li><a class="lmenu" href="driveby.php"><img onmouseout="this.src='res/side-driveby-logo-clear.png'" onmouseover="this.src='res/side-driveby-logo-fade.png'" src="res/side-driveby-logo-clear.png" /> </a></li>
	<?php } ?>
	<?php if( !empty($support_emg) ) { ?>
	<li><a class="lmenu" href="emg.php"><img src="res/side-emg-logo-green.png" /> </a></li>
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
<strong><span style="font-size:26px;">Emergency Events</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">

<!-- work area --->
<p/>

<div>
<table id="tag_list"></table> 
<div id="tag_list_pager"></div>
</div>
<p>
<button id="Video">Play Video</button>
<button id="showmap">Show Map</button>
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
<!--
<button id="showPanic">Panic Event</button>
<button id="showCrash">Crash Event</button>
-->
</p>
<p/>

</div>

<!-- Video Playback Dialog -->
<div class="dialog" title="Emergency Videos" id="dialog_emgvideo">
	<video id="emgvideo" width="640" height="480" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
	<br/>
	<div>Select camera: <select name="emgvideocam"></select></div>
</div>

<!-- Video Playback Dialog -->
<div class="dialog" title="Emergency Location" id="dialog_map">
	<div id="emgmap" width="640" height="480" />
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