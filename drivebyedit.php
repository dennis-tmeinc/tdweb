<?php
// drivebyedit.php - edit a sigle by tag
// Request:
//      id: tag name (idx)
//		oper: 'edit'  ( for editing, generated by jqgrid )
//      imgquality: image quality (Good/Poor/Bad)
//      plateofviolator: plate number of violator
//      notes: notes of this violator
// 		State:	location
//		City:	location
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-07-11
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {
		if( $_REQUEST['oper'] == 'edit' ) {	// editting
			$sql = "UPDATE drive_by_event SET `imgquality` =  '$_REQUEST[imgquality]', `Plateofviolator` =  '$_REQUEST[Plateofviolator]', `notes` = '$_REQUEST[notes]', `State` =  '$_REQUEST[State]', `City` =  '$_REQUEST[City]' WHERE `idx` = $_REQUEST[id] " ;
			if($result=$conn->query($sql)) {
				$resp['res'] = 1 ;
			}
		}
	}
	
	echo json_encode($resp);
?>