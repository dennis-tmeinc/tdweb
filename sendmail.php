<?php
// sendmail.php - send email directly to receiver without smtp server
// By Dennis Chen @ TME  - 2014-07-21
// Copyright 2014 Toronto MicroElectronics Inc.
//
// 2022-12-6, use smtp server from $td_conf
// 2023-01-19, add attachment support on to http request
// 2023-02-03, add email server account on requests
// 2023-07-18, (0.6) add oauth2 support (works for gmail)

include 'config.php';

// x-mailer
$mail_x_mailer = "Touchdown sendmail 0.6 - 247 Security Inc" ;
// for EHLO cmd. mail.example.com is not a valid dns name but works on most server (as used by openssl)
$mail_domain = 'mail.example.com' ;

// smtp server credential
$mail_server = '' ;
$mail_port = 0 ;            // auto
$mail_securetype = 0 ;      // 0: STARTTLS, 1: tls
$mail_username = '' ;
$mail_password = '' ;
$mail_oauth = '' ;

// server capability
$mail_starttls = false ;
$mail_auth = 0 ;       // auth type, 1: login, 2: plain, 3: oauth2
$mail_8bitmime = false ;

// get oauth2 token
function oauth_token()
{
    global $mail_oauth ;

    $tokenfile = $mail_oauth."/token.json" ;
    $token=json_decode(file_get_contents($tokenfile),TRUE);
    if( empty($token['access_token']) || time() > intval($token['expires']) ) {
        // refresh token
        $client=json_decode(file_get_contents($mail_oauth."/client.json"),TRUE);
        $params = array(
            'client_id' => $client['web']['client_id'],
            'client_secret' => $client['web']['client_secret'],
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token'
        );
        if( !empty($token['scope']) ) {
            $params['scope'] = $token['scope'];
        }
        $ctx = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params)
                )
        );
        $response = file_get_contents( $client['web']['token_uri'] , false, stream_context_create($ctx) );
        $newtoken = json_decode($response, TRUE);
        // keep refresh_token
        $newtoken['refresh_token'] = $token['refresh_token'];
        $newtoken['expires'] = time() + $newtoken['expires_in'] - 60 ;
        file_put_contents($tokenfile, json_encode($newtoken) );
        return $newtoken['access_token'];
    }
    else {
        return $token['access_token'];
    }
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

// get name part of email address, ex dennis<dennisc@tme-inc.com> => dennis
function mail_name( $addr )
{
    $na='';
    $b = strpos($addr, '<' );
    if( $b === false ) {
        $a = strpos( $addr, '@');
        if( $a === false) {
            $na = $addr;
        }
        else {
            $na = substr($addr, 0, $a);
        }
    }
    else {
        $na = substr( $addr, 0, $b) ;
    }
    $na = trim($na);
    // unquote
    if(strlen($na)>1 && $na[0]=='"' && $na[-1] == '"') {
        return trim(substr( $na, 1, -1 ));
    }
    else {
        return $na;
    }
}

