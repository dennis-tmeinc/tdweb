<?php
// fread.php - read file over http
// Request:
//      c :  command, r=read, rl=readline(gets), s=size, e=exists, d="dir exists"
//      n :  filename, (local file name of the server
//      o :  offset 
//      l :  length
// Return:
//      binary for file data, json stucture for for command size/exists
// By Dennis Chen @ TME	 - 2013-12-23
// Copyright 2013 Toronto MicroElectronics Inc.
//

$resp = array();
$resp['res'] = 0 ;

switch ( $_REQUEST['c'] ) {
    case 's':
		@$fs = filesize( $_REQUEST['n'] );
		$resp['size'] = $fs ;
		$resp['res'] = 1 ;
		break;
		
    case 'e':
		if( file_exists( $_REQUEST['n'] ) ) {
			$resp['res'] = 1 ;
		}
		break;
		
    case 'd':
		if( is_dir( $_REQUEST['n'] ) ) {
			$resp['res'] = 1 ;
		}
		break;

	case 'rl':
		$f = fopen( $_REQUEST['n'], 'rb' ) ;
		if( $f ) {
			if( !empty( $_REQUEST['o'] ) ) {
				fseek( $f, $_REQUEST['o'] );
			}
			if( empty( $_REQUEST['l'] ) ) {
				$len = 8192 ;
			}
			else {
				$len = $_REQUEST['l'];
			}
			$line = fgets( $f, $len ) ;
			if( $line === FALSE ) {
				$resp['errormsg']="Read Failed or EOF!" ;
			}
			else {
				$resp['line'] = $line;
				$resp['offset'] = ftell( $f );
			}
			fclose( $f );
		}
		else {
			$resp['errormsg'] = "Can't open this file." ;
		}
		
	default :
		header("Content-Type: application/octet-stream");	
		$f = fopen( $_REQUEST['n'], 'rb' ) ;
		if( $f ) {
			if( !empty( $_REQUEST['o'] ) ) {
				fseek( $f, $_REQUEST['o'] );
			}
			if( empty( $_REQUEST['l'] ) ) {
				$len = 8192 ;
			}
			else {
				$len = $_REQUEST['l'];
			}
			$da = fread( $f, $len );
			$t = ftell( $f ) ;
			header("x-r-offset: ".$t);
			if( strlen($da)>0 ) {
				echo $da ;
			}
			fclose( $f );
		}
		
		return ;
}

header("Content-Type: application/json");
echo json_encode($resp);

?>