<?php
// mapquery.php - query map area using BING REST
// Requests:
//      q: query strig (empty to use default map area)
// Return:
//      JSON array of area info
// By Dennis Chen @ TME	 - 2013-12-16
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		if( isset( $_REQUEST['q'] ) ) {
			$url =  "https://dev.virtualearth.net/REST/v1/Locations?q=".rawurlencode($_REQUEST['q'])."&o=json&maxResults=1&key=".$map_credentials ;
		}
		else if( isset( $_REQUEST['p'] ) ){		// points
			$url =  "https://dev.virtualearth.net/REST/v1/Locations/$_REQUEST[p]?o=json&maxResults=1&key=$map_credentials" ;
		}
		else if( !empty($map_area) ) {
			$url =  "https://dev.virtualearth.net/REST/v1/Locations?q=".rawurlencode($map_area)."&o=json&maxResults=1&key=".$map_credentials ;
		}
		else {
			$url =  "https://dev.virtualearth.net/REST/v1/Locations?q=USA&o=json&maxResults=1&key=".$map_credentials ;
		}
		
		@$maparea = file_get_contents( $url );
		if( !empty($maparea) ) {
			$maparea = json_decode($maparea, true) ;
		}
		if( ! empty( $maparea['resourceSets'][0]['resources'][0] )  ) {
			$resp['map'] = $maparea['resourceSets'][0]['resources'][0] ;
			$resp['res'] = 1 ;
		}
	
	}

done:	
	echo json_encode( $resp );
?>