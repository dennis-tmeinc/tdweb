<?php
// istream.php - video streaming interface
// By Dennis Chen @ TME	 - 2013-09-25
// Copyright 2013 Toronto MicroElectronics Inc.

require_once 'config.php' ;
require_once 'vfile.php' ;

session_save_path( $session_path );
session_name( $session_idname );
@$sid=$_REQUEST[session_name()] ;
if( !empty($sid) ) {
	session_id ($sid);
}
session_start();

$resp = array();
$resp['error'] = 0 ;

$xt = $_SERVER['REQUEST_TIME'];
if( empty($_SESSION['user']) ||
	empty($_SESSION['user_type']) ||
	empty($_SESSION['xtime']) || 
    $xt<$_SESSION['xtime'] ||
	$xt>$_SESSION['xtime']+$session_timeout ||
	empty($_SESSION['clientid']) ) 
{
	// error session
	$resp['error']=101 ;
	$resp['error_message'] = "Session error!" ;
	if( !empty($_REQUEST['cmd']) && $_REQUEST['cmd']=='getvideo' ) {
		header( "x-touchdown-vdata: error=".$resp['error'] );
	}
}
else {
	$_SESSION['xtime']=$xt ;
}

session_write_close();

// store one variable to session
function session_save( $vname, $value )
{
	$fsess = fopen( session_save_path().'/sess_'.session_id(), 'r+' );
	if( $fsess ) {
		flock( $fsess, LOCK_EX ) ;		// exclusive lock
		
		$sess_str = fread( $fsess, 20000 );
		session_decode ( $sess_str ) ;
		$_SESSION[$vname] = $value ;
		$sess_str = session_encode() ;
		rewind( $fsess ) ;
		fwrite( $fsess, $sess_str );
		fflush( $fsess ) ;              // flush before releasing the lock
		ftruncate( $fsess, ftell($fsess));

		flock( $fsess, LOCK_UN ) ;		// unlock ;
		fclose( $fsess );
	}
}

// Get channel info from camera, and busname ( playtime in $chctx['ve'] )
// channel context
//   (input)
//   hs: default file header size
//   ve: previous video end time ( search time )
function findvideo( &$chctx, $camera, $busname )
{

    $ret = false ;

	global $smart_server, $smart_user, $smart_password, $smart_database ;
	@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
	
	$searchtime = $chctx['ve'];
	@$st = new DateTime($searchtime);
	if( empty($st) ) {
		$st=new DateTime("2000-01-01");
	}
	
	// find video ending after $chctx['ve']
	$sql = "SELECT time_start, time_end, TIMESTAMPDIFF(SECOND, time_start, time_end ) AS length, path FROM videoclip WHERE vehicle_name = '$busname' AND channel = $camera AND time_end > '$searchtime' ORDER BY time_start" ;

	if( $result = $conn->query($sql, MYSQLI_USE_RESULT) ) {
		while( $row=$result->fetch_array() ) {
			if( vfile_exists ( $row['path'] ) ) {
			
				$chctx['v'] = $row['path'] ;			// video file
				$chctx['vt'] = $row['time_start'] ;		// video file time
				$chctx['ve'] = $row['time_end'] ;		// video file end time
				$chctx['vl'] = $row['length'] ;			// video file time length
				$chctx['vs'] = vfile_size($chctx['v']) ;	// video file size
				
				if( $chctx['vs']<1000 ) {
					// file size too small ?
					continue;
				}

				$chctx['no'] = $chctx['hs'] ;			// next frame offset
				$chctx['nt'] = 0 ;						// next frame time (ms diff from file time)

				$chctx['nh'] = 1 ;						// indicate new header should be output
				
				$chctx['k'] = substr_replace( $chctx['v'], "k", -3 )  ;		// key file
				$chctx['ko'] = 0 ;											// key file offset
				
				if( ($kfile = vfile_open($chctx['k'])) ) {
					$line = vfile_gets( $kfile ) ;
					
					$off = 0 ;
					$dms = 0 ; 
					if( sscanf( $line, "%d,%d", $dms, $off )==2 ) {
						$chctx['hs'] = $off ;
					}
					// detect offset
					vfile_seek($kfile,0);					
					
					while( $lines = vfile_readlines( $kfile, 1000 ) ) {
						for( $i=0; $i<count($lines); $i++ ) {
							if( sscanf( $lines[$i]['text'], "%d,%d", $dms, $off )==2 ) {
								$chctx['no'] = $off ;
								$chctx['nt'] = $dms ;						
								$ft = new DateTime( $chctx['vt'] );		
								$di = new DateInterval( 'PT'.((integer)($dms/1000)).'S') ;
								$ft->add( $di );
								if( $ft >= $st ) {
									// detect offset
									$chctx['ko'] = $lines[$i]['npos'] ;
									$ret = true ;
									break;
								}
							}
						}
						if( $ret ) break;
					}
					vfile_close( $kfile );
					if( $ret ) {
						break;
					}
				}
				else {		// no usable key file
					// do something
					$ft = new DateTime( $chctx['vt'] );
					if( $st <= $ft ) {
						$chctx['no'] = $chctx['hs'] ;			// next frame offset
						$chctx['nt'] = 0 ;						// next frame time (ms diff from file time)
						$ret = true ;
						break;
					}
					else {
						$diffs = $st->getTimestamp() - $ft->getTimestamp();
						if( $diffs < $chctx['vl'] ) {
							$chctx['no'] =  (integer)( $diffs * $chctx['vs'] / $chctx['vl'] ) + $chctx['hs'] ;
							$chctx['no'] &= ~3 ;
							$chctx['nt'] = 0 ;						// next frame time (ms diff from file time)
							$ret = true ;
							break;
						}
					}
				}
			}
		}
		$result->free();
	}

	$conn->close();	
	
	return $ret ;
}

