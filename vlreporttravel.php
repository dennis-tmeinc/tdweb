<?php
// vlreporttravel.php -  report travel time, travel distance
// Requests:
//     
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2013-7-4
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		$mapfilter = $_SESSION['mapfilter']['filter'] ;
		$vehiclelist = $_SESSION['mapfilter']['vehiclelist'] ;
		$endTime = $_SESSION['mapfilter']['endTime'] ;
		$startTime = $_SESSION['mapfilter']['startTime'] ;				

		$resp['summary']=array();

		// coor2dist - calculate distance between 2 points by coordinates
		// distance per degree (lat/lon)  = 40075.02/360 = 111.3195 km = 69.1706 miles
		// return distance in degrees
		function coor2dist( $lat1, $lon1, $lat2, $lon2 )
		{
			$dx = ($lon2-$lon1)*cos(deg2rad($lat1));
			$dy = ($lat2-$lat1);
			return ( sqrt( $dx*$dx+$dy*$dy  ) ); 
		}

		$traveltime = 0 ;
		$traveldistance = 0 ;
		$min_traveltime = empty($min_traveltime)?30:$min_traveltime ;
		
		$sql = "SELECT vl_vehicle_name, vl_datetime, vl_lat, vl_lon FROM `vl` WHERE vl_vehicle_name IN ($vehiclelist) AND vl_datetime BETWEEN '$startTime' AND '$endTime' ORDER BY vl_vehicle_name, vl_datetime;";
		
		$result=$conn->query($sql,MYSQLI_USE_RESULT);	// set MYSQLI_USE_RESULT for huge data
		if( $result ){
			$pvehicle = "";
			$ptime=0;
			$plat = 0;
			$plon = 0;
			while( $row = $result->fetch_array(MYSQLI_NUM) ) {
				$ntime = strtotime( $row[1] );
				// calculate travel time and travel distance?
				if( $pvehicle == $row[0] ) {	// same vehicle?
					$dtime = $ntime - $ptime ;
					if( $dtime <= $min_traveltime ) {		// if wait too long , consider as not travelling 
						$traveltime+=$dtime ;
						$dist = coor2dist( $plat, $plon, $row[2], $row[3] ) ;
						if( $dist < 1/111.3195 ) {
    						$traveldistance += $dist ;
						}
					} 
				}				
				else {
					$pvehicle = $row[0] ;
				}
				$ptime = $ntime ;
				$plat = $row[2];
				$plon = $row[3];

			}
			// one degree = (40075.02/1.6093472/360) miles
			$traveldistance *= 69.1706 ;		// convert degrees to miles
			$result->free();
		}

		$h = floor($traveltime/3600) ;
		$m = floor($traveltime/60)%60 ;
		if( $m < 10 ) $m='0'.$m ;
		$s = $traveltime%60 ;
		if( $s < 10 ) $s='0'.$s ;
		
		$resp['summary']['traveltime']= "$h:$m:$s";
		$resp['summary']['traveldistance']= round ( $traveldistance, 1 ) ;
		
		$resp['serial'] = $_REQUEST['serial'] ;
		$resp['res'] = 1 ;
		
		$conn->close();
	}
	echo json_encode( $resp );
?>