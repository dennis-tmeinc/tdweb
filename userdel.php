<?php
// userdel.php - delete one user
// Requests:
//      username: user name to delete
//      alluser: yes (to delete all users, admin only)
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-07-05
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user'] == 'admin' && !empty($_REQUEST['alluser']) && $_REQUEST['alluser'] == 'yes' ) {
			$sql="DELETE FROM `app_user` WHERE `user_name` != 'admin' ;" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Delete user failed!";
			}
		} 
		else if( $_REQUEST['username'] != 'admin' && 
		    ( $_SESSION['user'] == 'admin' ||  $_SESSION['user'] == $_REQUEST['username'] ) )
		{	// only one admin can do deleting.  not on admin self
		
			// MySQL connection
			$conn=mysqli_connect($smart_server, $smart_user, $smart_password, $smart_database );
			
			// escaped string for SQL
			$esc_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$esc_req[$key]=$conn->escape_string($value);
			}
			
			$sql="DELETE FROM app_user WHERE user_name = '$esc_req[username]' ;";
			if( mysqli_query($conn, $sql) ) {
				if( $_SESSION['user'] == $_REQUEST['username'] ) {
					// current user removed!!!
					session_save('user', '');	// stop this session
				}
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="Delete User Failed!";
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>