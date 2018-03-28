<?php
// companysave.php - save company data
// Request:
//		id : company id
//      form data
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2014-04-17
// Copyright 2014 Toronto MicroElectronics Inc.
	
    include_once 'session.php' ;
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
	
	function set_var( $cfg, $key, $value)
	{
		$f = fopen($cfg,"r+");
		if( $f ) {
			$lines = array();
			while( ($line=fgets($f)) ) {
				$lines[] = $line ;
			}
			
			$xlines = array();
			foreach( $lines as $line ) {
				$x = explode( '=', $line ) ;
				$xset = false ;
				if( count($x) > 1 ) {
					$k = trim( $x[0] ) ;
					if( $k == $key ) {
						$xset = true ;
						$xlines[] = $key."=\"".addslashes($value)."\";\n" ;
					}
				}	
				if( !$xset ) {
					$xlines[] = $line ;
				}
			}

			fseek( $f, 0, SEEK_SET );
			ftruncate( $f, 0 ) ;
			foreach( $xlines as $line ) {
				fputs( $f, $line );
			}
			fclose( $f );
		}
		return true ;
	}
	
	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" && !empty($_REQUEST['CompanyId']) ) {
	
		$cfgfile = "client/".$_REQUEST['CompanyId']."/config.php" ;
		$company_root = get_var( $cfgfile, "\$company_root" ) ;
					
		// mkdir company root folder
		if( !empty($_REQUEST['NewCompany']) ) {		
			if( !empty( $company_root ) ) {
				$resp['errormsg'] = "Company ID already exists" ;
				goto done ;
			}

			// create client directory
			@mkdir(  "client/".$_REQUEST['CompanyId']  );
			
			// create default config.php
			$cfile = fopen( $cfgfile, "w" );
			$defconf = file_get_contents( "companydef.conf" );
			fwrite( $cfile, $defconf );
			fclose( $cfile );

			$company_root = $_REQUEST['RootFolder']  ;
			set_var( $cfgfile, "\$company_root", $company_root );
			set_var( $cfgfile, "\$smart_database", $_REQUEST['Database'] );		

			// mkdir company root folder
			@mkdir(  $company_root  );
		}

		set_var( $cfgfile, "\$timezone", $_REQUEST['TimeZone'] );
		set_var( $cfgfile, "\$map_area", $_REQUEST['MapArea'] );
		set_var( $cfgfile, "\$session_timeout", $_REQUEST['SessionTimeout'] );
		
		$companyinfo = new SimpleXMLElement( "<companyinfo></companyinfo>" );

		$companyinfo->CompanyName = $_REQUEST['CompanyName'] ;
		
		$companyinfo->CompanyId = $_REQUEST['CompanyId'] ;
		$companyinfo->Database = $_REQUEST['Database'] ;
		$companyinfo->Address = $_REQUEST['Address'] ;
		$companyinfo->City = $_REQUEST['City'] ;
		$companyinfo->State = $_REQUEST['State'] ;
		$companyinfo->Country = $_REQUEST['Country'] ;
		$companyinfo->ZipCode = $_REQUEST['ZipCode'] ;
		$companyinfo->Tel = $_REQUEST['Tel'] ;
		$companyinfo->Fax = $_REQUEST['Fax'] ;
		$companyinfo->ContactName = $_REQUEST['ContactName'] ;
		$companyinfo->ContactEmail = $_REQUEST['ContactEmail'] ;
		
		if( file_put_contents( $company_root."/companyinfo.xml", $companyinfo->asXML() ) ) {
			// may need to do more cleaning on company root directory and database
			
			if( !empty($_REQUEST['NewCompany']) ) {
				// create new company instance
				if( !empty( $td_new ) && is_executable( $td_new ) ) {
					// script execution : <script> <company id> <company root directory> <database name>
					$cmd = $td_new." $_REQUEST[CompanyId] \"$company_root\" $_REQUEST[Database]" ;
					@chdir( $company_root );
					exec( $cmd );
				}
			}
			
			$resp['errormsg']='success' ;
			$resp['res'] = 1 ;
		}
		else {
			$resp['errormsg']='Failed to create configuration file.' ;
		}
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
done:	
	echo json_encode($resp);
?>