<?php
// grouplist.php - listing vehicle group
// Requests:
//      name: to list one group by this name
//      nameonly: to list group name only
// Return:
//      JSON array of vehicle group info
// By Dennis Chen @ TME	 - 2013-06-19
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		if( !empty($_REQUEST['name']) ) {
			$sql="SELECT * FROM vgroup WHERE `name` = $_REQUEST[name];";
		}
		else if( !empty($_REQUEST['nameonly']) && $_REQUEST['nameonly'] == "y" ) {
			$sql="SELECT `name` FROM vgroup;" ;
		}
		else {
			$sql="SELECT * FROM vgroup ;" ;
		}
		if($result=$conn->query($sql)) {
			$grouplist = array();
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$grouplist[]=$row;
			}
			echo json_encode($grouplist);
			$result->free();
		}
		else {
			echo "[]" ;
		}
	}
	else {
			echo "[]" ;
	}
?>