<?php
// storageload.php - load storage settings
//      storage settings are save on Windows registry HKLM\Software\tme\touchdown
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		$resp['store']=array();
		
		function ex($cmd, &$result, &$ret)
		{
			if( $fsvr = vfile_remote() ) {
				$j = vfile_readhttp( $fsvr."?c=e&n=".rawurlencode($cmd) ) ;
				@$st = json_decode( $j, true );
				if( !empty( $st['res'] ) ) {
					$result = $st['output'] ;
					$ret = $st['ret'] ;
				}
			}
			else {
				exec( $cmd,$result,$ret);
			}
		}
		
		$result=array();
		$ret=-1 ;
		ex("reg query HKLM\\Software\\tme\\touchdown",$result,$ret);
		if( $ret != 0 ) {
			$result=array();
			$ret=-1 ;		
			// query on 64 bit OS
			ex("reg query HKLM\\SOFTWARE\\Wow6432Node\\tme\\touchdown",$result,$ret);
		}
		if( $ret == 0 ) {	// success
			for($i=0; $i<count($result); $i++) {
				$keys=explode("REG_SZ",$result[$i],2);
				if( count($keys)==2 ) {
					$resp['store'][trim($keys[0])]=trim($keys[1]);
				}			
			}
		}
		
		// to read keepGpsLogDataForDays	keepVideoDataForDays
		// MySQL connection
		$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql="SELECT keepGpsLogDataForDays, keepVideoDataForDays FROM tdconfig ;";
		if( $result = $conn->query($sql) ) {
			if( $row = $result->fetch_array() ) {
				$days = $row['keepGpsLogDataForDays'] ;
				// justify days
				$resp['store']['keepGpsLogDataForDays']=( $days < 365 )?round($days/31)*31: round($days/366)*366 ;
				$days = $row['keepVideoDataForDays'] ;
				// justify days
				$resp['store']['keepVideoDataForDays']=( $days < 365 )?round($days/31)*31: round($days/366)*366 ;
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}			
		}
		else {
			$resp['errormsg']="SQL error: ".$conn->error ;
		}			
	}
	echo json_encode($resp);
?>