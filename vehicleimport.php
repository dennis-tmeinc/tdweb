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

		}
	}
	
	header( 'Location: settingsfleet.php' ) ;
	
?>