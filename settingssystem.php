<!DOCTYPE html>
<html>
<head><?php 
require 'config.php' ; 
require 'session.php'; 

// remember settings sub page
$_SESSION['settingpage']=$_SERVER['REQUEST_URI'] ;

// apply new theme
if( !empty($_COOKIE['tdcui'])){
	$ui_theme=$_COOKIE["tdcui"] ;
	$_SESSION['ui']=$ui_theme ;

	// save user setting
	$uset=array();
	$userfile=@fopen( $user_path."/".$_SESSION['user'], "r" );
	if( $userfile ) {
		$ujs = fread ( $userfile, 4096 );
		fclose($userfile);
		$uset = json_decode($ujs,true);
	}
	$uset['ui']=$ui_theme ;
	$userfile=@fopen( $user_path."/".$_SESSION['user'], "w" );
	if( $userfile ) {
		$ujs=json_encode($uset);
		fwrite ($userfile, $ujs );
		fclose($userfile);
	}
}

// session_write
session_write();

?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script>if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>
	<link href="jq/ui-timepicker-addon.css" rel="stylesheet" type="text/css" /><script src="jq/ui-timepicker-addon.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none;}" ?>
	</style>
	<script>
// start up 

function setcookie(cname,value,expires) {
	var ck=cname+"="+escape(value);
	if( expires ) {
		var exp = new Date();
    	exp.setTime(exp.getTime()+expires*1000);
        ck += ";expires="+exp.toGMTString();
    }
   	document.cookie = ck ;
}

