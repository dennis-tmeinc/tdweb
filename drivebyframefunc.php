<?php
// drivebyframefunc.php - define function for driveby frame
// By Dennis Chen @ TME	 - 2014-5-16
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require_once 'vfile.php' ;

	function drivebyframe_x($tagfile, $channel, $pos )
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
					$time = 0 ;
					$part = 0.0 ;
					if( !empty($pos) ) {
						$time = (int)$pos ;
						$part = $pos - $time ;
					}
					$namehash = md5( $vid );
					$imgfile=  'videocache/' .$namehash.'_'.$time.'_1.jpg' ;  
					
					if( vfile_size( $imgfile ) < 10 ) {
						function ex($cmd, &$result, &$ret)
						{
							if( $fsvr = vfile_remote() ) {
								$j = vfile_readhttp( $fsvr."?c=e&n=".rawurlencode($cmd) ) ;
								@$st = json_decode( $j, true );
								if( !empty( $st['res'] ) ) {
									$result = $st['output'] ;
									$ret = $st['ret'] ;
								}
							}
							else {
								exec( $cmd,$result,$ret);
							}
						}
						
						$cachefn = "videocache\\".$namehash.'_'.$time.'_%d.jpg' ;  
						
						$cmdline = "bin\\ffmpeg.exe -i $vid -ss $time -t 1.02 -y $cachefn" ;
						
						set_time_limit(60) ;
						$eoutput = array();
						$eret = 1 ;
						$lline = ex( $cmdline, $eoutput, $eret ) ;
					}
					$fs = vfile_glob(  'videocache/'.$namehash.'_'.$time.'_*.jpg' ) ;
					$fc = count( $fs );
					$part = ((int)($part*$fc)) + 1 ;
					$imgfile = 'videocache/' .$namehash.'_'.$time.'_'.$part.'.jpg' ;  
					
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
					$imgfile = session_save_path()."/sess_".md5($vid.$pos).".jpg" ;
					$pos += 0.03 ;
					$cmdline = "bin\\ffmpeg.exe -ss $pos -i $vid -frames 1 $imgfile" ;

					set_time_limit(100) ;
					$eoutput = array();
					$eret = 1 ;
					$lline = exec( $cmdline, $eoutput, $eret ) ;
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
		$imgfile = "videocache/frame".md5($videofile.$pos).".jpg" ;
		$pos += 0.03 ;
		$cmdline = "bin\\ffmpeg.exe -ss $pos -i $videofile -frames 1 $imgfile" ;

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