<?php
// drivebydeletereport.php - delete drive report and events
// Request:
//      tag: tag name (filename)
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-05-29
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {
		// only to mark event as un-processed
		$tagfile = $driveby_eventdir.'/'.$_REQUEST['tag'] ;
		$x = file_get_contents( $tagfile ) ;
		if( $x ) {
			$x = new SimpleXMLElement( $x );
			$x->status = "" ;
			$x->asXML($tagfile) ;
		}
		
		$resp['res'] = 1 ;
	}
	
	echo json_encode($resp);
?>