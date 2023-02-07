<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ItemTRXMaster_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if(!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
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
		
		$recordId = $request->get('record');
		
		//To Save In House packing items against packing material reference number
		if(!empty($recordId)) {
			
		}
		else{
			$ItemTRXMaster_id = $recordModel->getId();
			$inhouse = $request->get('cf_5593');
			$glk_company_id = $request->get('cf_5595');
			$packing_material_reference_id = $request->get('cf_6064');
			$document_type = $request->get('cf_5583');
			
			$ItemTRXMaster_info_detail = Vtiger_Record_Model::getInstanceById($ItemTRXMaster_id, 'ItemTRXMaster');
			$document_number = $ItemTRXMaster_info_detail->get('cf_5585');
			$document_type = $ItemTRXMaster_info_detail->getDisplayValue('cf_5583');
			$warehouse_id = $ItemTRXMaster_info_detail->get('cf_5591');
				
			//1147797:: Received Doc Type
			//if($inhouse=='Yes' && !empty($packing_material_reference_id) && $document_type=='1147797') //After confirmation we will activate
			if($inhouse=='Yes' && !empty($packing_material_reference_id))
			{
				$pm_info_detail = Vtiger_Record_Model::getInstanceById($packing_material_reference_id, 'PMRequisitions');
				
				$pagingModel_1 = new Vtiger_Paging_Model();
				$pagingModel_1->set('page','1');
				
				$relatedModuleName_1 = 'PMItems';
				$parentRecordModel_1 = $pm_info_detail;
				$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
				$models_1 = $relationListView_1->getEntries($pagingModel_1);
				foreach($models_1 as $key => $model){
					$pm_items_id  = $model->getId();			
					$sourceModule_pmitems   = 'PMItems';	
					$pmitem_info = Vtiger_Record_Model::getInstanceById($pm_items_id, $sourceModule_pmitems);
					
					$pm_items_type_id = $pmitem_info->get('cf_4279');
					$pmtype_info_detail = Vtiger_Record_Model::getInstanceById($pm_items_type_id, 'PMType');
					$pm_type_code = $pmtype_info_detail->get('cf_4037');
					
					$pm_items_qty = $pmitem_info->get('cf_4281');
					$pm_items_price_per_unit = $pmitem_info->get('cf_4283');
					$pm_items_price_per_line = $pmitem_info->get('cf_4573');
					$pm_items_vat_rate = $pmitem_info->get('cf_4719');
					$pm_items_vat = $pmitem_info->get('cf_4721');
					$pm_items_price_per_line_gross = $pmitem_info->get('cf_4725');
					$pm_items_currency = $pmitem_info->get('cf_4563');
					$pm_items_exchange_rate = $pmitem_info->get('cf_4565');
					$pm_items_final_amount_gross = $pmitem_info->get('cf_4723');
					$pm_items_final_amount_net = $pmitem_info->get('cf_4567');
					$pm_items_total_gross_dollar = $pmitem_info->get('cf_4575');
					
					//To insert data from packing material reference number to item transaction detail section
					 $adb = PearDatabase::getInstance();
					 $new_id = $adb->getUniqueId('vtiger_crmentity');
					 $current_user = Users_Record_Model::getCurrentUserModel();
					 
					 $db = PearDatabase::getInstance();
					 $date_var = date("Y-m-d H:i:s");	
					 $db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$current_user->getId()."' ,
														   smownerid ='".$current_user->getId()."', setype = 'ItemTRXDetail', 
														   createdtime='".$date_var."',modifiedtime='".$date_var."' ");
			
					$db->pquery("INSERT INTO vtiger_itemtrxdetail SET itemtrxdetailid = '".$new_id."', name='".$pm_items_id."'");
					//packing material pm item id::$pm_items_id		
					
					$db->pquery("INSERT INTO vtiger_itemtrxdetailcf SET itemtrxdetailid = '".$new_id."',
																		cf_5609 = '".$document_type."',
																		cf_5611 = '".$document_number."', cf_5613 ='".$pm_type_code."',
																		cf_5615 = '".$warehouse_id."', cf_5621 ='".$pm_items_qty."',
																		cf_6030 = '".$pm_items_price_per_unit."', cf_6032 ='".$pm_items_price_per_line."',
																		cf_6034 = '".$pm_items_vat_rate."', cf_6036 ='".$pm_items_vat."',
																		cf_6038 = '".$pm_items_price_per_line_gross."', cf_6040 ='".$pm_items_currency."',
																		cf_6042 = '".$pm_items_exchange_rate."', cf_6044 ='".$pm_items_final_amount_gross."',
																		cf_6046 = '".$pm_items_final_amount_net."', cf_6048 ='".$pm_items_total_gross_dollar."'
																		 ");
					$db->pquery("INSERT INTO vtiger_crmentityrel SET crmid = '".$ItemTRXMaster_id."', 
															 module = 'ItemTRXMaster', relcrmid = '".$new_id."', relmodule = 'ItemTRXDetail'");													 
																		 
				}
			}
		}
		
		
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
			//document type ! = old document type
			if($modelData['cf_5601']!='POSTED' && $modelData['cf_5583']!=$request->get('cf_5583'))
			{
				//To generate new document number in case of changing document type before posting
				$DocType_Id = $request->get('cf_5583'); //Document Type
				$WH_ID = $request->get('cf_5591'); // Warehouse ID
				
				$transaction_date = $request->get('cf_5587');
				$transaction_year = date('Y', strtotime($transaction_date));
				
				$document_number = $this->getNewDocNumber($DocType_Id, $WH_ID, $transaction_year);
				
				$request->set('cf_5585', $document_number);
				$request->set('name', $document_number);
				
				$inhouse = $request->get('cf_5593');
				if($inhouse=='Yes')
				{
					$request->set('cf_5597','');
				}
				else{
					$request->set('cf_5595','');				
				}
				
				$record_id = $modelData['record_id'];
				$record_module = $modelData['record_module'];
				
				$new_document_type = Vtiger_WHDocumentTypeList_UIType::getDisplayValue($DocType_Id);
				
				$db = PearDatabase::getInstance();
				
				$sql_ItemTRXDetail = "SELECT vtiger_itemtrxdetailcf.itemtrxdetailid FROM vtiger_itemtrxdetailcf
									  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_itemtrxdetailcf.itemtrxdetailid
									  INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
									  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='ItemTRXMaster' 
							 	   	  AND vtiger_crmentityrel.relmodule='ItemTRXDetail' 
									  ";
				$params_ItemTRXDetail = array($record_id);				  
				$result_ItemTRXDetail = $db->pquery($sql_ItemTRXDetail, $params_ItemTRXDetail);
				$numRows_ItemTRXDetail = $db->num_rows($result_ItemTRXDetail);
				for($jj=0; $jj< $db->num_rows($result_ItemTRXDetail); $jj++ ) {
					$row_ItemTRXDetail = $db->fetch_row($result_ItemTRXDetail,$jj);
					$itemtrxdetailid = $row_ItemTRXDetail['itemtrxdetailid'];
					$db_detail = PearDatabase::getInstance();
					//To update document type and number in item transaction detail record
					$sql_update_itemtrxdetail = "UPDATE vtiger_itemtrxdetailcf set WHERE cf_5609='".$new_document_type."', cf_5611='".$document_number."'
												 WHERE itemtrxdetailid=?";
					$update_params_ItemTRXDetail = array($itemtrxdetailid);
					$result_update_ItemTRXDetail = $db_detail->pquery($sql_update_itemtrxdetail, $update_params_ItemTRXDetail);						 				
					
				}
				
			}
			else{
			//Document must be same if transaction already posted
			$recordModel->set('cf_5583',$modelData['cf_5583']); //Document Type
			}
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} 
		else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
			$DocType_Id = $request->get('cf_5583'); //Document Type
			$WH_ID = $request->get('cf_5591'); // Warehouse ID
			$transaction_date = $request->get('cf_5587');
			$transaction_year = date('Y', strtotime($transaction_date));
			$document_number = $this->getNewDocNumber($DocType_Id, $WH_ID, $transaction_year);
			$request->set('cf_5585', $document_number);
			$request->set('name', $document_number);
			
			$inhouse = $request->get('cf_5593');
			if($inhouse=='Yes')
			{
				$request->set('cf_5597','');
			}
			else{
				$request->set('cf_5595','');				
			}
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
	
	// Getting New doc number 
	public function getNewDocNumber($DocType_Id, $WH_ID, $transaction_year='2019')
	{
		$db = PearDatabase::getInstance();
		/*
		SELECT * FROM `vtiger_whdocsequencecf` 
		INNER JOIN vtiger_crmentityrel ON vtiger_whdocsequencecf.whdocsequenceid = vtiger_crmentityrel.relcrmid 
		where vtiger_crmentityrel.crmid=1147818 and vtiger_whdocsequencecf.cf_5670=1137684
		*/
		
		$sql_type =  'SELECT * FROM `vtiger_whdoctypecf` WHERE whdoctypeid=? ';
		$rs_type = $db->pquery($sql_type, array($DocType_Id));
		$row_type = $db->fetch_array($rs_type);
		
		
		$sql = 'SELECT * FROM `vtiger_whdocsequencecf` 
				INNER JOIN vtiger_crmentityrel ON vtiger_whdocsequencecf.whdocsequenceid = vtiger_crmentityrel.relcrmid 
				WHERE vtiger_crmentityrel.crmid=? and vtiger_whdocsequencecf.cf_5670=? AND vtiger_whdocsequencecf.cf_5688=?';
		$rs = $db->pquery($sql, array($DocType_Id, $WH_ID, $transaction_year));
		$tr = $db->num_rows($rs);
		if($tr==0)
		{
			 $adb = PearDatabase::getInstance();
			 $new_id = $adb->getUniqueId('vtiger_crmentity');
			 $current_user = Users_Record_Model::getCurrentUserModel();
			 
			 $whdoctypeid = $row_type['whdoctypeid'];
			 $doc_type_last_number = $row_type['cf_5537'];
			 $date_var = date("Y-m-d H:i:s");	
			 $db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$current_user->getId()."' ,
														   smownerid ='".$current_user->getId()."', setype = 'WHDocSequence', 
														   createdtime='".$date_var."',modifiedtime='".$date_var."' ");
			
			$db->pquery("INSERT INTO vtiger_whdocsequence SET whdocsequenceid = '".$new_id."'");		
			
			$db->pquery("INSERT INTO vtiger_whdocsequencecf SET whdocsequenceid = '".$new_id."',
																cf_5670 = '".$WH_ID."',
																cf_5672 = '".$doc_type_last_number."', cf_5688 ='".$transaction_year."' ");
		
			$db->pquery("INSERT INTO vtiger_crmentityrel SET crmid = '".$whdoctypeid."', 
															 module = 'WHDocType', relcrmid = '".$new_id."', relmodule = 'WHDocSequence'");
		}
		
		$sql = 'SELECT * FROM `vtiger_whdocsequencecf` 
				INNER JOIN vtiger_crmentityrel ON vtiger_whdocsequencecf.whdocsequenceid = vtiger_crmentityrel.relcrmid 
				WHERE vtiger_crmentityrel.crmid=? and vtiger_whdocsequencecf.cf_5670=? AND vtiger_whdocsequencecf.cf_5688=?';
		$rs = $db->pquery($sql, array($DocType_Id, $WH_ID, $transaction_year));
		$tr = $db->num_rows($rs);
		$row = $db->fetch_array($rs);
		//$document_last_no = $row['cf_5537'];
		$whdocsequenceid = $row['whdocsequenceid'];
		$document_last_no = $row['cf_5672'];
		$doc_type_code = $row_type['cf_5535'];
		$new_document_no = intval($document_last_no)+1;
		$document_no = strval($new_document_no);
		
		//$sql_update =  "update `vtiger_whdoctypecf` SET cf_5537 = {$document_no} WHERE whdoctypeid=?";
		$sql_update =  "update vtiger_whdocsequencecf SET cf_5672 = {$document_no} WHERE whdocsequenceid=?";
		//$rs_update = $db->pquery($sql_update, array($DocType_Id));
		$rs_update = $db->pquery($sql_update, array($whdocsequenceid));
		
		return $doc_type_code.'-'.$document_no.'/'.date('y', strtotime($transaction_year));
		
	}
}
