<?php
// companylist.php - Get full company list
// Return:
//      JSON array
// By Dennis Chen @ TME	 - 2013-06-24
// Copyright 2013 Toronto MicroElectronics Inc.

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

	if( $_SESSION['superadmin'] && $_SESSION['superadmin'] == "--SuperAdmin--" ) {
		$resp=array();
		$resp['total'] = 1 ;
		$resp['page'] = 0 ;
		$resp['records'] = 0 ;
		$resp['rows'] = array();
		$count=0;
		foreach (glob("client/*") as $dirname) {
			if( is_dir( $dirname ) ) {
				$row = array();
				$row['id'] = basename($dirname) ;

				unset( $company_root );
				$company_root = get_var( $dirname."/config.php", "\$company_root" ) ;
				if( $company_root ) {
					$row['cell'] = array();
					$row['cell'][0]=basename($dirname) ;
					$row['cell'][1]='' ;
					$row['cell'][2]='' ;
					$row['cell'][3]='';
					
					unset($companyinfo);
					@$xmlcontents = file_get_contents( $company_root."/companyinfo.xml" ) ;
					if( $xmlcontents ) {
						@$companyinfo = new SimpleXMLElement( $xmlcontents ) ;
						if( !empty( $companyinfo ) ) {
							if( !empty($companyinfo->CompanyName) )
								$row['cell'][1]=(string)$companyinfo->CompanyName ;
							if( !empty($companyinfo->ContactName) )
								$row['cell'][2]=(string)$companyinfo->ContactName ;
							if( !empty($companyinfo->ContactEmail) )
								$row['cell'][3]=(string)$companyinfo->ContactEmail ; 
						}
						$resp['rows'][] = $row ;
					}
					
					//$resp['rows'][] = $row ;
				}
			}
			if( count($resp['rows']) >= $_REQUEST['rows'] ) break ;
		}
		$resp['records'] = count($resp['rows']);
		echo json_encode( $resp );
	}
	else {
		echo "{}" ;
	}
?>