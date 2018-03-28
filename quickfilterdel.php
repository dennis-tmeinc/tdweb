<?php
// quickfilterdel.php - delete one quick filter
// Requests:
//      name: quick filter name
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ){
		// MySQL connection
		$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql="DELETE FROM `quickfilter` WHERE `name` = '$_REQUEST[name]' ;" ;
		if( $conn->query($sql) ) {
			$resp['res']=1 ;	// success
		}
		else {
			$resp['errormsg']="SQL error: ".$conn->error ;
		}			
	}
	echo json_encode($resp);
?>