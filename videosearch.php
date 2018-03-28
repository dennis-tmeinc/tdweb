<?php
// videosearch.php - list video clip info
// Requests:
//      video search parameters
// Return:
//      JSON object
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		// to correct time
		$fromdate=new DateTime($_REQUEST['fromdate']);
		$fromdate = $fromdate->format("Y-m-d");
		$todate=new DateTime($_REQUEST['todate']);
		$todate = $todate->format("Y-m-d");
		
		// vehicle lists
		$vlist=array();
		if( isset($_REQUEST['groups']) ) {	// group
			foreach( $_REQUEST['groups'] as $value ) {
				// to read group 
				$sql="SELECT `vehiclelist` FROM `vgroup` WHERE `name` = '$value' ;";
				$result=$conn->query($sql);
				if( !empty($result)) {
					$row = $result->fetch_array( MYSQLI_NUM ) ;
					$vlist=array_merge( $vlist, explode(',', $row[0]));
				}
			}
		}
		if( isset($_REQUEST['vehicles']) ) {	// vehicles
			$vlist=array_merge( $vlist, $_REQUEST['vehicles'] );
		}
		
		if( count($vlist)<1 ) {
			$videofilter=" (`time_start` BETWEEN '$fromdate 0:00:00' AND '$todate 23:59:59' ) AND (`time_end` BETWEEN '$fromdate 0:00:00' AND '$todate 23:59:59' ) " ;
		}
		else {
			$videofilter=" (`time_start` BETWEEN '$fromdate 0:00:00' AND '$todate 23:59:59' ) AND (`time_end` BETWEEN '$fromdate 0:00:00' AND '$todate 23:59:59' ) AND ( vehicle_name IN ('".join("','",$vlist)."') ) " ;
		}

		// save session data
		session_save('videofilter', $videofilter);
		
		$resp['res']=1 ;	// success
	}
	echo json_encode( $resp );
	
?>