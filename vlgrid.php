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
		$filter = empty($_SESSION['mapfilter']['filter'])?"FALSE":$_SESSION['mapfilter']['filter'] ;

		$vlgridtableexist = false ;
		if(!empty($map_events_cache)) {		// experiment cache table mode
			$vlgrid_table = 'tmp'.md5($filter.$_REQUEST['sidx'].$_REQUEST['sord']);
			$sql = "SHOW TABLES LIKE '$vlgrid_table' ";
			if( $result = $conn->query($sql) ) {
				if( $result->num_rows > 0 ) {
					$vlgridtableexist = true ;
				}
			}
		
			if( empty($max_map_events) || $max_map_events<100000 || $max_map_events>2000000 ) {
				$max_map_events=500000 ;
			}
			
			if(!$vlgridtableexist) {
				$sql = "CREATE TABLE `$vlgrid_table` ( `vl_id` int ) ENGINE=MEMORY SELECT `vl_id` FROM `vl` WHERE $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $max_map_events; ";
				$conn->query($sql);
				$vlgridtableexist = true ;
			}
		}
		
		if( !empty($_SESSION['mapfilter']['evcounts']) ) {
			$records = $_SESSION['mapfilter']['evcounts'] ;
		}
		else {
			$records = 0 ;
			if( $vlgridtableexist ) {
				$sql="SELECT count(*) FROM $vlgrid_table ;" ;
			}
			else {
				$sql="SELECT count(*) FROM `vl` WHERE $filter ;" ;
			}
			if( $result=$conn->query($sql) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$records = $row[0] ;
				}
				$result->free();
			}
		}
		
		$grid=array( 
			"records" => $records,
			"total" => (string)ceil($records/$_REQUEST['rows']),
			"page" => $_REQUEST['page'] ,
			"rows" => array()  );
			
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;
		if( $start < 0 )
			$start = 0;

		// special icon id,
		// 10000:"Speeding",
		// 10001:"Front Impact" ,
		// 10002:"Rear Impact" ,
		// 10003:"Side Impact" ,
		// 10004:"Hard Brake" ,
		// 10005:"Racing Start" ,
		// 10006:"Hard Turn" ,
		// 10007:"Bumpy Ride" 		
		function vl_icon($row )
		{
			global $_SESSION ;
			
			$icon = $row[3] ;
			$vl_speed = $row[7] ;
			$vl_heading = $row[8] ;
			if( $icon == 2 ) {			// route, check for speeding
				if( $_SESSION['mapfilter']['bSpeeding'] && $vl_speed > $_SESSION['mapfilter']['speedLimit'] ) {
					$icon = 10000 ;		// speeding icon
				}
				$icon .= '?'. floor($vl_heading/20) * 20 ;
			}
			else if( $icon == 16 ) {	// g-force event
				$gforce_icons = array();
				if( $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gFrontImpact'] ) {
					if( $_SESSION['mapfilter']['bFrontImpact'] )
						$gforce_icons[]='10001';			// fi
				}
				else if( $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRearImpact'] ) {
					if( $_SESSION['mapfilter']['bRearImpact'] )
						$gforce_icons[]='10002';			// ri
				}
				else if( $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gHardBrake'] ) {
					if( $_SESSION['mapfilter']['bHardBrake'] )
						$gforce_icons[]='10004';			// hb
				}
				else if( $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRacingStart'] ) {
					if( $_SESSION['mapfilter']['bRacingStart'] )
						$gforce_icons[]='10005';			// rs
				}
					
				if( abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gSideImpact'] ) {
					if( $_SESSION['mapfilter']['bSideImpact'] )
						$gforce_icons[]='10003';			// si
				}
				else if( abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gHardTurn'] ) {
					if( $_SESSION['mapfilter']['bHardTurn'] )
						$gforce_icons[]='10006';			// ht
				}
				
				if( abs(1.0-$row['vl_impact_z']) >= $_SESSION['mapfilter']['gBumpyRide'] ) {
					if( $_SESSION['mapfilter']['bBumpyRide'] )
						$gforce_icons[]='10007';			// br
				}
					
				if( !empty($gforce_icons) ) {
					$icon = $gforce_icons;
				}
			}

			return $icon;
		}

		// original version
//		$sql="SELECT * FROM vl WHERE $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";

		if( $vlgridtableexist ) {
			// cached index table version
			$sql="SELECT * FROM vl JOIN (SELECT `vl_id` FROM $vlgrid_table LIMIT $start, $_REQUEST[rows] ) AS t ON t.vl_id = vl.vl_id ;";
		}
		else {
			// late row lookups version
			$sql="SELECT * FROM vl JOIN (SELECT `vl_id` FROM `vl` WHERE $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows]) AS t ON t.vl_id = vl.vl_id ;";
		}

		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				$h = floor($row[11]/3600) ;
				$m = floor($row[11]/60)%60 ;
				if( $m < 10 ) $m='0'.$m ;
				$s = $row[11]%60 ;
				if( $s < 10 ) $s='0'.$s ;
				// for country code, mph or km/h
				if( $_SESSION['country'] == "US") {
					// mph
					$speed = round($row[7] / 1.609334 , 1);
				}
				else {
					// km/h
					$speed = round($row[7], 1);
				}
				$co = round($row[5], 4).",".round($row[6], 4);
				$grid['rows'][] = array(
						"id" => $row[0],
						"cell" => array(
							$row[1], $row[9], vl_icon( $row ), $row[2], "$h:$m:$s", (string)$speed, $co
						));
			}
			$result->free();
		}
		
		if( $vlgridtableexist && $records < 5000  ) {
			// small results, just drop the table like a temporary table
			$sql = "DROP TABLE $vlgrid_table ;";
			$conn->query($sql);
		}
		
		echo json_encode( $grid );
	}
	else {
		echo '{}' ;		// empty grid?
	}
?>