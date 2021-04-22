<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
?>
	<title>Drive By Event</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-<?php echo $jqver; ?>.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css">
		#rcontainer { display:none }
	</style>	
<script>
// start up

$(document).ready(function(){

<?php if( strpos( $_SERVER["REQUEST_URI"], "deletedreports" )>0 ) { ?>	  
$("input#btdrivebydeletedreports").prop( "checked", true );
<?php } ?>		

$("button").button();	
$(".btset").controlgroup();
$(".btset input").checkboxradio({
      icon: false
    });
$(".btset input").change(function(){
   location=$(this).attr("href");
});

var selectedtag = "" ;


// Event Tag list
$("#tag_list").jqGrid({
//    scroll: true,
	datatype: "json",
	url:'drivebygrid.php',
	height: 300,
	width: 1092,
	caption: 'Drive By Event Tags',
    colNames:['Client ID', 'Bus ID', 'Date/Time', 'License Plate', 'Image Quality', 'Status', 
<?php if( strpos( $_SERVER["REQUEST_URI"], "deletedreports" )>0 ) { ?>	  
	'Deleted By', 'Deleted Time', 
<?php } else { ?>
	'Processed By', 'Processed Time', 
<?php } ?>	  
	'Sent To', 'State', 'City/Town' ],
    colModel :[ 
      {name:'Client_Id', index:'Client_Id', width:180, sortable: true }, 
      {name:'Bus_Id', index:'Bus_Id', width:180, sortable: true }, 
      {name:'Date_Time', index:'Date_Time', width:180, sortable: true },
      {name:'Plateofviolator', index:'Plateofviolator', width:180, sortable: true },
      {name:'imgquality', index:'imgquality', width:180, sortable: true },
	  {name:'email_status', index:'email_status', width:180, sortable: true },
<?php if( strpos( $_SERVER["REQUEST_URI"], "deletedreports" )>0 ) { ?>	  
      {name:'report_deleteby', index:'report_deleteby', width:180, sortable: true },
      {name:'report_deletetime', index:'report_deletetime', width:180, sortable: true },
<?php } else { ?>
      {name:'event_processedby', index:'event_processedby', width:180, sortable: true },
      {name:'event_processedtime', index:'event_processedtime', width:180, sortable: true },
<?php } ?>	  
      {name:'sentto', index:'sentto', width:180, sortable: true },
      {name:'State', index:'State', width:180, sortable: true,  editable: true},
      {name:'City', index:'City', width:180, sortable: true,  editable: true },
    ],
   	sortname: 'Date_Time',
    sortorder: "desc",
	pager: '#tag_list_pager',
    viewrecords: true,
	rowNum: 20 ,
    rowList:[20, 50, 100, 200],	
//	gridComplete: display_summary,
	onSelectRow: function(id){ 
		if( id && id!==selectedtag ) {
			selectedtag = id ;
		}
	}
});
//jQuery("#tag_list").jqGrid('filterToolbar',{searchOperators : true});
jQuery("#tag_list").jqGrid('filterToolbar',{searchOnEnter : false});

<?php if( !strpos( $_SERVER["REQUEST_URI"], "deleted" ) ) { ?>	  
$.getJSON("drivebysummary.php", function(resp){
	if( resp.res ) {
		var html='' ;
		// thead
		html += "<thead><tr>" ;
		html += "<th>Month</th>" ;
		var m ;
		for( m=0; m<resp.summary.length; m++ ) {
			html += "<td>" + resp.summary[m].month + "</td>" ;
		}
		html+="</tr></thead>" ;
		
		// total events
		html+="<tr><th>Total Events</th>" ;
		for( m=0; m<resp.summary.length; m++ ) {
			html += "<td>" + resp.summary[m].total + "</td>" ;
		}
		html+="</tr>" ;
		
		// Bad Images
		html+="<tr><th>Bad Images</th>" ;
		for( m=0; m<resp.summary.length; m++ ) {
			html += "<td>" + resp.summary[m].Bad + "</td>" ;
		}
		html+="</tr>" ;
		
		// Poor Images
		html+="<tr><th>Poor Images</th>" ;
		for( m=0; m<resp.summary.length; m++ ) {
			html += "<td>" + resp.summary[m].Poor + "</td>" ;
		}
		html+="</tr>" ;
		
		// Good Images
		html+="<tr><th>Good Images</th>" ;
		for( m=0; m<resp.summary.length; m++ ) {
			html += "<td>" + resp.summary[m].Good + "</td>" ;
		}
		html+="</tr>" ;

		// Reprots sent
		html+="<tr><th>Reprots Sent</th>" ;
		for( m=0; m<resp.summary.length; m++ ) {
			html += "<td>" + resp.summary[m].Sent + "</td>" ;
		}
		html+="</tr>" ;
		
		$("table#summary_table").html(html);
		$("div#summary14m").css("display","");
		
	}
}) ;
<?php } ?>	  

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
		param.id = selectedtag ;
		$.getJSON("drivebytagload.php", param, function(resp){
			if( resp.res == 1 ) {
				// setup camera select
				var camopts = [] ;
				var camopt = "" ;
				if( resp.tag.channels.channel.length )
				for( var ch = 0 ; ch < resp.tag.channels.channel.length; ch++ ) {
					// set lpr1
					var name = "camera"+(ch+1) ;
					if( resp.tag.channels.channel[ch].name ) {
						name = resp.tag.channels.channel[ch].name ;
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
		$("span#sr_clientid").text( tag.Client_Id );
		$("span#sr_busid").text( tag.Bus_Id );
		$("span#sr_eventtime").text( tag.Date_Time );
		$("span#sr_plate").text( tag.Plateofviolator );
		$("input[name='sendreportfrom']").val(email_from);
		$("textarea[name='sendreportto']").val(email_to);
		$("textarea[name='sendreportnotes']").val(email_notes);
	},
	close: function( event, ui ) {
	},
	buttons:{
		"Send": function(e) {
			var param = {} ;
			param.tag = selectedtag ;
			email_from = param.from = $("input[name='sendreportfrom']").val();
			email_to = param.to = $("textarea[name='sendreportto']").val();
			email_notes = param.notes = $("textarea[name='sendreportnotes']").val();
			$.getJSON("drivebysendreport.php", param, function(resp){
				if( resp.res == 1 ) {
					var param = {} 
					param.id = selectedtag ;
					$.getJSON("drivebytagload.php", param, function(resp){
						if( resp.res == 1 ) {
							jQuery("#tag_list").jqGrid('setRowData', resp.id, resp.tag);
						}
					});
					alert( "Email reports send out successfully!");
					$( ".dialog#dialog_sendreport" ).dialog("close");
					//load_taglist();
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
		$("span#dr_clientid").text( tag.Client_Id );
		$("span#dr_busid").text( tag.Bus_Id );
		$("span#dr_eventtime").text( tag.Date_Time );
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
	if( selectedtag )
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
<input name="btset" href="drivebyevents.php"         id="btdrivebyevents"         type="radio" /><label for="btdrivebyevents">New Events</label>
<input name="btset" href="drivebyprocessed.php"      id="btdrivebyprocessed"      type="radio" /><label for="btdrivebyprocessed">Processed Events</label> 
<input name="btset" href="drivebydeleted.php"        id="btdrivebydeleted"        type="radio" /><label for="btdrivebydeleted">Deleted Events</label>
<input name="btset" href="drivebyreports.php"        id="btdrivebyreports"        type="radio" checked="checked" /><label for="btdrivebyreports">Reports</label> 
<input name="btset" href="drivebydeletedreports.php" id="btdrivebydeletedreports" type="radio" /><label for="btdrivebydeletedreports">Deleted Reports</label> 
</p>

<!-- work area --->
<div>
<table id="tag_list"></table> 
<div id="tag_list_pager"></div> 
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

<div style="margin-left:14px;display:none" id="summary14m" >
<h4>14 Months Status Summary</h4>
<table border="1" class="summarytable" id="summary_table">
</table>
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