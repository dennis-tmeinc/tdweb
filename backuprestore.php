<?php
// backuprestore.php - Restore a backup
// Requests:
//      backupname: Backup name given by user
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2014-05-05
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
			if( empty( $backup_path ) ) {
				$backup_path=sys_get_temp_dir();
			}
			$progressfile = tempnam ( $backup_path, "per" ) ;
			$fpercent = fopen($progressfile, 'w');
			fwrite($fpercent, "-1");
			$resp['progressfile'] = $progressfile ;			
			$resp['res']=1;

			// flush out contents
			ob_clean();

			ob_start();
			echo json_encode($resp);
			header( "Content-Length: ". ob_get_length() );
			header( "Connection: close" );
			ob_end_flush();
			
			ob_flush();
			flush();
			ignore_user_abort( true );		
			
			require 'backuprestorefunction.php' ;
			$backupname = $backup_path."/bk".urlencode( $_REQUEST['backupname'] );
			dbrestore( $backupname, $conn, $fpercent ) ;
			fclose( $fpercent );
			return ;
		}
	}
	echo json_encode( $resp );

?>