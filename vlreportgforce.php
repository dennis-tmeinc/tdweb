<?php
// vlreportgforce.php -  report g-force events statistics number 
// Requests:
//             
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-23
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		$mapfilter = $_SESSION['mapfilter']['filter'] ;
		$param =  $_SESSION['mapfilter'];
		
		$resp['summary']=array();

		// G-force 
		
		// Rear Impacts:
		$resp['summary']['rearimpacts']=0 ;
		if( $_SESSION['mapfilter']['bRearImpact'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND vl_impact_x >= $param[gRearImpact] ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['rearimpacts']=$row[0] ;
			$result->free();
		}	
		}
		
		// Racing start
		$resp['summary']['racingstart']=0 ;
		if( $_SESSION['mapfilter']['bRacingStart'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND vl_impact_x >= $param[gRacingStart] AND vl_impact_x < $param[gRearImpact] ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['racingstart']=$row[0] ;
			$result->free();
		}	
		}
					
		// Front Impacts:
		$resp['summary']['frontimpacts']=0 ;
		if( $_SESSION['mapfilter']['bFrontImpact'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND vl_impact_x <= -$param[gFrontImpact] ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['frontimpacts']=$row[0] ;
			$result->free();
		}	
		}
		
		// Hard Brake
		$resp['summary']['hardbrake']=0;
		if( $_SESSION['mapfilter']['bHardBrake'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND vl_impact_x <= -$param[gHardBrake] AND vl_impact_x > -$param[gFrontImpact] ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['hardbrake']=$row[0] ;
			$result->free();
		}	
		}
		
		// Side Impacts:
		$resp['summary']['sideimpacts']=0 ;
		if( $_SESSION['mapfilter']['bSideImpact'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND ABS(vl_impact_y) >= $param[gSideImpact];" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['sideimpacts']=$row[0] ;
			$result->free();
		}	
		}
		
		// Hard Turn
		$resp['summary']['hardturn']=0 ;
		if( $_SESSION['mapfilter']['bHardTurn'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND ABS(vl_impact_y) >= $param[gHardTurn] AND ABS(vl_impact_y) < $param[gSideImpact] ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['hardturn']=$row[0] ;
			$result->free();
		}
		}
		
		// Bumpy Ride
		$resp['summary']['bumpyrides']=0 ;
		if( $_SESSION['mapfilter']['bBumpyRide'] ) {
		$sql="SELECT count(*) FROM vl WHERE $mapfilter AND vl_incident = 16 AND ABS(vl_impact_z - 1.0) >= $param[gBumpyRide] ;" ;
		$result=$conn->query($sql);
		if( !empty($result)) {
			$row = $result->fetch_array( MYSQLI_NUM ) ;
			$resp['summary']['bumpyrides']=$row[0] ;
			$result->free();
		}	
		}
		
		$resp['serial'] = $_REQUEST['serial'] ;
		$resp['res'] = 1 ;

	}
	echo json_encode( $resp );
?>