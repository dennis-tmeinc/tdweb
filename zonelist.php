<?php
// zonelist.php - list map zone
// Requests:
//      index: map zone index
//      name: map zone name
//      nameonly: to list only map zone names
// Return:
//      JSON array of zone info
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		if( !empty($_REQUEST['index']) ) {
			$sql="SELECT * FROM zone WHERE `index` = $_REQUEST[index] AND (`type` = 1 OR `user` = '$_SESSION[user]') ;" ;
		}
		else if( !empty($_REQUEST['name']) ) {
			$sql="SELECT * FROM zone WHERE `name` = '$_REQUEST[name]' AND (`type` = 1 OR `user` = '$_SESSION[user]') ;" ;
		}
		else {
			if( empty($_REQUEST['nameonly']) )
				$sql="SELECT * FROM `zone` WHERE `type` = 1 OR `user` = '$_SESSION[user]' ;" ;
			else 
				$sql="SELECT `name` FROM `zone` WHERE `type` = 1 OR `user` = '$_SESSION[user]' ;" ;
		}
		if( !empty($sql) ) {
			if($result=$conn->query($sql)) {
				$zonelist = array();
				while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
					if( $row['name']=='No Restriction' || $row['name']=='User Define' ) continue ;
					$zonelist[]=$row;
				}
				$result->free();
				$resp['zonelist'] = $zonelist ;
				$resp['res'] = 1 ;
			}
		}
	}

done:	
	echo json_encode( $resp );
?>