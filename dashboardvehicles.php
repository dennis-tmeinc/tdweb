<?php
// dashboardvehicles.php -  get dashboard vehicle status list
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
		if( $conf = vfile_open( $dashboard_conf ) ) {
			$confstr = vfile_read( $conf, 32000 ) ;
			$dashboard_option = parse_ini_string( $confstr );
			vfile_close( $conf );
		}
		
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

		$resp['vehicles']=array();
		
		$day_short=array ( 'sun','mon','tue','wen','thu','fri','sat' ) ;
		$sql = 'SELECT vehicle_name from vehicle WHERE Vehicle_report_'.$day_short[ $reqdate->format('w') ]." != 'n' AND vehicle_out_of_service = 0 ORDER BY vehicle_name ;";
		$result=$conn->query($sql);
		$v_in_service=array();
		if( $result ){
			while( $row = $result->fetch_array(MYSQLI_NUM) ){
				$v_in_service[]=$row[0] ;
			}
			$result->free();
		}

		// vehicles list
		
		$vcount = count($v_in_service) ;
		
		for( $i=0; $i<$vcount; $i++ ) {
		
			set_time_limit(30);
		
			$vehicle = array();
			$vehicle[0] = $v_in_service[$i] ;
			
			// Last Check-in
			$vehicle[1]='';
			$sql = "SELECT MAX(de_datetime) FROM dvr_event WHERE de_vehicle_name = '$v_in_service[$i]' AND de_event = 1 AND de_datetime BETWEEN '$date_begin' AND '$date_end' ";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array() ) {
					if( $row[0] )
						$vehicle[1]=$row[0];
				}
				$result->free();
			}
			
			// Video Clips Duration and clips number
			$sql= "SELECT sum(TimeStampDiff(SECOND, time_start, time_end)), count(*) FROM `videoclip` WHERE `vehicle_name` = '$v_in_service[$i]' AND `time_upload`  BETWEEN '$date_begin' AND '$date_end' ;";
			$v_duration=0;
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$v_duration=$row[0];
					$vehicle[3]=$row[1];
				}
				$result->free();
			}

			// convert to time format
			$h = floor($v_duration/3600) ;
			$m = floor($v_duration/60)%60 ;
			if( $m < 10 ) $m='0'.$m ;
			$s = $v_duration%60 ;
			if( $s < 10 ) $s='0'.$s ;			
			$vehicle[2] = "$h:$m:$s" ;
		   
			// M.Events
			$sql= "SELECT count(*) FROM `vl` WHERE vl_vehicle_name = '$v_in_service[$i]' AND vl_incident = '23' AND vl_datetime BETWEEN '$date_begin' AND '$date_end' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$vehicle[4]=$row[0];
				}
				$result->free();
			}
		   
			// Alerts
			/*
			$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$v_in_service[$i]' AND alert_code in (2,3,4,5) AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$vehicle[5]=$row[0];
				}
				$result->free();
			}
			*/
			
			// To display alerts types instead of alerts numbers
			//    alert_codes,  2:Fan Filter, 3:Connection, 4:Camera, 5:Recording
			
			$alert_types=array() ;
			
			// Connection
			$alerts=0;
			$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$v_in_service[$i]' AND alert_code = 3 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$alerts=$row[0];
				}
				$result->free();
			}
			if( $alerts>0 ) $alert_types[]="Connection" ;
			
			// Camera
			$alerts=0;
			$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$v_in_service[$i]' AND alert_code = 4 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$alerts=$row[0];
				}
				$result->free();
			}
			if( $alerts>0 ) $alert_types[]="Camera" ;
			
			// Recording
			$alerts=0;
			$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$v_in_service[$i]' AND alert_code = 5 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$alerts=$row[0];
				}
				$result->free();
			}
			if( $alerts>0 ) $alert_types[]="Recording" ;
			
			// Fan Filter
			$alerts=0;
			$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$v_in_service[$i]' AND alert_code = 2 AND date_time BETWEEN '$date_begin' AND '$date_end' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$alerts=$row[0];
				}
				$result->free();
			}
			if( $alerts>0 ) $alert_types[]="Fan Filter" ;
			
			$vehicle[5] = implode('/', $alert_types);
			
			// Good or Bad?
			$vehicle[6] = empty($vehicle[5])?'<span style="color:#0f0;font-size:14px;"><strong>Good</strong></span>':'<span style="color:#B22;font-size:14px;"><strong>Bad</strong></span>' ;

			$resp['vehicles'][]= $vehicle ;
		}

		$resp['res'] = 1 ;
		
		$conn->close();
	}
	echo json_encode( $resp );
?>