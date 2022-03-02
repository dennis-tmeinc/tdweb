<?php
// videoplay.php - generate a video play back list file
// Request:
//      index : video clip id
//      vehicle_name : vehicle name , if video clip id is not available
//      playtime : play back time
//      nodpl :  (omit or 0) output dpl header, 1: do not output dpl header
// Return:
//      .dpl file , contain JSON contents
// By Dennis Chen @ TME	 - 2013-10-29
// Copyright 2013 Toronto MicroElectronics Inc.
//

include_once 'session.php' ;
include_once 'playercontext.php' ;
	
if( $logon ){

	if( !empty($_REQUEST['index']) ) {
		$dpl = playbackcontext( $_REQUEST['index'], null, null );
	}
	else {
		$dpl = playbackcontext( null, $_REQUEST['vehicle_name'], $_REQUEST['playtime'] );
	}

	if( empty( $_REQUEST['nodpl'] ) ) {
		header( "Content-Type: application/x-touchdown-playlist" );
		header( "Content-Disposition: attachment; filename=\"touchdown.dpl\"" );			
		echo "#DPL\r\n" ;
		echo "# Content-Type: JSON\r\n" ;
		echo "# Touch Down Center ". $_SESSION['release']."\r\n\r\n" ;
	}
	else {
		header("Content-Type: application/json");
	}

	echo json_encode( $dpl ) ;
}
else {
	header("Content-Type: application/json");
	echo json_encode( $resp );
}

?>