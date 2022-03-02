<?php
// vmqlist.php - get video requests list
// Requests:
//		none
// Return:
//      JSON array of video requests
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$sql="SELECT * FROM vmq ;" ;
		if($result=$conn->query($sql)) {
			$vmqlist = array();
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$vmqlist[]=$row;
			}
			echo json_encode($vmqlist);
			$result->free();
		}
		else {
			echo "[]" ;
		}
	}
?>