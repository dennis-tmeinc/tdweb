<?php
// backupstart.php - Start a backup
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
				$fpercent = fopen($backup_path.'/bkpercent', 'w');
				fwrite($fpercent, "-1");
				fclose($fpercent);
			
				$cmdline = $php_cmd . ' backupscript.php bk'. urlencode( $_REQUEST['backupname'] ) ;
				if( !empty( $_SESSION['clientid'] ) ) {
					$cmdline .= ' '.rawurlencode( $_SESSION['clientid'] );
				}
				pclose(popen( $cmdline, "r"));
				$resp['res']=1;
			}
		}
	}
	echo json_encode( $resp );

?>