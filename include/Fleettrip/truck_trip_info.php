<?php	
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	global $adb;

  set_time_limit(0);
  date_default_timezone_set("UTC");	
  ini_set('memory_limit','64M');
  $fuel_info_arr =array();	  
  
  	$truck_id = $_REQUEST['truck_id'];
    $trip_template_id = $_REQUEST['trip_template_id'];
	$from_date = date('Y-m-d',strtotime($_REQUEST['from_date']));
	$to_date = date('Y-m-d',strtotime($_REQUEST['to_date']));
	
	$expected_from_date = @$_REQUEST['expected_from_date'];
	
	$rs_truck = $adb->pquery("SELECT * from vtiger_truck 
							   INNER JOIN vtiger_truckcf ON vtiger_truckcf.truckid=vtiger_truck.truckid
							   WHERE vtiger_truck.truckid='".$truck_id."'", array());
	$truck_info = $adb->fetch_array($rs_truck);						  
	$truck_type_id = $truck_info['cf_1911'];
	
	if(!empty($truck_info) && !empty($trip_template_id))
	{
		/*$query_truck_fuel =mysql_query("select sum(vtiger_fuelcf.cf_2097) as petrol_filling_l from vtiger_fuelcf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
							AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
							where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid='".$truck_id."' AND 
							vtiger_fuelcf.cf_2093 >='".$from_date."' AND vtiger_fuelcf.cf_2093 <='".$to_date."' order by vtiger_fuelcf.fuelid DESC limit 1
							");
		$row_truck_fuel_filling = mysql_fetch_array($query_truck_fuel);
		
		
		$query_truck_fuel_end = mysql_query("select sum(vtiger_fuelcf.cf_2099) as fuel_at_the_end from vtiger_fuelcf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
							AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
							where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid='".$truck_id."' AND 
							vtiger_fuelcf.cf_2093 <='".$from_date."' order by vtiger_fuelcf.fuelid DESC limit 1
							");
		$row_truck_fuel_end = mysql_fetch_array($query_truck_fuel_end);*/
		/*
		$truck_km_end = $this->db->select_sum('due_arrival_km')
								   ->order_by('id', 'DESC')
								   ->where('truck_km.km_date <= ',$from_date)
								   ->where('truck_id', $truck_id)
								   ->get('truck_km')->row();
		*/
		$query_truck_km_end =  $adb->pquery("SELECT sum(vtiger_kilometercf.cf_2117) as due_arrival_km from vtiger_kilometercf
										  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_kilometercf.kilometerid
										  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_kilometercf.kilometerid 
										  AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Kilometer'
										  where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid='".$truck_id."' AND 
										  vtiger_kilometercf.cf_2105 <='".$from_date."' order by vtiger_kilometercf.kilometerid DESC limit 1
										  ", array());
		$row_truck_km_end = $adb->fetch_array($query_truck_km_end);	
		
						
		
								
		$query_trip_expense =  $adb->pquery("SELECT * from vtiger_triptemplatescf 
							   			  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triptemplatescf.triptemplatesid
							  			  WHERE vtiger_triptemplatescf.triptemplatesid = '".$trip_template_id."' AND 
							         	  vtiger_crmentity.deleted = 0
									 	  Limit 1
										  ", array());
		$row_truck_type_trip_expense = $adb->fetch_array($query_trip_expense);	
		
		//$fuel_info_arr['petrol_filling_l']    = (isset($row_truck_fuel_filling['petrol_filling_l'])?@$row_truck_fuel_filling['petrol_filling_l']:0);
		//$fuel_info_arr['fuel_at_the_begin']   = (isset($row_truck_fuel_end['fuel_at_the_end'])? $row_truck_fuel_end['fuel_at_the_end'] : 0);
		$fuel_info_arr['average_allowed_consumption'] = $truck_info['cf_2189'];
		$fuel_info_arr['truck_id'] 			= $truck_info['truckid'];
		$fuel_info_arr['due_leave_km'] 		= $row_truck_km_end['due_arrival_km'];
		$fuel_info_arr['standard_days'] 	   = @$row_truck_type_trip_expense['cf_2067'];
		$fuel_info_arr['standard_distance']   = @$row_truck_type_trip_expense['cf_2065'];
		$fuel_info_arr['standard_fuel'] 	   = @$row_truck_type_trip_expense['cf_2061'];
		$fuel_info_arr['cash_required']       = @$row_truck_type_trip_expense['cf_4555'];
		
		//$fuel_info_arr['fuel_at_the_end']  = @$truck_fuel->fuel_at_the_begin;
		$average_consumption = 0;
		if(@$row_truck_type_trip_expense['cf_2061']!=0) //Standard fuel
		{
		    //average_consumption =  Standart distance(km) / Standard Fuel
			$average_consumption = @$row_truck_type_trip_expense['cf_2065'] / @$row_truck_type_trip_expense['cf_2061']; 
			$average_consumption = number_format($average_consumption, 2, '.', '');
		}
		$fuel_info_arr['average_consumption'] = $average_consumption;
		
		//$fuel_info_arr['driver_id'] = (!empty($truck_info)?$truck_info['cf_1919']:'');

	}
	echo json_encode($fuel_info_arr);  
?>