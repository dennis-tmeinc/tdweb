<?php
// vltliveview.php - generate a video play back list file for live track live view
// Request:
//      info : json encoded dvr info 
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
			
			// duplicate info['name'] 
			if( !empty( $info['dvrid'] ) ) {
				$info['name'] = $info['dvrid'] ;
			}
			
			if( !isset($info['support_live']) )
				$info['support_live'] = 1 ;
			if( !isset($info['support_playback']) )
				$info['support_playback'] = 0 ;
			if( !isset($info['playmode']) )
				$info['playmode'] = "live" ;
			
			// assing info['encoder']
			// if( empty( $info['type'] ) || (int)$info['type'] == 5 ) {
				$info['encoder'] = "266" ;		// support 266 only now
			//}
			
			$server = array();
			$server['protocol'] = "dvr" ;		// this is default protocol
			if( !empty( $req['ip'] ) ) {
				$server['host'] = $req['ip'] ;
			}
			
			if( !empty( $liveplay_protocol ) ) {
				$server['protocol'] = $liveplay_protocol ;
				if( !empty( $liveplay_host ) ) {
					$server['host'] = $liveplay_host ;
				}
				if( !empty( $liveplay_port ) ) {
					$server['port'] = $liveplay_port ;
				}				
				if( $server['protocol'] == "relay" ) {
					$info['support_relay'] = 1 ;
					if( empty( $server['host'] ) ) {
						$server['host'] = file_get_contents("https://api.ipify.org/");
					}
				}
			}

			$server["sessionidname"] = $session_idname ;
			$server[$session_idname] = session_id() ;

			$protocol = $_SERVER["REQUEST_SCHEME"] ;
			$host = $_SERVER['HTTP_HOST'];
			$port = $_SERVER["SERVER_PORT"];
			if( $port == "443" || $port == "80" ){
				$port = '';
			}
			else {
				$port = ':'.$port;
			}
			$app = dirname( $_SERVER["SCRIPT_NAME"] )."/istream.php" ;
			$server['url'] = "$protocol://$host$port$app" ;

			$dpl = array();
			$dpl["server"] = $server ;
			$dpl['info'] = $info ;
			
			echo "#DPL\r\n" ;
			echo "# Content-Type: JSON\r\n" ;
			echo "# Touch Down Center ". $_SESSION['release']."\r\n\r\n" ;
			
			echo json_encode( $dpl, JSON_PRETTY_PRINT ) ;
		}

	}
	else {
		echo json_encode( $resp );
	}
?>