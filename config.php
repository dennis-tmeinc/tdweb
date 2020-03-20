<?php
	// SQL server
	// $smart_host="50.242.127.167" ;
	// $smart_host="207.112.107.196" ;
	// $smart_host="99.35.219.116" ;
//	$smart_host="173.167.112.105";
//	$smart_host="tdlive.darktech.org" ;

	$smart_host="192.168.42.188";
	$smart_user="server" ;
	$smart_password="password" ;
	$smart_database="smart" ;
	
	$database_persistent=true ;
	
	$product_name = "TD FLEET MONITOR" ;

	// List of timezone: http://php.net/manual/en/timezones.php
	$timezone = "America/New_York" ;
	
	// number of icons on map , acture number of icons may get doubled
	$map_icons=100;
	
	// MSS configure file
	$mss_conf="C:\\SmartSvrApps\\mss.conf";
	
	// Dashboard Option file
	$dashboard_conf="C:\\SmartSvrApps\\dashboardoption.config" ;
	
	// Touchdown server configure file (include email server configure)
	$td_conf="R:\\tdconfig.conf";
	
	// backup file location, 
	$backup_path="\\TouchDownCenter\\www\\tdc\\smartbackup" ;
	
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

	// bing map credentials, for each tdweb installation, please head to https://www.bingmapsportal.com/  to get a new bing map key
	$map_credentials="AqH8jpFgh8cOPZNsTLo0wcOQNGji0uwHgiGyBXOxJvzDySVjKN36y6t_TU1o33e-" ;

	// Default map location, comment it to auto detect
	$map_area="Toronto" ;
	$mapmode="limit" ;
	
	// session timeout (max half hour)
	$session_timeout=900 ;
	
	$session_path= "R:\\td\\SESSION";
	$session_idname = "touchdownid";
	$remote_fileserver = "http://${smart_host}/tdc/fileservice.php" ;	
	
	$cache_dir="R:\\td\\cache" ;

	// avaialbe ui: ui-lightness ui-darkness smoothness start redmond sunny 
	//              overcast le-frog flick pepper-grinder eggplant dark-hive
	//              cupertino south-street blitzer humanity hot-sneaks excite-bike 
	//              vader dot-luv mint-choc black-tie trontastic swanky-purse
	$default_ui_theme = "smoothness" ;
	
	// Maximum videos cache size in Mega Bytes
	$webplay_cache_size = 10000 ;

	// new td live support
	$liveplay_protocol = "relay" ;
	$liveplay_host = "209.167.16.254" ;			// this is td live relay server's public ip address
	$support_https_playback = True;
					
	// live track server (AVL Service)
	$avlservice = "http://$smart_host:40520/avlservice" ;

	// multi company support
	$support_multicompany = 0 ;
	// script/excutable to create/remove com
	$td_new    = "C:\\SmartSvrApps\\tdnew.exe create " ;
	$td_clean  = "C:\\SmartSvrApps\\tdnew.exe remove " ;
	
	$enable_videos = true ;
	// drive by demo
	$support_driveby = 1 ;
	$driveby_eventdir = "\\TouchDownCenter\\drivebydemo1" ;

	$webplay_support = 1 ;						// enable web video playback
	$enable_livetrack = 1 ;						// enable or disable live track
	$support_viewtrack_logo = false ;
	$support_fleetmonitor_logo = true ;
	$support_livepreview = true ;
	$support_liveaudio = true ;
	$show_vehicles_uploaded = true ;
	$use_conv266 = true ;
	$disable_mss = false ;
	$support_videoviacellular = true ;
	
	return ;
?>
