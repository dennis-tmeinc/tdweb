<!DOCTYPE html>
<?php 
require 'session.php'; 
?>
<html>
<head>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">			
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><link rel="stylesheet" href="https://libs.cdnjs.net/jqueryui/<?php echo $jquiver; ?>/themes/<?php echo $jqtheme; ?>/jquery-ui.min.css"><script src="https://libs.cdnjs.net/jquery/<?php echo $jqver; ?>/jquery.min.js"></script><script src="https://libs.cdnjs.net/jqueryui/<?php echo $jquiver; ?>/jquery-ui.min.js"></script>
	<script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
	<link rel="stylesheet" href="https://libs.cdnjs.net/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css"><script src="https://libs.cdnjs.net/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
	<link rel="stylesheet" href="https://libs.cdnjs.net/free-jqgrid/4.14.1/css/ui.jqgrid.min.css"><script src="https://libs.cdnjs.net/free-jqgrid/4.14.1/i18n/min/grid.locale-en.js"></script><script src="https://libs.cdnjs.net/free-jqgrid/4.14.1/jquery.jqgrid.min.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
		#request {
        font-size:20px;
      }
	</style>
	<script src="td_alert.js"></script><script>
// start up 
$(document).ready(function(){

$(".btset").controlgroup();	
$("button").button();	

$(".btset input").checkboxradio({
      icon: false
}).change(function(){
   location=$(this).attr("href");
});		            

$( "#datepicker" ).datepicker({
  dateFormat: "yy-mm-dd"
});

$("#requestlist").jqGrid({
    scroll: <?php echo (empty($grid_scroll)||(!$grid_scroll))?'0':'1'; ?>,
    url:'vmqeventgrid.php',
    datatype: 'json',
    mtype: 'GET',
    colNames:['Vehicle','Date', 'Duration','Event', 'Pre-Time', 'Post-Time', 'Complete'],
    colModel :[ 
      {name:'vehicleId', index:'vehicleId', sortable: true, width:100 },
	  {name:'datetime_start', index:'datetime_start', width:120, sortable: true}, 
      {name:'duration', index:'duration', width:80, sortable: true , align:'right'}, 
      {name:'event_code', index:'event_code', width:100, sortable: true }, 
      {name:'pre_time', index:'pre_time', width:50, sortable: false }, 
      {name:'post_time', index:'post_time', width:50, sortable: false},
	  {name:'complete', index:'complete',width:90, sortable: true}
    ],
    pager: '#requestpager',
	height: '380',
	width: '800',
	rownumbers: true,
	rownumWidth: 50,
    rowNum:100,
    rowList:[20, 50, 100, 200],
    sortname: 'datetime_start',
    sortorder: 'desc',
	multiselect: true,
    viewrecords: true,
    gridview: true,
    caption: 'Video Request List'
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

$('#deleterequest').click(function(e){
	e.preventDefault();
	var ids=$("#requestlist").jqGrid('getGridParam','selarrrow') ;
	if( ids == null || ids.length == 0 ) {
		alert("Please select one video clip!");
		return ;
	}
	var qa = "Please confirm to delete this video request?" ;
	if( ids.length>1 ) {
		qa = "Please confirm to delete these video requests?" ;
	}
	$( ".tdcdialog#dialog_delete #deletemsg" ).text(qa) ;
	var na = "<p>";
	for( var i=0; i<ids.length; i++) {
		if( i>4 ) {
			na += '...';
			break;
		}
		na += $("#requestlist").jqGrid('getCell', ids[i], 2) + ' ' + $("#requestlist").jqGrid('getCell', ids[i], 3) ;
        na += '<br/>';
	}
	na += "</p>";
	$( ".tdcdialog#dialog_delete #deletename" ).html( na );
	$( "#dialog_vehicle" ).dialog( "option", "title", "Delete Video Clip?" );
	$( ".tdcdialog#dialog_delete" ).data("yesfunction", function(){
		var fdata=new Object ;
		fdata.ev_id=ids ;
		$.getJSON("vmqeventdel.php", fdata, function(resp){
			if( resp.res==1 ) {
				$("#requestlist").trigger("reloadGrid");
			}
			else if( resp.errormsg ) {
				alert( resp.errormsg );
			}
			else {
				alert("Delete Video Request Failed!");
			}
		});
		return true ;
	});
	$( ".tdcdialog#dialog_delete" ).dialog("open");
});

$('form').submit(function() {
	$.getJSON("vmqeventsave.php", $('form').serializeArray(), function(resp){
		if( resp.res == 1 ) {
			$("#requestlist").trigger("reloadGrid");
		}
		else if( resp.errormsg ) {
			alert( resp.errormsg );
		}
		else {
			alert("Video request failed!");
		}
	});
	
	return false ;
});

$( "select[name='ev_vehicle_name']" ).on( "change", function() {
	var vehicle = $( "select[name='ev_vehicle_name']" ).val();
	var param = { 'vehicle': vehicle };
	$.getJSON("cameralist.php", param, function(resp){
		var options="" ;
		if( resp.res == 1 && resp.cameras ) {
			for( i=0; i<resp.cameras.length; i++ ) {
				options += "<option selected value=\"" + resp.cameras[i] + "\"> Camera " + resp.cameras[i] + "</option>" ;
			}
		}
		$( "select#ev_camera" ).html( options );
		
	});
});

$("#rcontainer").show('slow');
});
	

</script>
</head>
<body><div id="container">
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
	<li><a class="lmenu" href="dashboardlive.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><img src="res/side-videos-logo-green.png" /></li>
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
<strong><span style="font-size:26px;">VIDEOS</span></strong> </div>

<div id="rcontainer">
<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">

<h3 style="text-align: center;">Request Video Clip</h3>

<form action="javascript:mapview();" id="mapviewform">

<fieldset><legend>Select Vehicle</legend>

<div>Vehicles &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</div>

<div><select name="ev_vehicle_name" size="8" style="min-width:90%;"> <?php 
  $sql="SELECT vehicle_name FROM vehicle ORDER BY vehicle_name;" ;
  $result=$conn->query($sql);
  while( $row = $result->fetch_array() ) { 
    echo "<option>" ;
    echo $row[0] ;
	echo "</option>" ;
  }
 ?> </select></div>
 <div>Select Cameras:</div>
<select id='ev_camera' name="ev_camera[]" multiple size="8" style="min-width:90%;"></select>
</fieldset>

<fieldset><legend>Events</legend>
<select name="event" id="event">
	<option value="17">Bus Stop</option>
	<option value="4">Idling</option>
	<option value="103">Speeding</option>
	<option value="104">Quick Acceleration</option>
	<option value="105">Hard Break</option>
</select>
<p/>
<label for="pre_time">Pre-time: </label>
<input type="number" id="pre_time" name="pre_time" value="60" min="1" max="60">s
<br/>
<label for="post_time">Post-time: </label>
<input type="number" id="post_time" name="post_time" value="60" min="1" max="60">s
</fieldset>
<fieldset><legend>Date Range</legend>
<table>
<tr>
<td>Date:</td><td><input id="datepicker" size="18" name="ev_start_time" type="text" value="<?php $da=new DateTime(); $da->sub(new DateInterval('P1D')); echo $da->format('Y-m-d'); ?>"/></td>
</tr>
<tr>
<td>Duration:</td><td><input name="ev_duration" size="5" type="number" value="1" min="1" max="999" /> days</td>
</tr>
</table>
</fieldset>

<p style="text-align: center;">
<button id="request" type="submit">Submit</button>
</p>
</form>
</div>

<div id="workarea" style="width:auto;">

<p class="btset">
  <input   name="btset" href="videos.php" id="btvideo" type="radio" /><label for="btvideo"> Browse &amp; Manage Video </label> 
<?php if( !empty($support_videoviacellular) ) { ?>
  <input   name="btset" href="videosrequest.php" id="btvideoreq" type="radio" /><label for="btvideoreq"> Request Video Clips Via WiFi </label>
  <input   name="btset" href="videosrequestcell.php" id="btvideoreqcell" type="radio" /><label for="btvideoreqcell"> Request Video Clips Via Cellular </label>
<?php } else { ?>
  <input   name="btset" href="videosrequest.php" id="btvideoreq" type="radio" /><label for="btvideoreq"> Request Video Clips </label>
<?php } ?>
  <input   name="btset" href="videosrequestevent.php" checked="checked" id="btvideoreqevent" type="radio" /><label for="btvideoreqevent"> Request Video Clips on Events </label>
</p>
  
<h4>Request Video Clips</h4>

<div id="tablecontainer">

<table id="requestlist"></table> 
<div id="requestpager"></div> 
<div>
<button id="deleterequest"><img src="res/button_delete.png" />Delete</button>
</div>

</div>
<p>&nbsp;</p>
<!-- Generic Delete Dialog -->
<div class="tdcdialog" id="dialog_delete">
<p id="deletemsg">delete:</p>
<p id="deletename" style="text-align: center;">this</p>
<p>&nbsp;</p>
</div>
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