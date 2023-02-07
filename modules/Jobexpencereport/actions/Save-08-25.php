<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Jobexpencereport_Save_Action extends Vtiger_Save_Action {

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
	
	public function get_parent_module($recordId=0)
	{
		$adb = PearDatabase::getInstance();
		
		$query_job_expense =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.relmodule="Jobexpencereport"';
		$check_params = array($recordId);
		$result       = $adb->pquery($query_job_expense, $check_params);
		$row          = $adb->fetch_array($result);
		$module 	   = $row['module'];
		//$sourceModule = $row['module'];
		return $module;
	}

	public function process(Vtiger_Request $request) {
		
		$type = $request->get('cf_1457');
		
		$recordId = $request->get('record');
		$job_id = $this->get_job_id($recordId);
		
		$profit_share_count = $request->get('profit_share_count');
		if($profit_share_count>0)
		{
			$fleet_record_id = $request->get('fleet_record_id');
			if(isset($fleet_record_id) && !empty($fleet_record_id))
			{
				$this->profit_share_fleet($request);
			}
			else{
				$this->profit_share($request);
			}
		}
		
		$parent_module='Job';
		$module_label='Job%20Revenue%20and%20Expence';
				
		if($type=='Expence')
		{
			$parent_crmmodule = $this->get_parent_module($recordId);
			
			if($parent_crmmodule!='Fleettrip')
			{
				$fleet_expense  = $this->get_fleet_id_from_expense($recordId);
				
				if(empty($fleet_expense))
				{				
					$recordModel =  $this->buying_edit($request);
					$parent_module='Job';
					$module_label='Job%20Revenue%20and%20Expence';	
				}
				else{
					$recordModel =  $this->fleet_buying_edit($request);
					//$job_id = $fleet_expense;
					$fleet_id = $this->get_fleet_id_from_expense($recordId);
					$job_id  = $fleet_id;	
					$parent_module='Fleet';	
					$module_label='Fleet Expense';	
				}
			}
			else{
				
				$recordModel =  $this->round_trip_buying_edit($request);
				//$job_id = $fleet_expense;
				$fleet_trip_id = $this->get_round_trip_id_from_expense($recordId);
				$job_id  = $fleet_trip_id;	
				$parent_module='Fleettrip';	
				$module_label='Fleet Expense';						
			}
		}
		else{
			//$this->selling_edit($request);
			//$recordModel = $this->saveRecord($request);
			$fleet_expense  = $this->get_fleet_id_from_expense($recordId);
			
			if(empty($fleet_expense))
			{
				$recordModel =  $this->selling_edit($request);
				$parent_module='Job';	
				$module_label='Job%20Revenue%20and%20Expence';
			}
			else{
				$recordModel =  $this->fleet_selling_edit($request);
				//$job_id = $fleet_expense;
				$fleet_id = $this->get_fleet_id_from_expense($recordId);
				$job_id  = $fleet_id;
				$parent_module='Fleet';
				$module_label='Fleet Expense';			
			}
		}
		
		//$recordModel = $this->saveRecord($request);
		if($request->get('relationOperation')) {
			
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			
			//$loadUrl = $recordModel->getDetailViewUrl();
			$loadUrl = 'index.php?module='.$parent_module.'&relatedModule='.$request->get('module').
				'&view=Detail&record='.$job_id.'&mode=showRelatedList&tab_label='.$module_label.'';
		}
		//http://mb.globalink.net/vt60/
		//index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record=51688&mode=showRelatedList&tab_label=Job%20Revenue%20and%20Expence
		
		header("Location: $loadUrl");
		exit;
	}
	
	public function get_job_id_from_fleet($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
		 $checkjob = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1 
				  							INNER JOIN vtiger_crmentityrel as rel2 ON rel1.relcrmid = rel2.crmid 
											where rel2.relcrmid='".$recordId."'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}
	
	public function get_fleet_id_from_expense($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
		 $checkfleet = $adb->pquery("SELECT rel1.crmid as fleet_id FROM `vtiger_crmentityrel` as rel1 where rel1.relcrmid='".$recordId."' and rel1.module='Fleet'", array());
		 $crmId = $adb->query_result($checkfleet, 0, 'fleet_id');
		 $fleet_id = $crmId;
		 return $fleet_id;		  
	}
	
	public function get_round_trip_id_from_expense($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
		 $checkfleet = $adb->pquery("SELECT rel1.crmid as fleet_trip_id FROM `vtiger_crmentityrel` as rel1 where rel1.relcrmid='".$recordId."' and rel1.module='Fleettrip'", array());
		 $crmId = $adb->query_result($checkfleet, 0, 'fleet_trip_id');
		 $fleet_trip_id = $crmId;
		 return $fleet_trip_id;		  
	}
	
	public function profit_share_fleet($request)
	{
		
		$internal_selling_arr = $request->get('internal_selling');
		if(!empty($internal_selling_arr))
		{
			foreach($internal_selling_arr as $key => $internal_selling)
			{
				//Office[0]-department[1]
				$branch_deparment = $key;
				$branch_department_arr = explode('-', $branch_deparment);
				$internal_selling_value = $internal_selling;
				$location_id = $branch_department_arr[0];
				$department_id = $branch_department_arr[1];
				$job_id = $request->get('record');
				
				$current_user = Users_Record_Model::getCurrentUserModel();
				
							
				//For fleet inernal selling
				 $fleet_record_id = $request->get('fleet_record_id');
				 $db_internal_fleet_count = PearDatabase::getInstance();
				 $jrer_internal_fleet_count = 'SELECT * FROM vtiger_jobexp
				 						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
						  			    INNER JOIN vtiger_jobexpcf ON vtiger_jobexpcf.jobexpid = vtiger_jobexp.jobexpid	
						 			    INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexp.jobexpid										 
						 			    where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=? 
						 	   		  ';
				 	  
				 $params_internal_fleet_count = array($fleet_record_id, $location_id, $department_id);				
				 $result_internal_fleet_count = $db_internal_fleet_count->pquery($jrer_internal_fleet_count,$params_internal_fleet_count);
				 if($db_internal_fleet_count->num_rows($result_internal_fleet_count)==0)
				 {					  
				  
				 $adb_fleet = PearDatabase::getInstance();
			     $date_var = date("Y-m-d H:i:s");
			     $usetime = $adb_fleet->formatDate($date_var, true);
			   
			     $current_fleet_id = $adb_fleet->getUniqueId('vtiger_crmentity');
			     $source_fleet_id = $fleet_record_id;
				  
				 $adb_fleet->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				 setype, description, createdtime, modifiedtime, presence, deleted, label)
				 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				 array($current_fleet_id, $current_user->getId(), $current_user->getId(), 'Jobexp', 'NULL', $date_var, $date_var, 1, 0, $branch_deparment));
			
				$adb_fleet_e = PearDatabase::getInstance();
				$jobexp_fleet_insert_query = "INSERT INTO vtiger_jobexp(jobexpid, name, truck_id) VALUES(?,?,?)";
				$params_fleet_jobexp= array($current_fleet_id, $source_fleet_id, $request->get('truck_id'));		
				$adb_fleet_e->pquery($jobexp_fleet_insert_query, $params_fleet_jobexp);			
				$jobexpid_fleet = $adb_fleet_e->getLastInsertID();
				
				$adb_fleet_ecf = PearDatabase::getInstance();
				$jobexpcf_fleet_insert_query = "INSERT INTO vtiger_jobexpcf(jobexpid, cf_1257, cf_1259, cf_1263) VALUES(?, ?, ?, ?)";
				$params_fleet_jobexpcf = array($current_fleet_id, $location_id, $department_id, $internal_selling_value);
				$adb_fleet_ecf->pquery($jobexpcf_fleet_insert_query, $params_fleet_jobexpcf);
				$jobexpcfid_fleet = $adb_fleet_ecf->getLastInsertID();
				
				$adb_rel_fleet = PearDatabase::getInstance();
				$crmentityrel_fleet_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel_fleet = array($source_fleet_id, 'Fleet', $jobexpcfid_fleet, 'Jobexp');
				$adb_rel_fleet->pquery($crmentityrel_fleet_insert_query, $params_crmentityrel_fleet);
				}
				else{
					$jrer_internal_arr_data_fleet = $db_internal_fleet_count->fetch_array($result_internal_fleet_count);
					$adb_ecf_fleet = PearDatabase::getInstance();
					$jobexpcf_update_query_fleet = "update vtiger_jobexpcf 
											  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpcf.jobexpid
											  set vtiger_jobexpcf.cf_1257=?, vtiger_jobexpcf.cf_1259=?, vtiger_jobexpcf.cf_1263=? WHERE vtiger_jobexpcf.jobexpid=? AND vtiger_crmentity.smcreatorid=?";
										  
					$params_jobexpcf_up_fleet = array($location_id, $department_id, $internal_selling_value, $jrer_internal_arr_data_fleet['jobexpid'], $current_user->getId());
					$adb_ecf_fleet->pquery($jobexpcf_update_query_fleet, $params_jobexpcf_up_fleet);
					//$jobexpcfid = $adb_ecf->getLastInsertID();					
				}
				
				 $adb = PearDatabase::getInstance();
			     $date_var = date("Y-m-d H:i:s");
			     $usetime = $adb->formatDate($date_var, true);
			   
			     $current_id = $adb->getUniqueId('vtiger_crmentity');
			     $source_id = $job_id;
				 
				 $db_internal_count = PearDatabase::getInstance();
				 $jrer_internal_count = 'SELECT * FROM vtiger_jobexp
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
						  			    INNER JOIN vtiger_jobexpcf ON vtiger_jobexpcf.jobexpid = vtiger_jobexp.jobexpid	
						 			    INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexp.jobexpid
						 			    where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_jobexpcf.cf_1257=? AND vtiger_jobexpcf.cf_1259=? 
						 	   		  ';
				$params_internal_count = array($job_id, $location_id, $department_id);
				$result_internal_count = $db_internal_count->pquery($jrer_internal_count,$params_internal_count);
				
				$truck_internal_selling = 0;
				
				if($db_internal_count->num_rows($result_internal_count)==0)
				{					  
				 
				 $adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				 setype, description, createdtime, modifiedtime, presence, deleted, label)
				 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				 array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexp', 'NULL', $date_var, $date_var, 1, 0, $branch_deparment));
			
				$adb_e = PearDatabase::getInstance();
				$jobexp_insert_query = "INSERT INTO vtiger_jobexp(jobexpid, name) VALUES(?,?)";
				$params_jobexp= array($current_id, $source_id);		
				$adb_e->pquery($jobexp_insert_query, $params_jobexp);			
				$jobexpid = $adb_e->getLastInsertID();
				
				$adb_ecf = PearDatabase::getInstance();
				$jobexpcf_insert_query = "INSERT INTO vtiger_jobexpcf(jobexpid, cf_1257, cf_1259, cf_1263) VALUES(?, ?, ?, ?)";
				$params_jobexpcf = array($current_id, $location_id, $department_id, $internal_selling_value);
				$adb_ecf->pquery($jobexpcf_insert_query, $params_jobexpcf);
				$jobexpcfid = $adb_ecf->getLastInsertID();
				
				$adb_rel = PearDatabase::getInstance();
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($source_id, 'Job', $jobexpcfid, 'Jobexp');
				$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
				}
				else{
					$truck_insertal_selling = 0;
					
					$adb_rel_j = PearDatabase::getInstance();
					$jobexp_j = "SELECT * from vtiger_crmentity
									 INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.crmid
									 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.module=? 
									 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.relmodule=?";
					
					$params_j = array('Job', $job_id, 'Fleet');
					
					$result_rel_j = $adb_rel_j->pquery($jobexp_j, $params_j);
					$numRows_rel_j = $adb_rel_j->num_rows($result_rel_j);
					
					for($jjj=0; $jjj< $adb_rel_j->num_rows($result_rel_j); $jjj++ ) {
						$row_rel_j = $adb_rel_j->fetch_row($result_rel_j,$jjj);
						$fleet_record_id = $row_rel_j['relcrmid'];
						
						$adb_rel_fleet = PearDatabase::getInstance();
						$jobexp_fleet = "SELECT * from vtiger_crmentity
										 INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.crmid
										 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.module=? 
										 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.relmodule=?";
						
						$params_fleet = array('Fleet', $fleet_record_id, 'Jobexp');
						
						$result_rel_fleet = $adb_rel_fleet->pquery($jobexp_fleet, $params_fleet);
						$numRows_rel_fleet = $adb_rel_fleet->num_rows($result_rel_fleet);
						
						//For all trucks internal selling
						for($jj=0; $jj< $adb_rel_fleet->num_rows($result_rel_fleet); $jj++ ) {
							$row_rel_fleet = $adb_rel_fleet->fetch_row($result_rel_fleet,$jj);
							$jobexpid = $row_rel_fleet['relcrmid'];
							
							$db_fleet_ps = PearDatabase::getInstance();
							$fleet_ps = 'SELECT * FROM vtiger_jobexp
										 INNER JOIN vtiger_jobexpcf ON vtiger_jobexpcf.jobexpid = vtiger_jobexp.jobexpid	
										 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
										 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentity.crmid=? AND vtiger_jobexpcf.cf_1257=? AND vtiger_jobexpcf.cf_1259=? 
												  ';
							$params_ps = array($jobexpid, $location_id, $department_id);
							$result_ps = $db_fleet_ps->pquery($fleet_ps,$params_ps);
							$fleet_internal_ps = $db_fleet_ps->fetch_array($result_ps);
							$truck_internal_selling += $fleet_internal_ps['cf_1263'];
						}
									
						
					}
					
						
					
					$jrer_internal_arr_data = $db_internal_count->fetch_array($result_internal_count);
					
					$adb_ecf = PearDatabase::getInstance();
					$jobexpcf_update_query = "update vtiger_jobexpcf 
											  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpcf.jobexpid
											  set vtiger_jobexpcf.cf_1257=?, vtiger_jobexpcf.cf_1259=?, vtiger_jobexpcf.cf_1263=? 
											  WHERE vtiger_jobexpcf.jobexpid=? AND vtiger_crmentity.smcreatorid=?";
									  
					$params_jobexpcf_up = array($location_id, $department_id, $truck_internal_selling, $jrer_internal_arr_data['jobexpid'], $current_user->getId());
					$adb_ecf->pquery($jobexpcf_update_query, $params_jobexpcf_up);
										//$jobexpcfid = $adb_ecf->getLastInsertID();					
				}
								
			}		
		}
		
		$loadUrl = 'index.php?module=Fleet&relatedModule='.$request->get('module').
				'&view=Detail&record='.$request->get('fleet_record_id').'&mode=showRelatedList&tab_label=Fleet%20Expense';
		header("Location: $loadUrl");		
					
	
	}
	
	public function profit_share($request)
	{
		$internal_selling_arr = $request->get('internal_selling');
		if(!empty($internal_selling_arr))
		{
			foreach($internal_selling_arr as $key => $internal_selling)
			{
				//Office[0]-department[1]
				$branch_deparment = $key;
				$branch_department_arr = explode('-', $branch_deparment);
				$internal_selling_value = $internal_selling;
				$location_id = $branch_department_arr[0];
				$department_id = $branch_department_arr[1];
				$job_id = $request->get('record');
				
				$current_user = Users_Record_Model::getCurrentUserModel();
				
				 $adb = PearDatabase::getInstance();
			     $date_var = date("Y-m-d H:i:s");
			     $usetime = $adb->formatDate($date_var, true);
			   
			     $current_id = $adb->getUniqueId('vtiger_crmentity');
			     $source_id = $job_id;
				 
				 $db_internal_count = PearDatabase::getInstance();
				$jrer_internal_count = 'SELECT * FROM vtiger_jobexp
						  			    INNER JOIN vtiger_jobexpcf ON vtiger_jobexpcf.jobexpid = vtiger_jobexp.jobexpid	
						 			    INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexp.jobexpid
						 			    where vtiger_crmentityrel.crmid=? and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=? 
						 	   		  ';
				$params_internal_count = array($job_id, $location_id, $department_id);
				$result_internal_count = $db_internal_count->pquery($jrer_internal_count,$params_internal_count);
				
				if($db_internal_count->num_rows($result_internal_count)==0)
				{					  
				 
				 $adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				 setype, description, createdtime, modifiedtime, presence, deleted, label)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexp', 'NULL', $date_var, $date_var, 1, 0, $branch_deparment));
			
				$adb_e = PearDatabase::getInstance();
				$jobexp_insert_query = "INSERT INTO vtiger_jobexp(jobexpid, name) VALUES(?,?)";
				$params_jobexp= array($current_id, $source_id);		
				$adb_e->pquery($jobexp_insert_query, $params_jobexp);			
				$jobexpid = $adb_e->getLastInsertID();
				
				$adb_ecf = PearDatabase::getInstance();
				$jobexpcf_insert_query = "INSERT INTO vtiger_jobexpcf(jobexpid, cf_1257, cf_1259, cf_1263) VALUES(?, ?, ?, ?)";
				$params_jobexpcf = array($current_id, $location_id, $department_id, $internal_selling_value);
				$adb_ecf->pquery($jobexpcf_insert_query, $params_jobexpcf);
				$jobexpcfid = $adb_ecf->getLastInsertID();
				
				$adb_rel = PearDatabase::getInstance();
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($source_id, 'Job', $jobexpcfid, 'Jobexp');
				$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
				}
				else{
					$jrer_internal_arr_data = $db_internal_count->fetch_array($result_internal_count);
					$adb_ecf = PearDatabase::getInstance();
					$jobexpcf_update_query = "update vtiger_jobexpcf 
											  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpcf.jobexpid
											  set vtiger_jobexpcf.cf_1257=?, vtiger_jobexpcf.cf_1259=?, vtiger_jobexpcf.cf_1263=? WHERE vtiger_jobexpcf.jobexpid=? AND vtiger_crmentity.smcreatorid=?";
										  
					$params_jobexpcf_up = array($location_id, $department_id, $internal_selling_value, $jrer_internal_arr_data['jobexpid'], $current_user->getId());
					$adb_ecf->pquery($jobexpcf_update_query, $params_jobexpcf_up);
					//$jobexpcfid = $adb_ecf->getLastInsertID();
					
				}				
			}		
		}
		
		$loadUrl = 'index.php?module=Job&relatedModule='.$request->get('module').
				'&view=Detail&record='.$request->get('record').'&mode=showRelatedList&tab_label=Job%20Revenue%20and%20Expence';
		header("Location: $loadUrl");		
					
	}
	
	public function selling_edit($request)
	{
		$recordId = $request->get('record');
		$job_id = $this->get_job_id($recordId);
		
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
				
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		include("include/Exchangerate/exchange_rate_class.php");
		
		$assigned_extra_selling = array();
		$s_generate_invoice_instruction_flag = true;
		$invoice_instruction = '';
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$s_job_charges_id = $request->get('cf_1455');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$s_office_id	  = $current_user->get('location_id');
			//$s_department_id  = $current_user->get('department_id');
			$s_department_id  = $request->get('cf_1479');
		}else{
			$s_office_id	  = $request->get('cf_1477');
			$s_department_id  = $request->get('cf_1479');
		}
		$s_bill_to_id 		  = $request->get('cf_1445');
		$s_bill_to_address 	  = $request->get('cf_1359');
		$s_ship_to 			  = $request->get('cf_1361');
		$s_ship_to_address 	  = $request->get('cf_1363');
		$s_remarks 			  = $request->get('cf_1365');
		$s_invoice_date 	  = $request->get('cf_1355');
		$s_selling_customer_currency_net = $request->get('cf_1357');
		$s_vat_rate 		  = $request->get('cf_1228');
		$vat_included = $request->get('cf_2695');
		
		
		$s_vat = '0.00';
		$s_selling_customer_currency_gross = '0.00';
		if(!empty($s_vat_rate) && $s_vat_rate>0)
		{
			if($vat_included=='Yes')
			{
				$s_selling_customer_currency_gross = $request->get('cf_1232');
				$s_vat_rate_ = $s_vat_rate + 100;
				$s_vat_rate_cal = $s_vat_rate_/100; 
				
				$s_selling_customer_currency_net = 	$s_selling_customer_currency_gross / $s_vat_rate_cal;
				
				$s_vat = $s_selling_customer_currency_gross - $s_selling_customer_currency_net;

			}
			else{
			$s_vat_rate_cal = $s_vat_rate/100; 
			$s_vat = 	$s_selling_customer_currency_net * $s_vat_rate_cal;
			}
		}
		
		if($vat_included=='Yes')
		{
			$s_sell_customer_currency_gross =$s_selling_customer_currency_gross;
			//$s_sell_customer_currency_net = $s_selling_customer_currency_net; 
		}
		else{
			$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
		}
		
		$s_customer_currency = $request->get('cf_1234');
		
		//$job_id 			  = $request->get('sourceRecord');
		//$sourceModule 		  = $request->get('sourceModule');	
		//$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		
		$invoice_date_arr = explode('-',$s_invoice_date);
		$s_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date_final));
		
		//$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date));
		$s_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
		
		if($file_title_currency =='KZT')
		{			
			$s_exchange_rate  		= exchange_rate_currency($s_invoice_date_format, $s_customer_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$s_exchange_rate = currency_rate_convert($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
		}
		else{			
			$s_exchange_rate = currency_rate_convert_others($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
		}
		
		
		if($file_title_currency !='USD')
		{	
			$s_sell_local_currency_gross = $s_sell_customer_currency_gross * $s_exchange_rate;
		}else{
			$s_sell_local_currency_gross = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $s_sell_customer_currency_gross, $s_invoice_date_format);
		}
		
		if($file_title_currency !='USD')
		{	
			$s_sell_local_currency_net = $s_selling_customer_currency_net * $s_exchange_rate;
		}else{
			$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $s_selling_customer_currency_net, $s_invoice_date_format);
		}
		
		
		$s_expected_sell_local_currency_net = $request->get('cf_1242');				
		$ss_expected_sell_local_currency_net = $request->get('cf_1242');
		
		//$s_variation_expected_and_actual_sellling  = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		//$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		$job_jrer_selling = array();
		if($recordId!=0)
		{
			$db_selling = PearDatabase::getInstance();						
			$jrer_selling = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   WHERE vtiger_crmentityrel.crmid=? AND vtiger_jobexpencereport.owner_id=? AND vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   AND vtiger_jobexpencereportcf.cf_1457="Selling" ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC limit 1';
			//jobid as record_id						   
			$params_selling = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			$result_selling = $db_selling->pquery($jrer_selling, $params_selling);
			$job_jrer_selling = $db_selling->fetch_array($result_selling);
			
			$job_costing_id = @$job_jrer_selling['jerid'];
			
			if(!empty($job_costing_id))
			{													   
			$db_costing_1 = PearDatabase::getInstance();		
			$query_costing = 'SELECT * FROM vtiger_jer
								   INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
								   where vtiger_crmentityrel.crmid=? and vtiger_jer.jerid=? AND vtiger_jercf.cf_1451=? '; //AND vtiger_jercf.cf_1433=? AND vtiger_jercf.cf_1435=?
			//$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id, $s_job_charges_id, $s_office_id, $s_department_id);
			//Because owner can also add selling of assigned user
			$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id, $s_job_charges_id);
			
			$result_costing_1 = $db_costing_1->pquery($query_costing, $params_costing_1);
			$sell_of_local_currency = $db_costing_1->fetch_array($result_costing_1);  	
			//sell_local_currency
			$s_expected_sell_local_currency_net = @$sell_of_local_currency['cf_1168'];
			
			}			
		}
		
		$s_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.selling_expence="no"
								   and vtiger_jobexpencereportcf.cf_1457="Expence" limit 1';
			$params_buying_exp = array($job_id, 
									   $s_job_charges_id, 
									   $s_office_id, 
									   $s_department_id,
									   $current_user->getId()								  
									  );
							  
			$result_buying_exp = $db_buying_exp->pquery($jrer_buying_exp,$params_buying_exp);
			$b_variation_expected_and_actual_buying_arr = $db_buying_exp->fetch_array($result_buying_exp);	
		}
		else{			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.user_id =?
								   and vtiger_jobexpencereportcf.cf_1457="Expence" limit 1';
			$params_buying_exp = array($job_id, 
									   $s_job_charges_id, 
									   $s_office_id, 
									   $s_department_id,
									   $current_user->getId(),
									   $current_user->getId(),
									  );
									  
			$result_buying_exp = $db_buying_exp->pquery($jrer_buying_exp,$params_buying_exp);
			$b_variation_expected_and_actual_buying_arr = $db_buying_exp->fetch_array($result_buying_exp);	
		}
		
		$s_variation_expect_and_actual_profit = $s_variation_expected_and_actual_sellling + @$b_variation_expected_and_actual_buying_arr['cf_1353'];				
		$ss_variation_expect_and_actual_profit = $ss_variation_expected_and_actual_sellling + @$b_variation_expected_and_actual_buying_arr['cf_1353'];
		
		$db_revenue = PearDatabase::getInstance();
		$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_revenue = array('Revenue');
		$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
		$gl_job_charges_id = $db_revenue->fetch_array($result_revenue);
		
		$db_coa = PearDatabase::getInstance();
		$query_coa = 'SELECT * FROM vtiger_companyaccount
					  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
					  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
					  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
		$params_coa = array($job_info->get('cf_1186'), $gl_job_charges_id['chartofaccountid']);
		$result_coa = $db_coa->pquery($query_coa, $params_coa);
		$coa_info = $db_coa->fetch_array($result_coa);
		
		if($job_office_id==$current_user_office_id){
			$file_title_id = $job_info->get('cf_1186');
		}
		else{
			$file_title_id = $current_user->get('company_id');
		}
		
		//Revenue
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));
		}
		else{			
			$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
			$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);
		}		
		$gl_account = $coa_info['name'].'-'.$office.'-'.$department;
		
		$db_ar = PearDatabase::getInstance();
		$query_ar = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_ar = array('Trade Debtors');
		$result_ar = $db_ar->pquery($query_ar,$params_ar);
		$ar_job_charges_id = $db_ar->fetch_array($result_ar);
		
		if($job_info->get('assigned_user_id')!=$current_user->getId() && $job_office_id!=$current_user->get('location_id'))
		{
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ar_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ar_coa_info = $db_coa->fetch_array($result_coa);
		}
		else{
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ar_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ar_coa_info = $db_coa->fetch_array($result_coa);
		}					
		$s_ar_gl_account = $ar_coa_info['name'].'-'.$office.'-'.$department;
		
		$extra_selling['s_generate_invoice_instruction'] = 0;
		$extra_selling_normal['s_generate_invoice_instruction'] = 0;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			
			if($job_office_id==$current_user_office_id){
				$file_title_id = $job_info->get('cf_1186');
			}
			else{
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? AND user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				
			}
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
			
			$ss_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
			if($assigned_file_title_currency == 'KZT')
			{						
				$ss_exchange_rate = exchange_rate_currency($s_invoice_date_format, $ss_customer_currency_code);					
			}
			elseif($assigned_file_title_currency =='USD')
			{
				$ss_exchange_rate = currency_rate_convert($ss_customer_currency_code, $assigned_file_title_currency, 1, $s_invoice_date_format);
			}
			else{
				$ss_exchange_rate = currency_rate_convert_others($ss_customer_currency_code, $assigned_file_title_currency, 1, $s_invoice_date_format);
			}
						
			if($assigned_file_title_currency !='USD')
			{						
				$ss_sell_local_currency_gross = $s_sell_customer_currency_gross * $ss_exchange_rate;
			}else{
				$ss_sell_local_currency_gross = exchange_rate_convert($ss_customer_currency_code, $assigned_file_title_currency,$s_sell_customer_currency_gross,$s_invoice_date_format);			
			}
			
			if($assigned_file_title_currency !='USD')
			{	
				$ss_sell_local_currency_net = $s_selling_customer_currency_net * $ss_exchange_rate;
			}else{
				$ss_sell_local_currency_net = exchange_rate_convert($ss_customer_currency_code, $assigned_file_title_currency, $s_selling_customer_currency_net, $s_invoice_date_format);
			}
			
			$extra_selling = array(
									//'user_id' => $this->current_user->id,
									'job_id'  		   => $job_id,
									'date_added'	   => date('Y-m-d H:i:s'),
									's_job_charges_id' => $s_job_charges_id,
									's_department_id'  => $s_department_id,
									's_office_id' 	   => $s_office_id,
									's_customer_id'    => $s_bill_to_id,
									's_bill_to_id'     => $s_bill_to_id,
									's_bill_to_address'=> $s_bill_to_address,
									's_ship_to'  	   => $s_ship_to,
									's_ship_to_address'=> $s_ship_to_address,
									's_remarks'   	   => $s_remarks,
									's_invoice_date'   => $s_invoice_date,
									's_selling_customer_currency_net' => $s_selling_customer_currency_net,
									's_vat_rate'  => $s_vat_rate,
									's_vat'       => $s_vat,
									's_sell_customer_currency_gross' => $s_sell_customer_currency_gross,
									's_customer_currency'           => $s_customer_currency,
									's_exchange_rate'           => $ss_exchange_rate,
									's_sell_local_currency_gross' => $ss_sell_local_currency_gross,
									's_sell_local_currency_net'      => $ss_sell_local_currency_net,
									's_expected_sell_local_currency_net' => (isset($ss_expected_sell_local_currency_net) ? $ss_expected_sell_local_currency_net : '0.00'),
									's_variation_expected_and_actual_sellling' => (isset($ss_variation_expected_and_actual_sellling) ? $ss_variation_expected_and_actual_sellling :'0.00'),
									's_variation_expect_and_actual_profit' => $ss_variation_expect_and_actual_profit,
									//'s_generate_invoice_instruction' => ((isset($s_generate_invoice_instruction) && $s_generate_invoice_instruction==1)  ? 1 : 0 ),
									'gl_account'	=> $gl_account,
									'ar_gl_account' => $s_ar_gl_account,
								  );
		}
		
		$extra_selling_normal = array(
										//'user_id' => $this->current_user->id,
										'job_id'  		   => $job_id,
										'date_added'	   => date('Y-m-d H:i:s'),
										's_job_charges_id' => $s_job_charges_id,
										's_department_id'  => $s_department_id,
										's_office_id' => $s_office_id,
										's_customer_id' => $s_bill_to_id,
										's_bill_to_id' => $s_bill_to_id,
										's_bill_to_address' => $s_bill_to_address,
										's_ship_to'  => $s_ship_to,
										's_ship_to_address' => $s_ship_to_address,
										's_remarks'   => $s_remarks,
										's_invoice_date' => $s_invoice_date,
										's_selling_customer_currency_net' => $s_selling_customer_currency_net,
										's_vat_rate'  => $s_vat_rate,
										's_vat'       => $s_vat,
										's_sell_customer_currency_gross' => $s_sell_customer_currency_gross,
										's_customer_currency'            => $s_customer_currency,
										's_exchange_rate'           	 => $s_exchange_rate,
										's_sell_local_currency_gross' 	 => $s_sell_local_currency_gross,
										's_sell_local_currency_net'      => $s_sell_local_currency_net,
										's_expected_sell_local_currency_net' => (isset($s_expected_sell_local_currency_net) ? $s_expected_sell_local_currency_net : '0.00'),
										's_variation_expected_and_actual_sellling' => (isset($s_variation_expected_and_actual_sellling) ? $s_variation_expected_and_actual_sellling :'0.00'),
										's_variation_expect_and_actual_profit' => $s_variation_expect_and_actual_profit,
										//'s_generate_invoice_instruction' => ((isset($s_generate_invoice_instruction) && $s_generate_invoice_instruction==1)  ? 1 : 0 ),
										'gl_account'	=> $gl_account,
										'ar_gl_account' => $s_ar_gl_account,
									  );
		
			
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$extra_selling['company_id'] = $file_title_id;
			//$extra_selling['s_jrer_buying_id'] = 0;
						
			$request->set('cf_1455', $extra_selling['s_job_charges_id']);
			$request->set('cf_1477', $extra_selling['s_office_id']);
			$request->set('cf_1479', $extra_selling['s_department_id']);
			$request->set('cf_1445', $extra_selling['s_bill_to_id']);
			$request->set('cf_1359', $extra_selling['s_bill_to_address']);
			$request->set('cf_1361', $extra_selling['s_ship_to']);
			$request->set('cf_1363', $extra_selling['s_ship_to_address']);
			$request->set('cf_1365', $extra_selling['s_remarks']);
			$request->set('cf_1355', $extra_selling['s_invoice_date']);			
			$request->set('cf_1357', $extra_selling['s_selling_customer_currency_net']);
			$request->set('cf_1228', $extra_selling['s_vat_rate']);
			$request->set('cf_1230', $extra_selling['s_vat']);
			$request->set('cf_1232', $extra_selling['s_sell_customer_currency_gross']);
			$request->set('cf_1234', $extra_selling['s_customer_currency']);
			$request->set('cf_1236', $extra_selling['s_exchange_rate']);
			$request->set('cf_1238', $extra_selling['s_sell_local_currency_gross']);
			$request->set('cf_1240', $extra_selling['s_sell_local_currency_net']);
			$request->set('cf_1242', $extra_selling['s_expected_sell_local_currency_net']);
			$request->set('cf_1244', $extra_selling['s_variation_expected_and_actual_sellling']);
			$request->set('cf_1246', $extra_selling['s_variation_expect_and_actual_profit']);
			
			$request->set('gl_account', $extra_selling['gl_account']);
			$request->set('ar_gl_account', $extra_selling['ar_gl_account']);
			//$request->set('company_id', $extra_selling['company_id']);
			//$request->set('user_id', $extra_selling['user_id']);
			//$request->set('owner_id', $extra_selling['owner_id']);
			//$request->set('s_jrer_buying_id', $extra_selling['s_jrer_buying_id']);
			$recordModel = $this->saveRecord($request);
			$jrer_selling_id_assigned = $recordModel->getId();
			
		}
		else{
			$extra_selling_normal['company_id'] = $file_title_id;	
			$extra_selling_normal['user_id'] = $current_user->getId();
			$extra_selling_normal['owner_id'] = $current_user->getId();
			
			$request->set('cf_1455', $extra_selling_normal['s_job_charges_id']);
			$request->set('cf_1477', $extra_selling_normal['s_office_id']);
			$request->set('cf_1479', $extra_selling_normal['s_department_id']);
			$request->set('cf_1445', $extra_selling_normal['s_bill_to_id']);
			$request->set('cf_1359', $extra_selling_normal['s_bill_to_address']);
			$request->set('cf_1361', $extra_selling_normal['s_ship_to']);
			$request->set('cf_1363', $extra_selling_normal['s_ship_to_address']);
			$request->set('cf_1365', $extra_selling_normal['s_remarks']);
			$request->set('cf_1355', $extra_selling_normal['s_invoice_date']);			
			$request->set('cf_1357', $extra_selling_normal['s_selling_customer_currency_net']);
			$request->set('cf_1228', $extra_selling_normal['s_vat_rate']);
			$request->set('cf_1230', $extra_selling_normal['s_vat']);
			$request->set('cf_1232', $extra_selling_normal['s_sell_customer_currency_gross']);
			$request->set('cf_1234', $extra_selling_normal['s_customer_currency']);
			$request->set('cf_1236', $extra_selling_normal['s_exchange_rate']);
			$request->set('cf_1238', $extra_selling_normal['s_sell_local_currency_gross']);
			$request->set('cf_1240', $extra_selling_normal['s_sell_local_currency_net']);
			$request->set('cf_1242', $extra_selling_normal['s_expected_sell_local_currency_net']);
			$request->set('cf_1244', $extra_selling_normal['s_variation_expected_and_actual_sellling']);
			$request->set('cf_1246', $extra_selling_normal['s_variation_expect_and_actual_profit']);
			
			$request->set('gl_account', $extra_selling_normal['gl_account']);
			$request->set('ar_gl_account', $extra_selling_normal['ar_gl_account']);
			$request->set('company_id', $extra_selling_normal['company_id']);
			$request->set('user_id', $extra_selling_normal['user_id']);
			$request->set('owner_id', $extra_selling_normal['owner_id']);
			///$request->set('s_jrer_buying_id', $extra_selling_normal['s_jrer_buying_id']);
				
			$recordModel = $this->saveRecord($request);
			$jobexpencereportid = $recordModel->getId();
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, gl_account=?, ar_gl_account=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_selling_normal['gl_account'], $extra_selling_normal['ar_gl_account'], $extra_selling_normal['company_id'], $extra_selling_normal['user_id'], $extra_selling_normal['owner_id'], $jobexpencereportid);
			$result_up = $db_up->pquery($query_up, $params_up);	
		}	
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$assigned_extra_selling[] = $extra_selling_normal;
		}
		
		if(!empty($assigned_extra_selling))
			{
				foreach($assigned_extra_selling as $selling)
				{
					$db_customer = PearDatabase::getInstance();				
					$query_pay_to_info = 'SELECT * FROM vtiger_accountscf where cf_1703=?';
					$params_pay_to_info = array($file_title_id);
					$result_pay_to_info = $db_customer->pquery($query_pay_to_info,$params_pay_to_info);
					$pay_to_info = $db_customer->fetch_array($result_pay_to_info);	
					
					if($job_info->get('assigned_user_id')!=$current_user->getId())
					{	
						//$b_type_id = $post->file_title.'-bank';
						$db_company_type = PearDatabase::getInstance();
						$query_company_type = 'select * from vtiger_companyaccounttype 
												INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccounttype.companyaccounttypeid
												where vtiger_crmentityrel.crmid=? and vtiger_companyaccounttype.name like "Bank%"';
						$params_company_type = array($job_info->get('cf_1186'));
						$result_company_type = $db_company_type->pquery($query_company_type, $params_company_type);
						$company_type_info = $db_company_type->fetch_array($result_company_type);
						$b_type_id = $company_type_info['companyaccounttypeid'];
						// need to discuss
					}	
					
					$db_assigned_jrer_buying_count = PearDatabase::getInstance();
					$jrer_assigned_jrer_buying_count = 'SELECT * FROM vtiger_jobexpencereport
										   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
										   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
										   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
										   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereport.selling_expence="yes" 
										   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
					$params_assigned_jrer_buying_count = array($selling['job_id'], 
										  $selling['s_job_charges_id'], 
										  $selling['s_office_id'], 
										  $selling['s_department_id'],								      
										  );
					$result_assigned_jrer_buying_count = $db_assigned_jrer_buying_count->pquery($jrer_assigned_jrer_buying_count,$params_assigned_jrer_buying_count);
					
					$assigned_extra_buying_cost = array(
														'date_added'	   => date('Y-m-d H:i:s'),
														'user_id'		  => $current_user->getId(),
														'job_id'  		   => $selling['job_id'],
														'date_added'	   => date('Y-m-d H:i:s'),
														'b_job_charges_id' => $selling['s_job_charges_id'],
														'b_office_id' 	  => $selling['s_office_id'],
														'b_department_id'  => $selling['s_department_id'],															
														//'b_pay_to_id' 	  => $pay_to_info->id,
														'b_pay_to_id' 	  => $pay_to_info['id'],														
														'b_invoice_date'   => $selling['s_invoice_date'],
														'b_invoice_due_date'=> date('Y-m-d'),
														'company_id' 	   => $job_info->get('cf_1186'),
														'owner_id' 		 => $job_info->get('assigned_user_id'),													
														'b_buy_vendor_currency_net' => $selling['s_selling_customer_currency_net'],
														'b_vat_rate'       => $selling['s_vat_rate'],
														'b_vat'            => $selling['s_vat'],
														'b_buy_vendor_currency_gross' => $selling['s_sell_customer_currency_gross'],
														'b_vendor_currency'    => $selling['s_customer_currency'],
														'b_exchange_rate'      => $selling['s_exchange_rate'],
														'b_buy_local_currency_gross' => $selling['s_sell_local_currency_gross'],
														'b_buy_local_currency_net'   => $selling['s_sell_local_currency_net'],
														'b_expected_buy_local_currency_net' => $selling['s_expected_sell_local_currency_net'],
														'b_variation_expected_and_actual_buying' => $selling['s_variation_expected_and_actual_sellling'],														
														'b_send_to_head_of_department_for_approval' => 0,
														'b_send_to_payables_and_generate_payment_voucher' => 0,
														'selling_expense' => 'yes'
														);
						
						if($db_assigned_jrer_buying_count->num_rows($result_assigned_jrer_buying_count)==0)
						{
							$assigned_extra_buying_cost['b_type_id'] 	   = $b_type_id;
							$assigned_extra_buying_cost['b_gl_account']	= $selling['gl_account'];
							$assigned_extra_buying_cost['b_ar_gl_account'] = $selling['ar_gl_account'];
							//$this->db->insert('job_jrer_buying',$assigned_extra_buying_cost);
							
							$request->set('cf_1453', $assigned_extra_buying_cost['b_job_charges_id']);
							$request->set('cf_1477', $assigned_extra_buying_cost['b_office_id']);
							$request->set('cf_1479', $assigned_extra_buying_cost['b_department_id']);
							$request->set('cf_1367', $assigned_extra_buying_cost['b_pay_to_id']);
							//$request->set('cf_1212', $assigned_extra_buying_cost['b_invoice_number']);
							$request->set('cf_1216', $assigned_extra_buying_cost['b_invoice_date']);
							$request->set('cf_1210', $assigned_extra_buying_cost['b_invoice_due_date']);
							$request->set('cf_1214', $assigned_extra_buying_cost['b_type_id']);
							$request->set('cf_1337', $assigned_extra_buying_cost['b_buy_vendor_currency_net']);			
							$request->set('cf_1339', $assigned_extra_buying_cost['b_vat_rate']);
							$request->set('cf_1341', $assigned_extra_buying_cost['b_vat']);
							$request->set('cf_1343', $assigned_extra_buying_cost['b_buy_vendor_currency_gross']);
							$request->set('cf_1345', $assigned_extra_buying_cost['b_vendor_currency']);
							$request->set('cf_1222', $assigned_extra_buying_cost['b_exchange_rate']);
							$request->set('cf_1347', $assigned_extra_buying_cost['b_buy_local_currency_gross']);
							$request->set('cf_1349', $assigned_extra_buying_cost['b_buy_local_currency_net']);
							$request->set('cf_1351', $assigned_extra_buying_cost['b_expected_buy_local_currency_net']);
							$request->set('cf_1353', $assigned_extra_buying_cost['b_variation_expected_and_actual_buying']);			
							$request->set('cf_1457', 'Expence');
							
							$request->set('record','');
							$recordModel->set('mode', '');
						
							$recordModel = $this->saveRecord($request);
							$jobexpencereportid = $recordModel->getId();
							
							$db_up = PearDatabase::getInstance();
							$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, b_gl_account=?, b_ar_gl_account=?, company_id=?, user_id=?, owner_id=?, selling_expence=?,  where jobexpencereportid=?';
							$params_up = array($assigned_extra_buying_cost['job_id'], $assigned_extra_buying_cost['b_gl_account'], $assigned_extra_buying_cost['b_ar_gl_account'], $assigned_extra_buying_cost['company_id'], $assigned_extra_buying_cost['user_id'], $assigned_extra_buying_cost['owner_id'], $assigned_extra_buying_cost['selling_expense'], $jobexpencereportid);
							$result_up = $db_up->pquery($query_up, $params_up);		
						}
						else{
						$assigned_jrer_buying_info = $db_assigned_jrer_buying_count->fetch_array($result_assigned_jrer_buying_count);
						$request->set('id',$assigned_jrer_buying_info['jobexpencereportid']);
						$request->set('record',$assigned_jrer_buying_info['jobexpencereportid']);
						$recordModel->set('mode', 'edit');						
						
						$request->set('cf_1453', $assigned_extra_buying_cost['b_job_charges_id']);
						$request->set('cf_1477', $assigned_extra_buying_cost['b_office_id']);
						$request->set('cf_1479', $assigned_extra_buying_cost['b_department_id']);
						$request->set('cf_1367', $assigned_extra_buying_cost['b_pay_to_id']);
						//$request->set('cf_1212', $assigned_extra_buying_cost['b_invoice_number']);
						$request->set('cf_1216', $assigned_extra_buying_cost['b_invoice_date']);
						$request->set('cf_1210', $assigned_extra_buying_cost['b_invoice_due_date']);
						$request->set('cf_1214', $assigned_extra_buying_cost['b_type_id']);
						$request->set('cf_1337', $assigned_extra_buying_cost['b_buy_vendor_currency_net']);			
						$request->set('cf_1339', $assigned_extra_buying_cost['b_vat_rate']);
						$request->set('cf_1341', $assigned_extra_buying_cost['b_vat']);
						$request->set('cf_1343', $assigned_extra_buying_cost['b_buy_vendor_currency_gross']);
						$request->set('cf_1345', $assigned_extra_buying_cost['b_vendor_currency']);
						$request->set('cf_1222', $assigned_extra_buying_cost['b_exchange_rate']);
						$request->set('cf_1347', $assigned_extra_buying_cost['b_buy_local_currency_gross']);
						$request->set('cf_1349', $assigned_extra_buying_cost['b_buy_local_currency_net']);
						$request->set('cf_1351', $assigned_extra_buying_cost['b_expected_buy_local_currency_net']);
						$request->set('cf_1353', $assigned_extra_buying_cost['b_variation_expected_and_actual_buying']);			
						$request->set('cf_1457', 'Expence');
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						}
					
					
				}
			}
		
		
	}
	
	public function buying_edit($request)
	{
		$recordId = $request->get('record');
		
		$job_id = $this->get_job_id($recordId);
		
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");
		
		$extra_expense = array();			
		$assigned_extra_expense = array();		
		$assigned_extra_selling = array();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$job_jrer_buying = array();
		if($recordId!=0)
		{
		  	$db_buying = PearDatabase::getInstance();						
			/*
			updation issue in expense						
			$jrer_buying = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
									   
			//jobid as record_id						   
			$params_buying = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			*/
			
			$jrer_buying = 'SELECT *, vtiger_jobexpencereport.jobexpencereportid as jrer_buying_id  FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jrer_buying_id=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
									   
			//jobid as record_id						   
			$params_buying = array(@$job_info->get('record_id'), $job_info->get('assigned_user_id'), $recordId);
			
			$result_buying = $db_buying->pquery($jrer_buying,$params_buying);
			$job_jrer_buying = $db_buying->fetch_array($result_buying);	
			
			
			//First Time expense editing SUBJCR user
			if($db_buying->num_rows($result_buying)==0)
			{
				$db_buying_2 = PearDatabase::getInstance();		
				$jrer_buying_2 = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
				$params_buying_2 = array(@$job_info->get('record_id'), $recordId);
				$result_buying_2 = $db_buying_2->pquery($jrer_buying_2,$params_buying_2);
				$job_jrer_buying = $db_buying_2->fetch_array($result_buying_2);				
			}									
		}
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
		$b_office_id	  = $current_user->get('location_id');
		//$b_department_id  = $current_user->get('department_id');
		}else{
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		}
		
		$b_pay_to_id	  = $request->get('cf_1367');
		$b_invoice_number = $request->get('cf_1212');
		$b_invoice_date   = $request->get('cf_1216');
		$b_invoice_due_date = $request->get('cf_1210');
		$b_type_id	  	  = $request->get('cf_1214');
		$b_buy_vendor_currency_net	= $request->get('cf_1337');
		if(empty($b_buy_vendor_currency_net)) 
		{ 
			$b_buy_vendor_currency_net = 0;
		}
		$b_vat_rate	  	  = $request->get('cf_1339');
		$vat_included 		= $request->get('cf_3293');
		
		$b_vat = '0.00';
		$b_buying_customer_currency_gross = '0.00';
		if(!empty($b_vat_rate) && $b_vat_rate>0)
		{
			if($vat_included=='Yes')
			{
				$b_buying_customer_currency_gross = $request->get('cf_1343');
				$b_vat_rate_ = $b_vat_rate + 100;
				$b_vat_rate_cal = $b_vat_rate_/100; 
				
				$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
				$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
			}
			else
			{
			$b_vat_rate_cal  = $b_vat_rate/100; 
			$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
			}
		}
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		$b_vendor_currency	  = $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		/*
		$invoice_date_arr = explode('-',$b_invoice_date);
		$b_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date_final));
		*/
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date));
		
		//$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date));		
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);		
		if($file_title_currency =='KZT')
		{			
			$b_exchange_rate  = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		else{			
			$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		
		
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_gross = $b_buy_vendor_currency_gross * $b_exchange_rate;
		}else{
			$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
		}
		
				
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_net = $b_buy_vendor_currency_net * $b_exchange_rate;
		}else{
			$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_net, $b_invoice_date_format);
		}
		
		$b_expected_buy_local_currency_net	  = $request->get('cf_1351');
		
		if(!empty($job_jrer_buying['jrer_buying_id']))
		{
			 $sql_jrer_pre = mysql_query("select * from vtiger_jobexpencereport where jobexpencereportid='".$job_jrer_buying['jrer_buying_id']."'");
		 	 $row_jrer_pre = mysql_fetch_array($sql_jrer_pre);
		  	 $job_costing_id = @$row_jrer_pre['jerid'];
		  
			//$job_costing_id = $job_jrer_buying['jerid'];
			
			if(!empty($job_costing_id))
			{													   
			$db_costing_1 = PearDatabase::getInstance();		
			$query_costing = 'SELECT * FROM vtiger_jer
								   INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
								   where vtiger_crmentityrel.crmid=? and vtiger_jer.jerid=?';
			$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id);
			
			$result_costing_1 = $db_costing_1->pquery($query_costing, $params_costing_1);
			$cost_of_local_currency = $db_costing_1->fetch_array($result_costing_1);								   	
			//cost_local_currency
			$b_expected_buy_local_currency_net = @$cost_of_local_currency['cf_1160'];
			}
			
			
		}
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		/*
		if($post->office_id==$this->current_user->office_id){
			$file_title_id = $post->file_title;
		}
		else{
			$file_title_id = $this->input->post('file_title');
		}*/
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		if($job_office_id==$current_user_office_id){
			$file_title_id = $job_info->get('cf_1186');
		}
		else{
			//$file_title_id = $current_user->get('company_id');
			$file_title_id = $request->get('cf_2191');
			
		}
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{	
			if($job_office_id==$current_user_office_id){
				$file_title_id = $job_info->get('cf_1186');
			}
			else{
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				//$this->db->where('job_id', $post->id)->where('user_id', $this->current_user->id)->update('job_user_assigned', array('sub_jrer_file_title' => $file_title_id));
			}
			
			
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));		
			
			if($b_type_id=='85793') //For JZ company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85772';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			elseif($b_type_id=='85773') // For DLC company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85756';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{
				$chartofaccount_name = 'Revenue';
				$link_company_id = $file_title_id;
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
				
			}		
			//Revenue				
			//$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
			$b_gl_account = @$coa_info.'-'.$office.'-'.$department;
			
			if($b_type_id=='85793')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85772';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);	
				
			}
			elseif($b_type_id=='85773')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85756';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = $file_title_id;
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			//Trade Creditors					
			//$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;
			$b_ap_gl_account = @$ap_coa_info.'-'.$office.'-'.$department;
			
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);		
				
			$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
			if($assigned_file_title_currency =='KZT')
			{	
				$bb_exchange_rate = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
			}
			elseif($assigned_file_title_currency =='USD')
			{
				$bb_exchange_rate = currency_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
			}
			else{			
				$bb_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
			}
			
			if($assigned_file_title_currency !='USD')
			{						
				$bb_buy_local_currency_gross = $b_buy_vendor_currency_gross * $bb_exchange_rate;
			}else{
				$bb_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
			}
			
			if($assigned_file_title_currency !='USD')
			{						
				$bb_buy_local_currency_net = $b_buy_vendor_currency_net * $bb_exchange_rate;
			}else{
				$bb_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency,$b_buy_vendor_currency_net, $b_invoice_date_format);
			}
			$bb_expected_buy_local_currency_net	  = $request->get('cf_1351');	
			
			$bb_variation_expected_and_actual_buying = $bb_expected_buy_local_currency_net - $bb_buy_local_currency_net;	
			
			$assigned_extra_buying = array(
											'job_id'  			=> $job_id,
											'date_added'		=> date('Y-m-d H:i:s'),
											'b_job_charges_id' 	=> $b_job_charges_id,
											'b_office_id' 		=> $b_office_id,
											'b_department_id' 	=> $b_department_id,
											'b_pay_to_id' 		=> $b_pay_to_id,
											'b_invoice_number' 	=> $b_invoice_number,
											'b_invoice_date' 	=> $b_invoice_date,
											'b_invoice_due_date'=> $b_invoice_due_date,
											'b_type_id'			=> $b_type_id,			
											'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
											'b_vat_rate'        => $b_vat_rate,
											'b_vat'             => $b_vat,
											'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
											'b_vendor_currency' => $b_vendor_currency,
											'b_exchange_rate'   => $bb_exchange_rate,
											'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
											'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
											'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
											'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
											'b_gl_account'		=> $b_gl_account,
											'b_ar_gl_account'	=> $b_ap_gl_account,												
										 );							 	
		}
		
		/*
		if($this->current_user->id!=$post->author_id)
		{	
			$b_type_id_arr = explode('-',$b_type_id);
			if($b_type_id_arr[0]==1)
			{
				$b_type_id = $b_type_id;
			}
			elseif($b_type_id_arr[0]!=1 && $post->file_title!=1)
			{
				$b_type_id = $post->file_title.'-'.$b_type_id_arr[1];
			}
			//elseif($b_type_id_arr[0]==1 && $post->file_title!=1)
			//{
			//	$b_type_id = $b_type_id_arr[0].'-'.@$b_type_id_arr[1];
			//}
			else{
				//Its JZ file because we need file title for bank
				$user_file_title_ = $this->user_companies_m->get_user_file_title_byuserid($post->author_id);
				$b_type_id = @$user_file_title_[0]->company_id.'-'.@$b_type_id_arr[1];						
			}
		}*/
		$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
		$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);	
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);					
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}		
		//For revenue
		//$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
		$b_gl_account = @$coa_info.'-'.$office.'-'.$department;
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);			
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		//Trade Creditors					
		//$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;	
		$b_ap_gl_account = @$ap_coa_info.'-'.$office.'-'.$department;	
		
		$extra_buying = array(
								//'user_id' => $this->current_user->id,
								'job_id'  => $job_id,
								'date_added'	=> date('Y-m-d H:i:s'),
								'b_job_charges_id' => $b_job_charges_id,
								'b_office_id' => $b_office_id,
								'b_department_id' => $b_department_id,
								'b_pay_to_id' => $b_pay_to_id,
								'b_invoice_number' => $b_invoice_number,
								'b_invoice_date' => $b_invoice_date,
								'b_invoice_due_date' => $b_invoice_due_date,
								'b_type_id'			=> $b_type_id,
								'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
								'b_vat_rate'        => $b_vat_rate,
								'b_vat'             => $b_vat,
								'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
								'b_vendor_currency'    => $b_vendor_currency,
								'b_exchange_rate'      => $b_exchange_rate,
								'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
								'b_buy_local_currency_net'   => $b_buy_local_currency_net,
								'b_expected_buy_local_currency_net' => (isset($b_expected_buy_local_currency_net) ? $b_expected_buy_local_currency_net :'0.00'),
								'b_variation_expected_and_actual_buying' => (isset($b_variation_expected_and_actual_buying) ? $b_variation_expected_and_actual_buying: '0.00'),
								'b_gl_account'		=> $b_gl_account,
								'b_ar_gl_account'	=> $b_ap_gl_account,
								);
			
										
			if($job_info->get('assigned_user_id')!=$current_user->getId())
				{
				
				$extra_buying['company_id'] = $job_info->get('cf_1186');
				$extra_buying['owner_id'] = $job_info->get('assigned_user_id');
				
				
				$request->set('cf_1453', $extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $extra_buying['b_office_id']);
				$request->set('cf_1479', $extra_buying['b_department_id']);
				$request->set('cf_1367', $extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $extra_buying['b_invoice_number']);
				$request->set('cf_1216', $extra_buying['b_invoice_date']);
				$request->set('cf_1210', $extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $extra_buying['b_type_id']);
				$request->set('cf_1337', $extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $extra_buying['b_vat_rate']);
				$request->set('cf_1341', $extra_buying['b_vat']);
				$request->set('cf_1343', $extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
				$request->set('company_id', $extra_buying['company_id']);
				//$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				//updation of expense for job owner of file
				$request->set('record',$job_jrer_buying['jrer_buying_id']);			
				$recordModel = $this->saveRecord($request);
				
				$jrer_buying_id_jobexpencereportid = $job_jrer_buying['jrer_buying_id'];
				
				$db_expence_up_1 = PearDatabase::getInstance();
				$query_expence_up_1 = 'UPDATE vtiger_jobexpencereport set company_id=?,  owner_id=?, b_gl_account=?, b_ar_gl_account=?  where jobexpencereportid=?';
				$params_expence_up_1 = array($extra_buying['company_id'], $extra_buying['owner_id'], $extra_buying['b_gl_account'], $extra_buying['b_ar_gl_account'], $jrer_buying_id_jobexpencereportid);
				$result_expence_up_1 = $db_expence_up_1->pquery($query_expence_up_1, $params_expence_up_1);
				
					
				$assigned_extra_buying['company_id'] = @$file_title_id;
				$assigned_extra_buying['user_id'] 	 = $current_user->getId();
				$assigned_extra_buying['owner_id']   = $current_user->getId();
				//$assigned_extra_buying['jrer_buying_id'] = 0;
				
				$request->set('cf_1453', $assigned_extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $assigned_extra_buying['b_office_id']);
				$request->set('cf_1479', $assigned_extra_buying['b_department_id']);
				$request->set('cf_1367', $assigned_extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $assigned_extra_buying['b_invoice_number']);
				$request->set('cf_1216', $assigned_extra_buying['b_invoice_date']);
				$request->set('cf_1210', $assigned_extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $assigned_extra_buying['b_type_id']);
				$request->set('cf_1337', $assigned_extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $assigned_extra_buying['b_vat_rate']);
				$request->set('cf_1341', $assigned_extra_buying['b_vat']);
				$request->set('cf_1343', $assigned_extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $assigned_extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $assigned_extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $assigned_extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $assigned_extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $assigned_extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $assigned_extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $assigned_extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $assigned_extra_buying['b_ar_gl_account']);
				$request->set('company_id', $assigned_extra_buying['company_id']);
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
				
				$request->set('record', $recordId);			
				
				$recordModel = $this->saveRecord($request);
				//$jrer_buying_id_assigned = $recordModel->getId();
				
				$jrer_buying_id_assigned = $recordId;
			
				$db_expence_up = PearDatabase::getInstance();
				$query_expence_up = 'UPDATE vtiger_jobexpencereport set  company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?  where jobexpencereportid=?';
				$params_expence_up = array($assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $jrer_buying_id_assigned);
				$result_expence_up = $db_expence_up->pquery($query_expence_up, $params_expence_up);
			}
			else{
				
				$extra_buying['company_id'] = $job_info->get('cf_1186');
				$extra_buying['owner_id'] = $job_info->get('assigned_user_id');
				
				$request->set('cf_1453', $extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $extra_buying['b_office_id']);
				$request->set('cf_1479', $extra_buying['b_department_id']);
				$request->set('cf_1367', $extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $extra_buying['b_invoice_number']);
				$request->set('cf_1216', $extra_buying['b_invoice_date']);
				$request->set('cf_1210', $extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $extra_buying['b_type_id']);
				$request->set('cf_1337', $extra_buying['b_buy_vendor_currency_net']);				
				$request->set('cf_1339', $extra_buying['b_vat_rate']);
				$request->set('cf_1341', $extra_buying['b_vat']);
				$request->set('cf_1343', $extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
				$request->set('company_id', $extra_buying['company_id']);
				//$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				$recordModel = $this->saveRecord($request);	
				
				$jrer_buying_id_jobexpencereportid = $recordId;
				
				$db_expence_up_1 = PearDatabase::getInstance();
				$query_expence_up_1 = 'UPDATE vtiger_jobexpencereport set company_id=?,  owner_id=?, b_gl_account=?, b_ar_gl_account=? where jobexpencereportid=?';
				$params_expence_up_1 = array($extra_buying['company_id'], $extra_buying['owner_id'], $extra_buying['b_gl_account'], $extra_buying['b_ar_gl_account'], $jrer_buying_id_jobexpencereportid);
				$result_expence_up_1 = $db_expence_up_1->pquery($query_expence_up_1, $params_expence_up_1);		
			}
					
			
			$extra_expense[] = $extra_buying;	
			if($job_info->get('assigned_user_id')!=$current_user->getId())
			{	
				$assigned_extra_expense[] = $assigned_extra_buying;
			}
			
			
			if(!empty($extra_expense))
			{
				foreach($extra_expense as $expense)
				{
					$db_selling_count = PearDatabase::getInstance();
					$jrer_selling_count = 'SELECT * FROM vtiger_jobexpencereport
										   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
										   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
										   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
										   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereportcf.cf_1445=? 
										   and vtiger_jobexpencereportcf.cf_1457="Selling" ';
					$params_selling_count = array($expense['job_id'], 
										  $expense['b_job_charges_id'], 
										  $expense['b_office_id'], 
										  $expense['b_department_id'],
										  $job_info->get('cf_1441')
										  );
										  
					$result_selling_count = $db_selling_count->pquery($jrer_selling_count,$params_selling_count);
					
					if($db_selling_count->num_rows($result_selling_count)==0)
					{
						 $extra_selling_cost = array(													
													'user_id'		   => $current_user->getId(),
													'job_id'  		   => $expense['job_id'],
													'date_added'	   => date('Y-m-d H:i:s'),
													's_job_charges_id' => $expense['b_job_charges_id'],
													's_office_id' 	   => $expense['b_office_id'],
													's_department_id'  => $expense['b_department_id'],
													's_bill_to_id' 	   => $job_info->get('cf_1441'),
													's_invoice_date'   => $expense['b_invoice_date'],
													's_customer_currency' => $expense['b_vendor_currency'],
													'company_id' 	   => @$expense['company_id'],
													'owner_id' 		   => @$expense['owner_id'],												
													's_variation_expect_and_actual_profit' => @$expense['b_variation_expected_and_actual_buying'],
													);
						
						$request->set('cf_1453', '');
						$request->set('cf_1367', '');
						$request->set('cf_1212', '');
						$request->set('cf_1216', '');
						$request->set('cf_1210', '');
						$request->set('cf_1214', '');
						$request->set('cf_1337', '');
						$request->set('cf_1339', '');
						$request->set('cf_1341', '');
						$request->set('cf_1343', '');
						$request->set('cf_1345', '');
						$request->set('cf_1222', '');
						$request->set('cf_1347', '');
						$request->set('cf_1349', '');
						$request->set('cf_1351', '');
						$request->set('cf_1353', '');							
													
						$request->set('cf_1455', $extra_selling_cost['s_job_charges_id']);
						$request->set('cf_1477', $extra_selling_cost['s_office_id']);
						$request->set('cf_1479', $extra_selling_cost['s_department_id']);
						$request->set('cf_1445', $extra_selling_cost['s_bill_to_id']);						
						$request->set('cf_1355', $extra_selling_cost['s_invoice_date']);							
						$request->set('cf_1234', $extra_selling_cost['s_customer_currency']);						
						$request->set('cf_1246', $extra_selling_cost['s_variation_expect_and_actual_profit']);
						$request->set('cf_1457', 'Selling');
						
						$request->set('record','');
						$request->set('id','');
						$recordModel->set('mode', '');
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						
						$db_sell_owner = PearDatabase::getInstance(); 
						$query_sell_owner = 'UPDATE vtiger_crmentity set smcreatorid=?, smownerid=? where crmid=?';
						$params_sell_owner = array($job_info->get('assigned_user_id'), $job_info->get('assigned_user_id'), $jobexpencereportid);
						$result_sell_owner = $db_sell_owner->pquery($query_sell_owner, $params_sell_owner);
						
						$db_sell_up = PearDatabase::getInstance();
						$query_sell_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
						$params_sell_up = array($extra_selling_cost['job_id'], $extra_selling_cost['company_id'], $extra_selling_cost['user_id'], $extra_selling_cost['owner_id'], $jobexpencereportid);
						$result_sell_up = $db_sell_up->pquery($query_sell_up, $params_sell_up);
					}
				}
			}
				
			if(!empty($assigned_extra_expense))
			{
				foreach($assigned_extra_expense as $expense)
				{
					$db_customer = PearDatabase::getInstance();				
					$query_bill_to_info = 'SELECT * FROM vtiger_accountscf where cf_1703=?';
					$params_bill_to_info = array($job_info->get('cf_1186'));
					$result_bill_to_info = $db_customer->pquery($query_bill_to_info, $params_bill_to_info);
					$bill_to_info = $db_customer->fetch_array($result_bill_to_info);
					
					$db_user_selling_count = PearDatabase::getInstance();
				$jrer_selling_count = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereportcf.cf_1445=? 
									   and vtiger_jobexpencereportcf.cf_1457="Selling" and vtiger_jobexpencereport.user_id=?';
				$params_selling_count = array($expense['job_id'], 
			  					      $expense['b_job_charges_id'], 
								      $expense['b_office_id'], 
								      $expense['b_department_id'],
								      $bill_to_info['accountid'],
									  $current_user->getId()
								      );
				$result_selling_count = $db_user_selling_count->pquery($jrer_selling_count,$params_selling_count);
				
					if($db_user_selling_count->num_rows($result_selling_count)==0)
					{
						$assigned_extra_selling_cost = array(
															'date_added'	   => date('Y-m-d H:i:s'),
															'user_id'		   => $current_user->getId(),
															'job_id'  		   => $expense['job_id'],
															'date_added'	   => date('Y-m-d H:i:s'),
															's_job_charges_id' => $expense['b_job_charges_id'],
															's_office_id' 	   => $expense['b_office_id'],
															's_department_id'  => $expense['b_department_id'],															
															's_bill_to_id' 	   => $bill_to_info['accountid'],
															's_invoice_date'   => $expense['b_invoice_date'],
															's_customer_currency' => $expense['b_vendor_currency'],
															'company_id' 	   => $expense['company_id'],
															'owner_id' 		   => $expense['owner_id'],
															's_variation_expect_and_actual_profit' => $expense['b_variation_expected_and_actual_buying'],	
															);
															
						$request->set('cf_1453', '');
						$request->set('cf_1367', '');
						$request->set('cf_1212', '');
						$request->set('cf_1216', '');
						$request->set('cf_1210', '');
						$request->set('cf_1214', '');
						$request->set('cf_1337', '');
						$request->set('cf_1339', '');
						$request->set('cf_1341', '');
						$request->set('cf_1343', '');
						$request->set('cf_1345', '');
						$request->set('cf_1222', '');
						$request->set('cf_1347', '');
						$request->set('cf_1349', '');
						$request->set('cf_1351', '');
						$request->set('cf_1353', '');							
													
						$request->set('cf_1455', $assigned_extra_selling_cost['s_job_charges_id']);
						$request->set('cf_1477', $assigned_extra_selling_cost['s_office_id']);
						$request->set('cf_1479', $assigned_extra_selling_cost['s_department_id']);
						$request->set('cf_1445', $assigned_extra_selling_cost['s_bill_to_id']);						
						$request->set('cf_1355', $assigned_extra_selling_cost['s_invoice_date']);							
						$request->set('cf_1234', $assigned_extra_selling_cost['s_customer_currency']);						
						$request->set('cf_1246', $assigned_extra_selling_cost['s_variation_expect_and_actual_profit']);
						$request->set('cf_1457', 'Selling');
						
						$request->set('record','');
						$request->set('id','');
						$recordModel->set('mode', '');
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						
						$db_sell_up = PearDatabase::getInstance();
						$query_sell_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
						$params_sell_up = array($assigned_extra_selling_cost['job_id'], $assigned_extra_selling_cost['company_id'], $assigned_extra_selling_cost['user_id'], $assigned_extra_selling_cost['owner_id'], $jobexpencereportid);
						$result_sell_up = $db_sell_up->pquery($query_sell_up, $params_sell_up);
					}
					
				}
			}
			
			
				
	}
	
	public function fleet_buying_edit($request)
	{
		$recordId = $request->get('record');
		
		$job_id = $this->get_job_id_from_fleet($recordId);
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);	
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");
		
		$extra_expense = array();			
		$assigned_extra_expense = array();		
		$assigned_extra_selling = array();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		 
		$fleet_id_from_expense = $this->get_fleet_id_from_expense($recordId);
		
		$job_jrer_buying = array();
		if($recordId!=0)
		{
		  	$db_buying = PearDatabase::getInstance();						
			$jrer_buying = 'SELECT * FROM vtiger_jobexpencereport
						   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jobexpencereportid=? 
						   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
			//jobid as record_id						   
			//$params_buying = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			$params_buying = array(@$fleet_id_from_expense, $current_user->getId(), $recordId);	
			$result_buying = $db_buying->pquery($jrer_buying,$params_buying);
			$job_jrer_buying = $db_buying->fetch_array($result_buying);
			
			//First Time expense editing SUBJCR user
			if($db_buying->num_rows($result_buying)==0)
			{
				$db_buying_2 = PearDatabase::getInstance();		
				$jrer_buying_2 = 'SELECT * FROM vtiger_jobexpencereport
							      INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
							      INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
							      where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.jobexpencereportid=? 
							      and vtiger_jobexpencereportcf.cf_1457="Expence" ';
				//$params_buying_2 = array(@$job_info->get('record_id'), $recordId);
				$params_buying_2 = array(@$fleet_id_from_expense, $recordId);
				$result_buying_2 = $db_buying_2->pquery($jrer_buying_2,$params_buying_2);
				$job_jrer_buying = $db_buying_2->fetch_array($result_buying_2);				
			}
			$expense_truck_id = $job_jrer_buying['cf_2195'];
			
			$adb_sub = PearDatabase::getInstance();
			$query_count_job_jrer_buying = "SELECT * FROM vtiger_jobexpencereportcf
														 INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
														 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid														 
														 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
														 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
														 AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
														 AND vtiger_jobexpencereportcf.cf_2195=? 
														 AND vtiger_jobexpencereportcf.cf_2193=''
														 AND vtiger_jobexpencereport.fleet_trip_id=?
														 limit 1	
														 ";
			$job_id = $job_id;		 
			$params_fleet_jrer = array(@$job_info->get('record_id'), $expense_truck_id, $fleet_id_from_expense);
			$result_fleet_jrer = $adb_sub->pquery($query_count_job_jrer_buying, $params_fleet_jrer);
			$row_fleet_sub_jrer = $adb_sub->fetch_array($result_fleet_jrer);
						
			$adb_main = PearDatabase::getInstance();
			$query_count_job_jrer_buying = "SELECT * FROM vtiger_jobexpencereportcf
													 	 INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid	 
														 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
														 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
														 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
														 AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
														 AND vtiger_jobexpencereportcf.cf_2195=? AND vtiger_jobexpencereportcf.cf_2193='Yes' 
														 AND vtiger_jobexpencereport.fleet_trip_id=?
														 limit 1	
														 ";
			$job_id = $job_id;		 
			$params_fleet_jrer = array(@$job_info->get('record_id'), $expense_truck_id, $fleet_id_from_expense);
			$result_fleet_jrer = $adb_main->pquery($query_count_job_jrer_buying, $params_fleet_jrer);
			$row_fleet_main_jrer = $adb_main->fetch_array($result_fleet_jrer);		
				
			
		}
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
		$b_office_id	  = $current_user->get('location_id');
		//$b_department_id  = $current_user->get('department_id');
		}else{
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		}
		
		$b_pay_to_id	  = $request->get('cf_1367');
		$b_invoice_number = $request->get('cf_1212');
		$b_invoice_date   = $request->get('cf_1216');
		$b_invoice_due_date = $request->get('cf_1210');
		$b_type_id	  	  = $request->get('cf_1214');
		$b_buy_vendor_currency_net	= $request->get('cf_1337');
		if(empty($b_buy_vendor_currency_net)) 
		{ 
			$b_buy_vendor_currency_net = 0;
		}
		$b_vat_rate	  	  = $request->get('cf_1339');
		$vat_included 		= $request->get('cf_3293');
		
		$b_vat = '0.00';
		$b_buying_customer_currency_gross = '0.00';
		if(!empty($b_vat_rate) && $b_vat_rate>0)
		{
			if($vat_included=='Yes')
			{
				$b_buying_customer_currency_gross = $request->get('cf_1343');
				$b_vat_rate_ = $b_vat_rate + 100;
				$b_vat_rate_cal = $b_vat_rate_/100; 
				
				$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
				$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
			}
			else
			{
			$b_vat_rate_cal  = $b_vat_rate/100; 
			$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
			}
		}
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		$b_vendor_currency	  = $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		
		$invoice_date_arr = explode('-',$b_invoice_date);
		$b_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date_final));
	
		//$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date));
		
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);		
		if($file_title_currency =='KZT')
		{			
			$b_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		else{			
			$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		
		
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_gross = $b_buy_vendor_currency_gross * $b_exchange_rate;
		}else{
			$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
		}
		
				
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_net = $b_buy_vendor_currency_net * $b_exchange_rate;
		}else{
			$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_net, $b_invoice_date_format);
		}
		
		$b_expected_buy_local_currency_net	  = $request->get('cf_1351');
		
		if(!empty($job_jrer_buying['jerid']))
		{
			$job_costing_id = $job_jrer_buying['jerid'];
			
			if(!empty($job_costing_id))
			{													   
			$db_costing_1 = PearDatabase::getInstance();		
			$query_costing = 'SELECT * FROM vtiger_jer
								   INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
								   where vtiger_crmentityrel.crmid=? and vtiger_jer.jerid=?';
			$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id);
			
			$result_costing_1 = $db_costing_1->pquery($query_costing, $params_costing_1);
			$cost_of_local_currency = $db_costing_1->fetch_array($result_costing_1);								   	
			//cost_local_currency
			$b_expected_buy_local_currency_net = @$cost_of_local_currency['cf_1160'];
			}
		}
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		/*
		if($post->office_id==$this->current_user->office_id){
			$file_title_id = $post->file_title;
		}
		else{
			$file_title_id = $this->input->post('file_title');
		}*/
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		if($job_office_id==$current_user_office_id){
			$file_title_id = $job_info->get('cf_1186');
		}
		else{
			//$file_title_id = $current_user->get('company_id');
			$file_title_id = $request->get('cf_2191');			
		}
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{	
			if($job_office_id==$current_user_office_id){
				$file_title_id = $job_info->get('cf_1186');
			}
			else{
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				//$this->db->where('job_id', $post->id)->where('user_id', $this->current_user->id)->update('job_user_assigned', array('sub_jrer_file_title' => $file_title_id));
			}
			
			
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));		
			
			if($b_type_id=='85793') //For JZ company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85772';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			elseif($b_type_id=='85773') // For DLC company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85756';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{
				$chartofaccount_name = 'Revenue';
				$link_company_id = $file_title_id;
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
				
			}		
			//Revenue				
			//$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
			$b_gl_account = @$coa_info.'-'.$office.'-'.$department;
			
			if($b_type_id=='85793')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85772';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);	
				
			}
			elseif($b_type_id=='85773')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85756';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = $file_title_id;
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			//Trade Creditors					
			//$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;
			$b_ap_gl_account = @$ap_coa_info.'-'.$office.'-'.$department;
			
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);		
				
			$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
			if($assigned_file_title_currency =='KZT')
			{	
				$bb_exchange_rate = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
			}
			elseif($assigned_file_title_currency =='USD')
			{
				$bb_exchange_rate = currency_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
			}
			else{			
				$bb_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
			}
			
			if($assigned_file_title_currency !='USD')
			{						
				$bb_buy_local_currency_gross = $b_buy_vendor_currency_gross * $bb_exchange_rate;
			}else{
				$bb_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
			}
			
			if($assigned_file_title_currency !='USD')
			{						
				$bb_buy_local_currency_net = $b_buy_vendor_currency_net * $bb_exchange_rate;
			}else{
				$bb_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency,$b_buy_vendor_currency_net, $b_invoice_date_format);
			}
			$bb_expected_buy_local_currency_net	  = $request->get('cf_1351');	
			
			$bb_variation_expected_and_actual_buying = $bb_expected_buy_local_currency_net - $bb_buy_local_currency_net;	
			
			$assigned_extra_buying = array(
											'job_id'  			=> $job_id,
											'date_added'		=> date('Y-m-d H:i:s'),
											'b_job_charges_id' 	=> $b_job_charges_id,
											'b_office_id' 		=> $b_office_id,
											'b_department_id' 	=> $b_department_id,
											'b_pay_to_id' 		=> $b_pay_to_id,
											'b_invoice_number' 	=> $b_invoice_number,
											'b_invoice_date' 	=> $b_invoice_date,
											'b_invoice_due_date'=> $b_invoice_due_date,
											'b_type_id'			=> $b_type_id,			
											'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
											'b_vat_rate'        => $b_vat_rate,
											'b_vat'             => $b_vat,
											'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
											'b_vendor_currency' => $b_vendor_currency,
											'b_exchange_rate'   => $bb_exchange_rate,
											'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
											'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
											'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
											'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
											'b_gl_account'		=> $b_gl_account,
											'b_ar_gl_account'	=> $b_ap_gl_account,												
										 );
										 							 	
		}
		
		/*
		if($this->current_user->id!=$post->author_id)
		{	
			$b_type_id_arr = explode('-',$b_type_id);
			if($b_type_id_arr[0]==1)
			{
				$b_type_id = $b_type_id;
			}
			elseif($b_type_id_arr[0]!=1 && $post->file_title!=1)
			{
				$b_type_id = $post->file_title.'-'.$b_type_id_arr[1];
			}
			//elseif($b_type_id_arr[0]==1 && $post->file_title!=1)
			//{
			//	$b_type_id = $b_type_id_arr[0].'-'.@$b_type_id_arr[1];
			//}
			else{
				//Its JZ file because we need file title for bank
				$user_file_title_ = $this->user_companies_m->get_user_file_title_byuserid($post->author_id);
				$b_type_id = @$user_file_title_[0]->company_id.'-'.@$b_type_id_arr[1];						
			}
		}*/
		$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
		$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);	
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);				
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}		
		//For revenue
		//$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
		$b_gl_account = @$coa_info.'-'.$office.'-'.$department;
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);			
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		//Trade Creditors					
		//$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;	
		$b_ap_gl_account = @$ap_coa_info.'-'.$office.'-'.$department;
		
		$extra_buying = array(
								//'user_id' => $this->current_user->id,
								'job_id'  => $job_id,
								'date_added'	=> date('Y-m-d H:i:s'),
								'b_job_charges_id' => $b_job_charges_id,
								'b_office_id' => $b_office_id,
								'b_department_id' => $b_department_id,
								'b_pay_to_id' => $b_pay_to_id,
								'b_invoice_number' => $b_invoice_number,
								'b_invoice_date' => $b_invoice_date,
								'b_invoice_due_date' => $b_invoice_due_date,
								'b_type_id'			=> $b_type_id,
								'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
								'b_vat_rate'        => $b_vat_rate,
								'b_vat'             => $b_vat,
								'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
								'b_vendor_currency'    => $b_vendor_currency,
								'b_exchange_rate'      => $b_exchange_rate,
								'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
								'b_buy_local_currency_net'   => $b_buy_local_currency_net,
								'b_expected_buy_local_currency_net' => (isset($b_expected_buy_local_currency_net) ? $b_expected_buy_local_currency_net :'0.00'),
								'b_variation_expected_and_actual_buying' => (isset($b_variation_expected_and_actual_buying) ? $b_variation_expected_and_actual_buying: '0.00'),
								'b_gl_account'		=> $b_gl_account,
								'b_ar_gl_account'	=> $b_ap_gl_account,
								);
		
												
			if($job_info->get('assigned_user_id')!=$current_user->getId())
				{
				/*
				$extra_buying['company_id'] = $job_info->get('cf_1186');
				$extra_buying['owner_id'] = $job_info->get('assigned_user_id');				
				
				$request->set('cf_1453', $extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $extra_buying['b_office_id']);
				$request->set('cf_1479', $extra_buying['b_department_id']);
				$request->set('cf_1367', $extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $extra_buying['b_invoice_number']);
				$request->set('cf_1216', $extra_buying['b_invoice_date']);
				$request->set('cf_1210', $extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $extra_buying['b_type_id']);
				$request->set('cf_1337', $extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $extra_buying['b_vat_rate']);
				$request->set('cf_1341', $extra_buying['b_vat']);
				$request->set('cf_1343', $extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
				$request->set('company_id', $extra_buying['company_id']);
				//$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				//updation of expense for job owner of file
				$request->set('record',$job_jrer_buying['jrer_buying_id']);			
				$recordModel = $this->saveRecord($request);
				*/
				
				
				
				/*$db_flold = PearDatabase::getInstance();
				$query_flold = 'SELECT * FROM vtiger_jobexpencereport
							   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
							   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
							   where vtiger_jobexpencereportcf.jobexpencereportid=?
							   AND vtiger_jobexpencereportcf.cf_1457="Expence" ';
				$params_flold = array($recordId);		
				$result_flold = $db_flold->pquery($query_flold,$params_flold);
				$before_update_jrer_buying_fleet = $db_flold->fetch_array($result_flold);
				
				$db_minus_sub = PearDatabase::getInstance();
				$query_minu_update_sub = "UPDATE vtiger_jobexpencereportcf SET 
																	   cf_1337 = (cf_1337 - ".$before_update_jrer_buying_fleet['cf_1343']."), 
																	   cf_1343 = (cf_1343 - ".$before_update_jrer_buying_fleet['cf_1343']."), 
																	   cf_1347 = (cf_1347 - ".$before_update_jrer_buying_fleet['cf_1347']."),
																	   cf_1349 = (cf_1349 - ".$before_update_jrer_buying_fleet['cf_1349']."),
																	   cf_1353 = (cf_1353 - ".$before_update_jrer_buying_fleet['cf_1353'].")
																	   WHERE jobexpencereportid='".$row_fleet_sub_jrer['jobexpencereportid']."' ";
											  
				$db_minus_sub->query($query_minu_update_sub);
				
				//Update in Fleet Main JRER with new value
				$db_plus_sub = PearDatabase::getInstance();
				$query_plus_update_sub = "UPDATE vtiger_jobexpencereportcf SET 
																	   cf_1337 = (cf_1337 + ".$extra_buying['b_buy_vendor_currency_net']."), 
																	   cf_1343 = (cf_1343 + ".$extra_buying['b_buy_vendor_currency_gross']."), 
																	   cf_1347 = (cf_1347 + ".$extra_buying['b_buy_local_currency_gross']."),
																	   cf_1349 = (cf_1349 + ".$extra_buying['b_buy_local_currency_net']."),
																	   cf_1353 = (cf_1353 + ".$extra_buying['b_variation_expected_and_actual_buying'].")
																	   WHERE jobexpencereportid='".$row_fleet_sub_jrer['jobexpencereportid']."' ";
				$db_plus_sub->query($query_plus_update_sub);	
				
				$db_minus_main = PearDatabase::getInstance();
				$query_minu_update_main = "UPDATE vtiger_jobexpencereportcf SET 
																	   cf_1337 = (cf_1337 - ".$before_update_jrer_buying_fleet['cf_1343']."), 
																	   cf_1343 = (cf_1343 - ".$before_update_jrer_buying_fleet['cf_1343']."), 
																	   cf_1347 = (cf_1347 - ".$before_update_jrer_buying_fleet['cf_1347']."),
																	   cf_1349 = (cf_1349 - ".$before_update_jrer_buying_fleet['cf_1349']."),
																	   cf_1353 = (cf_1353 - ".$before_update_jrer_buying_fleet['cf_1353'].")
																	   WHERE cf_2193='Yes' AND jobexpencereportid='".$row_fleet_main_jrer['jobexpencereportid']."' ";
											  
				$db_minus_main->query($query_minu_update_main);
				
				//Update in Fleet Main JRER with new value
				$db_plus_main = PearDatabase::getInstance();
				$query_plus_update_main = "UPDATE vtiger_jobexpencereportcf SET 
																	   cf_1337 = (cf_1337 + ".$extra_buying['b_buy_vendor_currency_net']."), 
																	   cf_1343 = (cf_1343 + ".$extra_buying['b_buy_vendor_currency_gross']."), 
																	   cf_1347 = (cf_1347 + ".$extra_buying['b_buy_local_currency_gross']."),
																	   cf_1349 = (cf_1349 + ".$extra_buying['b_buy_local_currency_net']."),
																	   cf_1353 = (cf_1353 + ".$extra_buying['b_variation_expected_and_actual_buying'].")
																	   WHERE cf_2193='Yes' AND jobexpencereportid='".$row_fleet_main_jrer['jobexpencereportid']."' ";
				$db_plus_main->query($query_plus_update_main);
				*/
				
					
				$assigned_extra_buying['company_id'] = @$file_title_id;
				$assigned_extra_buying['user_id'] 	 = $current_user->getId();
				$assigned_extra_buying['owner_id']   = $current_user->getId();
				//$assigned_extra_buying['jrer_buying_id'] = 0;
				
				$request->set('cf_1453', $assigned_extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $assigned_extra_buying['b_office_id']);
				$request->set('cf_1479', $assigned_extra_buying['b_department_id']);
				$request->set('cf_1367', $assigned_extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $assigned_extra_buying['b_invoice_number']);
				$request->set('cf_1216', $assigned_extra_buying['b_invoice_date']);
				$request->set('cf_1210', $assigned_extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $assigned_extra_buying['b_type_id']);
				$request->set('cf_1337', $assigned_extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $assigned_extra_buying['b_vat_rate']);
				$request->set('cf_1341', $assigned_extra_buying['b_vat']);
				$request->set('cf_1343', $assigned_extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $assigned_extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $assigned_extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $assigned_extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $assigned_extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $assigned_extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $assigned_extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $assigned_extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $assigned_extra_buying['b_ar_gl_account']);
				$request->set('company_id', $assigned_extra_buying['company_id']);
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
				
				$request->set('record', $recordId);			
				
				$recordModel = $this->saveRecord($request);
				//$jrer_buying_id_assigned = $recordModel->getId();
				
				//Updation in Fleet Main JRER
				
				$fleet_id = $fleet_id_from_expense;
				$db_flold = PearDatabase::getInstance();
				$query_fleet = 'SELECT sum(vtiger_jobexpencereportcf.cf_1337) as b_buy_vendor_currency_net, 
									   sum(vtiger_jobexpencereportcf.cf_1339) as b_vat_rate, 
									   sum(vtiger_jobexpencereportcf.cf_1341) as b_vat, 
									   sum(vtiger_jobexpencereportcf.cf_1343) as b_buy_vendor_currency_gross, 
									   sum(vtiger_jobexpencereportcf.cf_1347) as b_buy_local_currency_gross, 
									   sum(vtiger_jobexpencereportcf.cf_1349) as b_buy_local_currency_net, 
									   sum(vtiger_jobexpencereportcf.cf_1351) as b_expected_buy_local_currency_net, 
									   sum(vtiger_jobexpencereportcf.cf_1353) as b_variation_expected_and_actual_buying 
								FROM vtiger_jobexpencereport 
								INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
								INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid 
								WHERE vtiger_crmentityrel.crmid=? 
								AND vtiger_jobexpencereportcf.cf_1457="Expence" ';
				$params_fleet = array($fleet_id);		
				$result_fleet = $db_flold->pquery($query_fleet,$params_fleet);
				$jrer_buying_fleet = $db_flold->fetch_array($result_fleet);
				
						
				//charge = cf_1453 = Fleet Expense id = 85900
				$db_plus_sub = PearDatabase::getInstance();
				$b_invoice_date_main = date('Y-m-d',strtotime($assigned_extra_buying['b_invoice_date']));
				$query_sub_fleet_update_sub = "UPDATE vtiger_jobexpencereportcf
													   INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid	 	
													   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid  
													   SET 
													   vtiger_jobexpencereportcf.cf_1337 = '".$jrer_buying_fleet['b_buy_vendor_currency_net']."', 
													   vtiger_jobexpencereportcf.cf_1339 = '".$jrer_buying_fleet['b_vat_rate']."', 
													   vtiger_jobexpencereportcf.cf_1341 = '".$jrer_buying_fleet['b_vat']."', 
													   vtiger_jobexpencereportcf.cf_1343 = '".$jrer_buying_fleet['b_buy_vendor_currency_gross']."', 
													   vtiger_jobexpencereportcf.cf_1347 = '".$jrer_buying_fleet['b_buy_local_currency_gross']."',
													   vtiger_jobexpencereportcf.cf_1349 = '".$jrer_buying_fleet['b_buy_local_currency_net']."',
													   vtiger_jobexpencereportcf.cf_1351 = '".$jrer_buying_fleet['b_expected_buy_local_currency_net']."',
													   vtiger_jobexpencereportcf.cf_1353 = '".$jrer_buying_fleet['b_variation_expected_and_actual_buying']."',
													   vtiger_jobexpencereportcf.cf_1345 = '".$assigned_extra_buying['b_vendor_currency']."',
													   vtiger_jobexpencereportcf.cf_1222 = '".$assigned_extra_buying['b_exchange_rate']."',
													   vtiger_jobexpencereportcf.cf_1216 = '".$b_invoice_date_main."'
													   WHERE vtiger_jobexpencereportcf.cf_1453='85900' AND vtiger_crmentityrel.crmid='".$job_id."' 
													   AND vtiger_jobexpencereport.fleet_trip_id='".$fleet_id."' ";
												   
				$db_plus_sub->query($query_sub_fleet_update_sub);
			}
			else{
				
				//Get old values of Job_JRER_BUYING_FLEET for using + or - sing
				//$before_update_jrer_buying_fleet = $this->db->where('id', $jrer_buying_id)->where('job_id', $id)->get('job_jrer_buying_fleet')->row();
				$db_flold = PearDatabase::getInstance();
				$query_flold = 'SELECT * FROM vtiger_jobexpencereport
							   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
							   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
							   where vtiger_jobexpencereportcf.jobexpencereportid=?
							   AND vtiger_jobexpencereportcf.cf_1457="Expence" ';
				$params_flold = array($recordId);		
				$result_flold = $db_flold->pquery($query_flold,$params_flold);
				$before_update_jrer_buying_fleet = $db_flold->fetch_array($result_flold);	
				
				$extra_buying['company_id'] = $job_info->get('cf_1186');
				$extra_buying['owner_id'] = $job_info->get('assigned_user_id');
				
				$request->set('cf_1453', $extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $extra_buying['b_office_id']);
				$request->set('cf_1479', $extra_buying['b_department_id']);
				$request->set('cf_1367', $extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $extra_buying['b_invoice_number']);
				$request->set('cf_1216', $extra_buying['b_invoice_date']);
				$request->set('cf_1210', $extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $extra_buying['b_type_id']);
				$request->set('cf_1337', $extra_buying['b_buy_vendor_currency_net']);				
				$request->set('cf_1339', $extra_buying['b_vat_rate']);
				$request->set('cf_1341', $extra_buying['b_vat']);
				$request->set('cf_1343', $extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
				$request->set('company_id', $extra_buying['company_id']);
				//$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				$recordModel = $this->saveRecord($request);
				
				
				
				
				//Updation in Fleet Main JRER
				//Update Main JRER Fleet
			$fleet_id = $fleet_id_from_expense;
			$db_flold = PearDatabase::getInstance();
			$query_fleet = 'SELECT sum(vtiger_jobexpencereportcf.cf_1337) as b_buy_vendor_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1339) as b_vat_rate, 
								   sum(vtiger_jobexpencereportcf.cf_1341) as b_vat, 
								   sum(vtiger_jobexpencereportcf.cf_1343) as b_buy_vendor_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1347) as b_buy_local_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1349) as b_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1351) as b_expected_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1353) as b_variation_expected_and_actual_buying 
						    FROM vtiger_jobexpencereport 
							INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid 
							WHERE vtiger_crmentityrel.crmid=? 
							AND vtiger_jobexpencereportcf.cf_1457="Expence" ';
			$params_fleet = array($fleet_id);		
			$result_fleet = $db_flold->pquery($query_fleet,$params_fleet);
			$jrer_buying_fleet = $db_flold->fetch_array($result_fleet);
			
			//charge = cf_1453 = Fleet Expense id = 44515
			$db_plus_sub = PearDatabase::getInstance();
			$b_invoice_date_main = date('Y-m-d',strtotime($extra_buying['b_invoice_date']));
			$query_sub_fleet_update_sub = "UPDATE vtiger_jobexpencereportcf
												    INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid	 		
												   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid  
												   SET 
												   vtiger_jobexpencereportcf.cf_1337 = '".$jrer_buying_fleet['b_buy_vendor_currency_net']."', 
												   vtiger_jobexpencereportcf.cf_1339 = '".$jrer_buying_fleet['b_vat_rate']."', 
												   vtiger_jobexpencereportcf.cf_1341 = '".$jrer_buying_fleet['b_vat']."', 
												   vtiger_jobexpencereportcf.cf_1343 = '".$jrer_buying_fleet['b_buy_vendor_currency_gross']."', 
												   vtiger_jobexpencereportcf.cf_1347 = '".$jrer_buying_fleet['b_buy_local_currency_gross']."',
												   vtiger_jobexpencereportcf.cf_1349 = '".$jrer_buying_fleet['b_buy_local_currency_net']."',
												   vtiger_jobexpencereportcf.cf_1351 = '".$jrer_buying_fleet['b_expected_buy_local_currency_net']."',
												   vtiger_jobexpencereportcf.cf_1353 = '".$jrer_buying_fleet['b_variation_expected_and_actual_buying']."',
												   vtiger_jobexpencereportcf.cf_1345 = '".$extra_buying['b_vendor_currency']."',
												   vtiger_jobexpencereportcf.cf_1222 = '".$extra_buying['b_exchange_rate']."',
												   vtiger_jobexpencereportcf.cf_1216 = '".$b_invoice_date_main."'
												   WHERE vtiger_jobexpencereportcf.cf_1453='85900' AND vtiger_crmentityrel.crmid='".$job_id."'
												    AND vtiger_jobexpencereport.fleet_trip_id='".$fleet_id."'  ";
			$db_plus_sub->query($query_sub_fleet_update_sub);
				/*$db_minus = PearDatabase::getInstance();
				$query_minu_update = "UPDATE vtiger_jobexpencereportcf SET 
																	   cf_1337 = (cf_1337 - ".$before_update_jrer_buying_fleet['cf_1343']."), 
																	   cf_1343 = (cf_1343 - ".$before_update_jrer_buying_fleet['cf_1343']."), 
																	   cf_1347 = (cf_1347 - ".$before_update_jrer_buying_fleet['cf_1347']."),
																	   cf_1349 = (cf_1349 - ".$before_update_jrer_buying_fleet['cf_1349']."),
																	   cf_1353 = (cf_1353 - ".$before_update_jrer_buying_fleet['cf_1353'].")
																	   WHERE cf_2193='Yes' ";
			
												  
				$db_minus->query($query_minu_update);
				
				//Update in Fleet Main JRER with new value
				$db_plus = PearDatabase::getInstance();
				$query_plus_update = "UPDATE vtiger_jobexpencereportcf SET 
																	   cf_1337 = (cf_1337 + ".$extra_buying['b_buy_vendor_currency_net']."), 
																	   cf_1343 = (cf_1343 + ".$extra_buying['b_buy_vendor_currency_gross']."), 
																	   cf_1347 = (cf_1347 + ".$extra_buying['b_buy_local_currency_gross']."),
																	   cf_1349 = (cf_1349 + ".$extra_buying['b_buy_local_currency_net']."),
																	   cf_1353 = (cf_1353 + ".$extra_buying['b_variation_expected_and_actual_buying']."
																	   WHERE cf_2193='Yes' ";
																	   
				
				$db_plus->query($query_plus_update);*/
				
				/*
				//							
				//Updation in Fleet Main JRER
				//First Reverse with old value in Fleet Main JRER											
				$this->db->set('b_buy_vendor_currency_net', 'b_buy_vendor_currency_net - '.$before_update_jrer_buying_fleet->b_buy_vendor_currency_net.'', FALSE);	
				$this->db->set('b_buy_vendor_currency_gross', 'b_buy_vendor_currency_gross - '.$before_update_jrer_buying_fleet->b_buy_vendor_currency_gross.'', FALSE);							
				$this->db->set('b_buy_local_currency_gross', 'b_buy_local_currency_gross - '.$before_update_jrer_buying_fleet->b_buy_local_currency_gross.'', FALSE);							
				$this->db->set('b_buy_local_currency_net', 'b_buy_local_currency_net - '.$before_update_jrer_buying_fleet->b_buy_local_currency_net.'', FALSE);	
				$this->db->set('b_variation_expected_and_actual_buying', 'b_variation_expected_and_actual_buying - '.$before_update_jrer_buying_fleet->b_variation_expected_and_actual_buying.'', FALSE);
				$this->db->where('fleet','yes')->where('job_id', $id)->update('job_jrer_buying');
						
				//Update in Fleet Main JRER with new value
				$this->db->set('b_buy_vendor_currency_net', 'b_buy_vendor_currency_net + '.$b_buy_vendor_currency_net.'', FALSE);
				$this->db->set('b_buy_vendor_currency_gross', 'b_buy_vendor_currency_gross + '.$b_buy_vendor_currency_gross.'', FALSE);
				$this->db->set('b_buy_local_currency_gross', 'b_buy_local_currency_gross + '.$b_buy_local_currency_gross.'', FALSE);
				$this->db->set('b_buy_local_currency_net', 'b_buy_local_currency_net + '.$b_buy_local_currency_net.'', FALSE);	
				$this->db->set('b_variation_expected_and_actual_buying', 'b_variation_expected_and_actual_buying + '.$subjrer_variation_expected_and_actual_buying.'', FALSE);								
				$this->db->where('fleet','yes')->where('job_id', $id)->update('job_jrer_buying');
				*/
							
			}
					
			
			$extra_expense[] = $extra_buying;	
			if($job_info->get('assigned_user_id')!=$current_user->getId())
			{	
				$assigned_extra_expense[] = $assigned_extra_buying;
			}	
		
		
	}
	
	
	public function round_trip_buying_edit($request)
	{
		$recordId = $request->get('record');
		
		$expense_file_title  =$request->get('cf_2191');
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$expense_file_title);
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");
		
		$extra_expense = array();			
		$assigned_extra_expense = array();		
		$assigned_extra_selling = array();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$fleet_id_from_expense = $this->get_round_trip_id_from_expense($recordId);
		
		$sourceModule = 'Fleettrip';	
		$fleet_trip_info = Vtiger_Record_Model::getInstanceById($fleet_id_from_expense, $sourceModule);
		
		$fleettrip_jrer_buying = array();
		if($recordId!=0)
		{
			$db_buying = PearDatabase::getInstance();						
			$jrer_buying = 'SELECT * FROM vtiger_jobexpencereport
						   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jrer_buying_id=? 
						   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
			//jobid as record_id						   
			//$params_buying = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			$params_buying = array(@$fleet_id_from_expense, $fleet_trip_info->get('assigned_user_id'), $recordId);	
			$result_buying = $db_buying->pquery($jrer_buying,$params_buying);
			$fleetrip_jrer_buying = $db_buying->fetch_array($result_buying);
			
			//First Time expense editing SUBJCR user
			if($db_buying->num_rows($result_buying)==0)
			{
				$db_buying_2 = PearDatabase::getInstance();		
				$jrer_buying_2 = 'SELECT * FROM vtiger_jobexpencereport
							      INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
							      INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
							      where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.jobexpencereportid=? 
							      and vtiger_jobexpencereportcf.cf_1457="Expence" ';
				//$params_buying_2 = array(@$job_info->get('record_id'), $recordId);
				$params_buying_2 = array(@$fleet_id_from_expense, $recordId);
				$result_buying_2 = $db_buying_2->pquery($jrer_buying_2,$params_buying_2);
				$fleettrip_jrer_buying = $db_buying_2->fetch_array($result_buying_2);				
			}
			$expense_truck_id = $fleetrip_jrer_buying['cf_2195'];			
						
		}
		
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		
		$b_office_id	  = $current_user->get('location_id');
		
		$b_pay_to_id	  = $request->get('cf_1367');
		$b_invoice_number = $request->get('cf_1212');
		$b_invoice_date   = $request->get('cf_1216');
		$b_invoice_due_date = $request->get('cf_1210');
		$b_type_id	  	  = $request->get('cf_1214');
		$b_buy_vendor_currency_net	= $request->get('cf_1337');
		if(empty($b_buy_vendor_currency_net)) 
		{ 
			$b_buy_vendor_currency_net = 0;
		}
		$b_vat_rate	  	  = $request->get('cf_1339');
		$vat_included 		= $request->get('cf_3293');
		
		$b_vat = '0.00';
		$b_buying_customer_currency_gross = '0.00';
		if(!empty($b_vat_rate) && $b_vat_rate>0)
		{
			if($vat_included=='Yes')
			{
				$b_buying_customer_currency_gross = $request->get('cf_1343');
				$b_vat_rate_ = $b_vat_rate + 100;
				$b_vat_rate_cal = $b_vat_rate_/100; 
				
				$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
				$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
			}
			else
			{
			$b_vat_rate_cal  = $b_vat_rate/100; 
			$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
			}
		}
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		$b_vendor_currency	  = $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		$invoice_date_arr = explode('-',$b_invoice_date);
		$b_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date_final));
		
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);		
		if($file_title_currency =='KZT')
		{			
			$b_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		else{			
			$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		
		
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_gross = $b_buy_vendor_currency_gross * $b_exchange_rate;
		}else{
			$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
		}
		
				
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_net = $b_buy_vendor_currency_net * $b_exchange_rate;
		}else{
			$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_net, $b_invoice_date_format);
		}
		
		$b_expected_buy_local_currency_net	  = $request->get('cf_1351');
		
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		$file_title_id = $request->get('cf_2191');
		
		$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
		$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));		
		
		if($b_type_id=='85793') //For JZ company
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		elseif($b_type_id=='85773') // For DLC company
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			
		}		
		//Revenue				
		//$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
		$b_gl_account = @$coa_info.'-'.$office.'-'.$department;
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);	
			
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		
		//Trade Creditors					
		//$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;
		$b_ap_gl_account = @$ap_coa_info.'-'.$office.'-'.$department;
		
		$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);		
				
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
		if($assigned_file_title_currency =='KZT')
		{	
			$bb_exchange_rate = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
		}
		elseif($assigned_file_title_currency =='USD')
		{
			$bb_exchange_rate = currency_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
		}
		else{			
			$bb_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
		}
		
		if($assigned_file_title_currency !='USD')
		{						
			$bb_buy_local_currency_gross = $b_buy_vendor_currency_gross * $bb_exchange_rate;
		}else{
			$bb_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
		}
		
		if($assigned_file_title_currency !='USD')
		{						
			$bb_buy_local_currency_net = $b_buy_vendor_currency_net * $bb_exchange_rate;
		}else{
			$bb_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency,$b_buy_vendor_currency_net, $b_invoice_date_format);
		}
		$bb_expected_buy_local_currency_net	  = $request->get('cf_1351');	
		
		$bb_variation_expected_and_actual_buying = $bb_expected_buy_local_currency_net - $bb_buy_local_currency_net;
		
		$assigned_extra_buying = array(
										'job_id'  			=> 0,
										'date_added'		=> date('Y-m-d H:i:s'),
										'b_job_charges_id' 	=> $b_job_charges_id,
										'b_office_id' 		=> $b_office_id,
										'b_department_id' 	=> $b_department_id,
										'b_pay_to_id' 		=> $b_pay_to_id,
										'b_invoice_number' 	=> $b_invoice_number,
										'b_invoice_date' 	=> $b_invoice_date,
										'b_invoice_due_date'=> $b_invoice_due_date,
										'b_type_id'			=> $b_type_id,			
										'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
										'b_vat_rate'        => $b_vat_rate,
										'b_vat'             => $b_vat,
										'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
										'b_vendor_currency' => $b_vendor_currency,
										'b_exchange_rate'   => $bb_exchange_rate,
										'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
										'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
										'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
										'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
										'b_gl_account'		=> $b_gl_account,
										'b_ar_gl_account'	=> $b_ap_gl_account,												
									 );
									 
				
				$assigned_extra_buying['company_id'] = @$file_title_id;
				$assigned_extra_buying['user_id'] 	 = $current_user->getId();
				$assigned_extra_buying['owner_id']   = $current_user->getId();
				//$assigned_extra_buying['jrer_buying_id'] = 0;
				
				$request->set('cf_1453', $assigned_extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $assigned_extra_buying['b_office_id']);
				$request->set('cf_1479', $assigned_extra_buying['b_department_id']);
				$request->set('cf_1367', $assigned_extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $assigned_extra_buying['b_invoice_number']);
				$request->set('cf_1216', $assigned_extra_buying['b_invoice_date']);
				$request->set('cf_1210', $assigned_extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $assigned_extra_buying['b_type_id']);
				$request->set('cf_1337', $assigned_extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $assigned_extra_buying['b_vat_rate']);
				$request->set('cf_1341', $assigned_extra_buying['b_vat']);
				$request->set('cf_1343', $assigned_extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $assigned_extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $assigned_extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $assigned_extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $assigned_extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $assigned_extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $assigned_extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $assigned_extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $assigned_extra_buying['b_ar_gl_account']);
				$request->set('company_id', $assigned_extra_buying['company_id']);
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
				
				$request->set('record', $recordId);			
				
				$recordModel = $this->saveRecord($request);	
				
			if($fleet_trip_info->get('assigned_user_id')!=$current_user->getId())
			{
				$assigned_extra_buying['user_id'] 	= $current_user->getId();
				$assigned_extra_buying['owner_id']   = $fleet_trip_info->get('assigned_user_id');
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				$request->set('record', $fleetrip_jrer_buying['jobexpencereportid']);			
				$recordModel = $this->saveRecord($request);
			}
		
		
	}
	
	public function fleet_selling_edit($request)
	{
		$recordId = $request->get('record');
		
		$job_id = $this->get_job_id_from_fleet($recordId);
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		
		/*
		$recordId = $request->get('record');
		$job_id = $this->get_job_id($recordId);
		
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
				
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;*/
		
		include("include/Exchangerate/exchange_rate_class.php");
		
		$assigned_extra_selling = array();
		$s_generate_invoice_instruction_flag = true;
		$invoice_instruction = '';
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$s_job_charges_id = $request->get('cf_1455');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$s_office_id	  = $current_user->get('location_id');
			//$s_department_id  = $current_user->get('department_id');
			$s_department_id  = $request->get('cf_1479');
		}else{
			$s_office_id	  = $request->get('cf_1477');
			$s_department_id  = $request->get('cf_1479');
		}
		$s_bill_to_id 		  = $request->get('cf_1445');
		$s_bill_to_address 	 = $request->get('cf_1359');
		$s_ship_to 			 = $request->get('cf_1361');
		$s_ship_to_address 	 = $request->get('cf_1363');
		$s_remarks 			 = $request->get('cf_1365');
		$s_invoice_date 	    = $request->get('cf_1355');
		$s_selling_customer_currency_net = $request->get('cf_1357');
		$s_vat_rate 		  = $request->get('cf_1228');
		
		$vat_included = $request->get('cf_2695');
		
		
		$s_vat = '0.00';
		$s_selling_customer_currency_gross = '0.00';
		
		if(!empty($s_vat_rate) && $s_vat_rate>0)
		{
			if($vat_included=='Yes')
			{
				$s_selling_customer_currency_gross = $request->get('cf_1232');
				$s_vat_rate_ = $s_vat_rate + 100;
				$s_vat_rate_cal = $s_vat_rate_/100; 
				
				$s_selling_customer_currency_net = 	$s_selling_customer_currency_gross / $s_vat_rate_cal;
				
				$s_vat = $s_selling_customer_currency_gross - $s_selling_customer_currency_net;

			}
			else{
			$s_vat_rate_cal = $s_vat_rate/100; 
			$s_vat = 	$s_selling_customer_currency_net * $s_vat_rate_cal;
			}
		}
		
		if($vat_included=='Yes')
		{
			$s_sell_customer_currency_gross =$s_selling_customer_currency_gross;
			//$s_sell_customer_currency_net = $s_selling_customer_currency_net; 
		}
		else{
			//$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
			$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
		}
		
		
		
		$s_customer_currency = $request->get('cf_1234');
		
		//$job_id 			  = $request->get('sourceRecord');
		//$sourceModule 		  = $request->get('sourceModule');	
		//$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		
		$invoice_date_arr = explode('-',$s_invoice_date);
		$s_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date_final));
		
		//$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date));
		$s_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
		
		if($file_title_currency =='KZT')
		{			
			$s_exchange_rate  		= exchange_rate_currency($s_invoice_date_format, $s_customer_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$s_exchange_rate = currency_rate_convert($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
		}
		else{			
			$s_exchange_rate = currency_rate_convert_others($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
		}
		
		if($file_title_currency !='USD')
		{	
			$s_sell_local_currency_gross = $s_sell_customer_currency_gross * $s_exchange_rate;
		}else{
			$s_sell_local_currency_gross = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $s_sell_customer_currency_gross, $s_invoice_date_format);
		}
		
		if($file_title_currency !='USD')
		{	
			$s_sell_local_currency_net = $s_selling_customer_currency_net * $s_exchange_rate;
		}else{
			$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency_code, $file_title_currency,$s_selling_customer_currency_net, $s_invoice_date_format);
		}
		
		$s_expected_sell_local_currency_net = $request->get('cf_1242');				
		$ss_expected_sell_local_currency_net = $request->get('cf_1242');
		
		//$s_variation_expected_and_actual_sellling  = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		//$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		$job_jrer_selling = array();
		if($recordId!=0)
		{
			$db_selling = PearDatabase::getInstance();						

			$jrer_selling = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Selling" ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC limit 1';
			//jobid as record_id						   
			$params_selling = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			
			$result_selling = $db_selling->pquery($jrer_selling, $params_selling);
			$job_jrer_selling = $db_selling->fetch_array($result_selling);
			
			$job_costing_id = @$job_jrer_selling['jerid'];
			if(!empty($job_costing_id))
			{
													   
			$db_costing_1 = PearDatabase::getInstance();		
			$query_costing = 'SELECT * FROM vtiger_jer
								   INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
								   where vtiger_crmentityrel.crmid=? and vtiger_jer.jerid=? AND vtiger_jercf.cf_1451=? AND vtiger_jercf.cf_1433=? AND vtiger_jercf.cf_1435=?';
			$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id, $s_job_charges_id, $s_office_id, $s_department_id);
			
			$result_costing_1 = $db_costing_1->pquery($query_costing, $params_costing_1);
			$sell_of_local_currency = $db_costing_1->fetch_array($result_costing_1);  	
			//sell_local_currency
			$s_expected_sell_local_currency_net = @$sell_of_local_currency['cf_1168'];
			}
		}
		
		$s_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.selling_expence="no"
								   and vtiger_jobexpencereportcf.cf_1457="Expence" limit 1';
			$params_buying_exp = array($job_id, 
									   $s_job_charges_id, 
									   $s_office_id, 
									   $s_department_id,
									   $current_user->getId()						  
									  );
							  
			$result_buying_exp = $db_buying_exp->pquery($jrer_buying_exp,$params_buying_exp);
			$b_variation_expected_and_actual_buying_arr = $db_buying_exp->fetch_array($result_buying_exp);	
		}
		else{			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.user_id =?
								   and vtiger_jobexpencereportcf.cf_1457="Expence" limit 1';
			$params_buying_exp = array($job_id, 
									   $s_job_charges_id, 
									   $s_office_id, 
									   $s_department_id,
									   $current_user->getId(),
									   $current_user->getId(),
									  );
									  
			$result_buying_exp = $db_buying_exp->pquery($jrer_buying_exp,$params_buying_exp);
			$b_variation_expected_and_actual_buying_arr = $db_buying_exp->fetch_array($result_buying_exp);	
		}
		
		$s_variation_expect_and_actual_profit = $s_variation_expected_and_actual_sellling + @$b_variation_expected_and_actual_buying_arr['cf_1353'];				
		$ss_variation_expect_and_actual_profit = $ss_variation_expected_and_actual_sellling + @$b_variation_expected_and_actual_buying_arr['cf_1353'];
		
		$db_revenue = PearDatabase::getInstance();
		$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_revenue = array('Revenue');
		$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
		$gl_job_charges_id = $db_revenue->fetch_array($result_revenue);
		
		$db_coa = PearDatabase::getInstance();
		$query_coa = 'SELECT * FROM vtiger_companyaccount
					  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
					  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
					  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
		$params_coa = array($job_info->get('cf_1186'), $gl_job_charges_id['chartofaccountid']);
		$result_coa = $db_coa->pquery($query_coa, $params_coa);
		$coa_info = $db_coa->fetch_array($result_coa);
		
		if($job_office_id==$current_user_office_id){
			$file_title_id = $job_info->get('cf_1186');
		}
		else{
			$file_title_id = $current_user->get('company_id');
		}
		
		//Revenue
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));
		}
		else{			
			$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
			$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);
		}		
		$gl_account = $coa_info['name'].'-'.$office.'-'.$department;
		
		$db_ar = PearDatabase::getInstance();
		$query_ar = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_ar = array('Trade Debtors');
		$result_ar = $db_ar->pquery($query_ar,$params_ar);
		$ar_job_charges_id = $db_ar->fetch_array($result_ar);
		
		if($job_info->get('assigned_user_id')!=$current_user->getId() && $job_office_id!=$current_user->get('location_id'))
		{
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ar_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ar_coa_info = $db_coa->fetch_array($result_coa);
		}
		else{
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ar_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ar_coa_info = $db_coa->fetch_array($result_coa);
		}					
		$s_ar_gl_account = $ar_coa_info['name'].'-'.$office.'-'.$department;
		
		$extra_selling['s_generate_invoice_instruction'] = 0;
		$extra_selling_normal['s_generate_invoice_instruction'] = 0;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			
			if($job_office_id==$current_user_office_id){
				$file_title_id = $job_info->get('cf_1186');
			}
			else{
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? AND user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				
			}
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
			
			$ss_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
			if($assigned_file_title_currency == 'KZT')
			{						
				$ss_exchange_rate = exchange_rate_currency($s_invoice_date_format, $ss_customer_currency_code);					
			}
			elseif($assigned_file_title_currency =='USD')
			{
				$ss_exchange_rate = currency_rate_convert($ss_customer_currency_code, $assigned_file_title_currency, 1, $s_invoice_date_format);
			}
			else{
				$ss_exchange_rate = currency_rate_convert_others($ss_customer_currency_code, $assigned_file_title_currency, 1, $s_invoice_date_format);
			}
						
			if($assigned_file_title_currency !='USD')
			{						
				$ss_sell_local_currency_gross = $s_sell_customer_currency_gross * $ss_exchange_rate;
			}else{
				$ss_sell_local_currency_gross = exchange_rate_convert($ss_customer_currency_code, $assigned_file_title_currency,$s_sell_customer_currency_gross,$s_invoice_date_format);			
			}
			
			if($assigned_file_title_currency !='USD')
			{	
				$ss_sell_local_currency_net = $s_selling_customer_currency_net * $ss_exchange_rate;
			}else{
				$ss_sell_local_currency_net = exchange_rate_convert($ss_customer_currency_code, $assigned_file_title_currency, $s_selling_customer_currency_net, $s_invoice_date_format);
			}
			
			$extra_selling = array(
									//'user_id' => $this->current_user->id,
									'job_id'  		   => $job_id,
									'date_added'	   => date('Y-m-d H:i:s'),
									's_job_charges_id' => $s_job_charges_id,
									's_department_id'  => $s_department_id,
									's_office_id' 	   => $s_office_id,
									's_customer_id'    => $s_bill_to_id,
									's_bill_to_id'     => $s_bill_to_id,
									's_bill_to_address'=> $s_bill_to_address,
									's_ship_to'  	   => $s_ship_to,
									's_ship_to_address'=> $s_ship_to_address,
									's_remarks'   	   => $s_remarks,
									's_invoice_date'   => $s_invoice_date,
									's_selling_customer_currency_net' => $s_selling_customer_currency_net,
									's_vat_rate'  => $s_vat_rate,
									's_vat'       => $s_vat,
									's_sell_customer_currency_gross' => $s_sell_customer_currency_gross,
									's_customer_currency'           => $s_customer_currency,
									's_exchange_rate'           => $ss_exchange_rate,
									's_sell_local_currency_gross' => $ss_sell_local_currency_gross,
									's_sell_local_currency_net'      => $ss_sell_local_currency_net,
									's_expected_sell_local_currency_net' => (isset($ss_expected_sell_local_currency_net) ? $ss_expected_sell_local_currency_net : '0.00'),
									's_variation_expected_and_actual_sellling' => (isset($ss_variation_expected_and_actual_sellling) ? $ss_variation_expected_and_actual_sellling :'0.00'),
									's_variation_expect_and_actual_profit' => $ss_variation_expect_and_actual_profit,
									//'s_generate_invoice_instruction' => ((isset($s_generate_invoice_instruction) && $s_generate_invoice_instruction==1)  ? 1 : 0 ),
									'gl_account'	=> $gl_account,
									'ar_gl_account' => $s_ar_gl_account,
								  );
		}
		
		$extra_selling_normal = array(
										//'user_id' => $this->current_user->id,
										'job_id'  		   => $job_id,
										'date_added'	   => date('Y-m-d H:i:s'),
										's_job_charges_id' => $s_job_charges_id,
										's_department_id'  => $s_department_id,
										's_office_id' => $s_office_id,
										's_customer_id' => $s_bill_to_id,
										's_bill_to_id' => $s_bill_to_id,
										's_bill_to_address' => $s_bill_to_address,
										's_ship_to'  => $s_ship_to,
										's_ship_to_address' => $s_ship_to_address,
										's_remarks'   => $s_remarks,
										's_invoice_date' => $s_invoice_date,
										's_selling_customer_currency_net' => $s_selling_customer_currency_net,
										's_vat_rate'  => $s_vat_rate,
										's_vat'       => $s_vat,
										's_sell_customer_currency_gross' => $s_sell_customer_currency_gross,
										's_customer_currency'            => $s_customer_currency,
										's_exchange_rate'           	 => $s_exchange_rate,
										's_sell_local_currency_gross' 	 => $s_sell_local_currency_gross,
										's_sell_local_currency_net'      => $s_sell_local_currency_net,
										's_expected_sell_local_currency_net' => (isset($s_expected_sell_local_currency_net) ? $s_expected_sell_local_currency_net : '0.00'),
										's_variation_expected_and_actual_sellling' => (isset($s_variation_expected_and_actual_sellling) ? $s_variation_expected_and_actual_sellling :'0.00'),
										's_variation_expect_and_actual_profit' => $s_variation_expect_and_actual_profit,
										//'s_generate_invoice_instruction' => ((isset($s_generate_invoice_instruction) && $s_generate_invoice_instruction==1)  ? 1 : 0 ),
										'gl_account'	=> $gl_account,
										'ar_gl_account' => $s_ar_gl_account,
									  );
		
			
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$extra_selling['company_id'] = $file_title_id;
			//$extra_selling['s_jrer_buying_id'] = 0;
						
			$request->set('cf_1455', $extra_selling['s_job_charges_id']);
			$request->set('cf_1477', $extra_selling['s_office_id']);
			$request->set('cf_1479', $extra_selling['s_department_id']);
			$request->set('cf_1445', $extra_selling['s_bill_to_id']);
			$request->set('cf_1359', $extra_selling['s_bill_to_address']);
			$request->set('cf_1361', $extra_selling['s_ship_to']);
			$request->set('cf_1363', $extra_selling['s_ship_to_address']);
			$request->set('cf_1365', $extra_selling['s_remarks']);
			$request->set('cf_1355', $extra_selling['s_invoice_date']);			
			$request->set('cf_1357', $extra_selling['s_selling_customer_currency_net']);
			$request->set('cf_1228', $extra_selling['s_vat_rate']);
			$request->set('cf_1230', $extra_selling['s_vat']);
			$request->set('cf_1232', $extra_selling['s_sell_customer_currency_gross']);
			$request->set('cf_1234', $extra_selling['s_customer_currency']);
			$request->set('cf_1236', $extra_selling['s_exchange_rate']);
			$request->set('cf_1238', $extra_selling['s_sell_local_currency_gross']);
			$request->set('cf_1240', $extra_selling['s_sell_local_currency_net']);
			$request->set('cf_1242', $extra_selling['s_expected_sell_local_currency_net']);
			$request->set('cf_1244', $extra_selling['s_variation_expected_and_actual_sellling']);
			$request->set('cf_1246', $extra_selling['s_variation_expect_and_actual_profit']);
			
			$request->set('gl_account', $extra_selling['gl_account']);
			$request->set('ar_gl_account', $extra_selling['ar_gl_account']);
			//$request->set('company_id', $extra_selling['company_id']);
			//$request->set('user_id', $extra_selling['user_id']);
			//$request->set('owner_id', $extra_selling['owner_id']);
			//$request->set('s_jrer_buying_id', $extra_selling['s_jrer_buying_id']);
			$recordModel = $this->saveRecord($request);
			$jrer_selling_id_assigned = $recordModel->getId();
			
		}
		else{
			$extra_selling_normal['company_id'] = $file_title_id;	
			$extra_selling_normal['user_id'] = $current_user->getId();
			$extra_selling_normal['owner_id'] = $current_user->getId();
			
			$request->set('cf_1455', $extra_selling_normal['s_job_charges_id']);
			$request->set('cf_1477', $extra_selling_normal['s_office_id']);
			$request->set('cf_1479', $extra_selling_normal['s_department_id']);
			$request->set('cf_1445', $extra_selling_normal['s_bill_to_id']);
			$request->set('cf_1359', $extra_selling_normal['s_bill_to_address']);
			$request->set('cf_1361', $extra_selling_normal['s_ship_to']);
			$request->set('cf_1363', $extra_selling_normal['s_ship_to_address']);
			$request->set('cf_1365', $extra_selling_normal['s_remarks']);
			$request->set('cf_1355', $extra_selling_normal['s_invoice_date']);			
			$request->set('cf_1357', $extra_selling_normal['s_selling_customer_currency_net']);
			$request->set('cf_1228', $extra_selling_normal['s_vat_rate']);
			$request->set('cf_1230', $extra_selling_normal['s_vat']);
			$request->set('cf_1232', $extra_selling_normal['s_sell_customer_currency_gross']);
			$request->set('cf_1234', $extra_selling_normal['s_customer_currency']);
			$request->set('cf_1236', $extra_selling_normal['s_exchange_rate']);
			$request->set('cf_1238', $extra_selling_normal['s_sell_local_currency_gross']);
			$request->set('cf_1240', $extra_selling_normal['s_sell_local_currency_net']);
			$request->set('cf_1242', $extra_selling_normal['s_expected_sell_local_currency_net']);
			$request->set('cf_1244', $extra_selling_normal['s_variation_expected_and_actual_sellling']);
			$request->set('cf_1246', $extra_selling_normal['s_variation_expect_and_actual_profit']);
			
			$request->set('gl_account', $extra_selling_normal['gl_account']);
			$request->set('ar_gl_account', $extra_selling_normal['ar_gl_account']);
			$request->set('company_id', $extra_selling_normal['company_id']);
			$request->set('user_id', $extra_selling_normal['user_id']);
			$request->set('owner_id', $extra_selling_normal['owner_id']);
			///$request->set('s_jrer_buying_id', $extra_selling_normal['s_jrer_buying_id']);
				
			$recordModel = $this->saveRecord($request);
			$jobexpencereportid = $recordModel->getId();
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, gl_account=?, ar_gl_account=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_selling_normal['gl_account'], $extra_selling_normal['ar_gl_account'], $extra_selling_normal['company_id'], $extra_selling_normal['user_id'], $extra_selling_normal['owner_id'], $jobexpencereportid);
			$result_up = $db_up->pquery($query_up, $params_up);	
		}	
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$assigned_extra_selling[] = $extra_selling_normal;
		}
		
		
		
		
	}
	
	public function get_company_account_id($chartofaccount_name, $link_company_id)
	{
		$db_revenue_trade = PearDatabase::getInstance();
		$query_revenue_trade = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_revenue_trade = array($chartofaccount_name);
		$result_revenue_trade = $db_revenue_trade->pquery($query_revenue_trade,$params_revenue_trade);
		$job_charges_id = $db_revenue_trade->fetch_array($result_revenue_trade);
		
		$db_coa = PearDatabase::getInstance();
		$query_coa = 'SELECT * FROM vtiger_companyaccount
					  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
					  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid						  
					  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
		$params_coa = array($link_company_id, $job_charges_id['chartofaccountid']);
		$result_coa = $db_coa->pquery($query_coa, $params_coa);
		$coa_info = $db_coa->fetch_array($result_coa);
		return $coa_info['name'];
	}
	
	public function get_job_id($recordId=0)
	{
		$adb = PearDatabase::getInstance();
		
		$query_job_expense =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport"';
		$check_params = array($recordId);
		$result       = $adb->pquery($query_job_expense, $check_params);
		$row          = $adb->fetch_array($result);
		$job_id 	   = $row['crmid'];
		//$sourceModule = $row['module'];
		return $job_id;
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
