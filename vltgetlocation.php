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
		if( !empty( $_SESSION['clientid'] ) )
			$xml->company = $_SESSION['clientid'] ;

		if( empty($avlcbserver) ) {
			$avlcbserver = $_SERVER['REQUEST_SCHEME'] . "://". $_SERVER['HTTP_HOST'] . ":". $_SERVER['SERVER_PORT']; 
		}
		$xml->callbackurl = $avlcbserver . dirname($_SERVER['REQUEST_URI']).'/vltevent.php' ;
		$xml->session = $vltsession ;
		$xml->serialno = $serialno ;
		$xml->command=$cmd ;	
		foreach( $_REQUEST['dvrid'] as $value ) {
			$xml->target->dvrs->dvr[] = $value;
		}
		$xml->avlp='' ;
		
		/*
	        OBD filter: starting from bit 0 
			OBD_RPM, /* 0 - 8031 */
			OBD_SPEED, /* 0 - 250km/h */
			OBD_TRIP, /* 0 - 526,385,151 */
			OBD_TOTAL, /* 0 - 526,385,151 */
			OBD_FUEL_LEVEL, /* 0 to 100 % */
			OBD_OIL_LEVEL, /* 0 - 100 % */
			OBD_COOLANT_TEMP, /* -40 - 210 */
			OBD_OIL_TEMP, /* -273 to 1735 deg */
			OBD_FUEL_TEMP, /* -40 - 210 */
			OBD_GEAR, /* -125 to +125 */
			OBD_PARK, /* (00 - not set, 01 - set) */
			OBD_BRAKE, /* 0 to 32,127.5 kW */
			OBD_ACCEL, /* 0 - 100% */
			OBD_ENGINE_LOAD, /* 0 - 125% */
			OBD_ENGINE_TORQUE, /* 0 - 125% */
			OBD_IDLING_STATE, /* 0 - no idling, 1 - idling */
			OBD_STEERING_ANGLE, /* -31.374 to +31.374 */
			OBD_DOOR, /* (00 - closed, 01 - opened) */
			OBD_SEATBELT, /* 0 - not buckled, 1 - bucked */
			OBD_TURN_ANGLE, /* never happens. -125 to 125 deg */
			OBD_RIGHT, /*(00 - deactivated, 01 - activated) */
			OBD_LEFT, /*(00 - deactivated, 01 - activated) */
			OBD_ENGINE_STATE,
			 /*               0000 - stopped,  0001 - pre-start
			 *                0010 - starting, 0011 - warm-up
			 *                0100 - running,  0101 - cool-down
			 *                0110 - stopping, 0111 - post-runevery 300s
			 */
			OBD_BATTERY, /* 0 to 3212750 mV */
		*/
		// $xml->avlp->obd = 'ffffffff' ;
		
		@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );
		 
		if( empty($avlxml) ) {
			$resp['errormsg'] = "AVL Service error, no contents!" ;
			goto done ;
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

