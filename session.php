<?php
// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

require_once 'config.php' ;

// setup time zone
date_default_timezone_set($timezone) ;	
// persistent database connection
if(	$database_persistent ) {
	$smart_server = "p:".$smart_host ;
}
else {
	$smart_server = $smart_host ;
}

session_save_path( $session_path );
session_name( $session_idname );
if( !empty($_REQUEST[session_name()]) ) {
	session_id ($_REQUEST[session_name()]);
}
session_start();

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
	$xt>$_SESSION['xtime']+$session_timeout ||
	empty($_SESSION['clientid']) ||
	empty($_SESSION['release']) ||
	$_SESSION['clientid']!=$_SERVER['REMOTE_ADDR'] 
	){
	// logout
	unset($_SESSION['user']) ;
	session_write_close();
	$resp['errormsg']="Session error!";
	$resp['session'] = 0 ;
	$logon=false ;
	/* AJAX check */
	if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
		header( 'Location: logon.php' ) ;
	}	
	$resp['session'] = 'e' ;		// session ended
}
else {
	$oldsessiontime = $_SESSION['xtime'] ;
	$_SESSION['xtime']=$xt ;
	session_write_close();
	$logon=true ;
	$resp['session'] = 'l' ;		// session login
	/* page ui */
	if( !empty($_COOKIE['ui']))
		$default_ui_theme = $_COOKIE['ui'] ;	
}

return ;

?>