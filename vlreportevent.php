<?php
// vlreportevent.php -  get vl event reports
// Requests:
//      
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2013-05-31
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
				
		$mapfilter = $_SESSION['mapfilter']['filter'] ;
		$param =  $_SESSION['mapfilter'];
		$resp['summary']=array();

		// Stopping Total
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 1 ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['stoptotal']=$row[0] ;
			$result->free();
		}
		
		// Idling Total
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 4 ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['idletotal']=$row[0] ;
			$result->free();
		}

		// Parking Total
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 18 ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['parkingtotal']=$row[0] ;
			$result->free();
		}
		
		// Designated Stops
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 17 ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['desstoptotal']=$row[0] ;
			$result->free();
		}
			
		// Marked Events
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 23 ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['events']=$row[0] ;
			$result->free();
		}	
		
		// Drive By Events
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 40 ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['drivebytotal']=$row[0] ;
			$result->free();
		}
		
		$resp['serial'] = $_REQUEST['serial'] ;
		$resp['res'] = 1 ;
		
		$conn->close();
	}
	echo json_encode( $resp );
?>