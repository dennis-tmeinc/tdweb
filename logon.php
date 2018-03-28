<!DOCTYPE html>
<html>
<head><?php 
$noredir = 1 ;
require_once 'session.php' ;
if( isset($_REQUEST['cid'])) {
$_SESSION['clientid'] = $_REQUEST['cid'];
session_write();
header( 'Location: logon.php' );
}
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME V2.5">
	<meta name="author" content="Dennis Chen @ TME, 2013-06-15">	
	<link rel="shortcut icon" href="/favicon.ico" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.11.0.min.js"></script><?php echo "<link href=\"http://code.jquery.com/ui/1.11.0/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script><script>(window.jQuery || document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>'));</script>
	<script src="md5min.js"></script>
	<style> body { display:none; } </style>
	<script>
// start up 

$(document).ready(function(){

$("button").button();

<?php if( !empty($_COOKIE['ui'])) { ?>
// update cookie
var d = new Date();
d.setTime(d.getTime()+180*24*60*60*1000);
document.cookie =  "ui=" + escape( "<?php echo $_COOKIE['ui'] ?>" )+"; expires="+d.toGMTString();
<?php } ?>
	
function gencnonce(bits)
{
  var hexch = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ " ;
  var output = "" ;
  for( var i=0; i<bits; i++ ) {
	  output += hexch.charAt(Math.random()*62);
  }
  return output ;
}

function wait( w )
{
    if( w ) {
		$("body").append('<div class="wait"></div>');
	}
	else {
		$("div.wait").remove();
	}
}

function pwdEncode(pwd)
{
	if( pwd.length==0 ) return "";
	var acode = "a".charCodeAt(0);
	var bcode = ""+pwd.length+pwd;
	var blen = bcode.length ;
	var ecd="";
	var offset=[3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5, 8, 9, 7, 9, 3, 2, 3, 8, 4];
	for( var i=0; i<20; i++) {
		ecd += String.fromCharCode(acode+(bcode.charCodeAt(i%blen)+offset[i])%26);
	}
	return ecd;
}

$("form").submit(function(e){
	e.preventDefault();
	var userid=$("#userid").val();
	var clientid=$("#clientid").val();

	if (typeof(Storage) !== "undefined") {
		localStorage.setItem("ClientID", clientid);
		localStorage.setItem("UserId", userid);
	} 
	
	wait(1);
	var nonce = gencnonce(10);
	
	$.post("signuser.php", { user: userid, c: clientid, n: nonce }, function(data) {
		wait(0);
		if( data.res == 1 && data.user.toLowerCase() == userid.toLowerCase() ) {
		  var vcnonce=gencnonce(64);
		  var ha1 ;
		  if( data.keytype==0 ){
		  ha1 = hex_md5(data.user+":"+data.slt+":"+pwdEncode($("#password").val())) ;
		  }
		  else if(data.keytype==1){
		  ha1 = hex_md5(data.user+":"+data.slt+":"+$("#password").val()) ;
		  }
		  else {
			alert("Wrong keys!");
		  }
		  var ha2 = hex_md5(vcnonce+":"+data.user+":"+data.nonce);
		  var vresult = hex_md5(ha1+":"+ha2+":"+data.nonce+":"+vcnonce);
		  wait(1);
		  $.post("signkey.php", { user: data.user, cnonce: vcnonce, result: vresult }, function(kdata){
			wait(0);
			if( kdata.res == 1 && kdata.user == data.user ) {
				if( sessionStorage ) {
					// clear local session
					sessionStorage.clear();
				}			
				window.location=kdata.page;
			}
			else {
			  alert("Password error!") ;
			}
		  }, "json" );
		}
		else {
			if( data.errormsg ){
				alert(data.errormsg);
			}
			else {
				alert("User name error!") ;
			}
		}
	}, "json" );
});

$(window).resize(function(){
   var workarea = $("#workarea") ;
   var nh = window.innerHeight - workarea.offset().top -$("#footer").outerHeight() - 32 ;
   if( nh != workarea.height() ) {	// height changed
	  workarea.height( nh );
   }
});

setTimeout( '$(window).resize();' , 200);

$("body").show();

if (typeof(Storage) !== "undefined") {
	$("input#clientid").val(localStorage.getItem("ClientID"));
	$("input#userid").val(localStorage.getItem("UserId"));
} 


});
 
</script>  
</head>
<body>
<div id="rcontainer">
<div id="workarea" style="width:752px;margin:auto;min-height:400px;"> 
<div>&nbsp;</div>
<img alt="Touch Down Center (Internet Connection Required)" src="res/main-logo-top.jpg" />
<form style="padding-left:60px;padding-right:60px;padding-top:20px;padding-bottom:20px;">
<fieldset><legend> Sign in to Touch Down Center </legend>
<div style="padding-left:20px;">

<?php if(!empty($support_multicompany) ) { ?>
<div id="dclientid">
<p>Client ID<br />
<input id="clientid" name="clientid" value="<?php echo $_SESSION['clientid']; ?>" /></p>
</div>
<?php } ?>
<p>User ID<br />
<input id="userid" name="userid" type="text" /></p>

<p>Password<br />
<input id="password" name="password" type="password" /></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:95%">
	<tbody>
		<tr>
			<td><button id="usersignin" type="submit">Sign In</button></td>
			<td style="text-align: right;"><?php 
				if( !empty( $_SESSION['clientid'] ) ) echo $_SESSION['clientid'] ;
			?></td>
		</tr>
	</tbody>
</table>

</div>
</fieldset>
</form>
</div>
<!-- workarea --></div>
<!-- mcontainer -->

<div id="footer">
<hr />
<div id="footerline" style="padding-left:24px;padding-right:24px">
<div style="float:left"><span  id="servertime"  style="color:#800080;font-size: 11px;"><?php
echo date("Y-m-d H:i") ;
?> </span></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>