$(document).ready(function(){
            
$("button").button();	
$(".btset").buttonset();
$(".btset input").change(function(){
   location=$(this).attr("href");
});
			
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
			$.getJSON("backupgetprogress.php", function(resp){
				if( resp.res==1 ) {
					var toval = parseFloat(resp.percentage) ;
					if( toval < 0 ) {
						$("#progressmessage").text("Server script not started!!!");
						progInUse = false ;
					}
					else if( toval>= 100 ) {
						$( "#progressbar" ).progressbar( "value", 100 );
						$( "#progress-label" ).text( "Complete!" );
						var complete=new Object ;
						complete.complete="1" ;
						$.getJSON("backupgetprogress.php", complete);
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
		Microsoft.Maps.Events.addHandler(pin, 'drag', function(e){
			var latlon = e.entity.getLocation();
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
	$("#mssmap").show("slow");
});

$("button#msshidemap").click(function(event){
	event.preventDefault();
	$("#mssmap").hide("slow");
});

// Local MSS Tab
function localmss_reload()
{
	$("form#localmss")[0].reset();
	$.getJSON("localmssload.php", function(localmss){
		// fill form fields
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
	setcookie("tdcui",$(this).attr("id"),10);
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
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<!--	<li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li> -->
	<li><img src="res/side-settings-logo-green.png" /></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
bus1 : uploading abc
bus2 : fan alert
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
</p>

<h4><strong>System Configuration</strong></h4>

<div id="settingtabs">
<ul>
	<li><a href="#setting-storage">Storage&amp;Backup</a></li>
	<li><a href="#setting-event">Default Event Parameters</a></li>
	<li><a href="#setting-mss">MSS Setup</a></li>
	<li><a href="#setting-localmss">Local MSS Setup</a></li>
	<li><a href="#setting-email">E-mail Setup</a></li>
	<li><a href="#setting-ui">User Interface</a></li>
</ul>

<div id="setting-storage">
<form id="storageform">
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<caption>
	<h3>Storage Setup</h3>
	</caption>
	<tbody>
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
		<tr>
			<td style="text-align: right;">Keep GPS Data Within:</td>
			<td><select name="keepGpsLogDataForDays"><option value="93">3 months</option><option value="186">6 months</option><option value="366">1 years</option><option value="732">2 years</option><option value="1464">4 years</option><option value="0">Forever</option> </select></td>
		</tr>
		<tr>
			<td style="text-align: right;">Keep Video Data Within:</td>
			<td><select name="keepVideoDataForDays"><option value="31">1 month</option><option value="62">2 months</option><option value="93">3 months</option><option value="124">4 months</option><option value="155">5 months</option><option value="186">6 months</option><option value="0">Forever</option> </select></td>
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
			<td style="text-align: right;">Stopping(s)</td>
			<td><input name="stop_duration" type="text" /></td>
			<td style="text-align: right;">Racing Start</td>
			<td><input name="racing_start" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td style="text-align: right;">Des. Stops(s)</td>
			<td><input name="bstop_duration" type="text" /></td>
			<td style="text-align: right;">Rear Impact</td>
			<td><input name="rear_impact" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td style="text-align: right;">Idling (s)</td>
			<td><input name="idle_duration" type="text" /></td>
			<td style="text-align: right;">Hard Brake</td>
			<td><input name="hard_brake" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td style="text-align: right;">Parking (s)</td>
			<td><input name="park_duration" type="text" /></td>
			<td style="text-align: right;">Front Impact</td>
			<td><input name="front_impact" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td style="text-align: right;">Speeding</td>
			<td><input name="speed" type="text" />mph</td>
			<td style="text-align: right;">Hard Turn</td>
			<td><input name="hard_turn" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td style="text-align: right;">MaxUploadTime</td>
			<td><input name="maxuploadtime" type="text" />minute(s)</td>
			<td style="text-align: right;">Side Impact</td>
			<td><input name="side_impact" size="5" type="text" />g</td>
		</tr>
		<tr>
			<td style="text-align: right;">MaxConcurrentUpload</td>
			<td><input name="maxconcurrentupload" type="text" /></td>
			<td style="text-align: right;">Bumpy Ride</td>
			<td><input name="bumpy_ride" size="5" type="text" />g</td>
		</tr>
	</tbody>
</table>
</form>

<p><button id="eventsave">Save</button><button id="eventreset">Cancel</button></p>
</div>

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

<div id="setting-localmss">
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

<div id="setting-email">
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
		</tr>
		<tr>
			<td><textarea cols="35" name="recipient" rows="10"></textarea></td>
			<td><textarea cols="35" name="alertRecipients" rows="10"></textarea></td>
		</tr>
	</tbody>
</table>

<p>Send E-mail At: <input maxlength="8" name="tmSendDaily" type="text" value="19:00" /></p>

</form>
<button id="emailsave">Save</button><button id="emailreset">Cancel</button>

<p>&nbsp;</p>
</div>

<div id="setting-ui">
<table border="0" cellpadding="1" cellspacing="5">
	<caption>
	<h3>Select UI Theme</h3>
	</caption>
	<tbody>
		<tr>
			<td><input alt="UI Lightness" id="ui-lightness" src="http://jqueryui.com/resources/images/themeGallery/theme_90_ui_light.png" style="width: 90px; height: 80px;" title="UI lightness" type="image" /></td>
			<td><input alt="UI darkness" id="ui-darkness" src="http://jqueryui.com/resources/images/themeGallery/theme_90_ui_dark.png" style="width: 90px; height: 80px;" title="UI darkness" type="image" /></td>
			<td><input alt="Smoothness" id="smoothness" src="http://jqueryui.com/resources/images/themeGallery/theme_90_smoothness.png" style="width: 90px; height: 80px;" title="Smoothness" type="image" /></td>
			<td><input alt="Start" id="start" src="http://jqueryui.com/resources/images/themeGallery/theme_90_start_menu.png" style="width: 90px; height: 80px;" title="Start" type="image" /></td>
			<td><input alt="Redmond" id="redmond" src="http://jqueryui.com/resources/images/themeGallery/theme_90_windoze.png" style="width: 90px; height: 80px;" title="Redmond" type="image" /></td>
			<td><input alt="Sunny" id="sunny" src="http://jqueryui.com/resources/images/themeGallery/theme_90_sunny.png" style="width: 90px; height: 80px;" title="Sunny" type="image" /></td>
		</tr>
		<tr>
			<td style="text-align: center;">UI lightness</td>
			<td style="text-align: center;">UI darkness</td>
			<td style="text-align: center;">Smoothness</td>
			<td style="text-align: center;">Start</td>
			<td style="text-align: center;">Redmond</td>
			<td style="text-align: center;">Sunny</td>
		</tr>
		<tr>
			<td><input alt="Overcast" id="overcast" src="http://jqueryui.com/resources/images/themeGallery/theme_90_overcast.png" style="width: 90px; height: 80px;" title="Over cast" type="image" /></td>
			<td><input alt="Le Frog" id="le-frog" src="http://jqueryui.com/resources/images/themeGallery/theme_90_le_frog.png" style="width: 90px; height: 80px;" title="Le Frog" type="image" /></td>
			<td><input alt="Flick" id="flick" src="http://jqueryui.com/resources/images/themeGallery/theme_90_flick.png" style="width: 90px; height: 80px;" title="Flick" type="image" /></td>
			<td><input alt="Pepper Grinder" id="pepper-grinder" src="http://jqueryui.com/resources/images/themeGallery/theme_90_pepper_grinder.png" style="width: 90px; height: 80px;" title="Pepper Grinder" type="image" /></td>
			<td><input alt="Eggplant" id="eggplant" src="http://jqueryui.com/resources/images/themeGallery/theme_90_eggplant.png" style="width: 90px; height: 80px;" title="Eggplant" type="image" /></td>
			<td><input alt="Dark Hive" id="dark-hive" src="http://jqueryui.com/resources/images/themeGallery/theme_90_dark_hive.png" style="width: 90px; height: 80px;" title="Dark Hive" type="image" /></td>
		</tr>
		<tr>
			<td style="text-align: center;">Overcast</td>
			<td style="text-align: center;">Le Frog</td>
			<td style="text-align: center;">Flick</td>
			<td style="text-align: center;">Pepper Grinder</td>
			<td style="text-align: center;">Eggplant</td>
			<td style="text-align: center;">Dark Hive</td>
		</tr>
		<tr>
			<td><input alt="Cupertino" id="cupertino" src="http://jqueryui.com/resources/images/themeGallery/theme_90_cupertino.png" style="width: 90px; height: 80px;" title="Cupertino" type="image" /></td>
			<td><input alt="South Street" id="south-street" src="http://jqueryui.com/resources/images/themeGallery/theme_90_south_street.png" style="width: 90px; height: 80px;" title="South Street" type="image" /></td>
			<td><input alt="Blitzer" id="blitzer" src="http://jqueryui.com/resources/images/themeGallery/theme_90_blitzer.png" style="width: 90px; height: 80px;" title="Blitzer" type="image" /></td>
			<td><input alt="Humanity" id="humanity" src="http://jqueryui.com/resources/images/themeGallery/theme_90_humanity.png" style="width: 90px; height: 80px;" title="Humanity" type="image" /></td>
			<td><input alt="Hot Sneaks" id="hot-sneaks" src="http://jqueryui.com/resources/images/themeGallery/theme_90_hot_sneaks.png" style="width: 90px; height: 80px;" title="Hot Sneaks" type="image" /></td>
			<td><input alt="Excite Bike" id="excite-bike" src="http://jqueryui.com/resources/images/themeGallery/theme_90_excite_bike.png" style="width: 90px; height: 80px;" title="Excite Bike" type="image" /></td>
		</tr>
		<tr>
			<td style="text-align: center;">Cupertino</td>
			<td style="text-align: center;">South Street</td>
			<td style="text-align: center;">Blitzer</td>
			<td style="text-align: center;">Humanity</td>
			<td style="text-align: center;">Hot Sneaks</td>
			<td style="text-align: center;">Excite Bike</td>
		</tr>
		<tr>
			<td><input alt="Vader" id="vader" src="http://jqueryui.com/resources/images/themeGallery/theme_90_black_matte.png" style="width: 90px; height: 80px;" title="Vader" type="image" /></td>
			<td><input alt="Dot Luv" id="dot-luv" src="http://jqueryui.com/resources/images/themeGallery/theme_90_dot_luv.png" style="width: 90px; height: 80px;" title="Dot Luv" type="image" /></td>
			<td><input alt="Mint Choc" id="mint-choc" src="http://jqueryui.com/resources/images/themeGallery/theme_90_mint_choco.png" style="width: 90px; height: 80px;" title="Mint Choc" type="image" /></td>
			<td><input alt="Black Tie" id="black-tie" src="http://jqueryui.com/resources/images/themeGallery/theme_90_black_tie.png" style="width: 90px; height: 80px;" title="Black Tie" type="image" /></td>
			<td><input alt="Trontastic" id="trontastic" src="http://jqueryui.com/resources/images/themeGallery/theme_90_trontastic.png" style="width: 90px; height: 80px;" title="Trontastic" type="image" /></td>
			<td><input alt="Swanky Purse" id="swanky-purse" src="http://jqueryui.com/resources/images/themeGallery/theme_90_swanky_purse.png" style="width: 90px; height: 80px;" title="Swanky Purse" type="image" /></td>
		</tr>
		<tr>
			<td style="text-align: center;">Vader</td>
			<td style="text-align: center;">Dot Luv</td>
			<td style="text-align: center;">Mint Choc</td>
			<td style="text-align: center;">Black Tie</td>
			<td style="text-align: center;">Trontastic</td>
			<td style="text-align: center;">Swanky Purse</td>
		</tr>
	</tbody>
</table>
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
<div style="float:left"><span id="servertime" style="color:#800080;font-size: 11px;"><?php
echo date("Y-m-d H:i") ;
?> </span></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>