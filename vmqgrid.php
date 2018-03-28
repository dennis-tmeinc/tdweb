<?php
// vmqgrid.php - get video requests grid data
// Requests:
//		none
// Return:
//      JSON array of video requests
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			
		// get total records
		$sql="SELECT count(*) FROM vmq ;" ;
		$result=$conn->query($sql);
		$records = 0 ;
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$records = $row[0] ;
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
			
		$sql="SELECT  vmq_id, vmq_vehicle_name, vmq_start_time, TIMEDIFF( `vmq_end_time`, `vmq_start_time`) AS duration, vmq_comp, vmq_ins_user_name, vmq_description FROM vmq ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		if( $result=$conn->query($sql) )
		while( $row=$result->fetch_array() ) {
			$grid['rows'][] = array(
					"id" => $row[0],
					"cell" => array( 
						$row[1], $row[2], $row[3], (($row[4]==0)?"Pending":"Completed"), $row[5], $row[6] 
					) );
		}
		echo json_encode( $grid );

	}
	else {
		echo "[]";
	}
?>