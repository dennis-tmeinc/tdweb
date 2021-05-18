<?php
// mapfilter.php - map filter area, to be included in MAPVIEW/REPORTVIEW
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.
// MySQL connection
?>

<div class="ui-widget ui-widget-content ui-corner-all" id="rpanel">
<table style="width:100%;"><tr>
<td><button id="generate" style="font-size:1.3em;">Generate</button></td>
<td id="gen_count"></td>
</tr></table>
<p/>

<form action="#" id="filterform">
<fieldset><legend>Quick Filter</legend>

<div class="ui-widget" style="display:none;" >
<select id="cbBox" size="8">
</select>
</div>
<input id="quickfilter" name="quickfiltername" autocomplete="off" maxlength="45" type="text" style="background: white url(res/triangle_s.png) right no-repeat; padding-right: 12px; 
" /><p />
<button id="savequickfilter">Save</button><button id="deletequickfilter">Delete</button></fieldset>

<fieldset><legend>Select Time</legend> <input name="timeType" type="radio" value="0" />Exact Time<input checked="checked" name="timeType" type="radio" value="1" />Full Day<input name="timeType" type="radio" value="2" />Time Range
<table id="timeinput" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td id="starttime">Date:</td>
			<td><input class="datetimepicker" name="startTime" type="text" value="" /></td>
		</tr>
		<tr>
		</tr>
		<tr id="endtime" style="display:none;">
			<td>End Time:</td>
			<td><input class="datetimepicker" name="endTime" type="text" value="" /></td>
		</tr>
	</tbody>
</table>
</fieldset>
<p/>

<p><input checked="checked" name="vehicleType" type="radio" value="0" />Vehicle<input name="vehicleType" type="radio" value="1" />Group <select name="vehicleGroupName" style="width:150px" ></select></p>
<p><input checked="checked" name="zoneType" type="radio" value="0" />Inside<input name="zoneType" type="radio" value="1" />Outside <select name="zoneName" style="width:150px">
<option>No Restriction</option>
<?php
	if( basename($_SERVER["SCRIPT_NAME"]) == "mapview.php" ) {
		if( !empty($map_area) ) {
			echo "<option>Default Area</option>" ;
		}
		echo "<option>Current Map</option>" ;
	}
	$sql="SELECT `name` FROM zone WHERE `type` = 1 OR `user` = '$_SESSION[user]';" ;
	if( $result=$conn->query($sql) ){
		while( $row = $result->fetch_array(MYSQLI_NUM) ) {
			if( $row[0] != "No Restriction" && $row[0] != "User Define" ) {
				echo "<option>$row[0]</option>";
			}
		}
		$result->free();
	}
?></select></p>

<?php if( basename($_SERVER["SCRIPT_NAME"]) == "mapview.php" ) { ?>

<div>
<button id="btaddress">Address</button>
<input size="24" name="txtaddress" type="text"/>
</div>

<?php } ?>

<div id="accordion" >

<h3>Select Events</h3>
<div>

<table border="0" cellpadding="0" cellspacing="1" id="event" width="100%">
	<tbody>
		<tr>
			<td><input checked="checked" name="bStop" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_stop.png" /> Stopping </td>
			<td><input name="stopDuration" size="6" type="text" /> s </td>
		</tr>
		
<?php if( empty($tvs_filter) ) { ?>
		<tr>
			<td><input checked="checked" name="bDesStop" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_desstop.png" /> Bus Stops&nbsp;</td>
			<td><input name="desStopDuration" size="6" type="text" /> s </td>
		</tr>
<?php } ?>
		
		<tr>
			<td><input checked="checked" name="bIdling" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_idle.png" /> Idling </td>
			<td><input name="idleDuration" size="6" type="text" /> s </td>
		</tr>

<?php if( empty($tvs_filter) ) { ?>
		<tr>
			<td><input checked="checked" name="bParking" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_park.png" /> Parking </td>
			<td><input name="parkDuration" size="6" type="text" /> s </td>
		</tr>
<?php } ?>

		<tr>
			<td colspan="2"><input checked="checked" name="bSpeeding" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_speed.png" /> Speeding Limit: <input maxlength="10" name="speedLimit" size="2" type="text" value="0" /> mph</td>
		</tr>
		<tr>
			<td colspan="2"><input checked="checked" name="bRoute" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_route.png" /> Route </td>
		</tr>
		<tr>
			<td colspan="2"><input checked="checked" name="bEvent" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_mevent.png" /> M.Events </td>
		</tr>
		
