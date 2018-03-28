<?php
// groupsave.php - save one vehicle group info
// Requests:
//      oname: original group name (if exist)
//      name:  new group name
//      vehiclelist: list of vehicles update to this group
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		if( $_SESSION['user_type'] == "admin" ) {	// admin 
			// MySQL connection
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			@$oname=$_REQUEST['oname'] ;
			$name=$_REQUEST['name'] ;
			$vehiclelist=$_REQUEST['vehiclelist'] ;
			if( $oname ) {	// update
				$sql="UPDATE vgroup SET name='$name', vehiclelist='$vehiclelist' WHERE name='$oname';" ;
			}
			else {			// new
				$sql="INSERT INTO vgroup (name, vehiclelist) VALUES ('$name', '$vehiclelist');";
			}
			
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