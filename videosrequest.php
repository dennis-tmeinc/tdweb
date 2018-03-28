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
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-15">			
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script src="picker.js"></script>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
		#request {
        font-size:20px;
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
	});
}
touchdownalert();

$(".btset").buttonset();	
$("button").button();	

$(".btset input").change(function(){
   location=$(this).attr("href");
});		            

$( ".datetimepicker" ).datetimepicker({
	dateFormat: "yy-mm-dd",
	yearRange: "2000:2030",
	showTime: false ,
	timeFormat: "H:mm:ss"
});
			
$("#requestlist").jqGrid({
    scroll: <?php echo (empty($grid_scroll)||(!$grid_scroll))?'0':'1'; ?>,
    url:'vmqgrid.php',
    datatype: 'json',
    mtype: 'GET',
    colNames:['Vehicle','Date-Time', 'Duration','Status','Requested by', 'Description'],
    colModel :[ 
      {name:'vmq_vehicle_name', index:'vmq_vehicle_name', sortable: true, width:100}, 
      {name:'vmq_start_time', index:'vmq_start_time', width:150, sortable: true}, 
      {name:'duration', index:'duration', width:80, sortable: true , align:'right'}, 
      {name:'vmq_comp', index:'vmq_comp', width:100, sortable: true }, 
      {name:'vmq_ins_user_name', index:'vmq_ins_user_name', width:120, sortable: true }, 
      {name:'vmq_description', index:'vmq_description', width:240, sortable:false} 
    ],
    pager: '#requestpager',
	height: '380',
	width: '800',
	rownumbers: true,
	rownumWidth: 50,
    rowNum:100,
    rowList:[20, 50, 100, 200],
    sortname: 'vmq_start_time',
    sortorder: 'desc',
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
	var id=$("#requestlist").jqGrid('getGridParam','selrow') ;
	if( id == null ) {
		alert("Please select one video clip!");
		return ;
	}
	$( ".tdcdialog#dialog_delete #deletemsg" ).text("Please confirm to delete this video request?") ;
	$( ".tdcdialog#dialog_delete #deletename" ).text( $("#requestlist").jqGrid('getCell', id, 1) + '  ' + $("#requestlist").jqGrid('getCell', id, 2) );
	$( "#dialog_vehicle" ).dialog( "option", "title", "Delete Video Clip?" );
	$( ".tdcdialog#dialog_delete" ).data("yesfunction", function(){
		var fdata=new Object ;
		fdata.vmq_id=id ;
		$.getJSON("vmqdel.php", fdata, function(resp){
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
	$.getJSON("vmqsave.php", $('form').serializeArray(), function(resp){
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

$("#rcontainer").show('slow');
});
	

</script>
</head>
<body><div id="container">
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboardlive.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><img src="res/side-videos-logo-green.png" /></li>
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
<strong><span style="font-size:26px;">VIDEOS</span></strong> </div>

<div id="rcontainer">
<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">

<h3 style="text-align: center;">Request Video Clip</h3>

<form action="javascript:mapview();" id="mapviewform">

<fieldset><legend>Select Vehicle</legend>

<div>Vehicles &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</div>

<div><select name="vmq_vehicle_name" size="8" style="min-width:90%;"> <?php 
  $sql="SELECT vehicle_name FROM vehicle ORDER BY vehicle_name;" ;
  $result=$conn->query($sql);
  while( $row = $result->fetch_array() ) { 
    echo "<option>" ;
    echo $row[0] ;
	echo "</option>" ;
  }
 ?> </select></div>
</fieldset>

<fieldset><legend>Time Range</legend>

<table>
<tr>
<td>From:</td><td><input class="datetimepicker" size="24" name="vmq_start_time" type="text" value="<?php $da=new DateTime(); $da->sub(new DateInterval('P1D')); echo $da->format('Y-m-d H:i:s'); ?>"/></td>
</tr>
<tr>
<td>Duration:</td><td><input name="vmq_duration" size="10" type="text" value="1440" />minutes</td>
</tr>
</table>

</fieldset>

<p>Description: <br/><textarea name="vmq_description" cols="30" rows="8" maxlength="450" ></textarea></p>
<p style="text-align: center;">
<button id="request" type="submit">Submit</button>
</p>
</form>
</div>

<div id="workarea" style="width:auto;">
  
<p class="btset">
  <input   name="btset" href="videos.php" id="btvideo" type="radio" /><label for="btvideo"> Browse &amp; Manage Video </label> 
  <input   name="btset" checked="checked" href="videosrequest.php" id="btvideoreq" type="radio" /><label for="btvideoreq"> Request Video Clips </label>
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
<div style="float:left"><span  id="servertime" style="color:#800080;font-size: 11px;"><?php
echo date("Y-m-d H:i") ;
?> </span></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>