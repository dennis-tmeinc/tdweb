<?php
// localmsssave.php - save local MSS settings
//      local MSS settings are save on external text file.  Setting filename on $mss_conf.
// Requests:
//      local mss settings
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
			for($i=1;$i<=4;$i++) {
				$_REQUEST["check_ap$i"]=empty($_REQUEST["check_ap$i"])?"0":"1";
			}
			$confstr = '' ;
			foreach ( $_REQUEST as $key => $value ) {
				$confstr .= $key."=".$value."\r\n";
			}
			if( vfile_put_contents( $mss_conf, $confstr )>0 ) {
				$resp['res'] = 1 ;			// success ;
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	
	echo json_encode($resp);
?>