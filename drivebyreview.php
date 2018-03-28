<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
?>
	<title>Drive By Event</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.11.0/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script>
	<script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
	<script src="picker.js"></script>
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

var selectedtag = "" ;

// Event Tag list
$("#tag_list").jqGrid({        
    scroll: true,
	datatype: "local",
	height: 400,
	width: 720,
	caption: 'Drive By Events',
    colNames: [ 'Client ID', 'Bus ID', 'Event Time', 'Plate', 'Status' ],
    colModel: [ 
      {name:'clientid', index:'clientid', width:180, sortable: true }, 
      {name:'vehicle', index:'vehicle', width:180, sortable: true }, 
      {name:'datetime', index:'datetime', width:180, sortable: true }, 
      {name:'plate', index:'plate', width:180, sortable: true }, 
      {name:'status', index:'status', width:180, sortable: true } 
    ],
	
	onSelectRow: function(id){ 
		selectedtag = id ;
	}
});

(function load_taglist()
{
	$.getJSON("drivebytaglist.php?process=1", function(resp){
		if( resp.res == 1 ) {
			$("#tag_list").jqGrid("clearGridData");
			var griddata = [] ;
			for(var i=0;i<resp.tags.length;i++) {
				griddata[i] = { tagname: resp.tags[i].tagname,
						  clientid: resp.tags[i].clientid,
						  vehicle: resp.tags[i].vehicle,
						  datetime: resp.tags[i].datetime,
						  plate: resp.tags[i].plate,
						  status: resp.tags[i].status
						};
			}
			$("#tag_list").jqGrid('addRowData','tagname',griddata);		
			$("#tag_list").jqGrid('setGridParam',{sortname:'datetime'}).trigger('reloadGrid');
			 
			if( resp.tags.length>0 ) {
				// to select first tag
				var ids = $("#tag_list").jqGrid('getDataIDs');
				if( ids[0] ) 
					$("#tag_list").jqGrid('setSelection',ids[0],true);		
			}
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

$("button#reviewreport").click( function() {
	window.open("drivebyreportpdf.php?tag="+selectedtag);
} );

// initialize video review dialog
$( ".dialog#dialog_reviewvideo" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		var param = {} ;
		param.tag = selectedtag ;
		$.getJSON("drivebytag.php", param, function(resp){
			if( resp.res == 1 ) {
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
				$("select[name='reviewvideocam']").html( camopt ); 
				$("select[name='reviewvideocam']").change(); 
			}
		});
	},
	close: function( event, ui ) {
		$("video#reviewvideo").attr("src", "");
	},
	buttons:{
		"Close": function() {
			$( this ).dialog( "close" );
		}
	}
});

$("select[name='reviewvideocam']").change(function(){
	var tagname = selectedtag ;
	var channel = $("select[name='reviewvideocam']").val();
	var mp4 = "drivebyvideo.php?tag=" + tagname +"&channel="+channel;
	$("video#reviewvideo").attr("src", mp4);
});

$("button#reviewvideo").click( function() {
	$( ".dialog#dialog_reviewvideo" ).dialog("open");
} );

// load default email addresses
var email_to = "" ;
var email_from = "" ;
var email_notes = "" ;

$.getJSON("drivebyreportemailaddresses.php", function(resp){
	if( resp.res == 1 ) {
		email_to = resp.to ;
		email_from = resp.from ;
		email_notes = resp.notes ;
	}
});
			
// send report dialog
$( ".dialog#dialog_sendreport" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		var tag = $("#tag_list").jqGrid('getRowData', selectedtag);	
		$("span#sr_clientid").text( tag.clientid );
		$("span#sr_busid").text( tag.vehicle );
		$("span#sr_eventtime").text( tag.datetime );
		$("span#sr_plate").text( tag.plate );
		$("input[name='sendreportfrom']").val(email_from);
		$("textarea[name='sendreportto']").val(email_to);
		$("textarea[name='sendreportnotes']").val(email_notes);
	},
	close: function( event, ui ) {
	},
	buttons:{
		"Send": function() {
			var param = {} ;
			param.tag = selectedtag ;
			email_from = param.from = $("input[name='sendreportfrom']").val();
			email_to = param.to = $("textarea[name='sendreportto']").val();
			email_notes = param.notes = $("textarea[name='sendreportnotes']").val();
			$.getJSON("drivebysendreport.php", param, function(resp){
				if( resp.res == 1 ) {
					alert( "Email reports send out successfully!");
					$( ".dialog#dialog_sendreport" ).dialog("close");
				}
				else {
					alert( "Failed to send out all email reports!");
				}
			});
		},
		"Cancel": function() {
			$( this ).dialog( "close" );
		}
	}
});


$("button#sendreport").click( function() {
	$( ".dialog#dialog_sendreport" ).dialog("open");
} );


// delete report dialog
$( ".dialog#dialog_delreport" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	open: function( event, ui ) {
		var tag = $("#tag_list").jqGrid('getRowData', selectedtag);	
		$("span#dr_clientid").text( tag.clientid );
		$("span#dr_busid").text( tag.vehicle );
		$("span#dr_eventtime").text( tag.datetime );
	},
	buttons:{
		"Delete": function() {
			var param = {} ;
			param.tag = selectedtag ;
			$.getJSON("drivebydeletereport.php", param, function(resp){
				if( resp.res == 1 ) {
					$("#tag_list").jqGrid('delRowData', selectedtag);	
					selectedtag = "" ;
					alert( "Reports deleted!" );
				}
				else {
					alert( "Delete Report failed!" );
				}
				$( ".dialog#dialog_delreport" ).dialog("close");
			});
		},
		"Cancel": function() {
			$( this ).dialog( "close" );
		}
	}
});


