<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PostTrip_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		$fieldToBeSaved = $request->get('field');
		$response = new Vtiger_Response();
		try {
			global $adb;
			//$adb->setDebug(true);
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode',false));
			//$recordModel = $this->saveRecord($request);
			$cf_4317 = $request->get('cf_4317'); //post rate
		
			$cf_4319 = $request->get('cf_4319'); //post maximum value
			$cf_4321 = $request->get('cf_4321'); //post total in tenge
			$cf_4557 = $request->get('cf_4557'); //post check
			
			if(!empty($cf_4317))
			{
				foreach($cf_4317 as $key => $value)
				{
					$post_rate = $cf_4317[$key];
					$request->set('cf_4317', $post_rate);
					
					$post_maximum_value = $cf_4319[$key];
					$request->set('cf_4319', $post_maximum_value);
					
					//$post_total_in_tenge = $cf_4321[$key];
					$post_total_in_tenge = $post_rate * $post_maximum_value;
					$request->set('cf_4321', $post_total_in_tenge);
					
					$check = $cf_4557[$key];
					$checked=0;
					if($check=='on')
					{
						$checked=1;
					}
					$request->set('cf_4557',$checked);
					
					$request->set('record',$key);
					$request->set('module','PostTrip');
							
					$adb->pquery("update vtiger_posttripcf set cf_4317='".$post_rate."', cf_4319='".$post_maximum_value."', cf_4321='".$post_total_in_tenge."',
								cf_4557='".$checked."'
								where posttripid='".$key."' ");					
							
					$recordModel = $this->saveRecord($request);

					vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);

						$fieldModelList = $recordModel->getModule()->getFields();
						$result = array();
						$picklistColorMap = array();
						foreach ($fieldModelList as $fieldName => $fieldModel) {
							if($fieldModel->isViewable()){
								$recordFieldValue = $recordModel->get($fieldName);
								if(is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist') {
									foreach ($recordFieldValue as $picklistValue) {
										$picklistColorMap[$picklistValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $picklistValue);
									}
									$recordFieldValue = implode(' |##| ', $recordFieldValue);     
								}
								if($fieldModel->getFieldDataType() == 'picklist') {
									$picklistColorMap[$recordFieldValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $recordFieldValue);
								}
								$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
								if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date' && $fieldModel->getFieldDataType() !== 'double') { 
									$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId()); 
								}
								if ($fieldModel->getFieldDataType() == 'currency') {
									$displayValue = Vtiger_Currency_UIType::transformDisplayValue($fieldValue);
								}
								if(!empty($picklistColorMap)) {
									$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue, 'colormap' => $picklistColorMap);
								} else {
									$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
								}
							}
						}

						//Handling salutation type
						if ($request->get('field') === 'firstname' && in_array($request->getModule(), array('Contacts', 'Leads'))) {
							$salutationType = $recordModel->getDisplayValue('salutationtype');
							$firstNameDetails = $result['firstname'];
							$firstNameDetails['display_value'] = $salutationType. " " .$firstNameDetails['display_value'];
							if ($salutationType != '--None--') $result['firstname'] = $firstNameDetails;
						}

						// removed decode_html to eliminate XSS vulnerability
						$result['_recordLabel'] = decode_html($recordModel->getName());
						$result['_recordId'] = $recordModel->getId();
				}
			}

			if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				
				//$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				//$loadUrl = $parentRecordModel->getListUrl();
				
				$loadUrl = 'index.php?module='.$parentModuleName.'&relatedModule='.$request->get('module').
					'&view=Detail&record='.$parentRecordId.'&mode=showRelatedList&relationId=233&tab_label=Post%20Trip%20CheckList%20Expense&app=FLEET';
			}
				
			header("Location: $loadUrl");
			
			
			//$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		//	$response->setResult($result);
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if($moduleName == 'Calendar') {
			$moduleName = $request->get('calendarModule');
		}
		$recordId = $request->get('record');

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			$fieldModelList = $recordModel->getModule()->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				//For not converting createdtime and modified time to user format
				$uiType = $fieldModel->get('uitype');
				if ($uiType == 70) {
					$fieldValue = $recordModel->get($fieldName);
				} else {
					$fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));
				}

				// To support Inline Edit in Vtiger7
				if($request->has($fieldName)){
					$fieldValue = $request->get($fieldName,null);
				}else if($fieldName === $request->get('field')){
					$fieldValue = $request->get('value');
				}
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
				if($fieldName === 'contact_id' && isRecordExists($fieldValue)) {
					$contactRecord = Vtiger_Record_Model::getInstanceById($fieldValue, 'Contacts');
					$recordModel->set("relatedContact",$contactRecord);
				}
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
                if($fieldValue){
                    $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue,$fieldModel);
                }
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time' && $fieldValue !== null) {
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
