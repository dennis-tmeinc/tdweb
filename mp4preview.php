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
include_once "session.php" ;
include_once 'vfile.php' ;

if( $logon ) {

	if( !empty( $_REQUEST['preloadsize'] ) )
		$mp4preloadsize = (int)$_REQUEST['preloadsize'] ;

	include 'mp4previewfunc.php' ;
	
	if( empty( $_REQUEST['preload'] ) ) {
		mp4cache_output($_REQUEST['index']);
	}
	else {
		$cachefile = mp4cache_load($_REQUEST['index']); 
		if( !empty( $cachefile ) && vfile_exists( $cachefile ) ) {
			$resp['res'] = 1 ;
		}
		header("Content-Type: application/json");
		echo json_encode($resp);
	}
}

exit ;
?>