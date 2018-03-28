<?php
// liverun.php - run live view tunnel
// Requests:
//      DVR setup url :  ex, http://localhost/tdc/liverun.php?phone=<phonenumber>
//      phone : phone id (for dvr)
//      camera : channel number
// Return:
//      no data
// By Dennis Chen @ TME	 - 2016-12-06
// Copyright 2016 Toronto MicroElectronics Inc.

require_once 'config.php' ; 
require_once 'webtunstream.php' ;

$phone = $_REQUEST['phone'] ;
if( empty($_REQUEST['camera']) ) {
	$camera = 0 ;
}
else {
	$camera = $_REQUEST['camera'] ;
}

function live_log($msg)
{
	echo $msg ;
	ob_flush(); flush();
}

 // send single data DVR req
function dvr_req( $stream, $code, $data = 0, $databuf = '' )
{
	$dsiz = strlen($databuf) ;
	$req = pack("V3", $code, $data, $dsiz ) ;
	if( fwrite( $stream, $req ) != strlen($req) )
		return false ;
	
	if( $dsiz > 0 ) {
		if( fwrite( $stream, $databuf ) != $dsiz ) {
			return false ;
		}
	}
	return true ;
}
	
function dvr_read( $stream, $len )
{
	$data = '' ;
	while( $len>0 ) {
		$d = fread( $stream, $len ) ;
		@ $dlen = strlen($d) ;
		if( $dlen>0 ) {
			$data .= $d ;
			$len -= $dlen ;
		}
		else {
			return '';
		}
	}
	return $data ;
}
	
// receive DVR ans, return ans code, and data and databuf
function dvr_ans( $stream, &$data, &$databuf )
{
	$ans = dvr_read( $stream, 12 );
	if( strlen($ans) != 12 ) return -1 ;
	$ans = unpack("Vcode/Vdata/Vsize", $ans ) ;
	
	$data = $ans['data'] ;
	$databuf = dvr_read( $stream, $ans['size'] ) ;
	return $ans['code'] ;
}

// open live stream
function dvr_openlive($stream)
{
	global $camera ;
	$ansdata=0;
	$databuf="";

	// try open live stream without key
	// REQ ReqOpenLive
	if( !dvr_req( $stream, 215, $camera ) ) {
		return false ;
	}
	if( dvr_ans( $stream, $ansdata, $databuf ) == 201 ) {  // ANSSTREAMOPEN, live stream opened
		return true ;
	}
	
	foreach( glob( "bin/tvskey*" ) as $keyfile ) {
		$databuf = file_get_contents( $keyfile );
		
		// REQCHECKKEY : 303
		dvr_req( $stream, 303, 0, $databuf) ;
		if( dvr_ans( $stream, $ansdata, $databuf ) == 2 ) {	// ANSOK
			dvr_req( $stream, 215, $camera );
			if( dvr_ans( $stream, $ansdata, $databuf ) == 201 ) {  // ANSSTREAMOPEN, live stream opened
				return true ;
			}
		}
	}
	
	return false ;
}

$stream_headcode = 0 ;
$audio_codec = "pcm_alaw" ;
$audio_samplerate = 8000 ;

$afile = NULL ;
$vfile = NULL ;

if( empty($cache_dir) || !is_dir($cache_dir) ) {
	$cache_dir = "videocache" ;
}
$cache_dir = realpath( $cache_dir );
$video_prefix = $cache_dir . DIRECTORY_SEPARATOR . "vlp${phone}c${camera}" ;

$video_live = true ;
$video_active = time();
$video_runfile = NULL ;

// 266 file header
function on_file_header( $frame ) 
{
	global $stream_headcode , $audio_codec , $audio_samplerate ;
	
	$stream_headcode = substr($frame, 0, 4 );
	$acode = unpack( "vv", substr($frame, 12) )['v'] ;
	if($acode == 2) {
		$audio_codec = "pcm_alaw" ;
	}
	else {
		$audio_codec = "pcm_mulaw" ;
	}
	$audio_samplerate = unpack( "Vv", substr($frame, 16) )['v'] ;
	
	live_log( "X-audio-codec: $audio_codec \n" );
	live_log( "X-audio-samplerate: $audio_samplerate \n" ) ;
}

// receive pes packet,  return frame size
function on_pes_packet($frame, $start)
{
	return unpack( "nz", substr($frame, $start+4, 2) )['z'] + 6 ;
}

// receive audio frame, return frame size
function on_audio_frame($frame, $start, &$headerlen)
{
	$headerlen = unpack( "Cz", substr($frame, $start+8, 1) )['z'] + 9 ;
	return on_pes_packet($frame, $start) ;
}

