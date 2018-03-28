<!DOCTYPE html><?php
$noredir = true ;
require "session.php" ; 
if( empty($_SESSION['superadmin']) || $_SESSION['superadmin'] != "--SuperAdmin--" ) {
	header("Location: logon.php" );
}
?>
<html>
<head>
	<title>Touch Down Center - Company Management</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2014-04-14">		
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><?php echo "<link href=\"https://code.jquery.com/ui/1.11.0/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?> <script src="https://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type="text/javascript" src="https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1"></script><script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<script src="md5min.js"></script>
	<style type="text/css">
	
	.useritem {
		line-height: 24px;
	}
	
	</style>
<script>
// start up 
$(document).ready(function(){

$("#companygrid").jqGrid({        
   	url:'companygrid.php',
	datatype: "json",
	height: 280,
    colNames:['Company ID','Company Name', 'Contact Name', 'Email'],
    colModel :[ 
      {name:'com_id', index:'com_id', width:100, sortable:false}, 
      {name:'com_name', index:'com_name', width:300, sortable:false}, 
      {name:'com_contact', index:'com_contact', width:300, sortable:false}, 
      {name:'com_email', index:'com_email', width:200, sortable:false } 
    ],
   	rowNum:1000,
   	mtype: "GET",
	gridview: true,
    pager: '#companypager',
    viewrecords: true,
    caption: 'Company List'
});

// company edit dialog
$( "#dialog_company" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"Save": function() {
			$.getJSON("companysave.php", $("form#companyform").serialize(), function(resp){
				if( resp.res ) {
					$( "#dialog_company" ).dialog( "close" );
					$("#companygrid").trigger("reloadGrid");
				}
				else {
					if( resp.errormsg ) {
						alert( resp.errormsg );
					}
					else {
						alert("Saving configuration failed!");
					}
				}
			});		
		},
		Cancel: function() {
			$( "#dialog_company" ).dialog( "close" );
		}
	}
});

$("button").button();
$("button#btnew").click(function(e){
	e.preventDefault();
	$("form#companyform")[0].reset();
	
	$('form#companyform input[name="NewCompany"]').val( 1 ) ;
	$('form#companyform input[name="CompanyId"]').prop("readonly",false);
	$('form#companyform input[name="RootFolder"]').prop("readonly",false);
	$('form#companyform input[name="Database"]').prop("readonly",false);
		
	$("form#companyform").data("edit",false);
	$( "#dialog_company" ).dialog("option", "title", "Create A New Company Information");
	$( "#dialog_company" ).dialog("open");
});

function set_companyform( fdata )
{
	// set fields
	var field ;
	for (field in fdata) {
		var elm=$("form#companyform [name='"+field+"']");
		if( elm.length>0 ) {
			if( elm.attr("type")=="checkbox" ) {
				elm.prop("checked", (fdata[field]=='on' || fdata[field]=='y' || fdata[field]=='1' ));
			}
			else if( elm.attr("type")=="radio" ) {
				elm.filter('[value="'+fdata[field]+'"]').prop("checked",true);
			}
			else {
				elm.val(fdata[field]);
			}
		}
	}
}

$("button#btedit").click(function(e){
	e.preventDefault();
	var id=$("#companygrid").jqGrid('getGridParam','selrow') ;
	if( id == null ) {
		alert("Please select one company!");
		return ;
	}
	$.getJSON("companylist.php", {id:id}, function(resp){
		if( resp.res ) {
			$("form#companyform")[0].reset();
			$("form#companyform").data("edit",true);
			if( resp.companyinfo )
				set_companyform( resp.companyinfo ) ;
			if( resp.webset ) {
				set_companyform( resp.webset ) ;
			}

			$('form#companyform input[name="NewCompany"]').val( '' ) ;
			$('form#companyform input[name="CompanyId"]').prop("readonly",true);
			$('form#companyform input[name="RootFolder"]').prop("readonly",true);
			$('form#companyform input[name="Database"]').prop("readonly",true);
	
			$( "#dialog_company" ).dialog("option", "title", "Edit Company Information");
			$( "#dialog_company" ).dialog("open");
		}
	});
});

$("button#btremove").click(function(e){
	e.preventDefault();
	var id=$("#companygrid").jqGrid('getGridParam','selrow') ;
	if( id == null ) {
		alert("Please select one company!");
		return ;
	}
	if( confirm("Confirm to remove all data related to company : " + id ) ) {
		$( "#dialog_removeverify" ).data("company_id", id);
		$( "#dialog_removeverify" ).dialog("open");
	}
});

function gencnonce(bits)
{
  var hexch = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ " ;
  var output = "" ;
  for( var i=0; i<bits; i++ ) {
	  output += hexch.charAt(Math.random()*62);
  }
  return output ;
}

