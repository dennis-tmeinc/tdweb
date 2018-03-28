<?php
	
require_once 'config.php' ;

if( empty($session_path) ) {
	$session_path= "session" ;
}

if( !empty( $_REQUEST['c'] ) ) {

	set_time_limit( 300 );
	
	if( $_REQUEST['c'] == 'i' && !empty($_REQUEST['p']) ) {	// initial
		header( "X-Webt: ready" );				// give out right response
	
		$flvc = fopen( $session_path.'/sess_lvc_'.$_REQUEST['p'], "c+" );
		
		$starttime = time() ;
		$stime = $starttime ;
		$xtime = $stime ;
		$timeout = 100 ;
		
		while( ($xtime-$starttime)<1800 && ($xtime-$stime) < $timeout ) {
			set_time_limit( 30 );
			flock( $flvc, LOCK_EX ) ;		// exclusive lock

			$o=false ;
			fseek( $flvc, 0, SEEK_SET );
			while( $line = fgets( $flvc ) ) {
				$x = explode( ',', $line );
				if( count( $x )>= 4 && ($xtime - $x[0]) < 30 ) {
					echo 'c '.trim($x[1]).' '.trim($x[2]).' '.trim($x[3])."\r\n" ; 
					$o=true ;
				}
			}
			
			if( $o ) {							// clean init file
				ftruncate( $flvc, 0 );
				fflush( $flvc ) ;              	// flush before release the lock
			}
			flock( $flvc, LOCK_UN ) ;		// unlock ;
			
			if( $o ) {							// contents? flush out buffer, and clean the init file
				break ;
			}
	
			usleep(50000);
			$xtime = time() ;
		}
		fclose( $flvc );

	}
	else if( $_REQUEST['c'] == 'g' && !empty($_REQUEST['t']) ) {		// GET, WCURL
		$rport = 0 ;
		// check if dvr with phone number is ready
		$rfilename = $session_path.'/sess_lvr_'.$_REQUEST['t'] ;
		$rfile = fopen( $rfilename, "r" );
		if( $rfile ) {
			fscanf( $rfile, "%d", $rport );
			fclose( $rfile );
		}
		else {
			$rport = 0 ;
		}
		
		if( $rport ) {
			$conn = stream_socket_client("tcp://127.0.0.1:".$rport, $errno, $errstr, 30);
			if( $conn ) {
				fwrite( $conn, 'g' );		// Get data

				$msgtype = fread( $conn, 1 ) ;
 
				if( $msgtype === false || strlen($msgtype) == 0 ) {
					header( "X-Webt-Connection: close" );
				}
				else if( $msgtype == 'd' ) {			// data incoming
					while( true ) {
						$data = fread( $conn, 65536 ) ;
						if( $data === false || strlen($data) == 0 ) {
							break ;
						}
						echo $data ;
					}
				}
				else if( $msgtype == 'e' ) {	// end of output data
					// header( "X-Webt-Connection: gend" );	// GET END, *** not used, could cause webtun run into deadloop
					
					// wait 10 sec or until rfile removed
					for( $d=0; $d<10; $d++ ) {
						if( !file_exists( $rfilename ) ) {
							header( "X-Webt-Connection: close" );
							break;
						}
						sleep(1);
					}
				}
				
				fclose( $conn );
			}
			else {
				header( "X-Webt-Connection: close" );
			}
		}
		else {
			header( "X-Webt-Connection: close" );
		}
	}
	else if( $_REQUEST['c'] == 'p' && !empty($_REQUEST['t']) ) {		// PUT, RCURL
		$closed = false ;
		$rport = 0 ;
		// check if dvr with phone number is ready
		$rfile = fopen( $session_path.'/sess_lvr_'.$_REQUEST['t'], "r" );
		if( $rfile ) {
			fscanf( $rfile, "%d", $rport );
			fclose( $rfile );
		}
		else {
			$rport = 0 ;
		}
		
		if( $rport ) {
			$length = 500000000 ;		// an impossible max length
			if( !empty( $_SERVER['CONTENT_LENGTH'] ) ) {
				$length = (int) $_SERVER['CONTENT_LENGTH'] ;
			}
			if( $length > 0 ) {
				$conn = stream_socket_client("tcp://127.0.0.1:".$rport, $errno, $errstr, 10);
				if( $conn ) {
					$inputdata = fopen("php://input", "r");
					if( $inputdata ) {
						// message type ($msgtype)
						fwrite( $conn, 'd' );			// data
						// transfer data over
						while( $length > 0 ) {
							$rl = $length ;
							if( $rl > 65536 ) $rl = 65536 ;
							$data = fread($inputdata, $rl) ;
							if( $data === false || strlen($data)==0 ) 
								break;
							fwrite( $conn, $data );
							$length -= strlen( $data );
						}
						fclose( $inputdata );
					}
					fclose( $conn );
				}
				else {
					$closed = true ;
				}
			}
				
			if( !$closed && !empty($_SERVER['HTTP_X_WEBT_CONNECTION']) && $_SERVER['HTTP_X_WEBT_CONNECTION'] == "close" ) {	// connection closed!
				$conn = stream_socket_client("tcp://127.0.0.1:".$rport, $errno, $errstr, 10);
				if( $conn ) {
					// message type ($msgtype)
					fwrite( $conn, 'e' );			// End connection
					fclose( $conn );
				}
				else {
					$closed = true ;
				}			
			}	
		}
		else {						// no listener, close connection
			$closed = true ;
		}
		
		if( $closed ) {
			header( "X-Webt-Connection: close" );
		}
		header("Content-Length: 0");

	}
}

?>