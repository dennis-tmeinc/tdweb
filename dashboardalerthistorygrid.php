<?php
// dashboardalerthistorygrid.php -  get dashboard report alert history (grid data) (td health feature)
// Requests:
//             
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2015-02-25
// Copyright 2015 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	require_once 'vfile.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {

		@$conn=new mysqli($smart_server, $smart_user, $smart_password, $smart_database );
				
		$resp['report']=array();
		
		$filter = "" ;
		if( !empty( $_REQUEST["vehicle"] ) ) {
			$filter = " WHERE dvr_name = '$_REQUEST[vehicle]' " ;
		}
		
		// get total records	
		$sql="SELECT count(*) FROM td_alert $filter ;" ;
		$records = 0 ;
		if($result=$conn->query($sql)) {
			if(	$row = $result->fetch_array( MYSQLI_NUM ) ) {
				$records = $row[0] ;
			}
			$result->free();
		}
		
		$grid=array( 
			"records" => $records,
			"total" => ceil($records/$_REQUEST['rows']),
			"page" => $_REQUEST['page'] ,
			"rows" => array()  );
			
		if( $grid['page'] > $grid['total'] ) {
			$grid['page']=$grid['total'] ;
		}
		$start = $_REQUEST['rows'] * ($grid['page']-1) ;
			
		$alert_code = array(
		"unknown",
		"video uploaded",
		"temperature",
		"login failed",
		"video lost",
		"storage failed",
		"rtc error",
		"7",
		"system reset",
		"ignition on",
		"ignition off"
		);

		$sql="SELECT `index`, `dvr_name`, `description`, `alert_code`, `date_time` FROM td_alert $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord] LIMIT $start, $_REQUEST[rows] ;";
		
		if( $result=$conn->query($sql) ) {
			while( $row=$result->fetch_array() ) {
				if( $row[3]>0 && $row[3]<=10 ) {
					$grid['rows'][] = array(
						"id" => $row[0],
						"cell" => array( 
							$row[1], $row[2], $alert_code[$row[3]], $row[4]
						));
				}
			}
			$result->free();
		}
		$conn->close();
		echo json_encode( $grid );
	}
	else {
		echo json_encode( $resp );
	}
?>