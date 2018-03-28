<?php
// vltsensorsave.php - save avl sensor name settings
// Requests:
//      [key of sensor_index]=[value of sensor_name] 
//      ex:
//          Sensor+1+Low=s1+low&Sensor+1+High=s1+hi
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-11-12
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {

		foreach( $_REQUEST as $key => $value )
		{
			$esc_value = $conn->escape_string($value);
			$rkey = str_replace('_', ' ', $key);
			$sql = "UPDATE dvr_sensor SET sensor_name = '$esc_value' WHERE sensor_index = '$rkey' ; ";
			if( $conn->query($sql) ) {
				$resp['res'] |= 1 ;		// success
			}			
		}
	}	
	echo json_encode($resp);
?>