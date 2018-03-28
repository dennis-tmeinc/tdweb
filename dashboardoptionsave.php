<?php
// dashboardoptionsave.php - save dashboard options
//      Options are saved in user's setting file
// Requests:
//      Dashboard options
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-07-10
// Copyright 2013 Toronto MicroElectronics Inc.
// 
// V2.5
//    save to dashboard option file, only admin user allowed

	require 'session.php' ;
	header("Content-Type: application/json");
	
	$resp=array( 'res' => 0 );
	if($logon){
		if( $_SESSION['user_type'] == "admin" ) {		// admin only

			// read old settings first, to preserve settings order (?)
			@$dashboardopt = parse_ini_file($dashboard_conf);
			if( empty($dashboardopt) ) $dashboardopt=array();
			foreach ( $_REQUEST as $key => $value) {
				$dashboardopt[$key]=$value ;
			}
			
			if( !empty( $dashboardopt['tmStartOfDay'] ) ) {
				$tsod = new DateTime( $dashboardopt['tmStartOfDay'] );
				$tsod ->setDate( 2000, 1, 1 ) ;
				$dashboardopt['tmStartOfDay'] = $tsod->format('Y-m-d H:i:s');
			}

			@$dashboardoptfile=fopen( $dashboard_conf, "w" );
			if( !empty($dashboardoptfile) ) {
				foreach ( $dashboardopt as $key => $value) {
					if( $value == 'on' ) {
						$value='1';
					}
					fwrite($dashboardoptfile, $key."=".$value."\r\n");
				}			
				fclose( $dashboardoptfile );
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