$("button#deletereport").click( function() {
	$( ".dialog#dialog_delreport" ).dialog("open");
} );

$("button#EditContacts").click( function() {
	$( ".dialog#dialog_editcontacts" ).dialog("open");
} );


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
	<li><img src="res/side-driveby-logo-green.png" /></li>
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
<input name="btset" href="driveby.php" id="btdriveby" type="radio" /><label for="btdriveby">Drive By Event Process</label>
<input name="btset" href="drivebyreview.php" id="btdrivebyreview" type="radio" checked="checked" /><label for="btdrivebyreview">Drive By Event Review</label> 
</p>

<!-- work area --->

<div>
<table id="tag_list"></table> 
</div>
<p>
<button id="reviewreport" >Review Report</button>
<button id="reviewvideo" >Review Video</button>
<button id="sendreport" >Send Report</button>
<button id="deletereport" >Delete</button>
</p>

</div>

<!-- Edit Contacts Dialog -->
<div class="dialog" title="Contacts" id="dialog_editcontacts">

</div>

<!-- Review Video Dialog -->
<div class="dialog" title="Drive By Videos" id="dialog_reviewvideo">
	<video id="reviewvideo" width="640" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
	<br/>
	<div>Select camera: <select name="reviewvideocam"></select></div>
</div>

<!-- Send Report Dialog -->
<div class="dialog" title="Send Report" id="dialog_sendreport">
	<table>
	<tr><td>Client Id:</td><td><span id="sr_clientid"></span></td></tr>
	<tr><td>Bus Id:</td><td><span id="sr_busid"></span></td></tr>
	<tr><td>Event Time:</td><td><span id="sr_eventtime"></span></td></tr>
	<tr><td>Plate of Violator:</td><td><span id="sr_plate"></span></td></tr>
	</table>
	<br/>
	<p>From: <input size="30" name="sendreportfrom" /></p>
	<p>To: (Separate multiple recipients by comma) </p>
	<textarea name="sendreportto" style="margin:2px;width:300px;height:60px;"></textarea>
	<p>Notes:  </p>
	<textarea name="sendreportnotes" style="margin:2px;width:300px;height:100px;"></textarea>
</div>

<!-- Delete Report Dialog -->
<div class="dialog" title="Delete Report" id="dialog_delreport">
	<h4> Please confirm that you want to delete this report,</h4>
	<table>
	<tr><td>Client Id:</td><td><span id="dr_clientid"></span></td></tr>
	<tr><td>Bus Id:</td><td><span id="dr_busid"></span></td></tr>
	<tr><td>Event Time:</td><td><span id="dr_eventtime"></span></td></tr>
	</table>
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