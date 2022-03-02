<?php
// dashboardoptionsave.php - save dashboard options
//      Options are saved in user's setting file
// Requests:
//      Dashboard options
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2014-02-19
// Copyright 2013 Toronto MicroElectronics Inc.
// 
// V2.5
//    save to dashboard option file, only admin user allowed

	require_once 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	$resp=array( 'res' => 0 );
	if($logon){
		if( $_SESSION['user_type'] == "admin" ) {		// admin only

			// read old settings first, to preserve settings order (?)
			$dashboardopt = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;
			
			if( empty($dashboardopt) ) $dashboardopt=array();
			foreach ( $_REQUEST as $key => $value) {
				$dashboardopt[$key]=$value ;
			}
			
			if( !empty( $dashboardopt['tmStartOfDay'] ) ) {
				$tsod = new DateTime( $dashboardopt['tmStartOfDay'] );
				$tsod ->setDate( 2000, 1, 1 ) ;
				$dashboardopt['tmStartOfDay'] = $tsod->format('Y-m-d H:i:s');
			}

			$confstr = '' ;
			foreach ( $dashboardopt as $key => $value) {
				if( $value == 'on' ) {
					$value='1';
				}
				$confstr .= $key."=".$value."\r\n";
			}	
				
			if( vfile_put_contents( $dashboard_conf, $confstr ) > 0 ) {
				$resp['res']=1;
			}
			else {
				$resp['errormsg']="Storage Error!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	else {
		$resp['errormsg']="Session Error!";
	}
	echo json_encode($resp);
?>