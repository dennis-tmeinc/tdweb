<!DOCTYPE html>
<html>
<head><?php
require 'session.php'; 

// remember settings sub page
session_save('settingpage', $_SERVER['REQUEST_URI'] );

// MySQL connection
if( $logon ) {
	$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
}
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-05-24">		
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.10.4/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script><script src="md5min.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
	
	.useritem {
		line-height: 24px;
	}
	
	</style>
	<script src="td_alert.js"></script><script>
function genrandom(len)
{
  var ranch = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ " ;
  var output = "" ;
  for( var i=0; i<len; i++ ) {
	  output += ranch.charAt(Math.random()*62);
  }
  return output ;
}
		
// start up 
$(document).ready(function(){
            			
$("button").button();	
$(".xbutton").button();	
$(".btset").buttonset();

$(".btset input").change(function(){
   location=$(this).attr("href");
});

function updateuserlist()
{
	$.getJSON("userlist.php", function(userlist){
		if( userlist.length>0) {
			$("table#usertable tr.useritem").remove();
			for( var i=0; i<userlist.length; i++) {
				if( !userlist[i].last_logon ) {
					userlist[i].last_logon="Never";
				}
				var htmlstr = '<tr class="useritem"><td>' +
							userlist[i].user_name + '</td><td>' +
							userlist[i].first_name + '</td><td>' +
							userlist[i].last_name + '</td><td>' +
							(( userlist[i].user_type == 'admin' && userlist[i].user_name != 'admin' )?"power user":userlist[i].user_type)
							+ '</td><td>' +
							userlist[i].email + '</td><td>' +
							userlist[i].telephone + '</td><td>' +
							userlist[i].last_logon + '</td><td>' +
<?php 
if( $_SESSION['user'] == "admin" ) {
?>
							'<input class="edituser" src="res/button_edit.png" style="width: 24px; height: 24px;" type="image" /><input class="deleteuser" src="res/button_delete.png" style="width: 24px; height: 24px;" type="image" />' + 
<?php
}else{
?>							((userlist[i].user_name == <?php echo "'$_SESSION[user]'" ?> )?'<input class="edituser" src="res/button_edit.png" style="width: 24px; height: 24px;" type="image" />':'') +
<?php
}
?>
							'</td></tr>';
				$("table#usertable").append(htmlstr);
				$($("table#usertable tr.useritem")[i]).data("user", userlist[i]);
			}
			$("table#usertable tr.useritem").filter(':odd').addClass("alt");
			$("table#usertable input.edituser" ).click(function() {
				$("form#userform")[0].reset();
				var user = $(this.parentNode.parentNode).data("user");
				// fill form fields
				var field ;
				for (field in user) {
					var elm=$("form#userform input[name='"+field+"']");
					if( elm.length>0 ) {
						if( elm.attr("type")=="checkbox" ) {
							elm.prop("checked", (user[field]=='1' || user[field]=='y' ));
						}
						else if( elm.attr("type")=="radio" ) {
							elm.filter("[value='"+user[field]+"']").prop("checked",true);
						}
						else {
							elm.val(user[field]);
						}
					}
				}
				$("form#userform").data("xuser", user.user_name) ;
				$('#dialog_user #userform input[name="user_name"]').prop("readonly",true);
				// generate checking password
				$('#dialog_user').data("xpass1",genrandom(8));
				$('#dialog_user').data("xpass2",genrandom(8));
				$('#dialog_user #userform input[name="password"]').val($('#dialog_user').data("xpass1"));
				$('#dialog_user #userform input[name="confirmpassword"]').val($('#dialog_user').data("xpass2"));
				
				$( "#dialog_user" ).dialog( "option", "title", "Edit User ("+user.user_name+")");
				$( "#dialog_user" ).dialog( "open" );	
			});

			$( "table#usertable input.deleteuser" ).click(function() {
				var user = $(this.parentNode.parentNode).data("user");
				$( "#dialog_deleteuser #deleteusername" ).text(user.user_name) ;
				$( "#dialog_deleteuser" ).dialog( "open" );
			});
		}
	});
}	
	
updateuserlist();

$("form#userform").submit(function(e){
	e.preventDefault();
	// get form data
	var formdata = $('#dialog_user #userform').serializeArray();
	var formv = new Object ;
	for( var i=0; i<formdata.length; i++) {
		formv[formdata[i].name] = formdata[i].value ;
	}
	if( formv.password==$('#dialog_user').data("xpass1") && formv.confirmpassword==$('#dialog_user').data("xpass2") ) {
		// no change on password
		formv.password="";
		formv.confirmpassword="";
	}
	else if( formv.password == formv.confirmpassword && formv.user_name.length>0 ) {
		// password match
		formv.confirmpassword=genrandom(32);<?php 
$sql = "SELECT user_password FROM app_user;";
if( $result = $conn->query($sql) ) {
	if( $field = $result->fetch_field() ){
		$keylen=$field->length;
	}
}

if( !empty($keylen) && $keylen>=68 ) {	// new key fields
?>			formv.keytype=1;formv.password=hex_md5(formv.user_name+":"+formv.confirmpassword+":"+formv.password) ;
<?php			
}
else {
?>			formv.keytype=0;
		if( formv.password.length>0 ){
		var acode = "a".charCodeAt(0);
		var bcode = ""+formv.password.length+formv.password;
		var blen = bcode.length ;
		var offset=[3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5, 8, 9, 7, 9, 3, 2, 3, 8, 4];
		formv.password="";
		for( var i=0; i<20; i++) {
			formv.password += String.fromCharCode(acode+(bcode.charCodeAt(i%blen)+offset[i])%26);
		}}
		else 
		formv.password=hex_md5(formv.user_name+":"+formv.confirmpassword+":"+formv.password) ;
<?php
}
?>
	}
	else {
		alert("Username/Password Error!");
		return ;
	}
	var xuser = $("form#userform").data("xuser");
	var ajaxreq ;
	if( xuser ) {	// edit
		formv.xuser=xuser ;
		ajaxreq="usersave.php";
	}
	else {			// new
		ajaxreq="usernew.php";
	}
	
	$.getJSON(ajaxreq, formv, function(data){
		if( data.res > 0 ) {
			// success
			$( '#dialog_user' ).dialog( "close" );
			updateuserlist();
		}
		else if( data.errormsg ) {
			alert( data.errormsg );
		}
		else {
			alert( "User update failed!");
		}
	});
});	


// add more init functions
$( "#dialog_user" ).dialog(
{
  autoOpen: false,
  width:"auto",
  modal: true,
  open : function(){
	if( $('#dialog_user #userform input[name="user_name"]').val() == "<?php echo $_SESSION['user'] ;?>" ) {
		$('#dialog_user #userform input[name="user_type"]').prop("disabled",true);
	}
	else {
		$('#dialog_user #userform input[name="user_type"]').prop("disabled",false);
	}
  },
  buttons:{
    "Save": function() {
		$("form#userform input[type='submit']").click();
    },
    Cancel: function() {
       $( this ).dialog( "close" );
    }
  }
});

// load user dialog default
$.getJSON("userfields.php",function(resp){
	if( resp.res == 1 ) {
		var f ;
		for(f=0; f<resp.fields.length; f++) {
			if( resp.fields[f].maxlength ) {
				$("form#userform input[name='"+resp.fields[f].name+"']").attr("maxlength",resp.fields[f].maxlength );
			}
			if( resp.fields[f].defvalue ) {
				$("form#userform input[name='"+resp.fields[f].name+"']").attr("value",resp.fields[f].defvalue );
			}
		}
	}
});

$( "#dialog_deleteuser" ).dialog(
{
  autoOpen: false,
  width:"auto",
  modal: true,
  buttons:{
    "Yes": function() {
		var formv=new Object ;
		formv.username=$( "#dialog_deleteuser #deleteusername" ).text() ;
		$.post("userdel.php", formv, function(resp){
			if( resp.res == 1 ) {
				// success
				$( '#dialog_deleteuser' ).dialog( "close" );
				if( resp.user == "<?php echo $_SESSION['user'] ;?>" ) {	// current user deleted?
					location="logon.php" ;
				}
				else 
					updateuserlist();
			}
			else if( resp.errormsg ) {
				alert( resp.errormsg );
			}
			else {
				alert( "Delete User Failed!");
			}
		}
		);
    },
    Cancel: function() {
       $( this ).dialog( "close" );
    }
  }
});

$( "#adduser" ).click(function() {
	$("form#userform").removeData("xuser") ;
    $("#dialog_user #userform")[0].reset();
	$('#dialog_user #userform input[name="user_name"]').prop("readonly",false);
	$( "#dialog_user" ).dialog( "option", "title", "New User" );
    $( "#dialog_user" ).dialog( "open" );
});
	
$( "#deletealluser" ).click(function() {
	if( confirm("Are you sure to delete all users?") ) {
		$.getJSON("userdel.php?alluser=yes", function(resp){
			if( resp.res == 1 ) {
				updateuserlist();	
			}
		});
	}
});
	
$("form#formdriverimport").submit(function(e){
	if( confirm("Import user list from CSV file?") ) {
	}
	else {
		e.preventDefault();
	}
});

$("form#formdriverexport").submit(function(e){
	if( !confirm("Export user list to CSV file?") ) {
		e.preventDefault();
	}
});	
	
// show up 
$('#rcontainer').show(200);

});
        
	</script>
