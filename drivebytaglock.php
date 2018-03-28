<?php
// drivebytaglock.php - lock drive by tag file
// Request:
//      tag: tag name (filename)
//      lasttag: previous locked tag
//      plateofviolator: plate number of violator
//      notes: notes of this violator

// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-01-10
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require 'vfile.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {
	
		// update notes to lasttag file 
		if( !empty($_REQUEST['lasttag']) ) {
			$lasttagfile = $driveby_eventdir.'/'.$_REQUEST['lasttag'] ;
			if( vfile_exists( $lasttagfile ) ) {
				$x = new SimpleXMLElement( vfile_get_contents( $lasttagfile ) );
				if( !empty($_REQUEST['plateofviolator']) )
					$x->plateofviolator = $_REQUEST['plateofviolator'] ;
				else 
					unset( $x->plateofviolator ) ;
				
				if( !empty($_REQUEST['notes']) )
					$x->notes = $_REQUEST['notes'] ;
				else 
					unset( $x->notes ) ;

				vfile_put_contents( $lasttagfile, $x->asXML() );
			}
		}
	
		$v = vfile_get_contents( $driveby_eventdir.'/'.$_REQUEST['tag'] );
		if( $v ) {
			$x = new SimpleXMLElement( $v );
			if( $x->busid ) {
				$resp['tag'] = $x ;
				$resp['res'] = 1 ;
			}
		}
	}
	
	echo json_encode($resp);
?>