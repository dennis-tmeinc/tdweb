<?php
// vcqdel.php - delete one video request (cell table)
// Requests:
//      vcq_id: id of video request
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2024-02-15
// Copyright 2024 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if(  $_SESSION['user_type'] == "admin" ) {	// admin 
			$vcq_ids = $_REQUEST['vcq_id'];
		}
		else {
			// to verify if the request is come from the vcq owner
			$vcq_ids = array();
			foreach($_REQUEST['vcq_id'] as $id ) {
				$sql = "SELECT `vcq_ins_user_name` FROM `vcq` WHERE `vcq_id` = $id ;" ;
				$result=$conn->query($sql);
				if( !empty($result)) {
					$row = $result->fetch_array(MYSQLI_NUM);
					if($row) {
						if( $row[0]==$_SESSION['user']) {
							$vcq_ids[] = $id ;
						}
					}
				}
			}
		}

		if( empty($vcq_ids)) {
			$resp['errormsg']='Not allowed!' ;
		}
		else {
			$ids = implode( ',',$vcq_ids );
			$sql = "DELETE FROM `vcq` WHERE `vcq_id` in ($ids) " ;
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