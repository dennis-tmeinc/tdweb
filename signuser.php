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

// reload config file
include 'config.php' ;

unset($_SESSION['clientid']);

if( !empty($_REQUEST['c']) ) {
	$_SESSION['clientid'] = $_REQUEST['c'];
	$clientcfg = "$client_dir/$_SESSION[clientid]/config.php" ;
	if( file_exists ( $clientcfg ) ) {
		include $clientcfg ;
	}
	else {
		$resp['errormsg'] = "Client ID error!" ;
		goto done;		
	}
}

unset($_SESSION['user']) ;
$_SESSION['xtime']=time() ;
$_SESSION['country'] = "US";	// default country

if( !empty($support_multicompany) && empty($_REQUEST['c']) && strcasecmp($_REQUEST['user'],"SuperAdmin")==0 ) {

	$_SESSION['xuser'] = "SuperAdmin"  ;
	$_SESSION['xuser_type'] = $_SESSION['xuser'] ;
	$_SESSION['welcome_name'] = "SuperAdmin" ;
	
	$nonce=' ' ;
	$hexchar="0123456789abcdefghijklmnopqrstuvwxyz" ;
	for( $i=0; $i<64; $i++) {
		$nonce[$i] = $hexchar[mt_rand(0,35)] ;
	}
	$_SESSION['nonce'] = $nonce ;
	$user_password = file_get_contents( "$client_dir/sapass", "r" );
	$user_password = trim($user_password);
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

}
else {
	if( empty($smart_database) ) {
		$resp['errormsg'] = "User name error!" ;
		goto done;
	}

	// reconnect MySQL
	@$conn = new mysqli($smart_host, $smart_user, $smart_password, $smart_database );

	if( empty($conn) ) {
		$resp['errormsg'] = "Database error!" ;
		goto done;
	}
	
	// escaped string for SQL
	$esc_req=array();
	foreach( $_REQUEST as $key => $value )
	{
		$esc_req[$key]=$conn->escape_string($value);
	}
	
	$emailid = false ;
	$at = strstr($_REQUEST['user'], "@");
	if( !empty($at) ){
		$dot = strstr($at, ".");
		if( !empty($at) ) {
			$emailid = true;
		}
	}

	if( $emailid ) {
		$sql="SELECT user_name, user_password, user_type, first_name, last_name FROM app_user WHERE email = '$esc_req[user]';" ;
	}
	else {
		$sql="SELECT user_name, user_password, user_type, first_name, last_name FROM app_user WHERE user_name = '$esc_req[user]';" ;
	}

	if( $result=$conn->query($sql) ) {
		
		if( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
			$_SESSION['xuser'] = $row['user_name']  ;
			$_SESSION['xuser_type'] = $row['user_type'] ;
			$_SESSION['welcome_name'] = trim( $row['first_name'].' '.$row['last_name'] );
			if( empty( $_SESSION['welcome_name'] ) ) 
				$_SESSION['welcome_name']=$_SESSION['xuser'] ;

			// Add company name
			if( !empty($company_root) && vfile_exists($company_root."/companyinfo.xml") ) {
				@$xmlcontents = vfile_get_contents( $company_root."/companyinfo.xml" ) ;
				if( !empty($xmlcontents) ) {
					@$companyinfo = new SimpleXMLElement( $xmlcontents ) ;
					if( !empty( $companyinfo ) && !empty( $companyinfo->CompanyName ) ) {
						$_SESSION['welcome_name'] = $_SESSION['welcome_name']."  -  ".$companyinfo->CompanyName  ;
						if( isset( $companyinfo->Country ) ){
							$country = (string) $companyinfo->Country;
							$country = trim($country);
						}
					}
				}
			}

			// country code support, for display units (imperial)
			if( !empty($country) && ($country == "CA" || strcasecmp($country,"Canada") == 0 )) {
				$_SESSION['country'] = "CA";
			}
			else {
				// default country "US"
				$_SESSION['country'] = "US";
			}

			// setup password challenge			
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
		}
		$result->free();
	}
}

done:	
	if( !$resp['res'] ) {
		// clear session
		$_SESSION = [];
	} ;
	session_write() ;

	echo json_encode($resp) ;

	// forced close connection
	header( "Content-Length: ". ob_get_length() );
	header( "Connection: close" );

	ob_flush();
	flush();
	ignore_user_abort( true );
	
	// clean up
	$session_path = session_save_path () ;
	$cleanfile = $session_path.'/sessclrk7l9eln8hvg27th3bdsmtvq' ;
	$cf = fopen($cleanfile, "c+");
	if( flock($cf, LOCK_EX|LOCK_NB) ){
		$xtime = time() ;
		@$ptime = (int)fgets( $cf );
		if( empty($ptime) ) { 
			$ptime = $xtime - 7*24*3600 ;
		}

		if( $xtime - $ptime >= 18*3600 ) {
			clearstatcache();
			
			// clean old session files
			foreach (glob($session_path.'/sess_*') as $filename) {
				if( $xtime - fileatime($filename) > 10*24*3600 ) {
					@unlink($filename);
				}
			}

			// clean old video cache 
			foreach (glob($cache_dir.'/*') as $filename) {
				if( $xtime - fileatime($filename) > 15*24*3600 ) {
					@unlink($filename);
				}
			}

			// rotate apache logs
			foreach (glob($_SERVER["DOCUMENT_ROOT"] . "/../Apache/logs/*log*" ) as $filename) {
				if( filesize( $filename ) > 64*1024 ) {
					$f = fopen($filename,"r+");
					fseek($f, -40000, SEEK_END );
					fgets($f);  // skip a line
					$buf = fread($f, 64*1024);
					fseek($f,0);
					ftruncate($f,0);
					fwrite($f,$buf);
					fclose($f);
				}
			}

			// update ptime
			fseek($cf,0);
			ftruncate($cf,0);
			fwrite($cf,strval($xtime));
		}
	}
	fclose($cf);
?>