// mime packaging mail body
function mail_body( $to, $sender, $subject="", $message = "", $attachments = NULL )
{
    global $mail_x_mailer, $mail_8bitmime;

    // all mime content types I may use (strip from apache/conf/mime.types)
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

    // build email message body

    // need message id for gmail receiver
    $msgid = uniqid("TD-SENDMAIL-DENNISC-06-",true) . '@' . gethostname();

    // Main header
    $msg  = "From: $sender\r\n"
            ."To: $to\r\n"
            ."Message-Id: <$msgid>\r\n"
            ."Subject: $subject\r\n"
            ."Date: " .gmdate('r'). "\r\n"
            ."X-Mailer: $mail_x_mailer\r\n"
            ."MIME-Version: 1.0\r\n" ;

    $multipart = FALSE;
    // attachments
    if( !empty( $attachments ) ) {
        $multipart = TRUE;
        // multipart boundary
        $bdy = "--tdmailpart-".bin2hex(random_bytes(20)) ;
        // multipart/mixed, content will be ignored
        $msg .= "Content-Type: multipart/mixed; boundary=\"$bdy\"\r\n\r\n"
                ."This is a multi-part message in MIME format.\r\n"
                ."--$bdy\r\n" ;

        $attn = 1;
        foreach( $attachments as $att ) {
            if( !empty( $att['content'] ) ) {
                $content = $att['content'] ;
                if( !empty( $att['name'] ) ) {
                    $name = $att['name'] ;
                }
                else {
                    $name = "file$attn" ;
                }
            }
            else {
                $content = '';
                if( !empty( $att['name'] ) && file_exists( $att['name'] ) ) {
                    @$content = file_get_contents( $att['name'] ) ;
                    $name = basename($att['name']);
                }
            }
            if( !empty($content) ) {
                if( !empty( $att['type'] ) ) {
                    $content_type = $att['type'] ;
                }
                else {
                    $ext = strtolower( pathinfo($name, PATHINFO_EXTENSION) );
                    if( !empty( $mime_type[$ext] ) ) {
                        $content_type = $mime_type[$ext] ;
                    }
                    else {
                        $content_type = "application/octet-stream"  ;
                    }
                }
                $msg .= "Content-Type: $content_type; name=\"$name\"\r\n"
                        ."Content-Transfer-Encoding: base64\r\n"
                        ."Content-Disposition: attachment; filename=\"$name\"\r\n\r\n"
                        . chunk_split(base64_encode($content))
                        ."--$bdy\r\n";

                $attn = $attn + 1;
            }
        }
    }

    // message body
    if( substr($message,0,5) ==  "<html" ) {
        $content_type = "text/html" ;
    }
    else {
        $content_type = "text/plain" ;
    }
    $msg .= "Content-type: $content_type; charset=UTF-8\r\n" ;

    if( $mail_8bitmime && strlen($message) > 5000 ) {
        // use 8bit encoding
        $msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    }
    else {
        // quoted-printable
        $msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message = quoted_printable_encode( $message );
    }

    // dot-stuffing
    $msg .= str_replace("\r\n.", "\r\n..", $message );

    if( $multipart ) {
        // end of multipart
        $msg .= "\r\n--$bdy--";
    }
    // end of message
    $msg .= "\r\n";

    return $msg ;
}

// send smtp command , receive smpt response
function smtpcmd( $cli, $cmd = null )
{
    global $mail_username, $mail_oauth, $mail_starttls, $mail_auth, $mail_8bitmime;

    $ehlo = false;
    if( !empty($cmd) ){
        fwrite($cli, "$cmd\r\n");

        if( str_starts_with($cmd,'EHLO') ) {
            $ehlo = true;
            // reset capability
            $mail_starttls = false;
            $mail_auth = 0;
            $mail_8bitmime = false;
        }
    }
    while( $l = fgets( $cli ) ) {
        if( strlen($l)>3 ) {
            if( $ehlo ) {
                // check server capability
                $exts = explode(' ', trim(substr($l, 4)));
                if( $exts[0] == 'STARTTLS' ) {
                    $mail_starttls = true ;
                }
                else if( $exts[0] == '8BITMIME' ) {
                    $mail_8bitmime = true ;
                }
                else if( $exts[0] == 'AUTH' && !empty( $mail_username ) ) {
                    if( array_search('XOAUTH2', $exts) && !empty( $mail_oauth ) ) {
                        $mail_auth = 3 ;
                    }
                    else if( array_search('PLAIN', $exts) ){
                        $mail_auth = 2 ;
                    }
                    else if( array_search('LOGIN', $exts) ){
                        $mail_auth = 1 ;
                    }
                }
            }

            if( $l[3] == '-' )
                continue ;
            else
                return intval($l) ;
        }
        else {
            return intval($l) ;
        }
    }
    return 555 ;
}

