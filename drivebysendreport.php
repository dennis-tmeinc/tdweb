<?php
// drivebysendreport.php - send report through email
// Request:
//      tag: tag name 
//		from: sender email address
//		to:  receiver email addresses
//      notes: notes to add on emails
// Return:
//      json 
// By Dennis Chen @ TME	 - 2014-05-26
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//
    require 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	require_once "sendmail.php" ;

	header("Content-Type: application/json");
	
	if( $logon ) {
		// save to/from addresses
		$email = array();
		$email['to'] = $_REQUEST['to'] ;
		$email['from'] = $_REQUEST['from'] ;
		$email['notes'] = $_REQUEST['notes'] ;
		vfile_put_contents( $driveby_eventdir.'/email.conf', json_encode($email) );

		$sql = "SELECT * FROM drive_by_event WHERE `idx` = $_REQUEST[tag] " ;
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$report_file = $driveby_eventdir. '/' . $row['report_file'] ;
			}
			$result->free();
		}
		
		// output pdf
		$pdf_buffer = vfile_get_contents( $report_file );
		if( $pdf_buffer ) {
			$subject = "Stop-Arm Drive-By Violation Report" ;
			$message = "Stop-Arm Drive-By Violation Report\n\n" .
					   "  Date-Time: ".$row['Date_Time'] . "\n" .
					   "  Plate of Violator: ".$row['Plateofviolator']. "\n\n" ;
			if( !empty( $email['notes'] ) ) {
				$message .= "Notes:\n". $email['notes'] ; 
			}
			
			if( empty($use_tdcmail) ) {
				$attachment=array();
				$attachment[0]['name'] = "DriveByReport.pdf" ;
				$attachment[0]['content'] = $pdf_buffer ;
				if( sendmail_secure( $_REQUEST['to'], $_REQUEST['from'], $subject, $message, $attachment ) ) {
					$resp['res'] = 1 ;
				}
			}
			else {
				// use tdc mail server through tdc.my247now.com
				$content = array(
					'to' => $email['to'],
					'subject' => $subject,
					'message' => $message,
					'attachment_name' => "DriveByReport.pdf",
					'attachment' => $pdf_buffer
				);
				$sslctx = stream_context_create(array(
					'http' =>
						array(
							'method'  => 'POST',
							'header'  => 'Content-type: application/x-www-form-urlencoded',
							'content' => http_build_query($content)
						),
					'ssl' =>
						array(
							'verify_peer_name' => false,
							'allow_self_signed' => true
						)
						));
				$rsp = file_get_contents( 'https://tdc.my247now.com/tdc/sendmail.php', false, $sslctx );
				if( $rsp == "Mail Sent!" ) {
					$resp['res'] = 1 ;
				}
			}

			if( $resp['res']  ) {
				// update event status
				$sql = "UPDATE drive_by_event SET `email_status` = 'Sent', `sentto` = '$_REQUEST[to]'  WHERE `idx` = $_REQUEST[tag] " ;
				$conn->query($sql) ;
			}
		}

	}
	
	echo json_encode($resp);
?>