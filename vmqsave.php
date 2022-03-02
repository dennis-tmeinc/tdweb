<?php
// vmqsave.php - save a video request
// Requests:
//      video request parameters
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( empty($_REQUEST['vmq_vehicle_name']) || strlen($_REQUEST['vmq_vehicle_name'])<1 ) {
			$resp['errormsg']="No vehicle specified!";
		}
		else {

			$vmq_end_time=new DateTime($_REQUEST['vmq_start_time']);
			$vmq_end_time->add(new DateInterval('PT'.$_REQUEST['vmq_duration'].'M'));
			$vmq_end_time=$vmq_end_time->format('Y-m-d H:i:s');
			
			$vmq_ch = 'A' ;
			if( !empty( $_REQUEST['vmq_camera'] ) ) {
				$vmq_ch = implode( ',', $_REQUEST['vmq_camera']);
			}

			$sql="INSERT INTO vmq (`vmq_vehicle_name`,`vmq_ins_user_name`,`vmq_start_time`,`vmq_end_time`, `vmq_channel`, `vmq_description`) VALUES (
			'$_REQUEST[vmq_vehicle_name]','$_SESSION[user]','$_REQUEST[vmq_start_time]','$vmq_end_time', '$vmq_ch' ,'$_REQUEST[vmq_description]');" ;
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