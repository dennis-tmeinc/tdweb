<?php
// companylist.php - list company data
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
	
	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" && !empty($_REQUEST['id']) ) {
		$cfgfile = "client/".$_REQUEST['id']."/config.php" ;
		$company_root = get_var( $cfgfile, "\$company_root" ) ;
		if( $company_root ) {
			$resp['webset'] = array();

			@$xmlcontents = vfile_get_contents( $company_root."/companyinfo.xml" ) ;
			if( $xmlcontents ) {
				@$companyinfo = new SimpleXMLElement( $xmlcontents ) ;
				if( !empty( $companyinfo ) ) {
					foreach( $companyinfo as $key => $value ) {
						$resp['webset'][$key] = (string) $value ;
					}
				}
			}
			
			$resp['webset']['CompanyId'] = $_REQUEST['id'] ;
			$resp['webset']['RootFolder'] = $company_root ;
			$resp['webset']['Database'] =  get_var( $cfgfile, "\$smart_database" );
			$resp['webset']['TimeZone'] =  get_var( $cfgfile, "\$timezone" );
			$resp['webset']['MapArea'] =  get_var( $cfgfile, "\$map_area" );
			$resp['webset']['SessionTimeout'] =  get_var( $cfgfile, "\$session_timeout" );
			$resp['errormsg']='success' ;					
			$resp['res'] = 1 ;
		}		
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
	echo json_encode($resp);
?>