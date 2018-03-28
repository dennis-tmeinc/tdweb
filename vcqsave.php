<?php
// vcqsave.php - save a video request (cell table)
// Requests:
//      video request parameters
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2016-12-14
// Copyright 2016 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( empty($_REQUEST['vcq_vehicle_name']) || strlen($_REQUEST['vcq_vehicle_name'])<1 ) {
			$resp['errormsg']="No vehicle specified!";
		}
		else {

			$vcq_end_time=new DateTime($_REQUEST['vcq_start_time']);
			$vcq_end_time->add(new DateInterval('PT'.$_REQUEST['vcq_duration'].'M'));
			$vcq_end_time=$vcq_end_time->format('Y-m-d H:i:s');
			
			$sql="INSERT INTO vcq (`vcq_vehicle_name`,`vcq_ins_user_name`,`vcq_start_time`,`vcq_end_time`,`vcq_description`) VALUES (
			'$_REQUEST[vcq_vehicle_name]','$_SESSION[user]','$_REQUEST[vcq_start_time]','$vcq_end_time','$_REQUEST[vcq_description]');" ;
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