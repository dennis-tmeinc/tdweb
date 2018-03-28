<?php
// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

require 'config.php' ;

if( empty($session_path) ) {
	$session_path= "session";
}
if( empty($session_idname) ) {
	$session_idname = "touchdownid";
}

session_save_path( $session_path );
session_name( $session_idname );

if( !empty( $session_id ) ) {
	session_id ($session_id);
}
if( !empty($_REQUEST[session_name()]) ) {
	session_id ($_REQUEST[session_name()]);
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
// persistent database connection
if(	$database_persistent ) {
	$smart_server = "p:".$smart_host ;
}
else {
	$smart_server = $smart_host ;
}
	
/* page ui */
if( !empty($_COOKIE['ui']))
	$default_ui_theme = $_COOKIE['ui'] ;	

// store $_SESSION variable after session_write_close()
function session_write()
{
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

// store one variable to session
function session_save( $vname, $value )
{
	$fsess = fopen( session_save_path().'/sess_'.session_id(), 'r+' );
	if( $fsess ) {
		flock( $fsess, LOCK_EX ) ;		// exclusive lock
		
		$sess_str = fread( $fsess, 20000 );
		session_decode ( $sess_str ) ;
		$_SESSION[$vname] = $value ;
		$sess_str = session_encode() ;
		rewind( $fsess ) ;
		fwrite( $fsess, $sess_str );
		fflush( $fsess ) ;              // flush before releasing the lock
		ftruncate( $fsess, ftell($fsess));

		flock( $fsess, LOCK_UN ) ;		// unlock ;
		fclose( $fsess );
	}
}

$resp=array('res' => 0);	
$xt = time() ;
if( empty($_SESSION['user']) ||
	empty($_SESSION['xtime']) || 
	$xt>$_SESSION['xtime']+$session_timeout )
	{
	// logout
	unset($_SESSION['user']) ;
	$resp['errormsg']="Session error!";
	$resp['session'] = 0 ;
	$logon=false ;
	/* AJAX check */
	if( empty($noredir) && empty($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
		header( 'Location: logon.php' ) ;
	}	
	$resp['session'] = 'e' ;		// session ended
}
else {
	$oldsessiontime = $_SESSION['xtime'] ;
	if( empty( $noupdatetime ) )
		$_SESSION['xtime']=$xt ;
	$logon=true ;
	$resp['session'] = 'l' ;		// session login
}

session_write_close();

return ;
?>