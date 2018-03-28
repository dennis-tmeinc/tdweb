<?php
// drivebydeletereport.php - delete drive report and events
// Request:
//      tag: tag name (filename)
//      status: 'ok' | 'deleted' 
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-05-29
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {
	
		$sql = "SELECT `report_status` FROM Drive_By_Event WHERE `idx` = $_REQUEST[tag] " ;
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array() ) {
				if( $row[0] == 'deleted' ) {
					$sql = "UPDATE Drive_By_Event SET `report_status` =  'trash' WHERE `idx` = $_REQUEST[tag] " ;
				}
				else { 
					$sql = "UPDATE Drive_By_Event SET `report_status` =  'deleted', `report_deleteby` = '$_SESSION[user]', `report_deletetime` = NOW() WHERE `idx` = $_REQUEST[tag] " ;
				}
				if( $conn->query($sql) ) {
					$resp['res'] = 1 ;
					$resp['tag'] = $_REQUEST['tag'] ;
				}
			}
			$result->free();
		}
	}
	
	echo json_encode($resp);
?>