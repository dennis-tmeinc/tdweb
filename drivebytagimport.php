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

			// to convert data from data base to local file
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			
			$resp['t']=0 ;
			$resp['x']=array();
			
			foreach ( glob($driveby_eventdir.'/*.pdf' ) as $pdfname) {
				set_time_limit(30);
				$tag = readtag( substr( $pdfname, 0, -3 )."tag" ) ;
				if( $tag ) {
					$pdfname=basename($pdfname);
					$sql = "UPDATE Drive_By_Event SET `event_status` =  'processed', `report_status` =  'report', `report_file` = '$pdfname', `event_processedby` =  '$_SESSION[user]', `event_processedtime` =  NOW(), Plateofviolator = '$tag[plateofviolator]', `notes` = '$tag[notes]', `email_status` = 'Pending' WHERE `Date_Time` = '$tag[datetime]' AND `Bus_Id` = '$tag[vehicle]' AND Client_Id = '$tag[clientid]' " ;

					$resp['x'][] = $sql ;
				
					
					$conn->query($sql);
				}
			}

	}
	echo json_encode($resp);
?>