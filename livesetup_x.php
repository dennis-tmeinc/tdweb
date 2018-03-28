<?php
require 'session.php'; 

if( !empty( $_SERVER['PATH_INFO'] ) ) {
	$p = strpos( $_SERVER['PATH_INFO'], '/', 1 );
	if( $p > 0 ) {
		$phone = substr( $_SERVER['PATH_INFO'], 1, $p-1 );
		$nreq = substr(  $_SERVER['PATH_INFO'], $p );
		
		if(!empty($_SERVER['QUERY_STRING'])) {
			$nreq.='?'.$_SERVER['QUERY_STRING'] ;
		}

	}
}
if( empty( $phone ) || empty( $nreq) ) {
    echo "<html><body>Invalid Request !</body></html>" ;
	return ;
}

if( !$logon ) {
	header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); 
    echo "<html><body>Session Expired!</body></html>" ;
	return ;
}

if( $_SESSION['user_type'] != "admin" ) {
	header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request"); 
    echo "<html><body>Admin account required</body></html>" ;
	return ;
}

// repack http header
$_SERVER['HTTP_CONNECTION'] = "close" ;		// no keep alive 
$_SERVER['HTTP_HOST'] = "127.0.0.1" ;		// replace Host header

$header = $_SERVER['REQUEST_METHOD'].' '.$nreq.' '.$_SERVER['SERVER_PROTOCOL']."\r\n" ;
foreach( $_SERVER as $key => $value )
{
	if( strncmp( $key, "HTTP_", 5 ) == 0 ) {
		$l = strlen( $key ) ;
		$xkey = '' ;
		$cap = true ;
		for( $i=5 ; $i<$l; $i++ ) {
			$c = $key[$i] ;
			if($c=='_') {
				$xkey .='-' ;
				$cap = true ;
			}
			else if( $cap ) {
				$xkey .= $c ;
				$cap = false ;
			}
			else {
				$xkey .= strtolower( $c );
			}
		}
		$header .= $xkey . ': '. $value . "\r\n" ;
	}
}

$wserver= false ;
$wport = 0 ;

// web listener on random tcp port 10000 ~ 55000
while( !$wserver) {
	$wport = mt_rand( 10000, 55000 ) ;
	$wserver= stream_socket_server("tcp://127.0.0.1:".$wport, $errno, $errstr);
}

$tunnelid = $wport ;

$nsvrfile = $session_path.'/sess_lvr_'.$tunnelid ;
$wsvrfile = fopen( $nsvrfile , "w" );
if( $wsvrfile ) {
	fprintf( $wsvrfile, "%d\r\n", $wport );
	fclose( $wsvrfile );
}

//$rtime = time();
$rtime = $xt ;			// current time

$flvc = fopen( $session_path.'/sess_lvc_'.$phone, "r+" );
if( $flvc ) {
	flock( $flvc, LOCK_EX ) ;		// exclusive lock

	fseek( $flvc, 0, SEEK_END );
	$x = fwrite( $flvc, "$rtime,$tunnelid,127.0.0.1,80\n");

	fflush( $flvc ) ;              	// flush before release the lock
	flock( $flvc, LOCK_UN ) ;		// unlock ;
	fclose( $flvc );
}
else {
	echo "<html><body>Sorry, contents not available</body></html>" ;
	return ;
}

set_time_limit( 100 );
// wait for id listner
$timeout = 30 ;

