<?php
// vlreporttravel.php -  report event speeding counts
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
		$mapfilter = $_SESSION['mapfilter']['filter'] ;
		$speedLimit = $_SESSION['mapfilter']['speedLimit'] ;
		$vehiclelist = $_SESSION['mapfilter']['vehiclelist'] ;
		$endTime = $_SESSION['mapfilter']['endTime'] ;
		$startTime = $_SESSION['mapfilter']['startTime'] ;		

		$resp['summary']=array();

		$speedings = 0 ;
	
		if( $_SESSION['mapfilter']['bSpeeding'] ) {
	
			$sql = "SELECT vl_vehicle_name, vl_datetime FROM `vl` WHERE ( vl_datetime BETWEEN '$startTime' AND '$endTime' ) AND `vl_speed` > $speedLimit";
			if( !empty($vehiclelist) ) {
				$sql .= " AND vehicle_name in ($vehiclelist)";
			}			
			$sql .= " ORDER BY vl_vehicle_name, vl_datetime";
			
			$result=$conn->query($sql,MYSQLI_USE_RESULT);	// set MYSQLI_USE_RESULT for huge data
			if( $result ){
				$pvehicle = "";
				$ptime = 0 ;
				$speed = false ;
				while( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$ntime = strtotime( $row[1] );
					if( $pvehicle == $row[0] ) {	// same vehicle?
						$dtime = $ntime - $ptime ;
						if( $dtime > 60 ) {			// if in lower speed more than 60 seconds, count as another speeding
							$speedings++ ;
						}
						$ptime = $ntime ;
					}
					else {
						$pvehicle = $row[0] ;
						$ptime = $ntime ;
						$speedings++ ;
					}
				}
				$result->free();
			}
		}

		$resp['summary']['speedings']= $speedings ;

		$resp['serial'] = $_REQUEST['serial'] ;		
		$resp['res'] = 1 ;

	}
	echo json_encode( $resp );
?>