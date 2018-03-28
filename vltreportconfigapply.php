<?php
// vltreportconfigapply.php - apply vlt report configuration
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
//      vlt_... : vlt fields
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-04-08
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		$vltsession = session_id().'-'.$_REQUEST['vltpage'];
		
		$repcfg = array();
		$vlt_gpio = '' ;
		for( $io=0 ;$io<32; $io++ ) {
			$k = 'vlt_gpio_'.$io ;
			if( empty( $_REQUEST[$k] )) {
				$vlt_gpio .= '0' ;
			}
			else {
				$vlt_gpio .= '1' ;
			}
		}
		$repcfg['vlt_gpio'] = $vlt_gpio ;
		$repcfg['vlt_impact_front'] = empty($_REQUEST['vlt_impact_front'])?0:$_REQUEST['vlt_impact_front'] ;
		$repcfg['vlt_impact_rear'] = empty($_REQUEST['vlt_impact_rear'])?0:$_REQUEST['vlt_impact_rear'] ;
		$repcfg['vlt_impact_side'] = empty($_REQUEST['vlt_impact_side'])?0:$_REQUEST['vlt_impact_side'] ;
		$repcfg['vlt_impact_bumpy'] = empty($_REQUEST['vlt_impact_bumpy'])?0:$_REQUEST['vlt_impact_bumpy'] ;

		$repcfg['vlt_speed'] = empty($_REQUEST['vlt_speed'])?0:$_REQUEST['vlt_speed'] ;
		$repcfg['vlt_time_interval'] = empty($_REQUEST['vlt_time_interval'])?0:$_REQUEST['vlt_time_interval'] ;
		$repcfg['vlt_dist_interval'] = empty($_REQUEST['vlt_dist_interval'])?0:$_REQUEST['vlt_dist_interval'] ;
		$repcfg['vlt_maxcount'] = empty($_REQUEST['vlt_maxcount'])?0:$_REQUEST['vlt_maxcount'] ;
		$repcfg['vlt_maxbytes'] = empty($_REQUEST['vlt_maxbytes'])?0:$_REQUEST['vlt_maxbytes'] ;
		$repcfg['vlt_temperature'] = empty($_REQUEST['vlt_temperature'])?0:$_REQUEST['vlt_temperature'] ;
		$repcfg['vlt_idling'] = empty($_REQUEST['vlt_idling'])?0:$_REQUEST['vlt_idling'] ;
		$repcfg['vlt_geo'] = empty($_REQUEST['vlt_geo'])?'':$_REQUEST['vlt_geo'] ;

		$vehicles = array();
		if( $_REQUEST['vlt_select_type'] == 2 ) {
			// groups
			foreach( $_REQUEST['vlt_vehicle'] as $group ) {
				// to read group 
				$sql="SELECT `vehiclelist` FROM `vgroup` WHERE `name` = '$group' ;";
				if( $result=$conn->query($sql) ) {
					if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
						$vehicles = array_merge( $vehicles, explode(',', $row[0]));
					}
				}
			}		
			$vehicles = array_unique( $vehicles );
		}
		else {
			$vehicles = $_REQUEST['vlt_vehicle'] ;
		}
		
		// vlt session file
		$fvlt = fopen( session_save_path().'/sess_vlt_'.$vltsession, "r+" );
		if( $fvlt ) {
			flock( $fvlt, LOCK_EX ) ;		// exclusive lock
			
			@$vlt = json_decode( fread( $fvlt, 256000 ), true );
			if(!empty( $vlt['run'] ) ) {
				if( empty( $vlt['cfg'] ) ) {
					$vlt['cfg'] = array() ;
				}
				
				foreach( $vehicles as $vehicle ) {
					$vlt['cfg'][$vehicle] = $repcfg ;
				}

				fseek( $fvlt, 0, SEEK_SET );
				fwrite( $fvlt, json_encode($vlt) );
		
				ftruncate( $fvlt, ftell($fvlt) );
				fflush( $flvt ) ;              	// flush before release the lock
			}
			
			flock( $fvlt, LOCK_UN );
			fclose( $fvlt );
		}

		$resp['res'] = 1 ;

	}
	echo json_encode( $resp );
?>




