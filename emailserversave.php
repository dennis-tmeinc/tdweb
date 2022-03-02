<?php
// emailserversave.php - save email server configure (default email)
// Requests:
//      email parameter
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2017-03-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		// escaped string for SQL
		$esc_req=array();

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