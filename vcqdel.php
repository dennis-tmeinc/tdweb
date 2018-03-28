<?php
// vcqdel.php - delete one video request (cell table)
// Requests:
//      vcq_id: id of video request
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2016-12-14
// Copyright 2016 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$allow=false ;
		if(  $_SESSION['user_type'] != "admin" ) {	// admin 
			// to verify if the request is come from the vcq owner
			$sql = "SELECT `vcq_ins_user_name` FROM `vcq` WHERE `vcq_id` = $_REQUEST[vcq_id] ;" ;
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
			$sql = "DELETE FROM `vcq` WHERE `vcq_id` = $_REQUEST[vcq_id] ;" ;
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