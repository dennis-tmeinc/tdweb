<?php
// vfile.php - video file interface
// By Dennis Chen @ TME	 - 2014-01-03
// Copyright 2013 Toronto MicroElectronics Inc.

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

function vfile_http_post( $data )
{
    global $remote_fileserver ;
	return file_get_contents( $remote_fileserver, false, stream_context_create(array(
		'http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query($data)
			),
		'ssl' =>
			array(
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
    )));
}

function vfile_http_get( $data )
{
    global $remote_fileserver ;
    return file_get_contents( $remote_fileserver."?".http_build_query($data) );
}

function vfile_readhttp( $url )
{
	return file_get_contents( $url );
}

// return url link of remote file (for read)
function vfile_url( $filename )
{
	global $remote_fileserver ;
	if( vfile_remote() ) {
		// remote file
		$data = array(
		    'c' => "r",
			'n' => $filename
			);
		return $remote_fileserver."?".http_build_query($data) ;
	}
	else {
		// local file
		return $filename ;
	}
}

function vfile_stat( $filename )
{
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array(
			'c' => "i",
			'n' => $filename 
		));
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
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "d",
			'n' => $filename 
		));
		@$jd = json_decode( $j, true );
		return !empty( $jd['res'] ) ; 
	}
	else {
		// local file
		return unlink( $filename ) ;
	}
}

function vfile_mkdir( $dirname )
{
	if( vfile_remote() ) {
		$j = vfile_http_post( array( 
			'c' => "mkdir",
			'n' => $dirname 
		));
		@$jd = json_decode( $j, true );
		return !empty( $jd['res'] ) ; 
	}
	else {
		// local file
		return mkdir( $dirname, 0777, true ) ;
	}
}

function vfile_rmdir( $dirname )
{
	if( vfile_remote() ) {
		$j = vfile_http_post( array( 
			'c' => "rmdir",
			'n' => $dirname
		));
		@$jd = json_decode( $j, true );
		return !empty( $jd['res'] ) ; 
	}
	else {
		// local file
		return rmdir( $dirname ) ;
	}
}

function vfile_rename( $filename, $nfilename )
{
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "n",
			'n' => $filename,
			'l' => $nfilename
		));
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
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "i",
			'n' => $filename 
		));
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
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "dir",
			'n' => $filename 
		));
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

function vfile_exec($cmd, &$output, &$ret)
{
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "e",
			'n' => $cmd
		));
		@$jd = json_decode( $j, true );
		if( !empty($jd['res']) ) {
			$ret = $jd['ret'] ;
			$output = $jd['output'] ;
			if( !empty($jd['rvalue']) )
				return $jd['rvalue'] ;
		}
		return null ;
	}
	else {
		// local file
		return exec( $cmd,$output,$ret );
	}
}

function vfile_get_contents( $filename )
{
	if( vfile_remote() ) {
		// remote file
		return vfile_http_post( array( 
			'c' => "r",
			'n' => $filename
		));
	}
	else {
		// local file
		return file_get_contents( $filename );
	}
}

function vfile_put_contents( $filename, $data )
{
	if( vfile_remote() ) {
		// remote file
		// use post 
		$j = vfile_http_post( array( 
			'c' => "w",
			'n' => $filename,
			'l' => $data
		));
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

	if( vfile_remote() ) {
		// remote file
		$fctx = array(
			'type'	=> 2 ,			// file type, 1: local file handle, 2: remote
			'fn'	=> $filename ,	// filename
			'p'		=> 0 ,			// file pointer
			'mode'	=> $mode 
		);
		if( $mode[0] != 'w' ) {
			$fctx['stat'] = vfile_stat( $filename ) ; 	
		}
	}
	else {
		// local file
		$f = fopen( $filename, $mode ) ;
		if( $f ) {
			$fctx = array(
				'type'		=> 1, 			// file type, 1: local file handle, 2: remote
				'handle'	=> $f 
			);
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
			$d = vfile_http_post( array( 
				'c' => "r" ,
				'n' => $fctx['fn'] ,
				'o' => $fctx['p'] ,
				'l' => $length
			));
			$l = strlen( $d );
			if( $l>0 ) {
				$fctx['p'] += $l ;
			}
			return $d ;
		}
	}
	return false ;
}

function vfile_write( &$fctx, $wdata ) 
{
	if( !empty( $fctx['type'] )) {
		if( $fctx['type'] == 1 ) {
			return fwrite( $fctx['handle'], $wdata ) ;
		}
		else if( $fctx['type'] == 2 && $fctx['mode'][0] == 'w' ) {
			$j = vfile_http_post( array( 
				'c' => "w" ,
				'n' => $fctx['fn'] ,
				'o' => $fctx['p'] ,
				'l' => $wdata
			));
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
			$d = vfile_http_post( array( 
				'c' => "rl" ,
				'n' => $fctx['fn'] ,
				'o' => $fctx['p'] ,
				'l' => $length
			));
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
			$j = vfile_http_post( array( 
				'c' => "rm" ,
				'n' => $fctx['fn'] ,
				'o' => $fctx['p'] ,
				'l' => $nol
			));
			$lines = json_decode( $j, true );
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