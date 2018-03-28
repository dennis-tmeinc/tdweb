<?php
// vltliveview.php - generate a video play back list file for live track live view
// Request:
//      dvrid : dvr name (bus name)
//      ip : dvr ip address
//      phone : dvr mobile phone #
//      type : dvr type
// Return:
//      .dpl file , contain JSON contents
// By Dennis Chen @ TME	 - 2013-11-21
// Copyright 2013 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header( "Content-Type: application/x-touchdown-playlist");
	header( "Content-Disposition: attachment; filename=\"touchdown.dpl\"" );
	
	if( $logon ){
	
		$server = array() ;
		
		if( !empty($_REQUEST['info']) ) {
			$req = json_decode( $_REQUEST['info'], true ) ;
			
			$info = $req ;
			$info['name'] = $req['dvrid'] ;

			$server = array();
			$server['protocol'] = "dvr" ;
			$server['host'] = $req['ip'] ;
			
			if( !empty( $liveplay_protocol ) ) {
				$server['protocol'] = $liveplay_protocol ;
				if( !empty( $liveplay_host ) ) {
					$server['host'] = $liveplay_host ;
				}
				if( !empty( $liveplay_port ) ) {
					$server['port'] = $liveplay_port ;
				}
			}
			
			$dpl = array();
			$dpl["server"] = $server ;
			$dpl['info'] = $info ;
			
			echo "#DPL\r\n" ;
			echo "# Content-Type: JSON\r\n" ;
			echo "# Touch Down Center ". $_SESSION['release']."\r\n\r\n" ;
			
			echo json_encode( $dpl ) ;
		}

	}
	else {
		echo json_encode( $resp );
	}
?>