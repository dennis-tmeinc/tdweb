<?php
// emgvideo.php - video(mp4) file reader for emergency event
// Request:
//      tag : (idx) drive tag file name
//      channel : channel name
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
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql = "SELECT * FROM emg_event WHERE `idx` = $_REQUEST[tag] " ;
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$channels = new SimpleXMLElement( "<emg>" . $row['Video_Files'] . "</emg>" );
				$ch=0 ;
				if( !empty( $_REQUEST['channel'] ) ) {
					for( $i=0; $i<count($channels->channel); $i++ ) {
						if( $_REQUEST['channel'] == $channels->channel[$i]->name ) {
							$ch = $i ;
							break;
						}
						if( $_REQUEST['channel'] == ('camera'.($i+1)) ) {
							$ch = $i ;
							break;
						}
					}
				}
					
				if( !empty( $channels->channel[$ch]->video )) {
					$videofile = $channels->channel[$ch]->video ;
				}
			}
			$result->free();
		}
	}

	
	if( !empty( $videofile ) ) {
		$vstat = vfile_stat( $videofile ) ;
	}
	
	if( !empty( $vstat['size'] ) ) {
		$fsize = $vstat['size'] ;
		
		// enable cache 
		$expires=24*3600;		// expired in 1 day
		header('Cache-Control: public, max-age='.$expires);
		$lastmodtime = gmdate('D, d M Y H:i:s ', $vstat['mtime']).'GMT';
		$etag = hash('md5', $videofile.$fsize.$vstat['mtime'] );
		header('Expires: '.gmdate('D, d M Y H:i:s ', $_SERVER['REQUEST_TIME']+$expires).'GMT');
		if( (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==$etag ) ) {
			header("HTTP/1.1 304 Not Modified");
			die;
		}
		header('Etag: '.$etag);
		header('Last-Modified: '.$lastmodtime);
	
		$f = vfile_open( $videofile, 'rb' ) ;
		if( $f ) {
			if( !empty( $_SERVER['HTTP_RANGE'] ) ) {
				$range = sscanf($_SERVER['HTTP_RANGE'] , "bytes=%d-%d");
				if( empty( $range[0] ) ) {
					$startpos = 0 ;
				}
				else {
					$startpos = $range[0] ;
				}
				if( empty($range[1]) ) {
					$lastpos = $fsize - 1 ;
				}
				else {
					$lastpos = $range[1] ;
				}
				$len = $lastpos - $startpos + 1 ;
				vfile_seek( $f, $startpos );
				if( $len != $fsize || $startpos != 0 ) {
					header( "HTTP/1.1 206 Partial Content" );
					header( "Content-Range: bytes $startpos-$lastpos/$fsize" );
				}
			}
			else {
				$len = $fsize ;
			}

			header( "Accept-Ranges: bytes" );
			header( "Content-Length: $len" );

			while( $len > 0 ) {
				set_time_limit(30);
				$r = $len ;
				if( $r > 64*1024 ) {
					$r = 64*1024 ;
				}
				$da = vfile_read( $f, $r ) ;
				if( strlen( $da ) > 0 ) {
					echo $da ;
					$len -= $r ;
					if( connection_aborted () ) break;
				}
				else {
					break;
				}
			}
			
			vfile_close( $f );
		}
	}
?>