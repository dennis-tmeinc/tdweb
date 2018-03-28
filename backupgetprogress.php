<?php
// backupgetprogress.php - Get backup/restore progress percentage
// Requests:
//      complete:  to delete percentage file
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-07
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	$resp=array();
	$resp['res']=0;
	if( $logon ) {
		if( empty( $backup_path ) ) {
			$backup_path=sys_get_temp_dir();
		}
		$resp['percentage'] = '0';
		if( $fpercent = fopen($backup_path.'/bkpercent', 'r') ) {
			$resp['percentage'] = fread($fpercent,3);
			fclose($fpercent);
			$resp['res']=1 ;
		}
		if( !empty($_REQUEST['complete']) && $_REQUEST['complete']==1 ) {
			$resp['percentage'] = '100'	;
			unlink($backup_path.'/bkpercent');
		}	
	}
	else {
		$resp['errormsg']="Session error!";
	}
	echo json_encode( $resp );

?>