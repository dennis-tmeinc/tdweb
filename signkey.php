<?php
// signkey.php - Second step of user sign in 
//   Checking user password
// By Dennis Chen @ TME	 - 2013-07-12
// Copyright 2013 Toronto MicroElectronics Inc.
	
	require_once 'config.php' ;

	session_save_path( $session_path );
	session_name( $session_idname );
	session_start();
	
	header("Content-Type: application/json");
	
	$resp=array();
	$resp['res']=0 ;
	$resp['page']="#" ;
	$savesess=$_SESSION ;
	$_SESSION = array();
	
	if( !empty($savesess['xuser']) && !empty($_REQUEST['user']) && $savesess['xuser'] == $_REQUEST['user'] ) {
	    $ha1=$savesess['key'] ;
		$salt=$savesess['salt'] ;
		$nonce=$savesess['nonce'] ;
		$ha2=hash("md5", $_REQUEST['cnonce'] .":". $savesess['xuser'] .":". $nonce );
		$rescmp=hash("md5", $ha1 . ":" . $ha2 . ":" . $nonce . ":" . $_REQUEST['cnonce'] );
		if( $_REQUEST['result'] == $rescmp ) { // Matched!!!
			// what should be copied to new session
			$_SESSION['user']=$savesess['xuser'];
			$_SESSION['user_type']=$savesess['user_type'];
			$_SESSION['welcome_name'] = $savesess['welcome_name'];
			$_SESSION['clientid']=$_SERVER['REMOTE_ADDR'] ;
			$_SESSION['xtime'] = $_SERVER['REQUEST_TIME'] ;
			$_SESSION['release']="V3.4" ;

			// restore user theme
			if( empty( $user_path ) ) {
				$user_path = $session_path ;
			}
			$themefile=@fopen( $user_path."/theme", "r" );
			if( $themefile ) {
				flock($themefile, LOCK_EX ) ;
				$uthemestr = fread( $themefile, 100000 );
				$utheme = array();
				if( strlen( $uthemestr ) > 2 ) {
					@$utheme = json_decode($uthemestr, true) ;
				}
				if( !empty( $utheme[$_SESSION['user']] ) ) {
					setcookie("ui", $utheme[$_SESSION['user']]);
				}
				flock($themefile, LOCK_UN ) ;
				fclose( $themefile );
			}
	
		    $resp['res']=1 ;
			$resp['user']=$savesess['xuser'] ;
			if( !empty($savesess['lastpage']) ) {
				$resp['page']=$savesess['lastpage'];
			}
			else {
				$resp['page']="dashboard.php" ;
			}
			
			// update user last login time
			@$conn= new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql="UPDATE app_user SET last_logon=NOW() WHERE user_name = '".$_SESSION['user']."';" ;
			$conn->query($sql);
			$conn->close();
		}
	}
	echo json_encode($resp);
?>