// receive audio frame, return frame size
function on_video_frame($frame, $start, &$headerlen)
{
	$headerlen = unpack( "Cz", substr($frame, $start+8, 1) )['z'] + 9 ;
	$pesSize =  on_pes_packet($frame, $start) ;  ;
	if( $pesSize<=6 ) {
		$pesSize = unpack( "Nz", substr($frame, $start+15, 4) )['z'] + $headerlen  ;
	}
	return $pesSize ;
}

// Program Stream Header
function on_ps_header($frame, $start)
{
	return ( unpack( "Cz", substr($frame, $start+13, 1) )['z'] & 7 )+ 14  ;
}

function on_receive_frame( $frame ) 
{
	global $vfile, $afile, $vfilename, $video_prefix, $video_live ;
	global $audio_codec , $audio_samplerate ;
	global $support_liveaudio ;
	global $stream_headcode ;
	static $video_serno = 0 ;
	static $video_updatetime = 0 ;
	static $vname ;
	
	$framesize = strlen( $frame ) ;
	if( $stream_headcode === 0 && $framesize == 40 ) {
		// file header
		on_file_header( $frame ) ;
	}
	else if( $framesize>4 ) {
		$pos = 0 ;
		$vstart = 0 ;
		$avhead = pack("c3", 0 , 0, 1 );
		while( $pos < $framesize ) {
			$head3 = substr( $frame, $pos, 3 );

			if( $head3 == "TXT" || $head3 == "GPS") {
				$framelen = 8 + unpack("vv", substr($frame,$pos+6,4) )['v'] ;
				$vstart = $pos + $framelen ;
			}
			else if( $head3 === $avhead ) {
				$packtype = substr($frame, $pos+3, 1) ;
				if( $packtype == "\xc0" ) {
					$headerlen = 0 ;
					$framelen = on_audio_frame( $frame, $pos, $headerlen );
					$pos += $headerlen ;
					$framelen -= $headerlen ;
					if( $afile ) {
						fwrite( $afile, substr( $frame, $pos, $framelen ));
					}
					$vstart = $pos + $framelen ;
				}
				else if( $packtype == "\xe0" ) {
					$framelen = on_video_frame( $frame, $pos, $headerlen );
					if( $vfile ) {
						fwrite( $vfile, substr( $frame, $vstart,  $pos + $framelen - $vstart ) );
					}
					$vstart = $pos + $framelen ;
				}
				else if( $packtype == "\xba" ) {	// PS stream header
					$framelen = on_ps_header( $frame, $pos );
				}
				else if( $packtype == "\xbc" ) {	// program_stream_map ? this come with every I frame, so I just use it as I frame indicator
					// other video header (indication of a key frame)
					$framelen = on_pes_packet( $frame, $pos );

					$nowt = time();
					if( $nowt-$video_updatetime > 2 || empty($vfile) ) {
						if( !empty($afile) ) {
							fclose($afile) ;
							$afile=NULL ;
						}
						if( !empty($vfile) ) {
							fclose($vfile) ;
							$vfile = NULL ;
							if( empty($support_liveaudio) ) {
								$ffmpeg = "bin\\ffmpeg.exe -y -i $vname.v -codec:v copy $vname.mp4" ;
							}
							else {
								$ffmpeg = "bin\\ffmpeg.exe -y -f u8 -acodec $audio_codec -ar $audio_samplerate -i $vname.a -i $vname.v -codec:v copy $vname.mp4" ;
							}
							exec( $ffmpeg );
							$mp4box = "bin\\mp4box.exe $vname.mp4 -dash 100000 -rap -out $vname.mpd" ;
							exec( $mp4box );
							live_update( "$vname.mpd" ) ;
							@unlink( "$vname.a" ) ;
							@unlink( "$vname.v" ) ;
							@unlink( "$vname.mp4" ) ;
						}
						if($video_live) {
							$video_serno = $video_serno % 4 + 1 ;
							$vname = "$video_prefix.$video_serno" ;
							live_lock();
							foreach( glob( "${vname}*" ) as $val ) {
								@unlink( $val ) ;
							}
							live_unlock();
							$vfile = fopen( "$vname.v", "w" );
							if( !empty($support_liveaudio) ) {
								$afile = fopen( "$vname.a", "w" );
							}
							$video_updatetime = $nowt ;
						}
						else {
							break;
						}
					}
				}
				else {
					// all other pes packet ?
					$framelen = on_pes_packet( $frame, $pos );
				}
			}
			else {
				live_log("Unknown frame .  size: $framesize, pos: $pos, hex:  ".bin2hex( substr($frame, $pos, 8 )) );
				break;
			}
			$pos += $framelen ;
		}
	}
}

$live_lock_count = 0 ;
// recursive lock
function live_lock()
{
    global $video_runfile, $live_lock_count ;

    if( $live_lock_count++ == 0 ) {
		flock( $video_runfile, LOCK_EX );
	}

}

function live_unlock()
{
    global $video_runfile, $live_lock_count ;

    if( (--$live_lock_count) == 0 ) {
		fflush($video_runfile);
		flock( $video_runfile, LOCK_UN );
    }
}

