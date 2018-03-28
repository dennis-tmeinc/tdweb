<?php
// usersave.php - save one user information
// Requests:
//      xuser : original user name
//      other user informations (can't change user name, it is linked to password!!!!)
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		// only one true admin from now. 2013-06-14
		if( $_SESSION['user'] == "admin" || $_SESSION['user'] == $_REQUEST['xuser'] ) {		// admin or user self
		
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
			
			$sql="UPDATE app_user SET " .
				" first_name='$esc_req[first_name]'".
				",last_name='$esc_req[last_name]'".
				(isset($password)?",user_password='$password'":'').
				(( $_SESSION['user'] == "admin" && $_REQUEST['xuser'] != 'admin' )? 
					(",user_type='$esc_req[user_type]',user_access=".(($_REQUEST['user_type']=="admin")?"'all'":"NULL"))
					:''
				).
				",email='$esc_req[email]'".
				",telephone='$esc_req[telephone]'".
				",title='$esc_req[title]'".
				",notify_requested_video=$esc_req[notify_requested_video]".
				",notify_marked_video=$esc_req[notify_marked_video]".
				",notify_sys_health=$esc_req[notify_sys_health]".
				" WHERE user_name = '$esc_req[xuser]' ;" ;

			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
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