</head>
<body><div id="container">
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?>
	<?php if(  $_SESSION['user_type'] == "operator"  ){ ?>
	<li><a class="lmenu" href="driveby.php"><img onmouseout="this.src='res/side-driveby-logo-clear.png'" onmouseover="this.src='res/side-driveby-logo-fade.png'" src="res/side-driveby-logo-clear.png" /> </a></li>
	<?php } ?>	
	<li><img src="res/side-settings-logo-green.png" /></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">SETTINGS</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">
<p class="btset">
<input name="btset" href="settingsfleet.php" id="btfleet" type="radio" /><label for="btfleet">Fleet Setup</label>
<input name="btset" checked="checked" href="settingsuser.php" id="btuser" type="radio" /><label for="btuser">User Accounts</label> 
<input name="btset" href="settingssystem.php" id="btsys" type="radio" /><label for="btsys">System Configuration</label>
</p>

<h4><strong>User Accounts</strong></h4>
<!-- Add / Edit User dialog -->

<div class="tdcdialog" id="dialog_user" title="User">
<form id="userform">
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;" >
	<tbody>
		<tr>
			<td style="text-align: right;">User Name:</td>
			<td><input name="user_name" type="text" required /></td>
			<td style="text-align: right;">Title:</td>
			<td><input name="title" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">First Name:</td>
			<td><input name="first_name" type="text" /></td>
			<td style="text-align: right;">Last Name:</td>
			<td><input name="last_name" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Email:</td>
			<td><input name="email" type="text" /></td>
			<td style="text-align: right;">Telephone:</td>
			<td><input name="telephone" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">User Level:</td>
			<td colspan="3"><input checked="checked" name="user_type" type="radio" value="user" />User<input id="usertypeadmin" name="user_type" type="radio" value="admin" />Power User</td>
		</tr>
		<tr>
			<td style="text-align: right;">Password:</td>
			<td><input name="password" type="password" /></td>
			<td style="text-align: right;">Confirm Password:</td>
			<td><input name="confirmpassword" type="password" /></td>
		</tr>
	</tbody>
