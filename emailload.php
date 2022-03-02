<?php
// emailload.php - load email settings
// Requests:
//      none
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-06-21
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		// $sql="SELECT smtpServer,smtpServerPort,security,recipient,authenticationUserName,senderAddr,alertRecipients,panicAlertRecipients,sendSummaryDaily,senderName,tmSendDaily FROM tdconfig ;" ;
		$sql="SELECT * FROM tdconfig" ;
		if( $result=$conn->query($sql) ) {
			$resp['email'] = $result->fetch_assoc() ;
			// $resp['email']['authenticationPassword'] = '********' ;	// empty password
			if( !empty( $resp['email']['authenticationPassword'] ) ) {
				$resp['email']['authenticationPassword'] = base64_decode( $resp['email']['authenticationPassword'] );
			}
			if( !empty($resp['email']['tmSendDaily']) ) {
				$tmSendDaily = new DateTime( $resp['email']['tmSendDaily'] );
				$resp['email']['tmSendDaily'] = $tmSendDaily->format("H:i");
			}
			else {
				$resp['email']['tmSendDaily'] = '19:00';
			}
			$resp['res'] = 1 ;
		}
	}
	echo json_encode( $resp );

?>