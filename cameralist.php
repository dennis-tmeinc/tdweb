<?php
// cameralist.php - list all existing camera numbers
// Request:
//      vehicle: vehicle name
// Return:
//      json array
// By Dennis Chen @ TME	 - 2018-02-21
// Copyright 2018 Toronto MicroElectronics Inc.
//

require 'session.php' ;
header("Content-Type: application/json");
			
if( $logon ) {
	$vehicle = $conn->escape_string( $_REQUEST['vehicle'] );
	$sql = "SELECT distinct `channel` FROM `videoclip` WHERE `vehicle_name` = '$vehicle' order by `channel`" ;
	if( $result=$conn->query($sql) ) {
		$resp['res'] = 1 ;
		$resp['cameras'] = array();
		while( $raw = $result->fetch_array() ) {
			$resp['cameras'][] = $raw['channel'] ;
		}
	}
}
	
echo json_encode($resp);
?>