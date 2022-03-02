<?php
// groupadd.php - add a new empty vehicle group
// Requests:
//      name: new group name
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		if( $_SESSION['user_type'] == "admin" ) {	// admin 
			$sql="INSERT INTO vgroup (name) VALUES ('".$_REQUEST['name']."');" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Add vehicle group failed!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>