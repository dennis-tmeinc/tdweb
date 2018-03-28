<?php
// driverlist.php - Get full driver list
// Requests:
//      driver_id : optional driver id to query
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2013-06-24
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ){
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		if( !empty($_REQUEST['driver_id']) ) {
			$sql="SELECT * FROM driver WHERE driver_id = $_REQUEST[driver_id];";
		}
		else {
			$sql="SELECT * FROM driver ;" ;
		}
		if($result=$conn->query($sql)) {
			$resp['driverlist'] = array(); 
			while( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
				$resp['driverlist'][]=$row;
			}
			$result->free();
			$resp['res']=1 ; //success
		}
	}
	echo json_encode( $resp )
?>