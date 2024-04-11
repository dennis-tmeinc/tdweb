<?php
// backupdel.php - Delete backup file
// Requests:
//      backupname: Backup name given by user
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] != 'admin' ) {
			$resp['errormsg']="Not allowed!" ;
		}
		else if( empty($_REQUEST['backupname'] ) ) {
			$resp['errormsg']="No backup name specified!" ;
		}
		else {
			if( !empty( $backup_path ) ) {
				$backupname=urlencode( $_REQUEST['backupname'] );
				foreach (glob( $backup_path. DIRECTORY_SEPARATOR . "bk$backupname.sql*") as $filename) {
					unlink( $filename );
				}
				$resp['res']=1;
			}
			else {
				$resp['errormsg']="No backup path!" ;
			}
		}
	}
	echo json_encode( $resp );

?>