<?php
// drivebytaglist.php - get drive by tag file list
// Request:
//      status: new|processed|deleted|trash
//      report: ok|deleted|trash
//      vehicle_name : 
//      time_start :
//      channel :
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-01-10
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//
// 
//
    require 'session.php' ;
	require_once 'vfile.php' ;
	
	header("Content-Type: application/json");
	
	function readtag( $tagfile )
	{
		$v = file_get_contents( $tagfile );
		if( $v ) {
			$x = new SimpleXMLElement( $v );
			// recover empty fields
			if( empty($x->status) ) $x->status = 'new' ;
			if( empty($x->report) ) $x->report = 'ok' ;
			if( empty($x->plateofviolator) ) $x->plateofviolator = '' ;
			if( empty($x->notes) ) $x->notes = '' ;
			if( empty($x->imgquality) ) $x->imgquality = 'Good' ;
			if( empty($x->displaystatus) ) $x->displaystatus = 'Pending' ;
			if( empty($x->processedby) ) $x->processedby = '' ;
			if( empty($x->processedtime) ) $x->processedtime = '' ;
			if( empty($x->sentto) ) $x->sentto = '' ;
			if( empty($x->event_state) ) $x->event_state = '' ;
			if( empty($x->event_city) ) $x->event_city = '' ;
			if( empty($x->event_deleteby) ) $x->event_deleteby = '' ;
			if( empty($x->event_deletetime) ) $x->event_deletetime = '' ;
			if( empty($x->report_deleteby) ) $x->report_deleteby = '' ;
			if( empty($x->report_deletetime) ) $x->report_deletetime = '' ;
			
			$tag = array();
			$tag['tagname'] = basename($tagfile);
			$tag['clientid'] = trim($x->clientid) ;
			$tag['vehicle'] = trim($x->busid) ;
			$tag['datetime'] = trim($x->time) ;
			$tag['sensors'] = trim($x->sensors) ;
			
			$tag['status'] = trim($x->status) ;
			$tag['plateofviolator'] = trim($x->plateofviolator) ;
			$tag['eventstatus'] = trim($x->status) ;
			$tag['reportstatus'] = trim($x->report) ;
			$tag['notes'] = trim($x->notes) ;		
			$tag['imgquality'] = trim($x->imgquality) ;		
			$tag['displaystatus'] = trim($x->displaystatus) ;		
			$tag['processedby'] = trim($x->processedby) ;		
			$tag['processedtime'] = trim($x->processedtime) ;		
			$tag['sentto'] = trim($x->sentto) ;		
			$tag['event_state'] = trim($x->event_state) ;		
			$tag['event_city'] = trim($x->event_city) ;		
			$tag['event_deleteby'] = trim($x->event_deleteby) ;		
			$tag['event_deletetime'] = trim($x->event_deletetime) ;		
			$tag['report_deleteby'] = trim($x->report_deleteby) ;		
			$tag['report_deletetime'] = trim($x->report_deletetime) ;		
			
			unset($x);
						
			return $tag ;
		}
		return false ;
	}
			
	if( $logon ) {
			
		if( $_REQUEST['status'] == 'new' ) {
			// try make this directory
			if( !is_dir($driveby_eventdir) )
				@mkdir( $driveby_eventdir );
			// to convert data from data base to local file
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql = "SELECT * FROM Drive_By_Event" ;
			if($result=$conn->query($sql)) {
				while( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
					set_time_limit(30);
					// create on tag file for each raw ;
					$driveby = new SimpleXMLElement( "<driveby>" . $row['Video_Files'] . "</driveby>" );
					$driveby->clientid = $row['Client_Id'] ;
					$driveby->busid = $row['Bus_Id'] ;
					$driveby->time = $row['Date_Time'] ;
					$driveby->coordinate = $row['Lat'].','.$row['Lon'] ;
					$driveby->sensors = $row['Sensor_Status'] ;
			 		$driveby->status = 'new' ;				// new/processed/deleted/trash
					$driveby->report = '0' ;				// nil/ok/deleted/trash
					
					$driveby->plateofviolator = '' ;		
					$driveby->notes = '' ;		
						
					// new fields for all tabs
					$driveby->imgquality = '' ;				// Image quality: Good/Poor/Bad
					$driveby->displaystatus = '' ;	//  Pending/Sent, initial value: Pending 
					$driveby->processedby = '' ;				//  User id, initial value: empty 
					$driveby->processedtime = '' ;			//  
					$driveby->sentto = '' ;					//  email recipients,   
					$driveby->event_state = '' ;			//  State/Province of event location, assigned when processing 
					$driveby->event_city = '' ;				//  city/town of event location, assigned when processing 
					$driveby->event_deleteby = '' ;			//  user id who delete the event, show on "Deleted Events" tab 
					$driveby->report_deleteby = '' ;		//  user id who delete the reports, show on "Deleted Reports" tab
					
					// save tag (xml) file
					$fn = $driveby_eventdir."/". hash("md5", $driveby->clientid . $driveby->busid . $driveby->time ). ".tag" ;
					if( !file_exists( $fn ) ){
						file_put_contents( $fn, $driveby->asXML() );
					}
					
					// indicate record as imported!
					// $importedid = "imp-" . $row[ 'Client_Id' ] ;
					// $sql = "UPDATE Drive_By_Event SET Client_Id = '$importedid' WHERE `idx` = '$row[idx]' " ;
					// $conn->query($sql);
					
				}
				$result->free();
			}
		}
	
		$resp['tags'] = array();
		
		foreach ( glob($driveby_eventdir.'/*.tag' ) as $filename) {
			set_time_limit(30);
			$tag = readtag( $filename ) ;
			if( $tag ) {
				if( !empty( $_REQUEST['status'] ) ) {	// get list base on status
					if( $tag['status'] == $_REQUEST['status'] ) {
						$resp['tags'][] = $tag ;
					}
				}
				else if( !empty( $_REQUEST['report'] ) ) {	// get list base on report status
					$pdffile = substr( $filename, 0, strpos( $filename, '.' ) ).".pdf" ;
					if( file_exists( $pdffile ) && $tag['reportstatus'] == $_REQUEST['report'] ) {
						$resp['tags'][] = $tag ;
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