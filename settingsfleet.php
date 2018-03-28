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
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
	<style type="text/css"><?php echo "#rcontainer { display:none }" ?>

	option.dirty {
		color:#350;
		background-color:#fee;
	}
	</style>
	<script src="td_alert.js"></script><script>
    // start up 
        
$(document).ready(function(){
				
$("button").button();	
$(".btset").buttonset();

$(".xbutton").button();	

$(".btset input").change(function(){
   location=$(this).attr("href");
});		
		
var tab=0;
if( sessionStorage ) {
	var tdsess = sessionStorage.getItem('tdsess');
	var localsession = {} ;
	if( tdsess ) {
		localsession = JSON.parse(tdsess);
	}
	if( localsession.setting_fleet_tab ) {
		tab = localsession.setting_fleet_tab;
	}
}

$( "#settingtabs" ).tabs( {
	active: tab,
	activate: function( event, ui ) {
		tab = ui.newTab.index() ;
		set_tab();
	}
});	

// set tabs
function set_tab()
{
	if( sessionStorage ) {
		var tdsess = sessionStorage.getItem('tdsess');
		var localsession = {};
		if( tdsess ) {
			localsession = JSON.parse(tdsess);
		}
		localsession.setting_fleet_tab=tab;
		sessionStorage.setItem('tdsess', JSON.stringify(localsession));
	}
	switch(tab)
	{
	case 0:
		updvehiclelist();
		break;
	case 1:
		updatedriverlist();
		break;
	case 2:
		update_group();
		break;
	case 3:
		tab_zone();
		break;
	}	
}

// vehicles
function updvehiclelist()
{
	$.getJSON("vehiclelist.php", function(resp){
		if( resp.res == 1 ) {
			var vehiclelist = resp.vehiclelist ;

			var htmlstr = "";
			for( var i=0; i<vehiclelist.length; i++) {
				htmlstr += '<tr class="vehlist"><td>' +
							vehiclelist[i].vehicle_name +'</td><td>' +
							vehiclelist[i].vehicle_plate + '</td><td>' +
							vehiclelist[i].vehicle_model +'</td><td>' +
							vehiclelist[i].vehicle_year + '</td>' +
							'<td><input class="editvehicle" src="res/button_edit.png" style="width: 20px; height: 20px;" type="image" /><input class="deletevehicle" src="res/button_delete.png" style="width: 20px; height: 20px;" type="image" /></td></tr>' ;				
			}
			// remove all tr
			$("table#vehiclelist tr.vehlist").remove();
			$("table#vehiclelist ").append(htmlstr);
			
			$('table#vehiclelist tr.vehlist').filter(':odd').addClass("alt");
							
			$( "table#vehiclelist .editvehicle" ).click(function() {
				var mytr = this.parentNode.parentNode ;
				var vname = $(mytr.childNodes[0]);
				var form=new Object ;
				form.vehicle_name=vname.text() ;
				$.getJSON("vehiclelist.php", form, function(resp){
					if( resp.res == 1 ) { 
						var vehicle=resp.vehiclelist[0] ;
						$("form#editvehicle")[0].reset();
						$("form#editvehicle").data("oname",vehicle.vehicle_name);
						// set fields
						var field ;
						for (field in vehicle) {
							var elm=$("form#editvehicle [name='"+field+"']");
							if( elm.length>0 ) {
								if( elm.attr("type")=="checkbox" ) {
									elm.prop("checked", (vehicle[field]=='on' || vehicle[field]=='y' || vehicle[field]=='1' ));
								}
								else if( elm.attr("type")=="radio" ) {
									elm.filter('[value="'+vehicle[field]+'"]').prop("checked",true);
								}
								else {
									elm.val(vehicle[field]);
								}
							}
						}
						$( "#dialog_vehicle" ).dialog( "option", "title", "Edit Vehicle" );
						$( "#dialog_vehicle" ).dialog( "open" );
					}
				});
			});
			// delete vehicle
			$( "table#vehiclelist .deletevehicle" ).click(function() {
				var mytr = this.parentNode.parentNode ;
				var vname = $(mytr.childNodes[0]).text();
				$( ".tdcdialog#dialog_delete #deletemsg" ).text("Do you want to delete vehicle:");
				$( ".tdcdialog#dialog_delete #deletename" ).text(vname);
				$( ".tdcdialog#dialog_delete" ).dialog( "option", "title", "Delete Vehicle" );
				$( ".tdcdialog#dialog_delete" ).data( "yesfunction", function(diag){
					var formv=new Object ;
					formv.vehicle_name=vname ;
					$.getJSON("vehicledel.php", formv, function(data){
						if( data.res==1 ) {
							updvehiclelist();
							$( ".tdcdialog#dialog_delete" ).dialog( "close");
						}
						else if( data.errormsg ) {
							alert( data.errormsg );
						}
						else {
							alert( "Delete vehicle \""+ vname +"\" failed!");
						}
					});
					return false ;
				});
				$( ".tdcdialog#dialog_delete" ).dialog( "open");
			});			
		}
	});
}

$("form#editvehicle").submit(function(e){
	e.preventDefault();
	// get form data
	var formdata = $("form#editvehicle").serializeArray();
	var formv = new Object ;
	for( var i=0; i<formdata.length; i++) {
		formv[formdata[i].name] = formdata[i].value ;
	}
	var oname=$("form#editvehicle").data("oname");
	if( oname ) formv.oname=oname ;
	$.getJSON("vehiclesave.php", formv, function(data){
		if( data.res==1 ) {
			updvehiclelist();
			$( "#dialog_vehicle" ).dialog( "close" );
			alert( "Vehicle \""+ formv.vehicle_name +"\" updated!");
		}
		else if( data.errormsg ) {
			alert( data.errormsg );
		}
		else {
			alert( "Update vehicle \""+ formv.vehicle_name +"\" failed!");
		}
	});
});	

// vehicle dialog
$( "#dialog_vehicle" ).dialog({
	autoOpen: false,
	show: {
		effect: "blind",
		duration: 300
	},
	width:"auto",
	modal: true,
	buttons: {
		"Save": function() {
			$("form#editvehicle input[type='submit']").click();
		},
		Cancel: function() {
			$( "#dialog_vehicle" ).dialog( "close" );
		}
	}
});

// load vehicle dialog default
$.getJSON("vehiclefields.php",function(resp){
	if( resp.res == 1 ) {
		var f ;
		for(f=0; f<resp.fields.length; f++) {
			if( resp.fields[f].maxlength ) {
				$("form#editvehicle input[name='"+resp.fields[f].name+"']").attr("maxlength",resp.fields[f].maxlength );
			}
			if( resp.fields[f].defvalue ) {
				$("form#editvehicle input[name='"+resp.fields[f].name+"']").attr("value",resp.fields[f].defvalue );
			}
		}
	}
});


$( "#dialog_delete" ).dialog({
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

$( "#newvehicle" ).click(function() {
    $("form#editvehicle")[0].reset();
	$("form#editvehicle").removeData("oname");
	$( "#dialog_vehicle" ).dialog( "option", "title", "New Vehicle" );
    $( "#dialog_vehicle" ).dialog( "open" );
});

$( "#deleteallvehicle" ).click(function() {
	if( confirm("Are you sure to delete all vehicles?") ) {
		$.getJSON("vehicledel.php?allvehicle=yes", function(resp){
			if( resp.res == 1 ) {
				updvehiclelist();	
			}
		});
	}
});

$( "#searchvehicle" ).click(function() {
	if( confirm("Do you want to do an auto search of vehicles from existing database?") ){
		$.getJSON("vehiclesearch.php", function(resp){
			if( resp.res == 1 ) {
				updvehiclelist();	
			}
		});
	}
});

$("form#formvehicleimport").submit(function(e){
	if( confirm("Import vehicles list from CSV file?") ) {
	}
	else {
		e.preventDefault();
	}
});


$("form#formvehicleexport").submit(function(e){
	if( !confirm("Export vehicles list to CSV file?") ) {
		e.preventDefault();
	}
});

// Driver Tab

// update driver list
function updatedriverlist()
{
	$.getJSON("driverlist.php", function(resp){
		if( resp.res && resp.driverlist ) {
			var driverlist = resp.driverlist ;		
			var htmlstr = '';
			for( var i=0; i<driverlist.length; i++) {
				htmlstr += '<tr class="driveritem"><td>' +
							driverlist[i].driver_first_name+' '+driverlist[i].driver_last_name+'</td><td>' +
							driverlist[i].driver_driverid + '</td><td>' +
							driverlist[i].driver_tel + '</td><td>' +
							driverlist[i].driver_explevel +'</td>' +
							'<td driverid="' + driverlist[i].driver_id + '"><input class="editdriver" src="res/button_edit.png" style="width: 20px; height: 20px;" type="image" /><input class="deletedriver" src="res/button_delete.png" style="width: 20px; height: 20px;" type="image" /></td></tr>' ;				
			}
			$("table#driverlist tr.driveritem").remove();
			$("table#driverlist").append(htmlstr);
			
			$("table#driverlist tr.driveritem").filter(':odd').addClass("alt");
			$("table#driverlist input.editdriver" ).click(function() {
				var form=new Object ;
				form.driver_id=$(this.parentNode).attr("driverid");
				$.getJSON("driverlist.php", form, function(resp){
					if( resp.res==1 ) {
						var driver = resp.driverlist[0] ;
						$("form#editdriver")[0].reset();
						$("form#editdriver").data("driverid", driver.driver_id);
						// fill form fields
						var field ;
						for (field in driver) {
							var elm=$("form#editdriver input[name='"+field+"']");
							if( elm.length>0 ) {
								elm.val(driver[field]);
							}
						}
						$( "#dialog_driver" ).dialog( "option", "title", "Edit Driver");
						$( "#dialog_driver" ).dialog( "open" );				
					}
				});
			});

			$( "table#driverlist input.deletedriver" ).click(function() {
				var mytr = this.parentNode.parentNode ;
				var driver_name = $(mytr.childNodes[0]).text();
				var driver_id=$(this.parentNode).attr("driverid");
				$( ".tdcdialog#dialog_delete #deletemsg" ).text("Do you want to delete driver:");
				$( ".tdcdialog#dialog_delete #deletename" ).text(driver_name);
				$( ".tdcdialog#dialog_delete" ).dialog( "option", "title", "Delete Driver" );
				$( ".tdcdialog#dialog_delete" ).data( "yesfunction", function(diag){
					var formdel = new Object ;
					formdel.driver_id = driver_id ;
					$.getJSON("driverdel.php", formdel, function(data){
						if( data.res==1 ) {
							$( diag ).dialog( "close");
							alert( "Driver \""+ driver_name +"\" deleted!");
							updatedriverlist();
						}
						else if( data.errormsg ) {
							alert( "ERROR: "+data.errormsg );
						}
						else {
							alert( "Update vehicle \""+ formv.vehicle_name +"\" failed!");
						}
					});
					return false ;
				});
				
				$( ".tdcdialog#dialog_delete" ).dialog( "open" );
			});
		}
	});
}	

$("form#editdriver").submit(function(e){
	e.preventDefault();
	// get form data
	var formdata = $("form#editdriver").serializeArray();
	var formv = new Object ;
	for( var i=0; i<formdata.length; i++) {
		formv[formdata[i].name] = formdata[i].value ;
	}
	var driver_id = $("form#editdriver").data("driverid");
	if( driver_id ) {
		formv.driver_id = driver_id ;
	}
	$.getJSON("driversave.php", formv, function(data){
		if( data.res==1 ) {
			$( "#dialog_driver" ).dialog( "close" );
			updatedriverlist();
		}
		else if( data.errormsg ) {
			alert( data.errormsg );
		}
		else {
			alert( "Update driver \""+ formv.vehicle_name +"\" failed!");
		}
	});
});	
 
$( "#dialog_driver" ).dialog({
	autoOpen: false,
	show: {
		effect: "blind",
		duration: 300
	},
	width:"auto",
	modal: true,
	buttons: {
		"Save": function() {
			$("form#editdriver input[type='submit']").click();
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		}
	}
});

// load vehicle dialog default
$.getJSON("driverfields.php",function(resp){
	if( resp.res == 1 ) {
		var f ;
		for(f=0; f<resp.fields.length; f++) {
			if( resp.fields[f].maxlength ) {
				$("form#editdriver input[name='"+resp.fields[f].name+"']").attr("maxlength",resp.fields[f].maxlength );
			}
			if( resp.fields[f].defvalue ) {
				$("form#editdriver input[name='"+resp.fields[f].name+"']").attr("value",resp.fields[f].defvalue );
			}
		}
	}
});

$( "button#newdriver" ).click(function() {
	$("form#editdriver").removeData("driverid");
    $("form#editdriver")[0].reset();
	$( "#dialog_driver" ).dialog( "option", "title", "New Driver");
    $( "#dialog_driver" ).dialog( "open" );
});

$( "#deletealldriver" ).click(function() {
	if( confirm("Are you sure to delete all drivers?") ) {
		$.getJSON("driverdel.php?alldriver=yes", function(resp){
			if( resp.res == 1 ) {
				updatedriverlist();	
			}
		});
	}
});
	
$("form#formdriverimport").submit(function(e){
	if( confirm("Import driver list from CSV file?") ) {
	}
	else {
		e.preventDefault();
	}
});

$("form#formdriverexport").submit(function(e){
	if( !confirm("Export driver list to CSV file?") ) {
		e.preventDefault();
	}
});

// groups

var grouplist = [];
var vehiclenames = [] ;
var groupopts = $("select#grouplist")[0].options ;

function update_group()
{
	$.getJSON("vehiclelist.php?nameonly=y", function(resp){
		if( resp.res==1 ) {
			vehiclenames=[];
			for( var i=0; i<resp.vehiclelist.length; i++) {
				vehiclenames[i]=resp.vehiclelist[i].vehicle_name;
			}
			updgrouplist();
		}
	});
	
	if( groupopts.selectedIndex<0 )
	$.getJSON("grouplist.php", function(resp){
		if( resp ) {
			grouplist=[];
			for( var i=0; i<resp.length; i++) {
				grouplist[i]=resp[i];
			}
			$("select#grouplist").empty();
			for(var i=0;i<grouplist.length;i++) {
				$("select#grouplist").append("<option>"+grouplist[i].name+"</option>");
				$(groupopts[i]).data("oname", grouplist[i].name );
				if( grouplist[i].vehiclelist ) {
					var vlist = grouplist[i].vehiclelist.split(",") ;
					$(groupopts[i]).data("vlist", vlist );
				}
			}			
			groupopts = $("select#grouplist")[0].options ;
		}
	});
	
}

function updgrouplist()
{
   if( groupopts.selectedIndex>=0 ) {
	    $("select#groupvehicles").empty();
	    $("select#groupavailablevehicles").empty();
	    var vlist = $(groupopts[groupopts.selectedIndex]).data("vlist");
	    if( !vlist ) { 
	       vlist=[] ;
		   $(groupopts[groupopts.selectedIndex]).data("vlist",vlist);
	    }
	    for(var i=0;i<vehiclenames.length;i++) {
			var avail = 1 ;
			var vn = vehiclenames[i] ;
			for(var j=0;j<vlist.length;j++) {
				if( vn == vlist[j] ) {
					avail = 0 ;
					break ;
				}
			}
			if( avail == 1 )
				$("select#groupavailablevehicles").append("<option>"+vn+"</option>");
			else 
				$("select#groupvehicles").append("<option>"+vn+"</option>");
	   }
   }
}

$("select#grouplist").change(updgrouplist);

// button Group Add All
$("button#groupaddall").click(function(){
	if( groupopts.selectedIndex>=0) {
	  var vlist=[] ;
      for(var i=0;i<vehiclenames.length;i++) {
		vlist[i]=vehiclenames[i] ;
      }
	  $(groupopts[groupopts.selectedIndex]).data("vlist",vlist);
	  $(groupopts[groupopts.selectedIndex]).addClass("dirty");
	  updgrouplist();
   }
});

// button Group Add vehicle
$("button#groupaddvehicle").click(function(){
	if( groupopts.selectedIndex>=0) {
		var vlist = $("select#groupavailablevehicles").val();
		if( vlist ) {
			vlist = vlist.concat( $(groupopts[groupopts.selectedIndex]).data("vlist") );
			$(groupopts[groupopts.selectedIndex]).data("vlist",vlist);
			$(groupopts[groupopts.selectedIndex]).addClass("dirty");
			updgrouplist();
		}
	}
});

// button Group Remove Vehicle
$("button#groupremovevehicle").click(function(){
	if( groupopts.selectedIndex>=0) {
	    var rlist=$("select#groupvehicles").val();
		if( rlist ) {
			var vlist=$(groupopts[groupopts.selectedIndex]).data("vlist")
			for(var i=0; i<rlist.length; i++) {
				var idx = vlist.indexOf( rlist[i] ) ;
				if( idx>=0 ) {
					vlist.splice(idx,1);
				}
			}
			$(groupopts[groupopts.selectedIndex]).addClass("dirty");
			updgrouplist();
		}
	}
});

// button Group Remove All
$("button#groupremoveall").click(function(){
	if( groupopts.selectedIndex>=0) {
	  $(groupopts[groupopts.selectedIndex]).data("vlist",[]);
  	  $(groupopts[groupopts.selectedIndex]).addClass("dirty");
	  updgrouplist();
   }
});

// button Group Save
$("button#groupsave").click(function(){
	if( groupopts.selectedIndex>=0) {
		var fdata=new Object ;
		fdata.name =  $("select#grouplist").val();
		var oname=$(groupopts[groupopts.selectedIndex]).data("oname") ;
		if( oname ) {
		    fdata.oname=oname ;
		}
		var vlist=$(groupopts[groupopts.selectedIndex]).data("vlist")
		fdata.vehiclelist="";
		for( var i=0; i<vlist.length ; i++) {
		   if(i>0) fdata.vehiclelist+="," ;
		   fdata.vehiclelist+=vlist[i];
		}
		$.getJSON("groupsave.php", fdata, function(data){
		    if( data.res==1 ) {
				$(groupopts[groupopts.selectedIndex]).data("oname",fdata.name);
				$(groupopts[groupopts.selectedIndex]).removeClass("dirty");
			    alert( "Group \""+ fdata.name +"\" updated!");
			}
			else if( data.errormsg ) {
			    alert( data.errormsg );
			}
			else {
			    alert( "Group \""+ grouplist[groupidx].name +"\" update failed!");
			}
		});
	}
});

// button Delete Group
$("button#deletegroup").click(function(){
	if( groupopts.selectedIndex>=0) {
		$( ".tdcdialog#dialog_delete #deletemsg" ).text("Delete Group:");
		$( ".tdcdialog#dialog_delete #deletename" ).text($("select#grouplist").val() );
		$( ".tdcdialog#dialog_delete" ).dialog( "option", "title", "Delete Group" );
		$( ".tdcdialog#dialog_delete" ).data( "yesfunction", function(diag){ 
			var formdata=new Object ;
			formdata.name=$("select#grouplist").val() ;
			var oname=$(groupopts[groupopts.selectedIndex]).data("oname");
			if( oname ) {
				formdata.name=oname;
				$.getJSON("groupdel.php", formdata, function(data){
					if( data.res==1 ) {
						groupopts.remove(groupopts.selectedIndex) ;
					}
					else if( data.errormsg ) {
						alert( data.errormsg );
					}
					else {
						alert( "Delete Group \""+ grouplist[groupidx].name +"\" failed!");
					}
				});
			}
			else {
				groupopts.remove(groupopts.selectedIndex) ;
			}
			return true ;		// return true to close dialog
		});
		$( ".tdcdialog#dialog_delete" ).dialog( "open" );
	}
});

// button new group
$("button#newgroup").click(function(){
	var i = $(this).data( "nextname" );
	if( !i ) i=1 ;
	for( ; i<32768; i++ ) {
		var ngname="New Group "+i ;
		var nameisnew=true;
		for( var j=0; j<groupopts.length; j++) {
			if( ngname == groupopts[j].text ) {
			    nameisnew=false;
				break;
			}
		}
		if( nameisnew ) {
			$("select#grouplist").append("<option>"+ngname+"</option>");
			groupopts[groupopts.length-1].selected=true ; 
			$(groupopts[groupopts.length-1]).addClass("dirty");
			updgrouplist();
			break ;
		}
	}
	$(this).data("nextname",i);
});

$( ".tdcdialog#dialog_renamegroup" ).dialog({
autoOpen: false,
show: {
 effect: "blind",
 duration: 300
},
width:"auto",
modal: true,
buttons: {
	"Yes": function() {
		if( groupopts.selectedIndex>=0) {
			var newname = $(".tdcdialog#dialog_renamegroup input#newgroupname").val();
			if( newname == "" ) {
				message_box("Please enter new group name." );
				return ;
			}
			for( var j=0; j<groupopts.length; j++) {
				if( newname == groupopts[j].text ) {
					message_box("Group name '"+newname+"' is in use, please enter another group name.");
					return ;
				}
			}
			groupopts[groupopts.selectedIndex].text=newname ;
 		    $(groupopts[groupopts.selectedIndex]).addClass("dirty");
			$( this ).dialog( "close" );
		}
	},
	Cancel: function() {
		$( this ).dialog( "close" );
	}
}
});

$("button#renamegroup").click(function(){
	if( groupopts.selectedIndex>=0) {
		$(".tdcdialog#dialog_renamegroup input#newgroupname").val("");
		$(".tdcdialog#dialog_renamegroup span#oldgroupname").text(groupopts[groupopts.selectedIndex].text);
		$( ".tdcdialog#dialog_renamegroup" ).dialog("open");
	}
});

// Zone Tab

var zonemap ;

function showzone(zone)
{
	var top = parseFloat(zone.top);
	var bottom = parseFloat(zone.bottom);
	var left = parseFloat(zone.left);
	var right = parseFloat(zone.right);
	if( top==bottom || right==left ) {
		// default zone
		top=60;
		bottom=10;
		left=-150;
		right=-50;
	}
	
	zonemap.entities.clear(); 		

	var pushpinOptions = {
		icon:'res/pin_24.png', 
		height: 24,
		width: 24,
		anchor: new Microsoft.Maps.Point(12,12),
		draggable: true,
	}; 

	var pins=[
		new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location( top, left ), pushpinOptions),
		new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location( top, right ), pushpinOptions),
		new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location( bottom, right ), pushpinOptions),
		new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location( bottom, left ), pushpinOptions),
		new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location( (top+bottom)/2, (right+left)/2 ), pushpinOptions)
		];

	var locs = [pins[0].getLocation(),
			pins[1].getLocation(),
			pins[2].getLocation(),
			pins[3].getLocation(),
			pins[0].getLocation()] ;
	
	var polyline = new Microsoft.Maps.Polyline(locs, null); 

	var locrect=Microsoft.Maps.LocationRect.fromLocations( locs );
	locrect.width *= 1.05 ;
	zonemap.setView({ bounds: locrect});
		
	zonemap.entities.push(polyline);
	zonemap.entities.push(pins[0]);
	zonemap.entities.push(pins[1]);
	zonemap.entities.push(pins[2]);
	zonemap.entities.push(pins[3]);
	zonemap.entities.push(pins[4]);

	function ondragcorner(e){
		var ipin ;
		for( ipin=0; ipin<4; ipin++) {
			if( pins[ipin] == e.target ) {
				break;
			}
		}
<?php
	if( !empty($zone_mode) && $zone_mode == 'cross' ) {
?>	
		var loc0=pins[ipin].getLocation();
		var loc2=pins[(ipin+2)%4].getLocation();
		pins[(ipin+1)%4].setLocation( new Microsoft.Maps.Location( loc0.latitude, loc2.longitude ) );
		pins[(ipin+3)%4].setLocation( new Microsoft.Maps.Location( loc2.latitude, loc0.longitude ) );
		var locrect = Microsoft.Maps.LocationRect.fromLocations( loc0, loc2 );
		var loccenter = locrect.center ;
		pins[4].setLocation( loccenter );
<?php } else { ?>				
		var loc = pins[ipin].getLocation();
		var loccenter = pins[4].getLocation();
		var h = 2*(loccenter.latitude - loc.latitude);
		var w = 2*(loccenter.longitude - loc.longitude);
		pins[(ipin+1)%4].setLocation( new Microsoft.Maps.Location( loc.latitude+h, loc.longitude ) );
		pins[(ipin+2)%4].setLocation( new Microsoft.Maps.Location( loc.latitude+h, loc.longitude+w ) );
		pins[(ipin+3)%4].setLocation( new Microsoft.Maps.Location( loc.latitude,   loc.longitude+w ) );
<?php  }  ?>		
		polyline.setLocations([
			pins[0].getLocation(),
			pins[1].getLocation(),
			pins[2].getLocation(),
			pins[3].getLocation(),
			pins[0].getLocation()
			]); 
		$("button#savezone").show();
	};

	function ondragmove(e) {
		var cloc = pins[4].getLocation();
		var crect = Microsoft.Maps.LocationRect.fromLocations( [pins[0].getLocation(), pins[2].getLocation()] );
		var h2 = crect.height/2 ;
		var w2 = crect.width/2 ;
		pins[0].setLocation(new Microsoft.Maps.Location( cloc.latitude + h2,  cloc.longitude + w2 ));
		pins[1].setLocation(new Microsoft.Maps.Location( cloc.latitude + h2,  cloc.longitude - w2 ));
		pins[2].setLocation(new Microsoft.Maps.Location( cloc.latitude - h2,  cloc.longitude - w2 ));
		pins[3].setLocation(new Microsoft.Maps.Location( cloc.latitude - h2,  cloc.longitude + w2 ));
		polyline.setLocations([
			pins[0].getLocation(),
			pins[1].getLocation(),
			pins[2].getLocation(),
			pins[3].getLocation(),
			pins[0].getLocation()
			]);
		$("button#savezone").show();
	};
	
	$("button#savezone").hide();
	
	Microsoft.Maps.Events.addHandler(pins[0], 'drag', ondragcorner);  
	Microsoft.Maps.Events.addHandler(pins[1], 'drag', ondragcorner);  
	Microsoft.Maps.Events.addHandler(pins[2], 'drag', ondragcorner);  
	Microsoft.Maps.Events.addHandler(pins[3], 'drag', ondragcorner);  
	Microsoft.Maps.Events.addHandler(pins[4], 'drag', ondragmove);  
}

