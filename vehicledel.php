<?php
// vehicledel.php - delete one vehicle
// Requests:
//      vehicle_name: vehicle name to delete
//      allvehicle: 'yes' to delte all vehicles (only for admin)
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-07-05
// Copyright 2013 Toronto MicroElectronics Inc.
    
	require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' && !empty($_REQUEST['allvehicle']) && $_REQUEST['allvehicle'] == 'yes' ) {
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql="DELETE FROM `vehicle` WHERE TRUE ;" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Delete vehicle failed!";
			}
		}
		else if( $_SESSION['user_type'] == "admin" ) {	// admin 
			// MySQL connection
			$vehicle_name = $_REQUEST['vehicle_name'] ;
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql="DELETE FROM vehicle WHERE vehicle_name='$vehicle_name';" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Delete vehicle failed!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>