<?php
// vcqgrid.php - get video requests grid data (cell table)
// Requests:
//		rows:		rows per page
//		page:		page number, start from 1
//		sidx:		sort field
//		sord:		sort order
// Return:
//      JSON array of video requests
// By Dennis Chen @ TME	 - 2016-12-14
// Copyright 2016 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
			
		// get total records
		$sql="SELECT count(*) FROM vcq ;" ;
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
		if( $grid['page'] <= 0 ) {
			$grid['page'] = 1 ;
		}
		if( $grid['page'] > $grid['total'] ) {
			$grid['page']=$grid['total'] ;
		}		
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;

		$sql="SELECT  vcq_id, vcq_vehicle_name, vcq_start_time, TIMEDIFF( `vcq_end_time`, `vcq_start_time`) AS duration, vcq_comp, vcq_ins_user_name, vcq_description, vcq_end_time, vcq_ins_time FROM vcq ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		if( $result=$conn->query($sql) )
		while( $row=$result->fetch_array() ) {
			$rstatus = "Pending" ;
			if( $row[4] != 0 ) {
				$rstatus = "Requested" ;

				// check if video available
				$sql = "SELECT count(*) FROM `videoclip` WHERE `vehicle_name`='$row[1]' AND `time_start` <= '$row[7]' AND `time_end` >= '$row[2]' " ;

				$channels = explode(',', $row['vmq_channel']) ;
				if( count( $channels ) < 1 || $channels[0] == 'A' ||  $channels[0] == 'ALL' ||  $channels[0] == 'a' || $channels[0] == 'all' ) {
				}
				else {
					$sql .= "AND ( " ;
					for( $i=0; $i<count( $channels ) ; $i++ ) {
						if( $i > 0 ) {
							$sql .= "OR" ;
						}
						$sql .= " `channel` = $channels[$i] " ;
					}
					$sql .= " ) " ;
				}
				
				if( $xresult=$conn->query($sql) ) {
					$xrow = $xresult->fetch_array( MYSQLI_NUM ) ;
					$xresult->free();
					if( $xrow && $xrow[0] > 0 ) {
						$rstatus = "Completed" ;
					}
					else {
						$sql = "SELECT count(*) FROM dvr_event WHERE `de_vehicle_name`='$row[1]' AND `de_datetime` > '$row[8]' AND `de_event` = 1 ;" ;
						if( $xresult=$conn->query($sql) ) {
							$xrow = $xresult->fetch_array( MYSQLI_NUM ) ;
							$xresult->free();		
							if( $xrow && $xrow[0] > 0 ) {
								$rstatus = "Not Available" ;
							}
						}
					}
				}
			}
			$grid['rows'][] = array(
					"id" => $row[0],
					"cell" => array( 
						$row[1], $row[2], $row[3], $rstatus, $row[5], $row[6] 
					)
					);
		}
		echo json_encode( $grid );

	}
	else {
		echo "[]";
	}
?>