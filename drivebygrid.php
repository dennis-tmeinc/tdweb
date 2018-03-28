<?php
// drivebygrid.php 	
//		get drive by event list for grid view
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
		
		$referer = basename($_SERVER['HTTP_REFERER']);
		if( strpos( $referer, "processed" ) ) {
			$filter = " event_status = 'processed' " ;
		}
		else if( strpos( $referer, "deletedreports" ) ) {
			$filter = " report_status = 'deleted' " ;
		}
		else if( strpos( $referer, "deleted" ) ) {
			$filter = " event_status = 'deleted' " ;
		}
		else if( strpos( $referer, "reports" ) ) {
			$filter = " report_status = 'report' " ;
		}
		else {
			$filter = " event_status = 'new' " ;
		}
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		if( $_REQUEST['_search'] == 'true' ) {		// inline search
			// to add search filters
			$fields = array( 'Client_Id','Bus_Id','Date_Time','imgquality','Plateofviolator','notes','email_status', 'email_status','event_processedby','event_processedtime','sentto','State','City' );
			foreach( $fields as $f ) {
				if( !empty( $_REQUEST[$f] ) ) {
					$s = $_REQUEST[$f].'%' ;
					$filter .= " AND `$f` LIKE '$s' " ;
				}
			}
		}

		// assume failed
		$resp['records'] = 0 ;
		$resp['total'] = 0 ;
		$resp['page'] = 1 ;
		$resp['rows'] = array() ;
		// total records query
		$sql="SELECT count(*) FROM Drive_By_Event WHERE ".$filter ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$resp['records'] = $row[0] ;
				$resp['total'] = ceil($row[0]/$_REQUEST['rows']) ;
				$resp['page'] = $_REQUEST['page'] ;
			}
			$result->free();
		}
		
		$sql = "SELECT * FROM Drive_By_Event WHERE ".$filter ;
		if( !empty( $_REQUEST['sidx'] ) ) {
			$sql .= "ORDER BY $_REQUEST[sidx]  $_REQUEST[sord] " ;
		}

		$start = $_REQUEST['rows'] * ($resp['page']-1) ;
		$sql .= " LIMIT $start, $_REQUEST[rows] " ;
		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$cell = array() ;
				$cell['id'] = $row['idx'] ;
				// unset fields that is not needed by grid view
				unset( $row['Lat'] );
				unset( $row['Lon'] );
				unset( $row['Video_Files'] );
				$cell['cell'] = $row ;
				$resp['rows'][] = $cell ; 
			}
			$result->free();
		}			
	}
	echo json_encode($resp);
?>