<?php
// vltdvrlist.php - get vlt (live track) dvr (vehicle) list
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
// Return:
//      JSON array of vehicle list
// By Dennis Chen @ TME	 - 2014-02-21
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
		
		$mtime = time();

		// create vlt session file
		$fvlt = fopen( session_save_path().'/sess_vlt_'.$vltsession, "c" );
		if( $fvlt ) {
			flock( $fvlt, LOCK_EX ) ;		// exclusive lock

			$vlt = array();
			$vlt['run'] = 1 ;
			
			fwrite( $fvlt, json_encode($vlt) );
	
			ftruncate( $fvlt, ftell($fvlt) );
			fflush( $flvc ) ;              	// flush before release the lock
			flock( $fvlt, LOCK_UN ) ;		// unlock ;
			fclose( $fvlt );
		}
		
		$cmd = '23' ;				// AVL_DVR_LIST
		
		$xml = new SimpleXMLElement('<tdwebc></tdwebc>') ;

		// clientid support
		if( !empty( $_SESSION['clientid'] ) )
			$xml->company = $_SESSION['clientid'] ;
		
		$xml->callbackurl = $avlcbserver . dirname($_SERVER['REQUEST_URI']).'/' . $avlcbapp ;
		$xml->session = $vltsession ;
		$xml->serialno = $serialno ;
		$xml->target->avl='' ;
		$xml->command=$cmd ;	
		$xml->avlp->dvrlist='' ;
	
		// require dvr phone list
		$sql = "SELECT DISTINCT `vehicle_phone` FROM `vehicle` WHERE `vehicle_phone` <> '' ;";
		if( $result = $conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				$xml->avlp->dvrlist->dvritem[] = $row['vehicle_phone'] ;
			}
		}
		
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
				$resp['errormsg'] = "AVL error!" ;
			}
			else {
				$resp['errormsg'] = $tdwebc->errormsg ;
			}
			goto done ;
		}
	
		if( strcasecmp( $resp['status'], 'OK' )==0 ) {
			if( !empty( $tdwebc->avlp ) ) {
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
		
done:
			
	}

	echo json_encode($resp);
?>