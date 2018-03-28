<?php
// vehiclefields.php - get vehicle fields information
// Requests:
// Return:
//      JSON array of vehicle fields information
// By Dennis Chen @ TME	 - 2013-07-10
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		$maxuploadtime=60 ;
		$sql="SELECT `maxuploadtime` FROM `report_parameter` ;";
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array() ) {
				$maxuploadtime=$row[0] ;
			}
			$result->free();
		}
		
		$resp['fields']=array();
		
		$sql="DESCRIBE `vehicle`;" ;
		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array() ) {
				if( $row['Field'] == 'vehicle_max_upload_time' ) {
					$resp['fields'][] = array( 'name' => $row['Field'] , 'defvalue' => $maxuploadtime );
				}
				else {
					$f = sscanf($row['Type'],"varchar(%d)");
					if( !empty($f[0])) {
						$resp['fields'][] = array( 'name' => $row['Field'] , 'maxlength' => $f[0] );
					}
				}
			}
			$resp['res'] = 1 ;
			$result->free();
		}
		else {
			$resp['errormsg']="SQL ERROR!" ;
		}
	}
	echo json_encode($resp);
?>