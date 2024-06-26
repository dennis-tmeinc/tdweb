<!DOCTYPE html>
<html lang="en">
<head><?php
require 'session.php';

// remember recent page
session_save('dashboardpage', $_SERVER['REQUEST_URI'] );

if( strstr($_SERVER['REQUEST_URI'], 'dashboardmorning.php') ) {
    $day_title='Previous Day';
    $title_type='Morning ' ;
}
else {
    $day_title='Today';
    $title_type='Live ' ;
}

?>
    <title>TouchDown&trade Center</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta name="description" content="Touch Down Center by TME">
    <meta name="author" content="Dennis Chen @ TME, 2021-04-15">
    <link href="tdclayout.css" rel="stylesheet" type="text/css" /><link rel="stylesheet" href="https://libs.cdnjs.net/jqueryui/<?php echo $jquiver; ?>/themes/<?php echo $jqtheme; ?>/jquery-ui.min.css"><script src="https://libs.cdnjs.net/jquery/<?php echo $jqver; ?>/jquery.min.js"></script><script src="https://libs.cdnjs.net/jqueryui/<?php echo $jquiver; ?>/jquery-ui.min.js"></script>
    <script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
    <link rel="stylesheet" href="https://libs.cdnjs.net/free-jqgrid/4.14.1/css/ui.jqgrid.min.css"><script src="https://libs.cdnjs.net/free-jqgrid/4.14.1/i18n/min/grid.locale-en.js"></script><script src="https://libs.cdnjs.net/free-jqgrid/4.14.1/jquery.jqgrid.min.js"></script>
    <style type="text/css"><?php echo "#rcontainer { display:none }" ?>
.sum_circle
{
    margin-top: 3px;
    background-image:url('res/big_dashboard_gradient.png');
    background-size: ;
    background-repeat: repeat-x;
    background-position:center center;
    background-color: ;
    height: 100px;
    font-size:40px;
    text-align: center;
    min-width:150px;
}

.sum_circle_green
{
color:green;
}

.sum_circle_red
{
color:red;
}

.sum_title
{
height: 1em;
font-size:14px;
font-weight:bold;
text-align: center;
color: black;
}

#summary_table
{
border-collapse:collapse;
min-width:750px;
}

.system_alert_day {
    text-align: right;
    background-color: #fafafa;
}
.system_alert_avg {
    text-align: right;
    background-color: white;"
}

</style>
<script src="td_alert.js"></script><script>
// start up

