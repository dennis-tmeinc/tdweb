<?php
// vltagent.php - agent for vlt connection
// Requests:
//      none
// Return:
// By Dennis Chen @ TME	 - 2013-11-28
// Copyright 2013 Toronto MicroElectronics Inc.

require_once 'config.php'; 

// send xml (avlp) packet
function avl_send( $s, $cmd, $xml )
{
	$mss_msg = "MS" ;
	$mss_msg[2] = chr(1);
	$mss_msg[3] = chr($cmd);
	for( $i=4; $i<20; $i++) $mss_msg[$i] = chr(0);

	$xml = $xml->asXML();

	$crc = crc32( $xml );
	$mss_msg[20] = chr($crc & 0xff) ; $crc >>= 8 ;
	$mss_msg[21] = chr($crc & 0xff) ; $crc >>= 8 ;
	$mss_msg[22] = chr($crc & 0xff) ; $crc >>= 8 ;
	$mss_msg[23] = chr($crc & 0xff) ;

	$xmlsize = strlen( $xml ) ;
	$mss_msg[24] = chr($xmlsize & 0xff) ; $xmlsize >>= 8 ;
	$mss_msg[25] = chr($xmlsize & 0xff) ; $xmlsize >>= 8 ;
	$mss_msg[26] = chr($xmlsize & 0xff) ; $xmlsize >>= 8 ;
	$mss_msg[27] = chr($xmlsize & 0xff) ; 

	$crc = crc32( $mss_msg );
	$mss_msg[28] = chr($crc & 0xff) ; $crc >>= 8 ;
	$mss_msg[29] = chr($crc & 0xff) ; $crc >>= 8 ;
	$mss_msg[30] = chr($crc & 0xff) ; $crc >>= 8 ;
	$mss_msg[31] = chr($crc & 0xff) ; 

	fwrite( $s, $mss_msg . $xml );

}

// receive avlp packet
function avl_recv( $s, $timeout )
{
	$mss_msg = '' ;
	// receiving mss_msg header
	while( strlen($mss_msg)<32 ) {
		$read = array( $s );
		$write = array();
		$except = array();
		if( stream_select ( $read , $write , $except , $timeout )>0 ) {
			$rs = 32 - strlen($mss_msg) ;
			$in = fread($s, $rs);
			if( $in && strlen($in)>0 ) {
				$mss_msg .= $in ;
			}
		}
		else {
			return false ;	// time out
		}
	}
	// analyst mss_msg 
	if( substr( $mss_msg, 0, 2 ) != "MS" ) {
		return false ;		// wrong package
	}
	$xmlsize = ord( $mss_msg[27] ) & 0xff;
	$xmlsize <<= 8 ; $xmlsize |= ord( $mss_msg[26] ) & 0xff;
	$xmlsize <<= 8 ; $xmlsize |= ord( $mss_msg[25] ) & 0xff;
	$xmlsize <<= 8 ; $xmlsize |= ord( $mss_msg[24] ) & 0xff;
	
	// receiving avlp
	$avlp='';
	while( strlen($avlp)<$xmlsize ) {
		$read = array( $s );
		$write = array();
		$except = array();
		if( stream_select ( $read , $write , $except , $timeout )>0 ) {
			$rs = $xmlsize - strlen($avlp) ;
			$in = fread($s, $rs);
			if( $in && strlen($in)>0 ) {
				$avlp .= $in ;
			}
		}
		else {
			return false ;	// time out
		}
	}

	if( $xmlsize>12 ) {
		$avlp = new SimpleXMLElement( trim($avlp) );
	}
	else {
		$avlp = new SimpleXMLElement( '<avlp></avlp>' );
	}
	
	$avlp->command = ord( $mss_msg[3] );
	$avlp->ack_code = ord( $mss_msg[4] );
	$avlp->reason = ord( $mss_msg[5] );
	return $avlp ;
}

function avl_connect( $avlip )
{
	global $smart_server, $smart_user, $smart_password, $smart_database ;
	
	@$conn=new mysqli( $smart_server, $smart_user, $smart_password, $smart_database );
	$sql = "SELECT * FROM `tdconfig` " ;
	if( $result = $conn->query($sql) ) {
		if( $row=$result->fetch_array() ) {
			$tdid = $row['tdServerId'] ;
			$password = $row['avlPassword'] ;
		}
		$result->free();
	}
	$conn->close();
	
    $errno=0;$errstr='';
	$sock = stream_socket_client( "tcp://$avlip:40510", $errno, $errstr, 5);
	if( $sock ) {
		$avlp = new SimpleXMLElement( '<avlp></avlp>' );
		$avlp->tdid=$tdid ;
		avl_send( $sock, 24, $avlp ) ;
		$r = avl_recv( $sock, 5 );
		if( !$r || $r->ack_code == 1 ){
			fclose( $sock );
			return false ;
		}
		
		$avlp->digest = md5($r->challenge . $password );
		avl_send( $sock, 25, $avlp ) ;
		$r = avl_recv( $sock, 5 );
		if( !$r || $r->ack_code == 1 ){
			fclose( $sock );
			return false ;
		}
		
	}
	return $sock ;
}


function avl_getlist( $sock )
{
	global $smart_server, $smart_user, $smart_password, $smart_database ;
	
	@$conn=new mysqli( $smart_server, $smart_user, $smart_password, $smart_database );

	$avlp = new SimpleXMLElement( '<avlp></avlp>' );
	$avlp->dvrlist='';
	$sql = "SELECT DISTINCT `vehicle_phone` FROM `vehicle` WHERE `vehicle_phone` <> '' ;";
	if( $result = $conn->query($sql) ) {
		while( $row=$result->fetch_array() ) {
			$avlp->dvrlist->dvritem[] = $row['vehicle_phone'] ;
		}
		$result->free();
	}
	$conn->close();

	avl_send( $sock, 23, $avlp ) ;
	$r = avl_recv( $sock, 10 );
	
	if( $r && $r->ack_code != 1 ){
		return $r ;
	}
	else {
		return false ;
	}
}

function avl_getlocation( $sock )
{
	$avlp = new SimpleXMLElement( '<avlp></avlp>' );
	avl_send( $sock, 27, $avlp ) ;
	do {
		$r = avl_recv( $sock, 10 );
		if( $r ) {
			if( $r->command == 27 ){
				break;
			}
		}
	} while( $r ) ;
	return $r ;
}

$sock = avl_connect($avl);
if( $sock ) {
	$li = avl_getlist( $sock );
	if( $li  
	$loc = avl_getlocation( $sock );
	fclose($sock);
}

?>