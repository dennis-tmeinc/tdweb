<?php
// dashboardmssgrid.php -  get dashboard report , mss status (td health feature)
// Requests:
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2016-06-02
// Copyright 2015 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		$resp['report']=array();
		
		// get total records	
		$sql="SELECT count(*) FROM mss" ;
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
			
		$now = new DateTime();
		$nowTS = $now->getTimestamp();
				
		$sql="SELECT * FROM mss LIMIT $start, $_REQUEST[rows]";
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				$cell = array();
				// mss id
				$cell[0] = $row['mss_id'] ;
				
				// connection
				$ctime = new DateTime($row[3]);
				if( $nowTS - $ctime->getTimestamp() < 65*60 ) {	// login interval is now 1 hour (login less than 24 hours)
					$cell[1] = "OK" ; 
				}
				else {
					$cell[1] = "Error" ; 
				}
				// HDD status
				$cell[2] = ( $row['mss_hddErr'] == 0 )?"OK":"Error" ;
				// SD1 status
				$cell[3] = ( $row['mss_sd1Err'] == 0 )?"OK":"Error" ;
				// SD2 status
				$cell[4] = ( $row['mss_sd2Err'] == 0 )?"OK":"Error" ;
				// Access Point status
				if( $row['mss_apErr'] == 0 ) {
					$cell[5] ="OK";
				}
				else {
					$cell[5] = '<span style="color:#B22;font-size:14px;"><strong>Error</strong></span>' .
					sprintf( " (%04b)", $row['mss_apErr'] );
				}
				
				// location
				$cell[6]=$row['mss_lat'] ;
				$cell[7]=$row['mss_lon'] ;
				
				$grid['rows'][] = array(
					"id" => $row[0],
					"cell" => $cell 
					);
			}
			$result->free();
		}
		echo json_encode( $grid );
	}
	else {
		echo json_encode( $resp );
	}
?>