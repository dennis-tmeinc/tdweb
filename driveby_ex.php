<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
?>
	<title>Drive By Event</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2021-04-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><link rel="stylesheet" href="//code.jquery.com/ui/<?php echo $jquiver; ?>/themes/base/jquery-ui.css"><script src="https://code.jquery.com/jquery-<?php echo $jqver; ?>.js"></script><script src="https://code.jquery.com/ui/<?php echo $jquiver; ?>/jquery-ui.js"></script>
	<script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css">
		#rcontainer { display:none }
	</style>	
<script>
// start up

$(document).ready(function(){
		
$("button").button();	
$(".btset").controlgroup();
$(".btset input").checkboxradio({
      icon: false
    });
$(".btset input").change(function(){
   location=$(this).attr("href");
});

var selectedtag = "" ;
var tagchannels = {};

// Event Tag list
$("#tag_list").jqGrid({        
    scroll: true,
	datatype: "local",
	height: 142,
	width: 480,
	multiselect: true ,
	caption: 'Drive By Event Tags',
    colNames:['Client ID', 'Bus ID', 'Date/Time'],
    colModel :[ 
      {name:'clientid', index:'clientid', width:180, sortable: true }, 
      {name:'vehicle', index:'vehicle', width:180, sortable: true }, 
      {name:'datetime', index:'datetime', width:180, sortable: true } 
    ],
	
	onSelectRow: function(id){ 
		if( id && id!==selectedtag ) {
			var param = {} ;
			param.tag = id ;
			param.lasttag = selectedtag ;
			param.plateofviolator = $("input[name='plateofviolator']").val();
			param.notes = $("textarea[name='notes']").val();
			// update selectedtag
			selectedtag = id ;
			$.getJSON("drivebytaglock.php", param, function(resp){
				if( resp.res == 1 ) {
					// set notes
					if( resp.tag.plateofviolator && !jQuery.isEmptyObject(resp.tag.plateofviolator) )
						$("input[name='plateofviolator']").val(resp.tag.plateofviolator);
					else 
						$("input[name='plateofviolator']").val("");

					if( resp.tag.notes && !jQuery.isEmptyObject(resp.tag.notes) )
						$("textarea[name='notes']").val(resp.tag.notes);
					else 
						$("textarea[name='notes']").val("");
					
					// set map
					mapcoor = resp.tag.coordinate ;
					setmap();
					
					tagchannels = resp.tag.channel ;
					
					// setup camera select
					var camopts = [] ;
					var camopt = "" ;
					for( var ch = 0 ; ch < tagchannels.length; ch++ ) {
						// set lpr1
						var name = "camera"+(ch+1) ;
						if( tagchannels[ch].name ) {
							name = tagchannels[ch].name ;
						}
						camopts[ch] = name ;
						camopt += "<option>"+name+"</option>" ;
					}
					
					var sel = $("select[name='vidcam']")[0].selectedIndex ;
					if( sel >= tagchannels.length || sel < 0 ) {
						sel = 0 ;
					}
					
					// video camera selection
					$("select[name='vidcam']").html(camopt);
					$("select[name='vidcam']")[0].selectedIndex = sel ;
					$("select[name='vidcam']").change();

					var sels = ['lpr1','lpr2','ov1','ov2'] ;
					
					// camera selection
					var i;
					for( i= 0 ; i<sels.length; i++ ) {
						var camname = sels[i]+"cam" ;
						var s = $("select[name='"+camname+"']") ;
						sel = s[0].selectedIndex ;
						if( sel < 0 ) {
							sel = i ;
						}
						if( sel >= tagchannels.length ) {
							sel = 0 ;
						}
						s.html(camopt);
						s[0].selectedIndex = sel ;
						s.change();
					}
					
				}
				else {
					alert("Can not process this tag, it is being used by other user");
				}
			});	

		}
	}
});

(function load_taglist()
{
	$.getJSON("drivebytaglist.php", function(resp){
		if( resp.res == 1 ) {
			$("#tag_list").jqGrid("clearGridData");
			var griddata = [] ;
			for(var i=0;i<resp.tags.length;i++) {
				griddata[i] = { tagname: resp.tags[i].tagname,
						  clientid: resp.tags[i].clientid,
						  vehicle: resp.tags[i].vehicle,
						  datetime: resp.tags[i].datetime };
			}
			$("#tag_list").jqGrid('addRowData','tagname',griddata);		
			$("#tag_list").jqGrid('setGridParam',{sortname:'datetime'}).trigger('reloadGrid');
		}
	});	
})();

// initialize Notes dialog
$( ".dialog#dialog_editcontacts" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
	},
	close: function( event, ui ) {
	},
	buttons:{
		"OK": function() {
			$( this ).dialog( "close" );
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$("button#EditContacts").click( function() {
	$( ".dialog#dialog_editcontacts" ).dialog("open");
} );

$("button#GenerateReport").click( function() {
	var param = {} ;
	param.tag = selectedtag ;
	param.plateofviolator = $("input[name='plateofviolator']").val();
	param.notes = $("textarea[name='notes']").val();
	param.mapzoom = mapzoom ;
	var sels = ['lpr1','lpr2','ov1','ov2'] ;
	// cameras
	var i;
	for( i= 0 ; i<sels.length; i++ ) {
		var camname = sels[i]+"cam" ;
		param['ch'+i] = $("select[name='"+camname+"']").val();
		//param['pos'+i] = $("div#"+sels[i]+"slider").slider("value")/30;
		$("video[name='"+ camname +"']")[0].pause();
		param['pos'+i] = $("video[name='"+ camname +"']")[0].currentTime ;
	}
	window.open("drivebymkpdf.php?"+$.param(param));
});

// button delete event
$("button#deleteevent").click( function() {
	var tags = $("#tag_list").jqGrid('getGridParam', 'selarrrow') ;
	var param = {} ;
	param.tag = tags ;
	$.getJSON("drivebytagdelex.php", param, function(resp){
		if( resp.res == 1 ) {
			var i ; for( i=0; i<tags.length ; i++ ) {
				$("#tag_list").jqGrid('delRowData', tags[i]);	
			}
			selectedtag = "" ;
			$("#tag_list").jqGrid('setGridParam',{sortname:'datetime'}).trigger('reloadGrid');
		}
	}) ;
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
		
// load frames images
//   sections: lpr1, lpr2, ov1, ov2
//   time: offset from begin
//   channel: camera channel
function img_frame( section, time, channel )
{
	var tagname = selectedtag ;
	var img = "drivebyframe.php?tag=" + tagname +"&channel="+channel+"&time="+time;
	$("img[name='"+section+"img']").prop("src", img);
	var min = Math.floor(time/60) ;
	var sec = (time%60).toFixed(1) ;
	if( sec<10 ) sec = "0"+sec ;
	$("span#"+section+"time").text( ""+min+":"+sec );
}
		
$(".slider").slider({
min:0,
max:500,
change:function(event,ui){
	var s = $(this).attr("name") ;
	s = s.substr(0, s.length-6 ) ;
	var ch = $("select[name='"+s+"cam']").val();
	img_frame( s, ui.value/30, ch );
},
slide:function(event,ui){
	var s = $(this).attr("name") ;
	s = s.substr(0, s.length-6 ) ;
	var ch = $("select[name='"+s+"cam']").val();
	img_frame( s, ui.value/30, ch );	
}
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

$("button.step").on('click',function(){
	var name = $(this).attr("name") ;
	var dir = name.substr( name.length - 8 ) ;
	var s = name.substr( 0, name.length - 8 );
	
	var xslider = $("div#"+s+"slider");
	var sv = xslider.slider("value");
	if( dir=="stepback" ) {
		sv-=1 ;
	}
	else {
		sv+=1 ;
	}
	xslider.slider("value",sv);
	

	var step ;
	if( dir=="stepback" ) {
		step = -(1.0/30) ;
	}
	else {
		step = (1.0/30) ;
	}
	
	var videoname = s+"cam" ;
	$("video[name='"+ videoname +"']")[0].pause();
	$("video[name='"+ videoname +"']")[0].currentTime += step ;
	
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
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<li><img src="res/side-driveby-logo-green.png" /></li>
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
<strong><span style="font-size:26px;">DRIVE BY</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">
<p class="btset">
<input name="btset" href="driveby.php" id="btdriveby" type="radio" checked="checked" /><label for="btdriveby">Drive By Event Process</label>
<input name="btset" href="drivebyreview.php" id="btdrivebyreview" type="radio"  /><label for="btdrivebyreview">Drive By Event Review</label> 
</p>

<!-- work area --->
<table border="0" cellpadding="1" cellspacing="10" style="width:500px">
<tr>
<td>
<div>
<table id="tag_list"></table> 
</div>
</td>
<td>
<p>Plate of Violator:</p>
<input name="plateofviolator" type="text" />
<p>Notes:</p>
<textarea name="notes" maxlength="100" style="margin: 2px; width: 180px; height: 80px;"></textarea>
<button id="GenerateReport">Generate Report</button>
<button id="deleteevent">Delete Event</button>
<!-- <button style="width:180px" id="EditContacts">Edit Contacts</button> -->
</td>
</tr>
</table>

<table border="0" cellpadding="2" cellspacing="4" style="width:500px">
	<tbody>
		<tr>
			<td>
			<video id="lpr2cam" name="lpr1cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>LPR1:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam" name="lpr1cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="lpr1stepback"><span class="ui-icon ui-icon-seek-prev"></span></button><button class="step" name="lpr1stepforw"> <span class="ui-icon ui-icon-seek-next"></span></button>
			</div>
			</td>
			
			<td>
			<video id="lpr2cam" name="lpr2cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>LPR2:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="lpr2cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="lpr2stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="lpr2stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</div>
			</td>
			
			<td>
			<video id="avivideo" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<div>Video Clip:&nbsp; &nbsp;&nbsp;&nbsp;
			<select name="vidcam"></select></div>
			</td>			
		</tr>
		<tr>
			<td>
			<video id="ov1cam" name="ov1cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>OV1:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="ov1cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="ov1stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="ov1stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</div>
			</td>
			
			<td>
			<video id="ov2cam" name="ov2cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>OV2:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="ov2cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="ov2stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="ov2stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</div>
			</td>
			
			<td>
			<img id="mapimage" alt="MAP NOT AVAILABLE" src="" />
			<div>MAP:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;<label for="spinner">Zoom Level: </label><input id="zoomlevel" value="15"></div>
			<p>Address: <span id="mapaddress"></span></p>
			<p>Coordinate: <span id="coordinate"></span></p>
			</td>			
		</tr>

	</tbody>
</table>

<p/>

</div>

<!-- Edit Contacts Dialog -->
<div class="dialog" title="Contacts" id="dialog_editcontacts">

</div>

<!-- workarea --></div>
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