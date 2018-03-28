<?php
// netpackfunc.php - local send/recv network packets
// By Dennis Chen @ TME	 - 2016-12-01
// Copyright 2016 Toronto MicroElectronics Inc.

function net_available( $s, $tous = 0 ) 
{
	if( $s ) {
		$reads = array( $s );
		$writes = NULL ;
		$exs = NULL ;
		return stream_select($reads, $writes, $exs, $tous/1000000, $tous%1000000 ) > 0 ;
	}
	return false ;
}

$net_sid = rand();

// send one packet and wait for ack
function net_sendpack( $s, $data )
{
	global $net_sid   ;
	if( $s ) {
		$plen = strlen($data) ;
		$packheader = pack('ii', $plen, $net_sid++ ) ;
		$hlen = strlen( $packheader );
		$w = fwrite( $s, $packheader );
		if( $w != $hlen ) {
			return false ;
		}
		$pos = 0 ;
		while( $pos < $plen ) {
			$w = fwrite( $s, substr($data,$pos) );
			if( $w === false ) {
				return false ;
			}
			$pos += $w ;
		}
		 // wait ack
		return ( fread( $s, $hlen ) == $packheader ) ;
	}
	return false ;
}

// read packet ( send ack )
function net_readpack( $s )
{
	$data = '';
	if( $s ) {
		$hlen = strlen( pack('ii', 0, 0) );
		$packheader = fread( $s, $hlen );
		if( strlen($packheader)==$hlen ) {
			$packlen = unpack( 'iz', $packheader)['z'] ;
			while( $packlen > 0 ) {
				$r = fread( $s, $packlen ) ;
				$rlen = strlen($r);
				if( $r===false || $rlen==0 ) {
					return '';
				}
				$data .= $r ;
				$packlen -= $rlen ;
			}
			// send ack
			fwrite( $s, $packheader ) ;
		}
	}
	return $data ;
}

?>