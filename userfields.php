<?php
// userfields.php - get user fields information
// Requests:
// Return:
//      JSON array of user fields information
// By Dennis Chen @ TME	 - 2013-07-10
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		$resp['fields']=array();
		
		$sql="DESCRIBE `app_user`;" ;
		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array() ) {
				$f = sscanf($row['Type'],"varchar(%d)");
				if( !empty($f[0])) {
					$resp['fields'][] = array( 'name' => $row['Field'] , 'maxlength' => $f[0] );
				}
			}
			$resp['res'] = 1 ;
			$result->free();
		}
		else {
			$resp['errormsg']="SQL ERROR!" ;
		}
		$conn->close();
	}
	echo json_encode($resp);
?>