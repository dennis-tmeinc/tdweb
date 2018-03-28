<?php
	// SQL server
	$smart_host="tdweb.saferus.com" ;
	$smart_user="server" ;
	$smart_password="password" ;
	$smart_database="smart" ;
	$database_persistent=true ;

	// List of timezone: http://php.net/manual/en/timezones.php
	$timezone = "US/Pacific" ;
	
	// number of icons on map , acture number of icons may get doubled
	$map_icons=100;
	
	// MSS configure file
	$mss_conf="C:\\SmartSvrApps\\mss.conf";
	
	// Dashboard Option file
	$dashboard_conf="C:\\SmartSvrApps\\dashboardoption.config" ;
	
	// backup file location, 
	$backup_path="C:\\SmartSvrApps\\tdweb.saferus" ;
	
	// set to 1 to include auto_increment fields in backup.
	$backup_auto_increment = 0 ;
	
	// maximum map events to report, valid from 100000 to 2000000 (larger than 1000000 may not work well)
	$max_map_events=500000 ;
	
	// enable virtual scroll on grid display, may failed on huge table. value can be true or false
	$grid_scroll=false;
	
	// exact time range (seconds)
	$exact_time_range=60;

	// minimum travelling interval time.
	// $min_traveltime = 30 ;
	
	// zone moving mode, "cross" or "center"
	// $zone_mode = "cross" ;

	// Default map location, comment it to auto detect
	$map_area="San Francisco" ;
	
	// session timeout (max half hour)
	$session_timeout=900 ;	

	$remote_fileserver = "http://tdweb.saferus.com/tdc/fileservice.php" ;	
	

	// avaialbe ui: ui-lightness ui-darkness smoothness start redmond sunny 
	//              overcast le-frog flick pepper-grinder eggplant dark-hive
	//              cupertino south-street blitzer humanity hot-sneaks excite-bike 
	//              vader dot-luv mint-choc black-tie trontastic swanky-purse
	$default_ui_theme = "start" ;
	
	// web video playback
	$webplay_support = 1 ;
	
	// Maximum videos cache size in Mega Bytes
	$webplay_cache_size = 10000 ;
	
	// enable or disable live track
	$enable_livetrack = 1 ;
	// live track server (AVL Service)

	$avlservice = "http://tdweb.saferus.com:40520/avlservice" ;
	// this should be the public ip (domain name) of web server
	$avlcbserver = "http://127.0.0.1:80" ;
	$avlcbapp = "vltevent.php" ;
	
	// setup time zone
	date_default_timezone_set($timezone) ;	
	if(	$database_persistent ) {
		$smart_server = "p:".$smart_host ;
	}
	else {
		$smart_server = $smart_host ;
	}
	
	return ;
?>	