// Read And Return Channel Data
// channel context
//   rl: requested length, rs: requested size 
// return frame length
function videodata( &$chctx, &$data, &$vheaddata )
{
	$ret = 0 ;

	$chctx['ft'] = $chctx['nt'] ;			// current frame time (ms diff from file time)
	$chctx['fl'] = 0 ;						// clear current frame length ( in ms )

	if( ($vfile = vfile_open( $chctx['v'] )) ) {
		// read file header
		if( !empty($chctx['nh']) ) {
			unset($chctx['nh']) ;
			$vheaddata=vfile_read($vfile, $chctx['hs']);
		}
		vfile_seek( $vfile, $chctx['no'] );
		
		// frame size
		$framesize = 512*1024 ;			// max 512k
		if( $chctx['ko'] > 0 ) {
			if( ($kfile = vfile_open($chctx['k'])) ) {
				vfile_seek( $kfile, $chctx['ko'] );
				$off = 0 ;
				$dms = 0 ;
				while( $line = vfile_gets( $kfile ) ) {
					if( sscanf( $line, "%d,%d", $dms, $off )==2 ) {
						if( $off > $chctx['no'] ) {
							$framesize = $off - $chctx['no'] ;
							$chctx['fl'] = $dms - $chctx['ft'] ;
							$chctx['nt'] = $dms ;
							break;
						}
					}
				}
				$chctx['ko'] = vfile_tell($kfile);
				vfile_close($kfile);
			}
		}
		
		$data = vfile_read( $vfile, $framesize );
		
		if( $chctx['fl'] == 0 ) {
			// to calculate frame time base on readed size
			$l = strlen( $data ) ;
			if( $l>0 ) {
				$chctx['fl'] = (integer)(1000*$chctx['vl']*$l/$chctx['vs'])+10 ;
				$chctx['ft'] += $chctx['fl'] ;
			}
		}

		$chctx['no'] = vfile_tell( $vfile );
		vfile_close( $vfile );
	}

	return $chctx['fl'] ;
}

if( !empty( $_REQUEST['serno'] ) ) {
	$resp['serno'] = $_REQUEST['serno'];
}

