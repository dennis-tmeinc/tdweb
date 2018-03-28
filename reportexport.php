<?php
// reportexport.php - export report view events
// Requests:
//      All summary values
// Return:
//      CSV file
// By Dennis Chen @ TME	 - 2013-12-18
// Copyright 2013 Toronto MicroElectronics Inc.

	require 'session.php' ;
	
	if( $logon ) {
		header( "Content-Type: application/octet-stream" );
		header( "Content-Disposition: attachment; filename=report.csv" );   	
		
		$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		$output = fopen('php://output', 'w');
		
		// get total records
		if( empty($_SESSION['mapfilter']['filter']) ) {
			$filter = 'FALSE';
		}
		else {
			$filter = $_SESSION['mapfilter']['filter'] ;
		}
		$sql = "SELECT * FROM vl WHERE $filter";
		
		
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

			$event_name=array(
				1 => "stopping" ,
				2 => "route",
				4 => "idling",
				16 => "G force",
				17 => "bus stops",
				18 => "parking",
				23 => "event" ,
				40 => "drive by",
				100 => "speeding" ,
				101 => "front impact" ,
				102 => "rear impact" ,
				103 => "side impact" ,
				104 => "hard brake" ,
				105 => "racing start" ,
				106 => "hard turn" ,
				107 => "bumpy ride"  );
			if( empty( $event_name[$icon] ) ) {
				return "unknown" ;
			}
			else {
				return $event_name[$icon] ;
			}
		}
		
		fputs( $output, "Vehicle,Driver,Activity,DateTime,Duration,Coordinates\r\n");

		if( $result = $conn->query($sql,MYSQLI_USE_RESULT) ) {
			// contents
			while( $row = $result->fetch_array() ) {
				$line=array();
				$line[] = $row['vl_vehicle_name'];
				$line[] = $row['vl_driver_name'];
				$line[] = vl_icon($row);
				$line[] = $row['vl_datetime'];
				$line[] = $row['vl_time_len'];
				$line[] = $row['vl_lat'].' '.$row['vl_lon'];
				fputcsv ( $output , $line );
			}
			$result->free();
		}			
		fputs($output,"\r\n\r\nSummary\r\n");
		foreach( $_REQUEST as $key => $value ){
			$line=array();
			$key =  str_replace('_', ' ', $key );
			$key =  str_replace(':', '', $key );
			$line[] = $key ;
			$line[] = $value ;
			fputcsv ( $output , $line );
		}
	}
?>
