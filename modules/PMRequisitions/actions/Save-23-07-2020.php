<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMRequisitions_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$module_get = $_GET['module'];
		$record_get = $_GET['record'];
		$custom_permission_check = custom_access_rules($record_get,$module_get);
		//$record_owner = get_crmentity_details_own($record_get,'smcreatorid');
		//global $current_user;
		
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
		
		$loadUrl = 'index.php?module=PMRequisitions&relatedModule=PMItems&view=Detail&record='.$recordModel->getId().'&mode=showRelatedList&tab_label=PM%20Items';
				
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
			$db = PearDatabase::getInstance();
			sleep(5);
			$sql =  'SELECT MAX(serial_number) as max_ordering from vtiger_pmrequisitions 
					 INNER JOIN vtiger_pmrequisitionscf ON vtiger_pmrequisitionscf.pmrequisitionsid = vtiger_pmrequisitions.pmrequisitionsid
					 where vtiger_pmrequisitions.year_no=? AND vtiger_pmrequisitionscf.cf_4271=?';
				 
			$value = date('Y');
			$params = array($value, $request->get('cf_4271'));
			$result = $db->pquery($sql, $params);
			$row = $db->fetch_array($result);
			if($db->num_rows($result)==0 or !$row)
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
			$serial_number = $ordering+1;
			$db->pquery('update vtiger_pmrequisitions set year_no=?, serial_number = ? where pmrequisitionsid=?', array( date('Y'), $serial_number, $recordModel->getId() ) );	
			
			$location_of_branch = Vtiger_LocationList_UIType::getDisplayValue($request->get('cf_4273'));
			
			//$db = PearDatabase::getInstance();
			$result2 = $db->pquery('SELECT companyid, cf_996 FROM vtiger_companycf WHERE companyid=?',array($request->get('cf_4271')));
			$row_company = $db->fetch_array($result2);
			
			$ref_no = strtoupper($row_company['cf_996']).'-'.strtoupper($location_of_branch).'-'.str_pad($serial_number, 3, "0", STR_PAD_LEFT).'/'.date('y');
			$db->pquery('update vtiger_pmrequisitions set name = ? where pmrequisitionsid=?', array($ref_no, $recordModel->getId()));
			$db->pquery('update vtiger_pmrequisitionscf set cf_4593=?, cf_4717 = ? where pmrequisitionsid=?', array('In Progress',$ref_no, $recordModel->getId()));
			$db->pquery('update vtiger_crmentity set label = ? where crmid=?', array($ref_no, $recordModel->getId()));
			
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