if( $resp['error'] == 0 ) 
switch ( $_REQUEST['cmd'] ) {
    case 'getinfo':
        $resp = $_SESSION['playlist']['info'] ;
        break;
		
    case 'getdaylist':
		$camera = $_REQUEST['camera'];
		if( empty( $camera ) ) {
			$resp['error'] = 103 ;
			$resp['error_message'] = "No camera number specified!" ;
		}
		else {
			$busname = $_SESSION['playlist']['info']['name'] ;
			$channel = $camera - 1 ;
			$sql = "SELECT DISTINCT DATE(time_start) as `dat` from videoclip where vehicle_name = '$busname' AND channel = $channel" ;
			if( !empty( $_REQUEST['begin'] ) ){
				$resp['begin']=$_REQUEST['begin'] ;
				$sql = $sql . " AND time_start >= '$_REQUEST[begin]'" ;
			}
			if( !empty( $_REQUEST['end'] ) ){
				$resp['end']=$_REQUEST['end'] ;
				$sql = $sql . " AND time_start < '$_REQUEST[end]'" ;
			}
			$sql .= "ORDER BY `dat`" ;
			$resp['number'] = 0 ;
			
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			if( $result = $conn->query($sql) ) {
				$resp['list'] = array();
				while( $row=$result->fetch_array() ) {
					$resp['list'][]=$row[0] ;
					$resp['number'] ++ ;
				}
				$result->free();
			}
			$conn->close();
		}
        break;
		
    case 'getcliplist':
	
		$camera = $_REQUEST['camera'];
		if( empty( $camera ) ) {
			$resp['error'] = 103 ;
			$resp['error_message'] = "No camera number specified!" ;
		}
		else {
			$busname = $_SESSION['playlist']['info']['name'] ;
			$channel = $camera - 1 ;
			$sql = "SELECT time_start, TIMESTAMPDIFF(SECOND, time_start, time_end ) as length, path from videoclip where vehicle_name = '$busname' AND channel = $channel" ;
			if( !empty( $_REQUEST['begin'] ) ){
				$resp['begin']=$_REQUEST['begin'] ;
				$sql = $sql . " AND time_start >= '$_REQUEST[begin]'" ;
			}
			if( !empty( $_REQUEST['end'] ) ){
				$resp['end']=$_REQUEST['end'] ;
				$sql = $sql . " AND time_start < '$_REQUEST[end]'" ;
			}
			$sql = $sql . " ORDER BY time_start" ;
			
			$resp['number'] = 0 ;
			@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
			if( $result = $conn->query($sql) ) {
				$resp['number'] = 0 ;
				$resp['list'] = array();
				while( $row=$result->fetch_array() ) {
				
					if( vfile_exists ( $row['path'] ) ) {
						
						$clip = array() ;
						$clip['time'] = $row['time_start'] ;
						$clip['length'] = $row['length'] ;
						if( strstr( basename ( $row['path'] ), "_L_") ) {
							$clip['lock'] = 1 ;
						}
						else {
							$clip['lock']=0 ;
						}
						$kfile = substr_replace( $row['path'], "k", -3 )  ;
						if( vfile_exists ( $kfile ) ) {
							$clip['key'] = 1 ;
						}
						else {
							$clip['key'] = 0 ;
						}
						$clip['clipsize'] = vfile_size ( $row['path'] ) ;
						$resp['list'][]=$clip ;
						$resp['number'] ++ ;
					}
				}
				$result->free();
			}
			$conn->close();
		}

        break;

    case 'getkeylist':
		$camera = $_REQUEST['camera'];
		if( empty( $camera ) ) {
			$resp['error'] = 103 ;
			$resp['error_message'] = "No camera number specified!" ;
		}
		else {
			$busname = $_SESSION['playlist']['info']['name'] ;
		}
        break;
		
    case 'getvideo':
	
		$vdata = "" ;		// empty data
		$vheaddata = "" ;	// empty file header
		$vlength = 0 ;
		$camera = $_REQUEST['camera'];
		if( empty( $camera ) ) {
			$resp['error'] = 103 ;
			$resp['error_message'] = "No camera number specified!" ;
		}
		else if( empty( $_REQUEST['time'] ) ) {
			$resp['error'] = 104 ;
			$resp['error_message'] = "Parameter 'time' not specified!" ;
		}
		else if( empty( $_SESSION['playlist'] ) ) {
			$resp['error']=101 ;
			$resp['error_message'] = "Session error!" ;		
		}
		else {
			$play = array();
			if( !empty( $_REQUEST['headersize'] ) ) {
				$_SESSION['playlist']['headersize'] = $_REQUEST['headersize'] ;
				session_save( 'playlist', $_SESSION['playlist'] );
			}
				
			$busname = $_SESSION['playlist']['info']['name'] ;
			$camera = $camera - 1 ;
			
			// channel file
			$chfile = session_save_path().'/sess_'.session_id().'-'.$camera ;
			if( !empty( $_REQUEST['xid'] ) ) {
				$chfile .= '-'.$_REQUEST['xid'] ;
			}

			// channel context,   
			//   v: video file, vo: video file offset, vs: video file size, vt: video file time, vl: video length
			//   k: k file, ko: k file offset
			//   hs: file header size
			
			if( file_exists ( $chfile ) ) {
				$fch = fopen( $chfile, "r+" );
				flock( $fch, LOCK_EX );
				$chctx = unserialize( fread( $fch, 10000 ) );
			}
			else {
				$fch = fopen( $chfile, "w+" );
				fflush($fch);
				flock( $fch, LOCK_EX );
				$chctx = array();
			}
			
			if( $_REQUEST['time'] == 'continue' && !empty( $chctx )  ) {
				unset($chctx['rl']);
				if( !empty($_REQUEST['length'] ) ) {
					$chctx['rl'] = $_REQUEST['length'] ;
				}
				unset($chctx['rs']);
				if( !empty($_REQUEST['size'] ) ) {
					$chctx['rs'] = $_REQUEST['size'] ;
				}
				// continue form previous position
				while( ($vlength = videodata( $chctx, $vdata, $vheaddata )) == 0 ) {
					if( ! findvideo( $chctx, $camera, $busname ) ) {
						break;
					}
				}
			}
			else {
				// start a new reading
				$chctx = array();
				if( empty( $_SESSION['playlist']['headersize'] ) ) {
					$chctx['hs'] = 40 ;	// default header size
				}
				else {
					$chctx['hs'] = $_SESSION['playlist']['headersize'] ;	
				}
				$chctx['ve'] = $_REQUEST['time'] ;
				if( !empty($_REQUEST['length'] ) ) {
					$chctx['rl'] = $_REQUEST['length'] ;
				}
				if( !empty($_REQUEST['size'] ) ) {
					$chctx['rs'] = $_REQUEST['size'] ;
				}
				while( findvideo( $chctx, $camera, $busname )  ) {
					$vlength = videodata( $chctx, $vdata, $vheaddata );
					if( $vlength > 0 ) {
						break ;
					}
				}
			}
			
			rewind( $fch ) ;
			fwrite( $fch, serialize( $chctx ) );
			fflush( $fch ) ;              // flush before releasing the lock
			ftruncate( $fch, ftell($fch));
			flock( $fch, LOCK_UN );
			fclose( $fch );
			
		}
		
		// output extra header
		$extra_http_header = "x-touchdown-vdata: size=".strlen($vdata) ;
		$vheaddatalen = strlen($vheaddata);
		if( $vheaddatalen > 0 ) {
			$extra_http_header .= ",headersize=".$vheaddatalen ;
		}
		if( !empty($chctx['fl']) ) {
			$vtime = new DateTime( $chctx['vt'] );
			$ft = new DateInterval( 'PT'.((integer)($chctx['ft']/1000)).'S') ;
			$vtime->add($ft);
			$extra_http_header .= ",time=". $vtime->format('Y-m-d H:i:s').sprintf('.%03d',$chctx['ft']%1000) ;
			$extra_http_header .= ",length=".$chctx['fl'] ;
		}
		if( $resp['error'] != 0 ) {
			$extra_http_header .= ",error=".$resp['error'] ;
			if( !empty(	$resp['error_message'] )) {
				$extra_http_header .= ",error_message=".$resp['error_message'] ;
			}
		}

		if( !empty( $_REQUEST['xid'] ) ) {
			$extra_http_header .= ',xid='.$_REQUEST['xid'];
		}

		if( !empty( $_REQUEST['serno'] ) ) {
			$extra_http_header .= ',serno='.$_REQUEST['serno'];
		}

		header($extra_http_header);
		header("Content-Type: application/octet-stream");

		// output data
		header( "Content-Length:".($vheaddatalen+strlen($vdata)));
		if( $vheaddatalen > 0 ) {
			echo $vheaddata ;
		}
		if( strlen($vdata)>0 ) {
			echo $vdata ;
		}
		
		return ;
        break;

	case 'reporttime':
		$now=new DateTime();
		$etime=new DateTime("2000-01-01");
		@$playtime = new DateTime($_REQUEST['time']);
		if( empty($_REQUEST['time']) || $playtime >= $now || $playtime < $etime ) {
			$resp['error'] = 105 ;
			$resp['error_message'] = "Wrong time parameter!";
		}
		else {
			$playsync = array();
			$playsync['run'] = !empty($_REQUEST['run']);
			$playsync['playtime'] = $playtime->getTimestamp();
			$playsync['reporttime'] = $now->getTimestamp();
			session_save('playsync', $playsync);
			$resp['run'] = $synctime['run'] ? 1:0 ;
			$resp['time'] = $playtime->format('Y-m-d H:i:s');
		}
        break;
	
	default:
		$resp['error']=102 ;
		$resp['error_message'] = "Unknown command : ".$_REQUEST['cmd'] ;
		break;
}

header("Content-Type: application/json");
echo json_encode( $resp );

?>