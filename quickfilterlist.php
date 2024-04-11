<?php
// quickfilterlist.php - list quick filter
// Requests:
//      quickfiltername: load one quick filter of this name, list all quick filter if not provided.
// Return:
//      JSON array of quick filter list
// By Dennis Chen @ TME	 - 2021-04-29
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
		if( empty($_REQUEST['quickfiltername']) ) {
			$sql="SELECT `name` FROM quickfilter; " ;
		}
		else {
			$sql="SELECT * FROM quickfilter WHERE `name` = '$_REQUEST[quickfiltername]';" ;
		}
		if($result=$conn->query($sql)) {
			$resp['filterlist']=array();
			while($row=$result->fetch_array(MYSQLI_ASSOC)){
				$row['quickfiltername'] = $row['name'];
				$resp['filterlist'][]=$row ;
			}
			$resp['res']=1; 	// success
			$result->free();
		}
		else {
			$resp['errormsg']="SQL error: ".$conn->error ;
		}
	}
	echo json_encode( $resp );

?>