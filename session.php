<?php
// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

require_once 'sessionstart.php' ; 
	
$xt = time();
if( empty($_SESSION['user']) ||
	empty($_SESSION['user_type']) ||
	empty($_SESSION['xtime']) || 
    $xt<$_SESSION['xtime'] ||
	$xt>$_SESSION['xtime']+$session_timeout ||
	empty($_SESSION['clientid']) ||
	$_SESSION['clientid']!=hash("md5", $_SERVER['HTTP_USER_AGENT'].":".$_SERVER['REMOTE_ADDR']) 
	){
	// logout
	unset( $_SESSION['user'] );
	unset( $_SESSION['user_type'] );
	$logon=false ;
}
else {
	if( empty($noxtime) ) {
		$_SESSION['xtime']=$xt ;
	}
	$logon=true ;
}

/* AJAX check  */
$resp=array('res' => 0);
if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	/* ajax requests */
	if( !$logon ) {
		$resp['errormsg']="Session time out!";
	}
}
else {
	/* ui pages */
	$ui_theme=$default_ui_theme ;
	if( !empty($_SESSION['ui']) && $_SESSION['ui'] != "undefined" )
		$ui_theme = $_SESSION['ui'] ;
	$_SESSION['lastpage']=$_SERVER['REQUEST_URI'] ;
	if( !$logon ) 
		header( 'Location: logon.php' ) ;
}

session_write_close();

return $logon ;

?>