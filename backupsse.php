<?php
// backupsse.php - Start a backup, send 'Server-Sent Event' as response
//      HTTP/2 do not support header "Connection: close"
// Requests:
//      backupname: Backup name given by user
// Return:
//      SSE, JSON object as data
// By Dennis Chen @ TME	 - 20-06-19
// Copyright 2013 Toronto MicroElectronics Inc.

require 'session.php' ;

header("Cache-Control: no-store");
header("Content-Type: text/event-stream");

$dobackup = false ;

if( $logon ) {
    if( $_SESSION['user_type'] != 'admin' ) {
        $resp['errormsg']="Not allowed!" ;
    }
    else if( empty($_REQUEST['backupname'] ) ) {
        $resp['errormsg']="No backup name specified!" ;
    }
    else if( !empty($backup_path) ) {
        // try make dir
        @mkdir( $backup_path );

        $backupname = $backup_path. DIRECTORY_SEPARATOR . "bk".urlencode( $_REQUEST['backupname'] );
        $resp['res']=1;

        $resp['event'] = 'begin';
        echo 'data: '. json_encode( $resp ) . "\n\n";
        ob_flush();
        flush();

        // now let's do the real backup work
        require 'backupfunction.php' ;
        dbbackup( $backupname, $conn, False ) ;
    }
}

$resp['event'] = 'end';
echo 'data: '. json_encode( $resp ) . "\n\n";

?>