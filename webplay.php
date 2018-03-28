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
// By Dennis Chen @ TME	 - 2013-10-30
// Copyright 2013 Toronto MicroElectronics Inc.
//

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon && !empty($webplay_support) ) {
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

		if( empty( $_REQUEST['index'] ) ) {
			if( empty( $_REQUEST['dir'] ) ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' AND (  `time_start` <= '$_REQUEST[time_start]' OR `time_end` >= '$_REQUEST[time_start]' );" ;
			}
			else if( $_REQUEST['dir'] == 1 ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' AND `time_start` > '$_REQUEST[time_start]' ORDER BY `time_start` LIMIT 1 ;" ;
			}
			else if( $_REQUEST['dir'] == 2 ) {
				$sql = "SELECT * FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' AND `time_start` < '$_REQUEST[time_start]' ORDER BY `time_start` DESC LIMIT 1 ;" ;
			}
			else if( $_REQUEST['dir'] == 3 ) {
				$sql = "SELECT *, ABS( TIMESTAMPDIFF(SECOND, `time_start`, '$_REQUEST[time_start]') ) AS sdiff FROM videoclip WHERE `vehicle_name` = '$_REQUEST[vehicle_name]' AND `channel` = '$_REQUEST[channel]' ORDER BY sdiff LIMIT 1 ;" ;
				
				$resp['sql'] = $sql ;
			}
		}
		else {
			$sql = "SELECT * FROM videoclip WHERE `index` = $_REQUEST[index] ;" ;
		}
		$resp['ser'] = $_REQUEST['ser'];
				
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array() ) {
				$resp['vehicle_name'] = $row['vehicle_name'] ;
				$resp['time_start'] = $row['time_start'] ;
				$resp['channel'] = $row['channel'] ;
				$resp['filename'] = basename($row['path']) ;
						
			    $cachemp4 = 'videocache/v'.md5($row['path']).'.mp4' ;
				
				$cachefn = dirname( $_SERVER["SCRIPT_FILENAME"] ).'/'.$cachemp4 ;
				if( file_exists( $cachefn ) ) {
					$resp['mp4'] = $cachemp4 ;
					$resp['res'] = 1 ;
				}
				else {
					// check directory size
					$cachedir = dirname( $_SERVER["SCRIPT_FILENAME"] ).'/videocache' ;
					$cachesize = 0 ;
					if( empty( $$webplay_cache_size ) ) {
						$webplay_cache_size = 10000 ;
					}
					$remaindays = 10 ;
					do {
						$cachesize = 0 ;
						foreach (glob($cachedir."/*") as $filename) {
							if (fileatime($filename) + ($remaindays*24*60*60) < $xt ) {
								@unlink($filename);
							}
							else {
								$cachesize += filesize( $filename )/1000000 ;
							}
						}
					} while( $cachesizie > $webplay_cache_size && $remaindays-- > 1 ) ;
				
					$eoutput = array();
					$eret = 1 ;
					$cmdline = dirname( $_SERVER["SCRIPT_FILENAME"] )."/bin/ffmpeg.exe -i $row[path] -y -codec:v copy $cachefn" ;
					$lline = exec( escapeshellcmd($cmdline), $eoutput, $eret ) ;
					
					if( $eret == 0 ) {
						$resp['mp4'] = $cachemp4 ;
						$resp['res'] = 1 ;
					}
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