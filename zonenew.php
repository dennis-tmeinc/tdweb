<?php
// zonenew.php - create a new map zone
// Requests:
//      name : new zone name
//      zonetype: zone type, 1: public, 2: private
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-13
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		// MySQL connection
		$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
				
		// secaped sql values
		$esc_req=array();		
		foreach( $_REQUEST as $key => $value ){
			$esc_req[$key]=$conn->escape_string($value);
		}

		$sql="INSERT INTO zone (`name`, `type`, `user` ) VALUES ( '$esc_req[name]', '$esc_req[zonetype]', '$_SESSION[user]' ); " ;
		if( $conn->query($sql) ) {
			$resp['res']=$conn->affected_rows; 	// success
		}
		else {
			$resp['errormsg']="SQL error: ".$conn->error ;
		}	
	}
	echo json_encode($resp);
?>