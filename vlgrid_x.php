<?php
// vlgrid.php -  map events grid data
// Requests:
//      grid parameter
// Return:
//      JSON array of map events
// By Dennis Chen @ TME	 - 2013-05-17
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
	
		// get total records
		$filter = empty($_SESSION['mapfilter']['filter'])?"WHERE FALSE":" WHERE ".$_SESSION['mapfilter']['filter'] ;
		
		$records = 0 ;
		$sql="SELECT count(*) FROM vl $filter ;" ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$records = $row[0] ;
			}
			$result->free();
		}
		
		$grid=array( 
			"records" => $records,
			"total" => (string)ceil($records/$_REQUEST['rows']),
			"page" => $_REQUEST['page'] ,
			"rows" => array()  );
			
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;
			
//		$sql="SELECT * FROM vl $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		// late row lookups version
		$sql="SELECT * FROM vl JOIN (SELECT vl_id FROM vl $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows]) AS t ON t.vl_id = vl.vl_id ;";
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array(MYSQLI_NUM) ) {
				$grid['rows'][] = array(
						"id" => $row[0],
						"cell" => array( 
							$row[1], $row[9], $row[3], $row[2], $row[11], (string)round($row[7]/ 1.609334 , 1), $row[5].",".$row[6] 
						) );
			}
			$result->free();
		}
		echo json_encode( $grid );
	}
	else {
		echo '{}' ;		// empty grid?
	}
?>