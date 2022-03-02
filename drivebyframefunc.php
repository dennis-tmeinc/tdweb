<?php
// drivebyframefunc.php - define function for driveby frame
// By Dennis Chen @ TME	 - 2014-5-16
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require_once 'vfile.php' ;
	
	if( empty($cache_dir) ) {
		$cache_dir = "videocache" ;
	}

	function drivebyframe_x($tagfile, $channel, $pos )
	{
		global $cache_dir ;
		
		$v = file_get_contents( $tagfile );
		if( $v ) {
			$x = new SimpleXMLElement( $v );
			if( $x->busid ) {
				$ch=0 ;
				if( !empty( $channel ) ) {
					for( $i=0; $i<count($x->channel); $i++ ) {
						if( $channel == $x->channel[$i]->name ) {
							$ch = $i ;
							break;
						}
						if( $channel == ('camera'.($i+1)) ) {
							$ch = $i ;
							break;
						}
					}
				}
					
				if( !empty( $x->channel[$ch]->video )) {
					$vid = $x->channel[$ch]->video ;
					$time = 0 ;
					$part = 0.0 ;
					if( !empty($pos) ) {
						$time = (int)$pos ;
						$part = $pos - $time ;
					}
					$namehash = md5( $vid );
					$imgfile=  $cache_dir ."\\" .$namehash.'_'.$time.'_1.jpg' ;  
					
					if( vfile_size( $imgfile ) < 10 ) {
						set_time_limit(60) ;
					
						$cachefn = str_replace ( '/' , DIRECTORY_SEPARATOR , $cache_dir."\\".$namehash.'_'.$time.'_%d.jpg' ) ; 
						$pvid  = escapeshellarg( $vid );
						$ptime = escapeshellarg( $time );
						$pcache = escapeshellarg( $cachefn );
						
						$cmdline = "bin\\ffmpeg.exe -i $pvid -ss $ptime -t 1.02 -y $pcache" ;
						$eoutput = array();
						$eret = 1 ;
						$lline = vfile_exec( $cmdline, $eoutput, $eret ) ;
					}
					$fs = vfile_glob(  $cache_dir.'/'.$namehash.'_'.$time.'_*.jpg' ) ;
					$fc = count( $fs );
					$part = ((int)($part*$fc)) + 1 ;
					$imgfile = $cache_dir.'/' .$namehash.'_'.$time.'_'.$part.'.jpg' ;  
					
					return $imgfile ;
				}
			}
		}
		return false ;
	}

	function drivebysframe_f($tagfile, $channel, $pos )
	{
		$v = file_get_contents( $tagfile );
		if( $v ) {
			$x = new SimpleXMLElement( $v );
			if( $x->busid ) {
				$ch=0 ;
				if( !empty( $channel ) ) {
					for( $i=0; $i<count($x->channel); $i++ ) {
						if( $channel == $x->channel[$i]->name ) {
							$ch = $i ;
							break;
						}
						if( $channel == ('camera'.($i+1)) ) {
							$ch = $i ;
							break;
						}
					}
				}
					
				if( !empty( $x->channel[$ch]->video )) {
					$vid = $x->channel[$ch]->video ;
					$imgfile = session_save_path()."\\sess_".md5($vid.$pos).".jpg" ;
					$pos += 0.03 ;
					
					$pvid = escapeshellarg( $vid );
					$pimg = escapeshellarg( $imgfile );
					$cmdline = "bin\\ffmpeg.exe -ss $pos -i $pvid -frames 1 $pimg" ;

					set_time_limit(100) ;
					$eoutput = array();
					$eret = 1 ;
					$lline = vfile_exec( $cmdline, $eoutput, $eret ) ;
					if( is_file( $imgfile ) ) {
						return $imgfile ;
					}
				}
			}
		}
		return false ;
	}
	
	function drivebysframe($videofile, $pos )
	{
		global $cache_dir ;
		
		$imgfile = "$cache_dir\\frame".md5($videofile.$pos).".jpg" ;
		$pos += 0.03 ;
		$pvid = escapeshellarg( $videofile );
		$pimg = escapeshellarg( $imgfile );
		$cmdline = "bin\\ffmpeg.exe -ss $pos -i $pvid -frames 1 $pimg" ;

		set_time_limit(50) ;
		$eoutput = array();
		$eret = 1 ;
		vfile_exec( $cmdline, $eoutput, $eret ) ;
		if( $eret==0 && vfile_isfile( $imgfile ) ) {
			return $imgfile ;
		}
		return false ;
	}
	
	function drivebyframe($videofile, $pos )
	{
		global $cache_dir ;
		
		if( vfile_remote() ) {
			$imgfile = "videocache\\frame".md5($videofile.$pos).".jpg" ;
		}
		else {
			$imgfile = "$cache_dir\\frame".md5($videofile.$pos).".jpg" ;
		}
		
		$pvid = escapeshellarg( $videofile );
		$pimg = escapeshellarg( $imgfile );		
		$pos += 0.03 ;
		$cmdline = "bin\\ffmpeg.exe -ss $pos -i $pvid -frames 1 $pimg" ;

		set_time_limit(50) ;
		$eoutput = array();
		$eret = 1 ;
		vfile_exec( $cmdline, $eoutput, $eret ) ;
		if( $eret==0 && vfile_isfile( $imgfile ) ) {
			return $imgfile ;
		}
		return false ;
	}
?>