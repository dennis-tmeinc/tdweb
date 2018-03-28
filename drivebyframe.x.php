<?php
// drivebyframe.php - get drive by frame image
// Request:
//      tag : drive tag file name
//      channel : channel number
//      time : offset from beginnig
// Return:
//      jpeg image 
// By Dennis Chen @ TME	 - 2014-1-17
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require_once 'session.php' ;
	require_once 'vfile.php' ;
	require_once 'drivebyframefunc.php' ;
	
	// Content type
	header('Content-type: image/jpeg');
	
	if( $logon ) {
		$v = vfile_get_contents( $driveby_eventdir."\\".$_REQUEST['tag'] );
		if( $v ) {
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
					$vid = $x->channel[$ch]->video ;
					$time = 0 ;
					$part = 0.0 ;
					if( !empty($_REQUEST['time']) ) {
						$time = (int)$_REQUEST['time'] ;
						$part = $_REQUEST['time'] - $time ;
					}
					$namehash = md5( $vid );
					$imgfile=  'videocache/' .$namehash.'_'.$time.'_1.jpg' ;  
					
					
					if( vfile_size( $imgfile ) < 10 ) {
					header("x-img-file: ".$imgfile );
						$cachefn = "videocache\\".$namehash.'_'.$time.'_%d.jpg' ;  
						$cmdline = "bin\\ffmpeg.exe -i $vid -ss $time -t 1.02 -y $cachefn" ;
						
						set_time_limit(60) ;
						$eoutput = array();
						$eret = 1 ;
						$lline = vfile_exec( $cmdline, $eoutput, $eret ) ;
						
					}
					$fs = vfile_glob(  'videocache/'.$namehash.'_'.$time.'_*.jpg' ) ;
					$fc = count( $fs );
					$part = ((int)($part*$fc)) + 1 ;
					$imgfile = 'videocache/' .$namehash.'_'.$time.'_'.$part.'.jpg' ;  
					
					$imgdata = vfile_get_contents( $imgfile );
					if( $imgdata )
						echo $imgdata ;
				}
			}
		}
	}
?>