$('#zonelist').change(function() {
	var zone = $('#zonelist').val();
	if( $('#zonelist')[0].selectedIndex<0 || zone==0 ) {
		showzone({top:0,bottom:0,left:0,right:0});
		return ;
	}
	else if($('#zonelist').val()=="Default Area") {
		$.getJSON("mapquery.php", function(resp){
			if( resp.res && resp.map.bbox.length>=4) {
				showzone({bottom:resp.map.bbox[0], left:resp.map.bbox[1], top:resp.map.bbox[2], right:resp.map.bbox[3]});
			}
		});	
	}
	else 
		$.getJSON("zonelist.php?name="+zone, function(resp){
			if( resp.res && resp.zonelist.length>0) {
				showzone(resp.zonelist[0]);
			}
		});	
});	

function updzonelist()
{
	$.getJSON("zonelist.php", function(resp){
		if( resp.res && resp.zonelist ) {
			var htmlstr = "<option>Default Area</option>" ;
			for( var i=0; i<resp.zonelist.length; i++ ) {
				htmlstr += '<option>' + resp.zonelist[i].name + '</option>' ;
			}
			$('#zonelist').html(htmlstr);
			$('#zonelist').change();	// apply default zone;
		}
	});		
}

// ztype:  1: public, 2: private
function newzone(zname,ztype)
{
	var form = new Object ;
	form.name = zname ;
	form.zonetype = ztype ;
	$.getJSON("zonenew.php", form, function(resp){
		if( resp.res > 0 ) {	// success
		
			// use current map area
			var b = zonemap.getBounds();
			b.width /= 1.6 ;
			b.height /= 1.6 ;
			showzone({top:b.getNorth(), bottom:b.getSouth(),left:b.getWest(),right:b.getEast()});
		
			var htmlstr = '<option>'+zname+'</option>' ;
			$('#zonelist').append(htmlstr);
			$('#zonelist').val(zname);
			$("button#savezone").click();
		}
		else if( resp.errormsg ) {
			alert( resp.errormsg );
		}
		else {
			alert( "Create new zone \""+ zname +"\" failed!");
		}				
	});
}

