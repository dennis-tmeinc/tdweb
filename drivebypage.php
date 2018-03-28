<!DOCTYPE html>
<html>
<head><?php 
require 'session.php'; 
?>
	<title>Drive By Event</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="Touch Down Center by TME" name="description" />
	<meta content="Dennis Chen @ TME, 2013-05-15" name="author" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type="text/javascript" src="https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1"></script><script src="picker.js"></script>
	<link rel="stylesheet" type="text/css" media="screen" href="jq/ui.jqgrid.css" /><script src="jq/grid.locale-en.js" type="text/javascript"></script><script src="jq/jquery.jqGrid.min.js" type="text/javascript"></script>
	<style type="text/css">
		#rcontainer { display:none }
	</style>	
<script>
// start up

$(document).ready(function(){
$("button").button();	
$("input#<?php echo $btsetid ; ?>").prop( "checked", true );
$(".btset").buttonset();
$(".btset input").change(function(){
   location=$(this).attr("href");
});

//$( ".xsel" ).selectmenu();

var selectedtag = "" ;
var tagchannels = {};

var static_name = 1 ;

function display_summary()
{
	// display summary tables
	var da = new Date();
	var year = da.getFullYear() ;
	var month = da.getMonth() ;
	var sumarybodyhtml = '';
	var i ;
	
	var griddata = $("#tag_list").jqGrid( "getRowData" ) ;
	
	for( i=0; i<14; i++, month-- ) {
		if( month<0 ) {
			year-- ;
			month+=12 ;
		}

		var mstr ;
		if( month<9 ) {
		 mstr=''+year+'-'+'0'+(month+1) ;
		}
		else {
		 mstr=''+year+'-'+(month+1) ;
		}
		
		sumarybodyhtml += "<tr>" ;
		sumarybodyhtml += "<td>" + mstr + "</td>" ;
		
		var totalevents = 0;
		var badimages = 0 ;
		var poorimages = 0 ;
		var goodimages = 0 ;
		var sentreports = 0 ;
		for( var ix = 0 ; ix<griddata.length; ix++ ) {
			if( griddata[ix].datetime.substr(0,7) == mstr )  {
				totalevents++ ;
				if( griddata[ix].imgquality == 'Bad' ) {
					badimages++;
				}
				else if( griddata[ix].imgquality == 'Poor' ) {
					poorimages++;
				}
				else {
					goodimages++;
				}
				if( griddata[ix].displaystatus == 'Sent' ) {
					sentreports++;
				}
			}
		}
		
		sumarybodyhtml += "<td>"+totalevents+"</td>" ;
		sumarybodyhtml += "<td>"+badimages+"</td>" ;
		sumarybodyhtml += "<td>"+poorimages+"</td>" ;
		sumarybodyhtml += "<td>"+goodimages+"</td>" ;
		sumarybodyhtml += "<td>"+sentreports+"</td>" ;
		sumarybodyhtml += "</tr>" ;
	}
	$("table#summary_table tbody").html( sumarybodyhtml );
}

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

// Event Tag list
$("#tag_list").jqGrid({
//    scroll: true,
	datatype: "json",
	url:'drivebygrid.php',
	height: 180,
	width: 1092,
	caption: 'Drive By Event Tags',
    colNames:['Client ID', 'Bus ID', 'Date/Time', 'Image Quality', 'License Plate',  'Notes'
<?php if( $driveby_tab == "processed" ) { ?>
	, 'Status', 'Processed By', 'Processed Date & Time', 'Sent To', 'State', 'City/Town' 
<?php } ?>
<?php if( $driveby_tab == "deleted" ) { ?>
	, 'Status', 'Deleted By', 'Deleted Date & Time', 'Sent To', 'State', 'City/Town' 
<?php } ?>
	],
    colModel :[
      {name:'Client_Id', index:'Client_Id', width:180, sortable: true }, 
      {name:'Bus_Id', index:'Bus_Id', width:180, sortable: true }, 
      {name:'Date_Time', index:'Date_Time', width:180, sortable: true },
      {name:'imgquality', index:'imgquality', width:180, sortable: true,
			editable: true, edittype:"select",editoptions:{value:"Good:Good;Poor:Poor;Bad:Bad"} },
      {name:'Plateofviolator', index:'Plateofviolator', width:180, sortable: true,  editable: true },
      {name:'notes', index:'notes', width:240, sortable: true ,
		editable: true, edittype:"textarea", editoptions:{rows:"1",cols:"12"} },
<?php if( $driveby_tab != "new" ) { ?>		
	  {name:'email_status', index:'email_status', width:180, sortable: true },
<?php if( $driveby_tab == "deleted" ) { ?>		
      {name:'event_deleteby', index:'event_deleteby', width:180, sortable: true },
      {name:'event_deletetime', index:'event_deletetime', width:180, sortable: true },
<?php } else { ?>		
      {name:'event_processedby', index:'event_processedby', width:180, sortable: true },
      {name:'event_processedtime', index:'event_processedtime', width:180, sortable: true },
<?php } ?>	  
      {name:'sentto', index:'sentto', width:180, sortable: true },
      {name:'State', index:'State', width:180, sortable: true,  editable: true},
      {name:'City', index:'City', width:180, sortable: true,  editable: true },
<?php } ?>	  
    ],
   	sortname: 'Date_Time',
    sortorder: "desc",
	pager: '#tag_list_pager',
	editurl: "drivebyedit.php",
    viewrecords: true,
	rowNum: 20 ,
    rowList:[20, 50, 100, 200],	
//	gridComplete: display_summary,
	ondblClickRow: function(id) {
		$("#tag_list").jqGrid('editRow',id,true);
	},
	onSelectRow: function(id){ 
		if( id && id!==selectedtag ) {
			// edit new raw
			if( selectedtag ) {
				$("#tag_list").jqGrid('restoreRow',selectedtag);
			}
			// $("#tag_list").jqGrid('editRow',id,true);
		
			$("table#wvideos").css("display", "");
		
			var param = {} ;
			param.id = id ;

			// update selectedtag
			selectedtag = id ;
			$.getJSON("drivebytagload.php", param, function(resp){
				if( resp.res == 1 ) {
				
//					//if( resp.tag.imgquality && !jQuery.isEmptyObject(resp.tag.imgquality) )
//						$("select[name='imgquality']").val(resp.tag.imgquality);
//					else 
//						$("select[name='imgquality']").val("Good");
						
					// set notes
//					if( resp.tag.plateofviolator && !jQuery.isEmptyObject(resp.tag.plateofviolator) )
//						$("input[name='plateofviolator']").val(resp.tag.plateofviolator);
//					else 
//						$("input[name='plateofviolator']").val("");

//					if( resp.tag.notes && !jQuery.isEmptyObject(resp.tag.notes) )
//						$("textarea[name='notes']").val(resp.tag.notes);
//					else 
//						$("textarea[name='notes']").val("");
					
					// set map
					mapcoor = resp.tag.Lat+','+ resp.tag.Lon ;
					setmap();
					
					tagchannels = resp.tag.channels.channel ;
					
					if( !tagchannels ) return ;
					
					// setup camera select
					var camopts = [] ;
					var camopt = "" ;
					for( var ch = 0 ; ch < tagchannels.length; ch++ ) {
						// set lpr1
						var name = "camera"+(ch+1) ;
						if( tagchannels[ch].name ) {
							name = tagchannels[ch].name ;
						}
						camopts[ch] = name ;
						camopt += "<option>"+name+"</option>" ;
					}
					
					var sel = $("select[name='vidcam']")[0].selectedIndex ;
					if( sel >= tagchannels.length || sel < 0 ) {
						sel = 0 ;
					}
					
					// video camera selection
					$("select[name='vidcam']").html(camopt);
					$("select[name='vidcam']")[0].selectedIndex = sel ;
					$("select[name='vidcam']").change();

					var sels = ['lpr1','lpr2','ov1','ov2'] ;
					
					// camera selection
					var i;
					for( i= 0 ; i<sels.length; i++ ) {
						var camname = sels[i]+"cam" ;
						var s = $("select[name='"+camname+"']") ;
						sel = s[0].selectedIndex ;
						if( sel < 0 ) {
							sel = i ;
						}
						if( sel >= tagchannels.length ) {
							sel = 0 ;
						}
						s.html(camopt);
						s[0].selectedIndex = sel ;
						s.change();
					}
					
				}
				else {
					alert("Can not process this tag, it is being used by other user");
				}
			});	

		}
	}
});
//jQuery("#tag_list").jqGrid('filterToolbar',{searchOperators : true});
jQuery("#tag_list").jqGrid('filterToolbar',{searchOnEnter : false});

function load_taglist( param )
{
	param.status='processed' ;
	selectedtag = '';
	$.getJSON("drivebytaglist.php", param, function(resp){
		if( resp.res == 1 ) {
			$("#tag_list").jqGrid("clearGridData");
			var griddata = [] ;
			for(var i=0;i<resp.tags.length;i++) {
				griddata[i] = resp.tags[i] ;
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
}

// inital load
// load_taglist({status:"new"});

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

$("button#EditContacts").click( function() {
	$( ".dialog#dialog_editcontacts" ).dialog("open");
} );

// delete report dialog
$( ".dialog#dialog_delevent" ).dialog({
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
			$.getJSON("drivebydeleteevent.php", param, function(resp){
				if( resp.res == 1 ) {
					$("#tag_list").jqGrid('delRowData', resp.tag);	
					selectedtag = "" ;
					alert( "Reports deleted!" );
				}
				else {
					alert( "Delete Event failed!" );
				}
				$( ".dialog#dialog_delevent" ).dialog("close");
			});
		},
		"Cancel": function() {
			$( this ).dialog( "close" );
		}
	}
});


$("button#DeleteEvent").click( function(e) {
	if( selectedtag ) {
		$( ".dialog#dialog_delevent" ).dialog("open");
	}
});

$("button#EditEvent").click( function(e) {
	if( selectedtag ) {
		$("#tag_list").jqGrid('editRow',selectedtag,true);
	}
});

$("button#SaveEvent").click( function(e) {
	if( selectedtag ) {
		$("#tag_list").jqGrid('saveRow',selectedtag);
	}
});

$("button#GenerateReport").click( function() {
	var param = {} ;
	param.tag = selectedtag ;
	param.mapzoom = mapzoom ;
	var sels = ['lpr1','lpr2','ov1','ov2'] ;
	// cameras
	var i;
	for( i= 0 ; i<sels.length; i++ ) {
		var camname = sels[i]+"cam" ;
		param['ch'+i] = $("select[name='"+camname+"']").val();
		//param['pos'+i] = $("div#"+sels[i]+"slider").slider("value")/30;
		$("video[name='"+ camname +"']")[0].pause();
		param['pos'+i] = $("video[name='"+ camname +"']")[0].currentTime ;
	}
	$.getJSON("drivebymkreport.php", param, function(resp){
		if( resp.res == 1 ) {
			window.open("drivebyreportpdf.php?tag="+resp.tag, "_blank" ) ;
		}
		else {
			alert( "Generating report failed!" );
		}
	});
});

// current zoom level
var mapzoom = 15 ;
var mapcoor = "37.778297,-122.417297" ;

function setmap() 
{
	var mapimg = "https://dev.virtualearth.net/REST/v1/Imagery/Map/Road/"+mapcoor+"/"+mapzoom+"?pp="+mapcoor+";0&ms=360,200&key="+
			<?php echo "'$map_credentials'"; ?>	;
	$("img#mapimage").prop("src", mapimg );
	$("#mapaddress").text( "" );
	$("#coordinate").text( "" );
	$.ajax({
		url : "https://dev.virtualearth.net/REST/v1/Locations/"+mapcoor ,
		data : {o:"json",key:<?php echo "'$map_credentials'"; ?>},
		dataType : 'jsonp',	jsonp :'jsonp'
	}).done(function(location){
		if( location.resourceSets[0].resources[0] && location.resourceSets[0].resources[0].address && location.resourceSets[0].resources[0].address.formattedAddress ) {
			$("#mapaddress").text(location.resourceSets[0].resources[0].address.formattedAddress);
			$("#coordinate").text( mapcoor );
		}
	
	});

}

$("input#zoomlevel").spinner({
max: 18, 
min: 2,
create: function( event, ui ) {
	mapzoom = $("input#zoomlevel").spinner("value") ;
	setmap( );
},
change: function( event, ui ) {
	mapzoom = $("input#zoomlevel").spinner("value") ;
	setmap( );
},
spin: function( event, ui ) {
	mapzoom = ui.value;
	setmap();
},
});
		
// load frames images
//   sections: lpr1, lpr2, ov1, ov2
//   time: offset from begin
//   channel: camera channel
function img_frame( section, time, channel )
{
	var tagname = selectedtag ;
	var img = "drivebyframe.php?tag=" + tagname +"&channel="+channel+"&time="+time;
	$("img[name='"+section+"img']").prop("src", img);
	var min = Math.floor(time/60) ;
	var sec = (time%60).toFixed(1) ;
	if( sec<10 ) sec = "0"+sec ;
	$("span#"+section+"time").text( ""+min+":"+sec );
}
		
$(".slider").slider({
min:0,
max:500,
change:function(event,ui){
	var s = $(this).attr("name") ;
	s = s.substr(0, s.length-6 ) ;
	var ch = $("select[name='"+s+"cam']").val();
	img_frame( s, ui.value/30, ch );
},
slide:function(event,ui){
	var s = $(this).attr("name") ;
	s = s.substr(0, s.length-6 ) ;
	var ch = $("select[name='"+s+"cam']").val();
	img_frame( s, ui.value/30, ch );	
}
});

$("select.cam").change(function(){
	var name = $(this).attr("name") ;
	var cam = name.substr( 0, name.length - 3 );
	
	var s = $("select[name='"+cam+"cam']") ;
	var len = 20 ;
	var v = s.val();
	for( var ch = 0 ; ch < tagchannels.length; ch++ ) {
		if( v == tagchannels[ch].name ) {
			len = tagchannels[ch].videolen ;
			break;
		}
	}

	var tagname = selectedtag ;
	var channel = s.val();
	var mp4 = "drivebyvideo.php?tag=" + tagname +"&channel="+channel;
	$("video[name='"+ name +"']").attr("src", mp4);
	$("video[name='"+ name +"']")[0].oncanplay=function(){
		var video = $("video[name='"+ name +"']")[0] ;
		video.oncanplay=null ;
		video.currentTime = len/2 ;
	}
});

$("button.step").on('click',function(){
	var name = $(this).attr("name") ;
	var dir = name.substr( name.length - 8 ) ;
	var s = name.substr( 0, name.length - 8 );
	
	var xslider = $("div#"+s+"slider");
	var sv = xslider.slider("value");
	if( dir=="stepback" ) {
		sv-=1 ;
	}
	else {
		sv+=1 ;
	}
	xslider.slider("value",sv);
	

	var step ;
	if( dir=="stepback" ) {
		step = -(1.0/30) ;
	}
	else {
		step = (1.0/30) ;
	}
	
	var videoname = s+"cam" ;
	$("video[name='"+ videoname +"']")[0].pause();
	$("video[name='"+ videoname +"']")[0].currentTime += step ;
	
});
	
$("select[name='vidcam']").change(function(){
	var tagname = selectedtag ;
	var channel = $("select[name='vidcam']").val();
	var mp4 = "drivebyvideo.php?tag=" + tagname +"&channel="+channel;
	$("#avivideo").attr("src", mp4);
});

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
<input name="btset" href="drivebyevents.php"         id="btdrivebyevents"         type="radio" checked="checked" /><label for="btdrivebyevents">New Events</label>
<input name="btset" href="drivebyprocessed.php"      id="btdrivebyprocessed"      type="radio" /><label for="btdrivebyprocessed">Processed Events</label> 
<input name="btset" href="drivebydeleted.php"        id="btdrivebydeleted"        type="radio" /><label for="btdrivebydeleted">Deleted Events</label>
<input name="btset" href="drivebyreports.php"        id="btdrivebyreports"        type="radio" /><label for="btdrivebyreports">Reports</label> 
<input name="btset" href="drivebydeletedreports.php" id="btdrivebydeletedreports" type="radio" /><label for="btdrivebydeletedreports">Deleted Reports</label> 
</p>

<!-- work area --->
<div>
<table id="tag_list"></table> 
<div id="tag_list_pager"></div>
</div>
<button id="GenerateReport">Generate Report</button>
<button id="EditEvent">Edit</button>
<button id="SaveEvent">Save</button>
<button id="DeleteEvent">Delete</button>

<table id="wvideos" border="0" cellpadding="2" cellspacing="4" style="width:500px;display:none;">
	<tbody>
		<tr>
			<td>
			<video id="lpr2cam" name="lpr1cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>LPR1:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam" name="lpr1cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="lpr1stepback"><span class="ui-icon ui-icon-seek-prev"></span></button><button class="step" name="lpr1stepforw"> <span class="ui-icon ui-icon-seek-next"></span></button>
			</div>
			</td>
			
			<td>
			<video id="lpr2cam" name="lpr2cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>LPR2:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="lpr2cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="lpr2stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="lpr2stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</div>
			</td>
		
			<td  rowspan="2">
			<img id="mapimage" alt="MAP NOT AVAILABLE" src="" />
			<div>MAP:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;<label for="spinner">Zoom Level: </label><input id="zoomlevel" value="15"></div>
			<p>Address: <span id="mapaddress"></span></p>
			<p>Coordinate: <span id="coordinate"></span></p>
			</td>			
		</tr>
		<tr>
			<td>
			<video id="ov1cam" name="ov1cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>OV1:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="ov1cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="ov1stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="ov1stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</div>
			</td>
			
			<td>
			<video id="ov2cam" name="ov2cam" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<br/>
			<div>OV2:&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<select class="cam"  name="ov2cam"></select>
			&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
			<button class="step" name="ov2stepback"><span class="ui-icon ui-icon-seek-prev"></button><button class="step" name="ov2stepforw"><span class="ui-icon ui-icon-seek-next"></button>
			</div>
			</td>
		</tr>

	</tbody>
</table>

<!-- disabled video screen -->
<div style="display:none">
			<video id="avivideo" width="360" src="" type="video/mp4" controls>Your browser does not support the video tag.</video>
			<div>Video Clip:&nbsp; &nbsp;&nbsp;&nbsp;
			<select name="vidcam"></select></div>
</div>			
<p/>

</div>


<div style="margin-left:14px;display:none" id="summary14m" >
<h4>14 Months Status Summary</h4>
<table border="1" class="summarytable" id="summary_table">
</table>
</div>

<!-- Edit Contacts Dialog -->
<div class="dialog" title="Contacts" id="dialog_editcontacts">

</div>

<!-- Delete Report Dialog -->
<div class="dialog" title="Delete Event" id="dialog_delevent">
	<h4> Please confirm that you want to delete this event,</h4>
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
<div style="float:left"></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>