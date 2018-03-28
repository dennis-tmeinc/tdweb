<?php
	
require_once 'config.php' ;

if( empty($session_path) ) {
	$session_path= "session" ;
}

if( !empty( $_REQUEST['c'] ) ) {

	set_time_limit( 200 );
	
	if( $_REQUEST['c'] == 'i' && !empty($_REQUEST['p']) ) {	// initial
		$stime = time() ;
		$timeout = 100 ;
		$content = '' ;
		
		$flvc = fopen( $session_path.'/sess_lvc_'.$_REQUEST['p'], "c+" );
		while( ((int)(time()-$stime)) < $timeout ) {
			$r=false ;
		
			flock( $flvc, LOCK_EX ) ;		// exclusive lock
			
			fseek( $flvc, 0, SEEK_SET );
			while( $line = fgets( $flvc ) ) {
				$r = true ;
				$x = explode( ',', $line );
				if( count( $x )>= 4 && ($stime - $x[0]) < 30 ) {
					$content .= 'c '.trim($x[1]).' '.trim($x[2]).' '.trim($x[3])."\r\n" ;
				}
			}
			
			if( $r ) {							// contents? clean it
				ftruncate( $flvc, 0 );
				fflush( $flvc ) ;              	// flush before release the lock
			}
			flock( $flvc, LOCK_UN ) ;		// unlock ;
			
			if( empty( $content ) ) {
				usleep(10000);
			}
			else {
				break ;
			}
		}
		fclose( $flvc );
		
		header( "X-Webt: ready" );				// give out right response
		if( strlen( $content )>0 ) {
			echo $content ;
		}
	}
	else if( $_REQUEST['c'] == 'g' && !empty($_REQUEST['t']) ) {		// GET, WCURL
		$rport = 0 ;
		// check if dvr with phone number is ready
		$rfile = fopen( $session_path.'/sess_lvr_'.$_REQUEST['t'], "r" );
		if( $rfile ) {
			fscanf( $rfile, "%d", $rport );
			fclose( $rfile );
		}
		if( $rport ) {
			$conn = stream_socket_client("tcp://127.0.0.1:".$rport, $errno, $errstr, 10);
			if( $conn ) {
				fwrite( $conn, 'g' );		// Get data
				
				$msgtype = fread( $conn, 1 ) ;
				if( $msgtype == 'd' ) {			// data incoming
					while( true ) {
						$data = fread( $conn, 8192 ) ;
						if( $data === false || strlen($data) == 0 ) {
							break ;
						}
						echo $data ;
					}
				}
				else if( $msgtype == 'e' ) {	// end of output data
					header( "X-Webt-Connection: gend" );	// GET END
				}
				
				fclose( $conn );
			}
		}
		else {
			header( "X-Webt-Connection: close" );
		}
	}
	else if( $_REQUEST['c'] == 'p' && !empty($_REQUEST['t']) ) {		// PUT, RCURL
		$rport = 0 ;
		// check if dvr with phone number is ready
		$rfile = fopen( $session_path.'/sess_lvr_'.$_REQUEST['t'], "r" );
		if( $rfile ) {
			fscanf( $rfile, "%d", $rport );
			fclose( $rfile );
		}
		if( $rport ) {
			$inputdata = fopen("php://input", "r");
			$conn = stream_socket_client("tcp://127.0.0.1:".$rport, $errno, $errstr, 10);
			if( $conn ) {
				if( !empty($_SERVER['HTTP_X_WEBT_CONNECTION']) && $_SERVER['HTTP_X_WEBT_CONNECTION'] == "close" ) {	// connection closed!
					// message type ($msgtype)
					fwrite( $conn, 'e' );			// End connection
				}
				else {
					$length = 500000000 ;
					if( !empty( $_SERVER['CONTENT_LENGTH'] ) ) {
						$length = (int) $_SERVER['CONTENT_LENGTH'] ;
					}
					if( $length > 0 ) {
						// message type ($msgtype)
						fwrite( $conn, 'd' );			// data
						// transfer data over
						while( $length > 0 ) {
							$rl = $length ;
							if( $rl > 128*1024 ) $rl = 128*1024 ;
							$data = fread($inputdata, $rl) ;
							if( $data === false || strlen($data)==0 ) 
								break;
							fwrite( $conn, $data );
							$length -= strlen( $data );
						}
					}
				}
				fclose( $conn );
			}
			if( $inputdata ) fclose( $inputdata );
		}
		else {						// no listener, close connection
			header( "X-Webt-Connection: close" );
		}
		header("Content-Length: 0");

	}
}

?>