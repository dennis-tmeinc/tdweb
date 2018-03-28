<?php
// vltevent.php - AVL service call back
// Requests:
//      xml: xml document of AVL protocol
// Return:
//      xml document composed of AVL protocol
// By Dennis Chen @ TME	 - 2014-04-08
// Copyright 2013 Toronto MicroElectronics Inc.

header("Content-Type: application/xml");

$xmlresp = new SimpleXMLElement('<tdwebc><status>Error</status></tdwebc>') ;

if( empty($_REQUEST['xml']) ) {
	$xmlresp->status="ErrorNoXML" ;
	$xmlresp->errormsg='No XML parameter!' ;
	goto done ;
}

@$tdwebc = new SimpleXMLElement($_REQUEST['xml']) ;

if( empty( $tdwebc->session ) ) {
	$xmlresp->status='ErrorXML' ;
	$xmlresp->errormsg='XML document error or session error';
	goto done ;
}

$vltsession = $tdwebc->session ;
$xmlresp->session=$vltsession ;
$ss = explode('-', $vltsession);

$session_id = $ss[0] ;
$noredir = 1 ;
require_once 'session.php' ;

if( !$logon ) {
	// session wrong
	$xmlresp->status='ErrorSessionEnd' ;
	$xmlresp->errormsg='Session not exist or expired.' ;
	goto done ;
}

@$vltcommand = $tdwebc->command ;

@$vltserialno = $tdwebc->serialno ;
if( empty($vltserialno) ) 
	$vltserialno = '' ;		// no serial (generic)
else 
 	$xmlresp->serialno = $tdwebc->serialno ;

$mtime = time();	

if( empty($vltcommand) || $vltcommand < 1 || $vltcommand >200  ) {		// No command ?
   	$xmlresp->status='ErrorCommand' ;
	$xmlresp->errormsg='Wrong command value.' ;
	goto done ;
}
$xmlresp->command=$vltcommand ;

if( empty( $tdwebc->ack ) ) {
	// an event ?
	$xmlresp->ack = '2' ;
	$xmlresp->reason = '0' ;
}

// append this message to table
if( !empty( $tdwebc->avlp ) ) {

	// look for vlt event listener
	$fvlt = fopen( session_save_path().'/sess_vlt_'.$vltsession, "r+" );
	if( $fvlt ) {
		flock( $fvlt, LOCK_EX ) ;		// exclusive lock
		
		@$vlt = json_decode( fread( $fvlt, 256000 ), true );
		
		if( empty( $vlt['run'] ) ) {
			// session wrong
			$xmlresp->status='ErrorSessionEnd' ;
			$xmlresp->errormsg='Session not exist or expired.' ;
		}
		else {
			if( empty( $vlt['events'] ) ) {
				$vlt['events'] = array();
			}
			$vlt['events'][] = $tdwebc ;
			
			fseek( $fvlt, 0, SEEK_SET );
			fwrite( $fvlt, json_encode( $vlt ) );
			$xmlresp->status='OK' ;
						
			ftruncate( $fvlt, ftell( $fvlt ) );
			fflush( $fvlt );
		}
			
		flock( $fvlt, LOCK_UN );
		fclose( $fvlt );
	}
}

done:	
	echo $xmlresp->asXML() ;
?>
