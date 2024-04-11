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

		// escaped sql values
		$esc_req=array();		
		foreach( $_REQUEST as $key => $value ){
			$esc_req[$key]=$conn->escape_string($value);
		}

		// for group
		$vehiclelist='';
		if( empty( $_REQUEST['vehicleGroupName'] ) || $_REQUEST['vehicleGroupName'] == "ALL" ) {
			$vehiclelist='';
		}
		else if( $_REQUEST['vehicleType'] ) {	// group
			// to read group 
			$sql="SELECT `vehiclelist` FROM `vgroup` WHERE `name` = '$esc_req[vehicleGroupName]';" ;
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
		

		$mapfilter=array();

		// base on country code now, 2021-11-22
		if( $_SESSION['country'] == "US") {
			// convert to kmh (from mph)
			$mapfilter['speedLimit'] = $speedLimit = $_REQUEST['speedLimit']*1.609344 ;		
		}
		else{
			$mapfilter['speedLimit'] = $speedLimit = $_REQUEST['speedLimit'] ;		
		}
		
		$mapfilter['bSpeeding'] = 	!empty($_REQUEST['bSpeeding']) ;
		
		// correct g-force to absolute value
		$mapfilter['gRacingStart'] = round ( abs($_REQUEST['gRacingStart']), 8 );
		$mapfilter['gRearImpact'] = round( abs($_REQUEST['gRearImpact']), 8);
		$mapfilter['gHardBrake'] = round( abs($_REQUEST['gHardBrake']), 8);
		$mapfilter['gFrontImpact'] = round( abs($_REQUEST['gFrontImpact']), 8);
		$mapfilter['gHardTurn'] = round( abs($_REQUEST['gHardTurn']), 8);
		$mapfilter['gSideImpact'] = round( abs($_REQUEST['gSideImpact']), 8);
		$mapfilter['gBumpyRide'] = round( abs($_REQUEST['gBumpyRide']), 8);

		$mapfilter['bRacingStart'] = !empty($_REQUEST['bRacingStart']) ;
		$mapfilter['bRearImpact'] = !empty($_REQUEST['bRearImpact']) ;
		$mapfilter['bHardBrake'] = !empty($_REQUEST['bHardBrake']) ;
		$mapfilter['bFrontImpact'] = !empty($_REQUEST['bFrontImpact']) ;
		$mapfilter['bHardTurn'] = !empty($_REQUEST['bHardTurn']) ;
		$mapfilter['bSideImpact'] = !empty($_REQUEST['bSideImpact']) ;
		$mapfilter['bBumpyRide'] = !empty($_REQUEST['bBumpyRide']) ;

		// OBD search (2021-04-27)
		$mapfilter['bEnginOn'] = !empty($_REQUEST['bEnginOn']) ;
		$mapfilter['bEnginOff'] = !empty($_REQUEST['bEnginOff']) ;
		$mapfilter['bFuelLevel'] = !empty($_REQUEST['bFuelLevel']) ;
		$mapfilter['bCoolantTemperature'] = !empty($_REQUEST['bCoolantTemperature']) ;
		$mapfilter['bEngineOilLevel'] = !empty($_REQUEST['bEngineOilLevel']) ;
		$mapfilter['bBatteryVoltage'] = !empty($_REQUEST['bBatteryVoltage']) ;
		
		$mapfilter['gFuelLevel'] = $_REQUEST['gFuelLevel'] ;
		$mapfilter['gCoolantTemperature'] = $_REQUEST['gCoolantTemperature'] ;
		$mapfilter['gEngineOilLevel'] = $_REQUEST['gEngineOilLevel'] ;
		$mapfilter['gBatteryVoltage'] = $_REQUEST['gBatteryVoltage'] ;

		// Hard Brake & Quick Acceleration , ref email 2021-10-29 from Tongrui@tme-inc.com
		$mapfilter['bObdHardBrake'] = $_REQUEST['bObdHardBrake'] ;
		$mapfilter['gObdHardBrake'] = $_REQUEST['gObdHardBrake'] ;
		// convert it to what? the value of the above fields is defined in unit of 1/256 km/hr/s 
		$mapfilter['gObdHardBrake'] = $mapfilter['gObdHardBrake'] * 9.80665 * 3600 * 256 / 1000.0 ;
		$mapfilter['gObdHardBrake'] = (int)$mapfilter['gObdHardBrake'] ;

		$mapfilter['bObdQuickAcceleration'] = $_REQUEST['bObdQuickAcceleration'] ;
		$mapfilter['gObdQuickAcceleration'] = $_REQUEST['gObdQuickAcceleration'] ;
		$mapfilter['gObdQuickAcceleration'] = $mapfilter['gObdQuickAcceleration'] * 9.80665 * 3600 * 256 / 1000.0 ;
		$mapfilter['gObdQuickAcceleration'] = (int)$mapfilter['gObdQuickAcceleration'] ;

		// save parameter for video clips/hours statistics
		$mapfilter['vehiclelist'] = $vehiclelist ;
		$mapfilter['endTime'] = $endTime ;
		$mapfilter['startTime'] = $startTime ;

		// zone
		$north=100 ;	// invalid value for no zone

		if( !empty($_REQUEST['zoneName']) && 
			strcasecmp ( $_REQUEST['zoneName'] , 'No Restriction') && 
			strcasecmp ( $_REQUEST['zoneName'] , 'Default Area') && 
			strcasecmp ( $_REQUEST['zoneName'] , 'Current Map') && 
			strcasecmp ( $_REQUEST['zoneName'] , 'User Define') ) 
		{
			$sql = "SELECT * FROM zone WHERE `name` = '$esc_req[zoneName]' AND (`type` = 1 OR `user` = '$_SESSION[user]') ";
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
		
		if( !empty($_REQUEST['zoneName']) && 
			strcasecmp ( $_REQUEST['zoneName'] , 'Default Area') == 0 ) 
		{
			if( empty( $map_area ) ) {
				$query = "United States" ;
			}
			else {
				$query = $map_area ;
			}
			
			$url =  "https://dev.virtualearth.net/REST/v1/Locations?q=".rawurlencode($query)."&o=json&maxResults=1&key=".$map_credentials ;
			@$maparea = file_get_contents( $url );
			if( !empty($maparea) ) {
				$maparea = json_decode($maparea, true) ;
			}
			$resp['map'] = array();
			if( !empty( $maparea['resourceSets'][0]['resources'][0]['bbox'] )) {
				$south=$maparea['resourceSets'][0]['resources'][0]['bbox'][0] ;
				$west=$maparea['resourceSets'][0]['resources'][0]['bbox'][1] ;
				$north=$maparea['resourceSets'][0]['resources'][0]['bbox'][2];
				$east=$maparea['resourceSets'][0]['resources'][0]['bbox'][3];				
			}
		}
		
		// vehicle, time and area filter
		$filter_vta = "";
		if( !empty($mapfilter['vehiclelist'] ) ) {
			$filter_vta = "vl_vehicle_name IN ($mapfilter[vehiclelist]) AND ";
		}
		$filter_vta .= "( vl_datetime BETWEEN '$startTime' AND '$endTime' ) " ;
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
	
		// Drive By Event
		if( !empty($_REQUEST['bDriveBy']) ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 40 )" ;
		}
	
		// TVS events
		if( !empty($_REQUEST['bDoorOpen']) && $_REQUEST['bDoorOpen']!="0" ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 10 )" ;
		}
		
		if( !empty($_REQUEST['bDoorClose']) && $_REQUEST['bDoorClose']!="0" ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 11 )" ;
		}
		
		if( !empty($_REQUEST['bIgnitionOn']) && $_REQUEST['bIgnitionOn']!="0" ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 12 )" ;
		}
		
		if( !empty($_REQUEST['bIgnitionOff']) && $_REQUEST['bIgnitionOff']!="0" ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 13 )" ;
		}
		
		if( !empty($_REQUEST['bMeterOn']) && $_REQUEST['bMeterOn']!="0" ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 41 )" ;
		}
		
		if( !empty($_REQUEST['bMeterOff']) && $_REQUEST['bMeterOff']!="0" ) {
			if( strlen( $filter_event )>0 ) {
				$filter_event .= " OR " ;
			}
			$filter_event .= "( vl_incident = 42 )" ;
		}
		
		// g-force filter
		$filter_gforce = '';
		
		// all g-force value are absolute;
		$gRacingStart = $mapfilter['gRacingStart'];
		$gRearImpact = $mapfilter['gRearImpact'];
		$gHardBrake = $mapfilter['gHardBrake'];
		$gFrontImpact = $mapfilter['gFrontImpact'];
		$gHardTurn = $mapfilter['gHardTurn'];
		$gSideImpact = $mapfilter['gSideImpact'];
		$gBumpyRide = $mapfilter['gBumpyRide'];
		
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

		// OBD filters (2021-04-29)
		$obd_filters = "";

		// quotes ( definition from Tongrui)
		//
		// 1)  in vl table add three event code in vl_incident:  VL_ENGINE_ON (101)
		//		VL_ENGINE_OFF(102) VL_OBD_VALUE(103)

		// add on 2021-10-29, Hard Brake & Quick Acceleration 
		// VL_HARD_BRAKE = 104
		// VL_QUICK_ACCEL = 105


		// 2)  in vl table add 4 fields: 
		//		vl_fuel:	percentage(0~100)%
		//		vl_engine_coolant: coolant temperature (c)
		//		vl_engine_oil: 	percentage(0~100)%
		//		vl_battery: mV 
	
		//	for all above fields if value is -10000 means the value is invalid.

		$VL_ENGINE_ON = 101 ;
		$VL_ENGINE_OFF = 102 ;
		$VL_OBD_VALUE = 103 ;

		// add on 2021-10-29, Hard Brake & Quick Acceleration 
		$VL_HARD_BRAKE = 104;
		$VL_QUICK_ACCEL = 105;

		// Engine on
		if( $mapfilter['bEnginOn'] ) {
			$obd_filters .= " OR vl_incident = $VL_ENGINE_ON" ;
		}

		// Engine off
		if( $mapfilter['bEnginOff'] ) {
			$obd_filters .= " OR vl_incident = $VL_ENGINE_OFF" ;
		}

		// Fuel Level
		if( $mapfilter['bFuelLevel'] ) {
			$obd_filters .= " OR ( vl_incident = $VL_OBD_VALUE AND vl_fuel >= 0 AND vl_fuel < $mapfilter[gFuelLevel])" ;
		}

		// Coolant Temperature
		if( $mapfilter['bCoolantTemperature'] ) {
			// f -> c
			// c=(f-32.0)/1.8
			$tc = ($mapfilter['gCoolantTemperature'] - 32) / 1.8 ;
			$obd_filters .= " OR ( vl_incident = $VL_OBD_VALUE AND vl_engine_coolant > $tc)" ;
		}

		// Engine Oil Level
		if( $mapfilter['bEngineOilLevel'] ) {
			$obd_filters .= " OR ( vl_incident = $VL_OBD_VALUE AND vl_engine_oil >= 0 AND vl_engine_oil < $mapfilter[gEngineOilLevel])" ;
		}

		// Battery Voltage
		if( $mapfilter['bBatteryVoltage'] ) {
			$gBatteryVoltage = round( $mapfilter['gBatteryVoltage'] * 1000 );
			$obd_filters .= " OR ( vl_incident = $VL_OBD_VALUE AND vl_battery >= 0 AND vl_battery < $gBatteryVoltage)" ;
		}

		// add on 2021-10-29, Hard Brake & Quick Acceleration 
		if( $mapfilter['bObdHardBrake'] ) {
			// 2021-11-3, Quang request to have search value
			$obd_filters .= " OR ( vl_incident = $VL_HARD_BRAKE AND  vl_hard_brake >= $mapfilter[gObdHardBrake] )" ;
		}
		if( $mapfilter['bObdQuickAcceleration'] ) {
			// 2021-11-3, Quang request to have search value
			$obd_filters .= " OR ( vl_incident = $VL_QUICK_ACCEL AND  vl_quick_acceleration >= $mapfilter[gObdQuickAcceleration] )" ;
		}

		if( empty($filter) ) {
			$filter = "FALSE" ;
		}
		$filter .= $obd_filters ;

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

			$resp['sql'] = $sql;

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

		$mapfilter['evcounts'] = $resp['count'] ;
		$mapfilter['filter'] = $filter ;
		// save mapfilter to session data
		session_save( 'mapfilter', $mapfilter );

		$resp['res']=1;

	}

	echo json_encode( $resp );
?>