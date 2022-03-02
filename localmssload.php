<?php
// localmssload.php - load local MSS settings
//      local MSS settings are save on external text file.  
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2014-02-19
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
		if( $conf = vfile_get_contents( $mss_conf ) ) {
			$resp['mss'] = parse_ini_string( $conf );
			$resp['res'] = 1 ;			
		}
	}
	echo json_encode($resp);

?>