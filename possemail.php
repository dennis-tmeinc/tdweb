<?php

  /*
  ------------------------------------------------------------------------------
  
  Title       :     email()
  Version     :     0.9.2
  Author      :     Jason Jacques <jtjacques@users.sourceforge.net>
  URL         :     http://poss.sourceforge.net/email
  
  Description :     PHP mail() clone with build in MTA
                    Returns TRUE or FALSE depending on delivery status.

  Usage       :     email(to, subject, message [, headers [, parameters]])
                    Set $ev_verbose = TRUE in your PHP script for verbose error
                    output. See documentation for more details.
                      
  Copyright   :     2005, 2006 Jason Jacques
  License     :     MIT License
  
  Created     :     15/06/2005
  Modified    :     07/02/2006
  
  Key Updates :     * Changed to MIT License
  
                    + Fixed default DNS
                    + Removed requirement for internal str_pad() function
                    + Fixed faulty assumption of RFC 822 in address parsing
                    + Added header cleaning to prevent headers showing in
                      message
                    + Added chunk_split() as alternitive to wordwrap()
                    + Prevented time-out occuring due to DNS error
                    + Added X-Mailer: header
                    + Added Date: header
                    
  Notes       :     The default DNS server is 4.2.2.1 provided by Verizon via
                    Level 3 Communications, Inc. for the purpouse of testing
                    email() it is suggested that the default DNS is changed to
                    your DNS server if this script is to be utilised in a
                    production environment for both performance and [potential]
                    legal reasons.
  
  ------------------------------------------------------------------------------
  */
  
  /*
	This file has been modified by Dennis@tme-inc.com
  */
  
  // Set email() version
  
  $ev_version = "0.9.2";

  // Check functions have not been created before.
  if(!function_exists("email"))
  {
    
    // Main function - see documentation for useage details
    function email($to, $sender, $subject, $message, $headers="")
    {
      // Initialise variables to 0
      $user_no = 0; $ret_error = 0;
      
      // Decode formatted user and sender data
      $user    = esf_decode_to($to);
      
      // Clean up message data, prevent header injection
      $msg     = esf_clean_msg($message);
      $subject = esf_clean_subject($subject);
      
      // Create final message, RFC2822 complient
      $header  = esf_create_header($to, $sender, $subject, $headers);
      $msg     = esf_create_msg($header, $msg);
      
      // Send message to each user identified
      while(@$user[$user_no]['address'] != NULL)
      {
        // Send message, check return code for errors
        $errors[$user_no] = esf_send_msg($user[$user_no], $sender, $msg);
        
        // Check error 
        if($errors[$user_no] > 0)
          $ret_error++;
          
        // Advance to next recipient
        $user_no++;
      }
      
      if($ret_error == 0)
        return TRUE;
      
      //return $errors; 
      return FALSE;
    }
	
	// email with multipart contents (attachments), 	---- add by dennisc@tme-inc.com
	function email_multipart($to, $sender, $subject, $message, $attachments )
	{
		$bdy = uniqid("",true);

		// Customize X-Mailer
		$header = "X-Mailer: Touchdown email sender \r\n" ;

		// MIME header
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"".$bdy."\"\r\n\r\n";
		$header .= "This is a multi-part message in MIME format.";
		$header .= "\r\n--".$bdy."\r\n";

		// attachments
		if( is_array( $attachments ) ) {
			$aa = $attachments ;
		}
		else {
			$aa = array( $attachments ) ;
		}
		foreach( $aa as $att ) {
			$content = file_get_contents( $att ) ;
			if( $content ) {
				$header .= "Content-Type: application/octet-stream; name=\"".basename($att)."\"\r\n";
				$header .= "Content-Transfer-Encoding: base64\r\n";
				$header .= "Content-Disposition: attachment; filename=\"".basename($att)."\"\r\n\r\n";
				$header .= chunk_split(base64_encode($content));
				$header .= "\r\n--".$bdy."\r\n";
			}
		}
		
		// message body
		$header .= "Content-type:text/plain; charset=UTF-8\r\n";
		$header .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$header .= quoted_printable_encode ( $message );
		// last part
		$header .= "\r\n--".$bdy."--\r\n";

		return email($to, $sender, $subject, "", $header) ;
	}

    // email() support functions (esf_)
    
    // Seperate individual 'to' addresses
    function esf_decode_to($to)
    {
      // Initialise variables to 0
      $user_no = 0;

      // Sepeate 'to' user strings
      $to = explode(",", $to);
      
      while(@$to[$user_no] != NULL)
      {
        // Strip white space begining and end
        $to[$user_no] = trim($to[$user_no]);
        
        // Check if contains plain text name (and address in angled brackets)
        if(strpos($to[$user_no], "<") === FALSE)
        {
          // Assume email address
          $users[$user_no]['address'] = $to[$user_no];
        }
        else
        {
          // Seperate plain text name and email address
          $users[$user_no]['name']    =
                  trim(substr($to[$user_no], 0, strpos($to[$user_no], "<")));
          $users[$user_no]['address'] =
                  trim(substr($to[$user_no], strpos($to[$user_no], "<")+1, -1));
        }
        
        // Advance to next address
        $user_no++;
      }
      
      return $users;
    }
    
    // Clean up message in preperation to be sent
    function esf_clean_msg($msg)
    {
      // Convert '\r\n' into '\n' (prevent '\r\n.\r\n' error) and wordwrap
      $msg = str_replace("\r\n", "\n", $msg);
      if(function_exists("wordwrap"))
      {
        // Comply with reccomendations
        $msg = wordwrap($msg, 70, "\n");
      }
      else
      {
        // Prevent breach
        $msg = chunk_split($msg, 990, "\n");
      }
      
      return $msg;
    }
    
    // Clean up subject line (*Security related*)
    function esf_clean_subject($subject)
    {
      // Strip out '\r' and '\n' to prevent header injection
      $subject = str_replace("\r", "", str_replace("\n", "", $subject));
      
      return $subject;
    }
    
    // Create a RFC2822 complient header
    function esf_create_header($to, $sender, $subject, $header)
    {
      // Clean user defined headers
      $header = trim($header);
      
      // Add 'Subject:' header if missing
      if(strpos($header, "Subject:") === FALSE)
        $header = "Subject: " . $subject . "\r\n" . $header; 
      
      // Add 'From:' header if missing
      if(strpos($header, "From:") === FALSE)
        $header = "From: " . $sender . "\r\n" . $header;
      
      // Add 'To:' header if missing
      if(strpos($header, "To:") === FALSE)
        $header = "To: " . $to . "\r\n" . $header;
      
      // Add 'Date:' header if missing
      if(strpos($header, "Date:") === FALSE)
        $header = "Date: " . date("D, d M Y H:i:s O") . "\r\n" . $header;
        
      // Trim and finish header
      $header = trim($header) . "\r\n\r\n";     
      
      return $header;
    }
    
    // Create final RFC2822 compliant message
    function esf_create_msg($headers, $msg)
    {
      // Attach header to message and finalise
      $msg = $headers . $msg . "\r\n.\r\n";
      
      return $msg;
    }
    
    function esf_send_msg($to, $from, $msg)
    {
      // Initialise variables to 0
      $errors = 0;

	  $domain = $to['address'] ;
      if(strpos($domain, "@") !== false)
        $domain = substr($domain, (strpos($domain, "@")+1));
	
	  $mxhosts = false ;
	  getmxrr( $domain, $mxhosts );
	  
	  echo "MXHOST: ".$mxhosts[0] ;
	  
      // Open socket connection to SMTP server
      @$rcv_server = fsockopen($mxhosts[0], 25, $null, $null2, 5);
                                                        
      if(@$rcv_server)
      {
        socket_set_timeout($rcv_server, 5);
        
        // Handshake connection and prepare for message delivery
        $errors += (esf_read_response($rcv_server) * 1);
        fwrite($rcv_server, "HELO " . $_SERVER['HTTP_HOST']. "\r\n");
        $errors += (esf_read_response($rcv_server) * 2);
        fwrite($rcv_server, "MAIL FROM:<" . $from . ">\r\n");
        $errors += (esf_read_response($rcv_server) * 4);
        fwrite($rcv_server, "RCPT TO:<" . $to['address'] . ">\r\n");
        $errors += (esf_read_response($rcv_server) * 8);
        fwrite($rcv_server, "DATA\r\n");
        $errors += (esf_read_response($rcv_server) * 16);
        
        // Send message data
        fwrite($rcv_server, $msg);
        $errors += (esf_read_response($rcv_server) * 32);
        
        // Close connection
        fwrite($rcv_server, "QUIT\r\n");
        $errors += (esf_read_response($rcv_server) * 64);
        fclose($rcv_server);
      }
      else
      {
        // On connection error set all to 'Failed'
        return 127;
      }
      
      return $errors;
    }
    
    // Read response to data/command sent
    function esf_read_response($buffer)
    {
      // Get response code from the stream buffer
      $response_code = fgets($buffer);
      
      // If code's first digit is 4 or greater, and error has occured
      if((substr($response_code, 0, 1) > 3) || ($response_code == NULL))
        return 1;
      
      // Otherwise no error occured
      return 0;
    }
    
  }

?>
