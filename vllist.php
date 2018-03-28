<?php
// vllist.php - list vehicle event info
// Requests:
//      vl_id: vehicle event index
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		if( empty($_REQUEST['vl_id']) ) {	// no id
			$resp['errormsg']="No id specified" ;
		}
		else {
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			$sql = "SELECT * FROM vl WHERE `vl_id` = $_REQUEST[vl_id] ;" ;
			$result=$conn->query($sql);
			if( !empty($result)) {
				$resp['vl'] = $result->fetch_array( MYSQLI_ASSOC ) ;
				$resp['res'] = 1 ;
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}
		}
	}
	echo json_encode( $resp );
?>