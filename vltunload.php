<?php
// vltunload.php - unload vlt session
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
// Return:
//      JSON array of vehicle list
// By Dennis Chen @ TME	 - 2013-11-18
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
				
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];
		
		$mtime = time();
		$ltime = $mtime - $session_timeout ;
		// Delete every thing for this session
		$conn->query("DELETE FROM `_tmp_tdweb` WHERE `session` = '$vltsession' OR `mtime` < $ltime ");
		
		if( $result = $conn->query("SELECT count(*) FROM `_tmp_tdweb`") ) {
			if( $row=$result->fetch_array() ) {
				if( $row[0] == 0 ) {
					$conn->query("DROP TABLE `_tmp_tdweb`");
				}
			}
		}
		$conn->close();
	
		$sevent = stream_socket_client("udp://127.255.255.255:56328");
		if( $sevent ) {
			fwrite($sevent,"avlp");
			fclose($sevent);
		}
	
		$xml = new SimpleXMLElement('<tdwebc></tdwebc>') ;
		$xml->session = $vltsession ;
		$xml->serialno = $serialno ;
		$xml->target->avl='' ;
		$xml->avlp='' ;
		$xml->command='9999' ; 		// AVL_AUTO_REPORT_CONF(26)
		@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );	

		$resp['res'] = 1 ;
	}
	
done:	
	echo json_encode($resp);
?>