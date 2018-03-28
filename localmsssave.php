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
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
			for($i=1;$i<=4;$i++) {
				$_REQUEST["check_ap$i"]=empty($_REQUEST["check_ap$i"])?"0":"1";
			}
			$localmssfile=@fopen( $mss_conf, "w" );
			if( $localmssfile ) {
				foreach ( $_REQUEST as $key => $value) {
					fwrite($localmssfile,$key."=".$value."\r\n");
				}
				fclose( $localmssfile );
				$resp['res']=1;				// success
			}
			else {
				$resp['errormsg']="Storage Error!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>