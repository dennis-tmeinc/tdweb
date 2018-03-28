<?php
// sapasswordverify.php - verify super admin password
// Request:
//	  phase 1:
//      p : 1 = Get Nounce
//	  phase 2:
//      p : 2 = verify key
//		k : key = md5( md5("SuperAdmin:".$salt.":".password) : nounce );
//	  phase 3:
//      p : 3 = save new password
//		k : key = md5( md5("SuperAdmin:".$salt.":".password) : nounce );
//		s : salt for new key 
//		n : newkey = md5("SuperAdmin:".$s.":".password)
// Return:
//      JSON array
//      phase 1:
//          n: nounce, s: salt
//      phase 2,3:
//          res: 1 = successful
// By Dennis Chen @ TME	 - 2016-8-11
// Copyright 2016 Toronto MicroElectronics Inc.
	
    include_once 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon && $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" ) {
		if($_REQUEST['p']==1) {
			$nonce=' ' ;
			$hexchar="0123456789abcdefghijklmnopqrstuvwxyz" ;
			for( $i=0; $i<64; $i++) {
				$nonce[$i] = $hexchar[mt_rand(0,35)] ;
			}
			@$p = file_get_contents( "client/sapass" );
			$user_password = trim($p);
			if( strlen( $user_password ) > 25 && $user_password[0] == '$' ) {
				$keys=explode('$', $user_password );
				$key = $keys[2] ;
				$salt = $keys[3] ;
			}
			else {
				$salt = ' ';
				for( $i=0; $i<20; $i++) {
					$salt[$i] = $hexchar[mt_rand(0,35)] ;
				}				
				$key = md5( "SuperAdmin:".$salt.":" ) ;
			}
			$resp['errormsg']="";
			$resp['res'] = 1 ;
			$resp['n'] = $nonce ;
			$resp['s'] = $salt ;
			
			// save verifying key
			$_SESSION["sakey"] = md5($key . ":" . $nonce ) ;
			session_write();
		}
		else if($_REQUEST['p']==2) {
			// restore verifying key
			$sakey = $_SESSION["sakey"] ;
			unset($_SESSION["sakey"]) ;
			
			if( $_REQUEST['k'] == $sakey ) {
				$_SESSION["sa_verified"] = 1 ;
				$resp['errormsg']="";
				$resp['res'] = 1 ;
			}
			else {
				$resp['errormsg']="Password Not Correct!";
				$resp['res'] = 0 ;				
			}
			session_write();
		}
		else if($_REQUEST['p']==3) {
			// restore verifying key
			$sakey = $_SESSION["sakey"] ;
			unset($_SESSION["sakey"]) ;
			session_write();
			
			if( $_REQUEST['k'] == $sakey ) {
				$user_password = "$1$".$_REQUEST['n']."$".$_REQUEST['s'] ;
				file_put_contents( "client/sapass",  $user_password);
				$resp['errormsg']="";
				$resp['res'] = 1 ;
			}
			else {
				$resp['errormsg']="Password Not Correct!";
				$resp['res'] = 0 ;				
			}
		}		
	}
	else {
		$resp['errormsg']="Not allowed!";
	}
	echo json_encode($resp);
?>