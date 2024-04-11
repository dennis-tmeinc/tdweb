<?php
// dashboard_data_ph1.php - Calculate statistics data for dashboard
// Requests:
//      reqdata: date for which staticstics data to display
// Return:
//      NA
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

require_once 'vfile.php' ;

if( !$logon || empty($conn) || empty($_SESSION) ) die ;	// has to be included

if( !empty($_REQUEST['reqdate'])) {
	$reqdate = new DateTime($_REQUEST['reqdate']);
}

// default dashboardoption
$dashboard_option = array(
	'tmStartOfDay' => '03:00:00',
	'nAverageDuration' => '60',
);

// dashboard options
$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf  ) );
		
if( empty($dashboard_option['nAverageDuration']) || $dashboard_option['nAverageDuration']<2) 
	$dashboard_option['nAverageDuration']=60 ;

// time ranges
@$date_begin = new DateTime( $dashboard_option['tmStartOfDay'] );
if( empty($date_begin) ) {
	$date_begin = new DateTime( "03:00:00" );
}

$date_begin = new DateTime( $reqdate->format('Y-m-d ').$date_begin->format('H:i:s') );
$date_begin = $date_begin->format('Y-m-d H:i:s');
$date_end = new DateTime( $date_begin );
$date_end->add(new DateInterval('P1D'));
$date_end = $date_end->format('Y-m-d H:i:s');
$date_avg = new DateTime( $date_end );
$date_avg->sub(new DateInterval('P'.$dashboard_option['nAverageDuration'].'D'));
$date_avg = $date_avg->format('Y-m-d H:i:s');

// Veh. In-Service
$day_short=array ( 'sun','mon','tue','wen','thu','fri','sat' ) ;
$sql = 'SELECT vehicle_name from vehicle WHERE Vehicle_report_'.$day_short[ $reqdate->format('w') ]." != 'n' AND vehicle_out_of_service = 0 ;";
$result=$conn->query($sql);
$v_in_service=array();
if( $result ){
	while( $row = $result->fetch_array(MYSQLI_NUM) ){
		$v_in_service[]=$row[0] ;
	}
}

// Veh.Checked-In
$Vehicles_Checkedin_day = 0 ;
$sql = "SELECT count(DISTINCT de_vehicle_name) FROM dvr_event WHERE de_event = 1 AND de_datetime BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ){
		$Vehicles_Checkedin_day=$row[0] ;
	}
}

// Veh. Uploaded
$Vehicles_Uploaded_day = 0 ;
$sql = "SELECT count(DISTINCT vehicle_name) FROM `videoclip` WHERE time_upload BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ){
		$Vehicles_Uploaded_day=$row[0] ;
	}
}

// Marked Events
$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '23' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
$marked_events=0;
if( $result ){
	$row = $result->fetch_array(MYSQLI_NUM) ;
	$marked_events = $row[0] ;
}

// System Alerts
$sql = "SELECT count(*) FROM `td_alert` WHERE date_time BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
$system_alerts=0;
if( $result ){
	$row = $result->fetch_array(MYSQLI_NUM) ;
	$system_alerts = $row[0] ;
}

// sumary table

// Operating Hours
//   look for daily data first.

$Operating_time_day = 0 ;
$Operating_time_avg = 0 ;
$Distance_Travelled_day = 0;
$Distance_Travelled_avg = 0;

$sql = "SELECT sum(vd_distance), sum(vd_travel_time) FROM `vehicle_daily` WHERE DATE(`vd_datetime`) = DATE('$date_begin') ;" ;
$result=$conn->query($sql);
$__result_ok=FALSE ;
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Distance_Travelled_day = $row[0] ;
		$Operating_time_day =$row[1] ;
		$__result_ok=TRUE ;
	}
}

if( !empty($_REQUEST['calc']) && $_REQUEST['calc'] == 'y' ) {
$__result_ok=FALSE ;
}

if(!$__result_ok) {	// not hit by vehicle_daily, to calculate from table vl
	// coor2dist - calculate distance between 2 points by coordinates
	// distance per degree (lat/lon)  = 40075.02/360 = 111.3195 km = 69.1706 miles
	function coor2dist( $lat1, $lon1, $lat2, $lon2 )
	{
		$dx = ($lon2-$lon1)*cos(deg2rad($lat1));
		$dy = ($lat2-$lat1);
		return ( sqrt( $dx*$dx+$dy*$dy  ) ); 
	}
	$Distance_Travelled_day = 0;
	$Operating_time_day = 0;
	
	$sql = "SELECT vl_vehicle_name, vl_datetime, vl_lat, vl_lon FROM `vl` WHERE  vl_datetime BETWEEN '$date_begin' AND '$date_end' ORDER BY vl_vehicle_name, vl_datetime;";
	$result=$conn->query($sql);
	if( $result ){
		$pvehicle = "";
		$ptime=0;
		$plat = 0;
		$plon = 0;
		while( $row = $result->fetch_array(MYSQLI_NUM) ) {
			$ntime = strtotime( $row[1] );
			// calculate travel time and travel distance?
			if( $pvehicle == $row[0] ) {	// same vehicle?
				$dtime = $ntime - $ptime ;
				if( $dtime < 30 ) {		// if wait too long , consider stoped 
					$Operating_time_day+=$dtime ;
					$Distance_Travelled_day+=coor2dist( $plat, $plon, $row[2], $row[3] );
				}
			}
			else {
				$pvehicle = $row[0] ;
			}
			$ptime = $ntime ;
			$plat = $row[2];
			$plon = $row[3];
		}
		// one degree = (40075.02/1.6093472/360) miles
		$Distance_Travelled_day *= 69.1706 ;		// convert degrees to miles
	}
}

