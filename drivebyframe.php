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

	require 'session.php' ;
	require_once 'vfile.php' ;
	require_once 'drivebyframefunc.php' ;
	
	// Content type
	header('Content-type: image/jpeg');
	
	if( $logon ) {
		$imgfile = drivebyframe($driveby_eventdir."\\".$_REQUEST['tag'], $_REQUEST['channel'], $_REQUEST['time'] ) ;
		if( $imgfile ) {
			if( is_file( $imgfile ) ) {
				echo vfile_get_contents( $imgfile );
			}
			else {
				header( 'Location: res/247logo.jpg' ) ;
			}
		}
		else {
			header( 'Location: res/247logo.jpg' ) ;
		}
	}
?>