<?php
// livetun.php - live web tunnel over http protocl (connected from DVR)
// Requests:
//      c : command , i: initialize connection, g: get data, p: put data
//      p : phone number (DVR ID)
//      t : connection ID 
// Return:
//      raw data
// By Dennis Chen @ TME	 - 2016-11-18
// Copyright 2016 Toronto MicroElectronics Inc.

require_once 'config.php' ;
require_once 'netpackfunc.php' ;

// default session path
if( empty($session_path) ) {
	$session_path= "session";
}

if( !empty($_REQUEST['s']) ){
	header("X-Webt-Serial:".$_REQUEST['s'] );
}

if( !empty( $_REQUEST['c'] ) ) {

	set_time_limit( 150 );
	
	if( $_REQUEST['c'] == 'i' && !empty($_REQUEST['p']) ) {	// initial
		header( "X-Webt: ready" );				// give out right response
	
		$starttime = time() ;
		$xtime = $starttime ;
		$timeout = 100;
		
		$flvc = fopen( $session_path.'/sess_lvc_'.$_REQUEST['p'], "c+" );

		// write something to the file to force update filemtime
		flock( $flvc, LOCK_EX ) ;
		fseek( $flvc, 0, SEEK_END );
		if( ftell( $flvc ) == 0 ) {
			fwrite( $flvc, "n\n");
			fflush( $flvc ) ;
		}
		flock( $flvc, LOCK_UN ) ;
		
		while( ($xtime-$starttime) < $timeout ) {
			fseek( $flvc, 0, SEEK_END );
			if( ftell( $flvc )>1 ) {
				flock( $flvc, LOCK_EX ) ;		// exclusive lock

				rewind($flvc);
				while( $line = fgets( $flvc ) ) {
					$x = explode( ',', $line );
					if( count( $x )>1 && ($xtime - $x[0]) < 15 ) {
						echo $x[1] ;
					}
				}

				ftruncate( $flvc, 0 );
				fflush( $flvc ) ;              	// flush before release the lock
				flock( $flvc, LOCK_UN ) ;		// unlock ;

				if( ob_get_length()>0 ) {
					ob_flush();flush();
				}
			}
			else {
				usleep(20000);
			}
			$xtime = time() ;
		}
		fclose( $flvc );

	}
	else if( $_REQUEST['c'] == 'g' && !empty($_REQUEST['t']) ) {		// GET, WCURL
		// check if connection with phone number is ready
		$lvr = $session_path.'/sess_lvr_'.$_REQUEST['t'] ;
		if( file_exists ( $lvr ) ) {
			$conn = stream_socket_client("tcp://127.0.0.1:".$_REQUEST['t']);
			if( $conn ) {
				stream_set_timeout( $conn, 100 );
				// message type handshake
				fwrite( $conn, 'g' );		// Get data
				while( !feof($conn) ) {
					set_time_limit( 120 );	// a bit longer than stream timeout
					$data = net_readpack( $conn ) ;
					if( strlen($data) == 0 ) {	// timeout or closed
						break ;
					}
					echo $data ;
					ob_flush();
					flush();
					stream_set_timeout( $conn, 5);
				}
				$conn = NULL ;
			}
			else {
				$lvr = '.nc.' ;
			}
		}

		clearstatcache();
		if( !headers_sent() && !file_exists ( $lvr ) ) {
			header( "X-Webt-Connection: close", true, 204 );
		}
	}
	else if( $_REQUEST['c'] == 'p' && !empty($_REQUEST['t']) ) {		// PUT, RCURL
		if( isset( $_SERVER['CONTENT_LENGTH'] ) ) {
			$length = (int) $_SERVER['CONTENT_LENGTH'] ;
		}
		else {
			$length = 1000000000 ;		// an impossible max length
		}
		$lvr = $session_path.'/sess_lvr_'.$_REQUEST['t'] ;
		// check if connection with phone number is ready
		if( file_exists ( $lvr ) && $length > 0 ) {
		
			$inputdata = fopen("php://input", "r");
			if( $inputdata ) {
				$conn = stream_socket_client("tcp://127.0.0.1:".$_REQUEST['t']);
				if( $conn ) {
					stream_set_timeout( $conn, 100 );
					// message type handshake
					fwrite( $conn, 'p' );		// Put data
					// transfer data over
					while( $length > 0 && !feof($conn) && !feof($inputdata) ) {
						set_time_limit( 120 );
						if( $length > 100000 )
							$data =  fread($inputdata, 100000) ;
						else 
							$data =  fread($inputdata, $length) ;
						if( $data === false || strlen($data) == 0 ) 
							break;
						// write packet
						if( ! net_sendpack( $conn, $data ) )
							break;
						$length -= strlen($data);
					}
					$conn = NULL ;
				}
				else {
					$lvr = '.nc.' ;
				}
				fclose($inputdata);
			}
		}

		clearstatcache();
		if( file_exists ( $lvr ) && !empty($_SERVER['HTTP_X_WEBT_CONNECTION']) && $_SERVER['HTTP_X_WEBT_CONNECTION'] == "close" ) {	// connection closed!
			$conn = stream_socket_client("tcp://127.0.0.1:".$_REQUEST['t']);
			if( $conn ) {
				// message type handshake
				fwrite( $conn, 'e' );		// End connection
				fread($conn,1);				// wait connection closed by peer
				$conn = NULL ;
			}
		}
		
		clearstatcache();
		if( !headers_sent() && !file_exists ( $lvr ) ) {
			header( "X-Webt-Connection: close", true, 204 );
		}

	}
}

return ;
?>