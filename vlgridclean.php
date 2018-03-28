<?php
// vlgridclean.php -  clean all temporay tables used by vlgrid
// Requests:
//      none
// Return:
//      JSON array of map events
// By Dennis Chen @ TME	 - 2013-05-17
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		// get total records
		unset( $_SESSION['mapfilter'] );
		$vlgrid_table = 'tmp%';
		$sql = "SHOW TABLES LIKE '$vlgrid_table' ";
		if( $result = $conn->query($sql) ) {
			$tables = $result->fetch_all();
			$result->free();
			for( $i=0; $i<count($tables); $i++) {
				$droptable = $tables[$i][0] ;
				$sql = "DROP TABLE $droptable ;";
				$conn->query($sql);
			}
		}
		
		$resp['res']=1 ;
	}
	echo json_encode( $resp );
?>