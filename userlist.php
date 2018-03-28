<?php
// userlist.php - get user list
// Requests:
//      none
// Return:
//      JSON array of user list
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql="SELECT user_name,first_name,last_name, title,email,telephone,user_type,user_id,last_logon,notify_marked_video,notify_requested_video,notify_sys_health FROM `app_user` ;" ;
		if($result=$conn->query($sql)) {
			$userlist = array();
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$userlist[]=$row;
			}
			$resp = $userlist;
			$result->free();
		}
	}
	
	echo json_encode($resp);
?>