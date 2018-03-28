<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
session_save('lastpage', $_SERVER['REQUEST_URI'] );

// MySQL connection
if( $logon ) {
	@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
}
// clear video filter
session_save('videofilter','');
	
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-24">		
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.4/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
      #search {
        font-size:20px;
      }
	  
select#webplay_camera {
	min-width: 100px ;
    border-radius: 4px;
    box-shadow: 1px 1px 5px #cfcfcf inset;
    border: 1px solid #cfcfcf;
    vertical-align: middle;
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

$(".btset").buttonset();	
$("button").button();	

$(".btset input").change(function(){
   location=$(this).attr("href");
});		            

$( ".datepicker" ).datepicker({
 dateFormat: "yy-mm-dd" 
});

$("#videolist").jqGrid({
    scroll: <?php echo (empty($grid_scroll)||(!$grid_scroll))?'0':'1'; ?>,
    rowNum:100,
    rowList:[20, 50, 100, 200],
    url:'videogrid.php',
    datatype: 'json',
    mtype: 'GET',
    colNames:['Vehicle Name','Date&Time', 'Duration','Filename','Description'],
    colModel :[ 
      {name:'vehicle_name', index:'vehicle_name', sortable: true, width:100}, 
      {name:'time_start', index:'time_start', width:110, sortable: true}, 
      {name:'duration', index:'duration', width:70, sortable: true , align:'right'}, 
      {name:'path', index:'path', width:230, sortable: true }, 
      {name:'Description', index:'Description', width:120, sortable:false} 
    ],
    pager: '#videopager',
	height: '380',
	width: '768',
	rownumbers: true,
	rownumWidth: 50,
    sortname: 'vehicle_name,time_start ',
    sortorder: 'desc',
    viewrecords: true,
    gridview: true,
    caption: 'Video Clips List'
}); 


