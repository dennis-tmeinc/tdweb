<?php
// vfile.php - video file interface
// By Dennis Chen @ TME	 - 2014-01-03
// Copyright 2013 Toronto MicroElectronics Inc.
require_once 'config.php' ;

// debug string
function debug_out( $ostr )
{
	$fdeb = fopen( "d:\\debugout.txt", "a");
	if( $fdeb ) {
		fputs( $fdeb, $ostr."\r\n" );
		fclose( $fdeb );
	}
}

function vfile_remote()
{
	global $remote_fileserver ;
	if( empty( $remote_fileserver ) || $remote_fileserver == 'localhost' ) {
		return false ;
	}
	else {
		return $remote_fileserver ;
	}
}

function vfile_readhttp( $url )
{
	$opts = array( 'http'=>
		array(
			'method'=>"GET",
			'header'=>"Connection: close\r\n"
		)
	);
	$context = stream_context_create($opts);
	return file_get_contents( $url, false, $context );
}

function vfile_stat( $filename )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		$j = vfile_readhttp( $fileserver."?c=i&n=".rawurlencode($filename) ) ;
		@$st = json_decode( $j, true );
		if( !empty( $st['res'] ) ) {
			unset( $st['res'] );
			return $st ;
		}
	}
	else {
		// local file
		$st = stat( $filename ); 
		if( $st ) {
			$st['type'] = filetype( $filename ) ;
		}
		return $st ;
	}
	return false ;
}

function vfile_exists( $filename )
{
	$st = vfile_stat( $filename );
	return( !empty($st) );
}

function vfile_isfile( $filename )
{
	$st = vfile_stat( $filename );
	if( !empty( $st['type'] ) ) {
		return ( $st['type'] == "file" ) ;
	}
	return false ;
}

function vfile_isdir( $filename )
{
	$st = vfile_stat( $filename );
	if( !empty( $st['type'] ) ) {
		return ( $st['type'] == "dir" ) ;
	}
	return false ;
}

function vfile_size( $filename )
{
	$st = vfile_stat( $filename );
	if( $st ) {
		return $st['size'] ;
	}
	return 0 ;
}

function vfile_unlink( $filename )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		$j = vfile_readhttp( $fileserver."?c=d&n=".rawurlencode($filename) ) ;
		@$jd = json_decode( $j, true );
		return !empty( $jd['res'] ) ; 
	}
	else {
		// local file
		return unlink( $filename ) ;
	}
}

function vfile_rename( $filename, $nfilename )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		$j = vfile_readhttp( $fileserver."?c=n&n=".rawurlencode($filename)."&l=".rawurlencode($nfilename) ) ;
		@$jd = json_decode( $j, true );
		return !empty( $jd['res'] ) ;
	}
	else {
		// local file
		return rename( $filename, $nfilename ) ;
	}
}

// get realpath of the filename
function vfile_realpath( $filename )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		$j = vfile_readhttp( $fileserver."?c=i&n=".rawurlencode($filename) ) ;
		@$jd = json_decode( $j, true );
		if( !empty( $jd['realpath'] ) ) {
			return $jd['realpath'] ;
		}
	}
	else {
		// local file
		return realpath($filename);
	}
	return false ;
}

function vfile_glob( $filename )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		$j = vfile_readhttp( $fileserver."?c=dir&n=".rawurlencode($filename) ) ;
		@$jd = json_decode( $j, true );
		if( !empty( $jd['res'] ) ) {
			return $jd['list'] ;
		}
	}
	else {
		// local file
		return glob( $filename );
	}
	return false ;
}

function vfile_get_contents( $filename )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		return vfile_readhttp( $fileserver."?c=r&n=".rawurlencode($filename) ) ;
	}
	else {
		// local file
		return file_get_contents( $filename );
	}
}

function vfile_put_contents( $filename, $data )
{
	if( $fileserver = vfile_remote() ) {
		// remote file
		// to use post 
		$opts = array( 'http'=>
					array(
			        'method' => 'POST',
					'header'=>"Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\nConnection: close\r\n",
					'content' => "c=w&n=".rawurlencode($filename)."&l=".rawurlencode($data)
					) );
		$context = stream_context_create($opts);
		$j = file_get_contents( $fileserver, false, $context );
		@$jd = json_decode( $j, true );
		if( !empty( $jd['res'] ) ) {
			return $jd['ret'] ;
		}
	}
	else {
		// local file
		return file_put_contents( $filename, $data );
	}
	return false ;
}