// smtp send mail
function sendmail($to, $sender='', $subject = '', $message = '', $attachments = NULL )
{
    global $mail_domain;
    global $mail_server, $mail_port, $mail_securetype, $mail_username, $mail_password, $mail_oauth ;
    global $mail_starttls, $mail_auth;

    // fix $to addressses
    $to = str_replace(";", ',', $to );
    $to = str_replace("\n", ',', $to );
    $to = str_replace("\r", '', $to );

    // fix empty sender
    if( empty($sender) )
        $sender = $mail_username ;

    // group receipients by domain
    $domains = array() ;
    foreach( explode(",", $to) as $toaddr ) {
        $toaddr = mail_addr( $toaddr ) ;
        $at = strpos( $toaddr, '@' );
        if( !empty( $toaddr ) && $at ) {
            // domain part of the email address
            if( empty( $mail_server ) ) {
                $domain = strtolower( substr( $toaddr, $at+1 ) );
            }
            else {
                $domain = $mail_server; // if relay server available, send all rcpt to this server
            }
            if( empty( $domains[$domain] ) ) {
                $domains[$domain] = array() ;
            }
            $domains[$domain][] = $toaddr ;
        }
    }

    $totalerrors = 0;
    $errors = 0 ;

    // send out email
    foreach( $domains as $domain => $rcpts ) {

        $errors = 0 ;

        // if mail server not set, use MX records from domain
        if( $domain != $mail_server ) {
            // get mx record of receiver server
            $mxhosts = false ;
            if( getmxrr( $domain, $mxhosts ) ) {
                $domain = $mxhosts[0] ;
            }
        }

        // set default protocol / port
        if( $mail_securetype == 0 ) {           // 0: none/starttls
            $protocol = "tcp" ;
            if( empty($mail_port)) {
                // set defautl email pors
                if( empty($mail_server) ){
                    $mail_port = 25;            // Default port for relaying email on the internet (serverless)
                }
                else {
                    $mail_port = 587;           // The standard secure SMTP port (STARTTLS)
                }
            }
        }
        else {
            $protocol = "tls" ;
            if( empty($mail_port))
                $mail_port = 465;               // Deprecated SMTP ssl port
        }

        // allow unsafe tls
        // this is not necessary now since we moved away from TME server
        /*
        $sslctx = stream_context_create(array(
            'ssl' =>
                array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
        ));
        */

        $mxcli = stream_socket_client(
            "$protocol://$domain:$mail_port",
            $errno,
            $errstr,
            10
        );

        if($mxcli)
        {
            socket_set_timeout($mxcli, 30);

            // read greetings
            smtpcmd( $mxcli );

            // EHLO, code 250
            if( smtpcmd( $mxcli, "EHLO $mail_domain" ) >= 400 ) {
                $errors++ ;
            }

            // STARTTLS
            if( $mail_starttls && $protocol == "tcp" ) {
                if( smtpcmd( $mxcli, "STARTTLS" ) < 400 ) {
                    if( stream_socket_enable_crypto($mxcli, true,
                            STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT|STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT )) {
                        // EHLO again
                        if( smtpcmd( $mxcli, "EHLO $mail_domain" ) >= 300 ) {
                            $errors++ ;
                        }
                    }
                    else {
                        $errors++ ;
                    }
                }
                else {
                    $errors++ ;
                }
            }

            // AUTH
            if( $errors==0 ) {
                if( $mail_auth == 1){
                    // AUTH LOGIN
                    $errors++ ;
                    if( smtpcmd( $mxcli, "AUTH LOGIN" ) == 334 ) {
                        // user name challenge
                        if( smtpcmd( $mxcli, base64_encode($mail_username) ) == 334 ) {
                            // password challenge
                            if( smtpcmd( $mxcli, base64_encode($mail_password) ) == 235 ) {
                                $errors-- ;
                            }
                        }
                    }
                }
                else if( $mail_auth == 2){
                    // AUTH PLAIN
                    //   ref: rfc4616,  message   = [authzid] UTF8NUL authcid UTF8NUL passwd
                    $plain = "$mail_username\0$mail_username\0$mail_password";
                    if( smtpcmd( $mxcli, "AUTH PLAIN ".base64_encode($plain) ) != 235 ) {
                        $errors++ ;
                    }
                }
                else if( $mail_auth == 3 ) {
                    // AUTH XOAUTH2, ref: https://developers.google.com/gmail/imap/xoauth2-protocol
                    $access_token = oauth_token();
                    // sasl format: base64("user="{User}"^Aauth=Bearer "{Access Token}"^A^A")
                    $xoauth = "user=$mail_username\x01auth=Bearer $access_token\x01\x01";
                    if( smtpcmd( $mxcli, "AUTH XOAUTH2 ".base64_encode($xoauth) ) != 235 ) {
                        // not authenticated
                        $errors++ ;
                    }
                }
            }

            if( $errors == 0 ) {
                // from
                $from = mail_addr($sender) ;
                if( smtpcmd( $mxcli, "MAIL FROM:<$from>" ) >= 400 )
                    $errors++ ;
            }

            if( $errors == 0 )
            foreach( $rcpts as $rcpt ) {
                if( smtpcmd( $mxcli, "RCPT TO:<$rcpt>" ) >= 400 ) {
                    $errors++ ;
                }
            }

            if( $errors == 0 ) {
                if( smtpcmd( $mxcli, "DATA" ) < 400 ) {
                    // Send message body
                    if(empty($msgbody)){
                        $msgbody=mail_body( $to, $sender, $subject, $message, $attachments );
                    }
                    fwrite($mxcli, $msgbody);
                    // End of message body
                    if( smtpcmd( $mxcli, "." ) >= 400 ) {
                        $errors++ ;
                    }
                }
                else {
                    $errors++ ;
                }
            }

            // Quit smtp
            if( $errors == 0 ) {
                smtpcmd( $mxcli, "QUIT" );
            }
            fclose($mxcli);
        }
        else {
            $errors++;
        }

        if( $errors > 0 ) {
            $totalerrors++ ;
        }
    }

    return $totalerrors == 0  ;
}

