<?php
// vltreport.php - persist query for vlt messages
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
// Return:
//      JSON
// By Dennis Chen @ TME	 - 2013-11-21
// Copyright 2013 Toronto MicroElectronics Inc.

	$noupdatetime = 1 ;
    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
	
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];

		$errno = 0;
		$errstr='';
		$sevent=stream_socket_server("udp://127.0.0.1:56328", $errno , $errstr, STREAM_SERVER_BIND );
		if( $sevent ) {
			stream_set_timeout( $sevent, 30 );
		}
		
		$tdwebc = array();
		// wait for message from AVL service
		$mtime = time();		
		$starttime = $mtime ;
		
		$conn->query("UPDATE `_tmp_tdweb` SET `mtime` = '$mtime' WHERE `vname` = 'vltlistener' AND `session` = '$vltsession' ") ; 

		while( ($mtime - $starttime)<600 ) {
			// $conn->query("LOCK TABLES _tmp_tdweb WRITE;");

			if( $result = $conn->query("SELECT * FROM `_tmp_tdweb` WHERE `vname` = 'vltevent' AND `session` = '$vltsession' " ) ) {
				while( $row=$result->fetch_array() ) {
					if( !empty( $row['vdata'] ) ) {
						$vdata = json_decode( $row['vdata'], true ) ;
						$tdwebc[] = $vdata ;
					}
				}
				$result->free();
				$conn->query("DELETE FROM `_tmp_tdweb` WHERE `vname` = 'vltevent' AND `session` = '$vltsession' ");
			}
			// $conn->query("UNLOCK TABLES;");
			if( !empty( $tdwebc ) ) {
				break;
			}
		
			set_time_limit(60);
			if( $sevent ) {
				fread($sevent, 500);
			}
			else {
				sleep(2);
			}
			$mtime = time();
		}
		
		if( $sevent ) 
			fclose($sevent);
			
		if( !empty( $tdwebc ) ) {
			$resp['tdwebc'] = $tdwebc ;
			$resp['res'] = 1 ;
		}
		else {
			$resp['errormsg'] = "Empty tdwebc message, time out maybe.";
		}
		
	}
	
done:	
	if( !empty( $_REQUEST['vltserial'] ) ) {
		$resp['vltserial'] =  $_REQUEST['vltserial'] ;
	}
	echo json_encode($resp);	
?>
