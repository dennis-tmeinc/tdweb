<?php
// eventparametersave.php - save default event parameter 
// Requests:
//      event parameter
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
			// MySQL connection
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql="UPDATE report_parameter SET " .
				" speed =".$_REQUEST['speed'].
				",stop_duration =".$_REQUEST['stop_duration'].
				",idle_duration =".$_REQUEST['idle_duration'].
				",bstop_duration =".$_REQUEST['bstop_duration'].
				",park_duration =".$_REQUEST['park_duration'].
				",racing_start =".$_REQUEST['racing_start'].
				",hard_brake =".$_REQUEST['hard_brake'].
				",rear_impact =".$_REQUEST['rear_impact'].
				",front_impact =".$_REQUEST['front_impact'].
				",hard_turn =".$_REQUEST['hard_turn'].
				",side_impact =".$_REQUEST['side_impact'].
				",bumpy_ride =".$_REQUEST['bumpy_ride'].
				",maxuploadtime =".$_REQUEST['maxuploadtime'].
				",maxconcurrentupload =".$_REQUEST['maxconcurrentupload'].
				 " ;" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}			
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>