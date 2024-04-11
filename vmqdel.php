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
			$vmq_ids = $_REQUEST['vmq_id'];
		}
		else {
			// to verify if the request is come from the vmq owner
			$vmq_ids = array();
			foreach($_REQUEST['vmq_id'] as $id ) {
				$sql = "SELECT `vmq_ins_user_name` FROM `vmq` WHERE `vmq_id` = $id ;" ;
				$result=$conn->query($sql);
				if( !empty($result)) {
					$row = $result->fetch_array(MYSQLI_NUM);
					if($row) {
						if( $row[0]==$_SESSION['user']) {
							$vmq_ids[] = $id ;
						}
					}
				}
			}
		}

		if( empty($vmq_ids)) {
			$resp['errormsg']='Not allowed!' ;
		}
		else {
			$ids = implode( ',',$vmq_ids );
			$sql = "DELETE FROM `vmq` WHERE `vmq_id` in ($ids) " ;
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