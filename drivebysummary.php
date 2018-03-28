<?php
// drivebysummary.php 	
//		get drive by event summary
// Request:
//      rows : number or rows to retrieve
//      page : page number
//
//      _search : true/false
//      
//      vehicle_name : 
//      time_start :
//      channel :
// Return:
//      json 
//
// By Dennis Chen @ TME	 - 2014-07-18
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//
// 
//
    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {	

		$referer = basename($_SERVER['HTTP_REFERER']);
		if( strpos( $referer, "processed" ) ) {
			$filter = " event_status = 'processed' " ;
		}
		else if( strpos( $referer, "deletedreports" ) ) {
			$filter = " report_status = 'deleted' " ;
		}
		else if( strpos( $referer, "deleted" ) ) {
			$filter = " event_status = 'deleted' " ;
		}
		else if( strpos( $referer, "reports" ) ) {
			$filter = " report_status = 'report' " ;
		}
		else {
			$filter = " event_status = 'new' " ;
		}

		$curdate=getdate();
		$year = $curdate['year'] ;
		$month = $curdate['mon'] ;
	
		$sql="SELECT count(*) FROM Drive_By_Event WHERE $filter " ;
		
		if( $result=$conn->query($sql) ) {
			if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
				$resp['total'] = $row[0] ;
			}
			$result->free();
		}	
		
		$resp['summary'] = array();
		for( $m=0; $m<14; $m++ ) {
		
			set_time_limit(30);

			$sum = array() ;

			$y1 = (int)$year ;
			$m1 = ((int)$month) - $m ;
			if( $m1 <= 0 ) {
				$m1+=12 ;
				$y1-=1 ;
			}
			if( $m1 < 10 ) $mstr = $y1.'-0'.$m1 ;
			else $mstr = $y1.'-'.$m1 ;
			$sum['month'] = $mstr ;
			$mstr .= '%' ;
			
			$msql = $sql." AND `Date_Time` like '$mstr' " ;

			// month total
			if( $result=$conn->query( $msql) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$sum['total'] = $row[0] ;
				}
				$result->free();
			}	
			// Good image
			if( $result=$conn->query( $msql." AND `imgquality` = 'Good' " ) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$sum['Good'] = $row[0] ;
				}
				$result->free();
			}
			// Poor image
			if( $result=$conn->query( $msql." AND `imgquality` = 'Pood' " ) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$sum['Poor'] = $row[0] ;
				}
				$result->free();
			}
					
			// Bad image
			if( $result=$conn->query( $msql." AND `imgquality` = 'Bad' " ) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$sum['Bad'] = $row[0] ;
				}
				$result->free();
			}
		
			// Email Sent 
			if( $result=$conn->query( $msql." AND `email_status` = 'Sent' " ) ) {
				if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
					$sum['Sent'] = $row[0] ;
				}
				$result->free();
			}
			$resp['summary'][]=$sum ;
			
		}
		
		$resp['res'] = 1 ;
	}			
	echo json_encode($resp);
?>