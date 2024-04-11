<?php
// dashboardalerthistorygrid.php -  get dashboard report alert history (grid data) (td health feature)
// Requests:
//      vehicle : vehicle name
//      alert :  alert code
// Return:
//      JSON object, (contain event list)
// By Dennis Chen @ TME	 - 2015-02-25
// Copyright 2015 Toronto MicroElectronics Inc.

    require_once 'session.php' ;
    require_once 'vfile.php' ;
    header("Content-Type: application/json");

    if( $logon ) {

        $resp['report']=array();

        $escape_req=array();
        foreach( $_REQUEST as $key => $value )
        {
            $escape_req[$key]=$conn->escape_string($value);
        }

        $filter = "" ;
        if( !empty( $_REQUEST["vehicle"] ) ) {
            $filter = " dvr_name = '$escape_req[vehicle]'" ;
        }

        $set_alert_code = false ;
        if( !empty( $_REQUEST["_search"] ) && $_REQUEST["_search"] == "true" ) {
            if( !empty( $_REQUEST["alert_code"] ) ) {
                if( !empty( $filter ))
                    $filter .=" AND" ;
                $filter .= " alert_code = $escape_req[alert_code]" ;
                $set_alert_code = true ;
            }
            if( !empty( $_REQUEST["date_time"] ) ) {
                if( !empty( $filter ))
                    $filter .=" AND" ;
                $filter .= " date_time LIKE '$escape_req[date_time]%'";
            }
        }
        if(!$set_alert_code && !empty($escape_req["alert"]) ) {
            if( !empty( $filter ))
                $filter .=" AND" ;
            $filter .= " alert_code in ($escape_req[alert])" ;
        }

        if( !empty($filter) ) {
            $filter = " WHERE " .$filter ;
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
            "rows" => array() );

        $start = $_REQUEST['rows'] * ($grid['page']-1) ;

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
            "panic",
            "storage recover",
            "video recover",
            "login recover",
            "partial storage recover",
            "battery low"
        );

        $alert_code_count = count($alert_code);

        $sql="SELECT `index`, `dvr_name`, `description`, `alert_code`, `date_time` FROM td_alert $filter ORDER BY $_REQUEST[sidx] $_REQUEST[sord]";
        // save alert search for export function
        session_save("dashboardalertsql", $sql);
        $sql .=" LIMIT $start, $_REQUEST[rows]";

        if( $result=$conn->query($sql) ) {
            while( $row=$result->fetch_array() ) {
                if( $row[3]<$alert_code_count ) {
                    $grid['rows'][] = array(
                        "id" => $row[0],
                        "cell" => array(
                            $row[1], $row[2], $alert_code[$row[3]], $row[4]
                        ));
                }
                else {
                    $grid['rows'][] = array(
                        "id" => $row[0],
                        "cell" => array(
                            $row[1], $row[2], $row[3], $row[4]
                        ));
                }
            }
            $result->free();
        }
        echo json_encode( $grid );
    }
    else {
        echo json_encode( $resp );
    }
?>