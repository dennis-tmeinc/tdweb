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
// 2021-11-08, support less than 4 cameras in report

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
		
		$sql = "SELECT * FROM drive_by_event WHERE `idx` = $_REQUEST[tag] " ;
		$channels = array();
		if($result=$conn->query($sql)) {

			if( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
				$x = new SimpleXMLElement( "<driveby>" . $row['Video_Files'] . "</driveby>" );
				$y = json_decode( json_encode( $x ), true );
				$ch = $y['channel'] ;
				if( empty($ch[0])){		// fix single element XML to json array
					$ch=[$ch];
				}

				for( $i=0; $i<count($ch); $i++ ) {
					if( !empty( $ch[$i]['name'] ) ) {
						$channels[ $ch[$i]['name'] ] = $ch[$i]['video'] ;
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
		
		$pdftime = new DateTime();

		class MPDF extends FPDF
		{
			// Page header
			function Header()
			{
				// Logo
				$this->Image('res/TD-StopArm-Drive-By-Event-HEADER-247Logo.jpg', 12,8, 0, 20, "JPEG", "https://247securityinc.com/" );
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

				// Author, date time.
				//$dateformat = DATE_RFC2822;		// standard format
				$dateformat = "l, M d, Y H:i:s T";		// us/canada format
				global $pdftime;
				$this->Cell(100, 10, '247 Security Inc., '.$pdftime->format($dateformat)
					,0,0,'L');
				// page number								
				$this->Cell(88, 10, 'Page '.$this->PageNo().'/{nb}' ,0,0,'R');
			}
		}

		// Instanciation of inherited class
		$pdf = new MPDF();		// default, Portrait, mm, A4
		$pdf->SetCreator("Touch Down Center");
		$pdf->SetAutoPageBreak(false, 12);
		$pdf->AliasNbPages();
		$pdf->AddPage();

		$pdf->SetFont('Helvetica','',12);
		$h=5.5 ;		// font height would be 12pt, ~ 4.23 mm
		
		// images
		$px = $pdf->GetX() ;
		$py = $pdf->GetY() ;
		$ny = $py ;
		$width = $pdf->GetPageWidth();
		$center = $width/2 ;
		$iw = $center - $px - 1;		// image width
		
		for( $i=0; $i<4; $i++ ) {
			set_time_limit(30) ;

			if( $i%2 == 0 ) {
				$xx = $px ;
				$py = $ny ;
			}
			else {
				$xx = $px + 96 ;
				$xx = $center ;
			}
			$pdf->SetY($py);

			$videofile = $channels[ $_REQUEST['ch'.$i] ] ;
			if( empty($videofile) || !file_exists($videofile) ) {
				continue;
			}

			$pos = $_REQUEST['pos'.$i] ;
			$imgfile = $driveby_eventdir."/frame".md5($videofile.$pos).".jpg" ;
			$pos += 0.03 ;
			
			$pvid = escapeshellarg( $videofile );
			$pimg = escapeshellarg( $imgfile );		
			$cmdline = "bin\\ffmpeg.exe -ss $pos -i $pvid -frames 1 $pimg" ;
			$eoutput = array();
			$eret = 1 ;
			vfile_exec( $cmdline, $eoutput, $eret ) ;
			if( $eret==0 && vfile_isfile( $imgfile ) ) {
				//$videofilelink = mcrypt_encrypt( MCRYPT_BLOWFISH, "drivebyvideolink", $videofile, "ecb" ) ;
				$cipher="aes-128-gcm" ;
				$iv="ivcrbydennis";
				$tag="dennis.tmeinccom";
				$videofilelink = openssl_encrypt ( $videofile, $cipher, "drivebyvideolink", OPENSSL_ZERO_PADDING, $iv, $tag);
				$link = "http://".$_SERVER['HTTP_HOST']. dirname( $_SERVER['REQUEST_URI'] ). "/drivebyvideo.php?link=".rawurlencode($tag.$videofilelink) ;

				$pdf->Image( vfile_url($imgfile), $xx, null, $iw, 0, "JPEG", $link);
				// delete temp img file
				vfile_unlink( $imgfile );

				$y = $pdf->GetY() + 1;
				if( $y>$ny) {
					$ny = $y ;
				}
			}
		}

		$pdf->SetY($ny);
		$pdf->Ln(0);

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
		
		if( !empty( $_REQUEST['mapaddr'] )){
			$xaddr = $_REQUEST['mapaddr'];
			$pdf->Cell( 0, $h, "Address: ".$xaddr, 0, 1 );
			
			// set processed State/City
			$event['State'] = $_REQUEST['State'];
			$event['City'] = $_REQUEST['City'];

		}
		// retrieve address
		else if( empty($osmapi) ){
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
		}
		else {
			$osmurl = "https://nominatim.openstreetmap.org/reverse?lat=$event[Lat]&lon=$event[Lon]&addressdetails=1&format=json";
			$opts = array('http'=>array('header'=>"User-Agent: TouchDownServer 3.7\r\n"));
			$context = stream_context_create($opts);
			$addr = file_get_contents( $osmurl, false, $context );
			$addr = json_decode( $addr, true );

			// set processed State/City
			if( !empty($addr['address']['state']) ){
				$event['State'] = $addr['address']['state'];
			}
			if( !empty($addr['address']['city']) ){
				$event['City'] = $addr['address']['city'];
			}
			if( !empty($addr['address']['road']) ){
				$a = $addr['address'] ;
				$xaddr = "$a[house_number] $a[road], $a[city], $a[state] $a[postcode]";
				$pdf->Cell( 0, $h, "Address: ".$xaddr, 0, 1 );
			}
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
		$sql = "UPDATE drive_by_event SET `event_status` =  'processed', `report_status` =  'report', `report_file` = '$reportname', `event_processedby` =  '$_SESSION[user]', `event_processedtime` =  NOW(), `email_status` = 'Pending', `State` = '$event[State]', `City` = '$event[City]'  WHERE `idx` = $_REQUEST[tag] " ;
		$conn->query($sql) ;
		
		$resp['tag'] = $_REQUEST['tag'] ;
		$resp['res'] = 1 ;

	}
	echo json_encode($resp);
?>