$("button#newzone").click(function(){
	var i = $(this).data( "nextzonename" );
	if( !i ) i=1 ;
	for( ; i<32768; i++ ) {
		var nzname="New Zone "+i ;
		if( $("#zonelist option").filter(function(){
			return $(this).text()==nzname ;
		}).length<=0 ) {
			$(this).data( "nextzonename", i );
			newzone(nzname,1);
			break;
		}
	}
});

$("button#newmyzone").click(function(){
	var i = $(this).data( "nextmyzonename" );
	if( !i ) i=1 ;
	for( ; i<32768; i++ ) {
		var nzname="My Zone "+i ;
		if( $("#zonelist option").filter(function(){
			return $(this).text()==nzname ;
		}).length<=0 ) {
			$(this).data( "nextmyzonename", i );
			newzone(nzname,2);
			break;
		}
	}
});
			
$("button#deletezone").click(function(){
	var zone = $('#zonelist').val();
	// ask user confirm deleting
	$( ".tdcdialog#dialog_delete #deletemsg" ).text("Delete Zone:");
	$( ".tdcdialog#dialog_delete #deletename" ).text(zone);
	$( ".tdcdialog#dialog_delete" ).dialog( "option", "title", "Delete Zone" );
	$( ".tdcdialog#dialog_delete" ).data( "yesfunction", function(diag){
		var form = new Object ;
		form.name = zone ;
		$.getJSON("zonedel.php", form, function(resp){
			if( resp.res == 1 ) {	// success
				$( ".tdcdialog#dialog_delete" ).dialog( "close");
				updzonelist();
			}
			else if( resp.errormsg ) {
				alert( resp.errormsg );
			}
			else {
				alert( "Delete zone \""+ zonename +"\" failed!");
			}				
		});	
		return false ;
	});
	$( ".tdcdialog#dialog_delete" ).dialog( "open");
});

