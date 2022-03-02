<?php
// livesetup.php - live setup support over web tunnel 
// Requests:
//      DVR setup url :  ex, http://host/tdc/livesetup.php/<phonenumber>/page.html
// Return:
//      tunneled data (web page)
// By Dennis Chen @ TME	 - 2016-11-18
// Copyright 2016 Toronto MicroElectronics Inc.

require_once 'session.php' ; 
require_once 'webtunstream.php' ;

if( !empty( $_SERVER['PATH_INFO'] ) ) {
	$p = strpos( $_SERVER['PATH_INFO'], '/', 1 );
	if( $p > 0 ) {
		$phone = substr( $_SERVER['PATH_INFO'], 1, $p-1 );
		$nreq = substr(  $_SERVER['PATH_INFO'], $p );
		
		if(!empty($_SERVER['QUERY_STRING'])) {
			$nreq.='?'.$_SERVER['QUERY_STRING'] ;
		}
	}
}
if( empty( $phone ) || empty( $nreq) ) {
    echo "<html><body>Invalid Request !</body></html>" ;
	return ;
}

if( !$logon ) {
	header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); 
    echo "<html><body>Session Expired!</body></html>" ;
	return ;
}

if( $_SESSION['user_type'] != "admin" ) {
	header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); 
    echo "<html><body>Admin account required</body></html>" ;
	return ;
}

header("Content-type: application/octet-stream");

$stream = fopen("webtun://$phone", "p+ls -l /") ;
if( $stream ) {
	
	// don't let user abort interrupt this scripts
	ignore_user_abort(true);
	
    set_time_limit( 500 );
     
	$run = true ;
	$starttime=time();
	
	while( $run && (time()-$starttime)<30 && !feof($stream) && connection_status()==CONNECTION_NORMAL ) {
		echo fgets($stream, 1000) ;
	}

	$stream = NULL ;
}

return ;
?>
