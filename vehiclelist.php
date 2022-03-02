<?php
// vehiclelist.php - get vehicle list
// Requests:
//      vehicle_id: to provide vehicle info of this vehicle
//      nameonly=y: to provide list of vehicle names only
// Return:
//      JSON array of vehicle list
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		if( !empty($_REQUEST['vehicle_id']) ) {
			$vehicle_id = $conn->escape_string( $_REQUEST['vehicle_id'] ) ;
			$sql="SELECT * FROM vehicle WHERE vehicle_id = $vehicle_id " ;
		}
		else if( !empty($_REQUEST['vehicle_name']) ) {
			$vehicle_name = $conn->escape_string( $_REQUEST['vehicle_name'] ) ;
			$sql="SELECT * FROM vehicle WHERE vehicle_name = '$vehicle_name';";
		}
		else if( !empty($_REQUEST['nameonly']) && $_REQUEST['nameonly'] == "y" ) {
			$sql="SELECT vehicle_name FROM vehicle ORDER BY vehicle_name ;" ;
		}
		else {
			$sql="SELECT * FROM vehicle ORDER BY vehicle_name ;" ;
		}
		if($result=$conn->query($sql)) {
			$vehiclelist = array();
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$vehiclelist[]=$row;
			}
			$resp['vehiclelist'] = $vehiclelist;
			$resp['res'] = 1 ;
			$result->free();
		}
	}
	echo json_encode($resp);
?>