<?php if( empty($tvs_filter) ) { ?>
		<tr>
			<td colspan="2"><input checked="checked" name="bDriveBy" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_driveby.png" /> Drive By </td>
		</tr>
<?php } ?>
		
<?php if( !empty($tvs_filter) ) { ?>
		<tr>
			<td><input name="bDoorOpen" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_dooropen.png" />Door Open</td>
			<td><input name="bDoorClose" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_doorclose.png" />Door Close</td>
		</tr>

		<tr>
			<td><input name="bIgnitionOn" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_ignitionon.png" />Ignition On</td>
			<td><input name="bIgnitionOff" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_ignitionoff.png" />Ignition Off</td>
		</tr>
		
		<tr>
			<td><input name="bMeterOn" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_meteron.png" />Meter On</td>
			<td><input name="bMeterOff" type="checkbox" /><img alt="" class="evicon" src="res/map_icons_meteroff.png" />Meter Off</td>
		</tr>
<?php } ?>
		
	</tbody>
</table>

</div>

<h3>G-Force Parameters</h3>
<div>
<table border="0" cellpadding="0" cellspacing="1" id="gforceparameters" style="width: 100%;">
	<tbody>
		<tr>
			<td><input checked="checked" name="bRacingStart" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_rs.png" /></td><td>Racing Start</td>
			<td><input name="gRacingStart" size="5" type="text" value="0.0" /> g </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bHardBrake" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_hb.png" /></td><td>Hard Brake</td>
			<td><input name="gHardBrake" size="5" type="text" value="0.0" /> g </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bHardTurn" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_ht.png" /></td><td>Hard Turn</td>
			<td><input name="gHardTurn" size="5" type="text" value="0.0" /> g </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bRearImpact" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_ri.png" /></td><td>Rear Impact</td>
			<td><input name="gRearImpact" size="5" type="text" value="0.0" /> g </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bFrontImpact" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_fi.png" /></td><td>Front Impact</td>
			<td><input name="gFrontImpact" size="5" type="text" value="0.0" /> g </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bSideImpact" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_si.png" /></td><td>Side Impact</td>
			<td><input name="gSideImpact" size="5" type="text" value="0.0" /> g </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bBumpyRide" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_br.png" /></td><td>Bumpy Ride</td>
			<td><input name="gBumpyRide" size="5" type="text" value="0.0" /> g </td>
		</tr>
	</tbody>
</table>
</div>

<h3>OBD Parameters</h3>
<div>
<table border="0" cellpadding="0" cellspacing="1" id="obdparameters" style="width: 100%;">
	<tbody>
		<tr>
			<td><input checked="checked" name="bEnginOn" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_ignitionon.png" /></td><td>Engine On</td>
			<td></td>
		</tr>
		<tr>
			<td><input checked="checked" name="bEnginOff" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_ignitionoff.png" /></td><td>Engine Off</td>
		</tr>
		<tr>
			<td><input checked="checked" name="bFuelLevel" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_hb.png" /></td><td>Fuel Level < </td>
			<td><input name="gFuelLevel" size="5" type="text" value="10" /> (%) </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bCoolantTemperature" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_ri.png" /></td><td>Coolant Temperature > </td>
			<td><input name="gCoolantTemperature" size="5" type="text" value="250" /> F </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bEngineOilLevel" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_hb.png" /></td><td>Engine Oil Level < </td>
			<td><input name="gEngineOilLevel" size="5" type="text" value="10" /> (%) </td>
		</tr>
		<tr>
			<td><input checked="checked" name="bBatteryVoltage" type="checkbox" />
			<img alt="" class="evicon" src="res/map_icons_hb.png" /></td><td>Battery Voltage < </td>
			<td><input name="gBatteryVoltage" size="5" type="text" value="12.0" /> V </td>
		</tr>
	</tbody>
</table>
</div>

</div>

</form>

