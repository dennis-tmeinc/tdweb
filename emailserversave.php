<?php
// emailserversave.php - save email server configure to (smartapp) tdconfig (default email) 
// Requests:
//      email parameter
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2022-03-21
// Copyright 2022 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$tdconf = simplexml_load_file( $td_conf );
		if( !$tdconf ) {
			$tdconf = new SimpleXMLElement( "<tdconfig></tdconfig>" );
		}

		foreach( $_REQUEST as $key => $value )
		{
			$tdconf -> emailserver -> $key = $value ;
		}
		
		$tdconf->asXML ( $td_conf );
		
	}
	echo json_encode($resp);
?>