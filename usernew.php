<?php
// usernew.php - create new user
// Requests:
//      user_name: user name
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( strcasecmp($_REQUEST["user_name"],"SuperAdmin")==0 ) {
			$resp['errormsg']="Not allowed!" ;	
		}
		else if( $_SESSION['user'] == "admin" ) {		// only one admin can do this, 2013-06-14
		
			// MySQL connection
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			// escaped sql values
			$esc_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$esc_req[$key]=$conn->escape_string($value);
			}

			$esc_req['notify_requested_video']= empty($esc_req['notify_requested_video'])?0:1 ;
			$esc_req['notify_marked_video']   = empty($esc_req['notify_marked_video'])?0:1 ;
			$esc_req['notify_sys_health']     = empty($esc_req['notify_sys_health'])?0:1 ;
				
			// confirmpassword=key, password=salt
			if( strlen($_REQUEST['password'])>10 && strlen($_REQUEST['confirmpassword'])>10 ) {
				// need to set password ;
				if( $_REQUEST['keytype'] == '0' ) {	// compatible type
					// check is it a empty password?
					if( $_REQUEST['password'] == md5( $_REQUEST['user_name'] . ':' . $_REQUEST['confirmpassword'] . ':' ) ) {
						$password='';
					}
					else if(strlen($_REQUEST['password'])==20 ){
						$password=$esc_req['password'];
					}
				}
				else if( $_REQUEST['keytype'] == '1' && strlen($_REQUEST['password'])==32 ) {	// MD5
					$password = '$1$'. $esc_req['password'] . '$' . $esc_req['confirmpassword'] ;
				}
				// other types not supported
			}
			
			$sql="INSERT INTO app_user (user_name, user_password, user_type, user_access, first_name,last_name,email,telephone,title,notify_requested_video,notify_marked_video,notify_sys_health) VALUES (".
				"'$esc_req[user_name]',".
				"'$password',".
				"'$esc_req[user_type]',".
				(($esc_req['user_type']=="admin")?"'all',":"NULL,").
				"'$esc_req[first_name]',".
				"'$esc_req[last_name]',".
				"'$esc_req[email]',".
				"'$esc_req[telephone]',".
				"'$esc_req[title]',".
				"$esc_req[notify_requested_video],".
				"$esc_req[notify_marked_video],".
				"$esc_req[notify_sys_health]);";
			
			if( $conn->query($sql) ) {
				$resp['res']=$conn->affected_rows  ;	// success
			}
			else {
				$resp['errormsg']=$conn->error;
			}			
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>