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
		
		$result=array();
		$ret=-1 ;
		
		if(empty($company_root)) {		// only works on single company site
			if( empty($stroage_regkey) ) {
				vfile_exec("reg query HKLM\\SOFTWARE\\Wow6432Node\\tme\\touchdown", $result, $ret);
				if( $ret == 0 ) {
					// 64bit OS
					$stroage_regkey = "HKLM\\SOFTWARE\\Wow6432Node\\tme\\touchdown" ;
				}
				else {
					// 32bit OS
					$stroage_regkey = "HKLM\\SOFTWARE\\tme\\touchdown" ;
				}
			}
			
			if( $ret!=0 )
				vfile_exec("reg query $stroage_regkey",$result,$ret);
				
			if( $ret == 0 ) {	// success
				for($i=0; $i<count($result); $i++) {
					$keys=explode("REG_SZ",$result[$i],2);
					if( count($keys)==2 ) {
						$resp['store'][trim($keys[0])]=trim($keys[1]);
					}			
				}
			}
		}
		// to read keepGpsLogDataForDays	keepVideoDataForDays
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