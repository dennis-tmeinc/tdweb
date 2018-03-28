<?php
// zonerename.php - rename map zone
// Requests:
//      name: existed map zone name
//      newname: new zone name
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
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
			
		$sql="UPDATE zone SET `name` = '$esc_req[newname]' WHERE `name` = '$esc_req[name]' AND (`type` = 1 OR `user` = '$_SESSION[user]');" ;

		if( $conn->query($sql) ) {
			$resp['res']=$conn->affected_rows; 	// success
		}
		else {
			$resp['res']=0;
			$resp['errormsg']="SQL error: ".$conn->error ;
		}			
	}
	echo json_encode($resp);
?>