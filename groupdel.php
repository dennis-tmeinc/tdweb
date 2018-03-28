<?php
// groupdel.php - delete vehicle group
// Requests:
//      name: group name
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		if( $_SESSION['user_type'] == "admin" ) {	// admin 
			// MySQL connection
			$name = $_REQUEST['name'] ;
			$sql="DELETE FROM vgroup WHERE name='".$name."';" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="vehicle group update failed!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>