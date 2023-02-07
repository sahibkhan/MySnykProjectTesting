<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class ItemTRXDetail_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		//global $current_user;		
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record))) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {
		$recordModel = $this->saveRecord($request);
		
		//For batch item entries
		$item_code = $recordModel->get('cf_5613'); 
		
		//Checking batch item and inserting locationa and quantity
		$sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
						   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
						   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
						   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='{$item_code}'";
						   
		$rs_item = mysql_query($sql_item_query);
		$row_item = mysql_fetch_assoc($rs_item);
		
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
