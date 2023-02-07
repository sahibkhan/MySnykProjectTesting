<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Insurance_Edit_View extends Vtiger_Edit_View {
    protected $record = false;
	function __construct() {
		
		parent::__construct();
		
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		 $record = $request->get('record');

		$recordPermission = Users_Privileges_Model::isPermitted($moduleName, 'EditView', $record);

		//if(!$recordPermission) {
		//	throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		//}
	}

	public function process(Vtiger_Request $request) {
		
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
        if(!empty($record) && $request->get('isDuplicate') == true) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $viewer->assign('MODE', '');
        }else if(!empty($record)) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			
            $viewer->assign('RECORD_ID', $record);
            $viewer->assign('MODE', 'edit');
			
        } else {
			
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $viewer->assign('MODE', '');
			
			$db = PearDatabase::getInstance();
			$result = $db->pquery('SELECT * FROM vtiger_exchangerate rate
							   INNER JOIN vtiger_exchangeratecf cf
							   ON rate.exchangerateid = cf.exchangerateid
							   where cf.cf_1108 =? and cf.cf_1106=?',array(date('Y-m-d'), 'USD'));
			if($db->num_rows($result)) {
				$currency_rate =  $db->query_result($result, 0, 'name');
				$request->set('cf_2311', $currency_rate);
			}
			if(empty($record))
			{
				$isRelationOperation = $request->get('relationOperation');
				if($isRelationOperation) {
				$sourceRecord = $request->get('sourceRecord');
				$sourceModule = $request->get('sourceModule');
				$job_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
				
				$request->set('name', $job_info->get('cf_1198')); //Job ref no
				$request->set('cf_2251', $job_info->get('cf_1504'));//From Country
				$request->set('cf_2255', $job_info->get('cf_1506'));//To Country
				$request->set('cf_2253', $job_info->get('cf_1508')); //From City
				$request->set('cf_2257', $job_info->get('cf_1510')); //To City
				$expected_from_date = $job_info->get('cf_1516');
				if(!empty($expected_from_date))
				{
					$request->set('cf_2263', date('d-m-Y', strtotime($job_info->get('cf_1516')))); //Expected from date
				}
				$expected_to_date = $job_info->get('cf_1583');
				if(!empty($expected_to_date))
				{
					$request->set('cf_2265', date('d-m-Y', strtotime($job_info->get('cf_1583')))); //Expected to date
				}
				
				$request->set('cf_2267', $job_info->get('cf_1547')); //Cargo desc
				
				$pcs = $job_info->get('cf_1429'). 'pcs';
				$weight = $job_info->get('cf_1084').' '.$job_info->get('cf_1520');
				$volume = $job_info->get('cf_1086').' '.$job_info->get('cf_1522');
				$full_details = array('pcs' => $pcs, 'weight' => $weight, 'volume' => $volume);
				$request->set('cf_2269', implode(', ', $full_details)); //full details of package
				
				//$request->set('cf_2275', $job_info->get('cf_1518')); //commodity				
				$request->set('cf_2277', $job_info->get('cf_1711')); //Mode
				
				//Beneficiary
				$request->set('cf_2239', $job_info->get('cf_1441')); 
				$account_id = $job_info->get('cf_1441');
					if(!empty($account_id))
					{
					$account_module = 'Accounts';
					$accounts_info = Vtiger_Record_Model::getInstanceById($account_id, $account_module);
					$request->set('cf_2243',$accounts_info->get('bill_country'));
					/*
					$bill_street = $accounts_info->get('bill_street');
					$bill_pobox = $accounts_info->get('bill_pobox');
					$bill_code = $accounts_info->get('bill_code');
					$beneficiary_detail = array($bill_street, $bill_pobox, $bill_code);
					$request->set('cf_2245', implode(',',$beneficiary_detail));		
					*/
					}
				}
			}
			
        }
		
        if(!$this->record){
            $this->record = $recordModel;
        }
        
		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach($requestFieldList as $fieldName=>$fieldValue){
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) { 
				$specialField = true; 
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i"); 
                
			}
            
            if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) { 
                $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
                $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
                list($startDate, $startTime) = explode(' ', $startDateTime);
                $fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
            }
			if($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->view('EditView.tpl', $moduleName);
	}
}