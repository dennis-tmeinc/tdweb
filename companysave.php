<?php
// companysave.php - save company data
// Request:
//		id : company id
//      form data
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2017-05-03
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
					return stripslashes( trim(trim(rtrim( trim( $x[1] ), ";" )),"\"") );
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
			if( is_string( $value ) ) {
				$value = trim($value) ;
				if( strlen($value) == 0 ) {
					$xline="";
				}
				else {
					$xline = $key." = \"".addslashes($value)."\";" ;
				}
			}
			else {
				$xline = "$key=$value ;" ;
			}

			$lines = array();
			while( ($line=fgets($f)) ) {
				$line = trim( $line );
				if( substr( $line, 0, 1 )=='$' ) {
					$x = explode( '=', $line ) ;
					if( count($x) > 1 ) {
						$k = trim( $x[0] ) ;
						if( $k == $key ) {
							if( !empty($xline) ) {
								$lines[] = $xline ;
								$xline = "" ;
							}
						}
						else {
							$lines[] = $line ;
						}
					}
				}
			}
			if( !empty($xline) ) {
				$lines[] = $xline ;
			}

			fseek( $f, 0, SEEK_SET );
			ftruncate( $f, 0 ) ;
			fputs( $f, "<?php\n" );
			foreach( $lines as $line ) {
				fputs( $f, $line."\n" );
			}
			fputs( $f, "?>" );
			fclose( $f );
		}
		return true ;
	}

	if( empty($_REQUEST['CompanyId']) ) {
		$resp['errormsg'] = "Company ID can not be empty!" ;
		goto done ;
	}
	else 

	if( empty($_REQUEST['Database']) ) {
		$resp['errormsg'] = "Database name can not be empty!" ;
		goto done ;
	}

	if( empty($_REQUEST['CompanyName']) ) {
		$resp['errormsg'] = "Company name can not be empty!" ;
		goto done ;
	}
	
	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" && !empty($_REQUEST['CompanyId']) ) {
		$cfgfile = "$client_dir/".$_REQUEST['CompanyId']."/config.php" ;
		if( file_exists($cfgfile) ) {
			include $cfgfile ;
		}
		if( !empty($_REQUEST['NewCompany']) ) {
			if( file_exists($cfgfile) ) {
				$resp['errormsg'] = "Company ID already exists" ;
				goto done ;
			}

			// check global client id, url example: "https://dvp.my247now.com/dvp/ivudb.php?clientid=abc"
			$query = array(
                "clientid"=> $_REQUEST['CompanyId']
            );
            $ivudb = file_get_contents( "https://dvp.my247now.com/dvp/ivudb.php?".http_build_query($query));
			if( $ivudb ) {
				$jdb = json_decode( $ivudb, true);
				if( !empty($jdb['res']) && !empty($jdb['rows']) ){
					$resp['errormsg'] = "This company id has been used, please enter a new company id!" ;
					goto done ;
				} 
			}

			// to create a new company
			$company_root = $_REQUEST['CompanyRootDir'] ;

			// create client directory
			@mkdir(  "$client_dir/".$_REQUEST['CompanyId']  );
			
			// create default config.php
			$cfile = fopen( $cfgfile, "w" );
			fclose( $cfile );

			set_var( $cfgfile, "\$company_root", $company_root );
			set_var( $cfgfile, "\$smart_database", $_REQUEST['Database'] );

			// create root folder
			vfile_mkdir( $company_root );

		}

		// add video relay server per client. (2022-12-01)
		if( isset($_REQUEST['VideoRelay'] ) ){
			set_var( $cfgfile, "\$liveplay_host", $_REQUEST['VideoRelay'] );		
		}

		set_var( $cfgfile, "\$timezone", $_REQUEST['TimeZone'] );
		set_var( $cfgfile, "\$map_area", $_REQUEST['MapArea'] );
		$SessionTimeout = (int)$session_timeout ;
		if( !empty($_REQUEST['SessionTimeout']) ) {
			$SessionTimeout = (int)$_REQUEST['SessionTimeout'] ;
			if( $SessionTimeout<300 || $SessionTimeout>86400 ) {
				$SessionTimeout = (int)$session_timeout ;
			}
		}
		set_var( $cfgfile, "\$session_timeout", (int)$SessionTimeout );
		set_var( $cfgfile, "\$enable_videos", (int)!empty($_REQUEST['EnableVideos']) );
		set_var( $cfgfile, "\$enable_livetrack", (int)!empty($_REQUEST['EnableLiveTrack']) );
		set_var( $cfgfile, "\$support_driveby", (int)!empty($_REQUEST['EnableDriveBy']) );
		
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
		
		if( empty($_REQUEST['NewCompany']) ) {
			// edit, save the companyinfo
			if( vfile_put_contents( $company_root."/companyinfo.xml", $companyinfo->asXML() ) ) {
				$resp['res'] = 1 ;
			}
			else {
				$resp['errormsg']='Saving configure file failed!' ;
			}
		}
		else {
			if( vfile_put_contents( $company_root."/companyinfo.xml", $companyinfo->asXML() ) ) {
				// may need to do more cleaning on company root directory and database

				$output = array();
				$ret = 1;
					
				// create new company instance
				if( !empty( $td_new ) ) {
					//@chdir( $company_root );
					// script execution : <script> <company id> <company root directory> <database name>
					$p1 = escapeshellarg( $_REQUEST['CompanyId'] );
					$p2 = escapeshellarg( $company_root );
					$p3 = escapeshellarg( $_REQUEST['Database'] );
					$cmd = $td_new." $p1 $p2 $p3" ;
					vfile_exec($cmd, $output, $ret) ;
				}
				
				if( $ret != 0 ) {
					$resp['errormsg']='Creating database failed' ;
					$resp['res'] = 0 ;
					unlink($cfgfile);
				}
				else {
					$resp['errormsg']='success' ;
					$resp['res'] = 1 ;
				}
			}
			else {
				$resp['errormsg']='Failed to create configuration file.' ;
			}
		}
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
done:	
	echo json_encode($resp);
?>