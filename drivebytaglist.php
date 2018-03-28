<?php
// drivebytaglist.php - get drive by tag file list
// Request:
//      process:  0=unprocessed, 1=processed
//      vehicle_name : 
//      time_start :
//      channel :
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-01-10
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require_once 'vfile.php' ;
	
	header("Content-Type: application/json");
			
	if( $logon ) {
		$resp['tags'] = array();
		foreach (vfile_glob($driveby_eventdir.'/*.evn' ) as $filename) {
			$v = vfile_get_contents( $filename );
			if( $v ) {
				$x = new SimpleXMLElement( $v );
				if( $x->busid ) {
					$tag = array();
					$tag['tagname'] = basename($filename);
					$tag['clientid'] = trim($x->clientid) ;
					$tag['vehicle'] = trim($x->busid) ;
					$tag['datetime'] = trim($x->time) ;
					$resp['tags'][] = $tag ;
				}
			}
		}
		
		if( !empty( $resp['tags'] ) ) {
			$resp['res'] = 1 ;
		}

	}
	echo json_encode($resp);
?>