<?php
// dashboardreport.php -  get dashboard report (numbers)
// Requests:
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2013-07-03
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

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

		// dashboard options
		@$dashboard_option = parse_ini_file($dashboard_conf) ;
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
		
		// Veh. In-Service
		$day_short=array ( 'sun','mon','tue','wen','thu','fri','sat' ) ;
		$sql = 'SELECT count(*) from vehicle WHERE Vehicle_report_'.$day_short[ $reqdate->format('w') ]." != 'n' AND vehicle_out_of_service = 0 ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['Vehicles_In_Service'] = $row[0] ;
			}
			$result->free();
		}

		// Veh.Checked-In
		$sql = "SELECT count(DISTINCT de_vehicle_name) FROM dvr_event WHERE de_event = 1 AND de_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['Vehicles_Checkedin_day'] = $row[0] ;
			}
			$result->free();
		}

		// Veh. Uploaded
		$sql = "SELECT count(DISTINCT vehicle_name) FROM `videoclip` WHERE time_upload BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['Vehicles_Uploaded_day'] = $row[0] ;
			}
			$result->free();
		}

		// Marked Events
		$sql = "SELECT count(*) FROM `vl` WHERE vl_incident = '23' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['marked_events'] = $row[0] ;
			}
			$result->free();
		}

		// System Alerts
		$sql = "SELECT count(*) FROM `td_alert` WHERE date_time BETWEEN '$date_begin' AND '$date_end' ;";
		$result=$conn->query($sql);
		if( $result ){
			if( $row = $result->fetch_array(MYSQLI_NUM) ){
				$resp['report']['system_alerts'] = $row[0] ;
			}
			$result->free();
		}
			
		
		$resp['res'] = 1 ;
		
		$conn->close();
	}
	echo json_encode( $resp );
?>