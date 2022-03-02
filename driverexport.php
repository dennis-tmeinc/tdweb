<?php
// driverexport.php - export driver lists
// Requests:
//      ( admin only )
// Return:
//      CSV file
// By Dennis Chen @ TME	 - 2013-07-05
// Copyright 2013 Toronto MicroElectronics Inc.

	require 'session.php' ;
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' ) {
			header( "Content-Type: application/octet-stream" );
			header( "Content-Disposition: attachment; filename=driver.csv" );   	
			
			$output = fopen('php://output', 'w');
			
			$sql="SELECT * FROM `driver`;";
			if( $result = $conn->query($sql,MYSQLI_USE_RESULT) ) {
				$skip_auto_inc = false ;
				$fields = $result->fetch_fields();
				if( empty($backup_auto_increment) && !empty($fields) ){
					if($fields[0]->flags&MYSQLI_AUTO_INCREMENT_FLAG)
						$skip_auto_inc = true ;
				}
				
				// headers 
				$line=array();
				for( $i=($skip_auto_inc?1:0) ; $i<count($fields) ; $i++ ) {
					$line[]=$fields[$i]->name;
				}
				fputcsv ( $output , $line );
				
				// contents
				while( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$line=array();
					for( $i=($skip_auto_inc?1:0) ; $i<count($row) ; $i++ ) {
						$line[]=$row[$i];
					}
					fputcsv ( $output , $line );
				}
				$result->free();
			}			

		}
	}
?>