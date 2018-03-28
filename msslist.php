<?php
// msslist.php - list MSS settings
// Requests:
//      none
// Return:
//      JSON array of MSS settings
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql="SELECT * FROM mss ;" ;
		if($result=$conn->query($sql)) {
			$msslist = array();
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$msslist[]=$row;
			}
			$resp = $msslist ;
			$result->free();
		}
	}
	echo json_encode($resp);
?>