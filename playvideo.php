<?php
// videoplay.php - generate a video play back list file
// Request:
//      index : video clip id
//      vehicle_name : vehicle name , if video clip id is not available
// Return:
//      .dpl file , contain JSON contents
// By Dennis Chen @ TME	 - 2013-09-26
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header( "Content-Type: application/x-touchdown-playlist");
	header( "Content-Disposition: attachment; filename=\"touchdown.dpl\"" );
	
	if( $logon ){

		$server = array() ;
		$server['protocol']="http" ;
		$server['host']=$_SERVER["SERVER_NAME"];
		$server['port']=$_SERVER["SERVER_PORT"];
		$server['app']=dirname( $_SERVER["SCRIPT_NAME"] )."/istream.php" ;
		$server['url']="http://".$server['host'].":".$server['port'].$server['app'] ;
		$server['sessionidname']=session_name();
		$server[$server['sessionidname']]=session_id() ;
		
		$info = array();
		$info['name'] = $_REQUEST['vehicle_name'] ;
		$info['encoder'] = "" ;
		$info['camera_number'] = 0 ;
		$info['support_playback'] = 1 ;
		$info['support_live'] = 0 ;
		$info['playtime'] = "2013-08-01 09:30:00" ;
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		
		if( !empty($_REQUEST['index']) ) {
			$sql = "SELECT vehicle_name, time_start, path FROM videoclip WHERE `index` = $_REQUEST[index] ;" ;
		}
		else {
			$sql = "SELECT vehicle_name, time_start, path FROM videoclip WHERE `vehicle_name` = '$info[name]' ORDER BY time_start DESC LIMIT 1;" ;
		}
		if( $result = $conn->query($sql) ) {
			if( $row=$result->fetch_array() ) {
				$info['name'] = $row['vehicle_name'] ;
				$info['playtime'] = $row['time_start'] ;
				
				$path_parts = pathinfo($row['path']);
				$info['encoder'] = $path_parts['extension'] ;
			}
			$result->free();
		}

		// find channels number
		$sql = "SELECT MAX(channel) FROM videoclip WHERE `vehicle_name` = '$info[name]' ;" ;
		if( $result = $conn->query($sql) ) {
			if( $row=$result->fetch_array() ) {
				$info['camera_number'] =  $row[0] + 1 ;
				for( $i = 0 ; $i<$info['camera_number']; $i++ ) {
					$camera_name = 'camera' . ($i+1) ;
					$info[$camera_name] = $camera_name ;
				}
			}
			$result->free();
		}
		
		$conn->close();
		
		$dpl = array();
		$dpl["server"] = $server ;
		$dpl['info'] = $info ;
		
		$playlist = array();
		$playlist['info'] = $info ;
		$_SESSION['playlist'] = $playlist ;
		session_write() ;

		echo "#DPL\r\n" ;
		echo "# Touch Down Center ". $_SESSION['release']."\r\n\r\n" ;
		
		echo json_encode( $dpl ) ;
	}
	else {
		echo json_encode( $resp );
	}
?>