<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 

// remember recent page
session_save('settingpage', $_SERVER['REQUEST_URI'] );

?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2021-04-27" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><link rel="stylesheet" href="//code.jquery.com/ui/<?php echo $jquiver; ?>/themes/base/jquery-ui.css"><script src="https://code.jquery.com/jquery-<?php echo $jqver; ?>.js"></script><script src="https://code.jquery.com/ui/<?php echo $jquiver; ?>/jquery-ui.js"></script>
	<script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none;}" ?>
	</style>
	<script src="td_alert.js"></script><script>
// start up 

function setcookie(cname,value,expires) {
	var ck=cname+"="+escape(value);
	if( expires ) {
		var d = new Date();
		d.setTime(d.getTime()+(expires*24*60*60*1000));
        ck += "; expires="+d.toGMTString();
    }
   	document.cookie = ck ;
}

$(document).ready(function(){
            
$("button").button();	
$(".btset").controlgroup();
$(".btset input").checkboxradio({
      icon: false
}).change(function(){
   location=$(this).attr("href");
});
			
var tab=0;
if( sessionStorage ) {
	var tdsess = sessionStorage.getItem('tdsess');
	var localsession = {} ;
	if( tdsess ) {
		localsession = JSON.parse(tdsess);
	}
	if( localsession.setting_system_tab ) {
		tab = localsession.setting_system_tab;
	}
}

		
$( "#settingtabs" ).tabs({
	active: tab,
	activate: function( event, ui ) {
		tab = ui.newTab.index() ;
		if( sessionStorage ) {
			var tdsess = sessionStorage.getItem('tdsess');
			var localsession = {};
			if( tdsess ) {
				localsession = JSON.parse(tdsess);
			}
			localsession.setting_system_tab=tab;
			sessionStorage.setItem('tdsess', JSON.stringify(localsession));
		}
	}
});

// Storage Tab
function storage_reload()
{
	$("form#storageform")[0].reset();
	$.getJSON("storageload.php", function(resp){
		if( resp.res && resp.store ) {
			// fill form fields
			for (var field in resp.store) {
				$("form#storageform [name='"+field+"']").val(resp.store[field]);
			}
		}
	});
}
storage_reload();
$("button#storagereset").click(storage_reload);
$("button#storagesave").click(function(){
    $.getJSON("storagesave.php", $('form#storageform').serializeArray(), function(data){
		if( data.res == 1 ) {
			$( ".tdcdialog#dialog_message #message" ).text("Storage & Backup Settings Saved!");
		}
		else if( data.errormsg ) {
			$( ".tdcdialog#dialog_message #message" ).text(data.errormsg);
		}
		else {
			$( ".tdcdialog#dialog_message #message" ).text("Error on saving data!");
		}
		$( ".tdcdialog#dialog_message" ).dialog("open");
	});
});

$( "select[name='keepGpsLogDataForDays']").change(function(){
	var i = $( "select[name='keepGpsLogDataForDays']")[0].selectedIndex ;
	var t = $( $( "select[name='keepGpsLogDataForDays']")[0][i] ).text();
	if( t != "Forever" ) {
		var msg = "All the GPS data in the Database which older than " + t + " will be deleted." ;
		alert( msg );
	}
});

$( "select[name='keepVideoDataForDays']").change(function(){
	var i = $( "select[name='keepVideoDataForDays']")[0].selectedIndex ;
	var t = $( $( "select[name='keepVideoDataForDays']")[0][i] ).text();
	if( t != "Forever" ) {
		var msg = "All the DVR Video clip files which older than " + t + " will be deleted." ;
		alert( msg );
	}
});
			
// Backup Database Dialog
$( "#dialog_backupdatabase" ).dialog(
{
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Start": function() {
			$( this ).dialog( "close" );
			var param=new Object ;
			param.backupname = $( "#dialog_backupdatabase input").val();
			$.getJSON("backupstart.php", param, function(resp){
				if( resp.res==1 ) {
					if( resp.progressfile )
						progfile = resp.progressfile ;
					$("#progressmessage").text("Backup in progress, please wait");
					$( "#dialog_progress" ).dialog( "option", "title", "Backup" );
					$( "#dialog_progress" ).dialog("open");
				}
				else {
					alert("Backup failed to start!");
				}
			});			
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

// Restore Database Dialog
$( "#dialog_restoredatabase" ).dialog(
{
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Restore": function() {
			var param = new Object ;
			param.backupname=$("#dialog_restoredatabase select#backuplists").val();
			var d=new Date();
			param.ts=d.getTime(); // time stamp
			$.getJSON("backuprestore.php", param, function(resp){
				$( "#dialog_restoredatabase" ).dialog( "close" );
				if( resp.res==1 ) {
					if( resp.progressfile )
						progfile = resp.progressfile ;
					$("#progressmessage").text("Restore in progress, please wait");
					$( "#dialog_progress" ).dialog( "option", "title", "Restore" );
					$( "#dialog_progress" ).dialog("open");
				}
				else if( resp.errormsg ) {
					alert( resp.errormsg );
				}
				else {
					alert("Restore failed!");
				}
			});	
			$( this ).dialog( "close" );
			
		},
		"Delete": function() {
			var param = new Object ;
			param.backupname=$("#dialog_restoredatabase select#backuplists").val();
			var d=new Date();
			param.ts=d.getTime(); // time stamp
			$.getJSON("backupdel.php", param, function(resp){
				$( "#dialog_restoredatabase" ).dialog( "close" );
				if( resp.res==1 ) {
					alert( "Backup ("+param.backupname+") deleted!");
				}
				else {
					alert("Delete failed!");
				}
			});	
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$("button#backupdatabase").click(function(){
	var d= new Date();
	var defaultname = "Backup "+
		d.getFullYear() + '-' +
		("0" + (d.getMonth()+1)).slice(-2) + '-' +
		("0" + d.getDate()).slice(-2) + " " +
		("0" + d.getHours()).slice(-2) + ':' +
		("0" + d.getMinutes()).slice(-2) + ':' +
		("0" + d.getSeconds()).slice(-2);
	$( "#dialog_backupdatabase input").val(defaultname);
	$( "#dialog_backupdatabase" ).dialog("open");
});

$("button#restoredatabase").click(function(){
	$.getJSON("backuplist.php", function(resp){
		if( resp.res==1 ) {
			var html="";
			for(var i=0; i<resp.backuplist.length;i++) {
				html+="<option>"+resp.backuplist[i]+"</option>";
			}
			$("#dialog_restoredatabase select#backuplists").html(html);
			$( "#dialog_restoredatabase" ).dialog("open");
		}
	});
});

// backup/restore progress

var progfile ;
var progvalue ;
var progCloseTimer=null ;
var progInUse = false ;

$( "#progressbar" ).progressbar();

$( "#dialog_progress" ).dialog(
{
	autoOpen: false,
	modal: true,
	width: 450,
	beforeClose: function( event, ui ) {
		if( progInUse ) {
			return false ;
		}
		else {
			if( progCloseTimer ) {
				clearTimeout(progCloseTimer)
				progCloseTimer=null ;
			}
			setTimeout(function(){
				location.reload(); 
			},20);
			return true ;
		}
	},
	open: function() {
		var progTimer ;
		var smoothTimer ;
		$( "#progress-label" ).text("Loading...");
		progInUse = true ;
		progvalue = 0 ;
		$( "#progressbar" ).progressbar( "value", false );
		
		function inprogress() {
			if( $( "#dialog_progress" ).dialog( "isOpen" ) )
			var param=new Object ;
			param.progressfile = progfile ;			
			$.getJSON("backupgetprogress.php", param, function(resp){
				if( resp.res==1 ) {
					var toval = parseFloat(resp.percentage) ;
					if( toval < 0 ) {
						$("#progressmessage").text("Server script not started!!!");
						progInUse = false ;
					}
					else if( toval>= 100 ) {
						$( "#progressbar" ).progressbar( "value", 100 );
						$( "#progress-label" ).text( "Complete!" );
						var param=new Object ;
						param.complete="1" ;
						param.progressfile = progfile ;
						$.getJSON("backupgetprogress.php", param);
						if( smoothTimer	) {
							clearTimeout(smoothTimer);
							smoothTimer=null ;
						}
						progInUse = false ;
						progCloseTimer = setTimeout(function(){ 
							progCloseTimer = null;
							$( "#dialog_progress" ).dialog("close"); 
						},10000);					
					}
					else {
						// to get progress value in 2 seconds
						progTimer = setTimeout(inprogress,2000);

						var progstep = (toval-progvalue)/10 ;

						function smoothprogress(){
							smoothTimer=null;
							if( $( "#dialog_progress" ).dialog( "isOpen" ) ) {
								if( progvalue <= toval ) {
									progvalue += progstep ;
									$( "#progressbar" ).progressbar("value", progvalue ) ;
									$( "#progress-label" ).text( progvalue.toFixed()+"%" );
									smoothTimer = setTimeout( smoothprogress, 200 );
								}
							}
						}
						if( toval>0 )
							smoothprogress();
					}
				}
			});
		}
		// wait 3 seconds to let server start script
		progTimer = setTimeout(inprogress,3000);
	}
});

// Event Parameters Tab
function eventparameter_reload()
{
	$.getJSON("eventparameterload.php", function(eventparameter){
		$("form#eventform")[0].reset();
		// fill form fields
		for (var field in eventparameter) {
			$("form#eventform input[name='"+field+"']").val(eventparameter[field]);
		}
	});
}
eventparameter_reload();
$("button#eventreset").click(eventparameter_reload);
$("button#eventsave").click(function(){
    $.getJSON("eventparametersave.php", $('form#eventform').serializeArray(), function(data){
		if( data.res == 1 ) {
			$( ".tdcdialog#dialog_message #message" ).text("Default Event Parameters Saved!");
		}
		else if( data.errormsg ) {
			$( ".tdcdialog#dialog_message #message" ).text(data.errormsg);
		}
		else {
			$( ".tdcdialog#dialog_message #message" ).text("Error on saving data!");
		}
		$( ".tdcdialog#dialog_message" ).dialog("open");
	});
});

// MSS tab
function updatemsslist()
{
	$.getJSON("msslist.php", function(msslist){
		if( msslist.length>0) {
			$("table#msstable tr.mssitem").remove();
			for( var i=0; i<msslist.length; i++) {
				var htmlstr = '<tr class="mssitem"><td>' +
							msslist[i].mss_id + '</td><td>' +
							msslist[i].mss_maxlogin + '</td><td>' +
							'<input class="editmss" src="res/button_edit.png" style="width: 24px; height: 24px;" type="image" /><input class="deletemss" src="res/button_delete.png" style="width: 24px; height: 24px;" type="image" /></td></tr>' ;				
				$("table#msstable").append(htmlstr);
				$($("table#msstable tr.mssitem")[i]).data("mss", msslist[i]);
			}
			$("table#msstable tr.useritem").filter(':odd').addClass("alt");
			$("table#msstable input.editmss" ).click(function() {
				$("#dialog_mss #mssform")[0].reset();
				var mss = $(this.parentNode.parentNode).data("mss");				
				$("#dialog_mss").dialog( "option", "title", "Edit MSS ("+mss.mss_id+")" );
				// fill form fields
				var field ;
				for (field in mss) {
					var elm=$("form#mssform input[name='"+field+"']");
					if( elm.length>0 ) {
						elm.val(mss[field]);
					}
				}
				$("form#mssform").data("idx", mss.idx);		// idx, for edit
				$("#dialog_mss").dialog( "open" );
			});

			$( "table#msstable input.deletemss" ).click(function() {
				var mss = $(this.parentNode.parentNode).data("mss");		
				var idx = mss.idx ;
				$( ".tdcdialog#dialog_delete #deletemsg" ).text("Do you want to delete this MSS:");
				$( ".tdcdialog#dialog_delete #deletename" ).text(mss.mss_id);
				$( ".tdcdialog#dialog_delete" ).dialog( "option", "title", "Delete MSS" );
				$( ".tdcdialog#dialog_delete" ).data( "yesfunction", function(diag){
					var formv=new Object ;
					formv.idx=idx ;
					$.getJSON("mssdel.php", formv, function(data){
						if( data.res == 1 ) {
							// success
							updatemsslist();
							$( ".tdcdialog#dialog_delete" ).dialog("close");
						}
						else if( data.errormsg ) {
							alert( data.errormsg );
						}
						else {
							alert( "Delete MSS Failed!");
						}
					});
					return false ;
				});
				$( ".tdcdialog#dialog_delete" ).dialog( "open" );
			});
		}
	});
}	
	
updatemsslist();	
	
$("form#mssform").submit(function(e){
	e.preventDefault();
	// get form data
	var formdata = $('form#mssform').serializeArray();
	var formv = new Object ;
	for( var i=0; i<formdata.length; i++) {
		formv[formdata[i].name] = formdata[i].value ;
	}
	var idx=$("form#mssform").data("idx");		// idx, for edit
	if( idx ) {
		formv.idx=idx;
	}
	$.getJSON("msssave.php",formv, function(data){
		if( data.res == 1 ) {
			// success
			updatemsslist();
			$( "#dialog_mss" ).dialog("close");
		}
		else if( data.errormsg ) {
			alert( data.errormsg );
		}
		else {
			alert( "MSS update failed!");
		}
	});	
});
	
// add more init functions
$( "#dialog_mss" ).dialog(
{
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Save": function() {
			$("form#mssform input[type='submit']").click();
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	},
	resize: function( event, ui ) {
		if( mssmap ) {
			$("div#mssmap").height($("div#dialog_mss").height()-180);
		}
	},
	close: function(event, ui) {
		if( mssmap ) {
			mssmap.dispose(); 
			mssmap = null; 
			$("#mssmap").hide();
		}
	}
});

$( ".tdcdialog#dialog_message" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Ok": function() {
			$( this ).dialog( "close" );
		}
	}
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

$("button#addmss").click(function(){
    $("form#mssform").removeData("idx");
	$("#dialog_mss #mssform")[0].reset();
	$("#dialog_mss").dialog( "option", "title", "New MSS" );
    $("#dialog_mss").dialog( "open" );
});

var mssmap ;
$("button#mssshowmap").click(function(event){
	event.preventDefault();
	var lat=$("#mssform input[name='mss_lat']").val(); 
	var lon=$("#mssform input[name='mss_lon']").val(); 	
	var zoomlevel=$("#mssform").data("zoomlevel");
	if( lat==0 && lon==0 ) {
		lat = 41.878999 ;
		lon = -87.635805 ;
	}
	if( !zoomlevel ) {
		zoomlevel=15 ;
	}
	$("#mssmap").show("slow", function() {
		if( !mssmap ) {
			var mssloc = new Microsoft.Maps.Location(lat, lon) ;
			mssmap = new Microsoft.Maps.Map(document.getElementById("mssmap"), 
				{credentials: <?php echo '"'. $map_credentials . '"'; ?> ,
				zoom: zoomlevel,
				center: mssloc,
				enableSearchLogo: false,
				enableClickableLogo: false,
			});
			
			var pin=new Microsoft.Maps.Pushpin(mssloc, {draggable: true});
			mssmap.entities.push(pin);
			Microsoft.Maps.Events.addHandler(pin, 'drag', function(){
				var latlon = pin.getLocation() ;
				$("#mssform input[name='mss_lat']").val(latlon.latitude.toFixed(6)); 
				$("#mssform input[name='mss_lon']").val(latlon.longitude.toFixed(6));
			});  
		
			attachmapviewchangeend = Microsoft.Maps.Events.addHandler(mssmap, 'viewchangeend', function(e){
				$("#mssform").data("zoomlevel", mssmap.getZoom() );
			}); 
		}
		else {
			mssmap.setView({ zoom: zoomlevel, center: new Microsoft.Maps.Location(lat, lon) })
		}	
	});
});

$("button#msshidemap").click(function(event){
	event.preventDefault();
	$("#mssmap").hide("slow");
});

// Local MSS Tab
function localmss_reload()
{
	$("form#localmss")[0].reset();
	$.getJSON("localmssload.php", function(resp){
		if( resp.res ) {
		// fill form fields
			var localmss = resp.mss ;
			for (var field in localmss) {
				var elm=$("form#localmss input[name='"+field+"']");
				if( elm.length>0 ) {
					if( elm.prop("type")=="checkbox" ) {
						elm.prop("checked", (localmss[field]=='1'));
					}
					else {
						elm.val(localmss[field]);
					}
				}
			}
		}
	});
}
localmss_reload();
$("button#localmssreset").click(localmss_reload);
$("button#localmsssave").click(function(){
    $.getJSON("localmsssave.php", $('form#localmss').serializeArray(), function(data){
		if( data.res == 1 ) {
			$( ".tdcdialog#dialog_message #message" ).text("Local MSS Settings Saved!");
		}
		else if( data.errormsg ) {
			$( ".tdcdialog#dialog_message #message" ).text(data.errormsg);
		}
		else {
			$( ".tdcdialog#dialog_message #message" ).text("Error on saving data!");
		}
		$( ".tdcdialog#dialog_message" ).dialog("open");
	});
});

// Email tab
$("button#emailsave").click(function(){
    $.getJSON("emailsave.php", $('form#emailsetup').serializeArray(), function(data){
		if( data.res == 1 ) {
			$( ".tdcdialog#dialog_message #message" ).text("EMail Settings Saved!");
		}
		else if( data.errormsg ) {
			$( ".tdcdialog#dialog_message #message" ).text(data.errormsg);
		}
		else {
			$( ".tdcdialog#dialog_message #message" ).text("Error on saving data!");
		}
		$( ".tdcdialog#dialog_message" ).dialog("open");
	});
});

$("button#emailreset").click(function(){
    $.getJSON("emailload.php", function(data){
		if( data.res == 1 ) {
			for (var field in data.email ) {
				var elm=$("form#emailsetup [name='"+field+"']");
				if( elm.length>0 ) {
					if( elm.prop("type")=="checkbox" ) {
						elm.prop("checked", (data.email[field]=='1')||(data.email[field]=='y'));
					}
					else if( elm.prop("type")=="radio" ) {
						elm.filter("[value='"+data.email[field]+"']").prop("checked",true);
					}
					else {
						elm.val(data.email[field]);
					}
				}
			}
		}
		else if( data.errormsg ) {
			$( ".tdcdialog#dialog_message #message" ).text(data.errormsg);
			$( ".tdcdialog#dialog_message" ).dialog("open");
		}
		else {
			$( ".tdcdialog#dialog_message #message" ).text("Error on loading data!");
			$( ".tdcdialog#dialog_message" ).dialog("open");
		}
	});
});
$("button#emailreset").click();

$("form#emailsetup input[name='tmSendDaily']").timepicker({
	showTime: false ,
	timeFormat: "H:mm",
});

// user theme
$('#setting-ui input[type="image"]').click(function(){
	// setcookie
	setcookie("ui",$(this).attr("id"),180);
	location.reload();
});

$('#rcontainer').show(200);

});
	</script>
	<style type="text/css">.sum_circle
{
background-image:url('res/big_dashboard_circles.png');
background-repeat:no-repeat;
background-position:center;
height: 72px;
font-size:36px;
text-align: center;
}
.sum_title
{
height: 1em;
font-size:11px;
font-weight:bold;
text-align: center;
}
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
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<?php if( !empty($support_driveby) && ( $_SESSION['user_type'] == "operator" || $_SESSION['user'] == "admin" ) ){ ?>
	<li><a class="lmenu" href="driveby.php"><img onmouseout="this.src='res/side-driveby-logo-clear.png'" onmouseover="this.src='res/side-driveby-logo-fade.png'" src="res/side-driveby-logo-clear.png" /> </a></li>
	<?php } ?>	
		<?php if( !empty($support_emg) ) { ?>
	<li><a class="lmenu" href="emg.php"><img onmouseout="this.src='res/side-emg-logo-clear.png'" onmouseover="this.src='res/side-emg-logo-fade.png'" src="res/side-emg-logo-clear.png" /> </a></li>
	<?php } ?>
	<li><img src="res/side-settings-logo-green.png" /></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
bus1 : uploading abc
bus2 : high tempterature
</pre>
</div>
<strong><span style="font-size:26px;">SETTINGS</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">
<p class="btset">
<input name="btset" href="settingsfleet.php" id="btfleet" type="radio" /><label for="btfleet">Fleet Setup</label>
<input name="btset" href="settingsuser.php" id="btuser" type="radio" /><label for="btuser">User Accounts</label> 
<input name="btset" checked="checked" href="settingssystem.php" id="btsys" type="radio" /><label for="btsys">System Configuration</label>
<input name="btset" href="settingsemail.php" id="btemail" type="radio" /><label for="btemail">Email Configuration</label> 
</p>

<h4><strong>System Configuration</strong></h4>

<div id="settingtabs">
<ul>
	<li><a href="#setting-storage">Storage&amp;Backup</a></li>
	<li><a href="#setting-event">Default Event Parameters</a></li>
<?php if( empty($disable_mss) ) { ?>	
	<li><a href="#setting-mss">MSS Setup</a></li>
<?php } ?>	
<?php if( empty( $_SESSION['clientid'] )) { ?>
	<li><a href="#setting-localmss">Local MSS Setup</a></li>
<?php } ?>	
<!-- <li><a href="#setting-email">E-mail Setup</a></li> -->
</ul>

<div id="setting-storage">
<form id="storageform">
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<caption>
	<h3>Storage Setup</h3>
	</caption>
	<tbody>
<?php if( empty( $company_root ) ) { ?>
		<tr>
			<td style="text-align: right;">Video Data Folder:</td>
			<td><input name="videopath" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">GPS Data Folder:</td>
			<td><input name="gpspath" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Smart Log Folder:</td>
			<td><input name="smartlogpath" type="text" /></td>
		</tr>
<?php } ?>
		<tr>
			<td style="text-align: right;">Keep GPS Data Within:</td>
			<td><select name="keepGpsLogDataForDays">
				<option value="93">3 months</option>
				<option value="186">6 months</option>
				<option value="366">1 year</option>
				<option value="732">2 years</option>
				<option value="1098">3 years</option>
				<option value="0">Forever</option> </select></td>
		</tr>
		<tr>
			<td style="text-align: right;">Keep Video Data Within:</td>
			<td><select name="keepVideoDataForDays">
				<option value="93">3 months</option>
				<option value="186">6 months</option>
				<option value="366">1 year</option>
				<option value="732">2 years</option>
				<option value="1098">3 years</option>
				<option value="0">Forever</option> </select></td>
		</tr>
	</tbody>
</table>
</form>

<p><button id="storagesave">Save</button><button id="storagereset">Cancel</button></p>

<p><button id="backupdatabase">Backup Database</button><button id="restoredatabase">Restore Database</button></p>
</div>

<div id="setting-event">
<form id="eventform">
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<caption>
	<h3>Default Setting</h3>
	</caption>
	<tbody>
		<tr>
			<td><img alt="" class="evicon" src="res/map_icons_stop.png" /></td>
			<td style="text-align: right;">Stopping(s)</td>
			<td><input name="stop_duration" type="text" /></td>

			<td><img alt="" class="evicon" src="res/map_icons_rs.png" /></td>
			<td style="text-align: right;">Racing Start</td>
			<td><input name="racing_start" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td><img alt="" class="evicon" src="res/map_icons_desstop.png" /></td>
			<td style="text-align: right;">Bus Stops(s)</td>
			<td><input name="bstop_duration" type="text" /></td>
			<td><img alt="" class="evicon" src="res/map_icons_ri.png" /></td>
			<td style="text-align: right;">Rear Impact</td>
			<td><input name="rear_impact" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td><img alt="" class="evicon" src="res/map_icons_idle.png" /></td>
			<td style="text-align: right;">Idling (s)</td>
			<td><input name="idle_duration" type="text" /></td>
			<td><img alt="" class="evicon" src="res/map_icons_hb.png" /></td>
			<td style="text-align: right;">Hard Brake</td>
			<td><input name="hard_brake" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td><img alt="" class="evicon" src="res/map_icons_park.png" /></td>
			<td style="text-align: right;">Parking (s)</td>
			<td><input name="park_duration" type="text" /></td>
			<td><img alt="" class="evicon" src="res/map_icons_fi.png" /></td>
			<td style="text-align: right;">Front Impact</td>
			<td><input name="front_impact" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td><img alt="" class="evicon" src="res/map_icons_speed.png" /></td>
			<td style="text-align: right;">Speeding</td>
			<td><input name="speed" type="text" />mph</td>
			<td><img alt="" class="evicon" src="res/map_icons_ht.png" /></td>
			<td style="text-align: right;">Hard Turn</td>
			<td><input name="hard_turn" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td></td>
			<td style="text-align: right;">MaxUploadTime</td>
			<td><input name="maxuploadtime" type="text" />minute(s)</td>
			<td><img alt="" class="evicon" src="res/map_icons_si.png" /></td>
			<td style="text-align: right;">Side Impact</td>
			<td><input name="side_impact" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td></td>
			<td style="text-align: right;">MaxConcurrentUpload</td>
			<td><input name="maxconcurrentupload" type="text" /></td>
			<td><img alt="" class="evicon" src="res/map_icons_br.png" /></td>
			<td style="text-align: right;">Bumpy Ride</td>
			<td><input name="bumpy_ride" size="5" type="text" />g</td>
		</tr>
	</tbody>
</table>
</form>

<p><button id="eventsave">Save</button><button id="eventreset">Cancel</button></p>
</div>

<?php if( empty($disable_mss) ) { ?>	

<div id="setting-mss"><!-- Add / Edit User dialog -->
<div class="tdcdialog" id="dialog_mss" title="Edit MSS">
<form id="mssform">&nbsp;
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td style="text-align: right;">MSS Id:</td>
			<td><input name="mss_id" type="text" maxlength="45" required /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Max Login:</td>
			<td><input name="mss_maxlogin" type="text" /></td>
		</tr>
	</tbody>
</table>

<fieldset><legend>Location</legend>

<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td style="text-align: right;">Latitude:</td>
			<td><input name="mss_lat" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Longitude:</td>
			<td><input name="mss_lon" type="text" /></td>
		</tr>
	</tbody>
</table>

<div id="mssmap" style="position:relative;min-height:300px;min-width:400px;display:none;">&nbsp;</div>
<button id="mssshowmap">Show Map</button><button id="msshidemap">Hide Map</button></fieldset>
<input type="submit" style="display:none;" />
</form>
</div>
<!-- End MSS Dialog -->

<table border="0" cellpadding="1" cellspacing="1" class="listtable" id="msstable" style="width: 100%;">
	<caption>
	<h4 style="text-align: left;">Mini Smart Server List:</h4>
	</caption>
	<tbody>
		<tr>
			<th>MSS ID</th>
			<th>Max Login</th>
			<th>Edit/Delete</th>
		</tr>
	</tbody>
</table>
<button id="addmss"><img src="res/button_add.png" />New MSS</button></div>

<?php } ?>

<div id="setting-localmss" style="display:none;">
<p>&nbsp;</p>

<form id="localmss" method="get" name="localmss">
<p>Local MSS ID: <input name="mss_id" type="text" maxlength="45" /></p>

<fieldset><legend> AP Check </legend>

<p>AP1:<input name="ap_addr1" type="text" /> <input name="check_ap1" type="checkbox" /> Report Error</p>

<p>AP2:<input name="ap_addr2" type="text" /> <input name="check_ap2" type="checkbox" /> Report Error</p>

<p>AP3:<input name="ap_addr3" type="text" /> <input name="check_ap3" type="checkbox" /> Report Error</p>

<p>AP4:<input name="ap_addr4" type="text" /> <input name="check_ap4" type="checkbox" /> Report Error</p>
</fieldset>

<p>Regular Check-in Interval: <input name="ss_interval" type="text" /> minutes</p>
</form>
<button id="localmsssave">Save</button><button id="localmssreset">Cancel</button>

<p>&nbsp;</p>
</div>


<div id="setting-email" style="display:none;">
<form id="emailsetup">
<fieldset><legend> Email Server </legend>

<table border="0" cellpadding="0" cellspacing="1">
	<tbody>
		<tr>
			<td style="text-align: right;">Mail Server (SMTP):</td>
			<td><input name="smtpServer" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Port:</td>
			<td><input name="smtpServerPort" value="25" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Security Type:</td>
			<td><input name="security" value="2" type="radio" />SSL <input name="security"  value="1" type="radio" />TLS <input name="security" type="radio" checked="checked" value="0" />None</td>
		</tr>
		<tr>
			<td style="text-align: right;">Sender E-mail Addr:</td>
			<td><input name="senderAddr" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Sender Name:</td>
			<td><input name="senderName" type="text" /></td>
		</tr>
		<tr>
			<td>Authentication:</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align: right;">User Name:</td>
			<td><input name="authenticationUserName" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Password:</td>
			<td><input name="authenticationPassword" type="password" /></td>
		</tr>
	</tbody>
</table>
</fieldset>

<p><input name="sendSummaryDaily" type="checkbox" />Send Summary Data Daily</p>

<table border="0" cellpadding="1" cellspacing="8">
	<tbody>
		<tr>
			<td>Recipients: (Separated by semi-colon)</td>
			<td>Send Alert Mail To: (Separated by semi-colon)</td>
			<td>Send Panic Alert To: (Separated by semi-colon)</td>
		</tr>
		<tr>
			<td><textarea cols="35" name="recipient" rows="10"></textarea></td>
			<td><textarea cols="35" name="alertRecipients" rows="10"></textarea></td>
			<td><textarea cols="35" name="panicAlertRecipients" rows="10"></textarea></td>
		</tr>
	</tbody>
</table>

<p>Send E-mail At: <input maxlength="8" name="tmSendDaily" type="text" value="19:00" /></p>

</form>
<button id="emailsave">Save</button><button id="emailreset">Cancel</button>

<p>&nbsp;</p>
</div>

</div>
<!-- Backup Database Dialog -->

<div class="tdcdialog" id="dialog_backupdatabase" title="Backup Database">
<h4>Name this backup:</h4>
<input id="backupname" name="backupname" style="min-width: 24em;" maxlength="160" type="text" /></div>
<!-- Restore Database Dialog -->

<div class="tdcdialog" id="dialog_restoredatabase" title="Restore Database">
<h4>Select existing backup to restore</h4>
<select id="backuplists" name="backuplists" size="16" style="min-width: 24em;"> </select></div>
<!-- Generic Delete Dialog -->

<div class="tdcdialog" id="dialog_delete">
<p id="deletemsg">delete:</p>

<p id="deletename" style="text-align: center;">this</p>

<p>&nbsp;</p>
</div>
<!-- Generic Message Dialog -->

<div class="tdcdialog" id="dialog_message" title="Message">
<p id="message">Are you OK?</p>

<p>&nbsp;</p>
</div>

<div class="tdcdialog" id="dialog_progress" title="Backup In Progress">
<p id="progressmessage">&nbsp;</p>

<div id="progressbar" style="position:relative;">
<div id="progress-label" style="position:absolute;left:45%;top:4px;text-shadow:1px 1px 0 #888;">Loading</div>
</div>
</div>
</div>
<!-- workarea --></div>
</div>
<!-- mcontainer -->

<div id="push">&nbsp;</div>
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