<?php
// webplay.php - check if web play available
// Request:
//      index : video clip id
//      dir :   0 = current video, 1 = next video, other = previous video
//      vehicle_name : 
//      time_start :
//      channel :
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-2-14
// Copyright 2013 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon && !empty($webplay_support) ) {
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		if( empty( $_REQUEST['index'] ) ) {
			if( empty( $_REQUEST['dir'] ) ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' AND (  `time_start` <= '$_REQUEST[time_start]' OR `time_end` >= '$_REQUEST[time_start]' ) LIMIT 100" ;
			}
			else if( $_REQUEST['dir'] == 1 ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' AND `time_start` > '$_REQUEST[time_start]' ORDER BY `time_start` LIMIT 100 ;" ;
			}
			else if( $_REQUEST['dir'] == 2 ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' AND `time_start` < '$_REQUEST[time_start]' ORDER BY `time_start` DESC LIMIT 100 ;" ;
			}
			else if( $_REQUEST['dir'] == 3 ) {
				$sql = "SELECT *, ABS( TIMESTAMPDIFF(SECOND, `time_start`, '$_REQUEST[time_start]') ) AS sdiff FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' ORDER BY sdiff LIMIT 100 ;" ;
			}
		}
		else {
			$sql = "SELECT * FROM videoclip WHERE `index` = $_REQUEST[index] ;" ;
		}
		$resp['ser'] = $_REQUEST['ser'];

		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array() ) {
				$resp['vehicle_name'] = $row['vehicle_name'] ;
				$resp['time_start'] = $row['time_start'] ;
				$resp['channel'] = $row['channel'] ;
				$resp['filename'] = basename($row['path']) ;
				if( vfile_size( $row['path'] ) > 1000 ) {
					$resp['mp4'] = "mp4preview.php?index=".$row['index'] ;
					$resp['res'] = 1 ;
					break ;
				}
			}
			$result->free();
		}
		// detect total channel number
		if( $resp['res'] == 1 ) {
			$sql = "SELECT MAX(channel) FROM videoclip WHERE `vehicle_name` = '$resp[vehicle_name]' ;" ;
			if( $result = $conn->query($sql) ) {
				if( $row=$result->fetch_array() ) {
					$resp['camera_number'] =  $row[0] + 1 ;
					$resp['camera_name'] = array();
					for( $i = 0 ; $i<$resp['camera_number']; $i++ ) {
						$resp['camera_name'][$i] = 'camera' . ($i+1) ;
					}
				}
				$result->free();
			}			
		}

		$conn->close();
	}
	echo json_encode($resp);
?>