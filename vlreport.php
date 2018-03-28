<?php
// vlreport.php -  get map events start/end time
// Requests:
//      serial: serial number
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2013-05-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		$mapfilter = $_SESSION['mapfilter']['filter'] ;
		$param =  $_SESSION['mapfilter'];

		$resp['summary']=array();
			
		// Start time, End time
		$sql="SELECT min(vl_datetime), max(vl_datetime) FROM vl WHERE $mapfilter  ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['starttime']=$row[0] ;
			$resp['summary']['endtime']=$row[1] ;
			$result->free();
		}
		
		if( empty( $resp['summary']['starttime'] )) {
			$resp['summary']['starttime']='NA' ;
		}		
		if( empty( $resp['summary']['endtime'] )) {
			$resp['summary']['endtime']='NA' ;
		}
		
		$resp['serial'] = $_REQUEST['serial'] ;
		$resp['res'] = 1 ;
		
	}
	echo json_encode( $resp );
?>