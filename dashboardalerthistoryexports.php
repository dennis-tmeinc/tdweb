<?php
// dashboardalerthistoryexport.php -  export dashboard report alert history
// Requests:
//      none (preset by dashboardalerthistorygird.php)
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2024-02-12
// Copyright 2024 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
	require_once 'vfile.php' ;
	
	if( $logon ) {
        header( "Content-Type: text/csv" );
		header( "Content-Disposition: attachment; filename=alerts.csv" );   	
        $output = fopen('php://output', 'w');
        // csv header
        fputs( $output, "Vehicle Name, Description, Alert Code, Alert Time\r\n");

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

        $sql = $_SESSION["dashboardalertsql"];

		if( $result=$conn->query($sql) ) {
   			// contents
			while( $row = $result->fetch_array() ) {
				$line=array();
                $line[] = $row[1];
                $line[] = $row[2];
                if( $row[3]>0 && $row[3]<12 ) {
                    $line[] = $alert_code[$row[3]];
                }
                else {
                    $line[] = $row[3];
                }
                $line[] = $row[4];
				fputcsv ( $output , $line );
			}
		}
	}
	else {
		echo json_encode( $resp );
	}
?>