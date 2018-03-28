<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
?>
	<title>Drive By Event</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.4/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script>
	<script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
	<script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css">
		#rcontainer { display:none }
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

var lasttagid = -2 ;
var tagfile = {} ;

// Event Tag list
$("#tag_list").jqGrid({        
    scroll: true,
	datatype: "local",
	height: 138,
	width: 750,
	caption: 'Drive By Event Tags',
    colNames:['Tag Name','Client ID', 'Bus ID', 'Date/Time'],
    colModel :[ 
      {name:'tagname', index:'tagname', width:180, sortable: true }, 
      {name:'clientid', index:'clientid', width:180, sortable: true }, 
      {name:'vehicle', index:'vehicle', width:180, sortable: true }, 
      {name:'datetime', index:'datetime', width:180, sortable: true } 
    ],
	
	onSelectRow: function(id){ 
		if( id && lasttagid!=-1 && id!==lasttagid ) {
			
			var tagname = $("#tag_list").jqGrid('getCell', id, 0 );
			$.getJSON("drivebytaglock.php?tag="+tagname, function(resp){
				if( resp.res == 1 ) {
					lasttagid = id ;
					tagfile = resp.tag ;

					// set map
					if( resp.tag.coordinate ) {
						mapcoor = resp.tag.coordinate ;
						setmap();
					}
					// setup camera select
					var camopts = [] ;
					var camopt = "" ;
					for( var ch = 0 ; ch < resp.tag.channel.length; ch++ ) {
						// set lpr1
						var name = "camera"+(ch+1) ;
						if( resp.tag.channel[ch].name ) {
							name = resp.tag.channel[ch].name ;
						}
						camopts[ch] = name ;
						camopt += "<option>"+name+"</option>" ;
					}
					
					// video camera selection
					$("select[name='vidcam']").html(camopt);
					$("select[name='vidcam']").val( camopts[0] );

					var sels = ['lpr1','lpr2','ov1','ov2'] ;
					
					// selects
					var i;
					for( i= 0 ; i<sels.length; i++ ) {
						var s = $("select[name='"+sels[i]+"cam']") ;
						var xv = s.val();
						s.html(camopt);
						if( xv ){
							s.val( xv );
						}
						else {
							s.val( camopts[i] );
						}
					}
					
					for( i= 0 ; i<sels.length; i++ ) {
						$("div#"+sels[i]+"slider").slider("option","max",20*30);
						$("div#"+sels[i]+"slider").slider("value", 0);
					}
				}
				else {
					alert("Can not process this tag, it is being used by other user");
				}
			});	
			// clear all images ...
			lasttagid = -1 ;
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
			$("#tag_list").jqGrid('addRowData',1,griddata);		
		}
	});	
})();

// current zoom level
var mapzoom = 16 ;
var mapcoor = "37.778297,-122.417297" ;
	
function setmap() 
{
	var mapimg = "http://dev.virtualearth.net/REST/v1/Imagery/Map/Road/"+mapcoor+"/"+mapzoom+"?pp="+mapcoor+";0&ms=480,360&key="+
			<?php echo "'$map_credentials'"; ?>	;
	$("img#mapimage").prop("src", mapimg );
	$.ajax({
		url : "http://dev.virtualearth.net/REST/v1/Locations/"+mapcoor ,
		data : {o:"json",key:<?php echo "'$map_credentials'"; ?>},
		dataType : 'jsonp',	jsonp :'jsonp'
	}).done(function(location){
		if( location.resourceSets[0].resources[0] && location.resourceSets[0].resources[0].address && location.resourceSets[0].resources[0].address.formattedAddress ) {
			$("#mapaddress").text(location.resourceSets[0].resources[0].address.formattedAddress);
			$("#coordinate").text( mapcoor );
		}
	
	});

}

// frames images
//   sections: lpr1, lpr2, ov1, ov2
//   time: offset from begin
//   channel: camera channel
function img_frame( section, time, channel )
{
	var tagname = $("#tag_list").jqGrid('getCell', lasttagid, 0 );
	var img = "drivebyframe.php?tag=" + tagname +"&channel="+channel+"&time="+time;
	$("img[name='"+section+"img']").prop("src", img);
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
	$("#"+cam+"slider").slider("value",0);
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
});
	
$("select[name='vidcam']").change(function(){
	var tagname = $("#tag_list").jqGrid('getCell', lasttagid, 0 );
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
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<?php if( !empty($enable_driveby) ){ ?>
	<li><img src="res/side-driveby-logo-green.png" /></li>
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
<input href="dashboardmorning.php" name="btset" id="btmorning"
<?php 
	if( $title_type != 'Live ' ) {
		echo ' checked="checked" ' ;
	}
?>
type="radio" /><label for="btmorning"> Drive By Event Process </label> 
<input href="dashboardlive.php"    name="btset" id="btlive"  
<?php 
	if( $title_type == 'Live ' ) {
		echo ' checked="checked" ' ;
	}
?>
type="radio" /><label for="btlive"> Drive By Event Review </label> 
</p>

<!-- work area --->
<div>
<table id="tag_list"></table> 
</div>

<table border="0" cellpadding="1" cellspacing="18" style="width:500px">
	<tbody>
		<tr>
			<td>
			<p>LPR1:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam" name="lpr1cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="lpr1stepback"><span class="ui-icon ui-icon-seek-prev"></span> </button><button class="step" name="lpr1stepforw"> <span class="ui-icon ui-icon-seek-next"></span></button>
			</p>
			<img width="480" alt="" name="lpr1img" src="res/247logo.jpg" /><br/>
			<div class="slider" id="lpr1slider" name="lpr1slider" style="max-width:480px"></div>
			</td>
			
			<td>
			<p>LPR2:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="lpr2cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="lpr2stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="lpr2stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</p>
			<img width="480" alt="" name="lpr2img" src="res/247logo.jpg" /><br/>
			<div class="slider" id="lpr2slider" name="lpr2slider" style="max-width:480px"></div>
			</td>
			
			<td>
			<p>Video:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select name="vidcam"></select></p>
			<video id="avivideo" width="480" height="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			</td>			
		</tr>
		<tr>
			<td>
			<p>OV1:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="ov1cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="ov1stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="ov1stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</p>
			<img width="480" alt="" name="ov1img" src="res/247logo.jpg" /><br/>
			<div class="slider" id="ov1slider" name="ov1slider" style="max-width:480px"></div>
			</td>
			
			<td>
			<p>OV2:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="ov2cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="ov2stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="ov2stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</p>
			<img width="480" alt="" name="ov2img" src="res/247logo.jpg" /><br/>
			<div class="slider" id="ov2slider" name="ov2slider" style="max-width:480px"></div>
			</td>
			
			<td>
			<p>MAP:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;<label for="spinner">Zoom Level: </label><input id="zoomlevel" value="16"></p>
			<img id="mapimage" alt="MAP NOT AVAILABLE" src="" />
			<p id="mapaddress"></p>
			<p id="coordinate"></p>
			</td>			
		</tr>

	</tbody>
</table>


<button id="DriveByGenerate">Generate Event</button>
<p/>

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