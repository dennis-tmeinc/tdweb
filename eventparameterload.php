<?php
// eventparameterload.php - load default event parameter 
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql="SELECT * FROM report_parameter ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$resp = $result->fetch_assoc() ;
			$resp['startTime']=(new DateTime)->format("Y-m-d 0:00:00");
			$resp['endTime']=(new DateTime)->format("Y-m-d H:i:s");
		}
	}
	echo json_encode( $resp );

?>