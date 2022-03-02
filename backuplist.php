<?php
// backuplist.php - List backup names
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.


    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		$resp['backuplist']=array();
		if( empty( $backup_path ) ) {
			$backup_path=sys_get_temp_dir();
		}
		chdir($backup_path);
		foreach (glob("bk*.sql*") as $filename) {
			$cut = 0;
			if( substr($filename, -8, 8) == ".sql.bz2" ) {
				$cut = 8 ;
			}
			else if( substr($filename, -7, 7) == ".sql.gz" ) {
				$cut = 7 ;
			}
			else if( substr($filename, -4, 4) == ".sql" ) {
				$cut = 4 ;
			}
			else {
				continue ;
			}
			$resp['backuplist'][] = urldecode( substr($filename,2,-$cut) ) ;
		}
		$resp['res']=1;
	}
	echo json_encode( $resp );

?>