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
		$allow=false ;
		if(  $_SESSION['user_type'] != "admin" ) {	// admin 
			// to verify if the request is come from the vmq owner
			$sql = "SELECT `vmq_ins_user_name` FROM `vmq` WHERE `vmq_id` = $_REQUEST[vmq_id] ;" ;
			$result=$conn->query($sql);
			if( !empty($result)) {
				$row = $result->fetch_array(MYSQLI_NUM);
				if($row) {
					if( $row[0]==$_SESSION['user']) {
						$allow=true ;
					}
				}
			}
		}
		else {
			$allow=true ;
		}
		if( $allow ) {
			$sql = "DELETE FROM `vmq` WHERE `vmq_id` = $_REQUEST[vmq_id] ;" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}
		}
		else {
			$resp['errormsg']='Not allowed!' ;
		}
	}
	echo json_encode($resp);
?>