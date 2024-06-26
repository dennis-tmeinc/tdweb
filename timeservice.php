<?php
// timeservice.php - Exported Time Functions
// Request:
//      cmd : getLocal / getUTC / LocalToUTC / UTCtoLocal 
//      clientid : client id (for multiple company features) 
//      tz : timezone name
//      time : time to be converted
// Return:
//      json 
// By Dennis Chen @ TME	 - 2016-09-20
// Copyright 2016 Toronto MicroElectronics Inc.
//

include "config.php" ;

$result = array(
	'status' => "ERROR",
);


if( empty($client_dir) || !is_dir($client_dir) ) {
	$client_dir = "client" ;
}
if( !empty( $_REQUEST['clientid'] )) {
	$clientcfg = "$client_dir/$_REQUEST[clientid]/config.php" ;
	if( file_exists ( $clientcfg ) ) {
		include $clientcfg ;
	}
}

if( !empty( $_REQUEST['tz'] ) ) {
	$timezone = $_REQUEST['tz'];
}

if(!empty($timezone) ) {
	date_default_timezone_set($timezone) ;	
}

$timeformat='Y-m-d H:i:s' ;
		
if( !empty($_REQUEST['time']) ) {
	$ti = new DateTime( $_REQUEST['time'] ) ;
} 		
else {		
	$ti = new DateTime() ;
}

$result['tz'] = date_default_timezone_get() ;
$ts = $ti->getTimestamp() ;
$result['localtime'] = date( $timeformat, $ts );
$result['UTC'] = gmdate( $timeformat, $ts);
$result['status']="OK";

if( !empty($_REQUEST['vhost']) && !empty($liveplay_host)) {
    $result['vhost']=$liveplay_host ;
}

if( !empty( $result ) ) {
	header("Content-Type: application/json");
	echo json_encode( $result, JSON_PRETTY_PRINT );
}

?>