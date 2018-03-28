<?php
// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

include_once 'config.php' ;

if( empty($session_path) ) {
	$session_path= "session";
}
if( empty($session_idname) ) {
	$session_idname = "touchdownid";
}
if( empty($cache_dir) || !is_dir($cache_dir) ) {
	$cache_dir = "videocache" ;
}
session_save_path( $session_path );
session_name( $session_idname );

if( !empty($_REQUEST[session_name()]) ) {
	session_id ($_REQUEST[session_name()]);
}

if( empty($product_name) ) {
	$product_name = "TOUCH DOWN CENTER" ;
}

session_start();

// load client config
if( !empty( $_SESSION['clientid'] )) {
	$clientcfg = 'client/'.$_SESSION['clientid'].'/config.php' ;
	if( file_exists ( $clientcfg ) ) {
		require $clientcfg ;
		// fixed directories for multi companies
		if( !empty($company_root) ) {
			// MSS configure file
			$mss_conf=$company_root."\\mss.conf";
			// Dashboard Option file
			$dashboard_conf=$company_root."\\dashboardoption.config" ;
			// database backup file location, 
			$backup_path=$company_root."\\smartbackup" ;
		}
	}
	else {
		unset($_SESSION['clientid']);
	}
}

// setup time zone
date_default_timezone_set($timezone) ;	

/* page ui */
if( !empty($_COOKIE['ui']))
	$default_ui_theme = $_COOKIE['ui'] ;	

$resp=array('res' => 0);	
$request_time = time() ;
$logon=false ;
if( empty($_SESSION['user']) ||
	empty($_SESSION['xtime']) || 
	$request_time>$_SESSION['xtime']+$session_timeout )
{
	// logout
	unset($_SESSION['user']) ;
	unset($_SESSION['user_type']);
	$resp['errormsg']="Session error!";
	/* AJAX check */
	if( empty($noredir) && empty($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
		header( 'Location: logon.php' ) ;
	}	
}
else {
	if( empty( $noupdatetime ) )
		$_SESSION['xtime']=$request_time ;
	$logon=true ;
}

session_write_close();

if($logon) {
	if(	$database_persistent ) {
		$smart_server = "p:".$smart_host ;
	}
	else {
		$smart_server = $smart_host ;
	}
	// move sql connection here, in case for general session's settings (etc. timezone)
	if( empty( $nodb ) ) {
		@$conn = new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
	}
}

// save $_SESSION variable after session_write_close()
function session_write()
{
	if( empty($_SESSION) ) {
		// remove this session file
		@unlink( session_save_path().'/sess_'.session_id() );
	}
	else {
		//	file_put_contents( session_save_path().'/sess_'.session_id(), session_encode() );	
		$fsess = fopen( session_save_path().'/sess_'.session_id(), 'c+' );
		if( $fsess ) {
			flock( $fsess, LOCK_EX ) ;		// exclusive lock
			$sess_str = session_encode() ;
			fwrite( $fsess, $sess_str );
			ftruncate( $fsess, ftell($fsess));
			fflush( $fsess ) ;              // flush before releasing the lock
			flock( $fsess, LOCK_UN ) ;		// unlock ;
			fclose( $fsess );
		}
	}
}

// store one variable to session
function session_save( $vname, $value )
{
	if( empty( $value ) ) {
		unset( $_SESSION[$vname] );
	}
	else {
		$_SESSION[$vname] = $value ;		
	}
	session_write();
}

return ;
?>