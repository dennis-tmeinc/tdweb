<?php
// backupscript.php - Do the real backup job
// Parameter:
//      argv[1] : archive file path
//      argv[2] : database server
//      argv[3] : database user
//      argv[4] : database password
//      argv[5] : database name
//      argv[6] : progress file

// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-05
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'config.php' ;

	$fpercent = false ;
	
	echo $argv[1] ;
	echo "\r\n" ;
	
	if( !empty($argv[6]) ) {		// progress file
		$fpercent = fopen($argv[6], 'w');
	}
	
	if( $fpercent ) {
		fwrite($fpercent, "0");
		fflush( $fpercent );
	}

	// MySQL connection
	$conn=new mysqli($argv[2], $argv[3], $argv[4], $argv[5] );

	// No table options
	$sql = "set SESSION sql_mode = CONCAT_WS(',','NO_TABLE_OPTIONS', @@sql_mode)" ;
	$conn->query($sql);
	
	$tables = array();
	$sql = "SHOW TABLE STATUS ;";
	$progresstotal = 1 ;
	if( $result = $conn->query($sql) ) {
		while( $row = $result->fetch_array() ) {
			if( $row['Engine'] == 'MEMORY' ) {	// don't backup memory tables
				continue ;
			}
			if( strncasecmp( $row['Name'], '_tmp_', 5)==0 ) {	// don't backup temporary tables
				continue ;
			}
			$tables[]=$row;
			$progresstotal += 5 + $row['Rows'] ;
		}
		$result->free();
	}

	$backupname = $argv[1] ;
	$ext=".sql.bz2";
	$tmpext=".bz2";
	@$fout = fopen("compress.bzip2://$backupname$tmpext", "w");
	if( empty($fout) ) {
		$ext=".sql.gz";
		$tmpext=".gz";
		@$fout = fopen("compress.zlib://$backupname$tmpext", "w");
		if( empty($fout) ) {
			$ext=".sql";
			$tmpext=".tmp";
			@$fout = fopen("$backupname$tmpext", "w");
		}
	}

	if( empty($fout) ) {
//		echo "Can't open archive file.";
		if( $fpercent ) {
			fseek( $fpercent, 0 );
			fwrite($fpercent, "-1");
			fclose($fpercent);
		}
		die;
	}
	
	fputs( $fout, "--  TOUCHDOWNCENTER       --\n" );
	fputs( $fout, "--  Data Backup\n");
	fputs( $fout, "--  @PGT:$progresstotal \n");
	fputs( $fout, "--  \n");
	
	$now = new DateTime() ;
	fputs( $fout, "-- @TME ".$now->format("Y-m-d H:i:s")."\n\n");

	$progress = 0 ;
	$percent_s = 0 ;
	
	foreach( $tables as $table ) {
		$createtable = NULL ;
		$sql = "SHOW CREATE TABLE `$table[Name]`;" ;
		if( $result = $conn->query($sql) ) {
			if( $row = $result->fetch_array() ) {
				$createtable=$row[1] ;
			}
			$result->free();
		}
		if( !empty($createtable) ) {
			// to drop table
			fputs( $fout, "DROP TABLE IF EXISTS `$table[Name]`;\n".$createtable.";\n" );
			
			$sql="SELECT * FROM `$table[Name]`;";
			if( $result = $conn->query($sql,MYSQLI_USE_RESULT) ) {
				$skip_auto_inc = false ;
				$fields = $result->fetch_fields();
				if( empty($backup_auto_increment) && !empty($fields) ){
					if($fields[0]->flags&MYSQLI_AUTO_INCREMENT_FLAG)
						$skip_auto_inc = true ;
				}
			
				$length=0;
				while( $row = $result->fetch_array(MYSQLI_NUM) ) {
					$line = '';
					if( empty($length) ) {
						if( $skip_auto_inc ) {
							$line .= "INSERT INTO `$table[Name]` (" ;
							$fieldnames=array();
							for( $col=1; $col<count($fields); $col++) {
								$fieldnames[]='`' . $fields[$col]->name . '`' ;
							}
							$line .= implode(',', $fieldnames) ;
							$line .= ") VALUES " ;
						}
						else {
							$line .= "INSERT INTO `$table[Name]` VALUES " ;
						}
					}
					else {
						$line .= "," ;
					}
					
					$values = array() ;
					if( $skip_auto_inc ) {
						$col=1;
					}
					else {
						$col=0;
					}
					for( ; $col<count($row); $col++) {
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

					$length += strlen($line);
					if( $length > 102400 ) {
						$line .= ";\n" ;
						$length = 0;
					}
					fputs( $fout, $line );

					// progress
					// one row count as 1
					$progress ++ ;
					$percent = floor(($progress/$progresstotal)*100);
					if($percent>99)$percent=99 ;
					if( $percent > $percent_s ) {
						$percent_s = $percent ;
						if( $fpercent ) {
							fseek( $fpercent, 0 ); fwrite($fpercent, ''.$percent_s); fflush($fpercent);
						}
					}
				}
				if( $length > 0 ) {
					fputs($fout,";\n");
				}
				$result->free();
			}			
		}
		
		// one table complete, count 5
		$progress += 5 ;
		$percent = floor(($progress/$progresstotal)*100);
		if($percent>99)$percent=99 ;
		if( $percent > $percent_s ) {
			$percent_s = $percent ;
			if( $fpercent ) {
				fseek( $fpercent, 0 ); fwrite($fpercent, ''.$percent_s); fflush($fpercent);
			}
		}		
	}
	
	fclose( $fout );

	// rename to final file name
	rename("$backupname$tmpext", "$backupname$ext");
	
	if( $fpercent ) {
		fseek( $fpercent, 0 ); 
		fwrite($fpercent, "100"); 
		fclose($fpercent);
	}
	
?>