<?php
// driverimport.php - import driver lists from CSV file
// Requests:
//      ( only for admin )
// Return:
//      Redirect to settingfleet page
// By Dennis Chen @ TME	 - 2013-07-05
// Copyright 2013 Toronto MicroElectronics Inc.

	require 'session.php' ;
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' ) {
			// MySQL connection
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );			

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
				$sql = 'INSERT IGNORE INTO `driver` (' .implode(',', $fields). ') VALUES ('. implode(',', $row). ');';
				$conn->query($sql);
			}

		}
	}
	
	header( 'Location: settingsfleet.php' ) ;
	
?>