// update live run file, return false if timeout
function live_update( $dashfile )
{
	global $video_runfile, $video_active, $video_live ;
	
	if( !is_readable($dashfile) ) {
		live_log(" -- NO $dashfile \n");
		return ;
	}
	
	live_lock();
	rewind( $video_runfile ) ;
	
	$rc = fread( $video_runfile, 10000 );
	$jv = json_decode( $rc, true ) ;

	$jv['dash'] = $dashfile ;
	$jv['sn']++ ;
	$video_active = $jv['active'] ;

	rewind( $video_runfile ) ;
	ftruncate($video_runfile, 0) ;
	fwrite( $video_runfile, json_encode($jv) );
	live_unlock();
}

// check if another liverun is active, if not start runing this instance
function live_start()
{
	global $video_runfile, $video_prefix ;
	
	$video_runfile = fopen( "${video_prefix}run", "c+" );
	if( !$video_runfile ) {
		return false ;
	}

	$start = false ;
	$upd = false ;
	$sn = 0 ;
	
	// retry 10 time (seconds)
	for( $retry=0 ; $retry<20; $retry++ ) {
		live_lock();
		rewind( $video_runfile ) ;

		$rc = fread( $video_runfile, 10000 );
		$jv = json_decode( $rc, true ) ;

		if( empty($jv['live'])  || (time()-$jv['active'])>60 ) {
			$start = true ;
		}
		else if( $sn == 0 ) {
			$jv['active'] = time();		// keep old live task run
			$upd = true ;
			$sn = $jv['sn'] ;
		}
		else if( $sn != $jv['sn'] ) {
			// other task updating sn
			$br = true ;
		}
		else if( $retry>15 ) {
			$start = true ;
		}
		
		if( $start ) {
			$jv = array(
				'live' => true,
				'active' => time(),
				'sn' => 0 
			);
			$upd = true ;
			$br = true ;			
		}
		if( $upd ) {
			rewind( $video_runfile ) ;
			ftruncate($video_runfile, 0) ;
			fwrite( $video_runfile, json_encode($jv) );
			$upd = false ;
		}

		live_unlock();

		if( $br ) break ;
		sleep(1) ;
	}
	
	if( !$start ) 
		fclose($video_runfile);
	return $start ;
}

// mark stop of live run
function live_stop()
{
	global $video_runfile, $video_prefix ;
	global $vfile, $afile ;
	live_lock();
	
	if($vfile) {
		fclose($vfile) ;
		$vfile = NULL ;
	}
	if($afile) {
		fclose($afile) ;
		$afile = NULL ;
	}
	foreach( glob( "${video_prefix}.*" ) as $val ) {
		@unlink( $val ) ;
	}
	
	rewind( $video_runfile ) ;
	$rc = fread( $video_runfile, 10000 );
	$jv = json_decode( $rc, true ) ;
	$jv['live'] = false ;
	unset( $jv['active'] );
	unset( $jv['sn'] );
	unset( $jv['dash'] );
	
	rewind( $video_runfile ) ;
	ftruncate($video_runfile, 0) ;
	fwrite( $video_runfile, json_encode($jv) );

	live_unlock();
	fclose($video_runfile) ;
}

ignore_user_abort(true);

live_log( "Live Start\n" );

if( !live_start() ){
	live_log( "already running!\n" );
	exit ;
}

$live_stream = NULL ;

register_shutdown_function(function ()
{
	global $video_live ;
	global $live_stream ;
	if( $live_stream ){
		fclose( $live_stream ) ;
		$live_stream = NULL ;
	}
	
	live_stop();

	if( $video_live ) {
		live_log( "Live abort! \n" );
		// reload live run
		// $go = file_get_contents("http://localhost$_SERVER[REQUEST_URI]", false, NULL, 0, 1) ;
	}
	
});

$live_stream = fopen("webtun://$phone:15114", "c") ;
if( $live_stream ) {

	$video_live = true ;
	if( dvr_openlive($live_stream) ) {

		live_log( "Stream opened!\n" );
		
		$stream_headcode = 0;	// start Live streaming
		while( $video_live && time() - $video_active < 30  && !feof($live_stream) ) {
			set_time_limit( 60 );

			if( ($anscode = dvr_ans( $live_stream, $data, $databuf )) > 1 ) {  	// > ANSERROR
				if( strlen($databuf) > 0 ) {
					on_receive_frame( $databuf );
					// live_log( "Frame :".strlen($databuf)."\n" );
				}
			} 		
			else {
				live_log( "READ DATA failed! code: $anscode \n"  ) ;
				break ;
			}
		}
	}
	else {
		live_log("Keyfile failed!") ;
	}
	$video_live = false ;
}
else {
	live_log( "Stream Error!" );
}

live_log( "Live Finish!\n" );

?>