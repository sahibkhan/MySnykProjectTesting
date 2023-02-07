<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMRItem_Save_Action extends Vtiger_Save_Action {

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

            $parent = $parentRecordId;
            if ($parent) {
                $sql = mysql_query("SELECT cf.cf_2373 from vtiger_pmritemcf cf
                    inner join vtiger_crmentityrel r on r.crmid = ".$parent." 
                    inner join vtiger_crmentity e on e.crmid = r.relcrmid and e.deleted = 0
                    where cf.pmritemid = r.relcrmid limit 1");
                if (empty($sql) === false) {
                    $row = mysql_fetch_assoc($sql);
                    $currency = $row['cf_2373'];
                    $sql = mysql_query("update vtiger_pmrlistcf set cf_2359 = ".$currency." where pmrlistid = ".$parent);
                }
                $sql = mysql_query("SELECT sum(cf.cf_2379) as prelSum, sum(cf.cf_2381) as actualSum from vtiger_pmritemcf cf
                    inner join vtiger_crmentityrel r on r.crmid = ".$parent." 
                    inner join vtiger_crmentity e on e.crmid = r.relcrmid and e.deleted = 0
                    where cf.pmritemid = r.relcrmid");
                if (empty($sql) === false) {
                    $row = mysql_fetch_assoc($sql);
                    $prelSum = $row['prelSum'];
                    if (is_null($prelSum)) { $prelSum = 0; }
                    $actualSum = $row['actualSum'];
                    if (is_null($actualSum)) { $actualSum = 0; }
                    $sql = mysql_query("update vtiger_pmrlistcf set cf_2355 = ".$prelSum.", cf_2357 = ".$actualSum." where pmrlistid = ".$parent);
                } else {
                    $sql = mysql_query("update vtiger_pmrlistcf set cf_2355 = 0, cf_2357 = 0 where pmrlistid = ".$parent);
                }
            }
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
