<?php
// videoclipdel.php - delete one video clip
//      only delete video clip info database. Video file is not deleted for now.
// Requests:
//      index: video clip index
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		if( $_SESSION['user_type'] == 'admin' ) {

			if( count( $_REQUEST[index] ) > 0 ) {
				
				$od = false ;
				$sql = "DELETE FROM `videoclip` WHERE (" ;
				foreach( $_REQUEST[index] as $id ) {
					if( $od ) {
						$sql .= " OR " ;
					}
					$sql .= "( `index` = $id )" ;
					$od = true ;
				}
				$sql .=  ");" ;
				
				$resp['sql'] = $sql ;
				
				if( $conn->query($sql) ) {
					$resp['res']=1 ;	// success
				}
				else {
					$resp['errormsg']='SQL error: '.$conn->error ;
				}
			}

		}
		else {
			$resp['errormsg']='Not allowed';
		}
	}
	echo json_encode($resp);
?>