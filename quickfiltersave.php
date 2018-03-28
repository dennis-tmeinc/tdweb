<?php
// quickfiltersave.php - save one quick filter
// Requests:
//      quick filter parameters
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ){

		// to correct time
		$starttime=new DateTime($_REQUEST['startTime']);
		if( $_REQUEST['timeType'] == 0 ) {	// one hour from "Exact TIme"
			$starttime->add(new DateInterval("PT1H"));
			$_REQUEST['endTime']=$starttime->format("Y-m-d H:i:s");	// MYSQL format
		}
		else if( $_REQUEST['timeType'] == 1 ) {		// full day
			$starttime->setTime(0,0,0);
			$_REQUEST['startTime']=$starttime->format("Y-m-d H:i:s");	// MYSQL format
			$starttime->setTime(23,59,59);
			$_REQUEST['endTime']=$starttime->format("Y-m-d H:i:s");	// MYSQL format
		}
		else {
			$endtime=new DateTime($_REQUEST['endTime']);
			if( $starttime >= $endtime ) {
				$starttime->add(new DateInterval("PT1H"));				// make it same as EXACT time
				$_REQUEST['endTime']=$starttime->format("Y-m-d H:i:s");	// MYSQL format
			}
		}
		
		// MySQL connection
		$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
		// escaped string for SQL
		$esc_req=array();
		foreach( $_REQUEST as $key => $value )
		{
			$esc_req[$key]=$conn->escape_string($value);
		}	
		
		$sql="SELECT * FROM quickfilter WHERE `name` = '$esc_req[name]';" ;
		$result=$conn->query($sql) ;
		if( $result && $result->num_rows>0 ) {	// to update
			$sql=
"UPDATE quickfilter SET 
 `timeType`=$_REQUEST[timeType],
 `startTime`='$_REQUEST[startTime]',
 `endTime`='$_REQUEST[endTime]',
 `vehicleType`=$_REQUEST[vehicleType],
 `vehicleGroupName`='$_REQUEST[vehicleGroupName]',
 `zoneType`=$_REQUEST[zoneType],
 `zoneName`='$_REQUEST[zoneName]'".
", `bStop`=".(empty($_REQUEST['bStop'])?"0":"1").
", `bIdling`=".(empty($_REQUEST['bIdling'])?"0":"1").
", `bParking`=".(empty($_REQUEST['bParking'])?"0":"1").
", `bDesStop`=".(empty($_REQUEST['bDesStop'])?"0":"1").
", `bSpeeding`=".(empty($_REQUEST['bSpeeding'])?"0":"1").
", `bRoute`=".(empty($_REQUEST['bRoute'])?"0":"1").
", `bEvent`=".(empty($_REQUEST['bEvent'])?"0":"1").
", `bRacingStart`=".(empty($_REQUEST['bRacingStart'])?"0":"1").
", `bHardBrake`=".(empty($_REQUEST['bHardBrake'])?"0":"1").
", `bHardTurn`=".(empty($_REQUEST['bHardTurn'])?"0":"1").
", `bFrontImpact`=".(empty($_REQUEST['bFrontImpact'])?"0":"1").
", `bRearImpact`=".(empty($_REQUEST['bRearImpact'])?"0":"1").
", `bSideImpact`=".(empty($_REQUEST['bSideImpact'])?"0":"1").
", `bBumpyRide`=".(empty($_REQUEST['bBumpyRide'])?"0":"1").
", `stopDuration`=$_REQUEST[stopDuration],
 `idleDuration`=$_REQUEST[idleDuration],
 `parkDuration`=$_REQUEST[parkDuration],
 `desStopDuration`=$_REQUEST[desStopDuration],
 `speedLimit`=$_REQUEST[speedLimit],
 `gRacingStart`=$_REQUEST[gRacingStart],
 `gHardBrake`=$_REQUEST[gHardBrake],
 `gHardTurn`=$_REQUEST[gHardTurn],
 `gFrontImpact`=$_REQUEST[gFrontImpact],
 `gRearImpact`=$_REQUEST[gRearImpact],
 `gSideImpact`=$_REQUEST[gSideImpact],
 `gBumpyRide`=$_REQUEST[gBumpyRide]
 WHERE `name` = '$_REQUEST[name]' " ;
		
			if( $_SESSION['user_type'] == 'admin' ) {
				$sql .=';' ;
			}
			else {
				$sql .=" AND `user` = '$_SESSION[user]';" ;
			}

	    }
		else {		// to insert
			$sql=
"INSERT INTO `quickfilter`( `name`, `user`, `timeType`, `startTime`, `endTime`, `vehicleType`, `vehicleGroupName`, `zoneType`, `zoneName`, `bStop`, `bIdling`, `bParking`, `bDesStop`, `bSpeeding`, `bRoute`, `bEvent`, `bRacingStart`, `bHardBrake`, `bHardTurn`, `bFrontImpact`, `bRearImpact`, `bSideImpact`, `bBumpyRide`, `stopDuration`, `idleDuration`, `parkDuration`, `desStopDuration`, `speedLimit`, `gRacingStart`, `gHardBrake`, `gHardTurn`, `gFrontImpact`, `gRearImpact`, `gSideImpact`, `gBumpyRide`) VALUES ( '$_REQUEST[name]', '$_SESSION[user]', $_REQUEST[timeType], '$_REQUEST[startTime]', '$_REQUEST[endTime]', $_REQUEST[vehicleType], '$_REQUEST[vehicleGroupName]', $_REQUEST[zoneType], '$_REQUEST[zoneName]',".
(empty($_REQUEST['bStop'])?"0,":"1,").
(empty($_REQUEST['bIdling'])?"0,":"1,").
(empty($_REQUEST['bParking'])?"0,":"1,").
(empty($_REQUEST['bDesStop'])?"0,":"1,").
(empty($_REQUEST['bSpeeding'])?"0,":"1,").
(empty($_REQUEST['bRoute'])?"0,":"1,").
(empty($_REQUEST['bEvent'])?"0,":"1,"). 
(empty($_REQUEST['bRacingStart'])?"0,":"1,"). 
(empty($_REQUEST['bHardBrake'])?"0,":"1,"). 
(empty($_REQUEST['bHardTurn'])?"0,":"1,").
(empty($_REQUEST['bFrontImpact'])?"0,":"1,").
(empty($_REQUEST['bRearImpact'])?"0,":"1,").
(empty($_REQUEST['bSideImpact'])?"0,":"1,").
(empty($_REQUEST['bBumpyRide'])?"0,":"1,"). 
"$_REQUEST[stopDuration], $_REQUEST[idleDuration], $_REQUEST[parkDuration], $_REQUEST[desStopDuration], $_REQUEST[speedLimit], $_REQUEST[gRacingStart], $_REQUEST[gHardBrake], $_REQUEST[gHardTurn], $_REQUEST[gFrontImpact], $_REQUEST[gRearImpact], $_REQUEST[gSideImpact], $_REQUEST[gBumpyRide]);"	;
		}
		if( ($result = $conn->query($sql)) ) {
			$resp['sql'] = $sql ;
			if( $conn->affected_rows > 0 ) {
				$resp['res']=1 ;	// success
			}
			else {
				$resp['errormsg']="This filter may be created by other user!" ;
			}
		}
		else {
			$resp['res']=0;
//			$resp['errormsg']="SQL error: ".$conn->error ;
			$resp['errormsg']="Not allowed." ;
		}			

	}

	echo json_encode($resp);
?>