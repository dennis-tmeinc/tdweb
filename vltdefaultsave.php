<?php
// vltdefaultsave.php - save vlt (Live Track Report) default
// Requests:
//      default: 1-3, index of default settings
//      vlt_... : vlt fields
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-11-13
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		// get vlt_config field types, (int) filed should be set as number
		$sql = "SELECT * FROM vlt_config WHERE `index` = 0" ;  // sql to get fields
		$fields = array();
		if ($result = $conn->query($sql)) {
			while( $finfo = $result->fetch_field() ) {
				$fields[ $finfo->name ] = $finfo->type ;
			}
			$result->free();
		}
		
		// escaped string for SQL
		$esc_req=array();
		foreach( $_REQUEST as $key => $value )
		{
			if( !empty( $fields[ $key ] ) && $fields[ $key ] == MYSQLI_TYPE_LONG ) {
				$esc_req[$key]=(int)$value ;		// make it a number
			}
			else {
				$esc_req[$key]=$conn->escape_string($value);
			}
		}
		
		$vltid = "__default".$_REQUEST['default'] ;
		
		// gen vlt_gpio
		$vlt_gpio="" ;
		for($i=0; $i<32; $i++) {
			if( !empty( $_REQUEST[ 'vlt_gpio_'.$i ] ) ) {
				$vlt_gpio .='1' ;
			}
			else {
				$vlt_gpio .='0' ;
			}
		}
		$sql = "DELETE FROM vlt_config WHERE vlt_user_name = '$_SESSION[user]' AND vlt_config_id = '$vltid' " ;
		$conn->query($sql);
		
		$esc_req['vlt_maxcount'] = (int)$esc_req['vlt_maxcount'];
		$esc_req['vlt_maxbytes'] = (int)$esc_req['vlt_maxbytes'];
		
		// temperatry solution for 'max connt' and 'max bytes' 
		$sql = "INSERT INTO vlt_config (vlt_config_id, vlt_gpio, vlt_impact, vlt_speed, vlt_time_interval, vlt_dist_interval, vlt_temperature, vlt_geo, vlt_idling, vlt_max_count, vlt_max_kb, vlt_user_name ) VALUES ('$vltid', '$vlt_gpio' , '$esc_req[vlt_impact_front],$esc_req[vlt_impact_rear],$esc_req[vlt_impact_side],$esc_req[vlt_impact_bumpy]', '$esc_req[vlt_speed]', '$esc_req[vlt_time_interval]' , '$esc_req[vlt_dist_interval]' ,'$esc_req[vlt_temperature]', '$esc_req[vlt_geo]' ,'$esc_req[vlt_idling]', '$esc_req[vlt_maxcount]', '$esc_req[vlt_maxbytes]', '$_SESSION[user]' ) " ;
		if( $conn->query($sql) ) {
			$resp['res'] |= 1 ;		// success
		}	
	}
	echo json_encode( $resp );
?>