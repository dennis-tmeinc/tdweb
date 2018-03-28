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
		if( empty( $_REQUEST['q'] ) ) {
			if( empty( $map_area ) ) {
				$query = "United States" ;
			}
			else {
				$query = $map_area ;
			}
		}
		else {
			$query = $_REQUEST['q'] ;
		}
		
		$url =  "http://dev.virtualearth.net/REST/v1/Locations?q=".rawurlencode($query)."&o=json&maxResults=1&key=".$map_credentials ;
		@$maparea = file_get_contents( $url );
		if( !empty($maparea) ) {
			$maparea = json_decode($maparea, true) ;
		}
		$resp['map'] = array();
		if( !empty( $maparea['resourceSets'][0]['resources'][0]['bbox'] )) {
			$resp['map']['bbox'] =  $maparea['resourceSets'][0]['resources'][0]['bbox'] ;
		}
		if( !empty( $maparea['resourceSets'][0]['resources'][0]['name'] ) ) {
			$resp['map']['name'] =  $maparea['resourceSets'][0]['resources'][0]['name'] ;
		}
		if( !empty( $maparea['resourceSets'][0]['resources'][0]['point']['coordinates']) ) {
			$resp['map']['point'] = $maparea['resourceSets'][0]['resources'][0]['point']['coordinates'] ;
		}
		if( empty( $resp['map']['bbox'] ) ) {
			$resp['map']['bbox'] =  array(18.299999237060547,-172.60000610351563,71.699996948242188,-67.4000015258789) ;
			$resp['map']['name'] =  "United States";
			$resp['map']['point'] = array(43.648399353027344,-79.485702514648438);
		}
		$resp['res'] = 1 ;
	
	}

done:	
	echo json_encode( $resp );
?>