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
		@$st = stat( $filename ); 
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

// get realpath of the filename
function vfile_basename( $filename )
{
	@ $st = vfile_stat( $filename ) ;
	if( !empty($st['basename']) ) {
		return $st['basename'];
	}
	return basename( $filename );
}

// get disk free space
function vfile_disk_free_space( $directory ) 
{
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "disk",
			'n' => $directory 
		));
		@$jd = json_decode( $j, true );
		if( !empty( $jd['res'] ) && !empty($jd['free']) ) {
			return $jd['free'] ;
		}
		else {
			return false ;
		}
	}
	else {
		// local file
		return disk_free_space( $directory );
	}
}

// get disk total space
function vfile_disk_total_space( $directory ) 
{
	if( vfile_remote() ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "disk",
			'n' => $directory 
		));
		@$jd = json_decode( $j, true );
		if( !empty( $jd['res'] ) && !empty($jd['total']) ) {
			return $jd['total'] ;
		}
		else {
			return false ;
		}
	}
	else {
		// local file
		return disk_total_space( $directory ) ;
	}	
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

function vfile_exec($cmd, &$output = null, &$ret = null)
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
		if( file_exists($filename) )
			return file_get_contents( $filename );
		else 
			return "";
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

// return file context
function vfile_open( $filename , $mode = "rb" )
{
	// file context
	$fctx = false ;

	if( vfile_remote() ) {
		// remote file
		$fctx = array(
			'remote'=> 1 ,			// remote file, 0: local, 1: remote
			'fn'	=> $filename ,	// filename
			'p'		=> 0 ,			// file pointer
			'mode'	=> $mode 
		);
	}
	else {
		// local file
		$f = fopen( $filename, $mode ) ;
		if( $f ) {
			$fctx = array(
				'handle'	=> $f 
			);
		}
	}

	return $fctx ;
}

function vfile_fstat( &$fctx ) 
{
	if( !empty( $fctx['remote'] ) ) {	
		$j = vfile_http_post( array(
			'c' => "i",
			'n' => $fctx['fn']
		));
		@$st = json_decode( $j, true );
		if( !empty( $st['res'] ) ) {
			unset( $st['res'] );
			return $st ;
		}	
	}
	else if( !empty( $fctx['handle'] ) ) {
		return fstat( $fctx['handle'] );
	}
	return false ;
}


function vfile_seek( &$fctx, $offset, $whence = SEEK_SET )
{
	if( !empty( $fctx['remote'] ) ) {
		// remote file
		if( $whence == SEEK_SET ) {
			$fctx['p'] = $offset ;
		}
		else if( $whence == SEEK_CUR ) {
			$fctx['p'] += $offset ;
		}
		else if( $whence == SEEK_END ) {
			$fctx['p']  = vfile_size( $fctx['fn'] ) + $offset ;
		}
		if( $fctx['p'] < 0 ) $fctx['p'] = 0 ;		
		return 0 ;
	}
	else if( !empty( $fctx['handle'] ) ) {
		return fseek( $fctx['handle'], $offset, $whence );
	}
	return -1 ;
}

function vfile_tell( &$fctx )
{
	if( !empty( $fctx['remote'] ) ) {
		// remote file
		return $fctx['p'] ;
	}
	else if( !empty( $fctx['handle'] ) ) {
		// local handle
		return ftell( $fctx['handle'] );
	}
	return 0 ;
}


