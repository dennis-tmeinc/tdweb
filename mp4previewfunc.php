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
	global $webplay_cache_size;
	
	$tnow = time();
	$flist = array();
	$cachesize = 0 ;
	foreach( vfile_glob($pattern) as $filename) {
		$st = vfile_stat( $filename );
		if( $st && $st['size'] ) {
			if( $st['mtime'] > $st['atime'] ) {
				$st['atime'] = $st['mtime'] ;		// noatime fs fix
			}
			if( $tnow - $st['atime'] > 10*24*60*60 ) {
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
	
	if( empty( $webplay_cache_size ) || $webplay_cache_size < 2000 ) {
		$webplay_cache_size = 2000 ;
	}
	
	while( !empty($flist) && $cachesize > $webplay_cache_size * 1000000 ) {
		if( $p = array_pop( $flist ) ) {
			@vfile_unlink( $p['filename'] );
			$cachesize -= $p['stat']['size'] ;
		}
		else {
			break;
		}
	}
}

// return mp4cached file name 
function mp4cache_load( $index )
{
	global $conn ;
	global $cache_dir ;
	global $smart_server, $smart_user, $smart_password, $smart_database ;
	
	$preview_file = NULL ;
	
	if( empty( $conn ) )
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

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

				$preview_lockfile = session_save_path().'/sess_lock'.$hash ;
			
				// exclude other process do the converting
				$lockf = fopen( $preview_lockfile, "c" );
				if( $lockf ) {
					flock( $lockf, LOCK_EX ) ;		// exclusive lock
					
					if( vfile_remote() ) {
						$preview_dir = "videocache".$dirc ;
					}
					else {
						$preview_dir = $cache_dir.$dirc ;
					}

					$preview_file = $preview_dir."v".$hash.".mp4" ;
					
					if( !vfile_exists( $preview_file ) ) {

						// clear cache
						mp4cache_clear( $preview_dir."*.mp4" ) ;

						// convert
						$ifile = $row['path'] ;
						if( vfile_exists( $ifile ) ){
							set_time_limit(600) ;
							$tmp_mp4 = $preview_dir."t".$hash.".mp4" ;
							$conv266="bin\\conv266\\conv266.exe" ;
							if( !empty( $GLOBALS["use_conv266"] ) && vfile_exists( $conv266 ) ) {
								if( empty($GLOBALS["conv266_text"]) ) {
									$conv266 .= ' -a ' ;
								}
								$cmdline = "$conv266 -i \"$ifile\" -f \"$tmp_mp4\"" ;
							}
							else {
								$cmdline = "bin\\ffmpeg.exe -i \"$ifile\" -y -codec:v copy \"$tmp_mp4\" " ;
							}

							vfile_exec( $cmdline );
							
							if( vfile_exists( $tmp_mp4 ) ) {
								if( vfile_size( $tmp_mp4 ) > 1000 ) {
								  vfile_rename( $tmp_mp4, $preview_file );
								}
								else {
								   vfile_unlink( $tmp_mp4 );
								}
							}
						}
					}

					flock( $lockf, LOCK_UN ) ;		// unlock ;
					fclose( $lockf );
				}
			}
		}
		$result->free();
	}
	
	return $preview_file ;
}

function mp4cache_output( $index )
{
	header("Content-Type: video/mp4");	

	$preview_file = mp4cache_load( $index );
	if( !empty( $preview_file ) && vfile_exists( $preview_file ) ) {
		$vstat = vfile_stat( $preview_file ) ;

		// enable cache 
		if( !empty($vstat['mtime']) ) {
			// remove header "Pragma: no-cache" 
			header_remove('Pragma');
			$expires=24*3600;		// expired in 1 day
			$lastmodtime = gmdate('D, d M Y H:i:s ', $vstat['mtime']).'GMT';
			$etag = hash('md5', $hash.$vstat['mtime'].$vstat['size'] );
			header('Expires: '.gmdate('D, d M Y H:i:s ', $_SERVER['REQUEST_TIME']+$expires).'GMT');
			if( (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==$etag ) ) {
				header("HTTP/1.1 304 Not Modified");
				die;
			}
			header('Cache-Control: public, max-age='.$expires);
			header('Etag: '.$etag);
			header('Last-Modified: '.$lastmodtime);
		}
			
		if( $f = vfile_open( $preview_file ) ) {
			vfile_seek( $f, 0, SEEK_END );
			$fs = vfile_tell( $f );
			$offset = 0 ;
			$len = $fs ;
			$partial = false ;
			if( !empty( $_SERVER['HTTP_RANGE'] ) ) {
				$range = sscanf($_SERVER['HTTP_RANGE'] , "bytes=%d-%d");
				if( !empty($range[0]) ) {
					$offset = (int)$range[0] ;
				}
				if( empty($range[1]) ) {
					$len = $fs - $offset ; // to the end of file
				}
				else {
					$len = $range[1] - $offset + 1 ;
				}
				$partial = true ;
			}
			else {
				// only to output partial contents
				global $mp4preloadsize ;
				if( empty( $mp4preloadsize ) ) {
					$mp4preloadsize = 150000 ;		// 150K
				}
				if( $len > $mp4preloadsize ) {
					$len = $mp4preloadsize ;
					$partial = true ;
				}
			}

			if( $len>0 ) {
				header( "Accept-Ranges: bytes" );
				if( $partial ){
					header( "HTTP/1.1 206 Partial Content" );
					header( sprintf("Content-Range: bytes %d-%d/%d", $offset, $offset + $len - 1, $fs ));
				}
				header("Content-Length: $len" );
				
				@ob_end_flush();

				$t1 = gettimeofday(true);
				
				vfile_seek( $f, $offset );
				while( $len > 0  && !connection_aborted ()) {
					set_time_limit(30);

					$r = $len ;
					if( $r > 32768 ) {
						$r = 32768 ;
					}
					$da = vfile_read( $f, $r ) ;
					if( strlen( $da ) > 0 ) {
						echo $da ;
						flush();
						$len -= $r ;
						usleep(10000);				// throttle download speed 
					}
					else {
						break;
					}
				}
			}
			
			vfile_close( $f );		
		}
	}
}
	
?>