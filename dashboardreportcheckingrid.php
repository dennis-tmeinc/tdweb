<?php
// dashboardreportcheckingrid.php -  get dashboard report check in list (grid data)
// Requests:
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2015-02-25
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
		
		$filter = "de_event = 1 AND de_datetime BETWEEN '$date_begin' AND '$date_end' ";
	
		// get total records	
		$sql="SELECT count(*) FROM dvr_event WHERE $filter ;" ;
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
		if( $grid['page'] > $grid['total'] ) {
			$grid['page']=$grid['total'] ;
		}
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;
			
		$sql="SELECT id, de_vehicle_name, de_datetime FROM dvr_event WHERE $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				$grid['rows'][] = array(
						"id" => $row[0],
						"cell" => array( 
							$row[1], $row[2]
						));
			}
			$result->free();
		}
		echo json_encode( $grid );
	}
	else {
		echo json_encode( $resp );
	}
?>