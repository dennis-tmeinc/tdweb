<?php
// downloadplayer.php - download DVRViewer installer as player
// Request:
//
// Return:
//      player.msi file 
// By Dennis Chen @ TME	 - 2013-10-28
// Copyright 2013 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header( "Content-Type: application/octet-stream");
	
	if( $logon ){
		if( empty( $playerinstallfile ) ) {
			$playerinstallfile = "player/player.exe" ;
		}
		if( $playerinstallfile[0] != "/" && $playerinstallfile[0] != "\\" ) {	// not absolute path
			$playerinstallfile =  dirname( $_SERVER["SCRIPT_FILENAME"] ).'/'.$playerinstallfile ;
		}
		header( "Content-Disposition: attachment; filename=". basename( $playerinstallfile ) );
		
		if( file_exists ( $playerinstallfile ) ) {
		    header('Content-Length: ' . filesize($playerinstallfile) );
			readfile($playerinstallfile);
		}
	}
?>
