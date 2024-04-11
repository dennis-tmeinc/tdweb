<?php

// session.php - load/initial user session
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

include 'config.php';

// all default global path/dir
if (empty($session_path) || !is_dir($session_path)) {
    $session_path = sys_get_temp_dir() ;
}

if (empty($cache_dir)) {
    $cache_dir = sys_get_temp_dir().DIRECTORY_SEPARATOR."tdcache";
}
if (!is_dir($cache_dir)) {
    if (!mkdir($cache_dir, 0777, true)) {
        $cache_dir = "videocache";
    }
}

if (empty($client_dir) || !is_dir($client_dir)) {
    $client_dir = "client";
}
if (!is_dir($client_dir)) {
    mkdir($client_dir, 0777, true);
}

if (empty($driveby_eventdir) ) {
    $driveby_eventdir = "drivebyevents";
}
if (!is_dir($driveby_eventdir)) {
    mkdir($driveby_eventdir, 0777, true);
}

if (empty($session_idname)) {
    $session_idname = "touchdownid";
}
session_save_path($session_path);
session_name($session_idname);

if (!empty($_REQUEST[session_name()])) {
    session_id($_REQUEST[session_name()]);
}

if (empty($product_name)) {
    $product_name = "TouchDown Center";
}

if (empty($td_conf)) {
    if( is_file("C:\\SmartSvrApps\\tdconfig.conf") ) {
        $td_conf = "C:\\SmartSvrApps\\tdconfig.conf";
    }
    else {
        $td_conf = realpath("client") . "\\tdconfig.conf" ;
    }
}

if (empty($jqver)) {
    $jqver = "3.7.1" ;
}
if (empty($jquiver)) {
    $jquiver = "1.13.2" ;
}
if (empty($jqtheme)) {
    $jqtheme = "base" ;
}

if(empty($nologon)){
    session_start();
}

// load client config
if (!empty($_SESSION['clientid'])) {
    $clientcfg = "$client_dir/$_SESSION[clientid]/config.php";
    if (file_exists($clientcfg)) {
        include $clientcfg;
        // fixed directories for multi companies
        if (!empty($company_root)) {
            // MSS configure file
            $mss_conf = $company_root . "\\mss.conf";
            // Dashboard Option file
            $dashboard_conf = $company_root . "\\dashboardoption.config";
            // database backup file location, 
            $backup_path = $company_root . "\\smartbackup";
        }
    }
    else {
        unset($_SESSION['clientid']);
    }
}

// setup time zone
if(!empty($timezone) ) {
	date_default_timezone_set($timezone) ;	
}

$resp = array('res' => 0);
$request_time = time();
$logon = false;

if(empty($nologon)){
    if (empty($_SESSION['user']) ||
            empty($_SESSION['xtime']) ||
            $request_time > $_SESSION['xtime'] + $session_timeout) {
        // logout
        unset($_SESSION['user']);
        unset($_SESSION['user_type']);
        $resp['errormsg'] = "Session error!";
        /* AJAX check */
        if (empty($noredir) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Location: logon.php');
        }
    } else {
        if (empty($noupdatetime))
            $_SESSION['xtime'] = $request_time;
        $logon = true;
    }
}

if( session_status() == PHP_SESSION_ACTIVE ) {
    session_write_close();
}

$conn = null;
if ($logon) {
    // move sql connection here, in case for general session's settings (etc. timezone)
    if (empty($nodb)) {
        if( !empty($smart_database) ) {
            @$conn = new mysqli($smart_host, $smart_user, $smart_password, $smart_database);
            if( empty($conn) ){
                $logon = false;
            }
        }
    }
}

// save $_SESSION variable after session_write_close()
function session_write() {
    $x_session = $_SESSION ;
    session_start();
    $_SESSION = $x_session ;
    session_write_close();
}

// store one variable to session
function session_save($vname, $value) {
    $_SESSION[$vname] = $value;
    if( session_status() != PHP_SESSION_ACTIVE ) {
        session_write();
    }
}