// change password dialog
$( "#dialog_changepasswd" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"OK": function() {
			var curpass = $("input[name='currentpasswd']").val();
			var password = $("input[name='newpassword']").val();
			var password2 = $("input[name='newpassword2']").val();
			if( password == password2 ) {
				var slt = gencnonce(20) ;
				var key = hex_md5("SuperAdmin:"+slt+":"+password) ;
				$.getJSON("sapassword.php", { pass:curpass, salt:slt, key: key }, function(resp){
					if( resp.res ) {
						$( "#dialog_changepasswd" ).dialog( "close" );
						alert("Change password success.");
					}
					else {
						if( resp.errormsg ) {
							alert( resp.errormsg );
						}
						else {
							$( "#dialog_changepasswd" ).dialog( "close" );
							alert("Change password failed!");
						}
					}
				});	
			}
			else {
				alert( "Password not match, please enter again!");
			}
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

$("button#btchangepasswd").click(function(e){
	e.preventDefault();
	$("form#passwordform")[0].reset();
	$( "#dialog_changepasswd" ).dialog("open");
});


// removing company to verify password dialog
$( "#dialog_removeverify" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons:{
		"OK": function() {
			$.getJSON("sapasswordverify.php", { p:1 }, function(resp){
				if( resp.res ) {
					var verpass = $("input[name='input_passwordverify']").val();
					var key = hex_md5("SuperAdmin:"+resp.s+":"+verpass) ;
					key = hex_md5(key+":"+resp.n) ;
					$.getJSON("sapasswordverify.php", { p:2,k:key }, function(resp){
						if( resp.res ) {
							var id = $( "#dialog_removeverify" ).data("company_id");
							$.getJSON("companyremove.php", {id:id}, function(resp){
								if( resp.res==1 ) {
									alert( "All data related to " + id + " has been removed!");
								}
								else if( resp.errormsg ) {
									alert( resp.errormsg );
								}
								else {
									alert("Remove company :"+id+" failed!");
								}
								$("#companygrid").trigger("reloadGrid");
							});							
							$( "#dialog_removeverify" ).dialog( "close" );							
						}
						else {
							if( resp.errormsg ) {
								alert( resp.errormsg );
							}
							else {
								alert("Password error!");
							}							
						}
					});
				}
			});
		}
	}
});

});
</script>
</head>
<body><div id="container">
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>


<div id="mcontainer">
<p id="title" style="text-align: center;">
<strong><span style="font-size:26px;">Company Management</span></strong></p>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">

<div id="grid">
    <table id="companygrid"></table>
    <div id="companypager"></div>
	<button id="btnew" >New</button>
	<button id="btedit" >Edit</button>
	<button id="btremove" >Remove</button>
</div>

<!-- company dialog -->
<div id="dialog_company">
<form id="companyform">
<input name="NewCompany" value="" type="hidden" />
<table border="0" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td style="text-align:right">Company ID:</td>
			<td><input maxlength="20" name="CompanyId" type="text" /></td>
			<td>*</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Company Root Folder:</td>
			<td><input name="RootFolder" size="40" type="text" /></td>
			<td>*</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Database:</td>
			<td><input name="Database" type="text" /></td>
			<td>*</td>
			<td>&nbsp;</td>
		</tr>		
		<tr>
			<td style="text-align:right">Company Name:</td>
			<td><input name="CompanyName" type="text" /></td>
			<td>*</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Time Zone:</td>
			<td>
			<select name="TimeZone">
<?php
$timezonelist=DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, "US");
foreach( $timezonelist as $tz ) {
	printf('<option value="%s">%s</option>', $tz, $tz );
};
?>			
			</select>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Map Default Area:</td>
			<td><input name="MapArea" type="text" value="USA" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Session Timeout Value:</td>
			<td><input name="SessionTimeout" type="text" value="900"/></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>		
		<tr>
			<td style="text-align:right">Address:</td>
			<td><input name="Address" size="40" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">City:</td>
			<td><input name="City" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">State/Province:</td>
			<td><input name="State" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Country:</td>
			<td><input name="Country" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Zip Code:</td>
			<td><input maxlength="7" name="ZipCode" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Tel#:</td>
			<td><input name="Tel" type="tel" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Fax#:</td>
			<td><input name="Fax" type="tel" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Contact Name:</td>
			<td><input name="ContactName" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Email Address:</td>
			<td><input name="ContactEmail" type="text" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>
</form>
</div>

<!-- Dialog Change Password -->
<div id="dialog_changepasswd" title="Super Admin Password" >

<form id="passwordform">
<table border="0" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td style="text-align:right">Current Password:</td>
			<td><input  name="currentpasswd" type="password" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">New Password:</td>
			<td><input name="newpassword" type="password" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right">Repeat New Password:</td>
			<td><input name="newpassword2" type="password" /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>
</form>
</div>

<div>
	<button id="btchangepasswd" >Change Password</button>
</div>

<!-- Dialog Verify Password for removing company -->
<div id="dialog_removeverify" title="Verify Super Admin Password" >
<form id="passwordverifyform">
	<p>Verify Password:</p>
	<input name="input_passwordverify" type="password" />
</form>
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