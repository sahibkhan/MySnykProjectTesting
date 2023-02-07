<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleet_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$module_get = $_GET['module'];
		$record_get = $_GET['record'];
		$custom_permission_check = custom_access_rules($record_get,$module_get);
		
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) && ($custom_permission_check == 'yes')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {
		
		$adb = PearDatabase::getInstance();
		
		$due_arrival_km = $request->get('cf_2019');
		$due_leave_km  = $request->get('cf_2017');
		$total_km_traveled_during_trip =  $due_arrival_km - $due_leave_km;
		
		//$petrol_filling_l = $request->get('cf_2025');
		//$fuel_at_the_begin = $request->get('cf_2027');
		//$fuel_at_the_end = $request->get('cf_2029');
		
		//$fuel_used_during_trip = ( $petrol_filling_l + $fuel_at_the_begin ) - $fuel_at_the_end;
		$fuel_used_during_trip = $request->get('cf_2033');
			
		$fuel_at_the_end_chk = $request->get('cf_2029');
		
		$average_consumption = 0;
		
		$average_consumption = $request->get('cf_2035');
		
		$expected_from_date = strtotime($request->get('cf_2009'));
		$expected_to_date = strtotime($request->get('cf_2011'));
		$total_allowed_days = (($expected_to_date - $expected_from_date)/ (60 * 60 * 24))+1;
		
		$from_date = date('Y-m-d',strtotime($request->get('cf_2005')));
		$to_date = date('Y-m-d',strtotime($request->get('cf_2007')));
		
		$truck_id = $request->get('cf_2001');
		
		/*
		$query_truck_fuel = "select sum(vtiger_fuelcf.cf_2097) as petrol_filling_l from vtiger_fuelcf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
							AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
							where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid=? AND 
							vtiger_fuelcf.cf_2093 >=? AND vtiger_fuelcf.cf_2093 <=? order by vtiger_fuelcf.fuelid DESC limit 1
							";
		$check_params = array($truck_id, $from_date, $to_date);
		$result = $adb->pquery($query_truck_fuel, $check_params);
		$row_truck_fuel_filling = $adb->fetch_array($result);
		
							
		$query_truck_fuel_end = "select sum(vtiger_fuelcf.cf_2099) as fuel_at_the_end from vtiger_fuelcf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
							AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
							where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid=? AND 
							vtiger_fuelcf.cf_2093 <=? order by vtiger_fuelcf.fuelid DESC limit 1
							";
		$check_params_end = array($truck_id, $from_date);
		$result_end = $adb->pquery($query_truck_fuel_end, $check_params_end);
		$row_truck_fuel_end = $adb->fetch_array($result_end);
		*/
									
		$due_leave_km = $request->get('cf_2017');
		$due_arrival_km = $request->get('cf_2019');		   
		$fuel_at_the_end =  $request->get('cf_2029');
		
		$origin_country_id	  = $request->get('cf_1993');
		$origin_city_id		 = $request->get('cf_1997');
		$destination_country_id = $request->get('cf_1995');
		$destination_city_id	= $request->get('cf_1999');
			
		$truck_info = Vtiger_Record_Model::getInstanceById($truck_id, 'Truck');
		$truck_type_id = $truck_info->get('cf_1911');
		
		
		$query_trip_expense = "SELECT * from vtiger_triptemplatescf 
							   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triptemplatescf.triptemplatesid
							   WHERE vtiger_triptemplatescf.cf_2047 = ? AND 
							         vtiger_triptemplatescf.cf_2053 = ? AND
									 vtiger_triptemplatescf.cf_2057 = ? AND
									 vtiger_triptemplatescf.cf_2055 = ? AND
									 vtiger_triptemplatescf.cf_2059 = ?	AND
									 vtiger_crmentity.deleted = 0
									 Limit 1
								";
		$check_params_trip_expense = array($truck_type_id, $origin_country_id, $origin_city_id, $destination_country_id, $destination_city_id);
		$result_trip_expense = $adb->pquery($query_trip_expense, $check_params_trip_expense);
		$row_truck_type_trip_expense = $adb->fetch_array($result_trip_expense);
								
		$average_consumption = 0;
		if(@$row_truck_type_trip_expense['cf_2061']!=0) //Standard fuel
		{
			//average_consumption =  Standart distance(km) / Standard Fuel
			$average_consumption  = @$row_truck_type_trip_expense['cf_2065'] / @$row_truck_type_trip_expense['cf_2061'];
		}
		
		
		$request->set('cf_2015', $total_allowed_days);
		//$request->set('cf_2025', $row_truck_fuel_filling['petrol_filling_l']);
		//$request->set('cf_2027', $row_truck_fuel_end['fuel_at_the_end']);
		//$request->set('cf_2029', (!empty($fuel_at_the_end) ? $fuel_at_the_end : 0));
		$request->set('cf_2033', $fuel_used_during_trip);
		$request->set('cf_2035', number_format($average_consumption, 2, '.', ''));
		$request->set('cf_2037', $truck_info->get('cf_2189')); //average_allowed_consumption
		
		$recordId = $request->get('record');
		if(empty($recordId)) 
		{
			$request->set('cf_2013', $row_truck_type_trip_expense['cf_2067']); //standard days
			$request->set('cf_2021', $row_truck_type_trip_expense['cf_2065']); //standard distance
			$request->set('cf_2031', $row_truck_type_trip_expense['cf_2061']); //standard fuel
		}
			
		/*$query_count_job_fleet = "select count(*) as total_job_fleet from vtiger_fleetcf
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fleetcf.fleetid
								 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fleetcf.fleetid
								 where vtiger_crmentity.deleted = 0 AND vtiger_fleetcf.cf_2001=?  AND vtiger_crmentityrel.crmid=?
								 AND vtiger_fleetcf.fleetid!=?
								 ";
							
		//AND vtiger_fleetcf.cf_2003=?
		//$request->get('cf_2003'),
		//Apply condition only for truck
		
		$check_params_count = array($truck_id,  $request->get('sourceRecord'), $recordId);
		$result_count = $adb->pquery($query_count_job_fleet, $check_params_count);
		$row_count_job_fleet = $adb->fetch_array($result_count);*/
						
		//Still need to discuss below option
		//if($row_count_job_fleet['total_job_fleet']==0)
		//{
		//Without check on truck id... 
		//add or update data.Because single truck used for multiple jobs and 
		//restrict single truck usage for two times for single job	 
		$recordModel = $this->saveRecord($request);
		//}
		
		if(empty($recordId)) 
		{
			//$request->set('cf_2013', $row_truck_type_trip_expense['cf_2067']); //standard days
			//$request->set('cf_2021', $row_truck_type_trip_expense['cf_2065']); //standard distance
			//$request->set('cf_2031', $row_truck_type_trip_expense['cf_2061']); //standard fuel
			
			//$total_allowance = (@$row_truck_type_trip_expense['cf_2067'] * @$row_truck_type_trip_expense['cf_2069']); //standard_days * daily_allowance
			$total_allowance = @$row_truck_type_trip_expense['cf_2071']; //Total Allowance
			$parking = @$row_truck_type_trip_expense['cf_2073']; //parking
			$quest_house = @$row_truck_type_trip_expense['cf_2075']; //guest_house
			$others = @$row_truck_type_trip_expense['cf_2081']; //others
			
			
			
			//Calculate per litre price of petrol against truck filling latest
			/*
			$query_truck_fuel_latest = "select sum(vtiger_fuelcf.cf_2097) as petrol_filling_l from vtiger_fuelcf 
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
										INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
										AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
										where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid=? 
										order by vtiger_fuelcf.cf_2093 DESC limit 1
										";
			*/
			$query_truck_fuel_latest = "select vtiger_fuelcf.cf_2101 as petrol_price_l from vtiger_fuelcf 
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
										INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
										AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
										where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid=? 
										order by vtiger_fuelcf.cf_2093 DESC limit 1
										";							
			$check_params_latest = array($truck_id);
			$result_latest = $adb->pquery($query_truck_fuel_latest, $check_params_latest);
			$row_truck_fuel_latest = $adb->fetch_array($result_latest);
			
			$petrol_price_l = @$row_truck_fuel_latest['petrol_price_l'];
			$total_fuel_expense_costing = 	@$row_truck_type_trip_expense['cf_2061'] * $petrol_price_l; //standard_fuel * petrol_price_l
			
			//Total trip expense
			$total_trip_expense = $total_allowance + $parking + $quest_house + $others + $total_fuel_expense_costing;
				
			/*$adb_km = PearDatabase::getInstance();
			$new_id = $adb_km->getUniqueId('vtiger_crmentity');
			
			$adb_km->pquery("INSERT INTO vtiger_crmentity SET crmid = ".$new_id.", smcreatorid =".$_POST['assigned_user_id']." ,
														  smownerid =".$_POST['assigned_user_id'].", setype = 'Kilometer'");
			$adb_km->pquery("INSERT INTO vtiger_crmentityrel SET crmid = ".$truck_id.", module='Truck', relcrmid=".$new_id.", relmodule='Kilometer'");											  
			$adb_km->pquery("INSERT INTO vtiger_kilometer SET kilometerid = ".$new_id.", name='Fleet Mileage'");											  
			$adb_km->pquery("INSERT INTO vtiger_kilometercf SET kilometerid = ".$new_id.", cf_2107 = '".$origin_country_id."', cf_2109 = '".$origin_city_id."',
																cf_2111 = '".$destination_country_id."', cf_2113 = '".$destination_city_id."', 
																cf_2105 = '".$from_date."', cf_2115 = '".$due_leave_km."', cf_2117 = '".$due_arrival_km."',
																cf_2119 = '".$total_km_traveled_during_trip."', cf_2121 = 'Fleet Job'
																");*/
																
			
			//Fuel Costing
			$fuel_expense = array('b_job_charges_id' => '44559', 
								  'b_expected_buy_local_currency_net' => $total_fuel_expense_costing, 
								  'b_type_id' => '46114', 
								  'b_pay_to_id' => '',
								  'label'	=> 'Fuel Expense',
								  'parentmodule' => 'Fleet',
								  'truck_id'	 => $truck_id,
								  );
			$this->saveFleetExpense($recordModel, $request, $fuel_expense);			
			//End of Fuel
			
			//Daily Expense/ Trip EXPENSE
			$trip_expense = array(
								  'b_job_charges_id' => '44560', 
								  'b_expected_buy_local_currency_net' => $total_allowance, 
								  'b_type_id' => '46115', 
								  'b_pay_to_id' => '',
								  'label'	=> 'Daily Expense',
								  'parentmodule' => 'Fleet',
								  'truck_id'	 => $truck_id,
								  );
			$this->saveFleetExpense($recordModel, $request, $trip_expense);					  
			//End Daily Expense/Trip expense
			
			//Trip Parking
			$parking_expense = array(
								  'b_job_charges_id' => '44561', 
								  'b_expected_buy_local_currency_net' => $parking, 
								  'b_type_id' => '46115', 
								  'b_pay_to_id' => '',
								  'label'	=> 'Trip Expense',
								  'parentmodule' => 'Fleet',
								  'truck_id'	 => $truck_id,
								  );
			$this->saveFleetExpense($recordModel, $request, $parking_expense);
			//End Trip Parking
			
			//Guest House Expense
			$quest_house_expense = array(
								  'b_job_charges_id' => '44562', 
								  'b_expected_buy_local_currency_net' => $quest_house, 
								  'b_type_id' => '46115', 
								  'b_pay_to_id' => '',
								  'label'	=> 'Guest House Expense',
								  'parentmodule' => 'Fleet',
								  'truck_id'	 => $truck_id,
								  );
			$this->saveFleetExpense($recordModel, $request, $quest_house_expense);
			//End Guest House Expense
			
			//Misc Expense
			$other_misc_expenses = array(
								  'b_job_charges_id' => '44521', 
								  'b_expected_buy_local_currency_net' => $others, 
								  'b_type_id' => '46115', 
								  'b_pay_to_id' => '',
								  'label'		=> 'Misc Expense',
								  'parentmodule' => 'Fleet',
								  'truck_id'	 => $truck_id,
								  );
			$this->saveFleetExpense($recordModel, $request, $other_misc_expenses);
			//End Misc Expense
			
			//For SubJRER User to add sum up of all expense in fleet expense
			
			//SubJRER Fleet Expense
			$fleet_expenses = array(
								  'b_job_charges_id' => '44563', 
								  'b_expected_buy_local_currency_net' => $total_trip_expense, 
								  'b_type_id' => '46115', 
								  'b_pay_to_id' => '',
								  'label'	=> 'SubJRER Fleet Expense',
								  'parentmodule' => 'Job',
								  'truck_id'	 => $truck_id,	
								  );
			$this->saveFleetExpense($recordModel, $request, $fleet_expenses);
			//End SubJRER Fleet Expense
			
			/*
			check if file belongs to other department or branch 
			then create fleet expense to main owner 
			or update already added JRER against fleet 
			*/
			$current_user = Users_Record_Model::getCurrentUserModel();
			$job_id 			  = $request->get('sourceRecord');
		    $sourceModule_job 	= 'Job';	
	 	    $job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
			if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
			{
				//To Main JRER
				$adb = PearDatabase::getInstance();
				$query_count_job_jrer_buying = "SELECT * FROM vtiger_jobexpencereportcf
															 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
															 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
															 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
															 AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
															 AND vtiger_jobexpencereportcf.cf_2193='Yes' AND vtiger_jobexpencereportcf.cf_2195=?	
															 ";
				$job_id = $job_id;		 
				$params_fleet_jrer = array($job_id, $truck_id);
				$result_fleet_jrer = $adb->pquery($query_count_job_jrer_buying, $params_fleet_jrer);
				$row_fleet_jrer = $adb->fetch_array($result_fleet_jrer);
				
				$count_job_jrer_buying = $adb->num_rows($result_fleet_jrer);			 
				if($count_job_jrer_buying==0)
				{
					$fleet_expenses = array(
								  'b_job_charges_id' => '44563', 
								  'b_expected_buy_local_currency_net' => $total_trip_expense, 
								  'b_type_id' => '46115', 
								  'b_pay_to_id' => '',
								  'label'	=> 'Main JRER Fleet Expense',
								  'parentmodule' => 'Job',
								  'truck_id'	 => $truck_id,
								  );
								  
					//to check its showing or not in Main JRER			  
					$this->saveFleetExpense_MainJRER($recordModel, $request, $fleet_expenses, $job_info_detail);
				}
			}
				
		}
		else{
			//Mileage on due arrival to calcualte total mileage traveled during trip
			$adb_km = PearDatabase::getInstance();
			$new_id = $adb_km->getUniqueId('vtiger_crmentity');
			
			$adb_km->pquery("INSERT INTO vtiger_crmentity SET crmid = ".$new_id.", smcreatorid =".$_POST['assigned_user_id']." ,
														  smownerid =".$_POST['assigned_user_id'].", setype = 'Kilometer'");
			$adb_km->pquery("INSERT INTO vtiger_crmentityrel SET crmid = ".$truck_id.", module='Truck', relcrmid=".$new_id.", relmodule='Kilometer'");											  
			$adb_km->pquery("INSERT INTO vtiger_kilometer SET kilometerid = ".$new_id.", name='Fleet Mileage'");										  
			$adb_km->pquery("INSERT INTO vtiger_kilometercf SET kilometerid = ".$new_id.", cf_2107 = '".$origin_country_id."', cf_2109 = '".$origin_city_id."',
																cf_2111 = '".$destination_country_id."', cf_2113 = '".$destination_city_id."', 
																cf_2105 = '".$from_date."', cf_2115 = '".$due_leave_km."', cf_2117 = '".$due_arrival_km."',
																cf_2119 = '".$total_km_traveled_during_trip."', cf_2121 = 'Fleet Job'
																");
																
																
			if(!empty($fuel_used_during_trip) && $fuel_used_during_trip>0)
			{
			$adb = PearDatabase::getInstance();
			$query_truck_fuel_latest = "select vtiger_fuelcf.cf_2101 as petrol_price_l from vtiger_fuelcf 
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
										INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
										AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
										where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid=? 
										order by vtiger_fuelcf.cf_2093 DESC limit 1
										";							
			$check_params_latest = array($truck_id);
			$result_latest = $adb->pquery($query_truck_fuel_latest, $check_params_latest);
			$row_truck_fuel_latest = $adb->fetch_array($result_latest);
			
			$petrol_price_l = @$row_truck_fuel_latest['petrol_price_l'];
			$final_fuel_expense = 	@$fuel_used_during_trip * $petrol_price_l; //standard_fuel * petrol_price_l
			
			$query_fuel_expense_before = "SELECT * FROM vtiger_jobexpencereport
										INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
										INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid
										where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? 
										AND vtiger_crmentityrel.module='Fleet' AND vtiger_crmentityrel.relmodule='Jobexpencereport'
										AND vtiger_jobexpencereportcf.cf_1453=? ";
			$check_params_rel = array($recordId, 44559); //(fleet_id, b_job_charges_id=fuel)
			$result_fuel_expense_before = $adb->pquery($query_fuel_expense_before, $check_params_rel);
			$row_fuel_expense_before = $adb->fetch_array($result_fuel_expense_before);
			
			$variation_expected_and_actual_buying = $row_fuel_expense_before['cf_1351'] - $final_fuel_expense;
			$query_fuel_expense_after = "UPDATE vtiger_jobexpencereportcf SET cf_1337=?, cf_1343=?, cf_1347=?, cf_1349=?, cf_1353=? WHERE jobexpencereportid=?";
			$check_params_after = array($final_fuel_expense, $final_fuel_expense, $final_fuel_expense, $final_fuel_expense, $variation_expected_and_actual_buying,$row_fuel_expense_before['jobexpencereportid']);
			$result_fuel_expense_after = $adb->pquery($query_fuel_expense_after, $check_params_after);
			
			}
																
		}
		
		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
			$loadUrl = 'index.php?module=Job&relatedModule='.$request->get('module').'&view=Detail&record='.$job_id.'&mode=showRelatedList&tab_label=Fleet';
		
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}	
			
		
		header("Location: $loadUrl");
	}
	
	
	
	public function saveFleetExpense($recordModel, $request, $fleet_expence=array())
	{
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$job_fleet_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$source_id = $request->get('sourceRecord');
		
		
		//INSERT data in JRER expense module from job costing
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));
		
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id) VALUES(?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $current_user->getId(), $current_user->getId(), $source_id);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		$jobexpencereportid = $adb_e->getLastInsertID();
		
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1367 = pay_to
		//cf_1345 = vendor currency
		//cf_1222 = exchange rate
		//cf_1351 = Expected Buy (Local Currency NET)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_2195) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $current_user->get('department_id'), $current_user->get('location_id'), $fleet_expence['b_job_charges_id'], $fleet_expence['b_type_id'], 
										   $fleet_expence['b_expected_buy_local_currency_net'], 'Expence', $fleet_expence['truck_id']);
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		$jobexpencereportcfid = $adb_ecf->getLastInsertID();
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array((($fleet_expence['parentmodule']=='Fleet') ? $recordModel->getId() : $source_id ), $fleet_expence['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
	}
	
	
	public function saveFleetExpense_MainJRER($recordModel, $request, $fleet_expence=array(), $job_info_detail)
	{
		
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$job_fleet_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$source_id = $request->get('sourceRecord');
		
		
		//INSERT data in JRER expense module from job costing
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));
		
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id) VALUES(?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $current_user->getId(), $job_info_detail->get('assigned_user_id'), $source_id);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		$jobexpencereportid = $adb_e->getLastInsertID();
		
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1367 = pay_to
		//cf_1345 = vendor currency
		//cf_1222 = exchange rate
		//cf_1351 = Expected Buy (Local Currency NET)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_2193, cf_2195) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $current_user->get('department_id'), $current_user->get('location_id'), $fleet_expence['b_job_charges_id'], $fleet_expence['b_type_id'], 
										   $fleet_expence['b_expected_buy_local_currency_net'], 'Expence', 'Yes', $fleet_expence['truck_id']);
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		$jobexpencereportcfid = $adb_ecf->getLastInsertID();
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array((($fleet_expence['parentmodule']=='Fleet') ? $recordModel->getId() : $source_id ), $fleet_expence['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		$_SESSION['sendmsg_repeat'] = $request->getModule();
		
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
