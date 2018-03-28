<?php
// drivebytagdelex.php - remove tag
// Request:
//      tag: tag name (filename)
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-05-30
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header("Content-Type: application/json");
			
	if( $logon ) {

		$resp['sqls'] = array();
		
		
		$n = count( $_REQUEST['tag'] ) ;
		
		for( $i = 0 ; $i<$n ; $i++ ) {
		
			$tagname =  $driveby_eventdir.'/'.$_REQUEST['tag'][$i] ;
			$v = file_get_contents( $tagname );
			if( $v ) {
				$x = new SimpleXMLElement( $v );
				$sql = "DELETE FROM Drive_By_Event WHERE Client_Id = '".$x->clientid."' AND Bus_Id = '" . $x->busid . "'  AND Date_Time = '". $x->time."'" ;
				
				$resp['sqls'][] = $sql ;
				
				$conn->query($sql);
				
				$bakname = substr( $tagname, 0, strrpos( $tagname, '.' ) ). ".tbk" ;
				
				if( file_exists($bakname) ) unlink($bakname) ;
				
				@rename( $tagname, $bakname );
				$resp['res'] = 1 ;
			}
		}
	}
	
	echo json_encode($resp);
?>