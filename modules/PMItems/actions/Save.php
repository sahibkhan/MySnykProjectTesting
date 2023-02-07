<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMItems_Save_Action extends Vtiger_Save_Action {

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
			global $adb;
			$pm_item_id = $request->get('record');
		
			$query_pm_item =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.module="PMRequisitions"';
			$check_params = array($pm_item_id);
			$result = $adb->pquery($query_pm_item, $check_params);
			$row = $adb->fetch_array($result);
			$pmrequisitions_id 	  = $row['crmid'];
			$sourceModule = $row['module'];	

			$pmrequisitions_info = Vtiger_Record_Model::getInstanceById($pmrequisitions_id, $sourceModule);
		
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pmrequisitions_info->get('cf_4271'));
			$file_title_currency = $reporting_currency;
			include("include/Exchangerate/exchange_rate_class.php");


			$createdtime =  $pmrequisitions_info->get('createdtime');
				$createdtime_ex = date('Y-m-d', strtotime($createdtime));
				
				$cf_4279 = $request->get('cf_4279'); //pm type
				$cf_4281 = $request->get('cf_4281'); //quantity
				$cf_4283 = $request->get('cf_4283'); //price per unit
				
				//$cf_4573 = $cf_4281 * $cf_4283;  //price per line
				$cf_4563 = $request->get('cf_4563'); //pay to currency
				$cf_4719 = $request->get('cf_4719'); //VAT Rate
				
				$quantity = $cf_4281;
				if(empty($quantity)) { $quantity = 0; }
				$request->set('cf_4281', $cf_4281);
				
				$price_per_unit = $cf_4283;
				if(empty($price_per_unit)) { $price_per_unit = 0; }
				$request->set('cf_4283', $cf_4283);
				
				$price_per_line = $quantity * $price_per_unit;
				$request->set('cf_4573', $price_per_line);
				
				$pm_vat_rate = $cf_4719;
				$request->set('cf_4719', $pm_vat_rate);
				
				$pay_to_currency_code = $cf_4563;
				$request->set('cf_4563', $cf_4563);
				
				$pay_to_currency = Vtiger_CurrencyList_UIType::getDisplayValue($pay_to_currency_code);
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
				
				
				if(!empty($price_per_line))
				{
					$pm_vat = '0.00';
					if(!empty($pm_vat_rate) && $pm_vat_rate>0)
					{
						$pm_vat_rate_cal = $pm_vat_rate/100; 
						$pm_vat          = $price_per_line * $pm_vat_rate_cal;
					}		
					$price_per_line_gross = $price_per_line + $pm_vat;
						
					
					if($file_title_currency !='USD')
					{
						$final_amount_gross = $price_per_line_gross * $cost_exchange_rate;							
						$costlocalcurrency = $price_per_line * $cost_exchange_rate;
					}else{
						$final_amount_gross = exchange_rate_convert($pay_to_currency, $file_title_currency,$price_per_line_gross, $createdtime_ex);
						$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$price_per_line,  $createdtime_ex);
					}
					$final_amount_gross  = number_format($final_amount_gross, 2, '.', '');
					$cost_local_currecny  = number_format($costlocalcurrency, 2, '.', '');
					$cost_exchange_rate  = number_format($cost_exchange_rate, 2, '.', '');
					
					$request->set('cf_4723', $final_amount_gross);
					$request->set('cf_4565', $cost_exchange_rate);
					$request->set('cf_4567', $cost_local_currecny);
					
					if(!empty($createdtime_ex))
					{
						if($file_title_currency!='USD')
						{
							$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $createdtime_ex);
						}else{
							$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $createdtime_ex);
						}
					}
					
					
					//$value_in_usd_normal = $costlocalcurrency;	
					$value_in_usd_normal = $final_amount_gross;
					if($file_title_currency!='USD')
					{
						//$value_in_usd_normal = $costlocalcurrency/$b_exchange_rate;
						$value_in_usd_normal = $final_amount_gross/$b_exchange_rate;
					}	
					
					$value_in_usd =  number_format($value_in_usd_normal,2,'.','');
					$request->set('cf_4575', $value_in_usd);
				}
				


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
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
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
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
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
}
