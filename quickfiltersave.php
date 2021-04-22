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

		// optional fields
		$optional_fields = array(
			'bDesStop',
			'desStopDuration',
			'bParking',
			'parkDuration',
			'bDriveBy' ,
			'bParking',
			'bDoorOpen',
			'bDoorClose',
			'bIgnitionOn',
			'bIgnitionOff',
			'bMeterOn',
			'bMeterOff' );
		
		foreach( $optional_fields as $key )
		{
			if( empty($_REQUEST[$key]) ) {
				$_REQUEST[$key] = 0 ;
			}
		}	

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
 `zoneName`='$_REQUEST[zoneName]',
 `bStop`='$_REQUEST[bStop]',
 `bIdling`='$_REQUEST[bIdling]',
 `bParking`='$_REQUEST[bParking]',
 `bDesStop`='$_REQUEST[bDesStop]',
 `bSpeeding`='$_REQUEST[bSpeeding]',
 `bRoute`='$_REQUEST[bRoute]',
 `bEvent`='$_REQUEST[bEvent]',
 `bRacingStart`='$_REQUEST[bRacingStart]',
 `bHardBrake`='$_REQUEST[bHardBrake]',
 `bHardTurn`='$_REQUEST[bHardTurn]',
 `bFrontImpact`='$_REQUEST[bFrontImpact]',
 `bRearImpact`='$_REQUEST[bRearImpact]',
 `bSideImpact`='$_REQUEST[bSideImpact]',
 `bBumpyRide`='$_REQUEST[bBumpyRide]',
 `stopDuration`=$_REQUEST[stopDuration],
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
 `gBumpyRide`=$_REQUEST[gBumpyRide],
 `bDriveBy` = $_REQUEST[bDriveBy],
 `bDoorOpen` = $_REQUEST[bDoorOpen],
 `bDoorClose` = $_REQUEST[bDoorClose],
 `bIgnitionOn` = $_REQUEST[bIgnitionOn],
 `bIgnitionOff` = $_REQUEST[bIgnitionOff],
 `bMeterOn` =$_REQUEST[bMeterOn],
 `bMeterOff` = $_REQUEST[bMeterOff]
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
			"INSERT INTO `quickfilter`( 
				`name`, `user`, `timeType`, `startTime`, `endTime`, `vehicleType`, `vehicleGroupName`, `zoneType`, `zoneName`, `bStop`, `bIdling`, `bParking`, `bDesStop`, `bSpeeding`, `bRoute`, `bEvent`, `bRacingStart`, `bHardBrake`, `bHardTurn`, `bFrontImpact`, `bRearImpact`, `bSideImpact`, `bBumpyRide`, `stopDuration`, `idleDuration`, `parkDuration`, `desStopDuration`, `speedLimit`, `gRacingStart`, `gHardBrake`, `gHardTurn`, `gFrontImpact`, `gRearImpact`, `gSideImpact`, `gBumpyRide`, `bDriveBy`, `bDoorOpen`, `bDoorClose`, `bIgnitionOn`, `bIgnitionOff`, `bMeterOn`, `bMeterOff`
			) VALUES ( 
			'$_REQUEST[name]', '$_SESSION[user]', $_REQUEST[timeType], '$_REQUEST[startTime]', '$_REQUEST[endTime]', $_REQUEST[vehicleType], '$_REQUEST[vehicleGroupName]', $_REQUEST[zoneType], '$_REQUEST[zoneName]',
			$_REQUEST[bStop], 
			$_REQUEST[bIdling],
			$_REQUEST[bParking],
			$_REQUEST[bDesStop],
			$_REQUEST[bSpeeding],
			$_REQUEST[bRoute],
			$_REQUEST[bEvent],
			$_REQUEST[bRacingStart],
			$_REQUEST[bHardBrake],
			$_REQUEST[bHardTurn],
			$_REQUEST[bFrontImpact],
			$_REQUEST[bRearImpact],
			$_REQUEST[bSideImpact],
			$_REQUEST[bBumpyRide],
			$_REQUEST[stopDuration], 
			$_REQUEST[idleDuration], 
			$_REQUEST[parkDuration], 
			$_REQUEST[desStopDuration], 
			$_REQUEST[speedLimit], 
			$_REQUEST[gRacingStart], 
			$_REQUEST[gHardBrake], 
			$_REQUEST[gHardTurn], 
			$_REQUEST[gFrontImpact], 
			$_REQUEST[gRearImpact], 
			$_REQUEST[gSideImpact], 
			$_REQUEST[gBumpyRide],
			$_REQUEST[bDriveBy], 
			$_REQUEST[bDoorOpen], 
			$_REQUEST[bDoorClose],
			$_REQUEST[bIgnitionOn],
			$_REQUEST[bIgnitionOff], 
			$_REQUEST[bMeterOn], 
			$_REQUEST[bMeterOff]
);"	;
		}
		
		$resp['sql'] = $sql ;
		if( ($result = $conn->query($sql)) ) {
			$resp['res']=1 ;	// success
		}
		else {
			$resp['res']=0;
//			$resp['errormsg']="SQL error: ".$conn->error ;
			$resp['errormsg']="Not allowed." ;
		}			

	}

	echo json_encode($resp);
?>