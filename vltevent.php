<?php
// vltevent.php - AVL service call back
// Requests:
//      xml: xml document of AVL protocol
// Return:
//      xml document composed of AVL protocol
// By Dennis Chen @ TME	 - 2013-11-18
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

// database
@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

// look for vlt event listener
$ltime = $mtime - 800 ;
$sql = "SELECT * FROM `_tmp_tdweb` WHERE `vname` = 'vltlistener' AND `session` = '$vltsession' AND `mtime` > $ltime ;" ;

$listener = array();
if( $result=$conn->query($sql) ) {
	if( $row=$result->fetch_array() ) {
		$listener = $row['vdata'] ;
	}
	$result->free();
}

if( empty( $listener ) ) {
   	$xmlresp->status='ErrorSessionEnd' ;
	$xmlresp->errormsg='Unexpected message.' ;
	goto done ;
}

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
	$vdata = $conn->escape_string(json_encode( $tdwebc ));
	$sql = "INSERT INTO `_tmp_tdweb` (`vname`, `mtime`, `user`, `session`, `vdata` ) VALUES ( 'vltevent', '$mtime', '$_SESSION[user]', '$vltsession', '$vdata' ) ;";
	$conn->query($sql) ;
	$sevent = stream_socket_client("udp://127.255.255.255:56328");
	if( $sevent ) {
		fwrite($sevent,"avlp");
		fclose($sevent);
	}
}

// success
$xmlresp->status='OK' ;

done:	
	echo $xmlresp->asXML() ;
?>