$( ".tdcdialog#dialog_delete" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Yes": function() {
			var yesfunc = $( ".tdcdialog#dialog_delete" ).data( "yesfunction"); 
			if( yesfunc ) {
				if( yesfunc(this) ) {
					$( this ).dialog( "close" );
				}
			}
			else {
				$( this ).dialog( "close" );
			}
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$('#deletevideo').click(function(e){
	e.preventDefault();
	var id=$("#videolist").jqGrid('getGridParam','selrow') ;
	if( id == null ) {
		alert("Please select one video clip!");
		return ;
	}
	$( ".tdcdialog#dialog_delete #deletemsg" ).text("Please confirm to delete this video clip?") ;
	$( ".tdcdialog#dialog_delete #deletename" ).text( $("#videolist").jqGrid('getCell', id, 4 ) );
	$( "#dialog_vehicle" ).dialog( "option", "title", "Delete Video Clip?" );
	$( ".tdcdialog#dialog_delete" ).data("yesfunction", function(){
		var fdata=new Object ;
		fdata.index=id ;
		$.getJSON("videoclipdel.php", fdata, function(resp){
			if( resp.res==1 ) {
				$("#videolist").trigger("reloadGrid");
			}
			else if( resp.errormsg ) {
				alert( resp.errormsg );
			}
			else {
				alert("Delete Video Clips Failed!");
			}
		});
		return true ;
	});
	$( ".tdcdialog#dialog_delete" ).dialog("open");
});

$('#selectallgroup').click(function(e){
	e.preventDefault();
	$("select[name='groups[]'] option").prop("selected",true);
	$("select[name='vehicles[]'] option").prop("selected",false);
});

$('#selectallvehicle').click(function(e){
	e.preventDefault();
	$("select[name='vehicles[]'] option").prop("selected",true);
	$("select[name='groups[]'] option").prop("selected",false);
});

$("select[name='vehicles[]']").change(function(e){
	$("select[name='groups[]'] option").prop("selected",false);
});
$("select[name='groups[]']").change(function(e){
	$("select[name='vehicles[]'] option").prop("selected",false);
});

$('#videosearchform').submit(function() {
	var tablerequest=$(this).serialize();
	$.getJSON("videosearch.php", tablerequest, function(vclips){
		$("#videolist").clearGridData().trigger("reloadGrid");
	}).fail(function(jqXHR, textStatus) { 
		console.log( "error" ); 
	});
	return false ;
});

if( navigator.platform == 'Win32' || navigator.platform == 'Win64' )  {
	$("button#playvideo").click(function(e){
		e.preventDefault();
		var id=$("#videolist").jqGrid('getGridParam','selrow') ;
		if( id == null ) {
			alert("Please select one video clip!");
			return ;
		}
		$("#formplayvideo input[name='index']").val(id);
		$("#formplayvideo input[name='vehicle_name']").val($("#videolist").getCell(id,1));
		$('#formplayvideo').submit();
	});
}
else {
	$("button#playvideo").hide();
	$("#playsync").hide();
	$("#downloadplayer").hide();	
}

$("#playsync").button();
$("#downloadplayer").button();

var webplayser=1 ;
var webplay_clip ;
var webplay_playtime = 0;

function webplay_settitle()
{
	var ptime = $( "video#webplay" )[0].currentTime ;
	if( Math.abs( ptime - webplay_playtime ) < 1 ) {
		return ;
	}
	webplay_playtime = ptime ;

	var clipinfo = $( "video#webplay" ).data("clipinfo") ;
	var dt = clipinfo.time_start.split(" "); 
	var d = dt[0].split("-");
	var t = dt[1].split(":");
	var dt = new Date( d[0], d[1]-1, d[2], t[0], t[1], t[2], 0 );
	var start_time = dt.getTime() ;
	
	var dt = new Date( start_time + webplay_playtime * 1000 ) ;
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
	var dstr = dyear + "-" + dmon + "-" + ddate + ' ' + dhour + ":" + dmin + ":" + dsec ;

	$( ".tdcdialog#dialog_webplay" ).dialog("option", "title", clipinfo.vehicle_name + " - " + clipinfo.camera_name[ clipinfo.channel ] + "   " + dstr );	
}

function webplay_play()
{
	var clipinfo = $( "video#webplay" ).data("clipinfo") ;
	$( "video#webplay" )[0].onended = function(){
		if( $( "video#webplay" )[0].currentTime > 2 ) {
			$("button#webplay_next").click();
		}
	}
	webplay_playtime = -1 ;
	webplay_settitle();
	$( "video#webplay" )[0].ontimeupdate = webplay_settitle ;
	$( "video#webplay" )[0].src = clipinfo.mp4  ;
	$( "video#webplay" )[0].play();
//	$( "video#webplay" )[0].load();
}

var webplay_1open=0 ;

$( ".tdcdialog#dialog_webplay" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	beforeClose: function( event, ui ) {
		// $( "video#webplay" )[0].src = "" ;
		$( "video#webplay" )[0].autoplay=false;
		$( "video#webplay" )[0].pause();	
	},
	resize: function( event, ui ) {
		$( "video#webplay" )[0].width=$( "div#dialog_webplay" ).width() - $( ".tdcdialog#dialog_webplay" ).data("wdif");
		$( "video#webplay" )[0].height=$( "div#dialog_webplay" ).height() - $( ".tdcdialog#dialog_webplay" ).data("hdif");
	},
	open: function( event, ui ) {
		if( !webplay_1open ) {
			var wdif = $( "div#dialog_webplay" ).width() - $( "video#webplay" )[0].width ;
			var hdif = $( "div#dialog_webplay" ).height() - $( "video#webplay" )[0].height ;
			$( ".tdcdialog#dialog_webplay" ).data("wdif", wdif );
			$( ".tdcdialog#dialog_webplay" ).data("hdif", hdif );
			webplay_1open = 1 ;
		}
		else {
			$( "video#webplay" )[0].width=$( "div#dialog_webplay" ).width() - $( ".tdcdialog#dialog_webplay" ).data("wdif");
			$( "video#webplay" )[0].height=$( "div#dialog_webplay" ).height() - $( ".tdcdialog#dialog_webplay" ).data("hdif");
		}
		var clipinfo = $( "video#webplay" ).data("clipinfo") ;
		var options = "" ;
		for( var ci=0; ci<clipinfo.camera_number; ci++ ) {
			options += "<option>"+clipinfo.camera_name[ci]+"</option>" ;
		}
		$("select#webplay_camera").html(options);
		$("select#webplay_camera")[0].selectedIndex=clipinfo.channel ;
		webplay_play();
	},
	create: function( event, ui ) {
		$("select#webplay_camera").change( function(){
			var clipinfo = $( "video#webplay" ).data("clipinfo") ;
			var param=new Object ;
			param.dir = 3 ;
			param.vehicle_name = clipinfo.vehicle_name ;
			param.time_start = clipinfo.time_start ;
			param.channel = $("select#webplay_camera")[0].selectedIndex ;
			wait(1);
			$.getJSON("webplay.php", param , function(resp){
				wait(0);
				if( resp.res == 1 ) {
					$( "video#webplay" ).data("clipinfo", resp );
					webplay_play();
				}
			});
		});	
		$("button#webplay_prev").click(function(){
			var clipinfo = $( "video#webplay" ).data("clipinfo") ;
			var param=new Object ;
			param.dir = 2 ;
			param.vehicle_name = clipinfo.vehicle_name ;
			param.time_start = clipinfo.time_start ;
			param.channel = clipinfo.channel ;
			wait(1);
			$.getJSON("webplay.php", param , function(resp){
				wait(0);
				if( resp.res == 1 ) {
					$( "video#webplay" ).data("clipinfo", resp );
					webplay_play();
				}
			});		
		});
		$("button#webplay_next").click(function(){
			var clipinfo = $( "video#webplay" ).data("clipinfo") ;
			var param=new Object ;
			param.dir = 1 ;
			param.vehicle_name = clipinfo.vehicle_name ;
			param.time_start = clipinfo.time_start ;
			param.channel = clipinfo.channel ;
			wait(1);
			$.getJSON("webplay.php", param , function(resp){
				wait(0);
				if( resp.res == 1 ) {
					$( "video#webplay" ).data("clipinfo", resp );
					webplay_play();
				}
			});		
		});

		$("button#webplay_reload").click(function(){
			webplay_play();	
		});
		$("button#webplay_close").click(function(){
			$( ".tdcdialog#dialog_webplay" ).dialog( "close" );
		});
	}
});