$( ".tdcdialog#dialog_renamezone" ).dialog({
autoOpen: false,
show: {
 effect: "blind",
 duration: 300
},
width:"auto",
modal: true,
buttons: {
	"Ok": function() {
		var newname=$(".tdcdialog#dialog_renamezone input#newzonename").val();
		if( newname.length<1) {
			alert("Please enter zone name!");
			return ;
		}
		if( $("#zonelist option").filter(function(){
			return ( $(this).text()==newname );
		}).length>0 ) {
			alert("Zone name already exist!");
			return ;
		}
		var form = new Object ;		
		form.newname = newname ;
		form.name = $("#zonelist").val();
		$.getJSON("zonerename.php", form, function(resp){
			if( resp.res == 1 ) {	// success
				var selected = $('#zonelist')[0].selectedIndex ;
				$($("#zonelist option")[selected]).text(newname) ;
				$( ".tdcdialog#dialog_renamezone" ).dialog( "close" );
			}
			else if( resp.errormsg ) {
				alert( resp.errormsg );
			}
			else {
				alert( "Rename zone \""+ newname +"\" failed!");
			}				
		});
	},
	Cancel: function() {
		$( this ).dialog( "close" );
	}
}
});

$( ".tdcdialog#dialog_mapsearch" ).dialog({
autoOpen: false,
show: {
 effect: "blind",
 duration: 300
},
width:"auto",
modal: true,
buttons: {
	"Search": function() {
		var query=$(".tdcdialog#dialog_mapsearch input#mapquery").val();
		$.getJSON("mapquery.php?q="+query, function(resp){
			if( resp.res && resp.map.bbox.length>=4) {
				showzone({bottom:resp.map.bbox[0], left:resp.map.bbox[1], top:resp.map.bbox[2], right:resp.map.bbox[3]});
				$("button#savezone").show();
			}
		});	
		$( this ).dialog( "close" );
	},
	Cancel: function() {
		$( this ).dialog( "close" );
	}
}
});

