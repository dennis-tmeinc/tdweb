<?php
	$smart_host="localhost" ;	
	$smart_user="server" ;
	$smart_password="password" ;
	$smart_database="smart" ;
	$database_persistent=true ;

	// List of timezone: http://php.net/manual/en/timezones.php
	$timezone = "US/Eastern" ;
	
	// number of icons on map , acture number of icons may get doubled
	$map_icons=100;
	
	// MSS configure file
	$mss_conf="/TouchDownCenter/external/mss.conf";
	
	// Dashboard Option file
	$dashboard_conf="/TouchDownCenter/external/dashboardoption.config" ;
	
	// Not used anymore
	// $mysql_util="\\TouchDownCenter\\mysql\\bin" ;
	
	// backup file location, 
	$backup_path="\\TouchDownCenter\\mysql\\backup" ;
	
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
	
	// bing map
	$map_credentials="AnC6aHa2J8jWAluq14HQu6HDH1cDshGtPEPrYiotanIf-q6ZdoSiVGd96wzuDutw";
	// Default map location, comment it to auto detect
	// $map_area="Toronto, Canada" ;
	
	// session timeout (max half hour)
	$session_timeout=900 ;	
	$session_path= __DIR__ . "/session";
	$session_idname = "touchdownid";
	
	// user
	$user_path = $session_path ;

	// avaialbe ui: ui-lightness ui-darkness smoothness start redmond sunny 
	//              overcast le-frog flick pepper-grinder eggplant dark-hive
	//              cupertino south-street blitzer humanity hot-sneaks excite-bike 
	//              vader dot-luv mint-choc black-tie trontastic swanky-purse
	$default_ui_theme = "start" ;
	
	// web video playback
	$webplay_support = 1 ;
	// Maximum videos cache size in Mega Bytes
	$webplay_cache_size = 10000 ;
	
	// live track server (AVL Service)
	$avlservice = "http://localhost:40520/avlservice" ;
	$avlcbserver = "http://localhost:80" ;
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
