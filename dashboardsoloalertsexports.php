<?php
// dashboardsoloalertsexport.php - export dashboard report on solo alerts list 
// Requests:
//      (optional: alertcode)
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2016-06-02
// Copyright 2015 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	
	if( $logon ) {
        header( "Content-Type: text/csv" );
        header( "Content-Disposition: attachment; filename=driver.csv" );   	
        
		$reqdate = new DateTime() ;
		if( strstr($_SESSION['dashboardpage'], 'dashboardmorning') ) {		// dashboard morning?
			$reqdate->sub(new DateInterval('P1D'));
		}

		// dashboard options
		$dashboard_option = parse_ini_string( vfile_get_contents( $dashboard_conf ) ) ;
		
		// default value
		if( empty( $dashboard_option ) ) $dashboard_option =  array(
			'tmStartOfDay' => '3:00'
		);
		
		if( empty( $dashboard_option['tmStartOfDay'] ) || $dashboard_option['tmStartOfDay'] == 'n/a' ) {
			$dashboard_option['tmStartOfDay'] = '0:00' ;
		}
				
		// time ranges
		@$date_begin = new DateTime( $dashboard_option['tmStartOfDay'] );
		if( empty($date_begin) ) {
			$date_begin = new DateTime( "03:00:00" );
		}

		$date_begin = new DateTime( $reqdate->format('Y-m-d ').$date_begin->format('H:i:s') );
		$date_begin = $date_begin->format('Y-m-d H:i:s');
		$date_end = new DateTime( $date_begin );
		$date_end->add(new DateInterval('P1D'));
		$date_end = $date_end->format('Y-m-d H:i:s');

		$resp['report']=array();
		
		$escape_req=array();
		foreach( $_REQUEST as $key => $value )
		{
			$escape_req[$key]=$conn->escape_string($value);
		}
		
		if( empty( $escape_req['alertcode'] ) ) {
			$escape_req['alertcode'] = "-1" ;
		}
		
		$filter = " WHERE alert_code IN ($escape_req[alertcode]) AND date_time BETWEEN '$date_begin' AND '$date_end' ";
	
        // sql
        $sql="SELECT `dvr_name`, `description`, `alert_code`, `date_time` FROM td_alert $filter ;";
		
		$alert_code = array(
		"unknown",
		"video uploaded",
		"temperature",
		"login failed",
		"video lost",
		"storage failed",
		"rtc error",
		"partial storage failure",
		"system reset",
		"ignition on",
		"ignition off",
		"panic"
		);

        // attachment output
        $output = fopen('php://output', 'w');

		if( $result=$conn->query($sql) ) {
			// headers 
            $line=array();
            $fields = $result->fetch_fields();
            for( $i=0 ; $i<count($fields) ; $i++ ) {
                $line[]=$fields[$i]->name;
            }
            fputcsv( $output , $line );

            // contents
            while( $row = $result->fetch_array(MYSQLI_NUM) ) {
				if( $row[2]>0 && $row[2]<12 ) {     //alert code
                    $row[2] = $alert_code[$row[2]];
				}
                fputcsv ( $output , $row );
            }
		}
	}
	
?>