<?php
// playsync.php - setup synchronization between MAP VIEW and DVRViewer
// Requests:
//      map event filter parameters
//      maprect:  rect from map
// Return:
//      JSON array of map events
// By Dennis Chen @ TME	 - 2013-10-17
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		$now = new DateTime();
		$nowts = $now->getTimestamp();
		if( empty( $_SESSION['playsync'] ) ) {
			$resp['errormsg']='No player !' ;
		}
		else {
			if( empty( $_SESSION['playsync']['run']) ){
				// not run
				@$playtime = $_SESSION['playsync']['playtime'] ;
			}
			else {
				// normall run
				@$playtime = $_SESSION['playsync']['playtime'] + ( $nowts - $_SESSION['playsync']['reporttime'] )  ;
			}
			$t_ptime = new DateTime();
			@$t_ptime->setTimestamp($playtime);
			$ptime1 = $t_ptime->format('Y-m-d H:i:s');
			
			@$t_ptime->setTimestamp($playtime-600);
			$ptime2 = $t_ptime->format('Y-m-d H:i:s');
			
			$dname='';
			@$dname = $_SESSION['playlist']['info']['name'] ;

			// special icon id ,
			// 10000:"Speeding" ,
			// 10001:"Front Impact" ,
			// 10002:"Rear Impact" ,
			// 10003:"Side Impact" ,
			// 10004:"Hard Brake" ,
			// 10005:"Racing Start" ,
			// 10006:"Hard Turn" ,
			// 10007:"Bumpy Ride" 		
			function vl_icon($row)
			{
				$icon = (int)$row['vl_incident'] ;
				if( $icon == 2 ) {			// route, check for speeding
					if( !empty($_SESSION['mapfilter']['bSpeeding']) && $row['vl_speed'] > $_SESSION['mapfilter']['speedLimit'] ) {
						$icon = 10000 ;		// speeding icon
					}
				}
				else if( $icon == 16 ) {	// g-force event
					if( !empty($_SESSION['mapfilter']['bFrontImpact']) && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gFrontImpact'] ) 
						$icon=10001;			// fi
					else if( !empty($_SESSION['mapfilter']['bRearImpact']) && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRearImpact'] ) 
						$icon=10002;			// ri
					else if( !empty($_SESSION['mapfilter']['bSideImpact']) && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gSideImpact'] )
						$icon=10003;			// si
					else if( !empty($_SESSION['mapfilter']['bHardBrake']) && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gHardBrake'] ) 
						$icon=10004;			// hb
					else if( !empty($_SESSION['mapfilter']['bRacingStart'])  && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRacingStart'] ) 
						$icon=10005;			// rs
					else if( !empty($_SESSION['mapfilter']['bHardTurn'])  && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gHardTurn'] )
						$icon=10006;			// ht
					else if( !empty($_SESSION['mapfilter']['bBumpyRide']) && abs(1.0-$row['vl_impact_z']) >= $_SESSION['mapfilter']['gBumpyRide'] )
						$icon=10007;			// br
				}

				return $icon;
			}
			
			$sql = "SELECT * FROM vl WHERE vl_vehicle_name = '$dname' AND vl_datetime <= '$ptime1' AND vl_datetime > '$ptime2' ORDER BY vl_datetime DESC LIMIT 1 ;" ;

			if( $result=$conn->query($sql) ) {
				if( $row = $result->fetch_array( MYSQLI_ASSOC ) ) {
					$resp['mapevent']=array( $row['vl_id'], vl_icon($row), $row['vl_heading'], $row['vl_lat'], $row['vl_lon'] ) ;
					$resp['res'] = 1 ;
				}
				$result->free();
			}
			if( empty( $resp['mapevent'] )) {
				$resp['res'] = 0 ;
				$resp['errormsg']="No location info!" ;
			}
		}
	}
	
	echo json_encode( $resp );
?>