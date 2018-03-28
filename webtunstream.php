<?php
// webtunstream.php - webtun stream 
// By Dennis Chen @ TME	 - 2016-11-21
// Copyright 2016 Toronto MicroElectronics Inc.

header("x-ws-1:1");

require_once 'netpackfunc.php' ;

header("x-ws-2:1");

// default session path
if( empty($session_path) ) {
	$session_path= "session";
}

header("x-ws-3:1");

class WebtunStream {
    private $server ;
	private $lvrfile ;
    private $recv_stream ;
	private $recv_buf ;
    private $send_stream ;
	private $option_timeout ;

	// open web tunnel stream
	//     $path example: webtun://12345:80
	//     $mode : 'c' for tcp connection
    public function stream_open($path, $mode, $options, &$opened_path)
    {
		global $session_path ;
		// init
		$this->server = NULL ;
		$this->lvrfile = NULL ;
		$this->recv_stream = NULL ;
		$this->recv_buf = '' ;
		
		$this->send_stream = NULL ;
		
		$this->option_timeout = 60 ;
		
		$target_url = parse_url( $path );		// 'host' : use phone number, 'port' : target port
		
		$lvcfile = $session_path.'/sess_lvc_'.$target_url['host'] ;
		@$flvc = fopen( $lvcfile, "r+" );
		
		if( $flvc ) {
			flock( $flvc, LOCK_EX ) ;		// exclusive lock
			$rtime = time();				// current time
			
			// check host waitting time
			while( $line = fgets( $flvc ) ) {
				if( substr( $line, 0, 1) == 'i' && ($rtime - (int)substr($line, 2)) > 300 ) {
					flock( $flvc, LOCK_UN ) ;		// unlock ;
					fclose( $flvc );
					return false ;
				}
			}
			
			// web tunnel listener
			$this->server = stream_socket_server("tcp://127.0.0.1:0", $errno, $errstr);
			if( $this->server ) {
				// get the server port
				$local_url = parse_url( "tcp://" .stream_socket_get_name($this->server, false) ) ;
				$this->lvrfile = $session_path.'/sess_lvr_'.$local_url['port'] ;

				file_put_contents(  $this->lvrfile, "$rtime" ) ;

				fseek( $flvc, 0, SEEK_END );
				if( strstr($mode, '+') === false ) {
					fwrite( $flvc, "$rtime,$mode $local_url[port] 127.0.0.1 $target_url[port]\n");
				}
				else {
					$x = explode( '+', $mode );
					fwrite( $flvc, "$rtime,$x[0] $local_url[port] $x[1]\n");
				}
			}

			fflush( $flvc ) ;              	// flush before release the lock
			flock( $flvc, LOCK_UN ) ;		// unlock ;
			fclose( $flvc );
		}
		
		return !empty($this->server) ;
	}
	
	function stream_close() {
		if( !empty($this->lvrfile) ) {
			@unlink( $this->lvrfile ) ; 	
			$this->lvrfile=NULL;
		}
		if( $this->recv_stream  ) {
			fclose($this->recv_stream);
			$this->recv_stream = NULL ;
		}
		if( $this->send_stream ) {
			fclose($this->send_stream);
			$this->send_stream = NULL ;
		}
		if( $this->server ) {
			fclose($this->server);
			$this->server = NULL ;
		}
		$this->recv_buf = '' ;
	}
	
	public function stream_set_option ( $option , $arg1, $arg2 ) 
	{
		if( $option == STREAM_OPTION_READ_TIMEOUT  ) {
			$this->option_timeout = $arg1 + $arg2/1000000.0 ;
			return true ;
		}
		return false ;
	}
	
	// wait for connection ( also clear pending connections )
	private function wait( $tous = 0 ) {
		if( $this->recv_stream && feof($this->recv_stream) ) {
			fclose($this->recv_stream);
			$this->recv_stream = NULL ;
		}
		if( $this->send_stream && feof($this->send_stream) ) {
			fclose($this->send_stream);
			$this->send_stream = NULL ;
		}
		while( $this->server && net_available( $this->server, $tous ) ) {
			$conn = stream_socket_accept($this->server,1) ;
			if( $conn ) {
				// message type handshake
				$msgtype = fread( $conn, 1 ) ;
				if( $msgtype == 'p' ) {			// put data
					if( $this->recv_stream ) {
						fclose($this->recv_stream);
					}				
					$this->recv_stream = $conn ;
					$conn = NULL ;
				}
				else if(  $msgtype == 'g'  ) {	// get data
					if( $this->send_stream ) {
						fclose($this->send_stream);
					}
					$this->send_stream = $conn ;
					$conn = NULL ;
				}
				else if(  $msgtype == 'e'  ) {	// end connection
					$this->stream_close();
				}
				
				if( $conn ) {
					fclose( $conn ) ;
				}
			}
			$tous = 0 ;
		}
		return !empty($this->server) ;
	}
				
    public function stream_read($count)
    {
		$rbegin = time() ;
		$rtime = $rbegin ;
		while( $this->server && ($rtime - $rbegin) < $this->option_timeout   ) {
			if( strlen( $this->recv_buf ) > 0 ) {			// buffer availabe
				$r = substr( $this->recv_buf, 0, $count );
				$this->recv_buf = substr( $this->recv_buf, $count ) ;
				return $r ;
			}
			if( $this->recv_stream ) {
				if( net_available( $this->recv_stream , 1000000 ) ) {
					$this->recv_buf = net_readpack( $this->recv_stream );
				}
				else {
					$rtime = time();
				}
				$this->wait(0);
			}
			else {
				$this->wait(1000000);
				$rtime = time();
			}
		}
		return '';
	}

	public function stream_write($data)
    {
		$wbegin = time() ;
		$slen = strlen($data) ;
		while( $this->server && $slen>0 && ( time() - $wbegin) < 10 ) {
			if( $this->send_stream && net_sendpack( $this->send_stream, $data ) ) {
				return $slen ;
			} 
			$this->wait(500000) ;
		}
		return 0 ;
	}
	
	// to force closing GET tunnel (not necessary, livetun.php would flush out buffers every time)
	public function stream_flush()
	{
		if( $this->send_stream ) {
			fclose( $this->send_stream );
			$this->send_stream = NULL ;
		} 
		$this->wait();
		return true ;
	}

    public function stream_eof()
    {
		return empty($this->server) && (strlen( $this->recv_buf ) == 0) ;
    }

}

stream_wrapper_register("webtun", "WebtunStream") ;

?>