$( ".tdcdialog#dialog_message" ).dialog({
	autoOpen: false,
	width:"auto",
	modal: true,
	buttons: {
		"Ok": function() {
			$( this ).dialog( "close" );
		}
	}
});

function message_box( msg )
{
	$("p#alert_message").text( msg );
	$( ".tdcdialog#dialog_message" ).dialog("open");
}

$("button#mapsearch").click(function(){
	$( ".tdcdialog#dialog_mapsearch" ).dialog("open");
});

$("button#renamezone").click(function(){
	var selected = $('#zonelist')[0].selectedIndex ;
	var zonename = $($("#zonelist option")[selected]).text() ;
	$(".tdcdialog#dialog_renamezone span#oldzonename").text(zonename);
	$(".tdcdialog#dialog_renamezone input#newzonename").val("");
	$(".tdcdialog#dialog_renamezone" ).dialog("open");
});

$("button#savezone").click(function(){
	// get zonemap rect
	var i ;
	for( i=zonemap.entities.getLength()-1; i>=0; i-- ) {
		var polyline=zonemap.entities.get(i);
		if (polyline instanceof Microsoft.Maps.Polyline) { 
			var locations = polyline.getLocations(); 
			if( locations.length>=4 ) {
				var locrect=Microsoft.Maps.LocationRect.fromLocations( locations );
				var selected = $('#zonelist')[0].selectedIndex ;
				var form = new Object ;
				form.top = locrect.getNorth().toFixed(6);
				form.bottom= locrect.getSouth().toFixed(6);
				form.left= locrect.getWest().toFixed(6);
				form.right= locrect.getEast().toFixed(6);
				form.name = $("#zonelist").val();
				$.getJSON("zonesave.php", form, function(resp){
					if( resp.res >= 1 ) {	// success
//						alert("zone \"" +form.name+"\" update success!");
						$("button#savezone").hide();
					}
					else if( resp.errormsg ) {
						alert( resp.errormsg );
					}
					else {
						alert( "Update zone \""+ form.name +"\" failed!");
					}				
				});
			}
			break;
		}
	}
});


