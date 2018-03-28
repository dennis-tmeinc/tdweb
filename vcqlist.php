<?php
// vcqlist.php - get video requests list (cell table)
// Requests:
//		none
// Return:
//      JSON array of video requests
// By Dennis Chen @ TME	 - 2016-12-14
// Copyright 2016 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$sql="SELECT * FROM vcq ;" ;
		if($result=$conn->query($sql)) {
			$vcqlist = array();
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$vcqlist[]=$row;
			}
			echo json_encode($vcqlist);
			$result->free();
		}
		else {
			echo "[]" ;
		}
	}
?>