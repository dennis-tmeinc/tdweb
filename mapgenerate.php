<?php
// mapgenerate.php - generate map events list (MAPVIEW/REPORTVIEW)
// Requests:
//      map event filter parameters
//      maprect:  rect from map
// Return:
//      JSON array of map events
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

	require 'session.php' ;
	header("Content-Type: application/json");
	
	$resp=array();
	$resp['res']=0;
	if( $logon ){
		// time range
		if( $_REQUEST['timeType'] == 0 ) {								// Exact Time, 5 min before to 5 min after
			if( empty($exact_time_range) ) $exact_time_range=600 ;
			$interval=new DateInterval("PT".($exact_time_range/2)."S");
			$startTime=new DateTime($_REQUEST['startTime']);
			$startTime->sub($interval);
			$endTime=new DateTime($_REQUEST['startTime']);
			$endTime->add($interval);
		}
		else if( $_REQUEST['timeType'] == 1 ) {							// full day
			$startTime=new DateTime($_REQUEST['startTime']);
			$startTime->setTime(0,0,0);
			$endTime=new DateTime($_REQUEST['startTime']);
			$endTime->setTime(23,59,59);
		}
		else {															// time range
			$startTime=new DateTime($_REQUEST['startTime']);
			$endTime=new DateTime($_REQUEST['endTime']);
			if( $startTime >= $endTime ) {
				$endTime = new DateTime($_REQUEST['startTime']);
				$endTime->add(new DateInterval("PT5M"));				// make it 5 minutes
			}
		}
		$startTime=$startTime->format("Y-m-d H:i:s");	    		// MYSQL format
		$endTime=$endTime->format("Y-m-d H:i:s");	    			// MYSQL format

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		// for group
		$vehiclelist='';
		if( $_REQUEST['vehicleType'] != 0 ) {	// group
			// to read group 
			$sql="SELECT `vehiclelist` FROM `vgroup` WHERE `name` = '$_REQUEST[vehicleGroupName]';" ;
			if( $result=$conn->query($sql) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$row=explode(",",$row[0]);
					for($i=0; $i<count($row); $i++) {
						if($i>0) $vehiclelist.=",";
						$vehiclelist.="'$row[$i]'" ;
					}
				}
				$result->free();
			}
		}
		else {
			$vehiclelist="'$_REQUEST[vehicleGroupName]'";
		}
		

		$_SESSION['mapfilter']=array();

		// convert to kmh (from mph)
		$_SESSION['mapfilter']['speedLimit'] = $speedLimit = $_REQUEST['speedLimit']*1.609344 ;		
		
		$_SESSION['mapfilter']['bSpeeding'] = 	!empty($_REQUEST['bSpeeding']) ;
		
		// correct g-force to absolute value
		$_SESSION['mapfilter']['gRacingStart'] = round ( abs($_REQUEST['gRacingStart']), 8 );
		$_SESSION['mapfilter']['gRearImpact'] = round( abs($_REQUEST['gRearImpact']), 8);
		$_SESSION['mapfilter']['gHardBrake'] = round( abs($_REQUEST['gHardBrake']), 8);
		$_SESSION['mapfilter']['gFrontImpact'] = round( abs($_REQUEST['gFrontImpact']), 8);
		$_SESSION['mapfilter']['gHardTurn'] = round( abs($_REQUEST['gHardTurn']), 8);
		$_SESSION['mapfilter']['gSideImpact'] = round( abs($_REQUEST['gSideImpact']), 8);
		$_SESSION['mapfilter']['gBumpyRide'] = round( abs($_REQUEST['gBumpyRide']), 8);

		$_SESSION['mapfilter']['bRacingStart'] = !empty($_REQUEST['bRacingStart']) ;
		$_SESSION['mapfilter']['bRearImpact'] = !empty($_REQUEST['bRearImpact']) ;
		$_SESSION['mapfilter']['bHardBrake'] = !empty($_REQUEST['bHardBrake']) ;
		$_SESSION['mapfilter']['bFrontImpact'] = !empty($_REQUEST['bFrontImpact']) ;
		$_SESSION['mapfilter']['bHardTurn'] = !empty($_REQUEST['bHardTurn']) ;
		$_SESSION['mapfilter']['bSideImpact'] = !empty($_REQUEST['bSideImpact']) ;
		$_SESSION['mapfilter']['bBumpyRide'] = !empty($_REQUEST['bBumpyRide']) ;

		// save parameter for video clips/hours statistics
		$_SESSION['mapfilter']['vehiclelist'] = $vehiclelist ;
		$_SESSION['mapfilter']['endTime'] = $endTime ;
		$_SESSION['mapfilter']['startTime'] = $startTime ;

		// zone
		$north=100 ;	// invalid value for no zone

		if( !empty($_REQUEST['zoneName']) && strcasecmp ( $_REQUEST['zoneName'] , 'No Restriction') && strcasecmp ( $_REQUEST['zoneName'] , 'User Define') ) {
			$sql = "SELECT * FROM zone WHERE `name` = '$_REQUEST[zoneName]' ;";
			if( $result = $conn->query($sql) ) {
				if( $row=$result->fetch_assoc() ) {
					$north=$row['top'];
					$south=$row['bottom'];
					$west=$row['left'];
					$east=$row['right'];
				}
				$result->free();
			}
		}
		
		// vehicle, time and area filter
		$filter_vta = "vl_vehicle_name IN ($vehiclelist) AND ( vl_datetime BETWEEN '$startTime' AND '$endTime' )" ;
		if( $north<=90 ) {		// zone defined
			if($_REQUEST['zoneType']=="0") {
				$filter_vta .=" AND (vl_lat BETWEEN $south AND $north ) AND (vl_lon BETWEEN $west AND $east)" ;
			}
			else {
				$filter_vta .=" AND NOT ((vl_lat BETWEEN $south AND $north ) AND (vl_lon BETWEEN $west AND $east))" ;
			}
		}
 		
		// Event filter
		$filter_event = '' ;
		
		// route and speeding
		if( !empty($_REQUEST['bRoute']) ) {
			$filter_event = "( vl_incident = 2 )" ;
		}
		else if( !empty($_REQUEST['bSpeeding']) ){
			$filter_event = "( vl_incident = 2 AND vl_speed > $speedLimit )" ;
		}
		
		// stop
		if( !empty($_REQUEST['bStop']) ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 1 AND vl_time_len >= $_REQUEST[stopDuration] )" ;
		}
	
		// idling
		if( !empty($_REQUEST['bIdling']) ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 4 AND vl_time_len >= $_REQUEST[idleDuration] )" ;
		}
	
		// Des Stop
		if( !empty($_REQUEST['bDesStop']) ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 17 AND vl_time_len >= $_REQUEST[desStopDuration] )" ;
		}
	
		// Parking
		if( !empty($_REQUEST['bParking']) ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 18 AND vl_time_len >= $_REQUEST[parkDuration] )" ;
		}
		
		// Marked Event
		if( !empty($_REQUEST['bEvent']) ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 23 )" ;
		}
	
		// g-force filter
		$filter_gforce = '';
		
		// all g-force value are absolute;
		$gRacingStart = $_SESSION['mapfilter']['gRacingStart'];
		$gRearImpact = $_SESSION['mapfilter']['gRearImpact'];
		$gHardBrake = $_SESSION['mapfilter']['gHardBrake'];
		$gFrontImpact = $_SESSION['mapfilter']['gFrontImpact'];
		$gHardTurn = $_SESSION['mapfilter']['gHardTurn'];
		$gSideImpact = $_SESSION['mapfilter']['gSideImpact'];
		$gBumpyRide = $_SESSION['mapfilter']['gBumpyRide'];
		
		// Racing start & Rear impact
		$g = !empty($_REQUEST['bRacingStart']) ; 
		$i = !empty($_REQUEST['bRearImpact']) ;
		if( $g && $i ) {
			$filter_gforce .= "vl_impact_x >= $gRacingStart" ;
		}
		else if( $i ){
			$filter_gforce .= "vl_impact_x >= $gRearImpact" ;
		}
		else if( $g ){
			$filter_gforce .= "(vl_impact_x >= $gRacingStart AND vl_impact_x < $gRearImpact)" ;
		}
		
		// Hard Break & Front Impact
		$g = !empty($_REQUEST['bHardBrake']) ; 
		$i = !empty($_REQUEST['bFrontImpact']) ;
		if( ($g || $i) && strlen( $filter_gforce )>0 ) {
			$filter_gforce .= " OR " ;
		}		
		if( $g && $i ) {
			$filter_gforce .= "vl_impact_x <= -$gHardBrake" ;
		}
		else if( $i ){
			$filter_gforce .= "vl_impact_x <= -$gFrontImpact" ;
		}
		else if( $g ){
			$filter_gforce .= "(vl_impact_x <= -$gHardBrake AND vl_impact_x > -$gFrontImpact)" ;
		}
		
		// Hard turn & side impact
		$g = !empty($_REQUEST['bHardTurn']) ; 
		$i = !empty($_REQUEST['bSideImpact']) ;
		if( ($g || $i) && strlen( $filter_gforce )>0 ) {
			$filter_gforce .= " OR " ;
		}		
		if( $g && $i ) {
			$filter_gforce .= "ABS(vl_impact_y) >= $gHardTurn" ;
		}
		else if( $i ){
			$filter_gforce .= "ABS(vl_impact_y) >= $gSideImpact" ;
		}
		else if( $g ){
			$filter_gforce .= "(ABS(vl_impact_y) >= $gHardTurn AND ABS(vl_impact_y) < $gSideImpact)" ;
		}
				
		// Bumpy Ride
		if( !empty($_REQUEST['bBumpyRide']) ) {
			if( strlen( $filter_gforce )>0 ) {
				$filter_gforce .= " OR " ;
			}
			$filter_gforce .= "ABS(vl_impact_z - 1.0) >= $gBumpyRide";
		}

		if( strlen( $filter_gforce )>0 ) {
			$filter_gforce = "vl_incident = 16 AND ($filter_gforce)" ;
		}	
		
		if( strlen( $filter_event )>0 && strlen( $filter_gforce )>0 ) {
			$filter = "$filter_event OR ($filter_gforce)";
		}
		else {
			$filter = $filter_event.$filter_gforce ;			// only one or none filter valid
		}
		$resp['count'] = 0 ;
		
		if( strlen($filter)>0 ) {
			$filter = "$filter_vta AND ($filter)" ;
			
			$sql="SELECT count(*) FROM `vl` WHERE $filter ;" ;
			if( $result=$conn->query($sql) ) {
				if( $row = $result->fetch_array() ) {
					$resp['count'] = $row[0] ;
				}
				$result->free();
			}			

			$sql="SELECT min(vl_lon), max(vl_lon), max(vl_lat), min(vl_lat) FROM vl WHERE $filter AND vl_lon != 0 AND vl_lat != 0 ;" ;

			if( $result=$conn->query($sql) ) {
				if( $row = $result->fetch_array() ) {
					$resp['zone'] = array(
						'west' => $row[0],
						'east' => $row[1],
						'north' => $row[2],
						'south' => $row[3]
					) ;
				}
				$result->free();
			}		
		}
		else {
			$filter = "FALSE" ;
		}
		$conn->close();

		$_SESSION['mapfilter']['evcounts'] = $resp['count'] ;
		$_SESSION['mapfilter']['filter'] = $filter ;
		// save session data
		session_write();

		$resp['res']=1;

	}
	else {
		$resp['errormsg']="Session Error!";
	}
	echo json_encode( $resp );
?>