function sizezonemap(){
	if( tab==3) {
		var zonemaparea = $("#zonemaparea") ;
		var nh = window.innerHeight - zonemaparea.offset().top - $("#footer").outerHeight() - 20 ;
		zonemaparea.height( nh );
	}
}

function tab_zone()
{
	sizezonemap();

	if( !zonemap ) {
		zonemap = new Microsoft.Maps.Map(document.getElementById("zonemap"), 
		{ credentials: <?php echo '"'. $map_credentials . '"'; ?> ,
		  enableSearchLogo: false,
		  mapTypeId : Microsoft.Maps.MapTypeId.road,
		  enableClickableLogo: false
		});
	}


	if( zonemap.entities.getLength() <= 0 ) {
		setTimeout(updzonelist,1000);
	}
}

$(window).resize(sizezonemap);	

$("#rcontainer").show('slow', function(){
	set_tab();
});

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
<ul style="margin: 0px; padding: 0px; list-style-type: none;">
	<li><a class="lmenu" href="dashboard.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<?php if( !empty($enable_videos) ){ ?><li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li><?php } ?>
<?php if( !empty($enable_livetrack) ){ ?><?php if( !empty($enable_livetrack) ){ ?><li><a class="lmenu" href="livetrack.php"><img onmouseout="this.src='res/side-livetrack-logo-clear.png'" onmouseover="this.src='res/side-livetrack-logo-fade.png'" src="res/side-livetrack-logo-clear.png" /> </a></li><?php } ?><?php } ?>
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
 
</pre>
</div>
<strong><span style="font-size: 26px;">SETTINGS</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width: auto;">
<p class="btset">
<input name="btset" checked="checked" href="settingsfleet.php" id="btfleet" type="radio" /><label for="btfleet">Fleet Setup</label>
<input name="btset" href="settingsuser.php" id="btuser" type="radio" /><label for="btuser">User Accounts</label> 
<input name="btset" href="settingssystem.php" id="btsys" type="radio" /><label for="btsys">System Configuration</label>
<input name="btset" href="settingsemail.php" id="btemail" type="radio" /><label for="btemail">Email Configuration</label> 
</p>

<h4><strong>Fleet Setup</strong></h4>

<div id="settingtabs">
<ul>
	<li><a href="#settingtabs-vehicles">Vehicles</a></li>
	<li><a href="#settingtabs-drivers">Drivers</a></li>
	<li><a href="#settingtabs-groups">Groups</a></li>
	<li><a href="#settingtabs-zones">Zones</a></li>
</ul>

