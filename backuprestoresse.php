<?php
// backuprestoresse.php - Restore a backup , send back 'Server-Send Event'
// Requests:
//      backupname: Backup name given by user
// Return:
//      Server Sent Event, with data as JSON object
// By Dennis Chen @ TME	 - 2023-01-25
// Copyright 2023 Toronto MicroElectronics Inc.

require 'session.php' ;

header("Cache-Control: no-store");
header("Content-Type: text/event-stream");

if( $logon ) {
    if( $_SESSION['user_type'] != 'admin' ) {
        $resp['errormsg']="Not allowed!" ;
    }
    else if( empty($_REQUEST['backupname'] ) ) {
        $resp['errormsg']="No backup name specified!" ;
    }
    else {
        $resp['res']=1;

        $resp['event'] = 'begin';
        echo 'data: '. json_encode( $resp ) . "\n\n";
        ob_flush(); flush();
        
        require 'backuprestorefunction.php' ;
        $backupname = $backup_path. DIRECTORY_SEPARATOR . "bk".urlencode( $_REQUEST['backupname'] );
        dbrestore( $backupname, $conn, False ) ;
    }
}

$resp['event'] = 'end';
echo 'data: '. json_encode( $resp ) . "\n\n";

?>