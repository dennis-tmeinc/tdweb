<?php
// fakeavl.php - a fake avl service for development
// Requests:
//      xml: xml document of AVL request
// Return:
//      xml document composed of AVL protocol
// By Dennis Chen @ TME	 - 2013-11-20
// Copyright 2013 Toronto MicroElectronics Inc.

header("Content-Type: application/xml");

$resp = new SimpleXMLElement('<tdwebc><status>Error</status></tdwebc>') ;

if( empty($_REQUEST['xml']) ) {
	$resp->status="ErrorNoXML" ;
	$resp->errormsg='No XML parameter!' ;
	goto done ;
}

$log = fopen("session/eventlog.log", "a");
if( $log ) {
	fwrite( $log, "\nFake Event: ".date("h:i:s ").$_REQUEST['xml'] );
	fclose( $log );
}

@$rxml = new SimpleXMLElement($_REQUEST['xml']) ;

if( empty( $rxml->session ) ) {
	$resp->status='ErrorXML' ;
	$resp->errormsg='XML document error or session error';
	goto done ;
}

$resp->session=$rxml->session ;

@$vltcommand = $rxml->command ;
if( empty($vltcommand) || $vltcommand < 1 || $vltcommand >200  ) {		// No command ?
   	$resp->status='ErrorCommand' ;
	$resp->errormsg='Wrong command value.' ;
	goto done ;
}
$resp->command=$vltcommand ;

@$vltserialno = $rxml->serialno ;
if( empty($vltserialno) ) 
	$vltserialno = '' ;		// no serial (generic)
else 
 	$resp->serialno = $rxml->serialno ;
	
switch ($vltcommand) {
    case '23':
		// success
		$resp->status='OK' ;
		$resp->ack='Success' ;
		$resp->avlp->{'list'}->item[0]->dvrid='bus001';
		$resp->avlp->{'list'}->item[0]->phone='16479653203';
		$resp->avlp->{'list'}->item[0]->ip='192.168.1.100';
		$resp->avlp->{'list'}->item[0]->type='5.1';
		$resp->avlp->{'list'}->item[1]->dvrid='bus002';
		$resp->avlp->{'list'}->item[1]->phone='16479653204';
		$resp->avlp->{'list'}->item[1]->ip='192.168.1.100';
		$resp->avlp->{'list'}->item[1]->type='5.1';
        break;

	case '27':
		// success
		$resp->status='OK' ;
		$resp->ack='Success' ;
		$resp->source->dvrs->dvr[]=$rxml->target->dvrs->dvr ;
		$resp->avlp->pos="031121121314,43.641988N079.672085W0.0D134.05";
		$resp->avlp->di='ffffffff' ;
        break;

	default:
		$resp->status = 'ErrorUnknownCommand' ;
		$resp->errormsg = 'Unknown command!' ;
		break;
}
	
done:	
	echo $resp->asXML() ;
?>
