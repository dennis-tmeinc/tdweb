<?php
// dashboardsoloalertsgrid.php - get dashboard report on solo alerts list (grid data)
// Requests:
//      rows, page, sidx, sord, (optional: alertcode)
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2016-06-02
// Copyright 2015 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$reqdate = new DateTime() ;
		if( strstr($_SESSION['dashboardpage'], 'dashboardmorning') ) {		// dashboard morning?
			$reqdate->sub(new DateInterval('P1D'));
		}

		// dashboard options
		$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;
		
		// default value
		if( empty( $dashboard_option ) ) $dashboard_option =  array(
			'tmStartOfDay' => '3:00'
		);
		
		if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
			$dashboard_option['tmStartOfDay'] = '0:00' ;
		}
				
		// time ranges
		@$date_begin = new DateTime( $dashboard_option['tmStartOfDay'] );
		if( empty($date_begin) ) {
			$date_begin = new DateTime( "03:00:00" );
		}

		$date_begin = new DateTime( $reqdate->format('Y-m-d ').$date_begin->format('H:i:s') );
		$date_begin = $date_begin->format('Y-m-d H:i:s');
		$date_end = new DateTime( $date_begin );
		$date_end->add(new DateInterval('P1D'));
		$date_end = $date_end->format('Y-m-d H:i:s');

		$resp['report']=array();
		
		$escape_req=array();
		foreach( $_REQUEST as $key => $value )
		{
			$escape_req[$key]=$conn->escape_string($value);
		}
		
		if( empty( $escape_req['alertcode'] ) ) {
			$escape_req['alertcode'] = "-1" ;
		}
		
		$filter = " WHERE alert_code IN ($escape_req[alertcode]) AND date_time BETWEEN '$date_begin' AND '$date_end' ";
		
		// get total records	
		$sql="SELECT count(*) FROM td_alert $filter ;" ;
		$records = 0 ;
		if($result=$conn->query($sql)) {
			if(	$row = $result->fetch_array( MYSQLI_NUM ) ) {
				$records = $row[0] ;
			}
			$result->free();
		}

		$grid=array( 
			"records" => $records,
			"total" => ceil($records/$_REQUEST['rows']),
			"page" => $_REQUEST['page'] ,
			"rows" => array()  );
		
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;
			
		$alert_code = array(
		"unknown",
		"video uploaded",
		"temperature",
		"login failed",
		"video lost",
		"storage failed",
		"rtc error",
		"partial storage failure",
		"system reset",
		"ignition on",
		"ignition off",
		"panic"
		);

		$sql="SELECT `index`, `dvr_name`, `description`, `alert_code`, `date_time` FROM td_alert $filter ORDER BY $escape_req[sidx] $escape_req[sord] LIMIT $start, $escape_req[rows] ;";
		
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				if( $row[3]>0 && $row[3]<12 ) {
					$grid['rows'][] = array(
						"id" => $row[0],
						"cell" => array( 
							$row[1], $row[2], $alert_code[$row[3]], $row[4]
						));
				}
			}
			$result->free();
		}
		echo json_encode( $grid );
	}
	else {
		echo json_encode( $resp );
	}
		
?>