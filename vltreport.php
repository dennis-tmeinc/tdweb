<?php
// vltreport.php - persist query for vlt messages
// Requests:
//      vltserial : serial number for request
//      vltpage : page number
// Return:
//      JSON
// By Dennis Chen @ TME	 - 2014-07-29
// Copyright 2013 Toronto MicroElectronics Inc.

	$noupdatetime = 1 ;
    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( !empty( $_REQUEST['vltserial'] ) ) {
		$resp['vltserial'] =  $_REQUEST['vltserial'] ;
	}

	if( $logon ) {
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];

		$errno = 0;
		$errstr='';
		
		$tdwebc = array();

		$mtime = time();
		$starttime = $mtime ;

		$fvlt = fopen( session_save_path().'/sess_vlt_'.$vltsession, "r+" );
		if( $fvlt ) {
			// wait for message from AVL service
			while( ($mtime - $starttime)<300 ) {
				flock( $fvlt, LOCK_EX ) ;		// exclusive lock
		
				fseek( $fvlt, 0, SEEK_SET );
				@$vlt = json_decode( fread( $fvlt, 256000 ), true );

				if( !empty( $vlt['events'] ) ) {
					for( $i=0; $i<count($vlt['events']); $i++ ) {
						$vdata = $vlt['events'][$i] ;
						if( !empty( $vdata['command'] )) {
							$phone = '';
							if( $vdata['command'] == 23 ) {			// AVL_DVR_LIST(23)
								if( !empty( $vdata['avlp']['list']['item'] ) ) {
									if( !empty( $vdata['avlp']['list']['item']['phone'] ) ) {
										$phone = "p=".$vdata['avlp']['list']['item']['phone'] ;
									}
									else{
										for( $ii = 0; $ii<count($vdata['avlp']['list']['item']); $ii++) {
											if( !empty( $vdata['avlp']['list']['item'][$ii]['phone'] ) ) {
												if( !empty( $phone ) ) $phone .= "&" ;
												$phone .= "p[]=". $vdata['avlp']['list']['item'][$ii]['phone'] ;
											}
										}
									}
								}
							}
							else if( $vdata['command'] == 20 ) { // AVL_IP_REPORT(20)
								if( !empty( $vdata['avlp']['phone'] ) ) {
									$phone = "p=".$vdata['avlp']['phone'] ;
								}
							}
							$tdwebc[] = $vdata ;
							
							if( !empty( $phone ) ) {
								$ctx = stream_context_create(array(
								  'http' => array
								  ( 'method' => 'GET', 'timeout' => 1.0 )
								));
								// add live tun register url
								$phonereg = $phone."&u=".rawurlencode(dirname($_SERVER['SCRIPT_NAME']). "/livetun.php"  );
								@file_get_contents("http://tdlive.darktech.org/vlt/vltreg.php?$phonereg", false, $ctx ) ;
							}
							
						}
					}
					unset( $vlt['events'] );	// remove events ;
					
					fseek( $fvlt, 0, SEEK_SET );
					fwrite( $fvlt, json_encode( $vlt ) );

					ftruncate( $fvlt, ftell( $fvlt ) );
					fflush( $fvlt );
				}
				flock( $fvlt, LOCK_UN ) ;		// unlock ;
				
				if( empty( $vlt['run'] ) ) {	// session end
					break ;
				}
				
				if( empty( $tdwebc ) ) {
					usleep( 200000 ) ;
				}
				else {
					break;
				}
				set_time_limit(30);
				$mtime = time();
			}
			
			fclose( $fvlt );
		}
			
		if( empty( $vlt['run'] ) ) {	// session end
			// try delete session file
			@unlink(session_save_path().'/sess_vlt_'.$vltsession);
		}
				
		if( !empty( $tdwebc ) ) {
			$resp['tdwebc'] = $tdwebc ;
			$resp['res'] = 1 ;
		}
		else {
			$resp['errormsg'] = "Empty tdwebc message, time out maybe.";
		}
		
	}

	echo json_encode($resp);	
?>
