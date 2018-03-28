<?php
// mssdel.php - delete one MSS setting
// Requests:
//      idx: MSS setting index
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		if( $_SESSION['user_type'] == "admin" ) {	
			$query="DELETE FROM mss WHERE idx = '" . $_REQUEST['idx'] . "';" ;
			if( $conn->query($query) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['res']=0;
				$resp['errormsg']="SQL error: ".$conn->error;
			}
		}
		else {
			$resp['res']=0;
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>