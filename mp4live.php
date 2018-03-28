<?php
// mp4live.php - get live mp4 preview video data
// Request:
//      phone : phone id (for dvr)
//      camera : channel number
// Return:
//      video file data
// By Dennis Chen @ TME	 - 2016-12-15
// Copyright 2013 Toronto MicroElectronics Inc.
//

$noredir = 1 ;
require 'session.php'; 

if( !$logon ) {
	exit;
}

require_once 'vfile.php' ;
header("Cache-Control: no-cache, no-store"); 

$phone = $_REQUEST['phone'] ;
if( empty($_REQUEST['camera']) ) {
	$camera = 0 ;
}
else {
	$camera = $_REQUEST['camera'] ;
}

$xcookiename="xf${phone}c${camera}" ;
if(!empty($_COOKIE[$xcookiename]) ) {
	$xsn = $_COOKIE[$xcookiename] ;
}
else {
	$xsn = 0 ;
}

if( empty($cache_dir) || !is_dir($cache_dir) ) {
	$cache_dir = "videocache" ;
}
$cache_dir = realpath( $cache_dir );
$video_prefix = $cache_dir . DIRECTORY_SEPARATOR . "vlp${phone}c${camera}" ;
$video_runfile = fopen( "${video_prefix}run", "c+" );
if(!$video_runfile){
	exit;
}

$active = false ;

// activate live run
flock( $video_runfile,LOCK_EX );
$rc = fread( $video_runfile, 10000 );
$jv = json_decode( $rc, true ) ;
if( !empty($jv['live']) && ( time()-$jv['active']) < 30 ) {
	$active = true ;
	$jv['active'] = time() ;
	rewind( $video_runfile ) ;
	ftruncate($video_runfile, 0) ;
	fwrite( $video_runfile, json_encode($jv) );
}
fflush($video_runfile);
flock( $video_runfile, LOCK_UN );

if( !$active || $xsn == 0 ) {
	// start live run
	$reqdir = dirname( $_SERVER["REQUEST_URI"]) ;
	$go = file_get_contents("http://localhost${reqdir}/liverun.php?phone=${phone}&camera=${camera}", false, NULL, 0, 1) ;
}

ob_end_clean();
ob_start();

$starttime=time();
set_time_limit(60) ;

flock( $video_runfile,LOCK_SH );
while( time() - $starttime < 15 ){
	
	// read	live run parameter
	fflush( $video_runfile );
	rewind( $video_runfile ) ;
	$rc = fread( $video_runfile, 10000 );

	$jv = json_decode( $rc, true ) ;

	if( empty( $xsn ) ) {
		$xsn = $jv['sn'] ;
	}

	if( $jv['live'] && !empty($jv['sn']) && $jv['sn'] != $xsn && is_readable($jv['dash']) ) {

		// parse mpd file
		$xmpd = new SimpleXMLElement( file_get_contents($jv['dash']) );
		$Representation = $xmpd->Period->AdaptationSet->Representation ;
		$dashfile = $cache_dir . DIRECTORY_SEPARATOR . (string)($Representation->BaseURL) ;
		if( is_readable($dashfile) ) {
			header("Content-Type: video/mp4");
			setcookie($xcookiename, $jv['sn'] ) ;
			
			if( !empty( $_SERVER['HTTP_X_MIME'] ) ){
				
				if( !empty($jv['channels']) ) {
					header("x-dash-channels: " . json_encode($jv['channels']) );
				}
				
				$mimetype = (string)($Representation->attributes()['mimeType']);
				$codecs   = (string)($Representation->attributes()['codecs']);
				$width    = (string)($Representation->attributes()['width']);
				$height   = (string)($Representation->attributes()['height']);
			
				header("x-dash-mimetype: $mimetype" );
				header("x-dash-codecs: $codecs" );
				header("x-dash-width: $width" );
				header("x-dash-height: $height" );
				$initRange = explode('-', (string) ($Representation->SegmentList->Initialization->attributes()['range']) );
				$initLen = 1 + (int)$initRange[1] - (int)$initRange[0] ;
				echo file_get_contents($dashfile, false, NULL, (int)$initRange[0], $initLen ) ;
			}
			
			foreach ( $Representation->SegmentList->SegmentURL as $seg ) {
				$mediaRange = explode('-', (string)($seg->attributes()['mediaRange']) ) ;
				$mediaLen = 1 + (int)$mediaRange[1] - (int)$mediaRange[0] ;
				echo file_get_contents($dashfile, false, NULL, (int)$mediaRange[0], $mediaLen ) ;
			}

			break ;
		}
	}
	flock( $video_runfile,LOCK_UN );
	usleep(200000);
	flock( $video_runfile,LOCK_SH );
}

flock( $video_runfile, LOCK_UN );
fclose($video_runfile);

if( !headers_sent() ) {
	header("Content-Length: " . ob_get_length() );
}
ob_end_flush();

exit ;
?>