<?php
// localmssload.php - load local MSS settings
//      local MSS settings are save on external text file.  
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$localmss=array();
		$localmssfile=@fopen( $mss_conf, "r" );
		if( $localmssfile ) {
			while( $line=fgets($localmssfile) ) {
				$ar=explode ( "=", $line, 2 );
				if( count($ar)==2 ) {
					$localmss[trim($ar[0])]=trim($ar[1]);
				}
			}
			fclose($localmssfile);
		}
		$resp = $localmss ;
	}
	echo json_encode($resp);

?>