<?php
// sendmail.php - send email directly to receiver without smtp server
// By Dennis Chen @ TME	 - 2014-07-21
// Copyright 2014 Toronto MicroElectronics Inc.
//
	// x-mailer
	$mail_x_mailer = "Touchdown sendmail 0.3 - 247 Security Inc" ;
	$mail_starttls = false ;
	$mail_authlogin = false ;
	$mail_server = '' ;
	$mail_port = 25 ;
	$mail_securetype = 0 ;		// 0 : tcp, 1: tls, 2: ssl
	$mail_username = '' ;
	$mail_password = '' ;		// stored as bas64 encoded already

	// create mail body
	function mail_body( $to, $sender, $subject="", $message = "", $attachments = NULL )
	{
		global $mail_x_mailer ;
		
		// all mime content type I may use
		$mime_type = array( 
			"txt"  => "text/plain" ,
			"text" => "text/plain" ,
			"html" => "text/html" ,
			"htm"  => "text/html" ,
			"json" => "application/json" ,
			"jpg" => "image/jpeg" ,
			"jpeg" => "image/jpeg" ,
			"png" => "image/png" ,
			"gif" => "image/gif" ,
			"bmp" => "image/bmp" ,
			"mp4"  => "video/mp4"  ,
			"h264"  => "video/h264"  ,
			"mpeg"  => "video/mpeg"  ,
			"doc"  => "application/msword"  ,
			"pdf"  => "application/pdf"  ,
			"bz2"  => "application/x-bzip2"  ,
			"zip"  => "application/zip"  ,
			"mp3"  => "audio/mpeg"  ,
			"ogg"  => "audio/ogg"  ,
			"aac"  => "audio/x-wav"  ,
			"wav"  => "audio/x-aac"  ,
			"xml"  => "application/xml"  );

		// build email message
		$bdy = uniqid("",true);

		// fix $subject (no newlines)
		$subject = str_replace("\r\n", " ", $subject );
		$subject = str_replace("\n", " ", $subject );
		
		// Main header
        $msg  = "From: " . $sender . "\r\n" ;
        $msg .= "To: " . $to . "\r\n" ;
        $msg .= "Subject: " . $subject . "\r\n" ; 
        $msg .= "Date: " . date("D, d M Y H:i:s O") . "\r\n" ;
		// Customize X-Mailer
		if( !empty( $mail_x_mailer ) ) {
			$msg .= "X-Mailer: " .$mail_x_mailer. "\r\n" ;
		}

		// MIME header
		$msg .= "MIME-Version: 1.0\r\n";
		$msg .= "Content-Type: multipart/mixed; boundary=\"".$bdy."\"\r\n\r\n";
		$msg .= "This is a multi-part message in MIME format.\r\n";
		$msg .= "\r\n--".$bdy."\r\n";

		// attachments
		if( !empty( $attachments ) )
		foreach( $attachments as $att ) {
			if( !empty( $att['content'] ) ) {
				$content = $att['content'] ;
				if( empty( $att['name'] ) ) {
					$name = "attachment" ;
				}
				else {
					$name = $att['name'] ;
				}
			}
			else {
				if( empty( $att['name'] ) ) {
					$name = $att ;
				}
				else {
					$name = $att['name'] ;
				}
				@$content = file_get_contents( $name ) ;
			}
			if( !empty($content) ) {
				$name = basename($name);
				if( !empty( $att['type'] ) ) {
					$content_type = $att['type'] ;
				}
				else {
					$ext = strtolower( substr( $name, strrpos( $name, '.' )+1 ) );
					if( !empty( $mime_type[$ext] ) ) {
						$content_type = $mime_type[$ext] ;
					}
					else {
						$content_type = "application/octet-stream"  ;
					}
				}
				$msg .= "Content-Type: $content_type; name=\"$name\"\r\n";
				$msg .= "Content-Transfer-Encoding: base64\r\n";
				$msg .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
				$msg .= chunk_split(base64_encode($content));
				$msg .= "\r\n--".$bdy."\r\n";
				unset( $content );
			}
		}
		
		// message body
		$msg .= "Content-type:text/plain; charset=UTF-8\r\n";
		$msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		
		// quoted printable
		$qmessage = quoted_printable_encode( $message );
		if( !empty( $qmessage ) ) {
			$qmessage = str_replace("\n.", "\n..", $qmessage );
		}
		$msg .= $qmessage;
		
		// end of last part and end of message
		$msg .= "\r\n--".$bdy."--\r\n\r\n";

		return $msg ;
	}
	
	// send mail help , get my external ip address
	function sm_myip()
	{
		return trim(file_get_contents("https://api.ipify.org"));
	}
	
	// send smtp command , receive smpt response
	function sm_smtpcmd( $cli, $cmd = NULL, &$resp = NULL )
	{
		global $mail_starttls, $mail_authlogin ;

		if( !empty( $cmd ) ) {
			fwrite($cli, $cmd );
			fwrite($cli, "\r\n" );
			fflush($cli);
		}
		while( $l = fgets( $cli ) ) {
			$l = trim( $l );
			if( strlen($l)>4 ) {
				$resp = substr( $l, 4 ) ;
				if( substr( $resp, 0, 8) == "STARTTLS" ) {
					$mail_starttls = true ;
				}
				else if( substr( $resp, 0, 4) == "AUTH" ) {
					$mail_authlogin = true ;
				}
				
				if( $l[3] == '-' ) 
					continue ;
				else 
					return (int) $l ;
			}
			else {
				return (int) $l ;
			}
		}
		return 555 ;
	}
	
	// strip email address, ex dennis<dennisc@tme-inc.com> => dennisc@tme-inc.com
	function mail_addr( $addr ) 
	{
		$b1 = strpos($addr, '<' );
		if( $b1 !== false ) {
			$b2 = strpos( $addr, '>', $b1+1 ) ;
			if( $b2 !== false ) {
				return trim(substr($addr, $b1+1, $b2-$b1-1 ));
			}
		}
		return trim( $addr ) ;
	}

	
	function sendmail($to, $sender, $subject = '', $message = '', $attachments = NULL )
	{
		global $mail_starttls, $mail_authlogin, $mail_server, $mail_port, $mail_securetype, $mail_username, $mail_password ;
	
		if( empty($mail_server) || !function_exists("openssl_encrypt") ) {
			// fall back to default
			$mail_server = '' ;
			$mail_port=25 ;
			$mail_securetype = 0 ;
			$mail_username='' ;
			$mail_password='' ;
		}
	
		// fixing $to addressses
		$to = str_replace(";", ",", $to );
		$to = str_replace("\r\n", ",", $to );
		$to = str_replace("\n", ",", $to );

		$errors = 0 ;
		
		$myip = sm_myip();
		
		// MAIL FROM:
		if( (!empty($mail_username)) && (!empty($mail_server)) ) {
			$from = mail_addr($mail_username);
		}
		else {
			$from = mail_addr($sender) ;
		}

		// group receipients by domain
		$domains = array() ;
		foreach( explode(",", $to) as $toaddr ) {
			$toaddr = mail_addr( $toaddr ) ;
			if( !empty( $toaddr ) ) {
				// domain part
				if( empty( $mail_server ) && strpos( $toaddr, '@' )!=false ) {
					$domain = substr( $toaddr, strpos($toaddr, '@')+1 );
				}
				else {
					$domain = $mail_server;	// if relay server available, send all rcpt to this server
				}
				if( empty( $domains[$domain] ) ) {
					$domains[$domain] = array() ;
				}
				$domains[$domain][] = $toaddr ;
			}
		}
		
		// send out email
		foreach( $domains as $domain => $rcpts ) {
			
			// if mail server not set, use MX records from domain
			if( empty( $mail_server ) ) {
				// get mx record of receiver server
				$mxhosts = false ;
				if( getmxrr( $domain, $mxhosts ) ) {
					$domain = $mxhosts[0] ;
				}
			}

			$sslctx = stream_context_create(array(
				'ssl' =>
					array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
			));

			if( $mail_securetype==2 ) {
				$mail_protocol = "ssl://" ;
			}
			else if( $mail_securetype==1 ) {
				$mail_protocol = "tls://" ;
			}
			else {
				$mail_protocol = "tcp://" ;
			}
			
			$mxcli = stream_socket_client( 
				$mail_protocol.$domain.":".$mail_port, 
				$errno, 
				$errstr, 
				60, 
				STREAM_CLIENT_CONNECT, 
				$sslctx 
			);

			if($mxcli)
			{
				socket_set_timeout($mxcli, 10);
				if( sm_smtpcmd( $mxcli ) >= 400 ) { $errors++ ; }
	
				$mail_starttls = false ;
				$mail_authlogin = false ;
				if( sm_smtpcmd( $mxcli,   "EHLO [". $myip. "]" ) >= 400 ) {
					if( sm_smtpcmd( $mxcli,   "HELO [". $myip. "]" ) >= 400 ) {
						$errors++ ;
					}
				}
				
				// STARTTLS 
				if( $mail_starttls && !$mail_securetype ) {
					if( sm_smtpcmd( $mxcli, "STARTTLS" ) >= 400 ) {
						$errors++ ;
					}

					stream_socket_enable_crypto($mxcli, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
					
					// EHLO again
					if( sm_smtpcmd( $mxcli,   "EHLO [". $myip. "]" ) >= 400 ) {
						$errors++ ; 
					}
				}
				
				if( $mail_authlogin && (!empty($mail_username)) ) {
					// auth login
					if( sm_smtpcmd( $mxcli, "AUTH LOGIN" ) >= 400 ) {
						$errors++ ; 
					}
					if( sm_smtpcmd( $mxcli, base64_encode($mail_username)) >= 400 ) {
						$errors++ ; 
					}
					// $mail_password stored as base64 encoded
					if( sm_smtpcmd( $mxcli, $mail_password ) >= 400 ) {
						$errors++ ; 
					}
				}
				
				if( sm_smtpcmd( $mxcli, "MAIL FROM:<".$from.">" ) >= 400 ) {
					$errors++ ;
				}
		
				foreach( $rcpts as $rcpt ) {
					if( sm_smtpcmd( $mxcli, "RCPT TO:<".$rcpt.">" ) >= 400 ) {
						$errors++ ;
					}					
				}

				if( sm_smtpcmd( $mxcli, "DATA" ) >= 400 ) {
					$errors++ ;
				}
				else {
					// Send message body
					fwrite($mxcli, mail_body( $to, $sender, $subject, $message, $attachments ) );
					// End of message body
					if( sm_smtpcmd( $mxcli, "." ) >= 400 ) {
						$errors++ ;
					}
				}
				
				// Quit connection
				if( sm_smtpcmd( $mxcli, "QUIT" ) >= 400 ) {
					$errors++ ;
				}
				
				fclose($mxcli);
			}
			else {
				$errors++ ;
			}
		}

		if( $errors==0 ) return true ;
		else return false ;
    }
	
	// sending mail with user auth
	function sendmail_secure($to, $sender, $subject = '', $message = '', $attachments = NULL )
	{
		global $mail_server, $mail_port, $mail_securetype, $mail_username, $mail_password ;

		// get email settings from tdalert email settings
		global $conn ;

		$mail_server = '' ;

		$sql = "SELECT smtpServer,smtpServerPort,security,authenticationUserName,authenticationPassword,senderAddr FROM tdconfig ;" ;
		if( !empty($conn) && ($result=$conn->query($sql)) ) {
			$mailer = $result->fetch_assoc() ;
			@$mail_server = $mailer['smtpServer'] ;
			@$mail_port= (int)$mailer['smtpServerPort'] ;
			if( empty($mail_port) ) $mail_port = 25 ;
			@$mail_securetype = $mailer['security'] ;
			@$mail_username=$mailer['authenticationUserName'] ;
			@$mail_password=$mailer['authenticationPassword'] ;
		}

		return sendmail($to, $sender, $subject, $message, $attachments );
	}

?>