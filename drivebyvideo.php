<?php
// drivebyvideo.php - video(mp4) file reader for drive by
// Request:
//      tag : drive tag file name
//      channel : channel number
// Return:
//      mp4 stream
// By Dennis Chen @ TME	 - 2014-1-17
// Copyright 2013 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require_once 'vfile.php' ;
	// Content type
	header("Content-Type: video/mp4");	
	
	if( $logon ) {
	
		$v = vfile_get_contents( $driveby_eventdir.'/'.$_REQUEST['tag'] );
		
		if( !empty($v) ) {

			$x = new SimpleXMLElement( $v );
			if( $x->busid ) {
				$ch=0 ;
				if( !empty( $_REQUEST['channel'] ) ) {
					for( $i=0; $i<count($x->channel); $i++ ) {
						if( $_REQUEST['channel'] == $x->channel[$i]->name ) {
							$ch = $i ;
							break;
						}
						if( $_REQUEST['channel'] == ('camera'.($i+1)) ) {
							$ch = $i ;
							break;
						}
					}
				}
					
				if( !empty( $x->channel[$ch]->video )) {

					$f = vfile_open( $x->channel[$ch]->video, 'rb' ) ;
					if( $f ) {
						header( "Accept-Ranges: bytes" );
						vfile_seek( $f, 0, SEEK_END );
						$fsize = vfile_tell( $f );				
						if( !empty( $_SERVER['HTTP_RANGE'] ) ) {
							$range = sscanf($_SERVER['HTTP_RANGE'] , "bytes=%d-%d");
							if( empty($range[1]) ) {
								// max len
								$lastpos = $fsize - 1 ;
							}
							else {
								$lastpos = $range[1] ;
							}
							$len = $lastpos + 1 - $range[0] ;
							vfile_seek( $f, $range[0] );
							header( "HTTP/1.1 206 Partial Content" );
							header("Content-Length: $len" );
							header( sprintf("Content-Range: bytes %d-%d/%d", $range[0], $lastpos, $fsize ));
						}
						else {
							$len = $fsize ;
							header("Content-Length: $len" );
							vfile_seek( $f, 0 );
						}

						while( $len > 0 ) {
							set_time_limit(30);
							$r = $len ;
							if( $r > 256*1024 ) {
								$r = 256*1024 ;
							}
							$da = vfile_read( $f, $r ) ;
							if( strlen( $da ) > 0 ) {
								echo $da ;
								$len -= $r ;
							}
							else {
								break;
							}
						}
						
						vfile_close( $f );
					}
					
				}
			}
		}
	}
?>