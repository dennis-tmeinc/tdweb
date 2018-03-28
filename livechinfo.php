<?php
// livechinfo.php - get live preview channel info
// Requests:
//      phone : phone id (for dvr)
// Return:
//      json object, array of enabled channel name
// By Dennis Chen @ TME	 - 2016-12-19
// Copyright 2016 Toronto MicroElectronics Inc.

$nodb=1;
require_once 'session.php' ; 
require_once 'webtunstream.php' ;

// send single data DVR req
function dvr_req( $stream, $code, $data = 0, $databuf = '' )
{
	$dsiz = strlen($databuf) ;
	$req = pack("V3", $code, $data, $dsiz ) ;
	if( fwrite( $stream, $req ) != strlen($req) )
		return false ;
	
	if( $dsiz > 0 ) {
		if( fwrite( $stream, $databuf ) != $dsiz ) {
			return false ;
		}
	}
	return true ;
}
	
function dvr_read( $stream, $len )
{
	$data = '' ;
	while( $len>0 ) {
		$d = fread( $stream, $len ) ;
		@ $dlen = strlen($d) ;
		if( $dlen>0 ) {
			$data .= $d ;
			$len -= $dlen ;
		}
		else {
			return '';
		}
	}
	return $data ;
}
	
// receive DVR ans, return ans code, and data and databuf
function dvr_ans( $stream, &$data, &$databuf )
{
	$ans = dvr_read( $stream, 12 );
	if( strlen($ans) != 12 ) return -1 ;
	$ans = unpack("Vcode/Vdata/Vsize", $ans ) ;
	
	$data = $ans['data'] ;
	$databuf = dvr_read( $stream, $ans['size'] ) ;
	return $ans['code'] ;
}

set_time_limit(30) ;

if( $logon && !empty($_REQUEST['phone']) ) {
	
	$phone = $_REQUEST['phone'] ;
	$stream = fopen("webtun://$phone:15114", "c") ;
	stream_set_timeout($stream, 15);

	if( $stream ) {

		//struct channel_info {
		//	int Enable ;
		//	int Resolution ;
		//	char CameraName[64] ;
		//} ;	
		// get dvr channel info
		// REQCHANNELINFO
		if( dvr_req( $stream, 4 ) ) {
			$ans = dvr_ans( $stream, $ansdata, $databuf ) ;
			if( $ans > 1 ) {		// ANSCHANNELDATA
				$resp['res'] = 1 ;
				$resp['channels'] = array();
				for( $ch=0 ; $ch < $ansdata; $ch++ ) {
					$chinfo = substr( $databuf, $ch * 72, 72 ) ;
					if( strlen($chinfo) == 72 ) {
						$en = unpack("V", $chinfo )[1] ;
						if( $en ) {
							$n = substr( $chinfo, 8 );
							$resp['channels'][] = array( 
								"camera" => $ch,
								"name" => strstr( $n, "\0", true) );
						}
					}
				}
			}
			else {
				// try use REQGETCHANNELSETUP
				$resp['channels'] = array();
				for( $ch = 0 ; $ch < 256 ; $ch ++ ) {
					if( dvr_req( $stream, 14, $ch ) ) {
						if( dvr_ans( $stream, $ansdata, $databuf ) > 1 ) {		// ANSCHANNELDATA
							if( strlen( $databuf ) > 4 ) {
								$en = unpack("V", $databuf )[1] ;
								if( $en ) {
									$n = substr( $databuf, 4, 64 ) ;
									$resp['channels'][] = array(
										"camera" => $ch ,
										"name" => strstr( $n, "\0", true )  );
								}
							}
						}
						else break;
					}
					else break ;
				}
				if( !empty( $resp['channels'] ) ) {
					$resp['res'] = 1 ;
				}
			}
		}
		else {
			$resp['errmsg'] = "No connection." ;
		}
		$stream = NULL ;
	}
	else {
		$resp['errmsg'] = "Can't open stream." ;
	}
}

header("Content-Type: application/json");
echo json_encode($resp);

?>