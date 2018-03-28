<?php
// quickfilterdel.php - delete one quick filter
// Requests:
//      name: quick filter name
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-11-14
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ){
		// MySQL connection
		$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		// escaped string for SQL
		$esc_req=array();
		foreach( $_REQUEST as $key => $value )
		{
			$esc_req[$key]=$conn->escape_string($value);
		}		
		if( $_SESSION['user_type'] == 'admin' ) {
			$sql="DELETE FROM `quickfilter` WHERE `name` = '$esc_req[name]' ;" ;
		}
		else {
			$sql="DELETE FROM `quickfilter` WHERE `name` = '$esc_req[name]' AND `user` = '$_SESSION[user]';" ;
		}
		if( $conn->query($sql) ) {
			$resp['res']=1 ;	// success
		}
		else {
			$resp['errormsg'] = "Not allowed." ;
			//$resp['errormsg']="SQL error: ".$conn->error ;
		}			
	}
	echo json_encode($resp);
?>