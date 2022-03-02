<?php
// mp4preview.php - mp4 preview video data
// Request:
//      index : video clip id
// Return:
//      video file data
// By Dennis Chen @ TME	 - 2013-02-14
// Copyright 2013 Toronto MicroElectronics Inc.
//

$noredir=1 ;
require_once 'session.php' ;
require_once 'vfile.php' ;

header("Content-Type: video/mp4");	

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
			if( $tnow - $st['atime'] > 96*60*60 ) {
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

			$preview_file = $cache_dir.$dirc."v".$hash.".mp4" ;
			$preview_tmpfile = $cache_dir.$dirc."t".$hash.".mp4" ;
			$preview_file_pattern = $cache_dir.$dirc."*" ;
			$preview_lockfile = session_save_path().'/sess_lock'.$hash ;
			
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
						$ifile = $row['path'] ;
						$conv266="bin\\conv266\\conv266.exe" ;
						if( file_exists( $conv266 ) ) {
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
					$mp4preloadsize = 102400 ;		// 100 K
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
						if( $len > 256*1024 ) {
							$len = 256*1024 ;
						}
						$lastpos = $offset + $len - 1 ;	
						header("Content-Length: $len" );
						header( sprintf("Content-Range: bytes %d-%d/%d", $offset, $lastpos, $fs ));
						header( "HTTP/1.1 206 Partial Content" );
					}
					
					// header("Connection: close");
					vfile_seek( $f, $offset );

					while( $len > 0 ) {
						set_time_limit(30);
						
						usleep(200000);				// throttle download speed

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
				
				vfile_close( $f );
			}
		}
		$result->free();
	}
	$conn->close();
}
exit ;
?>