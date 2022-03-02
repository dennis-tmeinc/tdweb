<?php
// zonesave.php - save (update) map zone
// Requests:
//      name : zone name
//      top, left, bottom, right: zone area
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
		
		$sql="UPDATE zone SET ".
			"`top` =$esc_req[top],".
			"`left` =$esc_req[left],".
			"`bottom` =$esc_req[bottom],".
			"`right` =$esc_req[right] ".
			" WHERE `name` = '$esc_req[name]' AND (`type` = 1 OR `user` = '$_SESSION[user]');" ;

		$resp['sql']=$sql;
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