</table>

<fieldset><legend> User Notifications: </legend>

<p><input name="notify_requested_video" type="checkbox" />Requested Video Downloaded</p>

<p><input name="notify_marked_video" type="checkbox" />Marked Video Downloaded</p>

<p><input name="notify_sys_health" type="checkbox" />System Health Errors</p>
</fieldset>
<input type="submit" style="display:none" />
</form>
</div>
<!-- End User Dialog --><!-- delete User comfirm dialog -->

<div class="tdcdialog" id="dialog_deleteuser" title="Delete User">
<p>Do you want to delete user:</p>

<p id="deleteusername" style="text-align: center;">noauser</p>

<p>&nbsp;</p>
</div>
<!-- End delete User comfirm dialog -->

<div class="ui-widget ui-widget-content ui-corner-all">
<div style="margin:12px">
<table border="0" cellpadding="1" cellspacing="1" class="listtable" id="usertable" style="width: 100%;">
	<caption>
	<h4 style="text-align: left;">Existing Users:</h4>
	</caption>
	<tbody>
		<tr>
			<th>User Name</th>
			<th>First Name</th>
			<th>Last Name</th>
			<th>User Level</th>
			<th>Email</th>
			<th>Telephone</th>
			<th>Last Login</th>
			<th>Edit/Delete</th>
		</tr>
	</tbody>
</table>
<?php 
if( $_SESSION['user'] == "admin" ) {
?> 
<button id="adduser"><img src="res/button_add.png" />Add User</button>
<button id="deletealluser"><img src="res/button_delete.png" />Delete All</button>

<form id="formuserimport" enctype="multipart/form-data" method="POST" action="userimport.php" >
<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
<input class="xbutton" value="Import" type="submit" />
<input name="importfile" type="file" required />
</form>

<form id="formuserrexport" enctype="application/x-www-form-urlencoded" method="get" action="userexport.php" target="_blank" >
<input class="xbutton" value="Export" type="submit" />
</form>

<?php } ?>
</div>
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