// sending mail with user auth
function sendmail_secure($to, $sender='', $subject = '', $message = '', $attachments = NULL )
{
    global $mail_server, $mail_port, $mail_securetype, $mail_username, $mail_password, $mail_oauth;
    global $td_conf ;

    $senderName = '';
    $senderAddr = '';

    if( empty($mail_server) ) {
        // $td_conf file contains smtp server login info
        if( empty($td_conf) || !file_exists($td_conf) ){
            $td_conf = "/TouchDownCenter/conf/tdmail.conf" ;
        }
        if( file_exists($td_conf) ) {
            @$mailconf = simplexml_load_file( $td_conf );
            if( !empty($mailconf) && !empty( $mailconf -> emailserver ) ) {
                $mail_server = trim( $mailconf -> emailserver -> smtpServer );
                $mail_port = intval($mailconf -> emailserver -> smtpServerPort) ;
                $mail_securetype = intval($mailconf -> emailserver -> security) ;
                $mail_username = trim($mailconf -> emailserver -> authenticationUserName) ;
                $mail_password = trim($mailconf -> emailserver -> authenticationPassword) ;
                if( str_starts_with($mail_password, "base64,") ){
                    $mail_password = base64_decode( substr($mail_password, 7) );
                }
                $senderName = trim($mailconf -> emailserver -> senderName) ;
                $senderAddr = trim($mailconf -> emailserver -> senderAddr) ;
                // oauth2 support
                if( !empty($mailconf -> emailserver -> oauth) )
                    $mail_oauth = trim($mailconf -> emailserver -> oauth) ;
            }
        }
        global $conn ;
        if( empty($mail_server) && !empty($conn) ) { // still no server settings?
            // get server from database
            // single client hosts
            $sql = "SELECT * FROM tdconfig" ;
            if( $result=$conn->query($sql) ) {
                $mailer = $result->fetch_assoc() ;
                if( !empty($mailer['smtpServer']) ){
                    @$mail_server = $mailer['smtpServer'] ;
                    @$mail_port= intval( $mailer['smtpServerPort'] );
                    @$mail_securetype = intval( $mailer['security'] ) ;
                    @$mail_username=$mailer['authenticationUserName'] ;
                    @$mail_password=base64_decode( $mailer['authenticationPassword'] );
                    @$senderName = $mailer['senderName'] ;
                    @$senderAddr = $mailer['senderAddr'] ;
                }
            }
        }
    }

    // if mail_username is a email address, as for gmail and outlook mail
    //    replace sender addr
    if( !empty($mail_username) && strpos($mail_username, '@')>0 ){
        $senderAddr = $mail_username;       // force to used account name as sender email, to avoid been considered as scam
        if( !empty($sender) ) {
            // also replace sender name
            $senderName = mail_name($sender);
            $sender='';     // to reconstruct sender
        }
    }
    // reconstruct sender
    if( empty($sender) && !empty($senderAddr)){
        if( empty($senderName) ) {
            $sender = $senderAddr;
        }
        else {
            $sender = "\"$senderName\" <$senderAddr>";
        }
    }
    return sendmail($to, $sender, $subject, $message, $attachments );
}

