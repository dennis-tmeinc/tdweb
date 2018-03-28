<?php
// vehiclesearch.php - search vehicles from table 'vl', 'videoclip', 'td_alert' and 'dvr_event'
// Requests:
//		( only for admin )
// Return:
//      JSON array of vehicle list
// By Dennis Chen @ TME	 - 2013-07-05
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' ) {
			
			$sql = "INSERT IGNORE INTO `vehicle` (`vehicle_name`) SELECT DISTINCT `vl_vehicle_name` from `vl` " ;
			$conn->query($sql);
			$sql = "INSERT IGNORE INTO `vehicle` (`vehicle_name`) SELECT DISTINCT `vehicle_name` from `videoclip` " ;
			$conn->query($sql);
			$sql = "INSERT IGNORE INTO `vehicle` (`vehicle_name`) SELECT DISTINCT `dvr_name` from `td_alert` " ;
			$conn->query($sql);
			$sql = "INSERT IGNORE INTO `vehicle` (`vehicle_name`) SELECT DISTINCT `de_vehicle_name` from `dvr_event` " ;
			$conn->query($sql);
			$resp['res']=1 ;	// success
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>