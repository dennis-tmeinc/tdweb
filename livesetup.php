<?php
// livesetup.php - live setup support over web tunnel 
// Requests:
//      DVR setup url :  ex, http://host/tdc/livesetup.php/<phonenumber>/page.html
// Return:
//      tunneled data (web page)
// By Dennis Chen @ TME	 - 2016-11-18
// Copyright 2016 Toronto MicroElectronics Inc.

require_once 'session.php' ; 
require_once 'webtunstream.php' ;

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

$stream = fopen("webtun://$phone:80", "c") ;
if( $stream ) {

	register_shutdown_function(function ()
	{
		global $stream ;
		if( $stream ) {
			fclose( $stream );
		} 
		$stream = NULL ;
		
	});
	
	// send request
	fwrite( $stream,  $_SERVER['REQUEST_METHOD'].' '.$nreq.' '.$_SERVER['SERVER_PROTOCOL']."\r\n" ) ;

	$_SERVER['HTTP_CONNECTION'] = "close" ;		// no keep alive 
	$_SERVER['HTTP_HOST'] = "127.0.0.1" ;		// replace Host header

	// headers
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
			fwrite( $stream,  $xkey . ': '. $value . "\r\n"  );
		}
	}

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
		
		fwrite($stream,  "Content-Type: ".$_SERVER['CONTENT_TYPE']."\r\n" );
	}

	if( $length > 0 ) {
		fwrite( $stream, "Content-Length: ".$length."\r\n" );
	}
	fwrite( $stream, "\r\n" );	// empty line for end of http header
	
	// contents
	if( !empty( $multipart_boundary ) ) {		// multipart
	
		// POST array
		if( !empty( $_POST ) )
		foreach ( $_POST as $key => $value ) {
			// begin of boundary
			fwrite($stream, "--$multipart_boundary\r\n" );
			fwrite($stream, "Content-Disposition: form-data; name=\"$key\"\r\n\r\n" );
			fwrite($stream, $value);
			fwrite($stream, "\r\n");
		}

		// FILES array, ex:
		// -----------------------------22350430211355483321311962510 
		// Content-Disposition: form-data; name="xfile"; filename="app.yaml" 
		// Content-Type: application/x-yaml
		if( !empty( $_FILES ) )
		foreach ( $_FILES as $key => $value ) {
			// begin of boundary
			fwrite($stream, "--$multipart_boundary\r\n" );
			fwrite($stream, "Content-Disposition: form-data; name=\"$key\"; filename=\"$value[name]\"\r\n" );
			if( !empty( $value['type'] ) ) {
				fwrite($stream, "Content-Type: $value[type]\r\n" );
			}
			fwrite($stream, "\r\n");
			
			// output file contents
			$uploadfile = fopen($value['tmp_name'],"r");
			if( $uploadfile ) {
				$uploadsize = $value['size'] ;
				while( $uploadsize > 0 && connection_status()==CONNECTION_NORMAL ) {
					set_time_limit( 200 );
					if( $uploadsize > 4096 )
						$data = fread( $uploadfile, 4096 );
					else
						$data = fread( $uploadfile, $uploadsize );
					@$dlen = strlen( $data );
					if( $data === false || $dlen==0 ) {
						break;
					}
					fwrite( $stream, $data );
					$uploadsize -= $dlen;
				}
				fclose($uploadfile);
			}
			
			fwrite($stream, "\r\n");
		}

		// end boundary
		fwrite($stream, "--$multipart_boundary--\r\n" );
		
	}
	else if( $length > 0 ) {
		$inputdata = fopen("php://input", "r");
		if( $inputdata ) {
			while( $length > 0 && connection_status()==CONNECTION_NORMAL ) {
				set_time_limit( 200 );
				if( $length > 8192 )
					$data = fread( $inputdata, 8192 );
				else
					$data = fread( $inputdata, $length );
				@$dlen = strlen( $data );
				if( $data === false || $dlen == 0 ) {
					break;
				}
				fwrite( $stream, $data );
				$length -= $dlen;
			}
			fclose( $inputdata );
		}
	}
	
	// to enable cache
	header_remove("Pragma"); 
	// receive header first
	$recv_header = true ;
	// default with no content length, set to max
	$content_length = 1000000000 ;	
		
	// not necessary
	// fflush($stream);
	
	// get response
	while( $content_length>0 && connection_status()==CONNECTION_NORMAL ) {
		if( $recv_header ) {
			$line = fgets( $stream, 4096 ) ;
			if( $line === false || strlen( $line ) == 0 ) {
				break ;
			}
			$line = trim( $line );	
			if( strlen( $line ) == 0 ) {	// empty line, end of http header
				$recv_header = false ;		// to receive contents
			}
			else {
				// try to get content length
				if( strncasecmp( $line, "Content-Length:", 15)==0 ) {
					$content_length = (int)substr($line, 15);
				}
				header( $line );
			}
		}
		else {
			// contents, transfer data back to browser
			set_time_limit( 200 );
			if( $content_length>8192 ) {
				$data = fread( $stream, 8192 );
			}
			else {
				$data = fread( $stream, $content_length );
			}
			@$dlen = strlen( $data );
			if( $data === false || $dlen==0 ) {
				break;
			}
			echo $data ;
			$content_length -= $dlen;
		}
	}
    $stream = NULL;
}
else {
	echo "<html><body>Sorry, contents not available</body></html>" ;
}

return ;
?>