// send mail over https://dvp.my247now.com
function sendmail_https($to, $sender='', $subject = '', $message = '', $attachments = NULL )
{
    $params = array(
        'to' => $to,
        'from' => $sender,
        'subject' => $subject,
        'message' => $message
    );
    if( !empty( $attachments ) ){
        if( is_string($attachments) ) {
            $params['attachment'] = $attachments;
        }
        else if( is_array($attachments) ) {
            $atts = array();
            $attnames = array();
            for($i=0;$i<50;$i++) {
                if( !empty($attachments[$i]['content'] ) ) {
                    $atts[] = $attachments[$i]['content'];
                    if(!empty($attachments[$i]['name'])){
                        $attnames[]=$attachments[$i]['name'];
                    }
                    else {
                        $attnames[]='';
                    }
                }
            }
            $params['attachment'] = $atts ;
            $params['attachment_name'] = $attnames ;
        }
    }

    $ctx = array(
        'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            )
    );
    $response = file_get_contents( "https://dvp.my247now.com/dvp/sendmail.php", false, stream_context_create($ctx) );
    return $response == "Mail Sent!" ;
}

// direct https call to send email
// send over https request
if( basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)  &&
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] == 'on' &&
    !empty($_REQUEST['to']) ) {

    $sender='';
    if( !empty($_REQUEST['from']) ) {
        $sender = $_REQUEST['from'];
    }

    $subject="No subject";
    if(!empty($_REQUEST['subject'])){
        $subject=$_REQUEST['subject'];
    }

    $message = "";
    if( !empty($_REQUEST['message']) ) {
        $message = $_REQUEST['message'];
    }

    $attachment = NULL;
    if( !empty($_REQUEST['attachment']) ) {
        $attachment=array();
        if( is_array($_REQUEST['attachment']) ) {       // multiple attachment?
            for($i=0;$i<20;$i++) {
                if(!empty($_REQUEST['attachment'][$i])){
                    $attachment[$i]['content'] = $_REQUEST['attachment'][$i];
                    if( !empty($_REQUEST['attachment_name'][$i]) ){     // attachment name
                        $attachment[$i]['name'] = $_REQUEST['attachment_name'][$i];
                    }
                }
                else {
                    break;
                }
            }
        }
        else if( is_string($_REQUEST['attachment']) ) {
            $attachment[0]['content'] = $_REQUEST['attachment'];
            if( !empty($_REQUEST['attachment_name']) ){ // atattachment (file) name
                $attachment[0]['name'] = $_REQUEST['attachment_name'];
            }
        }
    }

    if( !empty($_REQUEST['server'])  &&
        !empty($_REQUEST['user'])  &&
        !empty($_REQUEST['password']) ) {
        $mail_server = $_REQUEST['server'] ;
        $mail_username = $_REQUEST['user'] ;
        $mail_password = $_REQUEST['password'] ;
    }

    if( !empty($_SERVER['SERVER_NAME'])
        && $_SERVER['SERVER_NAME'][0] >='a'
        && $_SERVER['SERVER_NAME'][0] <='z' ) {
        $mail_domain = $_SERVER['SERVER_NAME'];
    }

    if(sendmail_secure($_REQUEST['to'], $sender, $subject, $message, $attachment )) {
        echo "Mail Sent!";
    }
    else {
        echo "Failed!";
    }
}