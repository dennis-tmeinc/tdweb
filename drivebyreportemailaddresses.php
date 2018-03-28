<?php
// drivebyreportemailaddresses.php - get default email address for driveby reports
// Request:
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-05-26
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	if( $logon ) {
		$email = vfile_get_contents( $driveby_eventdir.'/email.conf' );
		if( $email ) {
			$email = json_decode( $email, true ) ;
			if( !empty( $email['to'] ) ) {
				$resp['to'] = $email['to'] ;
			}
			else {
				$resp['to'] = ' ' ;
			}
			if( !empty( $email['from'] ) ) {
				$resp['from'] = $email['from'] ;
			}
			else {
				$resp['from'] = " " ;
			}
			if( !empty( $email['notes'] ) ) {
				$resp['notes'] = $email['notes'] ;
			}
			else {
				$resp['notes'] = " " ;
			}
			$resp['res'] = 1 ;
		}
	}
	
	echo json_encode($resp);
?>