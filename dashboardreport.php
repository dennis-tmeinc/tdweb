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
		$reqdate = new DateTime() ;
		if( strstr($_SESSION['dashboardpage'], 'dashboardmorning') ) {		// dashboard morning?
			$reqdate->sub(new DateInterval('P1D'));
		}

		// dashboard options
		$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;
		
		// default value
		if( empty( $dashboard_option ) ) $dashboard_option =  array(
			'tmStartOfDay' => '3:00'
		);
		
		if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
			$dashboard_option['tmStartOfDay'] = '0:00' ;
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

		$resp['report']=array();
		
		// Veh. In-Service List
		$day_short=array ( 'sun','mon','tue','wen','thu','fri','sat' ) ;
		$sql = 'SELECT vehicle_name from vehicle WHERE Vehicle_report_'.$day_short[ $reqdate->format('w') ]." != 'n' AND vehicle_out_of_service = 0 ;";
		$result=$conn->query($sql);
		$resp['report']['list_Vehicles_In_Service'] = array() ;
		if( $result ){
			while( $row = $result->fetch_array(MYSQLI_ASSOC) ){
				$resp['report']['list_Vehicles_In_Service'][] = $row ;
			}
			$result->free();
		}
		$resp['report']['Vehicles_In_Service'] = count($resp['report']['list_Vehicles_In_Service']) ;
		
		// Veh.Checked-In List
		$sql = "SELECT de_vehicle_name, MAX(de_datetime) as de_datetime FROM dvr_event WHERE de_event = 1 AND de_datetime BETWEEN '$date_begin' AND '$date_end' GROUP BY de_vehicle_name ";
		$result=$conn->query($sql);
		$resp['report']['list_Vehicles_Checkedin_day'] = array() ;
		if( $result ){
			while( $row = $result->fetch_array(MYSQLI_ASSOC) ){
				$resp['report']['list_Vehicles_Checkedin_day'][] = $row ;
			}
			$result->free();
		}
		$resp['report']['Vehicles_Checkedin_day'] = count($resp['report']['list_Vehicles_Checkedin_day']) ;
		
		// Veh. Uploaded List
		$sql = "SELECT vehicle_name, MAX(time_upload) as time_upload FROM videoclip WHERE time_upload BETWEEN '$date_begin' AND '$date_end' GROUP by vehicle_name";
		$result=$conn->query($sql);
		$resp['report']['list_Vehicles_Uploaded_day'] = array() ;
		if( $result ){
			while( $row = $result->fetch_array(MYSQLI_ASSOC) ){
				$resp['report']['list_Vehicles_Uploaded_day'][] = $row ;
			}
			$result->free();
		}
		$resp['report']['Vehicles_Uploaded_day'] = count($resp['report']['list_Vehicles_Uploaded_day']) ;
		
		// Marked Events
		// $sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '23' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		// $result=$conn->query($sql);
		// if( $result ){
		//	if( $row = $result->fetch_array(MYSQLI_NUM) ){
		//		$resp['report']['marked_events'] = $row[0] ;
		//	}
		//	$result->free();
		// }
		
		// Marked events list
		// $sql = "SELECT vl_vehicle_name, vl_datetime FROM `vl` WHERE vl_incident = '23' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		// $result=$conn->query($sql);
		// $resp['report']['list_marked_events'] = array() ;
		// if( $result ){
		// 	while( $row = $result->fetch_array(MYSQLI_ASSOC) ){
		// 		$resp['report']['list_marked_events'][] = $row ;
		// 	}
		// 	$result->free();
		// }
		
		// Use panic alert as Marked Events ( remove bug report that consider they are diffferent )
		// Marked events list (use panic alert instead, to remove bug report that consider they are diffferent )
		// $sql = "SELECT dvr_name as vl_vehicle_name, date_time as vl_datetime FROM `td_alert` WHERE alert_code = '11' AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
		// $result=$conn->query($sql);
		// $resp['report']['list_marked_events'] = array() ;
		// if( $result ){
		// 	while( $row = $result->fetch_array(MYSQLI_ASSOC) ){
		// 		$resp['report']['list_marked_events'][] = $row ;
		// 	}
		// 	$result->free();
		// }
		// $resp['report']['marked_events'] = count( $resp['report']['list_marked_events'] );
		
		// System Alerts

		// System Alerts list
		$alert_types=array(
			"unknown",
			"video uploaded",		
			"High Temperature",
			"Connection",
			"Camera",
			"Recording",
			"rtc error",
			"partial storage failure",
			"system reset",
			"ignition on",
			"ignition off",
			"panic"			
			);
		//$system_alert_type = "2,3,4,5,7,8" ;
		
		//$sql = "SELECT dvr_name, description, alert_code, date_time FROM `td_alert` WHERE  alert_code in ($system_alert_type) AND date_time BETWEEN '$date_begin' AND '$date_end' ORDER BY `date_time` DESC ";
		//$resp['report']['list_system_alerts'] = array() ;
		//$result=$conn->query($sql);
		//if( $result ){
		//	while( $row = $result->fetch_array(MYSQLI_ASSOC) ){
		//		if( !empty( $alert_types[ $row['alert_code'] ] ) ) {
		//			$row['alert_code'] = $alert_types[ $row['alert_code'] ] ;
		//		}
		//		$resp['report']['list_system_alerts'][] = $row ;
		//	}
		//	$result->free();
		//}
		// $resp['report']['system_alerts'] = count( $resp['report']['list_system_alerts'] );
		
		$resp['res'] = 1 ;
	}
	echo json_encode( $resp );
?>