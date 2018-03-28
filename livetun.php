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
	else if( $_REQUEST['c'] == 't' && !empty($_REQUEST['u']) ) {		// RCURL
		$target = stream_socket_client( $_REQUEST['u'] );
		if( $target ) {
			if( isset( $_REQUEST['d'] ) && strlen( $_REQUEST['d'] )>0 ) {
				fwrite( $target, $_REQUEST['d']);
			}
			
			$ssend = stream_socket_server("tcp://localhost:0");
			if( $ssend ) {
				$sport = parse_url( "tcp://" .stream_socket_get_name($ssend, false) )['port'] ;
				header( "X-Webt: ready");
				header( "X-Webt-Id: $sport");
				_output( "p$sport" );
				
				$sdat = NULL ;
				while( !feof( $target ) ){
					$reads = array( $target, $ssend );
					if( $sdat ) {
						$reads[] = $sdat ;
					}
					$writes = NULL ;
					$exs = NULL ;
					set_time_limit(200) ;
					if( stream_select($reads, $writes, $exs, 180)>0 ) {
						if( in_array( $target, $reads, true) ) {
							$data = fread( $target, 100000 ) ;
							if( $data===false || strlen($data)==0 ) {
								// error ? or end of stream
								break ;
							}
							else {
								_output( $data );
							}
						}
						
						if( $sdat && in_array( $sdat, $reads, true) ) {
							if( feof( $sdat ) ) {
								fclose($sdat);
								$sdat = NULL ;							
							}
							else {
								$data = net_readpack( $sdat );
								if( strlen($data)>0 ) {
									fwrite( $target, $data );
								}
								else {
									fclose($sdat);
									$sdat = NULL ;
								}
							}
						}

						if( in_array( $ssend, $reads, true) ) {
							if( $sdat ) {
								fclose($sdat);
							}
							$sdat = stream_socket_accept( $ssend );
						}

					}
					else {
						break;
					}
				}

				if( $sdat ) {
					fclose($sdat);
				}				
				fclose($ssend);
			}
			
			fclose($target);
		}
		else {
			header( "X-Webt: error");
		}
	}
	else if( $_REQUEST['c'] == 's' && !empty($_REQUEST['p']) ) {		// send data
		$conn = stream_socket_client("tcp://localhost:".$_REQUEST['p']);
		if( $conn ) {
			$err = false ;
			if( isset( $_REQUEST['d'] ) && strlen( $_REQUEST['d'] )>0 ) {
				if( !net_sendpack( $conn, $_REQUEST['d'] ) ) {
					$err = true ;
				}
			}
			
			$inputdata = fopen("php://input", "r");
			if( $inputdata ) {
				// transfer data 
				while( !feof($inputdata) && ($sl = fgets( $inputdata, 100 )) ) {
					if( strlen($sl)<3 || $sl[0] != 's' ) {
						break ;
					}
					$sl = (int) substr( $sl, 1 );
					if( $sl > 0 ) {
						$data = '' ;
						while( $sl > 0 ) {
							$rdata =  fread($inputdata, $sl) ;
							@$rlen = strlen($rdata);
							if( $rdata === false || $rlen == 0 ) {		// eof
								$err = true ;
								break;
							}
							$data .= $rdata ;
							$sl -= $rlen ;
						}
						if( $err || !net_sendpack( $conn, $data ) ) {
							$err = true ;
							break;
						}
					}
				}
				fclose($inputdata);
			}
			fclose($conn);
			if( $err ) {
				header( "X-Webt: error");
				header( "X-Webt-Connection: close" );
			}
		}
		else {
			header( "X-Webt-Connection: close" );
		}
	}
	else if( $_REQUEST['c'] == 'u' && !empty($_REQUEST['u']) ) {		// U CURL
		@$target = stream_socket_client( $_REQUEST['u'] );
		if( !empty( $target ) ) {
			if( isset( $_REQUEST['d'] ) && strlen( $_REQUEST['d'] )>0 ) {
				fwrite( $target, $_REQUEST['d']);
			}
			
			$ssend = stream_socket_server("udp://localhost:0", $errno, $errstr, STREAM_SERVER_BIND );
			if( $ssend ) {
				$sport = parse_url( "udp://" .stream_socket_get_name($ssend, false) )['port'] ;
				header( "X-Webt: ready");
				header( "X-Webt-Id: $sport");
				_output( "p$sport" );
				
				while( !feof( $target ) ){
					$reads = array( $target, $ssend );
					$writes = NULL ;
					$exs = NULL ;
					set_time_limit(200) ;
					if( stream_select($reads, $writes, $exs, 180)>0 ) {
						if( in_array( $target, $reads, true) ) {
							$data = fread( $target, 100000 ) ;
							if( strlen($data)>0 ) {
								_output( $data );
							}
						}

						if( in_array( $ssend, $reads, true) ) {
							$data = fread( $ssend, 100000 ) ;
							if( strlen($data)>0 ) {
								fwrite( $target, $data );
							}
							else {
								break ;
							}
						}
					}
					else {
						break;
					}
				}
				fclose($ssend);
			}
			
			fclose($target);
		}
		else {
			header( "X-Webt: error");
		}
	}
	else if( $_REQUEST['c'] == 'z' ) {		// s u data
		if( !empty($_REQUEST['p']) ) {
			$p=(int)$_REQUEST['p'] ;
			$conn = stream_socket_client("udp://localhost:$p");
		}
		$err = false ;
		if( !empty($conn) && isset( $_REQUEST['d'] ) && strlen( $_REQUEST['d'] )>0 ) {
			fwrite( $conn, $_REQUEST['d'] );
		}
			
		$inputdata = fopen("php://input", "r");
		if( $inputdata ) {
			// transfer data 
			while( !feof($inputdata) && ($l = fgets( $inputdata, 100 )) ) {
				if( strlen($l)<3 ) {
					break ;
				}
				$sl = 0 ;
				if( $l[0] == 'p' ) {
					if( !empty($conn) )
						fclose( $conn );
					$p=(int)substr( $l, 1 );
					$conn = stream_socket_client("udp://localhost:$p");
				}
				else if( $l[0] == 's' ) {
					$sl = (int) substr( $l, 1 );
				}
				else {
					break ;
				}
				if( $sl > 0 ) {
					$data = '' ;
					while( $sl > 0 ) {
						$rdata =  fread($inputdata, $sl) ;
						@$rlen = strlen($rdata);
						if( $rdata === false || $rlen == 0 ) {		// eof
							$err = true ;
							break;
						}
						$data .= $rdata ;
						$sl -= $rlen ;
					}
					if( !$err && strlen($data)>0 && !empty($conn) ) {
						fwrite( $conn, $data );
					}
				}
			}
			fclose($inputdata);
		}

		if( !empty($conn) )
			fclose($conn);
		if( $err ) {
			header( "X-Webt: error");
		}
	}	
}

return ;
?>