<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JER_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		
		//echo "<pre>";
		$cf_1451 = $request->get('cf_1451');
		$cf_1433 = $request->get('cf_1433');
		$cf_1435 = $request->get('cf_1435');
		$cf_1176 = $request->get('cf_1176');
		$cf_1154 = $request->get('cf_1154');
		$cf_1156 = $request->get('cf_1156');
		$cf_1158 = $request->get('cf_1158');
		$cf_1160 = $request->get('cf_1160');
		$cf_1443 = $request->get('cf_1443');
		$cf_1162 = $request->get('cf_1162');
		$cf_1164 = $request->get('cf_1164');
		$cf_1166 = $request->get('cf_1166');
		$cf_1168 = $request->get('cf_1168');
		
		//print_r($cf_1451);
		$vendor = 0;
		$customer = 0;
		foreach($cf_1451 as $key => $value)
		{	
					
			$request->set('cf_1451', $cf_1451[$key]);
			$request->set('cf_1433', $cf_1433[$key]);
			$request->set('cf_1435', $cf_1435[$key]);
			$request->set('cf_1176', $cf_1176[$vendor]);
			$request->set('cf_1154', $cf_1154[$key]);
			$request->set('cf_1156', $cf_1156[$key]);
			$request->set('cf_1158', $cf_1158[$key]);
			$request->set('cf_1160', $cf_1160[$key]);
			$request->set('cf_1443', $cf_1443[$customer]);
			$request->set('cf_1162', $cf_1162[$key]);
			$request->set('cf_1164', $cf_1164[$key]);
			$request->set('cf_1166', $cf_1166[$key]);
			$request->set('cf_1168', $cf_1168[$key]);
			
									
			$recordModel = $this->saveRecord($request);

			$fieldModelList = $recordModel->getModule()->getFields();
			$result = array();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				$recordFieldValue = $recordModel->get($fieldName);
				if(is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist') {
					$recordFieldValue = implode(' |##| ', $recordFieldValue);
				}
				$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
				if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date') { 
					$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId()); 
				}
				
				$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
			}
	
			//Handling salutation type
			if ($request->get('field') === 'firstname' && in_array($request->getModule(), array('Contacts', 'Leads'))) {
				$salutationType = $recordModel->getDisplayValue('salutationtype');
				$firstNameDetails = $result['firstname'];
				$firstNameDetails['display_value'] = $salutationType. " " .$firstNameDetails['display_value'];
				if ($salutationType != '--None--') $result['firstname'] = $firstNameDetails;
			}
			
			
			// Adding job costing data in JRER (Expense and Selling)
			$adb = PearDatabase::getInstance();
			$current_user = Users_Record_Model::getCurrentUserModel();
			$ownerId = $recordModel->get('assigned_user_id');
			$date_var = date("Y-m-d H:i:s");
			$usetime = $adb->formatDate($date_var, true);
			$job_costing_id = $recordModel->getId();
			/*
			$check_dept_office_job_jobexp =  'SELECT * from vtiger_jobexpcf as jobexpcf
											  INNER JOIN vtiger_crmentityrel as crmentityrel ON crmentityrel.relcrmid=jobexpcf.jobexpid
											  where jobexpcf.cf_1259=? AND jobexpcf.cf_1257=? AND crmentityrel.crmid=? and crmentityrel.module="Job"';
			
			$check_params = array($cf_1435[$key], $cf_1433[$key], $request->get('sourceRecord'));
			$result = $adb->pquery($check_dept_office_job_jobexp, $check_params);
			$row = $adb->fetch_array($result);	
								  
			if($adb->num_rows($result)==0)
			{
			*/	
					
			   $current_id = $adb->getUniqueId('vtiger_crmentity');
			   $source_id = $request->get('sourceRecord');
			   
				//INSERT data in JRER expense module from job costing
				$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, 'Job Costing Buy'));
				
				//INSERT data in jobexpencereport module from job costing
				$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, jerid) VALUES(?,?,?)";			
				$params_jobexpencereport= array($current_id, $source_id, $job_costing_id);			
				$adb->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
				$jobexpencereportid = $adb->getLastInsertID();
				//cf_1477 = Office
				//cf_1479 = Department
				//cf_1367 = pay_to
				//cf_1345 = vendor currency
				//cf_1222 = exchange rate
				//cf_1351 = Expected Buy (Local Currency NET)
				$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1453, cf_1477, cf_1479, cf_1367, cf_1345, cf_1222, cf_1351, cf_1457) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$params_jobexpencereportcf = array($current_id, $cf_1451[$key], $cf_1433[$key], $cf_1435[$key], $cf_1176[$vendor], $cf_1156[$key], $cf_1158[$key], $cf_1160[$key], 'Expence');
				$adb->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
				$jobexpencereportcfid = $adb->getLastInsertID();
				
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
				$adb->pquery($crmentityrel_insert_query, $params_crmentityrel);
				
				
				//INSERT data in JRER selling module from job costing
				$current_id = $adb->getUniqueId('vtiger_crmentity');
				$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, 'Job Costing Sell'));
				
				//INSERT data in jobexpencereport module from job costing
				$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, jerid) VALUES(?,?,?)";			
				$params_jobexpencereport= array($current_id, $source_id, $job_costing_id);
				$adb->pquery($jobexpencereport_insert_query, $params_jobexpencereport);
				$jobexpencereportid = $adb->getLastInsertID();
				//cf_1455 = s_job_charges_id
				//cf_1477 = Office
				//cf_1479 = Department
				//cf_1445 = s_bill_to_id
				//cf_1355 = s_invoice_date				
				//cf_1234 = s_customer_currency
				//cf_1236 = s_exchange_rate
				//cf_1242 = s_expected_sell_local_currency_net
				
				$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf (jobexpencereportid, cf_1455, cf_1477, cf_1479, cf_1445, cf_1234, cf_1236, cf_1242, cf_1457) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$params_jobexpencereportcf = array($current_id, $cf_1451[$key], $cf_1433[$key], $cf_1435[$key], $cf_1443[$customer], $cf_1164[$key], $cf_1166[$key], $cf_1168[$key], 'Selling');
				$adb->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
				$jobexpencereportcfid = $adb->getLastInsertID();
				
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
				$adb->pquery($crmentityrel_insert_query, $params_crmentityrel);
				
			//}			
			
			$vendor = $vendor + 2;
			$customer = $customer + 2;
			
			// Adding job costing data in JRER (Expense and Selling)
			$result['_recordLabel'] = $recordModel->getName();
			$result['_recordId'] = $recordModel->getId();
			
		}
		
		
		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			
			//$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			//$loadUrl = $parentRecordModel->getListUrl();
			
			$loadUrl = 'index.php?module='.$parentModuleName.'&relatedModule='.$request->get('module').
				'&view=Detail&record='.$parentRecordId.'&mode=showRelatedList';
		}
		
		//http://localhost/vt60/index.php?module=Job&relatedModule=JER&view=Detail&record=44041&mode=showRelatedList&tab_label=Costing%20Report
		
		header("Location: $loadUrl");
        
		//$response = new Vtiger_Response();
		//$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		//$response->setResult($result);
		//$response->emit();
		
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			$fieldModelList = $recordModel->getModule()->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				$fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));

				if ($fieldName === $request->get('field')) {
					$fieldValue = $request->get('value');
				}
                $fieldDataType = $fieldModel->getFieldDataType();
                if ($fieldDataType == 'time') {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		} else {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');

			$fieldModelList = $moduleModel->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if ($request->has($fieldName)) {
					$fieldValue = $request->get($fieldName, null);
				} else {
					$fieldValue = $fieldModel->getDefaultFieldValue();
				}
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time') {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
			} 
		}

		return $recordModel;
	}
}
