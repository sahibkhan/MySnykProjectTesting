<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');

class Item_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if(!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
	
		public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$item = $request->get('name');
		$quan = $request->get('cf_3827');
		$recordId = $request->get('record');
			
		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
		$_FILES = $result['cf_3835'];
		
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


				
				
				/*$dictionaryRecordModel = Vtiger_Record_Model::getInstanceById($item, 'Dictionary');
				$modelData = $dictionaryRecordModel->getData();
				$KGs = $modelData['cf_3837']; //cf_3837	KGs 
				$LBs = $modelData['cf_3839']; //cf_3839	LBs
				$CBM = $modelData['cf_3841']; //cf_3841	CBM
				$CFT = $modelData['cf_3843']; //cf_3843	CFT
									
				$cf_3897	= ($KGs * $quan);     //cf_3897	Estimated Total Weight KGS
				$cf_3899	= ($LBs * $quan);    //cf_3899	Estimated Total Weight LBS
				$cf_3893 = ($CBM * $quan); 	//cf_3893	Estimated Total Volume CBM
				$cf_3895	= ($CFT * $quan);  //cf_3895	Estimated Total Volume CFT*/
				
				$db->pquery('UPDATE vtiger_surveycf SET cf_3897 = ?, cf_3899 = ?, cf_3893 = ?, cf_3895 = ?, cf_3737 = ?, cf_3739 = ? 		                             WHERE surveyid = ?', array($totalKGs, $totalLBs, $totalCBM, $totalCFT, $cf_3737EstimatedChargeableWeightKGS, $cf_3739EstimatedChargeableWeightLBS, $parentRecordId));
				$loadUrl = $recordModel->getDetailViewUrl();
				header("Location: $loadUrl");
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
				$loadUrl = $recordModel->getDetailViewUrl();
				header("Location: $loadUrl");
				
				
			}
			
		} // End Else
		
	} // Function End

//Aisha



}
