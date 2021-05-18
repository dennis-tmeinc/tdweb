<?php
// vlmap.php -  get map events on map
// Requests:
//      maps boundary (east, west, north, south)
//             
// Return:
//      JSON array, {id,icon,direction,lat,lon}
// By Dennis Chen @ TME	 - 2013-05-24
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		// get total records
		if( empty($_SESSION['mapfilter']['filter']) ) {
			$filter = 'FALSE';
		}
		else {
			$filter = $_SESSION['mapfilter']['filter'] ;
		}

		if( $_REQUEST['east'] > $_REQUEST['west'] ) {
			$maparea = " (vl_lat BETWEEN $_REQUEST[south] AND $_REQUEST[north] ) AND (vl_lon BETWEEN $_REQUEST[west] AND $_REQUEST[east]) " ;
		}
		else {
			$maparea = "TRUE" ;
		}

		$width = $_REQUEST['width'] ;
		$height = $_REQUEST['height'] ;
		$dist_x = abs( $_REQUEST['east'] - $_REQUEST['west'] );
		$dist_y = abs( $_REQUEST['north'] - $_REQUEST['south'] );
		$west = floor($_REQUEST['west']/$dist_x)*$dist_x ;
		$south = floor($_REQUEST['south']/$dist_y)*$dist_y ;
					
		$resp['mapevent']=array();
			
		if(empty($map_icons) || $map_icons<50 || $map_icons>800 ){
			$map_icons=100;
		}
	
		$resp['record'] = 0 ;
		$sql="SELECT count(*) FROM vl WHERE $filter AND $maparea  ;" ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$resp['record'] = $row[0] ;
			}
			$result->free();
		}
		if( empty($mapmode) ) $mapmode='grid' ;		// default grid mode
		
		// special icon id,
		// 10000:"Speeding",
		// 10001:"Front Impact" ,
		// 10002:"Rear Impact" ,
		// 10003:"Side Impact" ,
		// 10004:"Hard Brake" ,
		// 10005:"Racing Start" ,
		// 10006:"Hard Turn" ,
		// 10007:"Bumpy Ride" 
		function vl_icon($row)
		{
			global $_SESSION ;
			
			$icon = (int)$row['vl_incident'] ;
			if( $icon == 2 ) {			// route, check for speeding
				if( $_SESSION['mapfilter']['bSpeeding'] && $row['vl_speed'] > $_SESSION['mapfilter']['speedLimit'] ) {
					$icon = 10000 ;		// speeding icon
				}
			}
			else if( $icon == 16 ) {	// g-force event
				if( $_SESSION['mapfilter']['bFrontImpact'] && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gFrontImpact'] ) 
					$icon=10001;			// fi
				else if( $_SESSION['mapfilter']['bRearImpact'] && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRearImpact'] ) 
					$icon=10002;			// ri
				else if( $_SESSION['mapfilter']['bSideImpact'] && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gSideImpact'] )
					$icon=10003;			// si
				else if( $_SESSION['mapfilter']['bHardBrake'] && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gHardBrake'] ) 
					$icon=10004;			// hb
				else if( $_SESSION['mapfilter']['bRacingStart']  && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRacingStart'] ) 
					$icon=10005;			// rs
				else if( $_SESSION['mapfilter']['bHardTurn']  && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gHardTurn'] )
					$icon=10006;			// ht
				else if( $_SESSION['mapfilter']['bBumpyRide'] && abs(1.0-$row['vl_impact_z']) >= $_SESSION['mapfilter']['gBumpyRide'] )
					$icon=10007;			// br
			}

			return $icon;
		}

		if( ( $dist_x/$width < 5.0E-5 && $resp['record'] < 300 ) || $resp['record'] < $map_icons ) {
			// full records
			$sql="SELECT * FROM vl WHERE $filter AND $maparea ;";
		}
		else if ( $mapmode == 'limit' ) {		// limiting mode
			$divisor = (int)($resp['record'] / $map_icons) ;
			if( $divisor < 2 ) {
				$sql="SELECT * FROM vl WHERE $filter AND $maparea ;";
			}
			else {
				$sql="SELECT * FROM vl WHERE $filter AND $maparea GROUP BY ROUND(vl_id/$divisor) ;";
			}
		}		
		else if ($mapmode == 'grid' ) {			// gird mode
			// route icons
			$grid_x = $dist_x*20/$width ;
			$grid_y = $dist_y*20/$height ;			
			$sql="SELECT * FROM vl WHERE $filter AND $maparea GROUP BY ROUND(vl_lon/$grid_x), ROUND(vl_lat/$grid_y), vl_incident;";
		}
		else {		// top 500
			$sql="SELECT * FROM vl WHERE $filter AND $maparea LIMIT 0, 500 ;";
		}
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_assoc() ) {
				$resp['mapevent'][]=array( $row['vl_id'], vl_icon($row), $row['vl_heading'], $row['vl_lat'], $row['vl_lon'] ) ;
			}
			$result->free();
		}		

		$resp['serial']=$_REQUEST['serial'];
		$resp['res']=1 ;
	}
	echo json_encode( $resp );
?>