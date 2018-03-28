<?php
// backupscript.php - Do the real backup job
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-05
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'config.php' ;

	if( empty($argv[1]) ) {
		$backupname="archive";
	}
	else {
		$backupname=$argv[1];
	}
	
	if( empty( $backup_path ) ) {
		$backup_path=sys_get_temp_dir();
	}
	
	$fpercent = fopen($backup_path.'/bkpercent', 'w');
	fwrite($fpercent, "0");
	fflush( $fpercent );

	// MySQL connection
	$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );

	// No table options
	$sql = "set SESSION sql_mode = CONCAT_WS(',','NO_TABLE_OPTIONS', @@sql_mode)" ;
	$conn->query($sql);
	
	$tables = array();
	$sql = "SHOW TABLE STATUS ;";
	$progresstotal = 1 ;
	if( $result = $conn->query($sql) ) {
		while( $row = $result->fetch_array() ) {
			$tables[]=$row;
			$progresstotal += 5 + $row['Rows'] ;
		}
		$result->free();
	}

	$ext=".sql.bz2";
	@$fout = fopen("compress.bzip2://$backup_path/.$backupname$ext", "w");
	if( empty($fout) ) {
		$ext=".sql.gz";
		@$fout = fopen("compress.zlib://$backup_path/.$backupname$ext", "w");
		if( empty($fout) ) {
			$ext=".sql";
			@$fout = fopen("$backup_path/.$backupname$ext", "w");
		}
	}

	if( empty($fout) ) {
//		echo "Can't open archive file.";
		fseek( $fpercent, 0 );
		fwrite($fpercent, "-1");
		fclose($fpercent);
		die;
	}
	
	fputs( $fout, "--  TOUCHDOWNCENTER       --\n" );
	fputs( $fout, "--  Data Backup\n");
	fputs( $fout, "--  @PGT:$progresstotal \n");
	fputs( $fout, "--  \n");
	
	$sql = "select now();" ;
	$now = new DateTime() ;
	// use mysql time instead if possible
	if($result=$conn->query($sql)) {
		if( $row=$result->fetch_array() ) {
			$now = new DateTime( $row[0] );
		}
		$result->free();
	}
	
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
						fseek( $fpercent, 0 ); fwrite($fpercent, ''.$percent_s); fflush($fpercent);
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
			fseek( $fpercent, 0 ); fwrite($fpercent, ''.$percent_s); fflush($fpercent);
		}		
	}
	
	fclose( $fout );

	// rename to final file name
	rename("$backup_path/.$backupname$ext", "$backup_path/$backupname$ext");
	
	fseek( $fpercent, 0 ); 
	fwrite($fpercent, "100"); 
	fclose($fpercent);
	
	$conn->close();
	
?>