<?php
// driverdel.php - delete driver info
// Requests:
//      driver_id: 
//      alldriver: yes (to delete all drivers, admin only)
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' && !empty($_REQUEST['alldriver']) && $_REQUEST['alldriver'] == 'yes' ) {
			$sql="DELETE FROM `driver` WHERE TRUE ;" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Delete vehicle failed!";
			}
		}
		else if( $_SESSION['user_type'] == "admin" ) {	// admin 
			// MySQL connection
			$driver_id = $_REQUEST['driver_id'] ;
			$sql="DELETE FROM driver WHERE driver_id='$driver_id';" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Delete driver failed!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>