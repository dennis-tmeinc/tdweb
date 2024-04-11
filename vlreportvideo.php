<?php
// vlmap.php -  get map events on map
// Requests:
//      maps boundary (east, west, north, south)
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$mapfilter = $_SESSION['mapfilter']['filter'] ;
	
		$vehiclelist = $_SESSION['mapfilter']['vehiclelist'] ;
		$startTime = $_SESSION['mapfilter']['startTime'] ;
		$endTime = $_SESSION['mapfilter']['endTime'] ;
		
		$resp['summary']=array(
			'hoursofvideo' => 0,
			'videoclips' => 0	
		);

		$hoursofvideo = 0;
		$videoclips = 0;
		
		//$sql = "SELECT count(*), sum(TimeStampDiff(SECOND, time_start, time_end)) FROM `videoclip` WHERE vehicle_name in ($vehiclelist) AND ((time_start BETWEEN '$startTime' AND '$endTime') OR (time_end BETWEEN '$startTime' AND '$endTime'));";		
		$sql = "SELECT count(*), sum(TimeStampDiff(SECOND, time_start, time_end)) FROM `videoclip` WHERE ((time_start BETWEEN '$startTime' AND '$endTime') OR (time_end BETWEEN '$startTime' AND '$endTime'))";		
		if( !empty($vehiclelist) ) {
			$sql .= " AND vehicle_name in ($vehiclelist)";
		}

		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$hoursofvideo = $row[1] ;
				$videoclips = $row[0];
			}
			$result->free();
		}

		$resp['summary']['hoursofvideo']= round ( $hoursofvideo/3600, 1 ) ;
		$resp['summary']['videoclips']= $videoclips ;

		$resp['serial'] = $_REQUEST['serial'] ;
		$resp['res'] = 1 ;
	}
	echo json_encode( $resp );
?>