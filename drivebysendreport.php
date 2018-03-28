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
	header("Content-Type: application/json");
	if( $logon ) {
		require 'vfile.php' ;
		require "sendmail.php" ;
		
		// save to/from addresses
		$email = array();
		$email['to'] = $_REQUEST['to'] ;
		$email['from'] = $_REQUEST['from'] ;
		$email['notes'] = $_REQUEST['notes'] ;
		file_put_contents( $driveby_eventdir.'/email.conf', json_encode($email) );

		$tagfile = $driveby_eventdir.'/'.$_REQUEST['tag'] ;
		$x = vfile_get_contents( $tagfile ) ;
		if( $x ) {
			$x = new SimpleXMLElement( $x );
			if( empty($x) ) {
				return ;
			}
		}
		
		$pdffile = substr( $tagfile, 0, strrpos($tagfile, '.') ).".pdf" ;
		$reportname = str_replace( ' ', '_', "Report_". $x->busid. "_". $x->time . ".pdf" ) ;

		$pdf_buffer = file_get_contents( $pdffile );
		
		if( is_file( $pdffile ) ) {
			$subject = "Stop-Arm Drive-By Violation Report" ;
			$message = "Stop-Arm Drive-By Violation Report\n\n" .
					   "  Date-Time: ".$x->time . "\n" .
					   "  Plate of Violator: ".$x->plateofviolator. "\n\n" ;
			if( !empty( $email['notes'] ) ) {
				$message .= "Notes:\n". $email['notes'] ; 
			}
			
			if( sendmail( $_REQUEST['to'], $_REQUEST['from'], $subject, $message, $pdffile ) ) {
				$resp['res'] = 1 ;
			}
		}

	}
	
	echo json_encode($resp);
?>