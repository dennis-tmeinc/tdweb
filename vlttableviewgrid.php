<?php
// vlttableviewgrid.php 	
//		get vehicle current status for vlt table view grid view
// Request:
//      rows : number or rows to retrieve
//      page : page number
//
//      _search : true/false
//      
// Return:
//      json 
//		{
//			"records":"32680",
//			"total":327,
//			"page":"1",
//			"rows":[
//				{
//					"id":"52552",
//					"cell":[ "BUS#400-IP242", "2014-07-07 12:52:33","-23", "CH00_20140707125233_-23_L_BUS#400-IP242.264",""]
//				},
//              ...
//				{...}
//			]
//		}
//
// By Dennis Chen @ TME	 - 2021-03-16
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//
// 
//
    require 'session.php' ;
	
	header("Content-Type: application/json");
	
	if( $logon ) {

		// lock table
		$conn->query("LOCK TABLE vehicle_current_status READ ;");

        // create filter
		if( empty($vehicle_status_valid_time) ) {
			$vehicle_status_valid_time = 2 ;
		}
        $filter = " status_time > NOW() - INTERVAL ${vehicle_status_valid_time} MINUTE " ;

		if( $_REQUEST['_search'] == 'true' && !empty( $_REQUEST['searchField']) && isset( $_REQUEST['searchString']) && !empty( $_REQUEST['searchOper'] )){
			
			if ( $_REQUEST['searchField'] == "trip_distance" || $_REQUEST['searchField'] == "trip_distance" ) {
				$_REQUEST['searchString'] = $_REQUEST['searchString'] / 0.62137119 ;
			}

			if($_REQUEST['searchOper'] == 'eq' ) {
				$filter .= "AND `$_REQUEST[searchField]` = '$_REQUEST[searchString]' ";
			}
			else if($_REQUEST['searchOper'] == 'ne' ) {
				$filter .= "AND `$_REQUEST[searchField]` != '$_REQUEST[searchString]' ";
			}
			else if($_REQUEST['searchOper'] == 'cn' ) {
				$filter .= "AND `$_REQUEST[searchField]` LIKE '%$_REQUEST[searchString]%' ";
			}
			else if($_REQUEST['searchOper'] == 'nc' ) {
				$filter .= "AND `$_REQUEST[searchField]` !Like '%$_REQUEST[searchString]%' ";
			}
			else if($_REQUEST['searchOper'] == 'lt' ) {
				$filter .= "AND `$_REQUEST[searchField]` < '$_REQUEST[searchString]' ";
			}
			else if($_REQUEST['searchOper'] == 'le' ) {
				$filter .= "AND `$_REQUEST[searchField]` <= '$_REQUEST[searchString]' ";
			}
			else if($_REQUEST['searchOper'] == 'gt' ) {
				$filter .= "AND `$_REQUEST[searchField]` > '$_REQUEST[searchString]' ";
			}
			else if($_REQUEST['searchOper'] == 'ge' ) {
				$filter .= "AND `$_REQUEST[searchField]` >= '$_REQUEST[searchString]' ";
			}
		}

        // assume failed
		$resp['records'] = 0 ;
		$resp['total'] = 0 ;
		$resp['page'] = 1 ;
		$resp['rows'] = array() ;
		// total records query
		$sql="SELECT count(*) FROM vehicle_current_status WHERE $filter ;" ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$resp['records'] = $row[0] ;
				$resp['total'] = ceil($row[0]/$_REQUEST['rows']) ;
				$resp['page'] = $_REQUEST['page'] ;
			}
			$result->free();
		}

		$sql="SELECT 
			vehicle_id,
			status_time,
			if( obd_data, 'Yes', 'No') as _obd_data,
			ivu_power_src,
			engine_status,	
			if( ignition, 'On', 'Off') as _ignition,
			vehicle_status,	
			trip_distance * 0.62137119 as _trip_distance ,	
			total_distance * 0.62137119 as _total_distance ,	
			fuel_level,	
			engine_oil_level,	
			coolant_temp,	
			oil_temp,	
			transmission,	
			if( parking_break, 'On', 'Off' ) as _parking_break,	
			brake_power,	
			gas_pedal,	
			engine_load	,
			if( idling_status, 'Yes', 'No') as _idling_status,
			idling_time,	
			if( door_status, 'Open', 'Close' ) as _door_status,	
			if( seat_belt, 'On', 'Off' ) as	_seat_belt,
			battery/1000 as _battery
		 FROM vehicle_current_status WHERE $filter " ;
		if( !empty( $_REQUEST['sidx'] ) ) {
			$sql .= "ORDER BY $_REQUEST[sidx]  $_REQUEST[sord] " ;
		}

		$start = $_REQUEST['rows'] * ($resp['page']-1) ;
		$sql .= " LIMIT $start, $_REQUEST[rows] " ;

		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$cell = array() ;
				$cell['id'] = $row['vehicle_id'] ;
				// unset fields that is not needed by grid view
				$cell['cell'] = $row ;
				$resp['rows'][] = $cell ; 
			}
			$result->free();
		}
		$sql="SELECT MAX(status_time) FROM vehicle_current_status;" ;
		if(  $result=$conn->query($sql)) {
			if( $row=$result->fetch_array(MYSQLI_NUM) ) {
				session_save( 'vehicle_status_time', $row[0] );
			}
			$result->free();
		}

		$conn->query("UNLOCK TABLES");
	}
	echo json_encode($resp);
?>