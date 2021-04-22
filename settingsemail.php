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
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-<?php echo $jqver; ?>.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /><script src="jq/jquery-ui.js"></script><script>if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
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

$( ".tdcdialog#dialog_testemail" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Cancel": function() {
			$( this ).dialog( "close" );
		},
		"Send": function() {
			var form = $('form#emailsetup').serializeArray();
			form.forEach( function(v) {
				if( v.name == "recipient" ) {
					v.value=$("input#testreceiver").val();
				}
			});
			$.getJSON("emailtest.php", form, function(data){
				if( data.res == 1 ) {
					$( ".tdcdialog#dialog_message #message" ).text(data.msg);
				}
				$( ".tdcdialog#dialog_message" ).dialog("open");
			});
			$( this ).dialog( "close" );
		}
	}
});

// Email Server Test
$("button#emailtest").click(function(e){
	e.preventDefault(); 
	$( ".tdcdialog#dialog_testemail" ).dialog("open");
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
<input name="btset" href="settingssystem.php" id="btsys" type="radio" /><label for="btsys">System Configuration</label>
<input name="btset" checked="checked" href="settingsemail.php" id="btemail" type="radio" /><label for="btemail">Email Configuration</label> 
</p>

<h4><strong>E-mail Alert Settings</strong></h4>

<div id="setting-email">
<form id="emailsetup">
<?php if( empty($support_multicompany) ) { ?>
<div id="emailserver">
<?php } else { ?>
<div id="emailserver" style="display:none" >
<?php } ?>
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
		<tr>
			<td style="text-align: right;"></td>
			<td><button id="emailtest">Test</button></td>
		</tr>		
	</tbody>
</table>
</fieldset>
</div>

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
<!-- workarea --></div>

<!-- message box -->
<div class="tdcdialog" id="dialog_message" title="Message">
<p id="message">Are you OK?</p>

<p>&nbsp;</p>
</div>

<div class="tdcdialog" id="dialog_testemail" title="Send Testing Email">

<p>Enter email receiver:</p>
<input name="testreceiver", id="testreceiver" type="email" />

<p>&nbsp;</p>
</div>

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