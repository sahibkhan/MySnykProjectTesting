<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleettrip_Save_Action extends Vtiger_Save_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('source_module');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'source_module';
		}
		$record = $request->get('record');
		$recordId = $request->get('id');
		if (!$record) {
			$recordParameter = '';
		}else{
			$recordParameter = 'record';
		}
		$actionName = ($record || $recordId) ? 'EditView' : 'CreateView';
        $permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'DetailView', 'record_parameter' => $recordParameter);
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => $actionName, 'record_parameter' => $recordParameter);
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		try {


			$adb = PearDatabase::getInstance();
		
			$due_arrival_km = $request->get('cf_3259');
			$due_leave_km  = $request->get('cf_3257');
			$total_km_traveled_during_trip =  $due_arrival_km - $due_leave_km;
		
		
			$fuel_used_during_trip = $request->get('cf_3273');
			
			$average_consumption = 0;		
			$average_consumption = $request->get('cf_3275');
			
			$expected_from_date = strtotime($request->get('cf_3249'));
			$expected_to_date = strtotime($request->get('cf_3251'));
			$total_allowed_days = (($expected_to_date - $expected_from_date)/ (60 * 60 * 24))+1;
			
			$from_date = date('Y-m-d',strtotime($request->get('cf_3245')));
			$to_date = date('Y-m-d',strtotime($request->get('cf_3247')));
			
			$due_leave_km = $request->get('cf_3257');
			$due_arrival_km = $request->get('cf_3259');		   
			
			
			$origin_country_id	  = $request->get('cf_3237');
			$origin_city_id		 = $request->get('cf_3241');
			$destination_country_id = $request->get('cf_3239');
			$destination_city_id	= $request->get('cf_3243');
			
			$truck_id = $request->get('cf_3165');	
			$truck_info = Vtiger_Record_Model::getInstanceById($truck_id, 'Truck');
			$truck_type_id = $truck_info->get('cf_1911');
			/*
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
			*/
			$trip_template_id = $request->get('cf_4517');
			$query_trip_expense = "SELECT * from vtiger_triptemplatescf 
											INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triptemplatescf.triptemplatesid
											WHERE vtiger_triptemplatescf.triptemplatesid =? AND 
											vtiger_crmentity.deleted = 0
											Limit 1
											";
			$check_params_trip_expense = array($trip_template_id);
			$result_trip_expense = $adb->pquery($query_trip_expense, $check_params_trip_expense);
			$row_truck_type_trip_expense = $adb->fetch_array($result_trip_expense);
			
			$average_consumption = 0;
			if(@$row_truck_type_trip_expense['cf_2061']!=0) //Standard fuel
			{
				//average_consumption =  Standart distance(km) / Standard Fuel
				$average_consumption  = @$row_truck_type_trip_expense['cf_2065'] / @$row_truck_type_trip_expense['cf_2061'];
			}
		
			$request->set('cf_3255', $total_allowed_days);
			$request->set('cf_3273', $fuel_used_during_trip);
			$request->set('cf_3275', number_format($average_consumption, 2, '.', ''));
			$request->set('cf_3277', $truck_info->get('cf_2189')); //average_allowed_consumption
		
			$recordId = $request->get('record');
			if(empty($recordId) || $request->get('isDuplicate') == true) 
			{
				$request->set('cf_3253', $row_truck_type_trip_expense['cf_2067']); //standard days
				$request->set('cf_3261', $row_truck_type_trip_expense['cf_2065']); //standard distance
				$request->set('cf_3271', $row_truck_type_trip_expense['cf_2061']); //standard fuel
			}
		
			$accountant_id = $request->get('cf_4947');

			$recordModel = $this->saveRecord($request);

			if(empty($recordId) || $request->get('isDuplicate') == true) 
				{
					$total_allowance = @$row_truck_type_trip_expense['cf_2071']; //Total Allowance
					$parking = @$row_truck_type_trip_expense['cf_2073']; //parking
					$quest_house = @$row_truck_type_trip_expense['cf_2075']; //guest_house
					$others = @$row_truck_type_trip_expense['cf_2081']; //others
					
					
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
					//$total_trip_expense = $total_allowance + $parking + $quest_house + $others + $total_fuel_expense_costing;
					$total_trip_expense = $total_allowance + $others + $total_fuel_expense_costing;
					
					$current_user = Users_Record_Model::getCurrentUserModel();
					$company_id = $current_user->get('company_id');
					$CompanyAccountTypeList = Vtiger_Field_Model::getCompanyAccountTypeListNew();
					$CompanyAccountType_Bank_R_Key = array_search ('Bank R', $CompanyAccountTypeList);
					$CompanyAccountType_Cash_R_Key = array_search ('Cash R', $CompanyAccountTypeList);
					$local_account_type[] = $CompanyAccountType_Bank_R_Key;
					$local_account_type[] = $CompanyAccountType_Cash_R_Key;
					$local_account  = implode(",",$local_account_type);
					
					//Fuel Costing
					$fuel_expense = array('b_job_charges_id' => '85896', 
										'b_expected_buy_local_currency_net' => $total_fuel_expense_costing, 
										//'b_type_id' => '85785', 
										'b_type_id' => $CompanyAccountType_Bank_R_Key, 								  
										'b_pay_to_id' => '',
										'label'	=> 'Fuel Expense',
										'parentmodule' => 'Fleettrip',
										'truck_id'	 => $truck_id,
										'accountant_id' => $accountant_id
										);
					$this->saveFleetExpense($recordModel, $request, $fuel_expense);	
					//End of Fuel
					
					//AdBlue Expense
					$adblue_expense = array('b_job_charges_id' => '446588', 
										'b_expected_buy_local_currency_net' => '0.00', 
										//'b_type_id' => '85785',
										'b_type_id' => $CompanyAccountType_Bank_R_Key, 
										'b_pay_to_id' => '',
										'label'	=> 'AdBlue Expense',
										'parentmodule' => 'Fleettrip',
										'truck_id'	 => $truck_id,
										'accountant_id' => $accountant_id
										);
					$this->saveFleetExpense($recordModel, $request, $adblue_expense);	
					//End AdBlue Expense
					
					//Daily Expense/ Trip EXPENSE
					$trip_expense = array(
										'b_job_charges_id' => '85868', 
										'b_expected_buy_local_currency_net' => $total_allowance, 
										//'b_type_id' => '85786', 
										'b_type_id' => $CompanyAccountType_Cash_R_Key,								  
										'b_pay_to_id' => '',
										'label'	=> 'Daily Expense',
										'parentmodule' => 'Fleettrip',
										'truck_id'	 => $truck_id,
										'accountant_id' => $accountant_id
										);
					$this->saveFleetExpense($recordModel, $request, $trip_expense);					  
					//End Daily Expense/Trip expense
					
					//Trip Parking		
					$parking_expense = array(
										'b_job_charges_id' => '85898', 
										'b_expected_buy_local_currency_net' => $parking, 
										//'b_type_id' => '85786',
										'b_type_id' => $CompanyAccountType_Cash_R_Key,
										'b_pay_to_id' => '',
										'label'	=> 'Trip Expense',
										'parentmodule' => 'Fleettrip',
										'truck_id'	 => $truck_id,
										'accountant_id' => $accountant_id
										);
					//Mehtab :: Comment they requested to remove :: requested by janibeck no need to add
					//$this->saveFleetExpense($recordModel, $request, $parking_expense);
					//End Trip Parking
					
					//Guest House Expense
					$quest_house_expense = array(
										'b_job_charges_id' => '85899', 
										'b_expected_buy_local_currency_net' => $quest_house, 
										//'b_type_id' => '85786',
										'b_type_id' => $CompanyAccountType_Cash_R_Key,  
										'b_pay_to_id' => '',
										'label'	=> 'Guest House Expense',
										'parentmodule' => 'Fleettrip',
										'truck_id'	 => $truck_id,
										'accountant_id' => $accountant_id
										);
					//Mehtab :: Comment they requested to remove :: requested by janibeck no need to add
					//$this->saveFleetExpense($recordModel, $request, $quest_house_expense);
					//End Guest House Expense
					
					//Misc Expense
					$other_misc_expenses = array(
										'b_job_charges_id' => '85873', 
										'b_expected_buy_local_currency_net' => $others, 
										//'b_type_id' => '85786',
										'b_type_id' => $CompanyAccountType_Cash_R_Key,   
										'b_pay_to_id' => '',
										'label'		=> 'Misc Expense',
										'parentmodule' => 'Fleettrip',
										'truck_id'	 => $truck_id,
										'accountant_id' => $accountant_id
										);
					$this->saveFleetExpense($recordModel, $request, $other_misc_expenses);
					//End Misc Expense
					
					
					//POST Trip Template from Pre Trip Templates
					$trip_template_id = $request->get('cf_4517');
					if(!empty($trip_template_id))
					{
						$this->savePostTripTemplate($recordModel, $trip_template_id);
					}
					
					
				}

			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}


	public function savePostTripTemplate($recordModel, $trip_template_id)
	{
		
		//$source_id = $request->get('sourceRecord');
		$source_id = 0;
		
		/*
		$pagingModel_1 = new Vtiger_Paging_Model();
		$pagingModel_1->set('page','1');
		
		$relatedModuleName_1 = 'PreTrip';
		$parentRecordModel_1 = Vtiger_Record_Model::getInstanceById($trip_template_id, 'TripTemplates');
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);
		*/
		
		$adb_pretrip = PearDatabase::getInstance();
		$pretrip_sql =  "SELECT * FROM `vtiger_pretrip` 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_pretrip.pretripid 
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid) 
 							LEFT JOIN vtiger_pretripcf as vtiger_pretripcf on vtiger_pretripcf.pretripid=vtiger_pretrip.pretripid 
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? 
								  AND vtiger_crmentityrel.module='TripTemplates' AND vtiger_crmentityrel.relmodule='PreTrip' 
								  ";
							  
		$params_pre_trip = array($trip_template_id);
		$result_pretrip = $adb_pretrip->pquery($pretrip_sql, $params_pre_trip);
		
		
		
			//foreach($models_1 as $key => $model){
			for($ij=0; $ij<$adb_pretrip->num_rows($result_pretrip); $ij++) {
			$pre_trip_id = $adb_pretrip->query_result($result_pretrip, $ij, 'pretripid');
			
			$adb = PearDatabase::getInstance();
			$current_user = Users_Record_Model::getCurrentUserModel();
			$ownerId = $recordModel->get('assigned_user_id');
			$date_var = date("Y-m-d H:i:s");
			$usetime = $adb->formatDate($date_var, true);
			$fleet_trip_id = $recordModel->getId();
			
			$current_id = $adb->getUniqueId('vtiger_crmentity');
			
			
			//$pre_trip_id  = $model->getId();			
			
			$sourceModule   = 'PreTrip';	
			$pretrip_info = Vtiger_Record_Model::getInstanceById($pre_trip_id, $sourceModule);
			
			$adb_crm = PearDatabase::getInstance();
			$adb_crm->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $ownerId, $ownerId, 'PostTrip', 'NULL', $date_var, $date_var, 1, 0, 'NULL'));
			
			$adb_p = PearDatabase::getInstance();
			$posttrip_insert_query = "INSERT INTO vtiger_posttrip(posttripid, name) VALUES(?, ?)";
			$params_posttrip= array($current_id, $recordModel->getId());	
			//$params_jobexpencereport= array($current_id, $recordModel->getId(), $current_user->getId(), $current_user->getId(), $source_id, $fleet_trip_id);			
			$adb_p->pquery($posttrip_insert_query, $params_posttrip);			
			//$posttripid = $adb_p->getLastInsertID();
			$posttripid = $current_id;
			
			$pretrip_checklist = $pretrip_info->get('cf_4311');
			$pretrip_rate = $pretrip_info->get('cf_4303');
			$pretrip_maximum_value = $pretrip_info->get('cf_4305');
			$pretrip_units = $pretrip_info->get('cf_4307');
			//$pretrip_total_in_tenge = $pretrip_info->get('cf_4309');
			$pretrip_total_in_tenge = $pretrip_rate * $pretrip_maximum_value;
			$pretrip_type = $pretrip_info->get('cf_4313');
			
			$adb_pcf = PearDatabase::getInstance(); //cf_4319=post trip unit
			$posttripcf_insert_query = "INSERT INTO vtiger_posttripcf(posttripid, cf_4315, cf_4333, cf_4323, cf_4325, cf_4331, cf_4327, cf_4329) 
												VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
			$params_posttripcf = array($current_id, $pretrip_checklist, $pretrip_type, $pretrip_rate, $pretrip_maximum_value, $pretrip_units, $pretrip_total_in_tenge, $pretrip_units);
			$adb_pcf->pquery($posttripcf_insert_query, $params_posttripcf);
			//$posttripidcf = $adb_pcf->getLastInsertID();
			$posttripidcf = $current_id;
			
			$adb_rel = PearDatabase::getInstance();
			$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
			$params_crmentityrel = array($recordModel->getId(), 'Fleettrip', $posttripidcf, 'PostTrip');
			$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);			
		}
		
		
	}
	
	
	public function saveFleetExpense($recordModel, $request, $fleet_expence=array())
	{
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$fleet_trip_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		//$source_id = $request->get('sourceRecord');
		$source_id = 0;
		
		//INSERT data in JRER expense module from job costing
		//458 = Olga Sadykova
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $fleet_expence['accountant_id'], $fleet_expence['accountant_id'], 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));
			//array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));
		
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id, fleettrip_id) VALUES(?,?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $fleet_expence['accountant_id'], $fleet_expence['accountant_id'], $source_id, $fleet_trip_id);	
		//$params_jobexpencereport= array($current_id, $recordModel->getId(), $current_user->getId(), $current_user->getId(), $source_id, $fleet_trip_id);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		//$jobexpencereportid = $adb_e->getLastInsertID();
		$jobexpencereportid = $current_id;
		
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1367 = pay_to
		//cf_1345 = vendor currency
		//cf_1222 = exchange rate
		//cf_1351 = Expected Buy (Local Currency NET)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_2195, cf_1337, cf_1343, cf_1347, cf_1349, cf_1353) 
											VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $current_user->get('department_id'), $current_user->get('location_id'), $fleet_expence['b_job_charges_id'], $fleet_expence['b_type_id'], 
										   $fleet_expence['b_expected_buy_local_currency_net'], 'Expence', $fleet_expence['truck_id'], 0, 0, 0, 0, 0);
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		//$jobexpencereportcfid = $adb_ecf->getLastInsertID();
		$jobexpencereportcfid = $current_id;
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array($recordModel->getId(), $fleet_expence['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
		
		
		//For Fleet Owner
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $fleet_expence['accountant_id'], $fleet_expence['accountant_id'], 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));
			//array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));
		
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id, fleettrip_id, jrer_buying_id) VALUES(?,?,?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $fleet_expence['accountant_id'], $current_user->getId(), $source_id, $fleet_trip_id, $jobexpencereportcfid);	
		//$params_jobexpencereport= array($current_id, $recordModel->getId(), $current_user->getId(), $current_user->getId(), $source_id, $fleet_trip_id);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		//$jobexpencereportid = $adb_e->getLastInsertID();
		$jobexpencereportid = $current_id;
		
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1367 = pay_to
		//cf_1345 = vendor currency
		//cf_1222 = exchange rate
		//cf_1351 = Expected Buy (Local Currency NET)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_2195, cf_1337, cf_1343, cf_1347, cf_1349, cf_1353) 
											VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $current_user->get('department_id'), $current_user->get('location_id'), $fleet_expence['b_job_charges_id'], $fleet_expence['b_type_id'], 
										   $fleet_expence['b_expected_buy_local_currency_net'], 'Expence', $fleet_expence['truck_id'], 0, 0, 0, 0, 0);
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		//$jobexpencereportcfid = $adb_ecf->getLastInsertID();
		$jobexpencereportcfid = $current_id;
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array($recordModel->getId(), $fleet_expence['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
		
		
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		global $adb;
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}

		$recordId = $request->get('record');
		//Round Trip Status
		$old_fleet_trip_status = '';
		if(!empty($recordId))
		{
		$fleet_trip_info = Vtiger_Record_Model::getInstanceById($recordId, 'Fleettrip');
		$old_fleet_trip_status = $fleet_trip_info->get('cf_4803');
		}

		$recordModel->save();

		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}

		
		$recordId = $request->get('record');
		if(empty($recordId))
		{
			
			$sql =  'SELECT MAX(fleet_serial_number) as max_ordering from vtiger_fleettrip 
					 INNER JOIN vtiger_fleettripcf ON vtiger_fleettripcf.fleettripid = vtiger_fleettrip.fleettripid
					 where vtiger_fleettrip.year_no=? AND vtiger_fleettripcf.cf_3165=?';
				 
			$value = date('Y');
			$params = array($value, $request->get('cf_3165'));
			$result = $adb->pquery($sql, $params);
			$row = $adb->fetch_array($result);
			if($adb->num_rows($result)==0 or !$row)
			{
				$ordering = 0;
			}
			else{
				$max_ordering = $row["max_ordering"];
				if ( ! is_numeric($max_ordering))
				{
					$ordering = 0;
				}
				else
				{
					$ordering = $max_ordering;
				}
			}
			$fleet_serial_number = $ordering+1;
			
			//cf_3247= fleet ref no
			$truck_id 			  = $request->get('cf_3165');
			$sourceModule_truck 	= 'Truck';	
			$truck_info_detail = Vtiger_Record_Model::getInstanceById($truck_id, $sourceModule_truck);
			$truck_no = $truck_info_detail->getDisplayValue('name');
			$adb->pquery('update vtiger_fleettrip set year_no=?, fleet_serial_number = ? where fleettripid=?', array( date('Y'), $fleet_serial_number, $recordModel->getId() ) );
			$fleet_ref_no = strtoupper($truck_no).'-'.str_pad($fleet_serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y');
			$adb->pquery('update vtiger_fleettripcf set cf_4803=?, cf_3283 = ? where fleettripid=?', array('In Progress', $fleet_ref_no, $recordModel->getId()));			
			
		}
		else{
			
			if($old_fleet_trip_status=='Completed')
			{
				$adb->pquery("update vtiger_fleettripcf set cf_4803='Completed' where fleettripid='".$recordId."'", array());
				$current_user = Users_Record_Model::getCurrentUserModel();
				//if($current_user->getId()==463 || $current_user->getId()==458)
				if($current_user->getId()==420 || $current_user->getId()==458)
				{
					$adb->pquery("update vtiger_fleettripcf set cf_4803='".$request->get('cf_4803')."' where fleettripid='".$recordId."'",array());
				}
			}
			
		}

		$this->savedRecordId = $recordModel->getId();
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
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
