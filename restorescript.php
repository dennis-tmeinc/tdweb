<?php
// restorescript.php - restore database from .sql file
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-20
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'config.php' ;

	if( empty($argv[1]) ) {
		$backupname="archive";
	}
	else {
		$backupname=$argv[1];
	}
	
	if( !empty($argv[2]) ) {		// client id
		$clientcfg = 'client/'.rawurldecode($argv[2]).'/config.php' ;
		if( file_exists ( $clientcfg ) ) {
			require_once $clientcfg ;
		}
	}

	if( empty( $backup_path ) ) {
		$backup_path=sys_get_temp_dir();
	}
	
	$fpercent = fopen($backup_path.'/bkpercent', 'w');
	fwrite($fpercent, "0");
	fflush( $fpercent );

	$ext=".sql.bz2";
	$compress_rate = 10.6 ;		// assume bz2 get 10 times compressed
	@$fin = fopen("compress.bzip2://$backup_path/$backupname$ext", "r");
	if( empty($fin) ) {
		$ext=".sql.gz";
		$compress_rate = 6.6 ;	
		@$fin = fopen("compress.zlib://$backup_path/$backupname$ext", "r");
		if( empty($fin) ) {
			$ext=".sql";
			$compress_rate = 1 ;	
			@$fin = fopen("$backup_path/$backupname$ext", "r");
		}
	}

	if( empty($fin) ) {
		die;
	}
	
	function largefilesize( $path )
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
			$path = realpath($path) ;
			if (class_exists("COM")) {
				@$fsobj = new COM('Scripting.FileSystemObject');
				if( !empty( $fsobj ) ) {
					$f = $fsobj->GetFile($path);
					return $f->Size ;
				}
			}
			$path = escapeshellarg( $path );
			return trim(exec("for %F in ( $path ) do @echo %~zF"));
		}
		return filesize($path);
	}
	
	$fsize = largefilesize("$backup_path/$backupname$ext") ;
	$fsize *= $compress_rate ;

	$pgtotal = 0 ;
	$pgcount = 0 ;
	$percent = 0 ;
	$percent_s = 0 ;
	
	// MySQL connection
	$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
	
	$statement = '';
    while (($buffer = fgets($fin, 128*1024)) !== false) {
		$totalread += strlen($buffer);
		$tr = trim($buffer) ;
		if( substr($tr,0,2)=='--' || $tr=='' ) {		// comment
			if( substr( $tr,4,5) == "@PGT:" ) {		// custom progress size 
				$pgtotal = (real)substr($tr,9);
			}
			continue ;
		}
		else if( substr($tr,-1,1) == ';' ) {	// end of a statement
			$statement .= $buffer ;
			// execute statement
			$conn->query($statement);
			$statement='';

			// progress
			if( $pgtotal > 0 ) {
				$affected_rows = $conn->affected_rows;
				if( $affected_rows > 0 ) {
					$pgcount += $affected_rows ;
					$percent = floor( $pgcount * 100 / $pgtotal) ;
				}
			}
			else {
				$percent = floor( $totalread * 100 / $fsize) ;
			}
			
			if($percent>99)$percent=99 ;
			if( $percent > $percent_s ) {
				$percent_s = $percent ;
				fseek( $fpercent, 0 ); fwrite($fpercent, "".$percent_s); fflush($fpercent);
			}
		}
		else { 
			$statement .= $buffer ;
		}
    }
	
	fclose($fin);

	fseek( $fpercent, 0 ); 
	fwrite($fpercent, "100"); 
	fclose($fpercent);	
	
	$conn->close();
?>