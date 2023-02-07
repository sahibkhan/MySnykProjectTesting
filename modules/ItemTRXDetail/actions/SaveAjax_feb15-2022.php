<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ItemTRXDetail_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		
			
		$fieldToBeSaved = $request->get('field');
		$response = new Vtiger_Response();
		try {
			global $adb;
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode',false));
			$recordModel = $this->saveRecord($request);
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);
			
			$item_code = $recordModel->get('cf_5613');
			
			//Checking batch item and inserting locationa and quantity
			// $sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
			// 				   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
			// 				   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
			// 				   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='{$item_code}'";
			$rs_item = $adb->pquery("SELECT * FROM vtiger_whitemmastercf 
							   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
							   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
							   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565=? ", array($item_code));
			$row_item = $adb->fetch_array($rs_item);
							   
			// $rs_item = mysql_query($sql_item_query);
			// $row_item = mysql_fetch_assoc($rs_item);
			
			$item_tracking = $row_item['cf_5575'];
			if($item_tracking=='Batch')
			{
				$quantity = $request->get('quantity');
				$location = $request->get('location');
				foreach($location as $key => $value)
				{
					$location_id =  $location[$key];
					$quantity_value =  $quantity[$key];
					
					if($location_id!=0 && $quantity_value>0)
					{
						$adb = PearDatabase::getInstance();
						$current_user = Users_Record_Model::getCurrentUserModel();
						$ownerId = $recordModel->get('assigned_user_id');
						$date_var = date("Y-m-d H:i:s");
						$usetime = $adb->formatDate($date_var, true);
						$ItemTRXDetail_id = $recordModel->getId();
						
						$current_id = $adb->getUniqueId('vtiger_crmentity');
						$source_id = $ItemTRXDetail_id;
						
						//INSERT data in JRER expense module from job costing
						$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
							 setype, description, createdtime, modifiedtime, presence, deleted, label)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
							array($current_id, $current_user->getId(), $current_user->getId(), 'ItemTRXBDetail', 'NULL', $date_var, $date_var, 1, 0, 'Batch Item'));
						
							
						$adb_rel = PearDatabase::getInstance();
						$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
						$params_crmentityrel = array($source_id, 'ItemTRXDetail', $current_id, 'ItemTRXBDetail');
						$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
						
						
						$adb_e = PearDatabase::getInstance();
						$batchitem_insert_query = "INSERT INTO vtiger_itemtrxbdetail(itemtrxbdetailid, name) VALUES(?,?)";
						$params_batchitem= array($current_id, $source_id);	
						$adb_e->pquery($batchitem_insert_query, $params_batchitem);	
						
						$adb_bcf = PearDatabase::getInstance();
						//Batch::cf_5716 = TRX::cf_5609 = Doc Type
						//Batch::cf_5718 = TRX::cf_5611 = Doc Number
						//Batch::cf_5720 = TRX::cf_5613 = Item Code
						//Batch::cf_5722 = TRX::cf_5615 = From WH
						//Batch::cf_5724 = TRX::location_id = From Location WH
						//Batch::cf_5726 = TRX::cf_5710 = Batch ID
						//Batch::cf_5728 = TRX::quantity_value = TRX QTY
						$batchitemcf_insert_query = "INSERT INTO vtiger_itemtrxbdetailcf(itemtrxbdetailid, cf_5716, cf_5718, cf_5720, cf_5722, cf_5724, cf_5726, cf_5728) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
						$params_batchitemcf = array($current_id, $recordModel->get('cf_5609'), $recordModel->get('cf_5611'), $recordModel->get('cf_5613'), $recordModel->get('cf_5615'), $location_id, $recordModel->get('cf_5710'),$quantity_value);
						$adb_bcf->pquery($batchitemcf_insert_query, $params_batchitemcf);
					}
				}
			}

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
			$response->setEmitType(Vtiger_Response::$EMIT_JSON);
			$response->setResult($result);
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
