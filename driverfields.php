<?php
// driverfields.php - get driver fields information
// Requests:
// Return:
//      JSON array of driver fields information
// By Dennis Chen @ TME	 - 2013-07-19
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		$resp['fields']=array();
		
		$sql="DESCRIBE `driver`;" ;
		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array() ) {
				$f = sscanf($row['Type'],"varchar(%d)");
				if( !empty($f[0])) {
					$resp['fields'][] = array( 'name' => $row['Field'] , 'maxlength' => $f[0] );
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