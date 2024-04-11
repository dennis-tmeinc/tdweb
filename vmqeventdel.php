<?php
// vmqdel.php - delete one video request
// Requests:
//      vmq_id: id of video request
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if(  $_SESSION['user_type'] == "admin" ) {	// admin 
			$ev_ids = $_REQUEST['ev_id'];
		}

		if( empty($ev_ids)) {
			$resp['errormsg']='Not allowed!' ;
		}
		else {
			$ids = implode( ',',$ev_ids );
			$sql = "DELETE FROM `vmq_event` WHERE `serialNum` in ($ids) " ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}
		}
	}
	echo json_encode($resp);
?>