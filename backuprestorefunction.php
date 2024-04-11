<?php
// backuprestorefunction.php - restore database from .sql file
// Parameter:
//      $backupname : archive file path
//		$conn    : database connection
//      $fpercent : progress file (already opened)
// Return:
//      none
// By Dennis Chen @ TME	 - 2023-01-25
// Copyright 2023 Toronto MicroElectronics Inc.

function dbprogress($fpercent, $prog, $msg = "")
{
    if( $fpercent ) {
        fseek( $fpercent, 0 ); fwrite($fpercent, strval($prog)); fflush($fpercent);
    }
    else {
        $prog = [
            "event" => "progress",
            "progress" => $prog,
            "message" => $msg
        ];
        echo 'data: '. json_encode( $prog ) . "\n\n";
        ob_flush(); flush();
    }
}

function dbrestore( $backupname, $conn, $fpercent ) 
{
	dbprogress($fpercent, 0);

	$ext=".sql.bz2";
	$compress_rate = 10.2 ;		// assumed bz2 compression rate
	@$fin = fopen("compress.bzip2://$backupname$ext", "r");
	if( empty($fin) ) {
		$ext=".sql.gz";
		$compress_rate = 6.6 ;	// assumed gz compression rate
		@$fin = fopen("compress.zlib://$backupname$ext", "r");
		if( empty($fin) ) {
			$ext=".sql";
			$compress_rate = 1 ;	// plain rate
			@$fin = fopen("$backupname$ext", "r");
		}
	}

	if( empty($fin) ) {
		dbprogress($fpercent, -1, "Backup file error!");
		return;
	}
	
	$fsize = filesize("$backupname$ext") ;
	$fsize *= $compress_rate ;

	$percent = 0 ;
	$percent_s = 0 ;
	$ptime = time();
	
	$totalread = 0;
	$statement = '';
    while (($buffer = fgets($fin, 256*1024)) !== false) {
		set_time_limit(30);
		$totalread += strlen($buffer);
		$buffer = trim($buffer) ;
		if( substr($buffer,0,2)=='--' || $buffer=='' ) {		// comment or emptyline
			continue ;
		}
		else if( substr($buffer,-1,1) == ';' ) {	// end of a statement
			$statement .= $buffer ;
			$error = false;
			// execute statement
			try
			{
				$result = $conn->query($statement);
			}
			catch (mysqli_sql_exception $t)
			{
				$error = $t->getMessage();
			}
			catch (Throwable $t)
			{
				$error = $t->getMessage();
			}

			if( $error && !$fpercent ) {
				$prog = [
					"event" => "error",
					"message" => $error
				];
				echo 'data: '. json_encode( $prog ) . "\n\n";
				ob_flush(); flush();
			}

			$statement='';
		}
		else { 
			$statement .= $buffer ;
		}

		// progress
		$percent = floor( $totalread * 100.0 / $fsize) ;
		if($percent>99) {
			$percent=99 ;
		}
		$xtime = time();
		if( $percent > $percent_s || ($xtime-$ptime) >= 5 ) {
			$ptime = $xtime ;
			$percent_s = $percent ;
			dbprogress($fpercent, $percent_s );
            // update session time (back/restore would take very long)
            session_save('xtime', $xtime);			
		}
    }
	
	fclose($fin);
	
	dbprogress($fpercent, 100);

}
	
?>