<?php
// inifile.php - ini file reader / saver
// Requests:
//             
// Return:
//
// By Dennis Chen @ TME	 - 2021-03-22
// Copyright 2013 Toronto MicroElectronics Inc.
// 
// Notes:
//    builtin ini parser is boken on DVR ini file, so I make one for it

class inifile {

	public  $iniArray = array() ;
	
   	public function load($inistr) {

	   $m = explode("\n",$inistr);
	   $this->iniArray = array();

	   $curSec='';
	   $this->iniArray[$curSec] = array();

	   for( $i=0; $i<count($m); $i++ ) {
		   $l = trim($m[$i]);
		   if( strlen($l)<1 || $l[0] == '#' || $l[0] == ';' ) {	// comments
			  $this->iniArray[$curSec]['___l'.$i] = rtrim($m[$i]) ;
		   }
		   else if( $l[0] == '[' ) {			// new sec
			   $curSec = trim( strstr(substr($l, 1), "]", true ) ) ;
				  if( !isset($this->iniArray[$curSec]))
					  $this->iniArray[$curSec] = array();
		   }
		   else {
			   $ex = explode( "=", $l, 2 );
			   if( count($ex) > 1 ) {
				 $k = trim($ex[0]);
				 $v = trim($ex[1]);
				 $this->iniArray[$curSec][$k] = $v ;
			   }
			   else {
				 // unknonw key?
				 $this->iniArray[$curSec]['___l'.$i] = rtrim($m[$i]) ;
			   }
			}
	   }

	}
   
    public function str() {
        $m = array();
		foreach( $this->iniArray as $section => $kv ) {
			if( !empty($section) ) {
				$m[] = "[".$section."]" ;
			}
			foreach( $kv as $k => $v){
				if( str_starts_with($k, "___l" )) {
					// comments line
					$m[] = $v ;
				}
				else {
					$m[] = $k .  "=" . $v ;
				}
			}
		}
		return implode("\n", $m);
	}

	public function list_sections() {
		return array_keys($this->iniArray);
	}

	public function list_keys( $section ) {
		$ks = array();
		foreach( array_keys( $this->iniArray[$section] ) as $k ) {
			if( str_starts_with($k, "___l" )) {
				$ks[] = $k ;
			}	
		}
		return $ks ;
	}

	public function getv( $section, $key ) {
		return $this->iniArray[$section][$key];
	}

	public function setv( $section, $key, $value ) {
		if( !isset( $this->iniArray[$section] ) )
			$this->iniArray[$section] = array();
		$this->iniArray[$section][$key] = $value ;
	}

}
// testing codes
/*
$dvrconf = new inifile();
$dvrconf->load( file_get_contents("c:/tmp/dvr.conf") );
echo $dvrconf->getv("camera5","stream_URL") ;
$keys=$dvrconf->list_keys("system");
$dvrconf->setv("camera5","stream_URL", "mess it up!") ;
echo $dvrconf->getv("camera5","stream_URL") . "\n" ;
echo $dvrconf->str();
*/
?>