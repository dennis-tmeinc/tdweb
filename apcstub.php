<?php
// apcstub.php - stub file for all php souce
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

	
    require 'session.php' ;
	
	header("Content-Type: application/json");

	$resp=array();
	$resp['res']=0 ;

	
	echo json_encode($resp);
?>