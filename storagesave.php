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
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin (power user) only
			// MySQL connection
			$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

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
					
					// double the last '\'
					if( substr( $value, -1 ) == "\\" ) {
						$value .= "\\" ;
					}
					
					if( empty($stroage_regkey) ) {
						ex("reg query HKLM\\SOFTWARE\\Wow6432Node\\tme\\touchdown", $result, $ret);
						if( $ret == 0 ) {
							// 64bit OS
							$stroage_regkey = "HKLM\\SOFTWARE\\Wow6432Node\\tme\\touchdown" ;
						}
						else {
							// 32bit OS
							$stroage_regkey = "HKLM\\SOFTWARE\\tme\\touchdown" ;
						}
					}
					
					ex( "reg ADD $stroage_regkey /v $key /f /d " . escapeshellarg( $value ));
					
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