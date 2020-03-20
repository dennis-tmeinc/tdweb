<?php
// emailserverload.php - load email server configure (default email)
// Requests:
//      none
// Return:
//      JSON object, res=1 for success, email = email parameters
// By Dennis Chen @ TME	 - 2017-03-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		@$tdconf = simplexml_load_file( $td_conf, "SimpleXMLElement" , LIBXML_NOBLANKS  );
		if( !$tdconf ) {
			$tdconf = new SimpleXMLElement( "<tdconfig></tdconfig>" );
		}
		if( !empty( $tdconf -> emailserver ) ) {
			// $tdconf -> emailserver ->authenticationPassword = '********' ;
			$resp['email'] = array();
			foreach ($tdconf -> emailserver->children() as $key => $value) {
				$resp['email'][(string)$key] = (string)$value ;
			}
			$resp['res'] = 1 ;
		}
	}
	echo json_encode( $resp );

?>