// average
$sql = "SELECT sum(vd_distance), sum(vd_travel_time) FROM `vehicle_daily` WHERE DATE(`vd_datetime`) >= DATE('$date_avg') AND DATE(`vd_datetime`) < DATE('$date_begin') ;" ;
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Distance_Travelled_avg = ($row[0] + $Distance_Travelled_day)/($dashboard_option['nAverageDuration']) ;
		$Operating_time_avg = ($row[1] + $Operating_time_day)/($dashboard_option['nAverageDuration']) ;
	}
}

// convert to hours
$Operating_Hours_day = round($Operating_time_day/3600, 2) ;
$Operating_Hours_avg = round($Operating_time_avg/3600, 2) ;

$Distance_Travelled_day = round($Distance_Travelled_day, 2) ;	
$Distance_Travelled_avg = round($Distance_Travelled_avg, 2) ;	

// Vehicles Checked-In. 
$Vehicles_Checkedin_avg = 0 ;
$sql = "SELECT sum(dcount) FROM ( SELECT count(DISTINCT de_vehicle_name) as dcount FROM dvr_event WHERE de_event = 1 AND de_datetime BETWEEN '$date_avg' AND '$date_end' GROUP BY DATE(`de_datetime`) ) AS sq ";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ){
		$Vehicles_Checkedin_avg=round(($row[0])/($dashboard_option['nAverageDuration']),2) ; 
	}
}

// Vehicles Uploaded. 
$Vehicles_Uploaded_avg=0;
$sql = "SELECT sum(dcount) FROM ( SELECT count(DISTINCT vehicle_name) as dcount FROM `videoclip` WHERE time_upload BETWEEN '$date_avg' AND '$date_end' GROUP BY DATE(`time_upload`)  ) AS sq ";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ){
		$Vehicles_Uploaded_avg=round(($row[0])/($dashboard_option['nAverageDuration']),2) ; 
	}
}

// Hours Of Video  & Total Video clips
$Hours_Of_Video_day = 0 ;
$Hours_Of_Video_avg = 0 ;
$Total_Video_Clips_day = 0 ;
$Total_Video_Clips_avg = 0 ;

// single day
$sql= "SELECT sum(TimeStampDiff(SECOND, time_start, time_end)), count(*) FROM `videoclip` WHERE `time_upload`  BETWEEN '$date_begin' AND '$date_end' ;";

$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Hours_Of_Video_day=$row[0] ;
		$Total_Video_Clips_day=$row[1] ;
	}
}

// average
$sql= "SELECT sum(TimeStampDiff(SECOND, time_start, time_end)), count(*) FROM `videoclip` WHERE `time_start`  BETWEEN '$date_avg' AND '$date_end' ;";

$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Hours_Of_Video_avg=($row[0])/($dashboard_option['nAverageDuration']) ;
		$Total_Video_Clips_avg=($row[1])/($dashboard_option['nAverageDuration']) ;
	}
}

$Hours_Of_Video_day = round ( $Hours_Of_Video_day/3600, 2 );
$Hours_Of_Video_avg = round ( $Hours_Of_Video_avg/3600, 2 );

$Total_Video_Clips_day = round ( $Total_Video_Clips_day, 2 );
$Total_Video_Clips_avg = round ( $Total_Video_Clips_avg, 2 );

// Alerts come from table td_alert
//    alert_codes,  1= upload, 2=temperature(fan filter), 3=failed login (connectin), 4= video lost (camera), 5= recording alert

// Connection Alerts
$Connection_Alerts_day = 0 ;
$Connection_Alerts_avg = 0 ;

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 3 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Connection_Alerts_day=$row[0] ;
	}
}

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 3 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Connection_Alerts_avg=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
	}
}

// Camera Alerts
$Camera_Alerts_day = 0 ;
$Camera_Alerts_avg = 0 ;

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 4 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Camera_Alerts_day=$row[0] ;
	}
}

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 4 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Camera_Alerts_avg=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
	}
}

// Recording Alerts
$Recording_Alerts_day = 0 ;
$Recording_Alerts_avg = 0 ;

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 5 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Recording_Alerts_day=$row[0] ;
	}
}

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 5 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Recording_Alerts_avg=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
	}
}

// High Temperature Alerts
$Fan_Filter_Alerts_day = 0 ;
$Fan_Filter_Alerts_avg = 0 ;

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 2 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Fan_Filter_Alerts_day=$row[0] ;
	}
}

$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 2 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Fan_Filter_Alerts_avg=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
	}
}

// alerts (incident from table vl:
// 		vl_incident, 1=stop, 2=route, 4=idling, 16= g-force event, 17=desstop, 18=parking, 23=marked event

// Idling Alerts, from table vl
$Idling_Alerts_day = 0 ;
$Idling_Alerts_avg = 0 ;

$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '4' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Idling_Alerts_day=$row[0] ;
	}
}

$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '4' AND vl_datetime BETWEEN '$date_avg' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$Idling_Alerts_avg=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
	}
}

// G-Force Alerts, from table vl
$GForce_Alerts_day = 0 ;
$GForce_Alerts_avg = 0 ;

$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '16' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$GForce_Alerts_day=$row[0] ;
	}
}

$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '16' AND vl_datetime BETWEEN '$date_avg' AND '$date_end' ;";
$result=$conn->query($sql);
if( $result ){
	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
		$GForce_Alerts_avg=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
	}
}
?>