<p style="text-align: center;"><button id="reset">Reset</button></p>
</div>
<script>
// init mapfilter sections
$(function(){

$( "#accordion" ).accordion({
    collapsible: true,
	heightStyle: "content"
});

$("table#timeinput td").css("height", "32px");
$("table#gforceparameters td").css("height", "32px");
$("table#event td").css("height", "32px");

var vehiclelist=<?php
	$sql="SELECT vehicle_name FROM vehicle ORDER BY vehicle_name;" ;
	$vlist=array();
	if( $result=$conn->query($sql) ) {
		while( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$vlist[]=$row[0];
		}
		$result->free();
	}
	echo json_encode($vlist);
?>;
grouplist=<?php
	$sql="SELECT `name` FROM vgroup;" ;
	$glist=array();
	if( $result=$conn->query($sql) ) {
		while( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$glist[]=$row[0];
		}
		$result->free();
	}
	echo json_encode($glist);
?>;

$( ".datetimepicker" ).datetimepicker({
	dateFormat: "yy-mm-dd",
	yearRange: "2000:2030",
	showTime: false ,
	timeFormat: "H:mm:ss"
});

// time range selection changed
$("#filterform input[name='timeType']").change(function(e){
	if( !this.checked ) {
		return ;
	}
	var value=$(this).val();
	if( value==0 ) {
		$("#filterform #starttime").text("Time:");
		$("#filterform #endtime").hide("slow");
	}
	else if( value==1 ) {
		$("#filterform #starttime").text("Date:");
		$("#filterform #endtime").hide("slow");
	}
	else {
		$("#filterform #starttime").text("Start Time:");
		$("#filterform #endtime").show("slow");
	}
});

$.widget( "custom.combobox", {
      _create: function() {
        this.wrapper = $( "<span>" )
          .addClass( "custom-combobox" )
          .insertAfter( this.element );
 
        this.element.hide();
        this._createAutocomplete();
        this._createShowAllButton();
      },
 
      _createAutocomplete: function() {
        var selected = this.element.children( ":selected" ),
          value = selected.val() ? selected.text() : "";
 
        this.input = $( "<input>" )
          .appendTo( this.wrapper )
          .val( value )
          .attr( "title", "" )
          .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
          .autocomplete({
            delay: 0,
            minLength: 0,
            source: $.proxy( this, "_source" )
          })
          .tooltip({
            classes: {
              "ui-tooltip": "ui-state-highlight"
            }
          });
 
        this._on( this.input, {
          autocompleteselect: function( event, ui ) {
            ui.item.option.selected = true;
            this._trigger( "select", event, {
              item: ui.item.option
            });
          }
        });
      },
 
      _createShowAllButton: function() {
        var input = this.input,
          wasOpen = false;
 
        $( "<a>" )
          .attr( "tabIndex", -1 )
          .attr( "title", "Show All Items" )
          .tooltip()
          .appendTo( this.wrapper )
          .button({
            icons: {
              primary: "ui-icon-triangle-1-s"
            },
            text: false
          })
          .removeClass( "ui-corner-all" )
          .addClass( "custom-combobox-toggle ui-corner-right" )
          .on( "mousedown", function() {
            wasOpen = input.autocomplete( "widget" ).is( ":visible" );
          })
          .on( "click", function() {
            input.trigger( "focus" );
 
            // Close if already visible
            if ( wasOpen ) {
              return;
            }
 
            // Pass empty string as value to search for, displaying all results
            input.autocomplete( "search", "" );
          });
      },
 
      _source: function( request, response ) {
        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
        response( this.element.children( "option" ).map(function() {
          var text = $( this ).text();
          if ( this.value && ( !request.term || matcher.test(text) ) )
            return {
              label: text,
              value: text,
              option: this
            };
        }) );
      },
 
      _removeIfInvalid: function( event, ui ) {
 
        // Selected an item, nothing to do
        if ( ui.item ) {
          return;
        }
 
        // Search for a match (case-insensitive)
        var value = this.input.val(),
          valueLowerCase = value.toLowerCase(),
          valid = false;
        this.element.children( "option" ).each(function() {
          if ( $( this ).text().toLowerCase() === valueLowerCase ) {
            this.selected = valid = true;
            return false;
          }
        });
 
        // Found a match, nothing to do
        if ( valid ) {
          return;
        }
 
        // Remove invalid value
        this.input
          .val( "" )
          .attr( "title", value + " didn't match any item" )
          .tooltip( "open" );
        this.element.val( "" );
        this._delay(function() {
          this.input.tooltip( "close" ).attr( "title", "" );
        }, 2500 );
        this.input.autocomplete( "instance" ).term = "";
      },
 
      _destroy: function() {
        this.wrapper.remove();
        this.element.show();
      }
});

//$( "#cbBox" ).combobox();

function quickfilter_load()
{
    var fd=new Object ;
	fd.ts=new Date().getTime();
	$.getJSON("quickfilterlist.php", function(qfl){
		if( qfl.res!=1 ) {
			if(qfl.errormsg ) {
				alert(qfl.errormsg);
			}
			else {
				alert("Loading Quick Filter Error!");
			}
			return;
		}
		var qfl=qfl.filterlist;
		if( qfl.length>0 ) {
			var quickfilterlist = [];
			var options="";
			for(var i=0; i< qfl.length; i++){
				quickfilterlist[i]=qfl[i].quickfiltername ;
				options += "<option>" + qfl[i].quickfiltername + "</option>" ;
			}
			$("#cbBox").html(options);
			$("input#quickfilter").picker(quickfilterlist, function(v){
				// load quick filter
				$.getJSON("quickfilterlist.php?quickfiltername="+v, function(quickfilter){
					if( quickfilter.res==1 ){
						var qf=quickfilter.filterlist ;
						if( qf.length>0 ) {
							$("form#filterform")[0].reset();
							// fill form fields
							filterform_load(qf[0]);
							$("#filterform select[name='zoneName']").change();
						}
					}
				});
				return true;
			});
		}
	});
}

quickfilter_load();

function filterform_data()
{
	var fdata = $('#filterform').serializeArray();
	var formd = new Object ;
	$('#filterform input[type="checkbox"]').each(function(){
		formd[$(this).attr("name")]=0;
	});
	for( var i=0; i<fdata.length; i++ ) {
		if(fdata[i].value == 'on') {
			formd[ fdata[i].name ] = 1 ;
		}
		else {
			formd[ fdata[i].name ] = fdata[i].value ;
		}
	}
	formd.ts=new Date().getTime();    // time stamp
	return formd ;
}

// button "Save Quick Filter"
$("button#savequickfilter").click(function(e){
	e.preventDefault();

	//$("#quickfilter").val( $("#cbBox").parent().find("input").val() );

	var fdata = filterform_data();
	if( fdata.quickfiltername.length<1 ) {
		alert("Please enter a name for quick filter!");
		return;
	}
	$.getJSON("quickfiltersave.php", fdata, function(resp){
		if( resp.res == 1 ) {
			// success
			quickfilter_load();
			alert("Quick filter \'"+fdata.quickfiltername+"\' saved!");
		}
		// fine, just silently failed, instead reported as a bug.
		// else if( resp.errormsg ) {
		//	alert( resp.errormsg );
		// }
		else {
			alert( "Can't save this quick filter!");
		}
	});
});

// button "Delete Quick Filter"
$("button#deletequickfilter").click(function(e){
	e.preventDefault();

	//$("#quickfilter").val( $("#cbBox").parent().find("input").val() );

	var fdata = filterform_data();
	if( fdata.quickfiltername.length<1 ) {
		alert("Please select one quick filter!");
		return;
	}
	if( !confirm("Do you want to delete quick filter :\n    "+fdata.quickfiltername) ) {
	   return ;
	}
	$.getJSON("quickfilterdel.php", fdata, function(resp){
		if( resp.res == 1 ) {
			// success
			quickfilter_load();
			$("input#quickfilter").val("");
			alert("Quick filter \'"+fdata.quickfiltername+"\' deleted!");
		}
		else if( resp.errormsg ) {
			alert( resp.errormsg );
		}
		else {
			alert( "Deleting quick filter failed!");
		}
	});
});

function filterform_load( param )
{
	// preset vehicle list
	for( i=0; i<vehiclelist.length; i++) {
		var html="<option>"+vehiclelist[i]+"</option>";
		$("select[name='vehicleGroupName']").append(html);
	}
	for( i=0; i<grouplist.length; i++) {
		var html="<option>"+grouplist[i]+"</option>";
		$("select[name='vehicleGroupName']").append(html);
	}
	
	var field
	for (field in param) {
		var elm=$("form#filterform input[name='"+field+"']");
		if( elm.length>0 ) {
			if( elm.attr("type")=="checkbox" ) {
				elm.prop("checked", (param[field]=='1' || param[field]=='y' || param[field]=='on' ));
			}
			else if( elm.attr("type")=="radio" ) {
				elm.filter("[value='"+param[field]+"']").prop("checked",true);
			}
			else {
				elm.val(param[field]);
			}
		}
		elm=$("form#filterform select[name='"+field+"']");
		if( elm.length>0) {
			elm.val(param[field]);
		}
	}
	setvehicleType();
	$("#filterform input[name='timeType']").change();
}

// vehicle type
function setvehicleType()
{
	var value=$("select[name='vehicleGroupName']").val();
	$("select[name='vehicleGroupName']").empty();
	var list ;
	if( $("#filterform input[name='vehicleType']")[0].checked ) {
		list=vehiclelist ;
	}
	else {
		list=grouplist ;
	}
	for( var i=0; i<list.length; i++) {
		var html="<option>"+list[i]+"</option>";
		$("select[name='vehicleGroupName']").append(html);
	}
	if( value )
		$("select[name='vehicleGroupName']").val(value);
}

setvehicleType();

// vehicle or group type changed
$("#filterform input[name='vehicleType']").change(function(e){
	setvehicleType();
});

// Zone changed
$("#filterform select[name='zoneName']").change(function(e){
	if( typeof(map_zonechanged) == "function" ) {
		map_zonechanged($(this).val());
	}
});


function wait( w )
{
    if( w ) {
		$("body").append('<div class="wait"></div>');
	}
	else {
		$("div.wait").remove();
	}
}

// generate button
$("button#generate").click(function(e){
	e.preventDefault();
	map_clear();
	wait(true);
	var fdata = filterform_data();
	$.getJSON("mapgenerate.php", fdata, function(resp){
		wait(false);
		if( (resp instanceof Array) || resp.res == 1 ) {
			if( resp.count && resp.count>0 ) {
				$("td#gen_count").html(resp.count);
			}
			else {
				$("td#gen_count").html('');
			}
			map_generate(resp, fdata);
		}
		else if( resp.errormsg ) {
			alert(resp.errormsg);
		}
	}).fail(function(jqXHR, textStatus) { 
		console.log( "error" ); 
		wait(false);
	});
});	

// reset button
$("button#reset").click(function(){
	$("td#gen_count").html('');
	$.getJSON("eventparameterload.php",function(eventparameter){
		$("form#filterform")[0].reset();
		if( eventparameter.index ) {
			var param = new Object;
			// convert field name of table 'report_parameter' to 'quickfilter' field name
			param.stopDuration  = eventparameter.stop_duration ;
			param.desStopDuration  = eventparameter.bstop_duration ;
			param.idleDuration  = eventparameter.idle_duration ;
			param.parkDuration  = eventparameter.park_duration ;
			param.speedLimit  = eventparameter.speed ;
			param.gRacingStart  = eventparameter.racing_start ;
			param.gHardBrake  = eventparameter.hard_brake ;
			param.gHardTurn  = eventparameter.hard_turn ;
			param.gRearImpact  = eventparameter.rear_impact ;
			param.gFrontImpact  = eventparameter.front_impact ;
			param.gSideImpact  = eventparameter.side_impact ;
			param.gBumpyRide  = eventparameter.bumpy_ride ;
			param.startTime = eventparameter.startTime ;
			param.endTime = eventparameter.endTime ;
			filterform_load( param );
		}
	});
});

var filter = false ;
if( sessionStorage ) {
	var tdsess = sessionStorage.getItem('tdsess');
	var localsession ;
	if( tdsess ) {
		localsession = JSON.parse(tdsess);
	}
	if( localsession && localsession.mapfilter ) {
		filter = localsession.mapfilter ;
		filterform_load( filter );
	}
	$(window).on( "unload", function(){
		var tdsess = sessionStorage.getItem('tdsess');
		var localsession = {} ;
		if( tdsess ) {
			localsession = JSON.parse(tdsess);
		}
		localsession.mapfilter=filterform_data();

		if( map ) {
			var center = map.getCenter();
			var zoom = map.getZoom();
			localsession.tdcmap={ lat:center.latitude,lon:center.longitude,zoom:zoom };
		}
		sessionStorage.setItem('tdsess', JSON.stringify(localsession));
	});
}

if(!filter)
	$("button#reset").click();

});
</script>
