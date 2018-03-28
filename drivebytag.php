<?php
// drivebytag.php - get tag file detail
// Request:
//      tag: tag name (filename)
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-01-10
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require 'vfile.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {
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