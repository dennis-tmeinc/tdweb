<?php
// avlsave.php - save avl parameter 
// Requests:
//      avlServer: avl server address
//      avlPassword: avl password, if not empty
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
				
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
			
			// escaped string for SQL
			$esc_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$esc_req[$key]=$conn->escape_string($value);
			}
			
			if( empty( $_REQUEST['avlPassword'] ) ) {
				$sql="UPDATE tdconfig SET avlServer = '$esc_req[avlServer]' ;" ;
			}
			else {
				$sql="UPDATE tdconfig SET avlServer = '$esc_req[avlServer]', avlPassword = '$esc_req[avlPassword]' ;" ;
			}
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