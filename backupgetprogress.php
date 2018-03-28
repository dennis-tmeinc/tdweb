<?php
// backupgetprogress.php - Get backup/restore progress percentage
// Requests:
//      progressfile : progress file name
//      complete:  to delete percentage file
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2014-04-23
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	$resp=array();
	$resp['res']=0;
	if( $logon ) {
		if( empty($_REQUEST['progressfile'] ) ) {
			if( empty( $backup_path ) ) {
				$backup_path=sys_get_temp_dir();
			}
			$progressfile = $backup_path.'/bkpercent' ;
		}
		else {
			$progressfile = $_REQUEST['progressfile'] ;
		}
		
		$resp['percentage'] = '0';
		
		if( $fpercent = fopen($progressfile, 'r') ) {
			$resp['percentage'] = fgets($fpercent, 5);
			fclose($fpercent);
			$resp['res']=1 ;
		}
		if( !empty($_REQUEST['complete']) && $_REQUEST['complete']==1 ) {
			$resp['percentage'] = '100'	;
			unlink($progressfile);
		}	
	}
	else {
		$resp['errormsg']="Session error!";
	}
	echo json_encode( $resp );

?>