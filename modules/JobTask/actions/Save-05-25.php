<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JobTask_Save_Action extends Vtiger_Save_Action {

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
		$recordModel = $this->saveRecord($request);

		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		header("Location: $loadUrl");
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
		

		$sql = "update vtiger_jobtask SET job_id = ".$_POST['sourceRecord'].", user_id =".$_POST['assigned_user_id']." WHERE jobtaskid = ". $recordModel->getId();
		//echo $sql;
		//exit;
		$sql = mysql_query($sql) or die(mysql_error());
		
		$user_info = Vtiger_Record_Model::getInstanceById($_POST['assigned_user_id'], 'Users');
		$department_id = $user_info->get('department_id');
		$location_id   = $user_info->get('location_id');
		
		//$costing_branch_id = 'cf_1433';
		//$costing_department_id = 'cf_1435';
		
		$db_costing = PearDatabase::getInstance();
		$costing_expense = 'SELECT * FROM vtiger_jer
							INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
							 where vtiger_crmentityrel.crmid=? AND  vtiger_jercf.cf_1433=? AND vtiger_jercf.cf_1435=?
							';
		$params_costing = array($_POST['sourceRecord'], $location_id, $department_id);
		
		$result_costing = $db_costing->pquery($costing_expense,$params_costing);
		//$costing_info = $db_costing->fetch_array($result_costing);	
		$num_rows = $db_costing->num_rows($result_costing);
		
		for($i = 0; $i<$num_rows; $i++) {
		   $job_costing_id = decode_html($db_costing->query_result($result_costing, $i, 'jerid'));	
		   $charge_id = decode_html($db_costing->query_result($result_costing, $i, 'cf_1451'));
		   $office_id = decode_html($db_costing->query_result($result_costing, $i, 'cf_1433'));
		   $dept_id = decode_html($db_costing->query_result($result_costing, $i, 'cf_1435'));
		   //Expected Cost
		   $vendor_agent_id = decode_html($db_costing->query_result($result_costing, $i, 'cf_1176'));
		   $buy_currency = decode_html($db_costing->query_result($result_costing, $i, 'cf_1156'));
		   $cost_exchange_rate = decode_html($db_costing->query_result($result_costing, $i, 'cf_1158'));
		   $cost_local_currecny = decode_html($db_costing->query_result($result_costing, $i, 'cf_1160'));
		   
		   $adb = PearDatabase::getInstance();
		   $date_var = date("Y-m-d H:i:s");
		   $usetime = $adb->formatDate($date_var, true);
		   
		   $current_id = $adb->getUniqueId('vtiger_crmentity');
		   $source_id = $_POST['sourceRecord'];
		   
		     
		   //INSERT data in JRER expense module from job costing
			$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				 setype, description, createdtime, modifiedtime, presence, deleted, label)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array($current_id, $_POST['assigned_user_id'], $_POST['assigned_user_id'], 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, 'Job Costing Buy'));
			
			
			$db_assign = PearDatabase::getInstance();
			$query_assign = 'SELECT * FROM vtiger_jobexpencereport where jerid=? and job_costing_type=?';
			$params_assign = array($job_costing_id, 'Expense');
			$result_assign = $db_assign->pquery($query_assign,$params_assign);
			$job_costing_expense = $db_assign->fetch_array($result_assign);
			
			$db_assign_update = PearDatabase::getInstance();
			$query_assign_update = 'update vtiger_crmentity set smownerid=? where crmid=?';
			$params_assign_update = array($_POST['assigned_user_id'], $job_costing_expense['jobexpencereportid']);
			$result_assign_update = $db_assign_update->pquery($query_assign_update,$params_assign_update);
			
			//INSERT data in jobexpencereport module from job costing
			$adb_e = PearDatabase::getInstance();
			$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, job_costing_type, job_id, user_id, owner_id, jrer_buying_id) VALUES(?,?,?,?,?,?,?)";
			$params_jobexpencereport= array($current_id, $source_id, 'Expense', $source_id,  $_POST['assigned_user_id'],  $_POST['assigned_user_id'], $job_costing_expense['jobexpencereportid']);		
			$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
			$jobexpencereportid = $adb_e->getLastInsertID();
			
			$adb_ecf = PearDatabase::getInstance();
			$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1453, cf_1477, cf_1479, cf_1367, cf_1345, cf_1222, cf_1351, cf_1457) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$params_jobexpencereportcf = array($current_id, $charge_id, $office_id, $dept_id, $vendor_agent_id, $buy_currency, $cost_exchange_rate/*$cf_1158[$key]*/, $cost_local_currecny/*$cf_1160[$key]*/, 'Expence');
			$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
			$jobexpencereportcfid = $adb_ecf->getLastInsertID();
			
			$adb_rel = PearDatabase::getInstance();
			$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
			$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
			$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
		   
		   //Expected Revenue
		   $bill_to_id = decode_html($db_costing->query_result($result_costing, $i, 'cf_1443'));
		   $sell_currency = decode_html($db_costing->query_result($result_costing, $i, 'cf_1164'));
		   $revenue_exchange_rate = decode_html($db_costing->query_result($result_costing, $i, 'cf_1166'));
		   $sell_local_currency = decode_html($db_costing->query_result($result_costing, $i, 'cf_1168'));
		   
		   //INSERT data in JRER selling module from job costing
			$current_id = $adb_rel->getUniqueId('vtiger_crmentity');
			$adb_crm = PearDatabase::getInstance();
			$adb_crm->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				 setype, description, createdtime, modifiedtime, presence, deleted, label)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array($current_id, $_POST['assigned_user_id'], $_POST['assigned_user_id'], 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, 'Job Costing Sell'));
			
			$db_assign = PearDatabase::getInstance();
			$query_assign = 'SELECT * FROM vtiger_jobexpencereport where jerid=? and job_costing_type=?';
			$params_assign = array($job_costing_id, 'Selling');
			$result_assign = $db_assign->pquery($query_assign,$params_assign);
			$job_costing_expense = $db_assign->fetch_array($result_assign);
			
			$db_assign_update = PearDatabase::getInstance();
			$query_assign_update = 'update vtiger_crmentity set smownerid=? where crmid=?';
			$params_assign_update = array($_POST['assigned_user_id'], $job_costing_expense['jobexpencereportid']);
			$result_assign_update = $db_assign_update->pquery($query_assign_update,$params_assign_update);
			//INSERT data in jobexpencereport module from job costing
			$adb_s = PearDatabase::getInstance();
			$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, job_costing_type, job_id, user_id, owner_id) VALUES(?,?,?,?,?,?)";			
			$params_jobexpencereport= array($current_id, $source_id, 'Selling', $source_id, $_POST['assigned_user_id'], $_POST['assigned_user_id']);
			$adb_s->pquery($jobexpencereport_insert_query, $params_jobexpencereport);
			$jobexpencereportid = $adb_s->getLastInsertID();
			//cf_1455 = s_job_charges_id
			//cf_1477 = Office
			//cf_1479 = Department
			//cf_1445 = s_bill_to_id
			//cf_1355 = s_invoice_date				
			//cf_1234 = s_customer_currency
			//cf_1236 = s_exchange_rate
			//cf_1242 = s_expected_sell_local_currency_net
			$adb_scf = PearDatabase::getInstance();
			$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf (jobexpencereportid, cf_1455, cf_1477, cf_1479, cf_1445, cf_1234, cf_1236, cf_1242, cf_1457) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$params_jobexpencereportcf = array($current_id, $charge_id, $office_id, $dept_id, $bill_to_id, $sell_currency, $revenue_exchange_rate/*$cf_1166[$key]*/, $sell_local_currency/*$cf_1168[$key]*/, 'Selling');
			$adb_scf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
			$jobexpencereportcfid = $adb_scf->getLastInsertID();
			
			$adb_srel = PearDatabase::getInstance();
			$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
			$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
			$adb_srel->pquery($crmentityrel_insert_query, $params_crmentityrel);	  
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
