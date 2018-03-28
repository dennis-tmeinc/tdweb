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
 
	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" && !empty($_REQUEST['id']) ) {
		$cfgfile = "$client_dir/$_REQUEST[id]/config.php" ;
		include $cfgfile ;
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
			$resp['webset']['CompanyRoot'] = $company_root ; 
			$resp['webset']['Database'] =  $smart_database ;
			$resp['webset']['TimeZone'] =  $timezone ;
			$resp['webset']['MapArea'] =  $map_area ;
			$resp['webset']['SessionTimeout'] =  $session_timeout ;
			$resp['webset']['EnableVideos'] =  $enable_videos ;
			$resp['webset']['EnableLiveTrack'] = $enable_livetrack ;
			$resp['webset']['EnableDriveBy'] =  $support_driveby ;
				
			$resp['errormsg']='success' ;					
			$resp['res'] = 1 ;
		}		
	}
	else {
		$resp['erromsg']="Not allowed!";
	}
	echo json_encode($resp);
?>