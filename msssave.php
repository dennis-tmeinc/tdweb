<?php
// mssssave.php - save one MSS setting
// Requests:
//      idx: MSS setting index. Create a new MSS setting if not provided.
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
			if( !empty($_REQUEST['idx']) ) {
				$sql="UPDATE mss SET " .
				" mss_id ='".$_REQUEST['mss_id']."'".
				",mss_lat =".$_REQUEST['mss_lat'].
				",mss_lon =".$_REQUEST['mss_lon'].
				",mss_maxlogin =".$_REQUEST['mss_maxlogin'].
				 " WHERE idx = " . $_REQUEST['idx'] . " ;" ;
			}
			else {
				$sql="INSERT INTO mss (mss_id, mss_lat, mss_lon, mss_maxlogin) VALUES ('$_REQUEST[mss_id]','$_REQUEST[mss_lat]','$_REQUEST[mss_lon]','$_REQUEST[mss_maxlogin]') ;" ;
			}
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}			
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>