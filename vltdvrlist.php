<?php
// vltdvrlist.php - get vlt (live track) dvr (vehicle) list
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
		
		// create a temporary table for vlt
		$sql = "CREATE TABLE IF NOT EXISTS `_tmp_tdweb` (
			`vname`   varchar(20) NOT NULL,
			`mtime` int NOT NULL,
			`user` varchar (40) DEFAULT NULL,
			`session` varchar(80) DEFAULT NULL,
			`vdata` varchar(10000) DEFAULT NULL
		) ENGINE = MEMORY ; " ;

		$conn->query($sql) ;
				
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];

		if( empty($_REQUEST['vltserial'])) {
			$serialno = rand(101, 99999999) ;
		}
		else {
			$serialno = $_REQUEST['vltserial'] ;
			$resp['vltserial'] = $_REQUEST['vltserial'] ;
		}
		$cmd = '23' ;				// AVL_DVR_LIST
		
		$xml = new SimpleXMLElement('<tdwebc></tdwebc>') ;
		$xml->callbackurl = $avlcbserver . dirname($_SERVER['REQUEST_URI']).'/' . $avlcbapp ;
		$xml->session = $vltsession ;
		$xml->serialno = $serialno ;
		$xml->target->avl='' ;
		$xml->command=$cmd ;	
		$xml->avlp->dvrlist='' ;
	
		// require dvr phone list
		$sql = "SELECT DISTINCT `vehicle_phone` FROM `vehicle` WHERE `vehicle_phone` <> '' ;";
		if( $result = $conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				$xml->avlp->dvrlist->dvritem[] = $row['vehicle_phone'] ;
			}
		}
		
		@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );
		if( empty($avlxml) ) {
			$resp['errormsg'] = "AVL Service error, no contents!" ;
			goto done ;
		}

		@$tdwebc = new SimpleXMLElement($avlxml) ;
		
		if( empty($tdwebc->status) ) {
			$resp['errormsg'] = "AVL Service error!";
			goto done ;
		}
		
		$resp['status'] = (string)($tdwebc->status) ;
		if( $resp['status'][0] == 'E' || $resp['status'][0] == 'e' ) {
			// error
			if( empty( $tdwebc->errormsg ) ) {
				$resp['errormsg'] = "AVL error!" ;
			}
			else {
				$resp['errormsg'] = $tdwebc->errormsg ;
			}
			goto done ;
		}
	
		// wait for avl events
		$mtime = time();

		$sql = "INSERT INTO `_tmp_tdweb` ( `vname`, `mtime`, `user`, `session`, `vdata` ) VALUES ('vltlistener', '$mtime', '$_SESSION[user]', '$vltsession', '1' ); ";
		$conn->query($sql) ;
		
		if( strcasecmp( $resp['status'], 'OK' )==0 ) {
			if( !empty( $tdwebc->avlp ) ) {
				$vdata = $conn->escape_string(json_encode( $tdwebc ));
				$sql = "INSERT INTO `_tmp_tdweb` (`vname`, `mtime`, `user`, `session`, `vdata` ) VALUES ( 'vltevent', '$mtime', '$_SESSION[user]', '$vltsession', '$vdata' );";
				$conn->query($sql) ;
				//$resp['tdwebc'] = array();
				//$resp['tdwebc'][] = $tdwebc ;
			}
			else {
				$resp['errormsg'] = "Empty data.";
			}			
		}
		else {
			$resp['errormsg'] = "Data pending.";
		}
		$resp['res'] = 1 ;
	}
	
	$conn->close();
	
done:	
	echo json_encode($resp);
?>