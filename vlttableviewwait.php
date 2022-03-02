<?php
// vlttableviewwait.php 	
//		wait for vehicle current status update
// Request:
//      none
// Return:
//      json 
//		{
//			"res": 1
//      }
//
// By Dennis Chen @ TME	 - 2021-03-18
// Copyright 2013,2014 Toronto MicroElectronics Inc.
//
// 
//
    $noupdatetime = true ;
    require 'session.php' ;
	
	header("Content-Type: application/json");
    $resp['to'] = 0;
	
	if( $logon ) {

        $resp['sess'] = $_SESSION ;

        if( !empty($_SESSION['vehicle_status_time']) ){
            $st = $_SESSION['vehicle_status_time'] ;
            $sql="SELECT status_time FROM vehicle_current_status WHERE status_time > '$st' ;" ;
            for( $t1=0; $t1<25; $t1++) {
                if(  $result=$conn->query($sql)) {
                    if( $result->num_rows > 0) {
                        $resp['res'] = 1;
                        session_save( 'vehicle_status_time', '' );
                        break;
                    }
                    else {
                        sleep(1);
                    }
                }
            }
            $resp['to'] = 1;

        }
    }
	echo json_encode($resp);
?>