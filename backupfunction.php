<?php
// backupfunction.php - database backup
// Parameter:
//      $backupname : archive file path
//		$conn    : database connection
//      $fpercent : progress file (already opened). if False to use SSE
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

function dbbackup( $backupname, $conn, $fpercent )
{
    $abort=false;

    dbprogress($fpercent, 0);

    // No table options
    // $sql = "set SESSION sql_mode = CONCAT_WS(',','NO_TABLE_OPTIONS', @@sql_mode)" ;
    // $conn->query($sql);

    $tables = array();
    $sql = "SHOW TABLE STATUS";
    $progresstotal = 1 ;
    if( $result = $conn->query($sql) ) {
        while( $row = $result->fetch_array() ) {
            if( $row['Engine'] == 'MEMORY' ) {	// don't backup memory tables
                continue ;
            }
            if( strncasecmp( $row['Name'], '_tmp_', 5)==0 ) {	// don't backup temporary tables
                continue ;
            }
            $tables[]=$row['Name'];
            $progresstotal += 5 + $row['Rows'] ;            // each table for 5 point, each row for 1 point 
        }
        $result->free();
    }

    $ext=".sql.bz2";
    $tmpext=".bz2";
    $tmpname="$backupname$tmpext";
    @$fout = fopen("compress.bzip2://$tmpname", "w");
    if( empty($fout) ) {
        $ext=".sql.gz";
        $tmpext=".gz";
        $tmpname="$backupname$tmpext";
        @$fout = fopen("compress.zlib://$tmpname", "w");
        if( empty($fout) ) {
            $ext=".sql";
            $tmpext=".tmp";
            $tmpname="$backupname$tmpext";
            @$fout = fopen($tmpname, "w");
        }
    }
    
    if( empty($fout) ) {
        dbprogress($fpercent, -1, "Can't create backup file!");
        return;
    }

    fputs( $fout, "--  TOUCHDOWNCENTER       --\n" );
    fputs( $fout, "--  Data Backup\n");
    fputs( $fout, "--  @PGT:$progresstotal \n");
    fputs( $fout, "--  \n");

    $now = new DateTime() ;
    fputs( $fout, "-- @TME ".$now->format("Y-m-d H:i:s")."\n\n");

    $ptime = time();
    $progress = 0 ;
    $percent_s = 0 ;

    foreach( $tables as $table ) {
        // recreate table ?
        /*
        $createtable = NULL ;
        $sql = "SHOW CREATE TABLE `$table`;" ;
        if( $result = $conn->query($sql) ) {
            if( $row = $result->fetch_array() ) {
                // drop/recreate table ?
                $createtable = $row[1];
                fputs( $fout, "DROP TABLE IF EXISTS `$table`;\n$createtable;\n" );
            }
            $result->free();
        }
        */

        // truncate table
        fputs( $fout, "TRUNCATE `$table`;\n");

        set_time_limit(60);
        $sql="SELECT * FROM `$table`";
        if( $result = $conn->query($sql, MYSQLI_USE_RESULT) ) {
            $skip_auto_inc = 0 ;
            $fields = $result->fetch_fields();
            if( empty($backup_auto_increment) && !empty($fields) ){
                if($fields[0]->flags&MYSQLI_AUTO_INCREMENT_FLAG)
                    $skip_auto_inc = 1 ;
            }

            $fieldnames=array();
            for( $col=$skip_auto_inc; $col<count($fields); $col++) {
                $fieldnames[]='`' . $fields[$col]->name . '`' ;
            }
            $insertHeader = "INSERT INTO `$table` (". implode(',', $fieldnames) . ") VALUES ";

            $line = '';            
            while( $row = $result->fetch_array(MYSQLI_NUM) ) {
                if (connection_aborted()) {
                    $abort = True;
                    break;
                }

                if( strlen($line) <= 0 ) {
                    $line = $insertHeader ;
                }
                else {
                    $line .= "," ;
                }

                $values = array() ;
                for($col=$skip_auto_inc ; $col<count($row); $col++) {
                    if( is_null($row[$col]) ) {
                        $values[] = "NULL" ;
                    }
                    else if( is_numeric($row[$col]) ) {
                        $values[] = $row[$col];
                    }
                    else {
                        // quote strings + sql escape
                        $values[] = "'". $conn->escape_string( $row[$col] )."'" ;
                    }
                }
                $line .= '(' . implode(',',$values) . ')' ;

                if( strlen($line) > 30*1024 ) {
                    fputs( $fout, $line );
                    $line='';
                    fputs( $fout, ";\n");
                    fflush( $fout );
                }

                // progress
                // one row count as 1
                $progress ++ ;
                $percent = floor( $progress * 100 / $progresstotal);
                if($percent>99)
                    $percent=99 ;
                $xtime = time();
                if( $percent > $percent_s || ($xtime-$ptime) >= 5 ) {       // or every 5 seconds?
                    $percent_s = $percent ;
                    $ptime = $xtime ;
                    dbprogress($fpercent, $percent_s, $table);
                    set_time_limit(60);
                    // update session time (back/restore would take very long)
                    session_save('xtime', $xtime);
                }
            }
            $result->free();
            $result = null;

            if( strlen($line) > 0 ) {
                fputs( $fout, $line );
                $line='';
                fputs( $fout, ";\n");
                fflush( $fout );
            }

            if ( $abort || connection_aborted()) {
                $abort = True;
                break;
            }
        }

        // one table complete, count 5
        $progress += 5 ;
        $percent = floor( $progress * 100 / $progresstotal);
        if($percent>99)
            $percent=99 ;
        if( $percent > $percent_s ) {
            $percent_s = $percent ;
            dbprogress($fpercent, $percent_s, $table);
        }
    }

    fclose( $fout );

    if($abort) {
        unlink( $tmpname );
    }
    else {
        // success
        // rename to final file name
        rename($tmpname, "$backupname$ext");
    }

    dbprogress($fpercent, 100);
}

?>