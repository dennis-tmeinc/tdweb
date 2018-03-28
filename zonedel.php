<?php
// zonedel.php - delete one map zone
// Requests:
//      name: map zone name to delete
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-13
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		// secaped sql values
		$esc_req=array();		
		foreach( $_REQUEST as $key => $value ){
			$esc_req[$key]=$conn->escape_string($value);
		}
		
		$query="DELETE FROM zone WHERE `name` = '$esc_req[name]' AND (`type` = 1 OR `user` = '$_SESSION[user]');" ;
		if( $esc_req['name'] == "Default Area" ) {
			$resp['res']=0;
			$resp['errormsg']="Not allowed!" ;
		}
		else if( $conn->query($query) ) {
			$resp['res']=1 ;	// success
		}
		else {
			$resp['res']=0;
			$resp['errormsg']="SQL error: ".$conn->error;
		}
		
	}
	echo json_encode($resp);
?>