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
		$chmapname = 'chmap' ;
		if( empty( $_REQUEST['index'] ) ) {
			// map channel number
			$chmapname = 'chmap-' . $_REQUEST['vehicle_name'] ;
			$channel = $_REQUEST['channel'] ;
			@$channel = $_SESSION[$chmapname][$channel] ;
			
			if( empty( $_REQUEST['dir'] ) ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = $channel AND (  `time_start` <= '$_REQUEST[time_start]' OR `time_end` >= '$_REQUEST[time_start]' ) LIMIT 100" ;
			}
			else if( $_REQUEST['dir'] == 1 ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = $channel AND `time_start` > '$_REQUEST[time_start]' ORDER BY `time_start` LIMIT 100 ;" ;
			}
			else if( $_REQUEST['dir'] == 2 ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = $channel AND `time_start` < '$_REQUEST[time_start]' ORDER BY `time_start` DESC LIMIT 100 ;" ;
			}
			else if( $_REQUEST['dir'] == 3 ) {
				$sql = "SELECT *, ABS( TIMESTAMPDIFF(SECOND, `time_start`, '$_REQUEST[time_start]') ) AS sdiff FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = $channel ORDER BY sdiff LIMIT 100 ;" ;
			}
		}
		else {
			unset( $_SESSION[$chmapname] );
			$sql = "SELECT * FROM videoclip WHERE `index` = $_REQUEST[index] ;" ;
		}
		
		if( !empty($_REQUEST['ser']) )
			$resp['ser'] = $_REQUEST['ser'];

		if($result=$conn->query($sql)) {
			while( $row=$result->fetch_array() ) {
				$resp['vehicle_name'] = $row['vehicle_name'] ;
				$resp['time_start'] = $row['time_start'] ;
				$resp['channel'] = $row['channel'] ;
				// $resp['filename'] = basename($row['path']) ;
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
			if( empty($_SESSION[$chmapname] ) ) {
				$chmapname = 'chmap-' . $resp['vehicle_name'] ;
				$sql = "SELECT DISTINCT channel FROM videoclip WHERE `vehicle_name` = '$resp[vehicle_name]' ORDER BY channel " ;
				$chmap = array();
				if( $result = $conn->query($sql) ) {
					while( $row=$result->fetch_array() ) {
						$chmap[] = $row[0];
					}
					$result->free();
				}	
				$_SESSION[$chmapname] = $chmap ;
				session_write();
			}
			else {
				$chmap = $_SESSION[$chmapname] ;
			}
			
			// output camera names and replace channel to virtual number
			$resp['camera_name'] = array();
			// replace channel to virtual number
			for( $i=0; $i < count($chmap); $i++ ) {
				if( $resp['channel'] == $chmap[$i]  ) {
					$resp['channel'] = $i ;
				}
				$resp['camera_name'][]='camera-' . ($chmap[$i]+1) ;
			}
			$resp['camera_number'] =  count($resp['camera_name']) ;
		}

	}
	echo json_encode($resp);
?>