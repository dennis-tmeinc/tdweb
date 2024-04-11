<?php
// vmqeventsave.php - save a video request on event
// Requests:
//      video request parameters

// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2024-3-18
// Copyright 2024 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if(  $_SESSION['user_type'] != "admin" ) {	// admin 
			$resp['errormsg']='Not allowed!' ;
		}
		else if( empty($_REQUEST['ev_vehicle_name']) || strlen($_REQUEST['ev_vehicle_name'])<1 ) {
			$resp['errormsg']="No vehicle specified!";
		}
		else {
			$start_time=new DateTime($_REQUEST['ev_start_time']);
			$start_time_str = $start_time->format('Y-m-d H:i:s');
			$end_time=new DateTime($start_time_str);
			$end_time->add(new DateInterval('P'.$_REQUEST['ev_duration'].'D'));
			$end_time_str=$end_time->format('Y-m-d H:i:s');
			
			$ev_ch = 'A' ;
			if( !empty( $_REQUEST['ev_camera'] ) ) {
				$ev_ch = implode( ',', $_REQUEST['ev_camera']);
			}

			$sql="INSERT INTO vmq_event (`vehicleId`, `datetime_start`, `datetime_end`, event_code, camera, pre_time, post_time ) VALUES (
			'$_REQUEST[ev_vehicle_name]', '$start_time_str','$end_time_str', '$_REQUEST[event]', '$ev_ch' ,'$_REQUEST[pre_time]' , '$_REQUEST[post_time]');" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']=$conn->error;
			}
		}
	}
	echo json_encode($resp);
?>