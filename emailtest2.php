<?php
// emailtest2.php - testing email server configuration (2nd version)
//      by using sendmail.php
// Requests:
//      email parameter
//      smtpServer: smtp server
//      smtpServerPort: smtp port
//      security: security level, 0: starttls, 1: tls, 2: ssl
//      authenticationUserName: smtp login user
//      authenticationPassword: smtp login password
//      recipient: receiver
// Return:
//      JSON object, res=1 for success, msg: email test program output
// By Dennis Chen @ TME	 - 2019-03-29
// Copyright 2019 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");

    include 'sendmail.php' ;
	
	if( $logon ) {
        $resp['res'] = 0 ;
        $resp['msg'] = "Sending email failed!" ;
		if( !empty($_REQUEST['smtpServer']) &&
            !empty($_REQUEST['authenticationUserName']) &&
            !empty($_REQUEST['authenticationPassword']) &&
            !empty($_REQUEST['recipient']) ) {
				$mail_server = $_REQUEST['smtpServer'];
                $mail_port = intval($_REQUEST['smtpServerPort']) ;
                $mail_securetype = intval($_REQUEST['security']) ;
				$mail_username = $_REQUEST['authenticationUserName'] ;
				$mail_password = $_REQUEST['authenticationPassword'] ;
                if(sendmail($_REQUEST['recipient'], "", 
                    "TouchDownCenter Email Test", 
                    "Hi,\n\nIf you see this email, the email server has been setup correctly.") ) {
                        $resp['res'] = 1 ;
                        $resp['msg'] = "The email server works, please check on $_REQUEST[recipient] !" ;
                    }
        }
	}
	echo json_encode($resp);
?>