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
		// for search work day
		$weekday_short=array ( 'sun','mon','tue','wen','thu','fri','sat' ) ;

		// dashboard options
		$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf  ) );

		// default value
		if( empty( $dashboard_option ) ) $dashboard_option =  array(
			'tmStartOfDay' => '3:00'
		);

		if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
			$dashboard_option['tmStartOfDay'] = '0:00' ;
		}

		if( empty($_REQUEST['date']) ) {
			$reqdate = new DateTime() ;
		}
		else {
			$reqdate = new DateTime($_REQUEST['date']) ;
		}

		$startOfDay = new DateTime($dashboard_option['tmStartOfDay']);

		// time ranges
		$date_begin = new DateTime( $reqdate->format('Y-m-d ').$startOfDay->format('H:i:s') );
		$date_begin_str = $date_begin->format('Y-m-d H:i:s');

		// get previous (work?) day
		$prevdate = new DateTime($date_begin_str);
		$workday = $prevdate->format('w');
		if( $workday == 1 ) {
			// monday, backward 3 days, to last friday
			$prevdate->sub(new DateInterval('P3D'));
		}
		else if( $workday == 0  ){
			// sunday, backward 2 days, to friday
			$prevdate->sub(new DateInterval('P2D'));
		}
		else {
			// backward 1 day
			$prevdate->sub(new DateInterval('P1D'));
		}

		// no work day?
		$prevdate = new DateTime($date_begin_str);
		$prevdate->sub(new DateInterval('P1D'));

		if( strstr($_SESSION['dashboardpage'], 'dashboardmorning') ) {		// dashboard morning report
			$live = false;
			$date_end_str = $date_begin_str;
			$date_end = new DateTime( $date_end_str );
			$date_begin = $prevdate;
			$date_begin_str = $date_begin->format('Y-m-d H:i:s');
		}
		else {
			$live = true;
			$date_end = new DateTime( $date_begin_str );
			$date_end->add(new DateInterval('P1D'));
			$date_end_str = $date_end->format('Y-m-d H:i:s');
		}

		$resp['vehicles']=array();

		$sql = 'SELECT vehicle_name from vehicle WHERE Vehicle_report_'.$weekday_short[ $date_begin->format('w') ]." != 'n' AND vehicle_out_of_service = 0 ORDER BY vehicle_name ;";
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
		$resp['count'] = $vcount ;

		for( $i=0; $i<$vcount; $i++ ) {

			set_time_limit(30);
			$vehiclename = $v_in_service[$i];
			$vehicle = array();
			$vehicle[0] = $vehiclename ;

			// Last Check-in
			$vehicle[1]='';
			// $sql = "SELECT MAX(de_datetime) FROM dvr_event WHERE de_vehicle_name = '$vehiclename' AND de_event = 1 AND de_datetime BETWEEN '$date_begin_str' AND '$date_end_str' ";
			$sql = "SELECT MAX(de_datetime) FROM dvr_event WHERE de_vehicle_name = '$vehiclename' AND de_event = 1 AND de_datetime < '$date_end_str' ";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array() ) {
					if( $row[0] )
						$vehicle[1]=$row[0];
				}
				$result->free();
			}

			// Video Clips Duration and clips number
			$sql= "SELECT sum(TimeStampDiff(SECOND, time_start, time_end)), count(*) FROM `videoclip` WHERE `vehicle_name` = '$vehiclename' AND `time_upload`  BETWEEN '$date_begin_str' AND '$date_end_str' ;";
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
			// $sql= "SELECT count(*) FROM `vl` WHERE vl_vehicle_name = '$vehiclename' AND vl_incident = '23' AND vl_datetime BETWEEN '$date_begin_str' AND '$date_end_str' ;";
			// if( $result = $conn->query($sql) ) {
			// 	if( $row = $result->fetch_array(MYSQLI_NUM) ) {
			// 		$vehicle[4]=$row[0];
			// 	}
			// 	$result->free();
			// }

			// M.Events (use panic alerts from td_alert instead)
			$sql = "SELECT count(*) FROM `td_alert` WHERE  dvr_name = '$vehiclename' AND alert_code = '11' AND date_time BETWEEN '$date_begin_str' AND '$date_end_str' ;";
			if( $result = $conn->query($sql) ) {
				if( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$vehicle[4]=$row[0];
				}
				$result->free();
			}

			// Alerts
			/*
			$sql = "SELECT count(*) FROM `td_alert` WHERE dvr_name = '$vehiclename' AND alert_code in (2,3,4,5) AND date_time BETWEEN '$date_begin_str' AND '$date_end_str' ;";
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
			$sql = "SELECT alert_code FROM `td_alert` WHERE dvr_name = '$vehiclename' AND date_time BETWEEN '$date_begin_str' AND '$date_end_str' GROUP BY alert_code";
			if( $result = $conn->query($sql) ) {
				while( $row = $result->fetch_array(MYSQLI_NUM) ) {
					switch ($row[0]) {
						case 2:
							$alert_types[] = "High Temperature";
							break;
						case 3:
							$alert_types[] = "Connection";
							break;	
						case 4:
							$alert_types[] = "Camera" ;
							break;
						case 5:
							$alert_types[] = "Recording" ;
							break;
					} 
				}
			}
			$vehicle[5] = implode('/', $alert_types);

			// Good or Bad?
			$siz = 14;
			if(empty($vehicle[5])) {
				if( empty($vehicle[1]) ) {
					$lastlogin = new DateTime("2000-1-1");
				}
				else {
					$lastlogin = new DateTime( $vehicle[1]);
				}
				if( $live ){
					if( $lastlogin < $prevdate ) {
						// bad
						$color = 'Red' ;
						$state = "Bad";
					}
					else if( $lastlogin < $date_begin ) {
						// pending
						$color = 'Blue' ;
						$state = "Pending";
						$siz=12;
					}
					else {
						// good
						$color = 'Green' ;
						$state = "Good";
					}
				}
				else {
					if( $lastlogin < $date_begin ) {
						// bad
						$color = 'Red' ;
						$state = "Bad";
					}
					else {
						// good
						$color = 'Green' ;
						$state = "Good";
					}
				}
			}
			else {
				$color = 'Red' ;
				$state = "Bad";
			}
			$vehicle[6] = "<span style=\"color:$color;font-size:${siz}px;\"><strong>$state</strong></span>";
			$resp['vehicles'][]= $vehicle ;
		}

		$resp['res'] = 1 ;
	}
	echo json_encode( $resp );
?>