function wait( w )
{
    if( w ) {
		$("body").append('<div class="wait"></div>');
	}
	else {
		$("div.wait").remove();
	}
}

$("button#webplay").click(function(){
	var id=$("#videolist").jqGrid('getGridParam','selrow') ;
	if( id == null ) {
		alert("Please select one video clip!");
		return ;
	}
	webplayser++ ;
	wait(1);
	$.getJSON("webplay.php?index="+id+"&ser="+webplayser, function(resp){
		wait(0);
		if( resp.res == 1 && resp.ser == webplayser ) {
			$( "video#webplay" ).data("clipinfo", resp );
			$( ".tdcdialog#dialog_webplay" ).dialog("open");
		}
	});
	return ;
});

$("#rcontainer").show('slow' );
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
	<li><img src="res/side-videos-logo-green.png" /></li>
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
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
<strong><span style="font-size:26px;">VIDEOS</span></strong> </div>

<div id="rcontainer">
<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">

<h3 style="text-align: center;">Search Video</h3>

<form id="videosearchform" enctype="application/x-www-form-urlencoded" method="get" action="videosearch.php" >
<fieldset><legend>Date Range</legend>

<table>
<tr><td>From:</td><td><input class="datepicker" id="fromdate" name="fromdate" size="12" type="text" value="<?php $da=new DateTime(); $da->sub(new DateInterval('P1M')); echo $da->format('Y-m-d'); ?>" /></td></tr>
<tr><td>To:</td><td><input class="datepicker" id="todate" name="todate" size="12" type="text" value="<?php $da=new DateTime(); echo $da->format('Y-m-d'); ?>" /></td></tr>
</table>
</fieldset>

<fieldset><legend>Select Vehicles</legend>
<div>
Groups &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button id="selectallgroup">Select All</button>
<br/>
<select multiple="true" name="groups[]" size="8" style="min-width:13em;" > <?php 
  $sql="SELECT `name` FROM vgroup ORDER BY `name ;" ;
  if( $result=$conn->query($sql) ) {
	while( $row = $result->fetch_array() ) { 
		echo "<option>$row[name]</option>" ;
	}
	$result->free();
  }
 ?> </select>
 <br/>
Vehicles &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button id="selectallvehicle">Select All</button>
<br/>
<select multiple="true" name="vehicles[]" size="8" style="min-width:13em;"> <?php 
  $sql="SELECT vehicle_name FROM vehicle ORDER BY vehicle_name;" ;
  if( $result=$conn->query($sql) ) {
	  while( $row = $result->fetch_array() ) { 
		echo "<option>$row[0]</option>" ;
	  }
	  $result->free();
  }
 ?> </select>
 </div>
</fieldset>
<p style="text-align: center;"><button  id="search" type="submit">Search</button></p>
</form>
</div>

<div id="workarea" style="width:auto;">
  
<p class="btset">
  <input  checked="checked" href="videos.php" id="btvideo"   name="btset"  type="radio" /><label for="btvideo"> Browse &amp; Manage Video </label> 
  <input  href="videosrequest.php" id="btvideoreq"  name="btset" type="radio" /><label for="btvideoreq"> Request Video Clips </label>
</p>
  
<h4>Browse &amp; Manage Videos</h4>

<div id="vlist" style="overflow:auto;">
<table id="videolist"></table> 
<div id="videopager"></div> 
<p>
<form id="formplayvideo" enctype="application/x-www-form-urlencoded" method="get" action="playvideo.php" target="_blank" >
<input name="index" type="hidden" />
<input name="vehicle_name" type="hidden"  />
</form>
<button id="deletevideo"><img src="res/button_delete.png" > Delete </button>
<button  id="playvideo"><img src="res/button_play.png" > Play </button>
<?php if( ! empty( $webplay_support ) ) { ?>
<button  id="webplay"> Preview Video Clip </button>
<?php } ?>
<a  id="playsync" href="mapview.php?sync=1" >Sync on Map View</a>
<a  id="downloadplayer" href="downloadplayer.php" target="_blank" >Download Player</a>
</p>
</div>

<!-- Generic Delete Dialog -->
<div class="tdcdialog" id="dialog_delete">
<p id="deletemsg">delete:</p>
<p id="deletename" style="text-align: center;">this</p>
<p>&nbsp;</p>
</div>
<!-- Video Clip Preview Dialog -->
<div class="tdcdialog" id="dialog_webplay">
<video id="webplay" width="480" height="360" src="" type="video/mp4" controls>
Your browser does not support the video tag.
</video> 
<hr />
<p style="text-align: right;">
<select id="webplay_camera"></select>
&nbsp;&nbsp;&nbsp;<button id="webplay_prev">Prev</button> <button id="webplay_next">Next</button> <button id="webplay_reload">Reload</button> <button id="webplay_close">Close</button></p>
</div>

</div>
<!-- workarea --></div>
<!-- mcontainer --></div>
<div id="push">
</div>
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
<?php $conn->close(); ?>