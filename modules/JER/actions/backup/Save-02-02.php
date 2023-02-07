<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JER_Save_Action extends Vtiger_Save_Action {

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
		
		$recordId = $request->get('record');
		if(empty($recordId)) {
			$adb = PearDatabase::getInstance();
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$ownerId = $recordModel->get('assigned_user_id');
			
			
			
			$date_var = date("Y-m-d H:i:s");
			$usetime = $adb->formatDate($date_var, true);
			
			$check_dept_office_job_jobexp =  'SELECT * from vtiger_jobexpcf as jobexpcf
											  INNER JOIN vtiger_crmentityrel as crmentityrel ON crmentityrel.relcrmid=jobexpcf.jobexpid
											  where jobexpcf.cf_1259=? AND jobexpcf.cf_1257=? AND crmentityrel.crmid=? and crmentityrel.module="Job"';
			$check_params = array($_POST['cf_1435'],$_POST['cf_1433'], $_POST['sourceRecord']);
			$result = $adb->pquery($check_dept_office_job_jobexp, $check_params);
			$row = $adb->fetch_array($result);						  
			if($adb->num_rows($result)==0)
			{	
			   $current_id = $adb->getUniqueId('vtiger_crmentity');
			    // Below is profit share data before entering expense against department and office		
				$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $ownerId, 'Jobexp', 'NULL', $date_var, $date_var, 1, 0, $_POST['name']));
				
				//INSERT data in jobexp module from job costing
				$jobexp_insert_query = "INSERT INTO vtiger_jobexp(jobexpid, name) VALUES(?,?)";			
				$params= array($current_id, $_POST['name']);			
				$adb->pquery($jobexp_insert_query, $params);			
				$jobexpid = $adb->getLastInsertID();
				
				$jobexpcf_insert_query = "INSERT INTO vtiger_jobexpcf(jobexpid, cf_1259, cf_1257) VALUES(?, ?, ?)";
				$params_jobexpcf = array($current_id, $_POST['cf_1435'], $_POST['cf_1433']);
				$adb->pquery($jobexpcf_insert_query, $params_jobexpcf);	
				$jobexpcfid = $adb->getLastInsertID();
				
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($_POST['sourceRecord'], 'Job', $jobexpid, 'Jobexp');
				$adb->pquery($crmentityrel_insert_query, $params_crmentityrel);
				$source_id = $jobexpcfid;
			}
			else{
				$source_id = $row["relcrmid"];
			}
			
			$current_id = $adb->getUniqueId('vtiger_crmentity');
			$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $ownerId, 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $_POST['name']));
				
			//INSERT data in jobexpencereport module from job costing
			$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name) VALUES(?,?)";			
			$params_jobexpencereport= array($current_id, $_POST['name']);			
			$adb->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
			$jobexpencereportid = $adb->getLastInsertID();
			//cf_1345 = vendor currency
			//cf_1222 = exchange rate
			//cf_1351 = Expected Buy (Local Currency NET)
			$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1345, cf_1222, cf_1351, cf_1234, cf_1236, cf_1242) VALUES(?, ?, ?, ?, ?, ?, ?)";
			$params_jobexpencereportcf = array($current_id, $_POST['cf_1156'], $_POST['cf_1158'], $_POST['cf_1160'], $_POST['cf_1164'], $_POST['cf_1166'], $_POST['cf_1168']);
			$adb->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
			$jobexpencereportcfid = $adb->getLastInsertID();
			
			$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
			$params_crmentityrel = array($source_id, 'Jobexp', $jobexpencereportcfid, 'Jobexpencereport');
			$adb->pquery($crmentityrel_insert_query, $params_crmentityrel);
			
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
