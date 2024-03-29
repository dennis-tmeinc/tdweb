<?php
// vltautoreport.php - start/stop live track auto-report
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
//      start: 1 to start, 0 to stop
// Return:
//      JSON
// By Dennis Chen @ TME	 - 2013-11-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
				
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];

		if( empty($_REQUEST['vltserial'])) {
			$serialno = rand(101, 99999999) ;
		}
		else {
			$serialno = $_REQUEST['vltserial'] ;
			$resp['vltserial'] = $_REQUEST['vltserial'] ;
		}

		$xml = new SimpleXMLElement('<tdwebc></tdwebc>') ;
		
		// clientid support
		if( !empty( $_SESSION['clientid'] ) ) {
			$xml->company = $_SESSION['clientid'] ;
		}
		else {
			// no client support? may be use database name is a good idea?
			$xml->company = $smart_database ;  // only for testing
		}
		
		if( empty($avlcbserver) ) {
			$avlcbserver = $_SERVER['REQUEST_SCHEME'] . "://". $_SERVER['HTTP_HOST'] . ":". $_SERVER['SERVER_PORT']; 
		}
		$xml->callbackurl = $avlcbserver . dirname($_SERVER['REQUEST_URI']). "/vltevent.php" ;
		$xml->session = $vltsession ;
		$xml->serialno = $serialno ;
		
		// read report cfg from vlt file
		$fvlt = fopen( session_save_path().'/sess_vlt_'.$vltsession, "r" );
		if( $fvlt ) {
			flock( $fvlt, LOCK_SH ) ;		// read lock
	
			@$vlt = json_decode( fread( $fvlt, 500000 ), true );
			
			flock( $fvlt, LOCK_UN ) ;
			fclose( $fvlt );
		}
				
		if(!empty( $vlt['run'] ) && !empty( $vlt['cfg'] ) ) {
			foreach ( $vlt['cfg'] as  $key => $vdata ) {

				$resp['res'] = 1 ;
				
				$xml->target->dvrs->dvr = $key ;
				
				$xml->command='26' ; 		// AVL_AUTO_REPORT_CONF(26)
				$xml->avlp='' ;

				if( !empty($_REQUEST['start']) ) {
					$xml->avlp->time = $vdata['vlt_time_interval'] ;
					if( empty($vdata['vlt_dist_interval']) ) {
						$xml->avlp->dist = 0 ;
					}
					else {
						$xml->avlp->dist = (int)( $vdata['vlt_dist_interval'] * 0.3048 );		// feet to meters
					}
					if( empty($vdata['vlt_speed']) ) {
						$xml->avlp->speed = 0 ;
					}
					else {
						// base on country code now
						if( $_SESSION['country'] =="US") {
							// country US, use mph
							$xml->avlp->speed = (int)( $vdata['vlt_speed'] * 1.609344 );		// mph to km/h
						}
						else {
							// vlt_speed is km/h
							$xml->avlp->speed = (int)( $vdata['vlt_speed'] );		// km/h
						}
					}
					$xml->avlp->maxc = $vdata['vlt_maxcount'] ;
					$xml->avlp->maxkb = $vdata['vlt_maxbytes'] ;
					if( !empty($_REQUEST['obd']) ) {
						$xml->avlp->obd = dechex($_REQUEST['obd'])  ;
					}
					else {
						$xml->avlp->obd = 0  ;
					}					
				}
				else {
					$xml->avlp->time = 0;
					$xml->avlp->dist = 0;
					$xml->avlp->speed = 0 ;
					$xml->avlp->maxc = 0 ;
					$xml->avlp->maxkb = 0 ;
					$xml->avlp->obd = 0 ;
				}
				@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );	// don't care what is returned
				if ( !empty ($avl_log) ) {
					file_put_contents ( $avl_log , "REQ: " . $xml->asXML() . "\nANS: ". $avlxml. "\n", FILE_APPEND  );
				}

				$xml->command='29' ; 		// AVL_ALARM_CONF(29)
				$xml->avlp='' ;
				
				if( !empty($_REQUEST['start']) ) {
					$io_low=0;
					$io_high=0;
					$mask=1 ;
					for( $i=0; $i<16; $i++ ) {
						if( !empty($vdata['vlt_gpio'][$i*2] ) ) {
							$io_low+=$mask ;
						}
						if( !empty($vdata['vlt_gpio'][$i*2+1] ) ) {
							$io_high+=$mask ;
						}
						$mask *= 2 ;
					}
					$xml->avlp->lo = dechex($io_low);
					$xml->avlp->hi = dechex($io_high) ;
					$xml->avlp->idle = $vdata['vlt_idling'] ;
					// base on country code, use degree C or degree F
					if($_SESSION['country'] == "US"){
						$tempc = (int)(( $vdata['vlt_temperature'] - 32 ) * 5/9 ) ;		// F to C
					}
					else {
						$tempc = (int)( $vdata['vlt_temperature'] ) ;		// C
					}
					if( $tempc < 1 ) {
						$xml->avlp->temp = 0 ;
					}
					else {
						$xml->avlp->temp = $tempc ;
					}
				}
				else {
					$xml->avlp->lo = 0;
					$xml->avlp->hi = 0;
					$xml->avlp->temp = 0 ;
					$xml->avlp->idle = 0 ;
				}
				@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );	// don't care what is returned
				if ( !empty ($avl_log) ) {
					file_put_contents ( $avl_log , "REQ: " . $xml->asXML() . "\nANS: ". $avlxml. "\n", FILE_APPEND  );
				}

				$xml->command='30' ; 		// AVL_GFORCE_CONF(30)
				$xml->avlp='' ;
				
				if( !empty($_REQUEST['start']) ) {
					$xml->avlp->front = $vdata['vlt_impact_front'];
					$xml->avlp->back = $vdata['vlt_impact_rear'];
					$xml->avlp->left = $vdata['vlt_impact_side'];
					$xml->avlp->right = $vdata['vlt_impact_side'];
					$xml->avlp->bottom = $vdata['vlt_impact_bumpy'];
					$xml->avlp->top = $vdata['vlt_impact_bumpy'];	
					// obd related parameter
					$xml->avlp->hardbrake = $vdata['vlt_hard_brake'];	
					$xml->avlp->fastacc = $vdata['vlt_quick_acceleration'];	

				}
				else {
					$xml->avlp->front = 0;
					$xml->avlp->back = 0;
					$xml->avlp->left = 0 ;
					$xml->avlp->right = 0 ;
					$xml->avlp->bottom = 0 ;
					$xml->avlp->top = 0 ;

					$xml->avlp->hardbrake = 0;
					$xml->avlp->fastacc = 0;

				}
				@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );	// don't care what is returned
				if ( !empty ($avl_log) ) {
					file_put_contents ( $avl_log , "REQ: " . $xml->asXML() . "\nANS: ". $avlxml. "\n", FILE_APPEND  );
				}

				$xml->command='36' ; 		// AVL_GEOFENCE_RECT_SET(36)
				$xml->avlp = '';
				$xml->avlp->{'list'} = '';
				
				if( !empty($_REQUEST['start']) ) {
					$geos = explode(';', $vdata['vlt_geo'] );
					$i = 0 ;
					foreach( $geos as $geo ) {
						$ex = explode(',', $geo );
						if( $ex[4] == 'In' ) {
							$ex[4] = 1 ;
						}
						else if( $ex[4] == 'Out' ) {
							$ex[4] = 2 ;
						}
						else if( $ex[4] == 'Both' ) {
							$ex[4] = 3 ;
						}
						else {
							continue ;
						}
						$xml->avlp->{'list'}->item[$i]->lat = $ex[0] ;
						$xml->avlp->{'list'}->item[$i]->lon = $ex[1] ;
						$xml->avlp->{'list'}->item[$i]->lat2 = $ex[2] ;
						$xml->avlp->{'list'}->item[$i]->lon2 = $ex[3] ;
						$xml->avlp->{'list'}->item[$i]->dir = $ex[4] ;
						$i++;
					}
				}
				else {
					$resp['stop'] = 1 ;
				}
				@$avlxml = file_get_contents( $avlservice.'?xml='.rawurlencode($xml->asXML()) );	// don't care what is returned
				if ( !empty ($avl_log) ) {
					file_put_contents ( $avl_log , "REQ: " . $xml->asXML() . "\nANS: ". $avlxml. "\n", FILE_APPEND  );
				}				
			}
		}
	}
	
done:	
	echo json_encode($resp);	
?>