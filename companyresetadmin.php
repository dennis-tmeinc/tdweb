<?php
// companyresetadmin.php - reset company admin password (to empty)
// Request:
//		db : company database name
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2023-01-30
// Copyright 2023 Toronto MicroElectronics Inc.
	
    include_once 'session.php' ;
	
	header("Content-Type: application/json");

	if( empty($_REQUEST['db']) ) {
		$resp['errormsg'] = "Database name can not be empty!" ;
		goto done ;
	}
	
	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" ) {
        $conn = new mysqli($smart_host, $smart_user, $smart_password, $_REQUEST['db']);
        if( $conn ) {
            $sql = "UPDATE app_user SET user_password = '', user_type = 'admin' WHERE `user_name` = 'admin' ";
            $conn->query($sql);
            $resp['res'] = 1;
        }
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
done:	
	echo json_encode($resp);
?>