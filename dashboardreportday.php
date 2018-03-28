<?php
// dashboardreport.php -  get dashboard report (numbers)
// Requests:
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2013-07-03
// Copyright 2013 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		// td_alert codes
		/*
			1: video_uploaded, 
			2: temperature, 
			3: fail_login, 
			4: video_lost, 
			5: storage fail, 
			6: rtc error, 
			7: partial storage fail,
			8: system reset, 
			9: ignition on 
			10: ignition off
			11: panic alert
		*/

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql = "select now();" ;
		$reqdate = new DateTime() ;
		// use mysql time instead if possible
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array() ) {
				$reqdate = new DateTime( $row[0] );
			}
			$result->free();
		}
		if( strstr($_SESSION['dashboardpage'], 'dashboardmorning') ) {		// dashboard morning?
			$reqdate->sub(new DateInterval('P1D'));
		}

		// load dashboard options
		$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;
				
		// default value
		if( empty( $dashboard_option ) ) $dashboard_option =  array(
			'tmStartOfDay' => '3:00'
		);
		
		if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
			$dashboard_option['tmStartOfDay'] = '0:00' ;
		}
		if( empty( $dashboard_option['nAverageDuration'] ) || $dashboard_option['nAverageDuration'] < 2 ) {
			$dashboard_option['nAverageDuration'] = 60 ;
		}
				
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

		$resp['report']=array();
		
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
				if( $row[0] ) {
					$Distance_Travelled_day = $row[0] ;
					$__result_ok=TRUE ;
				}
				if( $row[1] ) {
					$Operating_time_day =$row[1] ;
					$__result_ok=TRUE ;
				}
			}
			$result->free();
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
		
			$min_traveltime = empty($min_traveltime)?30:$min_traveltime ;
			
			$sql = "SELECT vl_vehicle_name, vl_datetime, vl_lat, vl_lon FROM `vl` WHERE vl_datetime BETWEEN '$date_begin' AND '$date_end' ORDER BY vl_vehicle_name, vl_datetime;";
			$result=$conn->query($sql,MYSQLI_USE_RESULT);	// set MYSQLI_USE_RESULT for huge data
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
						if( $dtime <= $min_traveltime ) {		// if wait too long , consider as not travelling 
							$Operating_time_day+=$dtime ;
							$dist = coor2dist( $plat, $plon, $row[2], $row[3] ) ;
							if( $dist < 1/111.3195 ) {			// < 1km
								$Distance_Travelled_day+=$dist;
							}
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
				$result->free();
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
			$result->free();
		}

		// convert to hours
		$resp['report']['Operating_Hours_day'] = round($Operating_time_day/3600, 2) ;
		$resp['report']['Operating_Hours_avg'] = round($Operating_time_avg/3600, 2) ;

		$resp['report']['Distance_Travelled_day'] = round($Distance_Travelled_day, 2) ;	
		$resp['report']['Distance_Travelled_avg'] = round($Distance_Travelled_avg, 2) ;	

		// Vehicles Checked-In. 
		$sql = "SELECT sum(dcount) FROM ( SELECT count(DISTINCT de_vehicle_name) as dcount FROM dvr_event WHERE de_event = 1 AND de_datetime BETWEEN '$date_avg' AND '$date_end' GROUP BY DATE(`de_datetime`) ) AS sq ";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['Vehicles_Checkedin_avg']=round(($row[0])/($dashboard_option['nAverageDuration']),2) ; 
			}
			$result->free();
		}

		// Vehicles Uploaded. 
		$sql = "SELECT sum(dcount) FROM ( SELECT count(DISTINCT vehicle_name) as dcount FROM `videoclip` WHERE time_upload BETWEEN '$date_avg' AND '$date_end' GROUP BY DATE(`time_upload`)  ) AS sq ";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['Vehicles_Uploaded_avg']=round(($row[0])/($dashboard_option['nAverageDuration']),2) ; 
			}
			$result->free();
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
			$result->free();
		}

		// average
		$sql= "SELECT sum(TimeStampDiff(SECOND, time_start, time_end)), count(*) FROM `videoclip` WHERE `time_upload`  BETWEEN '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$Hours_Of_Video_avg=($row[0])/($dashboard_option['nAverageDuration']) ;
				$Total_Video_Clips_avg=($row[1])/($dashboard_option['nAverageDuration']) ;
			}
			$result->free();
		}

		$resp['report']['Hours_Of_Video_day'] = round ( $Hours_Of_Video_day/3600, 2 );
		$resp['report']['Hours_Of_Video_avg'] = round ( $Hours_Of_Video_avg/3600, 2 );

		$resp['report']['Total_Video_Clips_day'] = round ( $Total_Video_Clips_day, 2 );
		$resp['report']['Total_Video_Clips_avg'] = round ( $Total_Video_Clips_avg, 2 );

		// Alerts come from table td_alert
		//    alert_codes,  1= upload, 2=temperature(fan filter), 3=failed login (connectin), 4= video lost (camera), 5= recording alert

		// Connection Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 3 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Connection_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 3 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Connection_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}

		// Camera Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 4 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Camera_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 4 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Camera_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}

		// Recording Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 5 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Recording_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 5 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Recording_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}

		// System Reset Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 8 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['System_Reset_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 8 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['System_Reset_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}
		
		// High Temperature Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 2 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Fan_Filter_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = 2 AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Fan_Filter_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}

		// alerts (incident from table vl:
		// 		vl_incident, 1=stop, 2=route, 4=idling, 16= g-force event, 17=desstop, 18=parking, 23=marked event

		// Idling Alerts, from table vl
		$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '4' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Idling_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '4' AND vl_datetime BETWEEN '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Idling_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}

		// G-Force Alerts, from table vl
		$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '16' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['GForce_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '16' AND vl_datetime BETWEEN '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['GForce_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}
		
		
		// Partial Storage Alerts
		$alert_code = 7 ;
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = $alert_code AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Partial_Storage_Failure_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = $alert_code AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Partial_Storage_Failure_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}

		
		// Panic Alerts
		$alert_code = 11 ;
		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = $alert_code AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Panic_Alerts_day']=$row[0] ;
			}
			$result->free();
		}

		$sql = "SELECT count(*) FROM `td_alert` WHERE alert_code = $alert_code AND date_time BETWEEN  '$date_avg' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$resp['report']['Panic_Alerts_avg']=round( ($row[0])/($dashboard_option['nAverageDuration']), 2) ;
			}
			$result->free();
		}
		
		$resp['res'] = 1 ;
		
		$conn->close();
	}
	echo json_encode( $resp );
?>