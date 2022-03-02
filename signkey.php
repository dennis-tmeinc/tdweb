<?php
// signkey.php - Second step of user sign in 
//   Checking user password
// By Dennis Chen @ TME	 - 2013-07-12
// Copyright 2013 Toronto MicroElectronics Inc.

	$noredir = 1 ;
	require_once 'session.php' ;

	header("Content-Type: application/json");
	
	$resp=array();
	$resp['res']=0 ;
	$resp['page']="#" ;
	$savesess=$_SESSION ;
	$_SESSION = array();
	$_SESSION['xtime']=time() ;	
	$_SESSION['release']=file_get_contents("release") ;
	if( empty($_SESSION['release']) )
		$_SESSION['release'] = "V3.7.23" ;	// known last release
	$_SESSION['remote']=$_SERVER['REMOTE_ADDR'] ;
	
	if( $savesess['xuser'] == 'SuperAdmin' ) {
		
		$ha1=$savesess['key'] ;
		$salt=$savesess['salt'] ;
		$nonce=$savesess['nonce'] ;
		$ha2=hash("md5", $_REQUEST['cnonce'] .":". $savesess['xuser'] .":". $nonce );
		$rescmp=hash("md5", $ha1 . ":" . $ha2 . ":" . $nonce . ":" . $_REQUEST['cnonce'] );
		if( $_REQUEST['result'] == $rescmp ) { // Matched!!!
		
			// what should be copied to new session
			$_SESSION['superadmin']= "--SuperAdmin--" ;
			$_SESSION['user']=$savesess['xuser'];
			$_SESSION['user_type']=$savesess['xuser_type'];
			$_SESSION['welcome_name'] = $savesess['welcome_name'];

			$resp['user'] = $_SESSION['user'] ;
		    $resp['res']=1 ;
			$resp['page']="company.php" ;
		}	
	}
	else if( !empty($savesess['xuser']) && !empty($_REQUEST['user']) && $savesess['xuser'] == $_REQUEST['user'] ) {
	    $ha1=$savesess['key'] ;
		$salt=$savesess['salt'] ;
		$nonce=$savesess['nonce'] ;
		$ha2=hash("md5", $_REQUEST['cnonce'] .":". $savesess['xuser'] .":". $nonce );
		$rescmp=hash("md5", $ha1 . ":" . $ha2 . ":" . $nonce . ":" . $_REQUEST['cnonce'] );
		if( $_REQUEST['result'] == $rescmp ) { // Matched!!!
		
			// what should be copied to new session
			$_SESSION['user']=$savesess['xuser'];
			$_SESSION['user_type']=$savesess['xuser_type'];
			if( !empty($savesess['clientid']) ) {
				$_SESSION['clientid']=$savesess['clientid'];
			}
			$_SESSION['welcome_name'] = $savesess['welcome_name'];

			// country code support, for display units (imperial)
			$_SESSION['country'] = $savesess['country'];

		    $resp['res']=1 ;
			$resp['user']=$_SESSION['user'] ;
			if( !empty($savesess['lastpage']) ) {
				$resp['page']=$savesess['lastpage'];
			}
			else {
				$resp['page']="dashboard.php" ;
			}
			
			// update user last login time
			$now = new DateTime() ;

			// reconnect MySQL (don't remove this one)
			@$conn = new mysqli("p:".$smart_host, $smart_user, $smart_password, $smart_database );
		    $sql="UPDATE app_user SET last_logon = '".$now->format("Y-m-d H:i:s")."' WHERE user_name = '".$_SESSION['user']."';" ;
			$conn->query($sql);
		}
	}
	session_write();
	echo json_encode($resp);
?>