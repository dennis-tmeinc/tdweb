<?php
// driversave.php - update/create driver
// Requests:
//      driver_id:  existed driver id
//      driver info
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-07
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		if( $_SESSION['user_type'] == "admin" ) {	// admin 
			
			$escape_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$escape_req[$key]=$conn->escape_string($value);
			}
					
			if( empty($escape_req['driver_id']) ) {			// new
				// check if driver id exist?
				$sql = "SELECT * FROM driver WHERE `driver_driverid` = '$escape_req[driver_driverid]';";
				if( $result=$conn->query($sql) ) {
					$count = $result->num_rows;
					$result->free();
				}
				if( !empty($count) ) {
					$resp['errormsg']='Driver ID already exist!' ;
				}
				else {
					$sql="INSERT INTO driver ( 
						driver_first_name,
						driver_last_name,
						driver_License,
						driver_driverid,
						driver_add,
						driver_city,
						driver_state,
						driver_country,
						driver_pcode,
						driver_rfid,
						driver_sin,
						driver_tel,
						driver_email,
						driver_explevel,
						driver_bcode,
						driver_bcode2,
						driver_notes ) VALUES (
						'$escape_req[driver_first_name]',
						'$escape_req[driver_last_name]',
						'$escape_req[driver_License]',
						'$escape_req[driver_driverid]',
						'$escape_req[driver_add]',
						'$escape_req[driver_city]',
						'$escape_req[driver_state]',
						'$escape_req[driver_country]',
						'$escape_req[driver_pcode]',
						'$escape_req[driver_rfid]',
						'$escape_req[driver_sin]',
						'$escape_req[driver_tel]',
						'$escape_req[driver_email]',
						'$escape_req[driver_explevel]',
						'$escape_req[driver_bcode]',
						'$escape_req[driver_bcode2]',
						'$escape_req[driver_notes]' );" ;
					if( $conn->query($sql) ) {					
						$resp['res']=1 ;	// success
					}
					else {
						$resp['errormsg']="Create New Driver failed!";
					}
				}
			}
			else {	// update
				// check if driver id exist?
				$sql = "SELECT * FROM driver WHERE driver_id != $escape_req[driver_id] AND `driver_driverid` = '$escape_req[driver_driverid]';";
				if( $result=$conn->query($sql) ) {
					$count = $result->num_rows;
					$result->free();
				}
				if( !empty($count) ) {
					$resp['errormsg']='Driver ID already exist!' ;
				}
				else {
					$sql = "SELECT * FROM driver WHERE driver_id = $escape_req[driver_id]";
					if( $result=$conn->query($sql) ) {
						$driver = $result->fetch_array();
						$result->free();
					}					
					
					$sql="UPDATE `driver` SET ".
						"driver_first_name='$escape_req[driver_first_name]',".
						"driver_last_name='$escape_req[driver_last_name]',".
						"driver_License='$escape_req[driver_License]',".
						"driver_driverid='$escape_req[driver_driverid]',".
						"driver_add='$escape_req[driver_add]',".
						"driver_city='$escape_req[driver_city]',".
						"driver_state='$escape_req[driver_state]',".
						"driver_country='$escape_req[driver_country]',".
						"driver_pcode='$escape_req[driver_pcode]',".
						"driver_rfid='$escape_req[driver_rfid]',".
						"driver_sin='$escape_req[driver_sin]',".
						"driver_tel='$escape_req[driver_tel]',".
						"driver_email='$escape_req[driver_email]',".
						"driver_explevel='$escape_req[driver_explevel]',".
						"driver_bcode='$escape_req[driver_bcode]',".
						"driver_bcode2='$escape_req[driver_bcode2]',".
						"driver_notes='$escape_req[driver_notes]' ".
						"WHERE driver_id=$escape_req[driver_id];" ;
					if( $result=$conn->query($sql) ) {
						$resp['res']=1 ;	// success
						// also update table ass_d_v
						if( !empty($driver['driver_driverid']) && $driver['driver_driverid'] != $_REQUEST['driver_driverid'] ) {
							// driver_id changed
							$sql="UPDATE `ass_d_v` SET `ass_driver_id` = '$escape_req[driver_driverid]' WHERE `ass_driver_id` = '$driver[driver_driverid]' ;";
							$conn->query($sql);
						}
					}
					else {
						$resp['errormsg']="SQL error: ".$conn->error;
					}
				}
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>