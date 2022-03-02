<?php
// restorescript.php - restore database from .sql file
// Parameter:
//      argv[1] : archive file path
//      argv[2] : database server
//      argv[3] : database user
//      argv[4] : database password
//      argv[5] : database name
//      argv[6] : progress file

// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2014-04-23
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'config.php' ;

	$fpercent = false ;
	if( !empty($argv[6]) ) {		// progress file
		$fpercent = fopen($argv[6], 'w');
	}
	
	if( $fpercent ) {
		fwrite($fpercent, "0");
		fflush( $fpercent );
	}
	
	if( empty($argv[1]) ) {
		$backupname="archive";
	}
	else {
		$backupname=$argv[1];
	}

	$ext=".sql.bz2";
	$compress_rate = 10.6 ;		// assume bz2 get 10 times compressed
	@$fin = fopen("compress.bzip2://$backupname$ext", "r");
	if( empty($fin) ) {
		$ext=".sql.gz";
		$compress_rate = 6.6 ;	
		@$fin = fopen("compress.zlib://$backupname$ext", "r");
		if( empty($fin) ) {
			$ext=".sql";
			$compress_rate = 1 ;	
			@$fin = fopen("$backupname$ext", "r");
		}
	}

	if( empty($fin) ) {
		if( $fpercent ) {
			fwrite($fpercent, "-1");
			fclose( $fpercent );
		}
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
	
	$fsize = largefilesize("$backupname$ext") ;
	$fsize *= $compress_rate ;

	$pgtotal = 0 ;
	$pgcount = 0 ;
	$percent = 0 ;
	$percent_s = 0 ;
	
	// MySQL connection
	$conn=new mysqli($argv[2], $argv[3], $argv[4], $argv[5] );
	
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
				if( $fpercent ) {
					fseek( $fpercent, 0 ); fwrite($fpercent, "".$percent_s); fflush($fpercent);
				}
			}
		}
		else { 
			$statement .= $buffer ;
		}
    }
	
	fclose($fin);
	
	if( $fpercent ) {
		fseek( $fpercent, 0 ); 
		fwrite($fpercent, "100"); 
		fclose($fpercent);	
	}

?>