<?php
// vltdefaultload.php - load vlt (Live Track Report) default
// Requests:
//      default: 1-3, index of default settings
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-11-12
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$vltid = "__default".$_REQUEST['default'] ;
		$sql="SELECT * FROM  vlt_config WHERE vlt_user_name = '$_SESSION[user]' AND vlt_config_id = '$vltid' " ;
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_assoc() ){

				// mod vlt_gpio
				for( $i=0; $i<32; $i++) {
					if( !empty($row['vlt_gpio'][$i]) ) {
						$row['vlt_gpio_'.$i] = "1" ;
					}
					else {
						$row['vlt_gpio_'.$i] = "0" ;
					}
				}
				unset($row['vlt_gpio']);
				
				// mod vlt_impact
				$vlt_impact = explode(',', $row['vlt_impact']);
				$row['vlt_impact_front'] = $vlt_impact[0] ;
				$row['vlt_impact_rear'] = $vlt_impact[1] ;
				$row['vlt_impact_side'] = $vlt_impact[2] ;
				$row['vlt_impact_bumpy'] = $vlt_impact[3] ;
				unset($row['vlt_impact']);
				
				// temperatry solution for 'max connt' and 'max bytes' 
				$row['vlt_maxcount'] = $row['vlt_max_count'] ;
				unset($row['vlt_max_count']);

				$row['vlt_maxbytes'] = $row['vlt_max_kb'] ;
				unset($row['vlt_max_kb']);

				foreach( $row as $key => $value )
				{
					if( empty($row[$key] ) ) 
						$row[$key]='' ;				// make it empty string
				}
				
				$resp['vltconfig'] = $row ;
				$resp['res'] = 1 ;
			}
		}
	}
	echo json_encode( $resp );
?>