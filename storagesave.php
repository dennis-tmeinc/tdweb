<?php
// storagesave.php - save storage settings
//      storage settings are save on Windows registry HKLM\Software\tme\touchdown
// Requests:
//      storage settings
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
		
			// MySQL connection
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

			// secaped sql values
			$esc_req=array();		
			foreach( $_REQUEST as $key => $value ){
				if( substr($key,0,4)=='keep' ) {
					// keepGpsLogDataForDays	keepVideoDataForDays
					$esc_req[$key]=$conn->escape_string($value);
				}
				else {	// others , save to registry

					$result=array();
					$ret=-1 ;
					exec("reg query HKLM\\Software\\tme\\touchdown",$result,$ret);
					if( $ret == 0 ) {
						exec("reg ADD HKLM\\Software\\tme\\touchdown /v $key /d \"$value\" /f");
					}
					else {
						// try 64bit OS?
						exec("reg ADD HKLM\\SOFTWARE\\Wow6432Node\\tme\\touchdown /v $key /d \"$value\" /f");
					}
					
				}
			}
			$sql="UPDATE tdconfig SET keepGpsLogDataForDays = $esc_req[keepGpsLogDataForDays], keepVideoDataForDays = $esc_req[keepVideoDataForDays] ;" ;
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['res']=0;
				$resp['errormsg']="SQL error: ".$conn->error ;
			}	
		}
		else {
			$resp['res']=0;
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>