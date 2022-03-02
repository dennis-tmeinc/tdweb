<?php
// vltunload.php - unload vlt session
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
// Return:
//      JSON array of vehicle list
// By Dennis Chen @ TME	 - 2013-11-18
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	ignore_user_abort();
	
	if( $logon ) {

		// success
		$resp['res'] = 1 ;
		echo json_encode($resp);

		$vltsession = session_id().'-'.$_REQUEST['vltpage'];
	
		$xml = new SimpleXMLElement('<tdwebc></tdwebc>') ;
		// clientid support
		if( !empty( $_SESSION['clientid'] ) ) {
			$xml->company = $_SESSION['clientid'] ;
		}
		else {
			// no client support? may be use database name is a good idea?
			$xml->company = $smart_database ;  // only for testing
		}			
		
		$xml->session = $vltsession ;
		@$xml->serialno = $_REQUEST['vltserial'] ;
		$xml->target->avl='' ;
		$xml->avlp='' ;
		$xml->command='9999' ; 		// AVL_AUTO_REPORT_CONF(26)
		@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );	
		if ( !empty ($avl_log) ) {
			file_put_contents ( $avl_log , "REQ: " . $xml->asXML() . "\nANS: ". $avlxml. "\n", FILE_APPEND  );
		}

		// clear vlt session file
		$fvltname = session_save_path().'/sess_vlt_'.$vltsession ;
		$fvlt = fopen( $fvltname, "r+" );
		if( $fvlt ) {
			flock( $fvlt, LOCK_EX ) ;		// exclusive lock

			fwrite( $fvlt, "{}" );			// empty class
	
			ftruncate( $fvlt, ftell($fvlt) );
			fflush( $fvlt ) ;              	// flush before release the lock
			flock( $fvlt, LOCK_UN ) ;		// unlock ;
			fclose( $fvlt );
		}
		
		// remove vlt file if possible !!!
		@unlink( $fvltname );
	}
	else {
		echo json_encode($resp);
	}
?>