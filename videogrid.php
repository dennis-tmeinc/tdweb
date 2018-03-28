<?php
// videogrid.php - retrieve video grid data
// Requests:
//      grid parameter
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		// get total records
		$filter = (isset($_SESSION['videofilter']) && strlen($_SESSION['videofilter'])>2)?" WHERE ".$_SESSION['videofilter']:" WHERE FALSE "  ;
		$sql="SELECT count(*) FROM videoclip $filter ;" ;
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
			
		$sql="SELECT `index`, `vehicle_name`, `time_start`, TIMESTAMPDIFF(SECOND, `time_start`, `time_end`) AS duration, path FROM videoclip $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				$grid['rows'][] = array(
						"id" => $row[0],
						"cell" => array( 
							$row[1], $row[2], $row[3], substr( strrchr($row[4],'\\'), 1 ), ''
						) );
			}
			$result->free();
		}
		$conn->close();
		echo json_encode( $grid );
	}
	else {
		echo "{}" ;
	}
?>