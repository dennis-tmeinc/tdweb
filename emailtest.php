<?php
// emailtest.php - testing email server configuration
// Requests:
//      email parameter
// Return:
//      JSON object, res=1 for success, msg: email test program output
// By Dennis Chen @ TME	 - 2019-03-29
// Copyright 2019 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		$emailtest = new SimpleXMLElement( "<emailTest></emailTest>" );
		
		foreach( $_REQUEST as $key => $value )
		{
			$emailtest -> $key = $value ;
		}

		$emailtestfile = tempnam($cache_dir, 'st');
		$emailtest->asXML ( $emailtestfile );
		
		// execute email testing program
		$testprogram = "bin\\email\\SendTestMail.exe";
		$p = popen( "$testprogram $emailtestfile", 'r' );
		$msg ="" ;
		
		while( !feof( $p ) ) {
			if( $r = fread( $p, 100) ) {
				$msg .= $r;
			}
			else {
				break;
			}
		}
		$resp['res'] = 1 ;
		$resp['msg'] = $msg ;
		pclose($p);
		unlink( $emailtestfile );
	}
	echo json_encode($resp);
?>