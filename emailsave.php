<?php
// emailsave.php - save email setup parameter 
// Requests:
//      email parameter
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2014-02-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		if( $_SESSION['user_type'] == "admin" ) {		// admin only
			
			if( empty( $_REQUEST['sendSummaryDaily'] ) ) {
				$_REQUEST['sendSummaryDaily']='0';
			}
			else {
				$_REQUEST['sendSummaryDaily']='1';
			}
			
			if( empty( $_REQUEST['tmSendDaily'] ) ) {
				$_REQUEST['tmSendDaily']='2013-01-01 19:00:00';
			}
			else {
				$tmSendDaily = new DateTime( $_REQUEST['tmSendDaily'] );
				$_REQUEST['tmSendDaily']=$tmSendDaily->format('Y-m-d H:i:s');
			}
			
			// escaped string for SQL
			$esc_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$esc_req[$key]=$conn->escape_string($value);
			}

			$sql="UPDATE tdconfig SET ".
			"smtpServer = '$esc_req[smtpServer]', ".
			"smtpServerPort = '$esc_req[smtpServerPort]',security = '$esc_req[security]',recipient = '$esc_req[recipient]',".
			"authenticationUserName = '$esc_req[authenticationUserName]', " .
			"authenticationPassword = '" . base64_encode($_REQUEST['authenticationPassword']) . "', ".
			"senderAddr = '$esc_req[senderAddr]',alertRecipients = '$esc_req[alertRecipients]', ".
			"senderName = '$esc_req[senderName]',sendSummaryDaily='$esc_req[sendSummaryDaily]',tmSendDaily='$esc_req[tmSendDaily]' ;";

			if( $conn->query($sql) ) {
				if( !empty($esc_req["panicAlertRecipients"]) ) {
					$sql="UPDATE tdconfig SET panicAlertRecipients = '$esc_req[panicAlertRecipients]' ";
					$conn->query($sql) ;				
				}
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="SQL error: ".$conn->error ;
			}			
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>