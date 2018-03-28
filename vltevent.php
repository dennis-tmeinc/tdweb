<?php
// vltevent.php - AVL service call back
// Requests:
//      xml: xml document of AVL protocol
// Return:
//      xml document composed of AVL protocol
// By Dennis Chen @ TME	 - 2013-11-18
// Copyright 2013 Toronto MicroElectronics Inc.

header("Content-Type: application/xml");

$resp = new SimpleXMLElement('<tdwebc><status>Error</status></tdwebc>') ;

if( empty($_REQUEST['xml']) ) {
	$resp->status="ErrorNoXML" ;
	$resp->errormsg='No XML parameter!' ;
	goto done ;
}

@$tdwebc = new SimpleXMLElement($_REQUEST['xml']) ;

if( empty( $tdwebc->session ) ) {
	$resp->status='ErrorXML' ;
	$resp->errormsg='XML document error or session error';
	goto done ;
}


//$log = fopen("session/eventlog.log", "a");
//if( $log ) {
//	fwrite( $log, "\nEvent: ".date("h:i:s ").$_REQUEST['xml'] );
//	fclose( $log );
//}

$vltsession = $tdwebc->session ;
$resp->session=$vltsession ;
$ss = explode('-', $vltsession);

require_once 'config.php' ;

session_save_path( $session_path );
session_name( $session_idname );
session_id( $ss[0] );
session_start();
session_write_close();

$xt = $_SERVER['REQUEST_TIME'];
if( empty($_SESSION['user']) ||
	empty($_SESSION['xtime']) || 
	$xt>$_SESSION['xtime']+$session_timeout ||
	empty($_SESSION['release'])	)
{
	// session wrong
	$resp->status='ErrorSessionEnd' ;
	$resp->errormsg='Session not exist or expired.' ;
	goto done ;
}

@$vltcommand = $tdwebc->command ;

@$vltserialno = $tdwebc->serialno ;
if( empty($vltserialno) ) 
	$vltserialno = '' ;		// no serial (generic)
else 
 	$resp->serialno = $tdwebc->serialno ;

$mtime = time();	

// database
@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

// look for vlt event listener
$ltime = $mtime - 300 ;
$sql = "SELECT * FROM `_tmp_tdweb` WHERE `vname` = 'vltlistener' AND `session` = '$vltsession' AND `mtime` > $ltime ;" ;

$listener = array();
if( $result=$conn->query($sql) ) {
	if( $row=$result->fetch_array() ) {
		$listener = $row['vdata'] ;
	}
	$result->free();
}

if( empty( $listener ) ) {
   	$resp->status='ErrorSessionEnd' ;
	$resp->errormsg='Unexpected message.' ;
	goto done ;
}

if( empty($vltcommand) || $vltcommand < 1 || $vltcommand >200  ) {		// No command ?
   	$resp->status='ErrorCommand' ;
	$resp->errormsg='Wrong command value.' ;
	goto done ;
}
$resp->command=$vltcommand ;

if( empty( $tdwebc->ack ) ) {
	// an event ?
	$resp->ack = '2' ;
	$resp->reason = '0' ;
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
$resp->status='OK' ;

done:	
	echo $resp->asXML() ;
?>
