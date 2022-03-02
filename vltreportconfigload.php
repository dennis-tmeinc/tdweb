<?php
// vltreportconfigload.php - load vlt report configuration
// Requests:
//      vltpage : page number
//      vltvehicle : vehicle name
// Return:
//      JSON object with vlt fields
// By Dennis Chen @ TME	 - 2014-04-08
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon && !empty( $_REQUEST['vltpage'] ) && !empty( $_REQUEST['vltvehicle'] ) ) {
		
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];
		
		$fvlt = fopen( session_save_path().'/sess_vlt_'.$vltsession, "r" );
		if( $fvlt ) {
			flock( $fvlt, LOCK_SH ) ;		// read lock
	
			@$vlt = json_decode( fread( $fvlt, 256000 ), true );
			
			flock( $fvlt, LOCK_UN ) ;
			fclose( $fvlt );
		}
		
		if(!empty( $vlt['run'] ) && !empty( $vlt['cfg'][$_REQUEST['vltvehicle']] ) ) {
			$vdata = $vlt['cfg'][$_REQUEST['vltvehicle']] ;
			for( $io=0 ;$io<32; $io++ ) {
				$vdata['vlt_gpio_'.$io] = $vdata['vlt_gpio'][$io] ;
			}
			unset( $vdata['vlt_gpio'] );
			$resp['vltconfig'] = $vdata ;
			$resp['res'] = 1 ;
		}

	}
	echo json_encode( $resp );
?>