// return file context, only able to open file as read only mode
function vfile_open( $filename , $mode = "rb" )
{
	// file context
	$fctx = false ;

	if( $fileserver = vfile_remote() ) {
		// remote file
		if( $mode[0] == 'w' ) {
			$fctx = array();
			$fctx['type'] = 2 ;			// file type, 1: local file handle, 2: remote
			$fctx['fn'] =  $filename ;	// filename
			$fctx['p'] = 0 ;			// file pointer
			$fctx['mode'] = $mode ;
			$fctx['svr'] = $fileserver ;
		}
		else {
			$st = vfile_stat( $filename ) ;
			if( $st ) {
				$fctx = array();
				$fctx['type'] = 2 ;			// file type, 1: local file handle, 2: remote
				$fctx['fn'] =  $filename ;	// filename
				$fctx['p'] = 0 ;			// file pointer
				$fctx['mode'] = $mode ;
				$fctx['svr'] = $fileserver ;
				$fctx['stat'] = $st ;	
			}
		}
	}
	else {
		// local file
		$f = fopen( $filename, $mode ) ;
		if( $f ) {
			$fctx = array();
			$fctx['type'] = 1 ;			// file type, 1: local file handle, 2: remote
			$fctx['handle'] = $f ;
		}
	}

	return $fctx ;
}

function vfile_seek( &$fctx, $offset, $whence = SEEK_SET )
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			// local handle
			fseek( $fctx['handle'], $offset, $whence );
		}
		else if( $fctx['type'] == 2 ) {
			// remote file
			if( $whence == SEEK_SET ) {
				$fctx['p'] = $offset ;
			}
			else if( $whence == SEEK_END ) {
				if( empty( $fctx['stat'] ) || $fctx['mode'][0] == 'w' ) {
					$fctx['stat'] = vfile_stat( $fctx['fn'] ) ;
				}
				$fctx['p'] = $offset + $fctx['stat']['size'] ;
			}
			else if( $whence == SEEK_CUR ) {
				$fctx['p'] += $offset ;
			}
			if( $fctx['p'] < 0 ) $fctx['p'] = 0 ;
		}
	}
}

function vfile_tell( &$fctx )
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			// local handle
			return ftell( $fctx['handle'] );
		}
		else if( $fctx['type'] == 2 ) {
			// remote file
			return $fctx['p'] ;
		}
	}
	return 0;
}

function vfile_read( &$fctx, $length ) 
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			return fread( $fctx['handle'], $length ) ;
		}
		else if( $fctx['type'] == 2 ) {
			$d = vfile_readhttp( $fctx['svr'].'?c=r&o='.$fctx['p'].'&l='.$length.'&n='.rawurlencode($fctx['fn']) ) ;
			$l = strlen( $d );
			if( $l>0 ) {
				$fctx['p'] += $l ;
			}
			return $d ;
		}
	}
	return false ;
}

function vfile_write( &$fctx, $string ) 
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			return fwrite( $fctx['handle'], $string ) ;
		}
		else if( $fctx['type'] == 2 && $fctx['mode'][0] == 'w' ) {
			$url = $fctx['svr'].'?c=w&n='.rawurlencode($fctx['fn'])."&l=".rawurlencode($string) ;
			if( $fctx['p'] > 0 ) {
				$url .= "&o=".$fctx['p'] ;
			}
			$j = vfile_readhttp($url) ;
			@$jd = json_decode( $j, true );
			if( !empty( $jd['res'] ) ) {
				$fctx['p'] = $jd['pos'] ;
				return $jd['ret'] ;
			}
		}
	}
	return false ;
}

function vfile_gets( &$fctx, $length = 8192 ) 
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			return fgets( $fctx['handle'], $length ) ;
		}
		else if( $fctx['type'] == 2 ) {
			$d = vfile_readhttp( $fctx['svr'].'?c=rl&o='.$fctx['p'].'&l='.$length.'&n='.rawurlencode($fctx['fn']) ) ;
			$l = strlen( $d );
			if( $l>0 ) {
				$fctx['p'] += $l ;
			}
			return $d ;
		}
	}
	return false ;
}

// nol: max number of lines
function vfile_readlines( &$fctx, $nol ) 
{

	if( !empty( $fctx['type'] )) {
		if( empty($nol) )
			$nol = 1 ;
		if( $fctx['type'] == 1 ) {
			$lines = array();
			while( $nol-- > 0 && $line = fgets( $fctx['handle'] ) ) {
				$li = array();
				$li['text'] = $line ;
				$li['npos'] = ftell( $fctx['handle'] );
				$lines[] = $li ;
			}
			return $lines ;
		}
		else if( $fctx['type'] == 2 ) {
			$lines = vfile_readhttp( $fctx['svr'].'?c=rm&o='.$fctx['p'].'&l='.$nol.'&n='.rawurlencode($fctx['fn']) ) ;
			$lines = json_decode( $lines, true );
			if( !empty($lines['lines']) ) {
				$fctx['p'] = (int)$lines['pos'] ;
				return $lines['lines'] ;
			}
		}
	}
	return false ;
}


function vfile_close( &$fctx ) 
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			fclose( $fctx['handle'] ) ;
		}
	}
	unset( $fctx );
}

?>