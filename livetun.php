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
	
	// send back data
	function _output( $d )
	{
		echo 's'.strlen($d)."\n" ;
		echo $d ;
		ob_flush();
		flush();
	}	
	
	if( $_REQUEST['c'] == 'i' && !empty($_REQUEST['p']) ) {	// initial
		header( "X-Webt: ready" );				// give out right response
		
		$starttime = time() ;
		$xtime = $starttime ;
		$timeout = 100;
		
		$ttag = "i $xtime\n" ;
		$ttaglen = -1 ;
		
		$flvc = fopen( $session_path.'/sess_lvc_'.$_REQUEST['p'], "c+" );

		while( ($xtime-$starttime) < $timeout  ) {
			fseek( $flvc, 0, SEEK_END );
			if( ((int)ftell( $flvc ))>$ttaglen ) {
				flock( $flvc, LOCK_EX ) ;		// exclusive lock
				
				rewind($flvc);
				while( $line = fgets( $flvc ) ) {
					if( substr( $line, 0, 1) == 'i' ) {
						continue ;
					}
					$x = explode( ',', $line );
					if( count( $x )>1 && strlen($x[1]) > 0 && ($xtime - $x[0]) < 30 ) {
						echo $x[1] ;
						$timeout =  0;
					}
				}

				fseek( $flvc, 0, SEEK_SET );
				ftruncate( $flvc, 0 );
				fwrite( $flvc, $ttag );
				$ttaglen = ftell( $flvc );
			
				fflush( $flvc ) ;              	// flush before release the lock
				flock( $flvc, LOCK_UN ) ;		// unlock ;
			}
			else {
				usleep(20000);
				$xtime = time() ;
			}
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
					$data = net_readpack( $conn ) ;
					if( strlen($data) == 0 ) {	// timeout or closed
						break ;
					}
					echo $data ;
					ob_flush();
					flush();
					stream_set_timeout( $conn, 2);
					set_time_limit( 10 );	// a bit longer than stream timeout
				}
				fclose($conn);
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
						if( $length > 16384 )
							$data =  fread($inputdata, 16384) ;
						else 
							$data =  fread($inputdata, $length) ;
						if( $data === false || strlen($data) == 0 ) 
							break;
						// write packet
						if( ! net_sendpack( $conn, $data ) )
							break;
						$length -= strlen($data);
					}
					fclose($conn);
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
				fclose($conn);
				$conn = NULL ;
			}
		}
		
		clearstatcache();
		if( !headers_sent() && !file_exists ( $lvr ) ) {
			header( "X-Webt-Connection: close", true, 204 );
		}

	}
	else if( $_REQUEST['c'] == 't' && !empty($_REQUEST['t']) ) {		// RCURL
		$target = stream_socket_client( $_REQUEST['t'] );
		if( $target ) {
			$sserver = stream_socket_server("tcp://localhost:0");
			if( $sserver ) {
				$sport = parse_url( "tcp://" .stream_socket_get_name($sserver, false) )['port'] ;
				header( "X-Webt: ready");
				header( "X-Webt-Id: $sport");
				
				echo 'p'. $sport . "\n" ;		
				ob_flush();
				flush();
				
				$sdat = NULL ;
				while( $target ){
					if( $sdat ) {
						$reads = array( $target, $sdat );
					}
					else {
						$reads = array( $target, $sserver );
						$reads = array( $target, $sdat );
					}
					$writes = NULL ;
					$exs = NULL ;
					set_time_limit(200) ;
					if( stream_select($reads, $writes, $exs, 182)>0 ) {
						if( in_array( $target, $reads, true) ) {
							$data = fread( $target, 8192 );
							if( $data===false || strlen($data)==0 ) {
								// end of stream or error!
								break ;
							}
							else {
								echo 'a'.$address.'\n' . 's'.strlen($data).'\n';
								echo $data ;
								ob_flush();	flush();
							}
						}
						
						if( in_array( $sserver, $reads, true) ) {
							if( $sdat ) {
								fclose($sdat);
							}
							$sdat = stream_socket_accept( $sserver );
						}
						else if( $sdat && in_array( $sdat, $reads, true) ) {
							if( feof( $sdat ) ) {
								fclose($sdat);
								$sdat = NULL ;							
							}
							else {
								$data = fgets($sdat, 20);
								@$dlen = strlen($data);
								if( $data === false || $dlen < 2 || $data[0] != 's' ) {
									fclose($sdat);
									$sdat = NULL ;							
								}
								else {
									$dlen = fread( 
									fclose($sdat);
									$sdat=null;
								}
							}
						}
					}
					else {
						break;
					}
				}

				if( $sdat ) {
					fclose($sdat);
				}
				fclose($sserver);
			}
			
			if( $target )
				fclose($target);
		}
		else {
			header( $_SERVER['SERVER_PROTOCOL']." 404 Not Found" );
			header( "X-Webt: error");
		}
	}
	else if( $_REQUEST['c'] == 's' ) {		// send data
		$conn = false ;
		$tport = 0 ;
		if( !empty($_REQUEST['t'] ) ) {
			$tport = (int)$_REQUEST['t']			
			$conn = stream_socket_client("tcp://localhost:".$tport );
		}

		$inputdata = fopen("php://input", "r");
		if( $inputdata ) {
			// transfer data 
			while( !feof($inputdata) && ($data = fgets( $inputdata, 20 )) ) {
				$sl = 0 ;
				if( strlen($data)>2 ) {
					if(  $data[0] == 'p' ) {	// port
						$np = (int) substr( $sl, 1 ) ;
						if( $np != $tport ) {
							if( $conn ) {
								fclose($conn);
							}
							$tport = $np ;
							$conn = stream_socket_client("tcp://localhost:".$tport );
						}
						continue ;
					}
					else if( $data[0] == 's' ) {	// size
						$sl = (int) substr( $data, 1 );
					}
					else {
						break;	// error!
					}
				}
				else {
					break;
				}
				
				if( $conn ) {
					fwrite( 's'.$sl."\n" );
					while( $sl > 0 ) {
						$data =  fread($inputdata, $sl) ;
						@$dlen = strlen($data);
						if( $data === false || $dlen == 0 ) {		// eof
							break;
						}
						$w=0;
						while( $w < $dlen ) {
							$w += fwrite( $conn, substr($data,$w) );
						}
						$sl -= $dlen ;
					}
				}
			}
			fclose($inputdata);
		}
		if( $conn )
			fclose($conn);
	}
}

return ;
?>