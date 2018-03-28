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
	
	if( $_SESSION['superadmin'] && !empty($_REQUEST['id']) ) {
		
		$output = array();
		$ret = 1;

		$cfgfile = "client/".$_REQUEST['id']."/config.php" ;

		$company_root = get_var( $cfgfile, "\$company_root" ) ;
		$database = get_var( $cfgfile, "\$smart_database" ) ;
		if( $company_root && $database && !empty( $td_clean ) ) {
			// script execution : <script> <company id> <company root directory> <database name>
			$cmd = $td_clean." $_REQUEST[id] \"$company_root\" $database" ;
			vfile_exec($cmd, $output, $ret) ;
			
			@vfile_unlink( $company_root."/companyinfo.xml" ) ;
			@vfile_rmdir( $company_root ) ;

			@unlink( $cfgfile ) ;
			@rmdir( "client/".$_REQUEST['id'] );
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