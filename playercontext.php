<?php
// playercontext.php - generate a video play back context file
// 
// Make it a function, moved from playback.php
//
// By Dennis Chen @ TME	 - 2013-10-29
// Copyright 2013 Toronto MicroElectronics Inc.
//

// Generate a video play back context file
// Request:
//      index 		 : video clip index  (index or vehicle_name+playtime)
//      vehicle_name : vehicle name , if video clip id is not available
//      playtime 	 : play back time
function playbackcontext( $index, $vehicle_name, $playtime )
{
	global $conn ,$support_https_playback ;

	$server = array() ;

	if( empty( $_SERVER["REQUEST_SCHEME"] ) ) {
		$server['protocol'] = "http" ;
	}
	else {
		$server['protocol'] = $_SERVER["REQUEST_SCHEME"] ;
	}
	
	$server['port']=$_SERVER["SERVER_PORT"];
	$server['host']=$_SERVER["SERVER_NAME"];
	
	if( empty($support_https_playback) && $server['protocol'] == "https" ) {
		// https not supported
		$server['protocol'] = "http" ;
		$server['port']="80" ;
	}
	
	$server['app']=dirname( $_SERVER["SCRIPT_NAME"] )."/istream.php" ;
	$server['url']= $server['protocol'].'://'.$server['host'].":".$server['port'].$server['app'] ;
	$server['sessionidname']=session_name();
	$server[$server['sessionidname']]=session_id() ;
	
	$info = array();
	$info['name'] = $vehicle_name ;
	$info['encoder'] = "266" ;			// default encoder
	$info['camera_number'] = 0 ;
	$info['support_playback'] = 1 ;
	$info['support_live'] = 0 ;
	$info['playmode'] = "playback" ;
	$info['playtime'] = "2010-01-01 00:00:00" ;
	
	if( !empty($index) ){
		$sql = "SELECT vehicle_name, time_start, path FROM videoclip WHERE `index` = ${index} ;" ;
		if( $result = $conn->query($sql) ) {
			if( $row=$result->fetch_array() ) {
				$info['name'] = $row['vehicle_name'] ;
				$info['playtime'] = $row['time_start'] ;
				
				$path_parts = pathinfo($row['path']);
				$info['encoder'] = $path_parts['extension'] ;
			}
			$result->free();
		}
	}
	else {
		$info['playtime'] = $playtime ;
		$sql = "SELECT vehicle_name, path FROM videoclip WHERE vehicle_name = '$vehicle_name' DESC LIMIT 1;" ;
		if( $result = $conn->query($sql) ) {
			if( $row=$result->fetch_array() ) {
				$info['name'] = $row['vehicle_name'] ;
				
				$path_parts = pathinfo($row['path']);
				$info['encoder'] = $path_parts['extension'] ;
			}
			$result->free();
		}
	}
	
	// find channels number
	$camera_number = 0 ;
	$info['cameras'] = array( 
		'available' => 0 ,
		'channels' => array()
			);
	$sql = "SELECT DISTINCT channel FROM videoclip WHERE `vehicle_name` = '$info[name]' ORDER BY channel " ;
	if( $result = $conn->query($sql) ) {
		while( $row=$result->fetch_array() ) {
			$camera_number = $row[0] + 1 ;
			$camera_name = 'camera' . $camera_number ;
			$info[$camera_name] = 'camera-' . $camera_number ;

			$n = $info['cameras']['available'];
			$info['cameras']['channels'][$n] = $camera_number ;
			$info['cameras']['available'] = $n+1 ;
		}
		$result->free();
	}
	$info['camera_number'] = $camera_number ;

	$playlist = array();
	$playlist['info'] = $info ;
	$_SESSION['playlist'] = $playlist;
	
	// default player sync time
	$playsync = array();
	$playsync['run'] = false ;
	$playtime = new DateTime($info['playtime']);
	$playsync['playtime'] = $playtime->getTimestamp();
	$playtime = new DateTime();
	$playsync['reporttime'] = $playtime->getTimestamp();

	$_SESSION['playsync'] = $playsync;

	session_write();

	return array(
		'server' => $server ,
		'info' => $info 
	);
}

return ;
?>