<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Item_SaveAjax_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$item = $request->get('name');
		$quan = $request->get('cf_3827');
		$recordId = $request->get('record');
		
		$recordModel = $this->saveRecord($request);
		// synchronize Survey items with Droid System
		synchronizeSurveyItemWithDroid($recordModel->getId());	
		if($recordId && !$request->get('relationOperation'))
		{
			$query = "SELECT * FROM vtiger_crmentityrel WHERE relcrmid = ? AND relmodule = ?";
			$params = array($request->get('record'), $moduleName);
			
			$result = $db->pquery($query, $params);
			if ($db->num_rows($result)) 
			{
				$relatedRecordId  = $db->query_result($result, 0, 'relcrmid');
				$relatedModule  = $db->query_result($result, 0, 'relmodule');
				$parentRecordId   =  $db->query_result($result, 0, 'crmid');
				$parentModuleName = $db->query_result($result, 0, 'module');
								
				$sql = "SELECT item.itemid, item.`name` as item, diction.`name`, itemcf.cf_3827 as quantity, itemcf.cf_3831 as room, dictionary.cf_3837 as kgs, dictionary.cf_3839 as lbs, dictionary.cf_3841 as cbm, dictionary.cf_3843 as cft FROM  vtiger_item item, vtiger_itemcf itemcf, vtiger_crmentityrel crmRel, vtiger_crmentity crm, vtiger_dictionarycf dictionary, vtiger_dictionary as diction WHERE crmRel.crmid = ? and crm.deleted = '0' AND item.itemid=itemcf.itemid AND crmRel.relcrmid=item.itemid AND crm.crmid=item.itemid AND item.`name`=dictionary.dictionaryid AND dictionary.dictionaryid=diction.dictionaryid";
$result = $db->pquery($sql, array($parentRecordId));

		$totalKGs = 0;
		$totalLBs = 0;
		$totalCBM = 0;
		$totalCFT = 0;
		for($i=0; $i<$db->num_rows($result); $i++) {
			
			$kgs = $db->query_result($result, $i, 'kgs');
			$lbs = $db->query_result($result, $i, 'lbs');
			$cbm = $db->query_result($result, $i, 'cbm');
			$cft = $db->query_result($result, $i, 'cft');
			$quantity = $db->query_result($result, $i, 'quantity');
			
			$cf_3897KGS	= ($kgs * $quantity);     //cf_3897	Estimated Total Weight KGS
			$cf_3899LBS	= ($lbs * $quantity);    //cf_3899	Estimated Total Weight LBS
			$cf_3893CBM = ($cbm * $quantity); 	//cf_3893	Estimated Total Volume CBM
			$cf_3895CFT	= ($cft * $quantity);  //cf_3895	Estimated Total Volume CFT
		
			$totalKGs += $cf_3897KGS;
			$totalLBs += $cf_3899LBS;
			$totalCBM += $cf_3893CBM;
			$totalCFT += $cf_3895CFT;
			
		}
				$cf_3737EstimatedChargeableWeightKGS = ($totalCBM * 167);
				$cf_3739EstimatedChargeableWeightLBS = ($cf_3737EstimatedChargeableWeightKGS * 2.2);
				$db->pquery('UPDATE vtiger_surveycf SET cf_3897 = ?, cf_3899 = ?, cf_3893 = ?, cf_3895 = ?, cf_3737 = ?, cf_3739 = ? 		                             WHERE surveyid = ?', array($totalKGs, $totalLBs, $totalCBM, $totalCFT, $cf_3737EstimatedChargeableWeightKGS, $cf_3739EstimatedChargeableWeightLBS, $parentRecordId));
				//$loadUrl = $recordModel->getDetailViewUrl();
				//header("Location: $loadUrl");
			}
		} // End Main if
		else
		{
				if($request->get('relationOperation')) 
				{				
					
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				
				$sql = "SELECT item.itemid, item.`name` as item, diction.`name`, itemcf.cf_3827 as quantity, itemcf.cf_3831 as room, dictionary.cf_3837 as kgs, dictionary.cf_3839 as lbs, dictionary.cf_3841 as cbm, dictionary.cf_3843 as cft FROM  vtiger_item item, vtiger_itemcf itemcf, vtiger_crmentityrel crmRel, vtiger_crmentity crm, vtiger_dictionarycf dictionary, vtiger_dictionary as diction WHERE crmRel.crmid = ? and crm.deleted = '0' AND item.itemid=itemcf.itemid AND crmRel.relcrmid=item.itemid AND crm.crmid=item.itemid AND item.`name`=dictionary.dictionaryid AND dictionary.dictionaryid=diction.dictionaryid";
$result = $db->pquery($sql, array($parentRecordId));

		$totalKGs = 0;
		$totalLBs = 0;
		$totalCBM = 0;
		$totalCFT = 0;
		for($i=0; $i<$db->num_rows($result); $i++) {
			
			$kgs = $db->query_result($result, $i, 'kgs');
			$lbs = $db->query_result($result, $i, 'lbs');
			$cbm = $db->query_result($result, $i, 'cbm');
			$cft = $db->query_result($result, $i, 'cft');
			$quantity = $db->query_result($result, $i, 'quantity');
			
			$cf_3897KGS	= ($kgs * $quantity);     //cf_3897	Estimated Total Weight KGS
			$cf_3899LBS	= ($lbs * $quantity);    //cf_3899	Estimated Total Weight LBS
			$cf_3893CBM = ($cbm * $quantity); 	//cf_3893	Estimated Total Volume CBM
			$cf_3895CFT	= ($cft * $quantity);  //cf_3895	Estimated Total Volume CFT
		
			$totalKGs += $cf_3897KGS;
			$totalLBs += $cf_3899LBS;
			$totalCBM += $cf_3893CBM;
			$totalCFT += $cf_3895CFT;
			
		}
				$cf_3737EstimatedChargeableWeightKGS = ($totalCBM * 167);
				$cf_3739EstimatedChargeableWeightLBS = ($cf_3737EstimatedChargeableWeightKGS * 2.2);
				$db->pquery('UPDATE vtiger_surveycf SET cf_3897 = ?, cf_3899 = ?, cf_3893 = ?, cf_3895 = ?, cf_3737 = ?, cf_3739 = ? 		                             WHERE surveyid = ?', array($totalKGs, $totalLBs, $totalCBM, $totalCFT, $cf_3737EstimatedChargeableWeightKGS, $cf_3739EstimatedChargeableWeightLBS, $parentRecordId));
				//$loadUrl = $recordModel->getDetailViewUrl();
				//header("Location: $loadUrl");
				
				
			}
			
		} // End Else
		
		
		
		
		

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

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
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
