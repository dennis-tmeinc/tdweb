<?php
// drivebyreportpdf.php - review pdf report
// Request:
//      tag : drive tag file name (idx)
// Return:
//      pdf file
// By Dennis Chen @ TME	 - 2014-5-16
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require 'session.php' ;
	require 'vfile.php' ;

	if( $logon ) {
		
//		$tagfile = $driveby_eventdir.'/'.$_REQUEST['tag'] ;
//		$x = vfile_get_contents( $tagfile ) ;
//		if( $x ) {
//			$x = new SimpleXMLElement( $x );
//			if( empty($x) ) {
//				return ;
//			}
//		}
		
		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		$sql = "SELECT * FROM Drive_By_Event WHERE `idx` = $_REQUEST[tag] " ;
		if($result=$conn->query($sql)) {
			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$report_file = $driveby_eventdir. '/' . $row['report_file'] ;
			}
			$result->free();
		}
		
		// output pdf
		$pdf_buffer = vfile_get_contents( $report_file );
		if( $pdf_buffer ) {
			// inline output to browser
			header( "Content-Type: application/pdf" );  
			header( "Content-Disposition:inline; filename=\"". $row['report_file'] . "\"" );
			echo $pdf_buffer ;
		}
		else {
?>
<!DOCTYPE html>
<html>
<head></head>
<body>
<h> Sorry, this drive by report is not ready! (tag: <?php echo $x->busid. " - " . $x->time ; ?> ) </h>
</body>
</html>
<?php			
		}
	}
?>