$(document).ready(function(){

$("button").button();
$(".btset input").checkboxradio({
      icon: false
    });

var btlive = <?php
    if( $title_type == 'Live ' ) {
        echo '1' ;
    }
    else {
        echo '0' ;
    }
?>;
if( btlive ) {
    $("#btlive").prop("checked",true);
}
else {
    $("#btmorning").prop("checked",true);
}

$(".btset input").change(function(){
   location=$(this).attr("href");
});
$(".btset").controlgroup();

$("#vehicle_list").jqGrid({
    scroll: true,
    datatype: "local",
    height: 300,
    width: 750,
    colNames:['Vehicle','Last Check-In', 'Duration','#Clips','#M. Events', 'Alerts', 'Status'],
    colModel :[
      {name:'vehicle', index:'vehicle', width:180, sortable: true },
      {name:'checkin', index:'checkin', width:180, sortable: true },
      {name:'duration', index:'duration', width:80, sortable: true },
      {name:'clips', index:'clips', width:60, sortable: true, sorttype:"int" },
      {name:'mevents', index:'mevents', width:90, sortable: true, sorttype:"int"},
      {name:'alerts', index:'alerts', width:140, sortable: true },
      {name:'status', index:'status', width:80, sortable: true }
    ],
    rownumbers: true,
    onSelectRow: function(id){
        var vname = $("#vehicle_list").jqGrid('getCell',id, 1) ;
        // showHistory( vname );
    }
});

function load_vehiclelist()
{
    $.getJSON("dashboardvehicles.php", function(resp){
        if( resp.res == 1 ) {
            $("#vehicle_list").jqGrid("clearGridData");
            var griddata = [] ;
            for(var i=0;i<resp.vehicles.length;i++) {
                griddata[i] = { vehicle: resp.vehicles[i][0],
                          checkin: resp.vehicles[i][1],
                          duration: resp.vehicles[i][2],
                          clips: resp.vehicles[i][3],
                          mevents: resp.vehicles[i][4],
                          alerts: resp.vehicles[i][5],
                          status: resp.vehicles[i][6] };
            }
            $("#vehicle_list").jqGrid('addRowData',1,griddata);
        }
    });
}

$( "button#vlexport" ).on( "click", function( event ) {
    event.preventDefault();

    function downloadCSVFile(csv_data, filename) {
        // Create CSV file object and feed
        // our csv_data into it
        CSVFile = new Blob([csv_data], {
            type: "text/csv"
        });

        // Create to temporary link to initiate
        // download process
        let temp_link = document.createElement('a');

        // Download csv file
        temp_link.download = filename;
        let url = window.URL.createObjectURL(CSVFile);
        temp_link.href = url;

        // This link should not be displayed
        temp_link.style.display = "none";
        document.body.appendChild(temp_link);

        // Automatically click the link to
        // trigger download
        temp_link.click();
        document.body.removeChild(temp_link);
    }

    // Variable to store the final csv data
    let csv_data = [];

    // header
    let r = [];
    r.push("Vehicle");
    r.push("Last_CheckIn");
    r.push("Duration");
    r.push("Clips");
    r.push("M.Events");
    r.push("Alerts");
    r.push("Status");
    csv_data.push(r.join(","));

    // Get each row data
    let rows = $("#vehicle_list").jqGrid('getRowData');
    for (let i = 0; i < rows.length; i++) {
        // Stores each csv row data
        r = [];
        r.push( rows[i].vehicle);
        r.push( rows[i].checkin);
        r.push( rows[i].duration);
        r.push( rows[i].clips);
        r.push( rows[i].mevents);
        r.push( rows[i].alerts);
        let st = document.createElement('div');
        st.innerHTML = rows[i].status;
        r.push( st.innerText);
        // Combine each column value with comma
        csv_data.push(r.join(","));
    }
    // Combine each row data with new line character
    csv_data = csv_data.join('\n');
    // Call this function to download csv file
    downloadCSVFile(csv_data, "vehicles.csv");
});

function load_dashboard()
{
    var url = ['dashboardreport.php', 'dashboardreportday.php'];
    for( var phase=0; phase<url.length; phase++) {
        $.getJSON(url[phase], function(resp){
            if( resp.res == 1 ) {
                // summary table
                for (x in resp.report) {
                    if( x.substr( 0, 5 ) == "list_" ) {
                        $("#"+x).jqGrid('addRowData',1,resp.report[x]);
                    }
                    else {
                        $("td#"+x).text(resp.report[x]);
                    }
                }
            }
        });
    }
    load_vehiclelist();
}

load_dashboard();
//setInterval(load_dashboard,300000);

$("#list_Vehicles_In_Service").jqGrid({
    scroll: true,
    datatype: "local",
    height: 240,
    width: 600,
    colNames:['Vehicle Name'],
    colModel :[
      {name:'vehicle_name', width:180, sortable: true }
    ],
    onSelectRow: function(id){
        var vname = $("#list_Vehicles_In_Service").jqGrid('getCell',id, 1) ;
        showHistory( vname );
    },
    rownumbers: true
});

$("#list_Vehicles_Checkedin_day").jqGrid({
    scroll: 1,
    //url:'dashboardreportcheckingrid.php',
    datatype: "local",
    gridview: true,
    height: 240,
    width: 600,
    colNames:['Vehicle Name','Last Check-In'],
    colModel :[
      {name:'de_vehicle_name', width:180, sortable: true },
      {name:'de_datetime', width:180, sortable: true }
    ],
    sortname: "de_datetime",
    sortorder: "desc",
    rownumbers: true,
    rownumWidth: 50,
    onSelectRow: function(id){
        var vname = $("#list_Vehicles_Checkedin_day").jqGrid('getCell',id, 1) ;
        showHistory( vname, 3 );		// 3: login failed
    }
});

$("#list_Vehicles_Uploaded_day").jqGrid({
    scroll: true,
    datatype: "local",
    height: 240,
    width: 600,
    colNames:['Vehicle Name','Upload Time'],
    colModel :[
      {name:'vehicle_name', width:180, sortable: true },
      {name:'time_upload', width:180, sortable: true }
    ],
    rownumbers: true,
    onSelectRow: function(id){
        var vname = $("#list_Vehicles_Uploaded_day").jqGrid('getCell',id, 1) ;
        showHistory( vname, 1 );	// 1: video_uploaded
    }
});

$("#list_marked_events").jqGrid({
    scroll: true,
    datatype: "local",
    height: 240,
    width: 600,
    colNames:['Vehicle Name','Event Time'],
    colModel :[
      {name:'vl_vehicle_name', width:180, sortable: true },
      {name:'vl_datetime', width:180, sortable: true }
    ],
    rownumbers: true,
    onSelectRow: function(id){
        var vname = $("#list_marked_events").jqGrid('getCell',id, 1) ;
        showHistory( vname, 11 );  // panic event
    }
});

$("#list_system_alerts").jqGrid({
    scroll: true,
    datatype: "local",
    height: 240,
    width: 600,
    colNames:['Vehicle Name','Description', 'Alert Code', 'Alert Time'],
    colModel :[
      {name:'dvr_name', width:180, sortable: true },
      {name:'description', width:300, sortable: true },
      {name:'alert_code', width:100, sortable: true },
      {name:'date_time', width:180, sortable: true }
    ],
    rownumbers: true,
    onSelectRow: function(id){
        var vname = $("#list_system_alerts").jqGrid('getCell',id, 1) ;
        showHistory( vname );
    }
});

$("#list_solo_alerts").jqGrid({
    scroll: true,
    url:'dashboardsoloalertsgrid.php',
    datatype: "json",
    height: 240,
    width: 600,
    colNames:['Vehicle Name','Description', 'Alert Code', 'Alert Time'],
    colModel :[
      {name:'dvr_name', width:120, sortable: true, },
      {name:'description', width:300, sortable: true },
      {name:'alert_code', width:130, sortable: true },
      {name:'date_time', width:180, sortable: true }
    ],
    sortname: 'date_time',
    sortorder: 'desc',
    rownumbers: true,
    onSelectRow: function(id){
        var vname = $("#list_solo_alerts").jqGrid('getCell',id, 1) ;
        var alertcode = $("#list_solo_alerts").data("ALERTCODE");
        showHistory( vname, alertcode );
    }
});

$("div#dialog_solo_alerts").dialog({
    autoOpen: false,
    width:"auto",
    modal: true,
    open: function( event, ui ) {
        $("#list_solo_alerts").trigger("reloadGrid");
    },
    buttons:{
        "Close": function() {
            $( this ).dialog( "close" );
        }
    }
});

// compose alert codes
let alert_code = [
    "unknown",
    "video uploaded",
    "temperature",
    "login failed",
    "video lost",
    "storage failed",
    "rtc error",
    "partial storage failure",
    "system reset",
    "ignition on",
    "ignition off",
    "panic",
    "storage recover",
    "video recover",
    "login recover", 
    "partial storage recover",
    "battery low"
];

let alert_sel=":";
for( var c=1; c<alert_code.length; c++) {
    alert_sel += ";" + c + ":" + alert_code[c];
}

$("#list_alert_history").jqGrid({
    scroll: true,
    url:'dashboardalerthistorygrid.php',
    datatype: "json",
    height: 240,
    width: 600,
    colNames:['Vehicle Name','Description', 'Alert Code', 'Alert Time'],
    colModel :[
      {name:'dvr_name', index:'dvr_name', width:120, sortable: false, search: false },
      {name:'description', index:'description', width:300, sortable: false, search: false },
      {name:'alert_code', index:'alert_code', width:130, sortable: true,
        align: "left",
        stype: "select",
        searchoptions: { value:alert_sel }
      },
      {name:'date_time', index:'date_time', width:180, sortable: true }
    ],
    sortname: 'date_time',
    sortorder: 'desc',
    searching: {
            defaultSearch: "cn"
        },
    rownumbers: true
}).jqGrid('filterToolbar',{searchOnEnter : false});

$("div#diaglog_alert_history").dialog({
    autoOpen: false,
    width:"auto",
    modal: true,
    open: function( event, ui ) {
        var sgrid = $("#list_alert_history");
        sgrid[0].clearToolbar();
        sgrid.trigger("reloadGrid");
    },
    buttons:{
        "Export": function() {
            window.open( "dashboardalerthistoryexports.php" );
        },
        "Close": function() {
            $( this ).dialog( "close" );
        }
    }
});

function showHistory( vname, alertcode )
{
    var uurl = "dashboardalerthistorygrid.php?vehicle="+vname ;
    if( alertcode != null) {
        uurl = uurl+"&alert="+alertcode ;
    }
    $("#list_alert_history").setGridParam( {url: uurl, page: 1} );
    $("div#diaglog_alert_history").dialog("option", {title:"Alert History : "+vname });
    $("div#diaglog_alert_history").dialog("open");
}


var dialogid = '';
var selectedvehicle = "";

$("div#diaglog_list").dialog({
    autoOpen: false,
    width:"auto",
    modal: true,
    buttons:{
        "Close": function() {
            $( this ).dialog( "close" );
        }
    }
});

$(".system_status").click(function(){
    dialogid = $(this).attr("id") ;
    $(".listgrid").hide();

    $("div.listgrid#"+dialogid).show();
    $("div#diaglog_list").dialog("option", {title:$("div.listgrid#"+dialogid).attr("title") });
    $("div#diaglog_list").dialog("open");
});

$("td.system_alert").css( 'cursor', 'pointer' );
$("td.sum_circle").css( 'cursor', 'pointer');

$("td.system_alert").click(function(e){
    var alertcode = $(e.target).attr("alertcode") ;
    var alerttitle = $(e.target).attr("alerttitle") ;
    if( !alerttitle || alerttitle.length < 2 ) {
        alerttitle = $(e.target).text() ;
    }
    $("#list_solo_alerts").data("ALERTCODE", alertcode);
    $("#list_solo_alerts").setGridParam( {url: "dashboardsoloalertsgrid.php?alertcode="+alertcode, page: 1} );
    $("div#dialog_solo_alerts").dialog("option", {title: alerttitle});
    $("div#dialog_solo_alerts").dialog("open");
});

var mssmap = null ;
var mssloc = null ;

$("#mss_status").jqGrid({
    scroll: true,
    url:'dashboardmssgrid.php',
    datatype: "json",
    height: 300,
    width: 750,
    colNames:['MSS id','Connection', 'HDD Status','SD1 Status','SD2 Status', 'Access Point Status', 'lat', 'lon'],
    colModel :[
      {name:'mss_id', index:'mss_id', width:180 },
      {name:'mss_connection', index:'mss_connection', width:120 },
      {name:'mss_hdd', index:'mss_hdd', width:120 },
      {name:'mss_sd1', index:'mss_sd1', width:120 },
      {name:'mss_sd2', index:'mss_sd2', width:120},
      {name:'mss_ap', index:'mss_ap', width:180 } ,
      {name:'mss_lat', index:'mss_lat', hidden: true } ,
      {name:'mss_lon', index:'mss_lon', hidden: true }
    ],
    onSelectRow: function(id){
        var m_lat = $("#mss_status").jqGrid('getCell',id, 7) ;
        var m_lon = $("#mss_status").jqGrid('getCell',id, 8) ;
        mssloc = new Microsoft.Maps.Location(m_lat, m_lon) ;

        $("div#dialog_mss_location").dialog("open");
    },
    rownumbers: true,
});

$("div#dialog_mss_location").dialog({
    autoOpen: false,
    width:"auto",
    height:"auto",
    modal: true,
    close: function(event, ui) {
        //if( mssmap ) {
            // mssmap.dispose();
            // mssmap = null;
        //}
    },
    open: function(event, ui) {

        if( mssmap == null ) {
            mssmap = new Microsoft.Maps.Map(document.getElementById("mssmap"),
                {credentials: <?php echo '"'. $map_credentials . '"'; ?> ,
                enableSearchLogo: false,
                enableClickableLogo: false,
            });
        }

        if( mssloc == null ) {
            mssloc = new Microsoft.Maps.Location(47.60357, -122.32945)
        }
        mssmap.setView({
            zoom: 12,
            center: mssloc
        });

        var pin=new Microsoft.Maps.Pushpin(mssloc, {draggable: false});
        mssmap.entities.push(pin);
    },
    buttons:{
        "Close": function() {
            $( this ).dialog( "close" );
        }
    }
});

$( "#status_type_tabs" ).tabs({
    active: 0
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
    <li><img src="res/side-dashboard-logo-green.png" /></li>
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
    <li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">

</pre>
</div>
<strong><span>DASHBOARD</span></strong></div>

<div id="rcontainer">
<div id="rpanel">&nbsp;</div>

<div id="workarea" style="width:auto;">
<p class="btset">
<label for="btmorning"> Morning Status Report </label>
<input href="dashboardmorning.php" class="btsel" name="btset" id="btmorning"
<?php
    if( $title_type != 'Live ' ) {
        echo ' checked="checked" ' ;
    }
?>
type="radio" />
<label for="btlive"> Live Status Report </label>
<input href="dashboardlive.php"  class="btsel"   name="btset" id="btlive"
<?php
    if( $title_type == 'Live ' ) {
        echo ' checked="checked" ' ;
    }
?>
type="radio" />
<label for="btoption"> Dashboard Options </label>
<input href="dashboardoption.php" class="btsel"  name="btset" id="btoption" type="radio" />
</p>

<h2><strong><?php echo $title_type; ?>Status Report</strong></h2>

<div>
<table border="0" cellpadding="1" cellspacing="5" style="min-width: 600px;">
    <tbody>
        <tr>
            <td class="sum_circle sum_circle_green system_status" id="Vehicles_In_Service">0</td>
            <td class="sum_circle sum_circle_green system_status" id="Vehicles_Checkedin_day">0</td>
<?php if( !empty($show_vehicles_uploaded) ) { ?>
            <td class="sum_circle sum_circle_green system_status" id="Vehicles_Uploaded_day">0</td>
<?php } ?>
            <td class="sum_circle sum_circle_red system_alert" alertcode="11" alerttitle="Panic Alerts" id="marked_events">0</td>
            <td class="sum_circle sum_circle_red system_alert" alertcode="2,3,4,5,7,8"  alerttitle="System Alerts" id="system_alerts">0</td>
        </tr>
        <tr>
            <td class="sum_title ui-widget">VEHICLES IN-SERVICE</td>
            <td class="sum_title ui-widget">VEHICLES CHECKED-IN</td>
<?php if( !empty($show_vehicles_uploaded) ) { ?>
            <td class="sum_title ui-widget">VEHICLES UPLOADED</td>
<?php } ?>
            <td class="sum_title ui-widget">PANIC ALERTS</td>
            <td class="sum_title ui-widget">SYSTEM ALERTS</td>
        </tr>
    </tbody>
</table>
</div>

<div id="diaglog_list" >
<div  class="listgrid"  id="Vehicles_In_Service" title="Vehicles In-Service" >
<table id="list_Vehicles_In_Service"></table>
</div>
<div class="listgrid" id="Vehicles_Checkedin_day" title="Vehicles Checked-In" >
<table id="list_Vehicles_Checkedin_day"></table>
</div>

<div class="listgrid" id="Vehicles_Uploaded_day" title="Vehicles Uploaded" >
<table id="list_Vehicles_Uploaded_day"></table>
</div>

<div class="listgrid" id="marked_events" title="Marked Events" >
<table id="list_marked_events"></table>
</div>

<div class="listgrid"  id="system_alerts" title="System Alerts" >
<table id="list_system_alerts"></table>
</div>

</div>
<div id="dialog_solo_alerts" title="Alerts" >
<table id="list_solo_alerts"></table>
</div>
<div id="diaglog_alert_history">
<table id="list_alert_history"></table>
</div>
<!-- mss map dialog -->
<div id="dialog_mss_location" title="MSS Location">
<div id="mssmap" style="min-height:400px;min-width:400px;" ></div>
</div>
<!-- end mss map dialog -->
<h2><?php echo $title_type; ?>Status Summary</h2>

<div id="status_type_tabs">
<ul>
    <li><a href="#summary_simple">Simple</a></li>
    <li><a href="#summary_advance">Advanced</a></li>
<?php if( empty($disable_mss) ) { ?>
    <li><a href="#summary_mss">MSS</a></li>
<?php } ?>
</ul>

<div id="summary_simple">
<table border="1" class="summarytable" id="summary_table">
    <thead style="font-size:22px;">
        <tr>
            <th scope="col">&nbsp;</th>
            <th scope="col"><?php echo $day_title; ?></th>
            <th scope="col">Average (day)</th>
        </tr>
    </thead>
    <tbody style="font-size:18px;" >
        <tr>
            <td class="system_alert" alertcode="3"  >Connection Alerts</td>
            <td class="system_alert_day" id="Connection_Alerts_day" ></td>
            <td class="system_alert_avg" id="Connection_Alerts_avg" ></td>
        </tr>
        <tr>
            <td class="system_alert" alertcode="4" >Camera Alerts</td>
            <td class="system_alert_day" id="Camera_Alerts_day" ></td>
            <td class="system_alert_avg" id="Camera_Alerts_avg" ></td>
        </tr>
        <tr>
            <td class="system_alert" alertcode="5"  >Recording Alerts</td>
            <td class="system_alert_day" id="Recording_Alerts_day"></td>
            <td class="system_alert_avg"  id="Recording_Alerts_avg"></td>
        </tr>
        <tr>
            <td class="system_alert" alertcode="8" >System Reset Alerts</td>
            <td class="system_alert_day" id="System_Reset_Alerts_day"></td>
            <td class="system_alert_avg" id="System_Reset_Alerts_avg"></td>
        </tr>
        <tr>
            <td class="system_alert" alertcode="7" >Partial Storage Failure</td>
            <td class="system_alert_day" id="Partial_Storage_Failure_day"></td>
            <td class="system_alert_avg" id="Partial_Storage_Failure_avg"></td>
        </tr>
        <tr>
            <td class="system_alert" alertcode="2" >High Temperature Alerts</td>
            <td class="system_alert_day" id="Fan_Filter_Alerts_day"></td>
            <td class="system_alert_avg" id="Fan_Filter_Alerts_avg"></td>
        </tr>
        <tr>
            <td class="system_alert" alertcode="11" >Panic Alerts</td>
            <td class="system_alert_day" id="Panic_Alerts_day"></td>
            <td class="system_alert_avg" id="Panic_Alerts_avg"></td>
        </tr>
    </tbody>
</table>
</div>

<div id="summary_advance">
<table border="1" class="summarytable" id="summary_table">
    <thead>
        <tr>
            <th scope="col">&nbsp;</th>
            <th scope="col">
            <div><span style="font-size:14px;"><?php echo $day_title; ?></span></div>
            </th>
            <th scope="col">
            <div><span style="font-size:14px;">Average (day)</span></div>
            </th>
            <th scope="col">
            <div>&nbsp;</div>
            </th>
            <th scope="col">
            <div><span style="font-size:14px;"><?php echo $day_title; ?></span></div>
            </th>
            <th scope="col">
            <div><span style="font-size:14px;">Average (day)</span></div>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-size:12px;">Operating Hours</td>
            <td class="system_alert_day" id="Operating_Hours_day" ></td>
            <td class="system_alert_avg" id="Operating_Hours_avg" ></td>
            <td style="font-size:12px;">Connection Alerts</td>
            <td class="system_alert_day" id="Connection_Alerts_day" ></td>
            <td class="system_alert_avg" id="Connection_Alerts_avg" ></td>
        </tr>
        <tr>
            <td style="font-size:12px;">Distance Travelled</td>
            <td class="system_alert_day" id="Distance_Travelled_day" ></td>
            <td class="system_alert_avg" id="Distance_Travelled_avg" ></td>
            <td style="font-size:12px;">Camera Alerts</td>
            <td class="system_alert_day" id="Camera_Alerts_day" ></td>
            <td class="system_alert_avg" id="Camera_Alerts_avg" ></td>
        </tr>
        <tr>
            <td style="font-size:12px;">Vehicles Checked-In</td>
            <td class="system_alert_day" id="Vehicles_Checkedin_day"></td>
            <td class="system_alert_avg" id="Vehicles_Checkedin_avg"></td>
            <td style="font-size:12px;">Recording Alerts</td>
            <td class="system_alert_day" id="Recording_Alerts_day"></td>
            <td class="system_alert_avg" id="Recording_Alerts_avg"></td>
        </tr>
        <tr>
            <td style="font-size:12px;">Vehicles Uploaded</td>
            <td class="system_alert_day" id="Vehicles_Uploaded_day"></td>
            <td class="system_alert_avg" id="Vehicles_Uploaded_avg"></td>
            <td style="font-size:12px;">System Reset Alerts</td>
            <td class="system_alert_day" id="System_Reset_Alerts_day"></td>
            <td class="system_alert_avg" id="System_Reset_Alerts_avg"></td>
        </tr>
        <tr>
            <td style="font-size:12px;">Hours Of Video</td>
            <td class="system_alert_day" id="Hours_Of_Video_day"></td>
            <td class="system_alert_avg" id="Hours_Of_Video_avg"></td>
            <td style="font-size:12px;">High Temperature Alerts</td>
            <td class="system_alert_day" id="Fan_Filter_Alerts_day"></td>
            <td class="system_alert_avg" id="Fan_Filter_Alerts_avg"></td>
        </tr>
        <tr>
            <td style="font-size:12px;">Total Video Clips</td>
            <td class="system_alert_day" id="Total_Video_Clips_day"></td>
            <td class="system_alert_avg" id="Total_Video_Clips_avg"></td>
            <td style="font-size:12px;">Idling Alerts</td>
            <td class="system_alert_day" id="Idling_Alerts_day"></td>
            <td class="system_alert_avg" id="Idling_Alerts_avg"></td>
        </tr>
        <tr>
            <td style="font-size:12px;">G-Force Alerts</td>
            <td class="system_alert_day" id="GForce_Alerts_day"></td>
            <td class="system_alert_avg" id="GForce_Alerts_avg"></td>
            <td style="font-size:12px;">Partial Storage Failure</td>
            <td class="system_alert_day" id="Partial_Storage_Failure_day"></td>
            <td class="system_alert_avg" id="Partial_Storage_Failure_avg"></td>
        </tr>
        <tr>
            <td style="font-size:12px;">Panic Alerts</td>
            <td class="system_alert_day" id="Panic_Alerts_day"></td>
            <td class="system_alert_avg" id="Panic_Alerts_avg"></td>
            <td style="font-size:12px;"></td>
            <td class="system_alert_day" id="NNDEF_Alerts_day"></td>
            <td class="system_alert_avg" id="UNDEF_Alerts_day"></td>
        </tr>
    </tbody>
</table>

<h4>Vehicle Status List</h4>
<div>
<table id="vehicle_list"></table>
<button id="vlexport">Export</button>
</div>
</div>
<?php if( empty($disable_mss) ) { ?>
<div id="summary_mss">
<table id="mss_status"></table>
</div>
<?php } ?>
</div>
<p></p>
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