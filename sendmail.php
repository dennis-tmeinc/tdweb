<?php
// sendmail.php - send email directly to receiver without smtp server
// By Dennis Chen @ TME	 - 2014-07-21
// Copyright 2014 Toronto MicroElectronics Inc.
//
	// x-mailer
	$x_mailer = "Touchdown sendmail 0.2 - 247 Security Inc" ;

	// create mail body
	function mail_body( &$to, &$sender, &$subject, &$message, &$attachments )
	{
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
		
		// fix $to addressses
		$to = str_replace(";", ",", $to );
		$to = str_replace("\r", "", $to );
		$to = str_replace("\n", ",", $to );

		// fix $subject (no newlines)
		$subject = str_replace("\r", "", $subject );
		$subject = str_replace("\n", " ", $subject );
	
		// build email message
		$bdy = uniqid("",true);

		// Main header
        $msg  = "From: " . $sender . "\r\n" ;
        $msg .= "To: " . $to . "\r\n" ;
        $msg .= "Date: " . date("D, d M Y H:i:s O") . "\r\n" ;
        $msg .= "Subject: " . $subject . "\r\n" ; 
		// Customize X-Mailer
		if( !empty( $x_mailer ) ) {
			$msg .= "X-Mailer: " .$x_mailer. "\r\n" ;
		}

		// MIME header
		$msg .= "MIME-Version: 1.0\r\n";
		$msg .= "Content-Type: multipart/mixed; boundary=\"".$bdy."\"\r\n\r\n";
		$msg .= "This is a multi-part message in MIME format.";
		$msg .= "\r\n--".$bdy."\r\n";

		// attachments
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
		$msg .= "\r\n--".$bdy."--\r\n\r\n.\r\n";

		return $msg ;
	}
	
	function sendmail($to, $sender, $subject, &$message, &$attachments )
	{

		// build email message body
		$body = mail_body( $to, $sender, $subject, $message, $attachments ) ;

		$errors = 0 ;
		
		$myip = trim(file_get_contents("http://myip.dtdns.com/"));
		
		// get sender address inside "<>" brackets
		$from = trim($sender);
		if( strpos($from, "<")!==false )
		{	
			$from = substr( $from, strpos( $from, "<" )+1, -1 ) ;
		}
			
		// receive smpt response
		function sm_resp( $cli )
		{
			while( $l=fgets( $cli ) ) {
				if( $l[3]=='-' ) continue ;
				else return $l[0] ;
			}
			return 5 ;	// error
		}
		
		// group receipients by domain
		$domains = array() ;
		$to = explode(",", $to);
		foreach( $to as $toaddr ) {
			// extract address inside brackets
			$b1 = strpos($toaddr, '<' ) ;
			$b2 = strrpos($toaddr, '>' ) ;
			if( $b1 != false && $b2 != false && $b2 > $b1 ) {
				$toaddr = substr( $toaddr, $b1+1, $b2-$b1-1 );
			}
			$toaddr=trim($toaddr);	
			if( !empty( $toaddr ) ) {
				// domain part
				$domain = substr( $toaddr, strpos($toaddr, '@')+1 );
				if( empty( $domains[$domain] ) ) {
					$domains[$domain] = array() ;
				}
				$domains[$domain][] = $toaddr ;
			}
		}
		
		// send out email
		foreach( $domains as $domain => $v ) {
			// get mx record of receiver
			$mxhosts = false ;
			if( getmxrr( $domain, $mxhosts ) ) {
				$domain = $mxhosts[0] ;
			}

			$mxcli = stream_socket_client( "tcp://".$domain.":25" );
			if($mxcli)
			{
				socket_set_timeout($mxcli, 5);
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

				fwrite($mxcli, "EHLO [". $myip. "]\r\n" );
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

				fwrite($mxcli, "MAIL FROM:<".$from.">\r\n");
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

				foreach( $v as $rcpt ) {
					fwrite($mxcli, "RCPT TO:<".$rcpt.">\r\n");
					if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
				}

				fwrite($mxcli, "DATA\r\n");
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
				// Send message data
				fwrite($mxcli, $body);
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

				// Quit connection
				fwrite($mxcli, "QUIT\r\n");
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

				fclose($mxcli);
			}
			else {
				$errors++ ;
			}
		}

		if( $errors==0 ) return true ;
		else return false ;
     }

	// sending mail use ssl connection ( use gmail,yahoo... accounts) 
	function sendmail_secure($to, $sender, $subject, &$message, &$attachments )
	{
		// get email settings from tdalert email settings
		global $conn ;
		$sql = "SELECT smtpServer,smtpServerPort,security,authenticationUserName,authenticationPassword,senderAddr FROM tdconfig ;" ;
		if( !empty($conn) && ($result=$conn->query($sql)) ) {
			$mailer = $result->fetch_assoc() ;
		}

		if( empty($mailer) || empty($mailer['smtpServer']) || !function_exists("openssl_encrypt") ) {
			// fall back to use sendmail
			return sendmail($to, $sender, $subject, $message, $attachments );
		}
		
		@$mail_account = $mailer['authenticationUserName'] ;
		@$mail_password = $mailer['authenticationPassword'] ;
		if( empty( $mailer['senderAddr'] ) )
			$mail_from = $sender ;
		else 
			$mail_from = $mailer['senderAddr'] ;

		if( $mailer['security']==2 )
			$mail_protocol = "ssl://" ;
		else if( $mailer['security']==1 )
			$mail_protocol = "tls://" ;
		else 
			$mail_protocol = "tcp://" ;
		$mail_url=$mail_protocol.$mailer['smtpServer'].':'.$mailer['smtpServerPort'] ;
		@$mxcli = stream_socket_client($mail_url);
		if( empty($mxcli) ) {
			// fall back using sendmail
			return sendmail($to, $sender, $subject, $message, $attachments );
		}
	
		// mail body data
		$body = mail_body( $to, $sender, $subject, $message, $attachments ) ;
		if( $sender != $mail_from ) {
			// add reply address
			$body = "Reply-To: $sender \r\n" . $body ;
		}

		$myip = trim(file_get_contents("http://myip.dtdns.com/"));
		
		// get sender address inside "<>" brackets
		$mail_from = trim($mail_from);
		if( strpos($mail_from, "<")!==false )
		{	
			$mail_from = substr( $mail_from, strpos( $mail_from, "<" )+1, -1 ) ;
		}

		// receive smpt response
		function sm_resp( $cli )
		{
			while( $l=fgets( $cli ) ) {
				if( $l[3]=='-' ) continue ;
				else return $l[0] ;
			}
			return 5 ;	// error
		}

		// send out email
		$errors = 0 ;
		socket_set_timeout($mxcli, 10);
		if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

		fwrite($mxcli, "EHLO [". $myip. "]\r\n" );
		if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

		if( !empty($mail_account) ) {
			// auth login
			fwrite($mxcli, "AUTH LOGIN\r\n");
			if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
			
			fwrite($mxcli, base64_encode($mail_account)."\r\n");
			if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
			
			fwrite($mxcli, $mail_password."\r\n");
			if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
		}

		fwrite($mxcli, "MAIL FROM:<".$mail_from.">\r\n");
		if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

		// extract recipients
		$to = explode(",", $to);
		foreach( $to as $toaddr ) {
			// extract address inside brackets
			$b = strpos($toaddr, '<' ) ;
			if( $b!==false ) {
				$toaddr = substr( $toaddr, $b+1 ) ;
				$b = strpos($toaddr, '>' ) ;
				if( $b!==false ) {
					$toaddr = substr( $toaddr, 0, $b );
				}
			}
			$toaddr = trim($toaddr);	
			if( !empty( $toaddr ) ) {
				fwrite($mxcli, "RCPT TO:<".$toaddr.">\r\n");
				if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
			}
		}

		fwrite($mxcli, "DATA\r\n");
		if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }
		// Send message data
		fwrite($mxcli, $body);
		if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

		// Quit connection
		fwrite($mxcli, "QUIT\r\n");
		if( sm_resp( $mxcli ) > 3 ) { $errors++ ; }

		fclose($mxcli);

		if( $errors==0 ) return true ;
		else return false ;
     }
?>
