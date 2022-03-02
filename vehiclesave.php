<?php
// vehiclesave.php - save one vehicle information
// Requests:
//      oname: original vehicle name, create a new vehicle if not provided
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-05-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $logon ) {
		
		if( $_SESSION['user_type'] == "admin" ) {	// admin 
			
			$esc_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$esc_req[$key]=$conn->escape_string($value);
			}
			
			if( !empty($esc_req['oname']) ) {	// update
				// try update vehicle name?
				if( $_REQUEST['oname'] != $_REQUEST['vehicle_name'] ) {
					// vehicle name changed, update ass_d_v alse
					$sql="UPDATE `ass_d_v` SET `ass_vehicle_name` = '$esc_req[vehicle_name]' WHERE `ass_vehicle_name` = '$esc_req[oname]' ;";
					$conn->query($sql);
				}
			
			
				$sql="UPDATE vehicle SET ".
					"vehicle_name='$esc_req[vehicle_name]',".
					"vehicle_vin='$esc_req[vehicle_vin]',".
					"vehicle_rfid='$esc_req[vehicle_rfid]',".
					"vehicle_plate='$esc_req[vehicle_plate]',".
					"vehicle_model='$esc_req[vehicle_model]',".
					"vehicle_year='$esc_req[vehicle_year]',".
					"vehicle_uid='$esc_req[vehicle_uid]',".
					"vehicle_password='$esc_req[vehicle_password]',".
					"vehicle_notes='$esc_req[vehicle_notes]',".
					"vehicle_max_upload_time='$esc_req[vehicle_max_upload_time]',".
					"Vehicle_report_sun='$esc_req[Vehicle_report_sun]',".
					"Vehicle_report_mon='$esc_req[Vehicle_report_mon]',".
					"Vehicle_report_tue='$esc_req[Vehicle_report_tue]',".
					"Vehicle_report_wen='$esc_req[Vehicle_report_wen]',".
					"Vehicle_report_thu='$esc_req[Vehicle_report_thu]',".
					"Vehicle_report_fri='$esc_req[Vehicle_report_fri]',".
					"Vehicle_report_sat='$esc_req[Vehicle_report_sat]',".
					"vehicle_ivuid='$esc_req[vehicle_ivuid]',".
					"vehicle_phone='$esc_req[vehicle_phone]',".
					"vehicle_qa='$esc_req[vehicle_qa]',".
					"vehicle_hb='$esc_req[vehicle_hb]',".
					"vehicle_out_of_service=".(empty($esc_req['vehicle_out_of_service'])?'0':'1').
					" WHERE vehicle_name='$esc_req[oname]';" ;
			}
			else {			// new
				$sql="INSERT INTO vehicle ( 
					vehicle_name,
					vehicle_vin,
					vehicle_rfid,
					vehicle_plate,
					vehicle_model,
					vehicle_year,
					vehicle_uid,
					vehicle_password,
					vehicle_notes,
					vehicle_max_upload_time,
					Vehicle_report_sun,
					Vehicle_report_mon,
					Vehicle_report_tue,
					Vehicle_report_wen,
					Vehicle_report_thu,
					Vehicle_report_fri,
					Vehicle_report_sat,
					vehicle_ivuid, 
					vehicle_phone,
					vehicle_qa,
					vehicle_hb,
					vehicle_out_of_service
					) VALUES (
					'$esc_req[vehicle_name]',
					'$esc_req[vehicle_vin]',
					'$esc_req[vehicle_rfid]',
					'$esc_req[vehicle_plate]',
					'$esc_req[vehicle_model]',
					'$esc_req[vehicle_year]',
					'$esc_req[vehicle_uid]',
					'$esc_req[vehicle_password]',
					'$esc_req[vehicle_notes]',
					'$esc_req[vehicle_max_upload_time]',
					'$esc_req[Vehicle_report_sun]',
					'$esc_req[Vehicle_report_mon]',
					'$esc_req[Vehicle_report_tue]',
					'$esc_req[Vehicle_report_wen]',
					'$esc_req[Vehicle_report_thu]',
					'$esc_req[Vehicle_report_fri]',
					'$esc_req[Vehicle_report_sat]',
					'$esc_req[vehicle_ivuid]',
					'$esc_req[vehicle_phone]',
					'$esc_req[vehicle_qa]',
					'$esc_req[vehicle_hb]',
					"
					.(empty($esc_req['vehicle_out_of_service'])?'0':'1').
					") ;" ;
			}
			$resp['sql']=$sql ;
			
			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
				// 2021-02-26, call IVUSetup.exe -register [ivu id] [client id]
				if( !empty($_REQUEST['vehicle_ivuid'] )) {
					if( empty($_SESSION['clientid'])) {
						$clientid="";
					}
					else {
						$clientid=$_SESSION['clientid'];
					}
					$p1 = escapeshellarg( $_REQUEST['vehicle_ivuid'] );
					$p2 = escapeshellarg( $clientid );
					exec($td_ivu_setup." $p1 $p2");
				}
			}
			else {
				$resp['errormsg']=$conn->error;
			}
		}
		else {
			$resp['errormsg']="Not allowed!";
		}
	}
	echo json_encode($resp);
?>