$recv_header = true ;
$xline = '' ;
$content_length = 1000000000 ;	// set to an impossible max content length
while( $conn = stream_socket_accept($wserver, $timeout ) ) {
	$msgtype = fread( $conn, 1 ) ;
	if( $msgtype == 'd' ) {			// data incoming
		while( true ) {
			if( $recv_header ) {
				$line = fgets( $conn, 8192 ) ;
				if( $line === false || strlen( $line ) == 0 ) {
					break ;
				}
				$xline .= $line ;
				if( strstr( $xline, "\n" ) ) {		// completed line with eol
					$xline = trim( $xline );
					if( strlen( $xline ) == 0 ) {	// empty line ? end of http header
						$recv_header = false ;		// to receive contents
					}
					else {
						// try to get content length
						if( strncasecmp( $xline, "Content-Length:", 15)==0 ) {
							$content_length = (int)substr($xline, 15);
						}
						header( $xline );
						$xline = '' ;				// clean line buffer
					}
				}
			}
			else {
				// contents, transfer data back to browser
				$rl = $content_length ;
				if( $rl>128*1024 )$rl=128*1024 ;
				$data = fread( $conn, $rl );
				if( $data === false || strlen($data)==0 ) {
					$timeout = 15 ;
					break;
				}
				echo $data ;
				$content_length -= strlen($data);
				if( $content_length <= 0 ) {		// content completed
					$timeout = 0 ;
					break;
				}
			}
		}
	}
	else if( $msgtype == 'g' ) {		// GET data
		if( !empty( $header ) ) {		// to send header
			fwrite( $conn, 'd' );		// data coming
			

			// headers
			fwrite( $conn, $header );

			// Content-length
			$length = 0 ;
			if( isset( $_SERVER['CONTENT_LENGTH'] ) ) {
				$length = (int) $_SERVER['CONTENT_LENGTH'] ;
			}
			
			$multipart_boundary = '' ;

			// Content-Type, recalculate content length
			if( isset( $_SERVER['CONTENT_TYPE'] ) ) {
				// pack multipart/form-data
				if( strncasecmp($_SERVER['CONTENT_TYPE'], "multipart/form-data", 19 )== 0 ) {
					$multipart_boundary = stristr( $_SERVER['CONTENT_TYPE'], "boundary=" );
					if( !empty( $multipart_boundary ) ) {
						$multipart_boundary = trim(substr( $multipart_boundary, 9 ));
						// recalculate content size base on multipart/formdata
						$length =0 ;
						
						// POST array
						if( !empty( $_POST ) )
						foreach ( $_POST as $key => $value ) {
							// begin of boundary
							$length += 4+strlen($multipart_boundary) ; 	//fwrite($conn, "--$multipart_boundary\r\n" );
							$length += 43+strlen($key);             	//fwrite($conn, "Content-Disposition: form-data; name=\"$key\"\r\n\r\n" );
							$length += strlen($value);					//fwrite($conn, $value);
							$length += 2 ;								//fwrite($conn, "\r\n");
						}

						// FILES array, ex:
						// -----------------------------22350430211355483321311962510 
						// Content-Disposition: form-data; name="xfile"; filename="app.yaml" 
						// Content-Type: application/x-yaml
						if( !empty( $_FILES ) )
						foreach ( $_FILES as $key => $value ) {
							// begin of boundary
							$length += 4+strlen($multipart_boundary) ;			//fwrite($conn, "--$multipart_boundary\r\n" );
							$length += 54+strlen($key)+strlen($value['name']);	//fwrite($conn, "Content-Disposition: form-data; name=\"$key\"; filename=\"$value[name]\"\r\n" );
							if( !empty( $value['type'] ) ) {
								$length += 	16+strlen($value['type']);			//fwrite($conn, "Content-Type: $value[type]\r\n" );
							}
							$length += 2;										//fwrite($conn, "\r\n");
							$length += $value['size'] ;							// output file contents
							$length += 2;										// fwrite($conn, "\r\n");
						}

						// end boundary
						$length += 6+strlen($multipart_boundary);				//fwrite($conn, "--$multipart_boundary--\r\n" );
					}
				}
				
				fwrite($conn,  "Content-Type: ".$_SERVER['CONTENT_TYPE']."\r\n" );
			}

			if( $length > 0 ) {
				fwrite( $conn, "Content-Length: ".$length."\r\n" );
			}
			fwrite( $conn, "\r\n" );	// empty line for end of http header
			unset( $header );
			
			// contents
			if( !empty( $multipart_boundary ) ) {		// multipart
			
				// POST array
				if( !empty( $_POST ) )
				foreach ( $_POST as $key => $value ) {
					// begin of boundary
					fwrite($conn, "--$multipart_boundary\r\n" );
					fwrite($conn, "Content-Disposition: form-data; name=\"$key\"\r\n\r\n" );
					fwrite($conn, $value);
					fwrite($conn, "\r\n");
				}

				// FILES array, ex:
				// -----------------------------22350430211355483321311962510 
				// Content-Disposition: form-data; name="xfile"; filename="app.yaml" 
				// Content-Type: application/x-yaml
				if( !empty( $_FILES ) )
				foreach ( $_FILES as $key => $value ) {
					// begin of boundary
					fwrite($conn, "--$multipart_boundary\r\n" );
					fwrite($conn, "Content-Disposition: form-data; name=\"$key\"; filename=\"$value[name]\"\r\n" );
					if( !empty( $value['type'] ) ) {
						fwrite($conn, "Content-Type: $value[type]\r\n" );
					}
					fwrite($conn, "\r\n");
					// output file contents
					$uploadcontents = file_get_contents ( $value['tmp_name'] );
					fwrite($conn, $uploadcontents);
					unset( $uploadcontents );	// to release the memory
					fwrite($conn, "\r\n");
				}

				// end boundary
				fwrite($conn, "--$multipart_boundary--\r\n" );
				
			}
			else if( $length > 0 ) {
				$inputdata = fopen("php://input", "r");
				if( $inputdata ) {
					while( $length > 0 ) {
						$rl = $length ;
						if( $rl > 32*1024 ) $rl = 32*1024 ;
						$data = fread( $inputdata, $rl ) ;
						if( $data === false || strlen($data)==0 ) {
							break;
						}
						fwrite( $conn, $data );
						$length -= strlen( $data );
					}
					fclose( $inputdata );
				}
			}
		}
		else {
			fwrite( $conn, 'e' );	// end, no more GET data
		}
	}
	else if( $msgtype == 'n' ) {	// notification of get req ready
		if( !empty( $header ) ) {		// to send header
			$sendfile = fopen( $session_path.'/sess_lvs_'.$tunnelid, "r" );
			if( $sendfile ) {
				$sendfileport = 0 ;
				fscanf( $sendfile, "%d", $sendfileport );
				if( $sendfileport ) {
					$sendsocket = stream_socket_client( "tcp://127.0.0.1:".$sendfileport );
					if( $sendsocket ) {
						fwrite( $sendsocket, "d" );		// message type: data
						fwrite( $sendsocket, $header );

						// Content-length
						$length = 0 ;
						if( isset( $_SERVER['CONTENT_LENGTH'] ) ) {
							$length = (int) $_SERVER['CONTENT_LENGTH'] ;
							fwrite( $sendsocket, "Content-Length: ".$length."\r\n" );
						}
						
						fwrite( $sendsocket, "\r\n" );	// empty line for end of http header
						unset( $header );
						
						// contents
						if( $length > 0 ) {
							
							$inputdata = fopen("php://input", "r");
							if( $inputdata ) {
								while( $length > 0 ) {
									$rl = $length ;
									if( $rl > 128*1024 ) $rl = 128*1024 ;
									$data = fread( $inputdata, $rl ) ;
									if( $data === false || strlen($data)==0 ) {
										break;
									}
									else {
										fwrite( $sendsocket, $data );
										$length -= strlen( $data );
									}
								}
								fclose( $inputdata );
							}
						}

						fclose( $sendsocket );
					}
				}
				fclose( $sendfile );
			}
		}
	}
	else if( $msgtype == 'e' ) {	// close connection (end)
		$timeout = 0;				// quit !
	}

	fclose( $conn ) ;

}

// clear listener
fclose($wserver);

// remove server file
@unlink( $nsvrfile );

// notify sender to close connection
$sendfile = fopen( $session_path.'/sess_lvs_'.$tunnelid, "r" );
if( $sendfile ) {
	flock( $sendfile, LOCK_EX ) ;		// exclusive lock
	
	$sendfileport = 0 ;
	fscanf( $sendfile, "%d", $sendfileport );
	if( $sendfileport ) {
		$sendsocket = stream_socket_client( "tcp://127.0.0.1:".$sendfileport );
		if( $sendsocket ) {
			fwrite( $sendsocket, "e" );		// message type: end of connection
			fclose( $sendsocket );
		}
	}
	
	flock( $sendfile, LOCK_UN ) ;		// unlock 
	fclose( $sendfile );
}

?>