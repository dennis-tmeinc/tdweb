<?php
// vltreportconfigload.php - load vlt report configuration
// Requests:
//      vltpage : page number
//      vltvehicle : vehicle name
// Return:
//      JSON object with vlt fields
// By Dennis Chen @ TME	 - 2013-11-26
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

	if( $logon ) {
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		// escaped string for SQL
		$esc_req=array();
		foreach( $_REQUEST as $key => $value )
		{
			$esc_req[$key]=$conn->escape_string($value);
		}
		
		$vltsession = session_id().'-'.$_REQUEST['vltpage'];
		
		$mtime = time();
		$vdata = $conn->escape_string(json_encode( $_REQUEST )); 
		
		$sql = "SELECT `vdata` FROM `_tmp_tdweb` WHERE `vname` = 'vltrepcfg' AND `user` = '$esc_req[vltvehicle]' AND `session` = '$vltsession' ";
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$vdata = json_decode( $row[0], true );
				for( $io=0 ;$io<32; $io++ ) {
					if( !empty($vdata['vlt_gpio'][$i]) ) {
						$vdata['vlt_gpio_'.$i] = "1" ;
					}
				}
				unset( $vdata['vlt_gpio'] );
				
				$resp['vltconfig'] = $vdata ;
				$resp['res'] = 1 ;
			}
		}
	}
	echo json_encode( $resp );
?>




