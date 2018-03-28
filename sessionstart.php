<?php
// sessionstart.php - load/initial user session
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

require_once 'config.php' ;

session_save_path( $session_path );
session_name( $session_idname );
session_start();

// store $_SESSION variable after session_write_close()
function session_write()
{
	file_put_contents( session_save_path().'/sess_'.session_id(), session_encode() );	
}

return ;
	
?>	