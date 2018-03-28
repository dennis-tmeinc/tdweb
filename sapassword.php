<?php
// sapassword.php - change super admin password
// Request:
//		pass : current password
//      salt : new salt
//      key  : new key 
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2014-04-17
// Copyright 2014 Toronto MicroElectronics Inc.
	
    include_once 'session.php' ;
	header("Content-Type: application/json");
	
	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" ) {
	
		$keycheck = false ;
	
		$p = file_get_contents( "client/sapass" );
		$user_password = trim($p);
		if( strlen( $user_password ) > 25 && $user_password[0] == '$' ) {
			$keys=explode('$', $user_password );
			$key = $keys[2] ;
			$salt = $keys[3] ;
			$cmpkey = md5( "SuperAdmin:".$salt.":".$_REQUEST['pass'] ) ;
			if( $cmpkey == $key )  {
				$keycheck = true ;
			}
		}
		else if( empty($_REQUEST['pass']) ) {
			$keycheck = true ;
		}
		if( $keycheck ) {
			// old key matched
			$user_password = "$1$".$_REQUEST['key']."$".$_REQUEST['salt'] ;
			file_put_contents( "client/sapass",  $user_password);
			$resp['res'] = 1 ;
		}
		else {
			$resp['errormsg'] = "The Current Password Is Not Correct!" ;
		}
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
	echo json_encode($resp);
?>