<?php
// drivebyreportpdf.php - review pdf report
// Request:
//      tag : drive tag file name
// Return:
//      pdf file
// By Dennis Chen @ TME	 - 2014-5-16
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require 'session.php' ;
	require 'vfile.php' ;

	if( $logon ) {
		
		$tagfile = $driveby_eventdir.'/'.$_REQUEST['tag'] ;
		$x = vfile_get_contents( $tagfile ) ;
		if( $x ) {
			$x = new SimpleXMLElement( $x );
			if( empty($x) ) {
				return ;
			}
		}
		
		// output pdf
		$pdffile = substr( $tagfile, 0, strrpos($tagfile, '.') ).".pdf" ;
		$reportname = str_replace( ' ', '_', "Report_". $x->busid. "_". $x->time . ".pdf" ) ;

		$pdf_buffer = file_get_contents( $pdffile );
		
		if( $pdf_buffer ) {
			// inline output to browser
			header( "Content-Type: application/pdf" );  
			header( "Content-Disposition:inline; filename=\"". $reportname . "\"" );
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