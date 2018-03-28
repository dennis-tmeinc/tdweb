<?php
// mp4preview.php - mp4 preview video data
// Request:
//      index : video clip id
// Return:
//      video file data
// By Dennis Chen @ TME	 - 2013-02-14
// Copyright 2013 Toronto MicroElectronics Inc.
//

include_once 'vfile.php' ;

function fcmp($a, $b)
{
	return $a['stat']['atime'] - $b['stat']['atime'] ;
}

function mp4cache_clear( $pattern )
{
	$tnow = time();
	$flist = array();
	$cachesize = 0 ;
	foreach( vfile_glob($pattern) as $filename) {
		$st = vfile_stat( $filename );
		if( $st && $st['size'] ) {
			if( $st['mtime'] > $st['atime'] ) {
				$st['atime'] = $st['mtime'] ;		// noatime fs fix
			}
			if( $tnow - $st['atime'] > 7*24*60*60 ) {
				@vfile_unlink( $filename );
			}
			else {
				$cachesize += $st['size'] ;
				$flist[] = array(
					'filename' => $filename,
					'stat' => $st ,
					'a' => true 
				);
			}
		}
	}

	usort($flist, "fcmp");		
	while( !empty($flist) && $cachesize > 2000000000 ) {
		if( $p = array_pop( $flist ) ) {
			@vfile_unlink( $p['filename'] );
			$cachesize -= $p['stat']['size'] ;
		}
		else {
			break;
		}
	}
}

function outputvideo( $index )
{
	global $conn ;
	global $cache_dir ;
	
	header("Content-Type: video/mp4");	
	
	$sql = "SELECT * FROM videoclip WHERE `index` = $index ;" ;

	if($result=$conn->query($sql)) {
		if( $row=$result->fetch_array() ) {
			if( vfile_realpath( "/" ) == "/" ) {
				$dirc = "/" ;
			}
			else {
				$dirc = "\\" ;
			}

			$hash = md5($row['path']);

			if( !empty($row['mp4_path']) && vfile_exists( $row['mp4_path'] ) ) {
				$preview_file = $row['mp4_path'] ;
			}
			else {

				$preview_file = $cache_dir.$dirc."v".$hash.".mp4" ;
				$preview_lockfile = session_save_path().'/sess_lock'.$hash ;
			
				// exclude other process do the converting
				$lockf = fopen( $preview_lockfile, "c" );
				if( $lockf ) {
					flock( $lockf, LOCK_EX ) ;		// exclusive lock

					$preview_tmpfile = $cache_dir.$dirc."t".$hash.".mp4" ;

					if( !vfile_exists( $preview_file ) ) {
						// clear cache
						mp4cache_clear( $cache_dir.$dirc."*" ) ;
						
						// convert
						set_time_limit(200) ;
						$ifile = $row['path'] ;
						$conv266="bin\\conv266\\conv266.exe" ;
						if( !empty($use_conv266) && vfile_exists( $conv266 ) ) {
							$cmdline = "$conv266 -f \"$preview_tmpfile\" -i \"$ifile\"" ;
						}
						else {
							$cmdline = "bin\\ffmpeg.exe -i \"$ifile\" -y -codec:v copy \"$preview_tmpfile\" " ;
						}

						vfile_exec( $cmdline );
						vfile_rename( $preview_tmpfile, $preview_file );
					}

					flock( $lockf, LOCK_UN ) ;		// unlock ;
					fclose( $lockf );
				}

			}

			if( !empty( $preview_file ) ) {
				$vstat = vfile_stat( $preview_file ) ;
			}

			// enable cache 
			if( !empty($vstat['mtime']) ) {
				// remove header "Pragma: no-cache" 
				header_remove('Pragma');
				$expires=24*3600;		// expired in 1 day
				header('Cache-Control: public, max-age='.$expires);
				$lastmodtime = gmdate('D, d M Y H:i:s ', $vstat['mtime']).'GMT';
				$etag = hash('md5', $hash.$vstat['mtime'].$vstat['size'] );
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
				if( empty( $mp4preloadsize ) ) {
					$mp4preloadsize = 96000 ;		// 96 K
				}
				
				$partial = false ;
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
					if( $offset<0 ) $offset = 0 ;
					$len = $lastpos + 1 - $offset ;
					$partial = true ;
				}
				else {
					$len = $fs ;
					$offset = 0 ;
					// only to output partial contents
					if( $len > $mp4preloadsize ) {
						$len = $mp4preloadsize ;
						$partial = true ;
					}
				}

				if( $len>0 ) {
					if( $partial ){
						$lastpos = $offset + $len - 1 ;	
						header( "HTTP/1.1 206 Partial Content" );
						header( sprintf("Content-Range: bytes %d-%d/%d", $offset, $lastpos, $fs ));
						header("Content-Length: $len" );
					}
					
					// header("Connection: close");
					vfile_seek( $f, $offset );
					
					@ob_end_flush();

					while( $len > 0 ) {
						set_time_limit(10);
						if( connection_aborted () ) break;

						$r = $len ;
						if( $r > 32*1024 ) {
							$r = 32*1024 ;
						}
						$da = vfile_read( $f, $r ) ;
						if( strlen( $da ) > 0 ) {
							echo $da ;
							flush();
							$len -= $r ;

							if( $len>0 )
								usleep(30000);				// throttle download speed
						}
						else {
							break;
						}
					}
				}
				
				vfile_close( $f );
			}
		}
		$result->free();
	}
}

?>