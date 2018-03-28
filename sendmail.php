<?php
// sendmail.php - send email directly to receiver without smtp server
// By Dennis Chen @ TME	 - 2014-06-15
// Copyright 2014 Toronto MicroElectronics Inc.
//
	// x-mailer
	$x_mailer = "Touchdown sendmail 0.1 - 247 Security Inc" ;

	function sendmail($to, $sender, $subject, $message, $attachments )
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
		$to = str_replace("\r", "", $to );
		$to = str_replace("\n", " ", $to );
		$to = str_replace(";", ",", $to );

		// fix $subject (no newlines)
		$subject = str_replace("\r", "", $subject );
		$subject = str_replace("\n", " ", $subject );
	
		// build email message
		$bdy = uniqid("",true);

		// Main header
        $msg  = "From: " . $sender . "\r\n" ;
        $msg .= "To: " . $to . "\r\n" ;
        $msg .= "Subject: " . $subject . "\r\n" ; 
        $msg .= "Date: " . date("D, d M Y H:i:s O") . "\r\n" ;
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
		if( is_array( $attachments ) ) {
			$aa = $attachments ;
		}
		else {
			$aa = array( $attachments ) ;
		}
		foreach( $aa as $att ) {
			if( is_file( $att ) )
				$content = file_get_contents( $att ) ;
			if( !empty($content) ) {
				$ext = strtolower( substr( $att, strrpos( $att, '.' )+1 ) );
				if( !empty( $mime_type[$ext] ) ) {
					$content_type = $mime_type[$ext] ;
				}
				else {
					$content_type = "application/octet-stream" ;
				}
				$msg .= "Content-Type: " .$content_type. "; name=\"".basename($att)."\"\r\n";
				$msg .= "Content-Transfer-Encoding: base64\r\n";
				$msg .= "Content-Disposition: attachment; filename=\"".basename($att)."\"\r\n\r\n";
				$msg .= chunk_split(base64_encode($content));
				$msg .= "\r\n--".$bdy."\r\n";
			}
		}
		
		// message body
		$msg .= "Content-type:text/plain; charset=UTF-8\r\n";
		$msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		
		// quoted printable
		$message = quoted_printable_encode( $message );
		if( !empty( $message ) ) {
			$message = str_replace("\n.", "\n..", $message );
		}
		$msg .= $message;
		
		// end of last part and end of message
		$msg .= "\r\n--".$bdy."--\r\n\r\n.\r\n";

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
			// domain part
			$domain = substr( $toaddr, strpos($toaddr, '@')+1 );
			if( empty( $domains[$domain] ) ) {
				$domains[$domain] = array() ;
			}
			$domains[$domain][] = $toaddr ;
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

				fwrite($mxcli, "HELO [". $myip. "]\r\n" );
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
				fwrite($mxcli, $msg);
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

?>
