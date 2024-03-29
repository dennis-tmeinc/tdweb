<?php
// vltgetlocation.php - live track get dvr gps location
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
//      dvrid[]: dvr names
// Return:
//      JSON encoded avl response
// By Dennis Chen @ TME	 - 2013-11-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {

		$vltsession = session_id().'-'.$_REQUEST['vltpage'];

		if( empty($_REQUEST['vltserial'])) {
			$serialno = rand(101, 99999999) ;
		}
		else {
			$serialno = $_REQUEST['vltserial'] ;
			$resp['vltserial'] = $_REQUEST['vltserial'] ;
		}
		$cmd = '27' ;				// AVL_CURRENT_DATA_QUERY
		
		$xml = new SimpleXMLElement('<tdwebc></tdwebc>') ;
		// clientid support
		if( !empty( $_SESSION['clientid'] ) ) {
			$xml->company = $_SESSION['clientid'] ;
		}
		else {
			// no client support? may be use database name is a good idea?
			$xml->company = $smart_database ;  // only for testing
		}			

		if( empty($avlcbserver) ) {
			$avlcbserver = $_SERVER['REQUEST_SCHEME'] . "://". $_SERVER['HTTP_HOST'] . ":". $_SERVER['SERVER_PORT']; 
		}
		$xml->callbackurl = $avlcbserver . dirname($_SERVER['REQUEST_URI']).'/vltevent.php' ;
		$xml->session = $vltsession ;
		$xml->serialno = $serialno ;
		$xml->command=$cmd ;	
		if( !empty($_REQUEST['dvrid']) )
		foreach( $_REQUEST['dvrid'] as $value ) {
			$xml->target->dvrs->dvr[] = $value;
		}

		// $xml->avlp='' ;
		if( !empty($_REQUEST['obd']) ) {
			$xml->avlp->obd = dechex($_REQUEST['obd'])  ;
		}
		else {
			$xml->avlp->obd = 0  ;
		}
		
		@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );
		 
		if( empty($avlxml) ) {
			$resp['errormsg'] = "AVL Service error, no contents!" ;
			goto done ;
		}
		if ( !empty ($avl_log) ) {
			file_put_contents ( $avl_log , "REQ: " . $xml->asXML() . "\nANS: ". $avlxml. "\n", FILE_APPEND  );
		}

		@$tdwebc = new SimpleXMLElement($avlxml) ;
		
		if( empty($tdwebc->status) ) {
			$resp['errormsg'] = "AVL Service error!";
			goto done ;
		}
		
		$resp['status'] = (string)($tdwebc->status) ;
		if( $resp['status'][0] == 'E' || $resp['status'][0] == 'e' ) {
			// error
			if( empty( $tdwebc->errormsg ) ) {
				$resp['errormsg'] = "AVL response error!" ;
			}
			else {
				$resp['errormsg'] = $tdwebc->errormsg ;
			}
			goto done ;
		}
		
		if( strcasecmp( $resp['status'], 'OK' )==0 ) {
			if( !empty( $tdwebc->avlp ) ) {
			
				$resp['errormsg'] = "No error!";
				
				$resp['tdwebc'] = array();
				$resp['tdwebc'][] = $tdwebc ;
			}
			else {
				$resp['errormsg'] = "Empty data.";
			}			
		}
		else {
			$resp['errormsg'] = "Data pending.";
		}
		$resp['res'] = 1 ;
	}
	
done:	
	echo json_encode($resp);
?>

