<?php
// td_alert.php - read top 2 items of Touch Down alerts
// Requests:
//      none
// Return:
//      JSON array of Touch Down Alerts
// By Dennis Chen @ TME	 - 2013-06-20
// Copyright 2013 Toronto MicroElectronics Inc.
// V3.0
//      Show only today or yesterday alerts

$noredir=1 ;
$noupdatetime=1 ;
include 'session.php' ;
include 'vfile.php' ;

header("Content-Type: application/json");
if( $logon ) {
		
	$servertime = new DateTime() ;
	$resp['time']=$servertime->format('Y-m-d H:i') ;

	// dashboard options
	$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;

	// default value
	if( empty( $dashboard_option ) ) $dashboard_option =  array(
		'tmStartOfDay' => '3:00'
	);
	
	if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
		$dashboard_option['tmStartOfDay'] = '0:00' ;
	}

	// begin of day 
	$day_begin = new DateTime( $dashboard_option['tmStartOfDay'] );

	if( substr_compare($_SERVER['HTTP_REFERER'], "dashboardmorning.php", -20)==0 ) {
		// dashboard morning (count from yesterday to this morning
		$yesterday = new DateTime( $servertime->format('Y-m-d H:i:s') );
		$yesterday->sub(new DateInterval('P1D'));
		$sql = "SELECT * FROM td_alert WHERE date_time >= '".$yesterday->format('Y-m-d '). $day_begin->format('H:i:s') ."' AND date_time< '". $servertime->format('Y-m-d '). $day_begin->format('H:i:s') ."' ORDER BY date_time DESC LIMIT 0, 2 ; ";
	}
	else {
		$sql = "SELECT * FROM td_alert WHERE date_time >= '".$servertime->format('Y-m-d '). $day_begin->format('H:i:s') ."' ORDER BY date_time DESC LIMIT 0, 2 ; ";
	}
	
	if($result=$conn->query($sql)) {
		$resp['td_alert'] = array();
		while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
			$resp['td_alert'][]=$row;
		}
		$result->free();
		$resp['res']=1 ;	// success
	}
}

echo json_encode($resp);

?>