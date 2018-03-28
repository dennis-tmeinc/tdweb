<?php
// emggrid.php 	
//		get emergency event list for grid view
// Request:
//      rows : number or rows to retrieve
//      page : page number
//
//      _search : true/false
//      
//      vehicle_name : 
//      time_start :
//      channel :
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
// By Dennis Chen @ TME	 - 2014-01-10
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//
// 
//
    require 'session.php' ;
	
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		$filter = '' ;
		if( $_REQUEST['_search'] == 'true' ) {		// inline search
			// to add search filters
			$fields = array( 'Client_Id','Bus_Id','Date_Time','Sensor_Status','Event_Code' );
			foreach( $fields as $f ) {
				if( !empty( $_REQUEST[$f] ) ) {
					if( empty($filter) ) {
						$filter = " WHERE" ;
					}
					else {
						$filter .= " AND" ;
					}
					if( $f == 'Event_Code' ) {
						if( $_REQUEST[$f][0] == 'E' || 
							$_REQUEST[$f][0] == 'e' ||
							$_REQUEST[$f][0] == 'p' ||
							$_REQUEST[$f][0] == 'P' ) {		// Event Marker, or Panic
							$filter .= " `$f` = 1" ;
						}
						else if( $_REQUEST[$f][0] == 'C' || $_REQUEST[$f][0] == 'c' ) {	// Crash
							$filter .= " `$f` = 2" ;
						}
						else {
							$filter .= " `$f` = 0" ;
						}
					}
					else {
						$s = $_REQUEST[$f].'%' ;
						$filter .= " `$f` LIKE '$s'" ;
					}
				}
			}
		}

		// assume failed
		$resp['records'] = 0 ;
		$resp['total'] = 0 ;
		$resp['page'] = 1 ;
		$resp['rows'] = array() ;
		// total records query
		$sql="SELECT count(*) FROM emg_event ".$filter ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$resp['records'] = $row[0] ;
				$resp['total'] = ceil($row[0]/$_REQUEST['rows']) ;
				$resp['page'] = $_REQUEST['page'] ;
			}
			$result->free();
		}
		
		$sql = "SELECT * FROM emg_event ".$filter ;
		if( !empty( $_REQUEST['sidx'] ) ) {
			$sql .= " ORDER BY $_REQUEST[sidx]  $_REQUEST[sord]" ;
		}

		$start = $_REQUEST['rows'] * ($resp['page']-1) ;
		$sql .= " LIMIT $start, $_REQUEST[rows]" ;
		if($result=$conn->query($sql)) {
			$event_code = array( "Unknown", "Panic", "Crash" ) ;
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$cell = array() ;
				$cell['id'] = $row['idx'] ;
				// unset fields that is not needed by grid view
				unset( $row['Lat'] );
				unset( $row['Lon'] );
				unset( $row['Video_Files'] );
				if( !empty( $row['Event_Code'] ) ) {
					$row['Event_Code'] = $event_code[ $row['Event_Code'] ] ;
				}
				else {
					$row['Event_Code'] = "Unknown" ;
				}
				$cell['cell'] = $row ;
				$resp['rows'][] = $cell ; 
			}
			$result->free();
		}			
	}
	echo json_encode($resp);
?>