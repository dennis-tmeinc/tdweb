<?php
// emgload.php - load emergency event detail
// Request:
//      id: event index
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
		$sql = "SELECT * FROM emg_event WHERE `idx` = $_REQUEST[id] " ;
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$resp['tag'] = $row ;
				if( isset($row['Video_Files']) ){
					$resp['tag']['channels'] = new SimpleXMLElement( "<emg>" . $row['Video_Files'] . "</emg>" );
				}
				else if( isset($row['video_files']) ) {
					$resp['tag']['channels'] = new SimpleXMLElement( "<emg>" . $row['video_files'] . "</emg>" );
				}
				unset( $resp['tag']['Video_Files'] ) ;
				$resp['id'] = $_REQUEST['id'] ;
				$resp['res'] = 1 ;
			}
			$result->free();
		}
	}
	
	echo json_encode($resp);
?>