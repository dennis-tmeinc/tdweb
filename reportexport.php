<?php
// reportexport.php - export report view events
// Requests:
//      All summary values
// Return:
//      CSV file
// By Dennis Chen @ TME	 - 2013-12-18
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;

    if( $logon ) {
        header( "Content-Type: text/csv" );
        header( "Content-Disposition: attachment; filename=report.csv" );

        $output = fopen('php://output', 'w');

        // get total records
        if( empty($_SESSION['mapfilter']['filter']) ) {
            $filter = 'FALSE';
        }
        else {
            $filter = $_SESSION['mapfilter']['filter'] ;
        }
        $sql = "SELECT * FROM vl WHERE $filter";

        // special icon id,
        // 10000:"Speeding",
        // 10001:"Front Impact" ,
        // 10002:"Rear Impact" ,
        // 10003:"Side Impact" ,
        // 10004:"Hard Brake" ,
        // 10005:"Racing Start" ,
        // 10006:"Hard Turn" ,
        // 10007:"Bumpy Ride"
        function vl_icon($row)
        {
            global $_SESSION ;

            $icon = (int)$row['vl_incident'] ;
            if( $icon == 2 ) {			// route, check for speeding
                if( $_SESSION['mapfilter']['bSpeeding'] && $row['vl_speed'] > $_SESSION['mapfilter']['speedLimit'] ) {
                    $icon = 10000 ;		// speeding icon
                }
            }
            else if( $icon == 16 ) {	// g-force event
                if( $_SESSION['mapfilter']['bFrontImpact'] && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gFrontImpact'] )
                    $icon=10001;			// fi
                else if( $_SESSION['mapfilter']['bRearImpact'] && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRearImpact'] )
                    $icon=10002;			// ri
                else if( $_SESSION['mapfilter']['bSideImpact'] && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gSideImpact'] )
                    $icon=10003;			// si
                else if( $_SESSION['mapfilter']['bHardBrake'] && $row['vl_impact_x'] <= -$_SESSION['mapfilter']['gHardBrake'] )
                    $icon=10004;			// hb
                else if( $_SESSION['mapfilter']['bRacingStart']  && $row['vl_impact_x'] >= $_SESSION['mapfilter']['gRacingStart'] )
                    $icon=10005;			// rs
                else if( $_SESSION['mapfilter']['bHardTurn']  && abs($row['vl_impact_y']) >= $_SESSION['mapfilter']['gHardTurn'] )
                    $icon=10006;			// ht
                else if( $_SESSION['mapfilter']['bBumpyRide'] && abs(1.0-$row['vl_impact_z']) >= $_SESSION['mapfilter']['gBumpyRide'] )
                    $icon=10007;			// br
            }

            $event_name=array(
                1 => "stopping" ,
                2 => "route",
                4 => "idling",
                16 => "G force",
                17 => "bus stops",
                18 => "parking",
                23 => "event" ,
                40 => "drive by",
                101 => "engine on",
                102 => "engine off",
                103 => "obd data",
                10000 => "speeding" ,
                10001 => "front impact" ,
                10002 => "rear impact" ,
                10003 => "side impact" ,
                10004 => "hard brake" ,
                10005 => "racing start" ,
                10006 => "hard turn" ,
                10007 => "bumpy ride"  );
            if( empty( $event_name[$icon] ) ) {
                return "unknown" ;
            }
            else {
                return $event_name[$icon] ;
            }
        }

        function get_addrs($coors)
        {
            $query = array(
                "c"=>"batch",
                "coor"=>implode(";", $coors)
            );
            $addrs = file_get_contents( "https://dvp.my247now.com/dvp/geoaddr.php", false, stream_context_create(array(
                'http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($query)
                    )
            )));

            $jaddrs = json_decode( $addrs, TRUE);
            return $jaddrs['address'];
        }

        function export_lines( $lns, $ads) {
            global $output;
            $adcount = count($ads) ;
            $lncount = count($lns);
            for( $i=0; $i<$lncount; $i++){
                if( $i<$adcount) {
                    $lns[$i][] = $ads[$i];
                }
                fputcsv ( $output , $lns[$i] );
            }
        }

        // Header
        // fputs( $output, "Vehicle,Driver,Activity,DateTime,Duration,Speed,Coordinates\r\n");
        $line=array();
        $line[] = "Vehicle";
        $line[] = "Driver";
        $line[] = "Activity";
        $line[] = "DateTime";
        $line[] = "Duration";
        $line[] = "Speed";
        $line[] = "Coordinates";
        // address?
        $line[] = "Address";
        fputcsv ( $output , $line );

        $lines = array();
        $coors = array();

        if( $result = $conn->query($sql) ) {
            // contents
            while( $row = $result->fetch_array() ) {
                $line=array();
                $line[] = $row['vl_vehicle_name'];
                $line[] = $row['vl_driver_name'];
                $line[] = vl_icon($row);
                $line[] = $row['vl_datetime'];
                $line[] = $row['vl_time_len'];
                // for country code, mph or km/h
                if( $_SESSION['country'] == "US") {
                    // mph
                    $speed = round($row['vl_speed'] / 1.609344 , 1);
                }
                else {
                    // km/h
                    $speed = round($row['vl_speed'], 1);
                }
                $line[] = $speed ;
                $coor = $row['vl_lat'].','.$row['vl_lon'];
                $line[] = $coor;
                $lines[] = $line ;
                $coors[] = $coor;
                if( count($lines) > 3000 ) {
                    $addrs = get_addrs($coors);
                    export_lines( $lines, $addrs);

                    $lines = array();
                    $coors = array();
                }
            }
            $result->free();
        }

        if( count($lines) > 0 ) {
            $addrs = get_addrs($coors);
            export_lines( $lines, $addrs);
        }

        // output summary
        fputs($output,"\r\n\r\nSummary\r\n");
        foreach( $_REQUEST as $key => $value ){
            $line=array();
            $key =  str_replace('_', ' ', $key );
            $key =  str_replace(':', '', $key );
            $line[] = $key ;
            $line[] = $value ;
            fputcsv ( $output , $line );
        }
    }
?>
