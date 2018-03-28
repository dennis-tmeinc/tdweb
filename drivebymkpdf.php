<?php
// drivebymkpdf.php - generate pdf report
// Request:
//      tag : drive tag file name
//      plateofviolator : plate number of violator vehicle
//		notes : event notes
// Return:
//      mp4 stream
// Request:
//      tag: tag name (filename)
//      plateofviolator: plate number of violator
//      notes: notes of this violator
// By Dennis Chen @ TME	 - 2014-5-16
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require 'session.php' ;
	require_once 'vfile.php' ;
	require_once 'drivebyframefunc.php' ;

	if( $logon ) {
		
		// update notes to lasttag file 
		$tagfile = $driveby_eventdir.'/'.$_REQUEST['tag'] ;
		$x = vfile_get_contents( $tagfile ) ;
		if( $x ) {
			$x = new SimpleXMLElement( vfile_get_contents( $tagfile ) );
			if( empty($x) ) {
				return ;
			}
		}
		
		// update notes if neccessary
		if( $_REQUEST['plateofviolator']!=$x->plateofviolator || $_REQUEST['notes']!=$x->notes ) {
			$x->plateofviolator = $_REQUEST['plateofviolator'] ;
			$x->notes = $_REQUEST['notes'] ;
		}
		$x->status = "processed" ;
		vfile_put_contents( $tagfile, $x->asXML() );
		
		require('fpdf/fpdf.php');

		class MPDF extends FPDF
		{
			// Page header
			function Header()
			{
				// Logo
				$this->Image('res/TD-StopArm-Drive-By-Event-HEADER-247Logo.jpg', 12,8, 0, 20 );
				$this->Image('res/TD-StopArm-Drive-By-Event-HEADER-TouchDownLogo.jpg',168,8, 0, 20);
				
				$this->SetFont('Arial','',18);
				$this->Cell(0,10,'Stop-Arm Drive-By Violation Report',0,0,'C');
				
				$this->Line( 12, 32, 200, 32 );
				// Line break
				$this->Ln(28);
			}

			// Page footer
			function Footer()
			{
				// Position at 1.5 cm from bottom
				$this->SetY(-15);
				// Arial italic 8
				$this->SetFont('Arial','',10);
				// Page number
				$this->Cell(100,10,'247 Security Inc.',0,0,'L');
								
				$now = new DateTime();
				$this->Cell(88,10,'Page '.$this->PageNo().'/{nb} Created '.$now->format(DATE_RFC2822),0,0,'R');
			}
		}

		// Instanciation of inherited class
		$pdf = new MPDF();
		$pdf->SetAutoPageBreak(false, 12);
		$pdf->AliasNbPages();
		$pdf->AddPage();
		
		$pdf->SetFont('Arial','',12);
		$h=6 ;
		
		// images
		$px = $pdf->GetX() ;
		$py = $pdf->GetY() ;
		
		for( $i=0; $i<4; $i++ ) {
			$chn = $_REQUEST['ch'.$i] ;
			$pos = $_REQUEST['pos'.$i] ;
			if( $i==2 ) {
				// advance to 2nd line
				$pdf->Ln(1);
				$py = $pdf->GetY();
			}
			if( $i%2 == 0 ) {
				$xx = $px ;
				$yy = $py ;
			}
			else {
				$xx = $px + 96 ;
				$yy = null ;
			}
			set_time_limit(60) ;
			$fn = drivebysframe($tagfile, $chn, $pos ) ;
			if( $fn ) {
				$link = "http://".$_SERVER['HTTP_HOST']. dirname( $_SERVER['REQUEST_URI'] ). "/drivebyvideo.php?tag=". $_REQUEST['tag']."&channel=".$chn ;
				$pdf->Image($fn, $xx, $yy, 94, 0, "JPEG", $link);
				// delete this temp file
				@unlink( $fn );
			}
		}
		
		// map image
		$pdf->Cell( 0, $h, "MAP:", 0, 1 );
		$mapurl = "http://dev.virtualearth.net/REST/v1/Imagery/Map/Road/".$x->coordinate."/".$_REQUEST['mapzoom']."?pp=".$x->coordinate.";0&ms=720,200&key=" . $map_credentials ;
		$pdf->Image($mapurl, null, null, 0, 0, "JPEG", "http://www.bing.com/maps/?where=".urlencode($x->coordinate) );
		$pdf->Ln(3) ;

		$pdf->Cell( 0, $h, "Active Sensors: ".$x->sensors, 0, 1 );
		
		// y position for notes
		$notey = $pdf->GetY();

		$pdf->Cell( 0, $h, "Date-Time: ".$x->time, 0, 1 );
		$pdf->Cell( 0, $h, "Fleet Vehicle ID: ".$x->busid, 0, 1 );
		$pdf->Cell( 0, $h, "Vehicle Status: Stopped", 0, 1 );
		$pdf->Cell( 0, $h, "Plate of Violator: ".$x->plateofviolator, 0, 1 );
		$pdf->Cell( 0, $h, "Coordinate: ".$x->coordinate, 0, 1 );
		
		// retrieve address
		$addrurl = "http://dev.virtualearth.net/REST/v1/Locations/".$x->coordinate."?o=json&key=".$map_credentials ;
		$addr = file_get_contents( $addrurl );
		$addr = json_decode( $addr, true );
		if( !empty(  $addr['resourceSets'][0]['resources'][0]['address']['formattedAddress'] ) ) {
			$xaddr = $addr['resourceSets'][0]['resources'][0]['address']['formattedAddress'] ;
			$pdf->Cell( 0, $h, "Address: ".$xaddr, 0, 1 );
		}
				
		// print Notes
		if( !empty( $x->notes ) ) {
			$pdf->SetY($notey);
			$pdf->Cell(100);
			$pdf->Cell( 0, $h, "Notes: ", 0, 1 );
			$pdf->Cell(110);
			$pdf->MultiCell( 80, $h, $x->notes, 0, 1 );
		}
		
		// output pdf
		$pdffile = $driveby_eventdir. '/' . substr( $_REQUEST['tag'], 0, strpos($_REQUEST['tag'],'.') ).".pdf" ;
		$reportname = str_replace( ' ', '_', "Report_". $x->busid. "_". $x->time . ".pdf" ) ;

		$pdf_buffer = $pdf->Output($pdffile, "S");

		// inline output to browser
		header( "Content-Type: application/pdf" );  
		header( "Content-Disposition:inline; filename=\"". $reportname . "\"" );
		echo $pdf_buffer ;
		
		file_put_contents( $pdffile, $pdf_buffer );
		
	}
?>