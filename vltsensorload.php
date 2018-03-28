<?php
// vltsensorload.php - load vlt sensor name settings
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-11-12
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$sql="SELECT * FROM dvr_sensor ;";
		if( $result=$conn->query($sql) ) {
			$resp['vltsensor'] = array();
			while( $row = $result->fetch_assoc() ){
				$resp['vltsensor'][]=$row ;
			}
			$resp['res']=1 ;
		}
	}	
	echo json_encode( $resp );
?>