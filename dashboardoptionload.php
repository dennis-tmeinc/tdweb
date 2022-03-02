<?php
// dashboardoptionload.php - Load dashboard options 
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2014-02-19
// Copyright 2013 Toronto MicroElectronics Inc.
// V2.5
//   load from dashboard option file

	require_once 'session.php' ;
	require_once 'vfile.php' ;
	
	header("Content-Type: application/json");
	
	$resp=array();
	if($logon){

		// dashboard options
		$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;

		// default value
		if( empty( $dashboard_option ) ) $dashboard_option =  array();
		
		if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
			$dashboard_option['tmStartOfDay'] = '0:00' ;
		}
		if( empty( $dashboard_option['nAverageDuration'] ) || $dashboard_option['nAverageDuration'] < 2 ) {
			$dashboard_option['nAverageDuration'] = 60 ;
		}

		// mod tmStartOfDay to format hh:mm
		$tsod = new DateTime( $dashboard_option['tmStartOfDay'] );
		$dashboard_option['tmStartOfDay'] = $tsod->format('G:i');
		
		$resp=$dashboard_option ;
	}
	echo json_encode($resp);
	
?>