<div id="settingtabs-vehicles"><!-- Add / Edit vechile dialog -->
<div class="tdcdialog" id="dialog_vehicle" title="Add New Vehicle">
<form id="editvehicle">
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td style="text-align: right;">Vehicle Name:</td>
			<td><input type="text" name="vehicle_name" maxlength="45" required /></td>
			<td style="text-align: right;">VIN#:</td>
			<td><input name="vehicle_vin" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">RFID:</td>
			<td><input name="vehicle_rfid" type="text" /></td>
			<td style="text-align: right;">Plate#:</td>
			<td><input name="vehicle_plate" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Model#:</td>
			<td><input name="vehicle_model" type="text" /></td>
			<td style="text-align: right;">Year:</td>
			<td><input name="vehicle_year" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Unit ID:</td>
			<td><input name="vehicle_uid" type="text" /></td>
			<td style="text-align: right;">Password:</td>
			<td><input name="vehicle_password" type="password" value="247SECURITY" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Notes:</td>
			<td><input name="vehicle_notes" size="30" type="text" /></td>
			<td style="text-align: right;">Max Upload Time</td>
			<td><input name="vehicle_max_upload_time" type="text" value="60" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Phone:</td>
			<td><input name="vehicle_phone" size="30" type="text" /></td>
			<td style="text-align: right;"></td>
			<td></td>
		</tr>
	</tbody>
</table>

<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<caption>
	<p style="text-align: left;">Upload Schedule:<span style="display: none;">&nbsp;</span></p>
	</caption>
	<tbody>
		<tr>
			<td>Sunday</td>
			<td>Monday</td>
			<td>Tuesday</td>
			<td>Wednesday</td>
			<td>Thursday</td>
			<td>Friday</td>
			<td>Saturday</td>
		</tr>
		<tr>
			<td><input name="Vehicle_report_sun" checked="checked" value="n" type="radio" />NA</td>
			<td><input name="Vehicle_report_mon" value="n" type="radio" />NA</td>
			<td><input name="Vehicle_report_tue" value="n" type="radio" />NA</td>
			<td><input name="Vehicle_report_wen" value="n" type="radio" />NA</td>
			<td><input name="Vehicle_report_thu" value="n" type="radio" />NA</td>
			<td><input name="Vehicle_report_fri" value="n" type="radio" />NA</td>
			<td><input name="Vehicle_report_sat" checked="checked" value="n" type="radio" />NA</td>
		</tr>		<tr>
			<td><input name="Vehicle_report_sun" value="a" type="radio" />AM</td>
			<td><input name="Vehicle_report_mon" value="a" type="radio" />AM</td>
			<td><input name="Vehicle_report_tue" value="a" type="radio" />AM</td>
			<td><input name="Vehicle_report_wen" value="a" type="radio" />AM</td>
			<td><input name="Vehicle_report_thu" value="a" type="radio" />AM</td>
			<td><input name="Vehicle_report_fri" value="a" type="radio" />AM</td>
			<td><input name="Vehicle_report_sat" value="a" type="radio" />AM</td>
		</tr>		<tr>
			<td><input name="Vehicle_report_sun" value="p" type="radio" />PM</td>
			<td><input name="Vehicle_report_mon" value="p" type="radio" />PM</td>
			<td><input name="Vehicle_report_tue" value="p" type="radio" />PM</td>
			<td><input name="Vehicle_report_wen" value="p" type="radio" />PM</td>
			<td><input name="Vehicle_report_thu" value="p" type="radio" />PM</td>
			<td><input name="Vehicle_report_fri" value="p" type="radio" />PM</td>
			<td><input name="Vehicle_report_sat" value="p" type="radio" />PM</td>
		</tr>		<tr>
			<td><input name="Vehicle_report_sun" value="y" type="radio" />All Day</td>
			<td><input name="Vehicle_report_mon" checked="checked" value="y" type="radio" />All Day</td>
			<td><input name="Vehicle_report_tue" checked="checked" value="y" type="radio" />All Day</td>
			<td><input name="Vehicle_report_wen" checked="checked" value="y" type="radio" />All Day</td>
			<td><input name="Vehicle_report_thu" checked="checked" value="y" type="radio" />All Day</td>
			<td><input name="Vehicle_report_fri" checked="checked" value="y" type="radio" />All Day</td>
			<td><input name="Vehicle_report_sat" value="y" type="radio" />All Day</td>
		</tr>
	</tbody>
</table>

<p>Out Of Service<input name="vehicle_out_of_service" type="checkbox" /></p>
<input type="submit" style="display:none"/>
</form>
</div>
<!-- End vehicle Dialog --><!-- delete vechile comfirm dialog -->

<table border="0" cellpadding="1" cellspacing="1" class="listtable" id="vehiclelist" style="width: 100%;">
	<caption>
	<h4 style="text-align: left;">Vehicle List:</h4>
	</caption>
	<tbody>
		<tr>
			<th>Vehicle Name</th>
			<th>Plate NO</th>
			<th>Model</th>
			<th>Year</th>
			<th>Edit/Delete</th>
		</tr>
	</tbody>
</table>
<button id="newvehicle"><img src="res/button_add.png" />New Vehicle</button>
<?php
if( $_SESSION['user'] == 'admin' ) {
?>
<button id="deleteallvehicle"><img src="res/button_delete.png" />Delete All</button>
<button id="searchvehicle"><img src="res/button_search.png" />Auto Search</button>

<form id="formvehicleimport" enctype="multipart/form-data" method="POST" action="vehicleimport.php" >
<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
<input class="xbutton" value="Import" type="submit" />
<input name="importfile" type="file" required />
</form>

<form id="formvehicleexport" enctype="application/x-www-form-urlencoded" method="get" action="vehicleexport.php" target="_blank" >
<input class="xbutton" value="Export" type="submit" />
</form>

<?php } ?>
</div>

