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
			
			$vendor = $vendor + 2;
			$customer = $customer + 2;
						
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
