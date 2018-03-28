<?php
// backuprestore.php - Restore a backup
// Requests:
//      backupname: Backup name given by user
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-19
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

			if( empty($php_bin) ) {
				if( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) {
					// my installed php path
					$php_bin = dirname( php_ini_loaded_file() )."\\php-win.exe";
				}
				else {
					$php_bin = "php" ;
				}
			}

			// windows system
			if( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) {
				$php_cmd = 'start /B '.$php_bin;
			}
			else {
				$php_cmd = $php_bin ;
			}
			
			if( !empty($php_cmd) ) {
				if( empty( $backup_path ) ) {
					$backup_path=sys_get_temp_dir();
				}
				$progressfile = tempnam ( $backup_path, "per" ) ;
				$fpercent = fopen($progressfile, 'w');
				fwrite($fpercent, "-1");
				fclose($fpercent);
				$resp['progressfile'] = $progressfile ;

				// restore script argument
				//      argv[1] : backup file path
				//      argv[2] : database server
				//      argv[3] : database user
				//      argv[4] : database password
				//      argv[5] : database name
				//      argv[6] : progress file
				$backupname = $backup_path."/bk".urlencode( $_REQUEST['backupname'] );
				$cmdline = "$php_cmd restorescript.php \"$backupname\" $smart_host $smart_user $smart_password $smart_database $progressfile" ;
				pclose(popen( $cmdline, "r"));
				$resp['res']=1;
			}
		}
	}
	echo json_encode( $resp );

?>