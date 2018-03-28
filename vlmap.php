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

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
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
		$grid_x = $dist_x*18/$width ;
		$grid_y = $dist_y*18/$height ;
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
		
		function vl_icon($row)
		{
			global $_SESSION ;
			
			$icon = (int)$row['vl_incident'] ;
			if( $icon == 2 ) {			// route, check for speeding
				if( $_SESSION['mapfilter']['bSpeeding'] && $row['vl_speed'] > $_SESSION['mapfilter']['speedLimit'] ) {
					$icon = 100 ;		// speeding icon
				}
			}
			else if( $icon == 16 ) {	// g-force event
				if( $_SESSION['mapfilter']['bFrontImpact'] && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gFrontImpact'] ) 
					$icon=101;			// fi
				else if( $_SESSION['mapfilter']['bRearImpact'] && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRearImpact'] ) 
					$icon=102;			// ri
				else if( $_SESSION['mapfilter']['bSideImpact'] && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gSideImpact'] )
					$icon=103;			// si
				else if( $_SESSION['mapfilter']['bHardBrake'] && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gHardBrake'] ) 
					$icon=104;			// hb
				else if( $_SESSION['mapfilter']['bRacingStart']  && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRacingStart'] ) 
					$icon=105;			// rs
				else if( $_SESSION['mapfilter']['bHardTurn']  && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gHardTurn'] )
					$icon=106;			// ht
				else if( $_SESSION['mapfilter']['bBumpyRide'] && abs(1.0-$row['vl_impact_z']) >= $_SESSION['mapfilter']['gBumpyRide'] )
					$icon=107;			// br
			}

			return $icon;
		}

		if( ( $dist_x/$width < 5.0E-5 && $resp['record'] < 300 ) || $resp['record'] < 2*$map_icons ) {
			// full records
			$sql="SELECT * FROM vl WHERE $filter AND $maparea ;";
		}
		else if ( $mapmode == 'limit' ) {		// limiting mode
			$divisor = (int)($resp['record'] / $map_icons) ;
			$sql="SELECT * FROM vl WHERE $filter AND $maparea AND (vl_id % $divisor = 0) ;";
		}		
		else if ($mapmode == 'grid' ) {			// gird mode
			// route icons
			$sql="SELECT * FROM vl WHERE $filter AND $maparea GROUP BY (vl_incident != '2'), ROUND((vl_lon-$west)/$grid_x), ROUND((vl_lat-$south )/$grid_y); ";
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
		$conn->close();
	}
	echo json_encode( $resp );
?>