<div id="settingtabs-drivers"><!-- Add / Edit Driver dialog -->
<div class="tdcdialog" id="dialog_driver" title="Edit Driver">
<form id="editdriver">
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td style="text-align: right;">First Name:</td>
			<td><input name="driver_first_name" type="text" required /></td>
			<td style="text-align: right;">Last Name:</td>
			<td><input name="driver_last_name" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Driver ID#:</td>
			<td><input name="driver_driverid" type="text" required /></td>
			<td style="text-align: right;">Driver License Number:</td>
			<td><input name="driver_License" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">SS#:</td>
			<td><input name="driver_sin" type="text" /></td>
			<td style="text-align: right;">RFID:</td>
			<td><input name="driver_rfid" type="text" /></td>
		</tr>		
		<tr>
			<td style="text-align: right;">Address:</td>
			<td><input name="driver_add" type="text" /></td>
			<td style="text-align: right;">City:</td>
			<td><input name="driver_city" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">State/Prov:</td>
			<td><input name="driver_state" type="text" /></td>
			<td style="text-align: right;">Country:</td>
			<td><input name="driver_country" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Zip/Postal Code:</td>
			<td><input name="driver_pcode" type="text" /></td>
			<td style="text-align: right;">Tel:</td>
			<td><input name="driver_tel" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">E-Mail Address:</td>
			<td><input name="driver_email" type="text" /></td>
			<td style="text-align: right;">Experience Level:</td>
			<td><input name="driver_explevel" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Billing Code 1:</td>
			<td><input name="driver_bcode" type="text" /></td>
			<td style="text-align: right;">Billing Code 2:</td>
			<td><input name="driver_bcode2" type="text" /></td>
		</tr>
		<tr>
			<td style="text-align: right;">Notes:</td>
			<td colspan="3"><input name="driver_notes" size="50" type="text" /></td>
		</tr>
	</tbody>
</table>
<input type="submit" style="display:none"/>
</form>
</div>
<!-- End Add / Edit Driver dialog --><!-- delete Driver dialog -->

<table border="0" cellpadding="1" cellspacing="1" class="listtable" id="driverlist" style="width: 100%;">
	<caption>
	<h4 style="text-align: left;">Driver List:</h4>
	</caption>
	<thead>
		<tr>
			<th>Driver Name</th>
			<th>Driver ID</th>
			<th>Tel#</th>
			<th>EXP Level#</th>
			<th>Edit/Delete</th>
		</tr>	
    </thead>		
	<tbody>
	</tbody>
</table>
<button id="newdriver"><img src="res/button_add.png" />New Driver</button>
<?php
if( $_SESSION['user'] == 'admin' ) {
?>
<button id="deletealldriver"><img src="res/button_delete.png" />Delete All</button>

<form id="formdriverimport" enctype="multipart/form-data" method="POST" action="driverimport.php" >
<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
<input class="xbutton" value="Import" type="submit" />
<input name="importfile" type="file" required />
</form>

<form id="formdriverexport" enctype="application/x-www-form-urlencoded" method="get" action="driverexport.php" target="_blank" >
<input class="xbutton" value="Export" type="submit" />
</form>

<?php } ?>

</div>



<div id="settingtabs-groups">
<h4>Vehicle Group Management</h4>

<!-- rename group dialog -->
<div class="tdcdialog" id="dialog_renamegroup" title="Rename Group">
<p>Rename Group [<span id="oldgroupname">group</span>] To:</p>
<input id="newgroupname" type="text" maxlength="45" /></div>
<!-- rename group dialog -->

<table border="0" cellpadding="1" cellspacing="5">
	<tbody>
		<tr>
			<th>Select A Group:</th>
			<th>Vehicles In This Group:</th>
			<th>&nbsp;</th>
			<th>Available Vehicles:</th>
		</tr>
		<tr>
			<td>
			<p><select id="grouplist" name="group" size="16" style="min-width: 13em;"> </select></p>

			<p><button id="deletegroup" style="min-width: 10em;">Delete Group</button></p>

			<p><button id="newgroup" style="min-width: 10em;">New Group</button></p>

			<p><button id="renamegroup" style="min-width: 10em;">Rename Group</button></p>
			</td>
			<td>
			<p><select id="groupvehicles" multiple="multiple" size="20" style="min-width: 13em;"></select></p>
			</td>
			<td><button id="groupaddall" style="min-width: 10em;">Add All</button><br />
			<button id="groupaddvehicle" style="width: 10em;">Add</button><br />
			<button id="groupremovevehicle" style="width: 10em;">Remove</button><br />
			<button id="groupremoveall" style="width: 10em;">Remove All</button>
			<p><button id="groupsave" style="width: 10em;">Save</button></p>
			</td>
			<td>
			<p><select id="groupavailablevehicles" multiple="multiple" size="20" style="min-width: 13em;"></select></p>
			</td>
		</tr>
	</tbody>
</table>
</div>

<div id="settingtabs-zones"><!-- rename group dialog -->
<h4>Zone Management</h4>

<p>Select a Zone:
<select id="zonelist" name="zonelist" style="min-width: 12em;">
</select> &nbsp; &nbsp;<button id="newzone"><img src="res/button_add.png" style="width: 20px; height: 20px;" />New Zone</button><button id="newmyzone"><img src="res/button_add.png" style="width: 20px; height: 20px;" />New My Zone</button><button id="deletezone"><img src="res/button_delete.png" style="width: 20px; height: 20px;" />Delete Zone</button><button id="renamezone">Rename</button><button id="mapsearch">Search</button><button id="savezone">Save</button></p>

<div id="zonemaparea" style="width: auto; position: relative; min-height: 300px">
<div id="zonemap" style="height:100%;width:100%;">Zone Map</div>
</div>

<div class="tdcdialog" id="dialog_renamezone" title="Rename Zone">
<p>Rename Zone (<span id="oldzonename">zoneup</span>) To:</p>
<input id="newzonename" type="text" maxlength="45" />
</div>
<!-- rename group dialog --></div>

<!-- Generic Delete Dialog -->
<div class="tdcdialog" id="dialog_delete">
<p id="deletemsg">delete:</p>

<p id="deletename" style="text-align: center;">this</p>

<p>&nbsp;</p>
</div>

<!-- Map Search Dialog -->
<div class="tdcdialog" id="dialog_mapsearch" title="Search" >
<p id="deletemsg">Enter address , city name or coordinates:</p>
<input id="mapquery" type="text" size="50" />
</div>

<!-- message dialog -->
<div class="tdcdialog" id="dialog_message" title="Message">
<p id="alert_message"> Warning </p>
<!-- message dialog -->
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
<div style="float:left"></div>

<p style="text-align: right;"><span style="font-size: 11px;"><a href="http://www.247securityinc.com/" style="text-decoration: none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>