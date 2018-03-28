<?php
// drivebytagload.php - load drive by event detail
// Request:
//      id: tag event index
//
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-07-16
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require 'vfile.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {
		$sql = "SELECT * FROM Drive_By_Event WHERE `idx` = $_REQUEST[id] " ;
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$resp['tag'] = $row ;
				$resp['tag']['channels'] = new SimpleXMLElement( "<driveby>" . $row['Video_Files'] . "</driveby>" );
				unset( $resp['tag']['Video_Files'] ) ;
				$resp['id'] = $_REQUEST[id] ;
				$resp['res'] = 1 ;
			}
			$result->free();
		}
	}
	
	echo json_encode($resp);
?>