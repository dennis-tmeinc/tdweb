<?php
// drivebymkreport.php - generate pdf report
// Request:
//      tag : drive tag file name(idx)
// 		mapzoom: map zoom level
//      ch0:  channel name	
//      pos0: video position
//      ch1:  channel name	
//      pos1: video position
//      ...
// Return:
//      mp4 stream
// Request:
//      tag: tag name (filename)
//      plateofviolator: plate number of violator
//      notes: notes of this violator
// By Dennis Chen @ TME	 - 2014-7-16
// Copyright 2013 Toronto MicroElectronics Inc.
//

	require 'session.php' ;
	require_once 'vfile.php' ;
	require_once 'drivebyframefunc.php' ;
		
	header("Content-Type: application/json");
	
	if( $logon ) {

		// update notes to lasttag file 
//		$tagfile = $driveby_eventdir.'/'.$_REQUEST['tag'] ;
//		$x = file_get_contents( $tagfile ) ;
//		if( $x ) {
//			$x = new SimpleXMLElement( file_get_contents( $tagfile ) );
//			if( empty($x) ) {
//				return ;
//			}
//		}
		
		$sql = "SELECT * FROM Drive_By_Event WHERE `idx` = $_REQUEST[tag] " ;
		$channels = array();
		if($result=$conn->query($sql)) {

			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$x = new SimpleXMLElement( "<driveby>" . $row['Video_Files'] . "</driveby>" );
				$y = json_decode( json_encode( $x ), true );
				
				for( $i=0; $i<16; $i++ ) {
					if( !empty( $y['channel'][$i]['name'] ) ) {
						$channels[ $y['channel'][$i]['name'] ] = $y['channel'][$i]['video'] ;
					}
					else {
						break ;
					}
				}
				$event = $row ;
			}
			$result->free();
		}
		if( empty( $event ) || empty($channels) ) {
			return ;		// error
		}
		
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
			
			$pos = $_REQUEST['pos'.$i] ;
			$videofile = $channels[ $_REQUEST['ch'.$i] ] ;
			$imgfile = $driveby_eventdir."/frame".md5($videofile.$pos).".jpg" ;
			$pos += 0.03 ;
			
			$pvid = escapeshellarg( $videofile );
			$pimg = escapeshellarg( $imgfile );		
			$cmdline = "bin\\ffmpeg.exe -ss $pos -i $pvid -frames 1 $pimg" ;
			$eoutput = array();
			$eret = 1 ;
			vfile_exec( $cmdline, $eoutput, $eret ) ;
			if( $eret==0 && vfile_isfile( $imgfile ) ) {
				$videofilelink = mcrypt_encrypt( MCRYPT_BLOWFISH, "drivebyvideolink", $videofile, "ecb" ) ;
				$link = "http://".$_SERVER['HTTP_HOST']. dirname( $_SERVER['REQUEST_URI'] ). "/drivebyvideo.php?link=".rawurlencode(base64_encode($videofilelink)) ;
				$pdf->Image( vfile_url($imgfile), $xx, $yy, 94, 0, "JPEG", $link);
				// delete temp img file
				vfile_unlink( $imgfile );
			}
		}
		
		// map image
		$pdf->Cell( 0, $h, "MAP:", 0, 1 );
		$mapurl = "https://dev.virtualearth.net/REST/v1/Imagery/Map/Road/".$event['Lat'].','.$event['Lon']."/".$_REQUEST['mapzoom']."?pp=".$event['Lat'].','.$event['Lon'].";0&ms=720,200&key=" . $map_credentials ;
		$pdf->Image($mapurl, null, null, 0, 0, "JPEG", "https://www.bing.com/maps/?where=".urlencode($event['Lat'].','.$event['Lon']) );
		$pdf->Ln(3) ;

		$pdf->Cell( 0, $h, "Active Sensors: ".$event['Sensor_Status'], 0, 1 );
		
		// y position for notes
		$notey = $pdf->GetY();

		$pdf->Cell( 0, $h, "Date-Time: ".$event['Date_Time'], 0, 1 );
		$pdf->Cell( 0, $h, "Fleet Vehicle ID: ".$event['Bus_Id'], 0, 1 );
		$pdf->Cell( 0, $h, "Vehicle Status: Stopped", 0, 1 );
		$pdf->Cell( 0, $h, "Plate of Violator: ".$event['Plateofviolator'], 0, 1 );
		$pdf->Cell( 0, $h, "Coordinate: ".$event['Lat'].','.$event['Lon'], 0, 1 );
		
		// retrieve address
		$addrurl = "https://dev.virtualearth.net/REST/v1/Locations/".$event['Lat'].','.$event['Lon']."?o=json&key=".$map_credentials ;
		$addr = file_get_contents( $addrurl );
		$addr = json_decode( $addr, true );
		if( !empty(  $addr['resourceSets'][0]['resources'][0]['address']['formattedAddress'] ) ) {
			$xaddr = $addr['resourceSets'][0]['resources'][0]['address']['formattedAddress'] ;
			$pdf->Cell( 0, $h, "Address: ".$xaddr, 0, 1 );
			
			// set processed State/City
			$event['State'] = $addr['resourceSets'][0]['resources'][0]['address']['adminDistrict'] ; 
			$event['City'] =  $addr['resourceSets'][0]['resources'][0]['address']['locality'] ;		
			
		}
				
		// print Notes
		if( !empty( $event['notes'] ) ) {
			$pdf->SetY($notey);
			$pdf->Cell(100);
			$pdf->Cell( 0, $h, "Notes: ", 0, 1 );
			$pdf->Cell(110);
			$pdf->MultiCell( 80, $h, $event['notes'], 0, 1 );
		}
		
		// output pdf
		$reportname = md5( $event['Client_Id'].$event['Bus_Id'].$event['Date_Time'] ) . ".pdf" ;
		$pdffile = $driveby_eventdir. '/' . $reportname ;

		$pdf_buffer = $pdf->Output($pdffile, "S");
		
		vfile_put_contents( $pdffile, $pdf_buffer );

		// update notes if neccessary
		// record process user, time
		$sql = "UPDATE Drive_By_Event SET `event_status` =  'processed', `report_status` =  'report', `report_file` = '$reportname', `event_processedby` =  '$_SESSION[user]', `event_processedtime` =  NOW(), `email_status` = 'Pending', `State` = '$event[State]', `City` = '$event[City]'  WHERE `idx` = $_REQUEST[tag] " ;
		$conn->query($sql) ;
		
		$resp['tag'] = $_REQUEST['tag'] ;
		$resp['res'] = 1 ;

	}
	echo json_encode($resp);
?>