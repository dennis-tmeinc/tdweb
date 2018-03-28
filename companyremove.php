<?php
// companyremove.php - Remove company data
// Request:
//		id : company id
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2014-04-17
// Copyright 2014 Toronto MicroElectronics Inc.

    include_once 'session.php' ;
	include_once 'vfile.php' ;
	
	header("Content-Type: application/json");
	
	function get_var( $cfg, $key )
	{
		$f = fopen($cfg,"r");
		if( $f ) {
		while( ($line=fgets($f)) ) {
			$x = explode( '=', $line ) ;
			if( count($x) > 1 ) {
				$k = trim( $x[0] ) ;
				if( $k == $key ) {
					fclose( $f );
					return stripcslashes( trim(trim(rtrim( trim( $x[1] ), ";" )),"\"") );
				}
			}
		}
		fclose($f);
		}
		return false ;
	}
	
	function rm_emptydir( $dir ) {
	  $empty=true;
	  foreach( vfile_glob( "$dir\\*" ) as $subdir ) {
		 if (vfile_isdir( $subdir ) ){
			 if( !rm_emptydir( $subdir ) ) {
				 $empty = false ;
			 }
		 }
		 else {
			$empty=false;
		 }
	  }
	  if( $empty ) 
		  vfile_rmdir( $path ) ;
	  return $empty;
	}		
	
	if( $_SESSION['superadmin'] && $_SESSION['sa_verified'] == 1 && !empty($_REQUEST['id']) ) {
		
		unset($_SESSION["sa_verified"]);
		session_write();
		
		$output = array();
		$ret = 1;

		$cfgfile = "$client_dir/$_REQUEST[id]/config.php" ;

		$company_root = get_var( $cfgfile, "\$company_root" ) ;
		$database = get_var( $cfgfile, "\$smart_database" ) ;
		if( $company_root && $database && !empty( $td_clean ) ) {
			// script execution : <script> <company id> <company root directory> <database name>
			$p1 = escapeshellarg( $_REQUEST['id'] );
			$p2 = escapeshellarg( $company_root );
			$p3 = escapeshellarg( $database );
			
			$cmd = $td_clean." $p1 $p2 $p3" ;
			@vfile_exec($cmd, $output, $ret) ;
			
			@vfile_unlink( "$company_root/companyinfo.xml" ) ;
			foreach( vfile_glob( "$company_root/*" ) as $file ) {
				if( vfile_isdir( $file ) ) {
					vfile_rmdir( $file );
				}
				else {
					vfile_unlink( $file );
				}
			}
			@vfile_rmdir( $company_root );

			@unlink( $cfgfile ) ;
			@rmdir( "$client_dir/".$_REQUEST['id'] );
		}

		// may need to do more cleaning on company root directory and database
		$resp['errormsg']='success' ;
		$resp['res'] = 1 ;
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
	echo json_encode($resp);
?>