<?php
// vmqsave.php - save a video request
// Requests:
//      video request parameters
//		2024-02-13 add
//          repeats: number of repeat request
//          repeatcycle:  Daily, Weekly, Monthly, Yearly

// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2024-2-14
// Copyright 2024 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( empty($_REQUEST['vmq_vehicle_name']) || strlen($_REQUEST['vmq_vehicle_name'])<1 ) {
			$resp['errormsg']="No vehicle specified!";
		}
		else {
			$repeats = $_REQUEST['repeats'];
			if( empty($repeats))
				$repeats = 1;

			$start_time=new DateTime($_REQUEST['vmq_start_time']);
			for( $r = 0; $r<$repeats; $r++) {
				$vmq_start_time = $start_time->format('Y-m-d H:i:s');
				$vmq_end_time=new DateTime($vmq_start_time);
				$vmq_end_time->add(new DateInterval('PT'.$_REQUEST['vmq_duration'].'M'));
				$vmq_end_time=$vmq_end_time->format('Y-m-d H:i:s');
				
				$vmq_ch = 'A' ;
				if( !empty( $_REQUEST['vmq_camera'] ) ) {
					$vmq_ch = implode( ',', $_REQUEST['vmq_camera']);
				}

				$sql="INSERT INTO vmq (`vmq_vehicle_name`,`vmq_ins_user_name`,`vmq_start_time`,`vmq_end_time`, `vmq_channel`, `vmq_description`) VALUES (
				'$_REQUEST[vmq_vehicle_name]','$_SESSION[user]','$vmq_start_time','$vmq_end_time', '$vmq_ch' ,'$_REQUEST[vmq_description]');" ;
				if( $conn->query($sql) ) {
					$resp['res']=1 ;	// success
				}
				else {
					$resp['errormsg']=$conn->error;
				}
				// repeatcycle: Daily, Weekly, Monthly, Yearly
				if( $_REQUEST['repeatcycle'] == 'Daily' ) {
					$start_time->add(new DateInterval('P1D'));
				}
				else if( $_REQUEST['repeatcycle'] == 'Weekly' ) {
					$start_time->add(new DateInterval('P1W'));
				}
				else if( $_REQUEST['repeatcycle'] == 'Monthly' ) {
					$start_time->add(new DateInterval('P1M'));
				}
				else if( $_REQUEST['repeatcycle'] == 'Yearly' ) {
					$start_time->add(new DateInterval('P1Y'));
				}
				else {
					break;
				}
			}
		}
	}
	echo json_encode($resp);
?>