function vfile_read( &$fctx, $length ) 
{
	if( !empty( $fctx['remote'] ) ) {
		// remote file
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
	else if( !empty( $fctx['handle'] ) ) {
		// local handle
		return fread( $fctx['handle'], $length ) ;
	}
	return false ;
}


function vfile_write( &$fctx, $wdata ) 
{
	if( !empty( $fctx['remote'] ) ) {
		// remote file
		$j = vfile_http_post( array( 
			'c' => "w" ,
			'n' => $fctx['fn'] ,
			'o' => $fctx['p'] ,
			'l' => $wdata
		));
		@$jd = json_decode( $j, true );
		if( !empty( $jd['res'] ) ) {
			$fctx['p'] = $jd['pos'] ;	// update position
			return $jd['ret'] ;
		}
	}
	else if( !empty( $fctx['handle'] ) ) {
		// local handle
		return fwrite( $fctx['handle'], $wdata ) ;
	}
	return false ;
}


// nol: max number of lines, return array of struct lines
function vfile_readlines( &$fctx, $nol=1 ) 
{
	if( empty($nol) )
		$nol = 1 ;	
	if( !empty( $fctx['remote'] ) ) {
		// remote file
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
	else if( !empty( $fctx['handle'] ) ) {
		// local handle
		$lines = array();
		while( $nol-- > 0 && $line = fgets( $fctx['handle'] ) ) {
			$li = array();
			$li['text'] = $line ;
			$li['npos'] = ftell( $fctx['handle'] );
			$lines[] = $li ;
		}
		return $lines ;
	}
	return false ;
}

function vfile_gets( &$fctx, $length = 8192 ) 
{
	if( !empty( $fctx['remote'] ) ) {
		// remote file
		$lines = vfile_readlines( $fctx , 1);
		if( !empty($lines) ) {
			return $lines[0]['text'] ;
		}
	}
	else if( !empty( $fctx['handle'] ) ) {
		// local handle
		return fgets( $fctx['handle'], $length ) ;
	}
	return false ;
}

function vfile_close( &$fctx ) 
{
	if( !empty( $fctx['remote'] ) ) {
		unset( $fctx['remote'] );
		unset( $fctx['fn'] );
		return true ;
	}
	else if( !empty( $fctx['handle'] ) ) {
		// local handle
		$res = fclose( $fctx['handle'] ) ;
		unset( $fctx['handle'] );
		return $res ;
	}
	return false ;
}


class vfile {

	protected  $handle = false ;
	protected  $remote = false ;
	protected  $rname = "" ;
	protected  $p = 0 ;
	
	function __destruct() {
		$this->close();
	}
   
   	public function open($filename , $mode = "rb") {
		if( vfile_remote() ) {
			// remote file
			$this->remote = true ;
			$this->rname = $filename ;
			$this->p = 0 ;
		}
		else {
			// local file
			$this->remote = false ;
			$this->handle = fopen( $filename, $mode ) ;
		}		
	}
   
	public function close() {
		if( $this->handle ) {
			fclose( $this->handle ) ;
			$this->handle = false ;
		}
	}   
   
	public function fstat() 
	{
		if( $this->remote ) {
			// remote file
			$j = vfile_http_post( array(
				'c' => "i",
				'n' => $this->rname 
			));
			@$st = json_decode( $j, true );
			if( !empty( $st['res'] ) ) {
				unset( $st['res'] );
				return $st ;
			}
		}
		else if( $this->handle ) {
			// local handle
			return fstat( $this->handle );
		}
		return false ;
	}
	
	public function seek( $offset, $whence = SEEK_SET )
	{
		if( $this->remote ) {
			// remote file
			if( $whence == SEEK_SET ) {
				$this->p = $offset ;
			}
			else if( $whence == SEEK_CUR ) {
				$this->p += $offset ;
			}
			else if( $whence == SEEK_END ) {
				$this->p  = $offset ;
				$st = $this->fstat() ;
				if( $st ) {
					$this->p += $st['size'] ;
				}
			}
			if( $this->p < 0 ) $this->p = 0 ;		
			return 0 ;
		}
		else if( $this->handle ) {
			return fseek( $this->handle, $offset, $whence );
		}
		return -1 ;
	}

	public function tell()
	{
		if( $this->remote ) {
			// remote file
			return $this->p;
		}
		else if( $this->handle ) {
			// local handle
			return ftell( $this->handle );
		}
		return 0 ;
	}
	
		
	public function read( $length ) 
	{
		if( $this->remote ) {
			// remote file
			$d = vfile_http_post( array( 
				'c' => "r" ,
				'n' => $this->rname,
				'o' => $this->p,
				'l' => $length
			));
			$l = strlen( $d );
			if( $l>0 ) {
				$this->p += $l ;
			}
			return $d ;
		}
		else if( $this->handle ) {
			// local handle
			return fread( $this->handle, $length ) ;			
		}
		return false ;
	}


	public function write( $wdata ) 
	{
		if( $this->remote ) {
			// remote file
			$d = vfile_http_post( array( 
				'c' => "w" ,
				'n' => $this->rname,
				'o' => $this->p,
				'l' => $wdata
			));
			@$jd = json_decode( $j, true );
			if( !empty( $jd['res'] ) ) {
				$this->p = $jd['pos'] ;	// update position
				return $jd['ret'] ;
			}
		}
		else if( $this->handle ) {
			// local handle
			return fwrite( $this->handle, $wdata ) ;			
		}
		return false ;
	}

	// read multiple line in one short to save IO/network time	
	// nol: max number of lines, return array of struct lines
	public function readlines( $nol=1 ) 
	{
		if( empty($nol) )
			$nol = 1 ;	
		if( $this->remote ) {
			// remote file
			$j = vfile_http_post( array( 
				'c' => "rm" ,
				'n' => $this->rname,
				'o' => $this->p,
				'l' => $nol
			));
			$lines = json_decode( $j, true );
			if( !empty($lines['lines']) ) {
				$this->p = (int)$lines['pos'] ;
				return $lines['lines'] ;
			}
		}
		else if( $this->handle ) {
			// local handle
			$lines = array();
			while( $nol-- > 0 && $line = fgets( $this->handle ) ) {
				$li = array();
				$li['text'] = $line ;
				$li['npos'] = ftell( $this->handle );
				$lines[] = $li ;
			}
			return $lines ;			
		}
		return false ;
	}

	// read one line
	public function gets( $length = 8192 ) 
	{
		if( $this->remote ) {
			// remote file
			$lines = $this->readlines( 1 );
			if( !empty($lines) ) {
				return $lines[0]['text'] ;
			}
		}
		else if( $this->handle ) {		
			// local handle
			return fgets(  $this->handle, $length ) ;
		}
		return false ;
	}	
}

?>