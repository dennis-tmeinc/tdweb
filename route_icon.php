<?php
// route_icon - rotated route icon (arrow)
// Requests:
//      deg: rotate degrees
// Return:
//      PNG picture
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

	// Content type
	header('Content-type: image/png');
	
	if( empty($_REQUEST['deg']) ) {
		$degrees=0;
	}
	else {
		$degrees=360-(int)$_REQUEST['deg'];
	}
	
	// cache 
	$expires=30*24*3600;		// expired in 30 days
	header('Cache-Control: public, max-age='.$expires);
	
	if( isset($_REQUEST['img']) ) {
		$filename = 'res/'.$_REQUEST['img'];
	}
	else {
		$filename = 'res/map_icons_route.png';
	}
	$ftime1=filemtime(__FILE__) ;				// script file time
	$ftime2=filemtime($filename) ;				// image file time
	$lastmod = ( $ftime1>$ftime2 ) ? $ftime1 : $ftime2 ;
	$lastmodtime = gmdate('D, d M Y H:i:s ', $lastmod).'GMT';

	$etag = hash('md5', 'r'.$ftime1.$ftime2.$degrees );
	header('Expires: '.gmdate('D, d M Y H:i:s ', $_SERVER['REQUEST_TIME']+$expires).'GMT');

	if( (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==$etag ) ) {
		header("HTTP/1.1 304 Not Modified");
		die;
	}
	header('Etag: '.$etag);
	header('Last-Modified: '.$lastmodtime);

	// Load Image
	$source = imagecreatefrompng($filename);

	if( $degrees==0 ) {
		imagesavealpha($source, TRUE);
		imagepng($source);
	}
	else {
		// transparent color
		$transcolor = imagecolorclosestalpha ( $source , 0,0,0, 127);
		
		// Rotate
		$rotate = imagerotate($source, $degrees, $transcolor);
		
		// crop
		$sx = imagesx($source);
		$sy = imagesy($source);
		$rx = imagesx($rotate);
		$ry = imagesy($rotate);
		
		$output = imagecreatetruecolor ( $sx, $sy );
		imagefill($output,0,0,$transcolor);
		imagecopy($output,$rotate,0,0,($rx-$sx)/2, ($ry-$sy)/2, $sx, $sy);
		
		// Output		
		imagesavealpha($output, TRUE);
		imagepng($output);

		// Free the memory
		imagedestroy($rotate);
		imagedestroy($output);
	}
	
	// Free the memory
	imagedestroy($source);

?>