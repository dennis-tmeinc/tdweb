<?php
// vehicleimport.php - import vehicle lists from CSV file
// Requests:
//      ( only for admin)
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-07-05
// Copyright 2013 Toronto MicroElectronics Inc.

	require 'session.php' ;
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' ) {

			// uploaded file
			$input = fopen( $_FILES['importfile']['tmp_name'] , "r" );
		
			$fields = fgetcsv( $input ) ;
			// quote fields name
			for( $i=0; $i<count($fields); $i++) {
				$fields[$i] = '`'.trim($fields[$i]).'`' ;
			}
			
			while( $row = fgetcsv( $input ) ) {
				for( $i=0; $i<count($row); $i++) {
					$row[$i] = "'". $conn->escape_string( $row[$i] )."'" ;				
				}
				$sql = 'INSERT IGNORE INTO `vehicle` (' .implode(',', $fields). ') VALUES ('. implode(',', $row). ');';
				$conn->query($sql);
			}

			// register all ivuid, asked by @tongrui 2022-12
			if( !empty($td_ivu_setup) && !empty($_SESSION['clientid']) ) {
				$sql = 'SELECT vehicle_ivuid FROM vehicle;';
				if($result=$conn->query($sql)) {
					while( $row=$result->fetch_array() ) {
						if(!empty($row[0])){
							$p1 = escapeshellarg( $row[0] );
							$p2 = escapeshellarg( $_SESSION['clientid'] );
							exec($td_ivu_setup." $p1 $p2");
						}
					}
				}
			}
		}
	}
	
	header( 'Location: settingsfleet.php' ) ;
	
?>