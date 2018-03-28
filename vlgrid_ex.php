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
	
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		if( $_REQUEST['page'] == 1 ) {
		// create temporary table
		$sql = "CREATE TABLE `tmp_vlid` IF NOT EXISTS ( `id` int NOT NULL AUTO_INCREMENT, `vl_id` int, KEY `id` (`id`) USING BTREE );";

		$sql = "TRUNCATE TABLE `tmp_vlid`;";
		$conn->query($sql);
		
		$conn->query($sql);
		$sql = "INSERT INTO `tmp_vlid` (`vl_id`) SELECT `vl_id` FROM `vl` $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] ;" ;
		$conn->query($sql);
		}
		
		$records = 0 ;
		$sql="SELECT count(*) FROM tmp_vlid ;" ;
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
			
		if( $grid['page'] > $grid['total'] ) {
			$grid['page']=$grid['total'] ;
		}
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;
			
//		$sql="SELECT * FROM vl $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		// late row lookups version
//		$sql="SELECT * FROM vl JOIN (SELECT vl_id FROM vl $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows]) AS t ON t.vl_id = vl.vl_id ;";
		
		$sql="SELECT * FROM vl JOIN (SELECT vl_id FROM tmp_vlid WHERE `id` > $start AND `id` <= ($start+$_REQUEST[rows])  ) AS t ON t.vl_id = vl.vl_id ;";

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
		$conn->close();
		echo json_encode( $grid );
	}
	else {
		echo '{}' ;		// empty grid?
	}
?>