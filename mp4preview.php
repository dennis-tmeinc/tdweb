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

header("Content-Type: video/mp4");	

if( $logon ) {
	@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

	include 'mp4previewfunc.php' ;

	outputvideo($_REQUEST[index]);
}

exit ;
?>