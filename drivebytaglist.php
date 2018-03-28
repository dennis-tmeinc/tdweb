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
				
		// try make this directory
		if( !is_dir($driveby_eventdir) )
			@mkdir( $driveby_eventdir );
			
		if( empty( $_REQUEST['process'] ) ) {
			// to convert data from data base to local file
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql = "SELECT * FROM Drive_By_Event" ;
			if($result=$conn->query($sql)) {
				while( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
					// create on tag file for each raw ;
					$driveby = new SimpleXMLElement( "<driveby>" . $row['Video_Files'] . "</driveby>" );
					$driveby->clientid = $row['Client_Id'] ;
					$driveby->busid = $row['Bus_Id'] ;
					$driveby->time = $row['Date_Time'] ;
					$driveby->coordinate = $row['Lat'].','.$row['Lon'] ;
					$driveby->sensors = $row['Sensor_Status'] ;
					
					// save tag (xml) file
					$fn = $driveby_eventdir."/". hash("md5", $driveby->clientid . $driveby->busid . $driveby->time ). ".tag" ;
					if( !file_exists( $fn ) ){
						file_put_contents( $fn, $driveby->asXML() );
					}
					
				}
				$result->free();
			}
		}
	
		$resp['tags'] = array();
		$resp['tagnumber'] = 0 ;
		$resp['tagnumberp'] = 0 ;
		$resp['tagnumbern'] = 0 ;
		
		foreach ( glob($driveby_eventdir.'/*.tag' ) as $filename) {
			$v = file_get_contents( $filename );
			if( $v ) {
				$x = new SimpleXMLElement( $v );
				if( empty( $_REQUEST['process'] ) ) {	// get unprocessed list
				
					// use all listing for demo
					
					$tag = array();
					$tag['tagname'] = basename($filename);
					$tag['clientid'] = trim($x->clientid) ;
					$tag['vehicle'] = trim($x->busid) ;
					$tag['datetime'] = trim($x->time) ;
					$resp['tags'][] = $tag ;
				}
				else {	// processed list
				
					$resp['tagnumber'] ++ ;
					
					if( substr( basename($filename),0,4 ) == "fb42" ) {
							$resp['tagnumberp'] ++ ;
							$resp['fb'] = $x ;
					}

					if( !empty( $x->status ) ) {
					

						$tag = array();
						$tag['tagname'] = basename($filename);
						$tag['clientid'] = trim($x->clientid) ;
						$tag['vehicle'] = trim($x->busid) ;
						$tag['datetime'] = trim($x->time) ;
						$tag['plate'] = trim($x->plateofviolator) ;
						$tag['status'] = trim($x->status) ;
						$resp['tags'][] = $tag ;
					}
					else {
							$resp['tagnumbern'] ++ ;

					}
				}
			}
		}
		
		if( !empty( $resp['tags'] ) ) {
			$resp['res'] = 1 ;
		}

	}
	echo json_encode($resp);
?>