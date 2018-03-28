<?php
// avlload.php - load avl settings
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-11-11
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql="SELECT avlServer, avlPassword FROM tdconfig ;" ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_assoc() ) {
				$resp['avl'] = array();
				$resp['avl']['avlServer'] = $row['avlServer'] ;
				$resp['avl']['passlen'] = strlen($row['avlPassword']) ;
				$resp['res'] = 1 ;
			}
			$result->free();
		}
	}
	echo json_encode( $resp );

?>