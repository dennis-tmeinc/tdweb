<?php
// mp4preview.php - mp4 preview video data
// Request:
//      index : video clip id
// Return:
//      video file data
// By Dennis Chen @ TME	 - 2013-02-14
// Copyright 2013 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require_once 'vfile.php' ;

	header("Content-Type: video/mp4");	

	function fcmp($a, $b)
	{
		return $a['stat']['atime'] - $b['stat']['atime'] ;
	}

	function mp4cache_clear( $pattern )
	{
		$cachesize = 0 ;
		$remaindays = 10 ;
		$tnow = time();
		$flist = array();
		$cachesize = 0 ;
		foreach( vfile_glob($pattern) as $filename) {
			$st = vfile_stat( $filename );
			if( $st['atime'] + 8*60*60 < $tnow ) {
				$ft = array();
				$ft['filename'] = $filename ;
				$ft['stat'] = $st ;
				$cachesize += $st['size'] ;
				$ft['a'] = true ;
				$flist[] = $ft ;
			}
		}
		usort($flist, "fcmp");
		
		while( $flist && $cachesize > 2000000000 ) {
			if( $p = array_pop( $flist ) ) {
				vfile_unlink( $p['filename'] );
				$cachesize -= $p['stat']['size'] ;
			}
			else {
				break;
			}
		}
	}
	
	if( $logon ) {

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		$sql = "SELECT `path` FROM videoclip WHERE `index` = $_REQUEST[index] ;" ;

		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array() ) {
				$hash = md5($row['path']);

				if( vfile_realpath( "/" ) == "/" ) {
					$dirc = "/" ;
				}
				else {
					$dirc = "\\" ;
				}

				$vcachedir = "videocache" ;
				
				$preview_file = $vcachedir.$dirc."v".$hash.".mp4" ;
				$preview_tmpfile = $vcachedir.$dirc."t".$hash.".mp4" ;
				$preview_file_pattern = $vcachedir.$dirc."*.mp4" ;
				$preview_lockfile = session_save_path().$dirc.'sess_lock'.$hash ;
				
				if( vfile_size( $preview_file ) < 100 ) {
				
					// exclude other process do the converting
					$lockf = fopen( $preview_lockfile, "c" );
					if( $lockf ) {
						flock( $lockf, LOCK_EX ) ;		// exclusive lock

						if( !vfile_exists( $preview_file ) ) {
							// clear cache
							mp4cache_clear( $preview_file_pattern ) ;
							
							// convert
							set_time_limit(200) ;
							$cmdline = "bin\\ffmpeg.exe -i ".$row['path']." -y -codec:v copy ".$preview_tmpfile ;
							if( $fsvr = vfile_remote() ) {
								vfile_readhttp( $fsvr."?c=e&n=".rawurlencode($cmdline) ) ;
							}
							else {
								exec( escapeshellcmd($cmdline) ) ;
							}
							vfile_rename( $preview_tmpfile, $preview_file );
						}

						flock( $lockf, LOCK_UN ) ;		// unlock ;
						fclose( $lockf );
					}
	
				}
				else {
					// lock file are safe to be removed
					if( file_exists( $preview_lockfile ) ) 
						unlink( $preview_lockfile );
				}

				if( !empty( $preview_file ) ) {
					$vstat = vfile_stat( $preview_file ) ;
				}
	
				// enable cache 
				if( !empty($vstat['mtime']) ) {
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
				}
				
				if( $f = vfile_open( $preview_file ) ) {
					header( "Accept-Ranges: bytes" );
					vfile_seek( $f, 0, SEEK_END );
					$fs = vfile_tell( $f );

					if( !empty( $_SERVER['HTTP_RANGE'] ) ) {
						$range = sscanf($_SERVER['HTTP_RANGE'] , "bytes=%d-%d");
						if( empty($range[1]) ) {
							// max len
							$lastpos = $fs - 1 ;
						}
						else {
							$lastpos = $range[1] ;
						}
						$offset = $range[0] ;
						$len = $lastpos + 1 - $offset ;
						header("Content-Length: $len" );
						header( sprintf("Content-Range: bytes %d-%d/%d", $offset, $lastpos, $fs ));
						header( "HTTP/1.1 206 Partial Content" );
					}
					else {
						$len = $fs ;
						$offset = 0 ;
						header("Content-Length: $len" );
						// test for only return partial contents
						if( empty( $mp4preloadsize ) ) {
							$mp4preloadsize = 102400 ;		// 100 K
						}
						if( $mp4preloadsize < $fs ) {
							$len = $mp4preloadsize ;
							$lastpos = $len - 1 ;	
							header( sprintf("Content-Range: bytes %d-%d/%d", $offset, $lastpos, $fs ));
							header( "HTTP/1.1 206 Partial Content" );								
						}
					}

					if( $len>0 ) {
						// header("Connection: close");
						vfile_seek( $f, $offset );

						while( $len > 0 ) {
							set_time_limit(30);
							$r = $len ;
							if( $r > 256*1024 ) {
								$r = 256*1024 ;
							}
							$da = vfile_read( $f, $r ) ;
							if( strlen( $da ) > 0 ) {
								echo $da ;
								if( connection_aborted () ) break;
								$len -= $r ;
							}
							else {
								break;
							}
						}
					}
					else {
						header("Content-Length: 0" );
					}
					
					vfile_close( $f );
				}
			}
			$result->free();
		}
		$conn->close();
	}
?>