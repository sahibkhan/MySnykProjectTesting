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

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('source_module');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'source_module';
		}
		$record = $request->get('record');
		$recordId = $request->get('id');
		if (!$record) {
			$recordParameter = '';
		}else{
			$recordParameter = 'record';
		}
		$actionName = ($record || $recordId) ? 'EditView' : 'CreateView';
        $permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'DetailView', 'record_parameter' => $recordParameter);
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => $actionName, 'record_parameter' => $recordParameter);
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		try {
			$recordModel = $this->saveRecord($request);
			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		global $adb;
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
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
				include("include/Exchangerate/exchange_rate_class.php");
				$pm_info_detail = Vtiger_Record_Model::getInstanceById($packing_material_reference_id, 'PMRequisitions');
				
				//Both possibility from $ItemTRXMaster_info_detail or $pm_info_detail to get globalink company currency
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pm_info_detail->get('cf_4271'));
				$file_title_currency = $reporting_currency;
				
				$createdtime =  $pm_info_detail->get('CreatedTime');
				$createdtime_ex = date('Y-m-d', strtotime($createdtime));
				
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
					
					$rs = $adb->pquery("SELECT SUM(vtiger_itemtrxdetailcf.cf_5621) as pm_items_qty FROM vtiger_itemtrxdetailcf 
									   INNER JOIN vtiger_itemtrxdetail ON vtiger_itemtrxdetail.itemtrxdetailid =  vtiger_itemtrxdetailcf.itemtrxdetailid 
									   WHERE vtiger_itemtrxdetail.name='".$pm_items_id."' AND vtiger_itemtrxdetailcf.cf_5613='".$pm_type_code."' ");
					$row_ItemTRXDetail_old = $adb->fetch_array($rs);
					$pm_items_qty = $pmitem_info->get('cf_4281');
					if($adb->num_rows($rs)>0)
					{
						$pm_items_qty = $pmitem_info->get('cf_4281') - $row_ItemTRXDetail_old['pm_items_qty'];
					}					
					$pm_items_price_per_unit = $pmitem_info->get('cf_4283');
					//$pm_items_price_per_line = $pmitem_info->get('cf_4573');
					$pm_items_price_per_line = $pm_items_qty * $pm_items_price_per_unit;
					$pm_items_vat_rate = $pmitem_info->get('cf_4719');
					//$pm_items_vat = $pmitem_info->get('cf_4721');
					//$pm_items_price_per_line_gross = $pmitem_info->get('cf_4725');
					$pm_items_vat = '0.00';
					if(!empty($pm_items_vat_rate) && $pm_items_vat_rate>0)
					{
						$pm_vat_rate_cal = $pm_items_vat_rate/100; 
						$pm_items_vat    = $pm_items_price_per_line * $pm_vat_rate_cal;
					}		
					$pm_items_price_per_line_gross = $pm_items_price_per_line + $pm_items_vat;				
					$pm_items_currency = $pmitem_info->get('cf_4563');
					$pay_to_currency_code = $pm_items_currency;
					$pay_to_currency = Vtiger_CurrencyList_UIType::getDisplayValue($pay_to_currency_code);
					//$pm_items_exchange_rate = $pmitem_info->get('cf_4565');
					if($file_title_currency =='KZT')
					{	
						$cost_exchange_rate   = exchange_rate_currency($createdtime_ex, $pay_to_currency);			
					}
					elseif($file_title_currency =='USD')
					{
						$cost_exchange_rate = currency_rate_convert($pay_to_currency, $file_title_currency, 1, $createdtime_ex);
					}
					else{
						$cost_exchange_rate = currency_rate_convert_others($pay_to_currency, $file_title_currency, 1, $createdtime_ex);
					}
					$pm_items_exchange_rate = $cost_exchange_rate;	
					
					//$pm_items_final_amount_gross = $pmitem_info->get('cf_4723');
					//$pm_items_final_amount_net = $pmitem_info->get('cf_4567');
					//$pm_items_total_gross_dollar = $pmitem_info->get('cf_4575');
					if($file_title_currency !='USD')
					{
						$final_amount_gross = $pm_items_price_per_line_gross * $cost_exchange_rate;										
						$costlocalcurrency = $pm_items_price_per_line * $cost_exchange_rate;
					}else{
						$final_amount_gross = exchange_rate_convert($pay_to_currency, $file_title_currency,$pm_items_price_per_line_gross, $createdtime_ex);
						$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$pm_items_price_per_line,  $createdtime_ex);
					}
					$pm_items_final_amount_gross = $final_amount_gross;
					$pm_items_final_amount_net = $costlocalcurrency;
					
					if(!empty($createdtime_ex))
					{
						if($file_title_currency!='USD')
						{
							$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $createdtime_ex);
						}else{
							$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $createdtime_ex);
						}
					}
					$value_in_usd_normal = $final_amount_gross;
					if($file_title_currency!='USD')
					{
						//$value_in_usd_normal = $costlocalcurrency/$b_exchange_rate;
						$value_in_usd_normal = $final_amount_gross/$b_exchange_rate;
					}
					$pm_items_total_gross_dollar = $value_in_usd_normal;
					
					
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
			if(empty($parentModuleName)){
				$parentModuleName = $request->get('returnmodule');
			}
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			if(empty($parentRecordId)){
				$parentRecordId = $request->get('returnrecord');				
			}
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		$this->savedRecordId = $recordModel->getId();
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
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
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
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
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
