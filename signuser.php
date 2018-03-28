<?php
// signuser.php - First step of user sign in 
//   Load user info, create password hash
//   parameter:
//		user: userid, c: clientid, n: nonce
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

$noredir = 1 ;
require 'session.php' ;

if( !empty($_REQUEST['c']) ) {
	// unset database 
	$smart_host="";
	$smart_user="" ;
	$smart_password="" ;
	$smart_database="" ;
	
	$_SESSION['clientid'] = $_REQUEST['c'];
	session_write() ;
	$clientcfg = 'client/'.$_SESSION['clientid'].'/config.php' ;
	if( file_exists ( $clientcfg ) ) {
		require $clientcfg ;
	}
	
	if(	$database_persistent ) {
		$smart_server = "p:".$smart_host ;
	}
	else {
		$smart_server = $smart_host ;
	}
}

header("Content-Type: application/json");

$resp=array();
$resp['res']=0 ;
	
// MySQL connection
@$conn = new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
if( empty($conn) ) {
	$resp['errormsg'] = "Database error!" ;
	goto done;
}

unset($_SESSION['user']) ;

// escaped string for SQL
$esc_req=array();
foreach( $_REQUEST as $key => $value )
{
	$esc_req[$key]=$conn->escape_string($value);
}	

if( !empty($support_multicompany) && empty($_REQUEST['c']) && strcasecmp($_REQUEST['user'],"SuperAdmin")==0 ) {

	$_SESSION['xuser'] = "SuperAdmin"  ;
	$_SESSION['user_type'] = $_SESSION['xuser'] ;
	$_SESSION['welcome_name'] = "SuperAdmin" ;
	$nonce=' ' ;
	$hexchar="0123456789abcdefghijklmnopqrstuvwxyz" ;
	for( $i=0; $i<64; $i++) {
		$nonce[$i] = $hexchar[mt_rand(0,35)] ;
	}
	$_SESSION['nonce'] = $nonce ;
	$user_password = file_get_contents( "client/sapass", "r" );
	if( strlen( $user_password ) > 25 && $user_password[0] == '$' ) {
		$keys=explode('$', $user_password );
		$_SESSION['keytype'] = $keys[1] ;
		$_SESSION['key'] = $keys[2] ;
		$_SESSION['salt'] = $keys[3] ;
	}
	else {
		$salt=' ' ;
		for( $i=0; $i<32; $i++) {
			$salt[$i] = $hexchar[mt_rand(0,35)] ;
		}
		$_SESSION['keytype'] = '0' ;
		$_SESSION['salt'] = $salt ;
		$_SESSION['key'] = hash("md5", $_SESSION['xuser'].":".$salt.":".$user_password);
	}
	$resp['user']=$_SESSION['xuser'] ;
	$resp['slt']=$_SESSION['salt'] ;
	$resp['keytype']=$_SESSION['keytype'] ;
	$resp['nonce']=$_SESSION['nonce'] ;
	$resp['res']=1 ;
	session_write();

}
else {
	$sql="SELECT user_name, user_password, user_type, first_name, last_name FROM app_user WHERE user_name = '$esc_req[user]';" ;

	if( $result=$conn->query($sql) ) {
		if( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$_SESSION['xuser'] = $row['user_name']  ;
			$_SESSION['user_type'] = $row['user_type'] ;
			$_SESSION['welcome_name'] = $row['first_name'].' '.$row['last_name'] ;
			if($_SESSION['welcome_name']==' ') $_SESSION['welcome_name']=$_SESSION['xuser'] ;
			$nonce=' ' ;
			$hexchar="0123456789abcdefghijklmnopqrstuvwxyz" ;
			for( $i=0; $i<64; $i++) {
				$nonce[$i] = $hexchar[mt_rand(0,35)] ;
			}
			$_SESSION['nonce'] = $nonce ;
			if( strlen( $row['user_password'] ) > 25 && $row['user_password'][0] == '$' ) {
				$keys=explode('$', $row['user_password'] );
				$_SESSION['keytype'] = $keys[1] ;
				$_SESSION['key'] = $keys[2] ;
				$_SESSION['salt'] = $keys[3] ;
			}
			else {
				$salt=' ' ;
				for( $i=0; $i<32; $i++) {
					$salt[$i] = $hexchar[mt_rand(0,35)] ;
				}
				$_SESSION['keytype'] = '0' ;
				$_SESSION['salt'] = $salt ;
				$_SESSION['key'] = hash("md5", $row['user_name'].":".$salt.":".$row['user_password']);
			}
			$resp['user']=$_SESSION['xuser'] ;
			$resp['slt']=$_SESSION['salt'] ;
			$resp['keytype']=$_SESSION['keytype'] ;
			$resp['nonce']=$_SESSION['nonce'] ;
			$resp['res']=1 ;
			session_write();
		}
		$result->free();
	}
	$conn->close();
}
	
done:	

	// flush contents before do more cleaning jobs
	$content = json_encode($resp);
	header( "Content-Length: ".strlen($content) );
	echo $content ;
	ob_flush();
	flush();
	
// clean old session files
$xt = time() ;
// clean old session files
foreach (glob($session_path.'/sess_*') as $filename) {
	if( filemtime($filename) + 86400 < $xt ) {
		@unlink($filename);
	}
}

?>