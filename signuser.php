<?php
// signuser.php - First step of user sign in 
//   Load user info, create password hash
//   parameter:
//		user: userid, c: clientid, n: nonce
// By Dennis Chen @ TME	 - 2014-04-23
// Copyright 2013 Toronto MicroElectronics Inc.

$noredir = 1 ;
require 'session.php' ;
require 'vfile.php' ;

header("Content-Type: application/json");
$resp=array();
$resp['res']=0 ;

if( !empty($_REQUEST['c']) ) {
	$_SESSION['clientid'] = $_REQUEST['c'];
	session_write() ;
	$clientcfg = 'client/'.$_SESSION['clientid'].'/config.php' ;
	if( file_exists ( $clientcfg ) ) {
		include $clientcfg ;
		if(	$database_persistent ) {
			$smart_server = "p:".$smart_host ;
		}
		else {
			$smart_server = $smart_host ;
		}
	}
	else {
		$resp['errormsg'] = "Client ID error!" ;
		goto done;		
	}
}
	
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
			
			// Add company name
			if( !empty($company_root) && vfile_exists($company_root."/companyinfo.xml") ) {
				@$xmlcontents = vfile_get_contents( $company_root."/companyinfo.xml" ) ;
				if( !empty($xmlcontents) ) {
					@$companyinfo = new SimpleXMLElement( $xmlcontents ) ;
					if( !empty( $companyinfo ) && !empty( $companyinfo->CompanyName ) ) {
						$_SESSION['welcome_name'] = $_SESSION['welcome_name']."  -  ".$companyinfo->CompanyName  ;
					}
				}
			}
			
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