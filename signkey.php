<?php
// signkey.php - Second step of user sign in 
//   Checking user password
// By Dennis Chen @ TME	 - 2013-07-12
// Copyright 2013 Toronto MicroElectronics Inc.
	
    require 'sessionstart.php' ;
	
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
			$_SESSION['clientid']=hash("md5", $_SERVER['HTTP_USER_AGENT'].":".$_SERVER['REMOTE_ADDR']);
			$_SESSION['xtime'] = time() ;
			$_SESSION['ui'] = $default_ui_theme ;
			$_SESSION['release']="V3.0" ;
			session_regenerate_id(true);

			// read ui theme from user setting
			$userfile=@fopen( $user_path."/".$_SESSION['user'], "r" );
			if( $userfile ) {
				$ujs = fread ( $userfile, 4096 );
				fclose($userfile);
				$uset = json_decode($ujs,true);
				if( isset($uset['ui']) )
					$_SESSION['ui']=$uset['ui'];				
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