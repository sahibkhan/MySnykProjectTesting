<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Save_Action extends Vtiger_Save_Action {

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
		global $adb;
		$db=$adb;
		try {
		//	echo "<pre>";
		//	print_r($request);exit;
			// $procurementID = $request->get('record');
		 //  "SELECT * FROM vtiger_procurementitemscf  where  procitem_procid=$procurementID";
		 // $result = $db->pquery("SELECT * FROM vtiger_procurementitemscf  where  procitem_procid=$procurementID");
		 //
		 // for($i=0; $i<$db->num_rows($result); $i++) {
		 // 				$deletedID = $db->query_result($result, $i, 'procurementitemsid');
		 //  $adb->pquery("UPDATE `vtiger_crmentity` SET `deleted` = 1 WHERE `setype` = 'ProcurementItems' and crmid = $deletedID");
		 //
		 //
		 // }
$department = $request->get('proc_department'); 

//updated code
$account_id = $request->get('proc_supplier'); //supplier ID

$cleanAgreementNo = explode('(Expired)',$request->get('proc_agreement_no'));
$cleanedAgreementNo = trim($cleanAgreementNo[0]);
if($account_id=='' || $request->get('proc_agreement_no')=='')
{
	throw new IRRELEVANT_AGREEMENT_NO('-	This agrrement number is not relevant to the selected supplier  -');
}
if($request->get('proc_agreement_no')!='') //check if supplier agreement is relevant with the selected supplier
{
	$is_agreement_no_ok = 	$adb->pquery("SELECT vtiger_serviceagreement.name as agreement_no FROM vtiger_serviceagreement 
													INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_serviceagreementcf.serviceagreementid
													WHERE vtiger_serviceagreementcf.cf_6094='".$account_id."'  
													AND vtiger_serviceagreement.name = '".$cleanedAgreementNo."'
													AND  vtiger_crmentity.deleted = 0
													 limit 1");
	if($adb->num_rows($is_agreement_no_ok)==0){
				throw new IRRELEVANT_AGREEMENT_NO('-	This agrrement number is not relevant to the selected supplier  -');
			}
}
//updated code ends

$request->set('proc_department',$department);
$location = $request->get('proc_location');
$request->set('proc_location',$location);
$Total_local_amounts = $request->get('Total_local_currency');
$Total_usd_amounts = $request->get('Total_USD');
$updated_total_local = '';
$updated_total_usd = '';
	for($i=0;$i<count($Total_local_amounts)-1;$i++){
		$updated_total_local += $gross_local[$i];
		$updated_total_usd += $Total_usd_amounts[$i];
}
		$request->set('proc_loc_currency',$updated_total_local);
		$request->set('proc_total_amount',$updated_total_usd);
			$quantity = $request->get('qty1');
			if(count($quantity)-1<1 || count($quantity)-1==0){
				throw new ITEMS_MISSING('-	Procurement items missing. Please add items to proceed  -');
			}
			 $recordModel = $this->saveRecord($request);
			
		 	// echo "UPDATE `vtiger_crmentity` SET `deleted` = 1 WHERE `setype` = 'Procurement' and crmid = $deletedID";
			
			if(count($quantity)-1>0){
			mysqli_query($db, "LOCK TABLES vtiger_procurement WRITE;");
			mysqli_query($db, "LOCK TABLES vtiger_procurementcf WRITE;");
			$this->saveprocurementitem_record($recordModel,$request);
			mysqli_query($db, "UNLOCK TABLES;");
}

			//exit;
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
		} 
		catch(ITEMS_MISSING $e)	{
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			$requestData['view'] = 'Edit';
			$requestData['items_missing'] = true;
			
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			global $vtiger_current_version;
			$viewer = new Vtiger_Viewer();
			
			$viewer->assign('REQUEST_DATA', $requestData);
			$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
			$viewer->view('RedirectToEditView.tpl', 'Vtiger');

		}
		catch(IRRELEVANT_AGREEMENT_NO $e)	{
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			$requestData['view'] = 'Edit';
			$requestData['irrelevant_agreement_no'] = true;
			
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			global $vtiger_current_version;
			$viewer = new Vtiger_Viewer();
			
			$viewer->assign('REQUEST_DATA', $requestData);
			$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
			$viewer->view('RedirectToEditView.tpl', 'Vtiger');

		}
		catch (DuplicateException $e) {
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
	public function saveitemRecord($request,$procurementid) {
		global $adb;
		$db=$adb;

		$recordModel = $this->getRecordModelFromRequest($request);

		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}



		$recordModel->save();

		$result=$db->query("SELECT * FROM vtiger_crmentity WHERE setype='ProcurementItems' ORDER By crmid desc");
		$proid=$db->query_result($result,0,'setype');
		//echo "<pre>";
	//print_r($proid);exit;

	$sql= "Insert into vtiger_crmentityrel (crmid,module,relcrmid,relmodule)
				values ($procurementid,'Procurement',$proid,'ProcurementItems')";
				$db->query($sql);
		return $recorddata;

	// if($request->get('relationOperation')) {
	// 	$parentModuleName = $request->get('sourceModule');
	// 	if(empty($parentModuleName)){
	// 		$parentModuleName = $request->get('returnmodule');
	// 	}
	// 	$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
	// 	$parentRecordId = $request->get('sourceRecord');
	// 	if(empty($parentRecordId)){
	// 		$parentRecordId = $request->get('returnrecord');
	// 	}
	//
	// 	 $relatedModule = $recordModel->getModule();
	// 	 $relatedRecordId = $recordModel->getId();
	// 	if($relatedModule->getName() == 'Events'){
	// 		$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
	// 	}
	//
	// 	$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
	// 	$relationModel->addRelation($parentRecordId, $relatedRecordId);
	// }
	// $this->savedRecordId = $recordModel->getId();
	// return $recordModel;
	}
	public function saveRecord($request) {

		// echo "<pre>";
		// print_r($request);
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
	public function saveprocurementitem_record($procurement_table_record,$request){
		global $adb;
		$db=$adb;


		$refToken = $request->get('__vtrftk');
		$Lastprocurementdata = Vtiger_Record_Model::getInstanceById($procurement_table_record->get('id'), 'Procurement');
		
	 	$maximum_request_no = $Lastprocurementdata->get('proc_request_no');
	  	$procurementid = $procurement_table_record->get('id');
	 	 $proc_proctype = $Lastprocurementdata->get('proc_proctype');
		 
$department = $request->get('department');
$request_no='';
if($maximum_request_no){
	$currentdate = date("Y");
	$updateddate = substr($currentdate, 2);
	$reference_no_first_two_digit = substr($maximum_request_no, 0, 2);
	if($reference_no_first_two_digit==$updateddate){
	$exploded_data = explode('-',$maximum_request_no);
	$request_no = $exploded_data[0]+1;
	$request_no .='-'.$department;
}else{
$currentdate = date("Ymd");
$updateddate = substr($currentdate, 2);
$request_no = $updateddate.'001'.'-'.$department;
}
}else{
		$currentdate = date("Ymd");
		$updateddate = substr($currentdate, 2);
		$request_no = $updateddate.'-'.$department.'001';

}
$currentdate = date("ymd");
//updated code
//get proc_proctype shortcode
	$proc_proctype_data = Vtiger_Record_Model::getInstanceById($proc_proctype, 'ProcurementTypes');
	$proc_proctype_code = $proc_proctype_data->get('proctype_shortcode');
	//get location
	$location_id = $request->get('proc_location');
	$sql_location = $adb->pquery("SELECT cf_1559 FROM `vtiger_locationcf` WHERE `locationid` = '$location_id'");
	$get_location = $adb->fetch_array($sql_location);
	$location_shortcode = $get_location['cf_1559'];	 
/*Make the request_no and update*/
$today_procurements = $adb->pquery("SELECT * FROM `vtiger_procurement` where 	proc_request_no like '".$currentdate."-%'");
	$today_requests = $adb->num_rows($today_procurements);
	$request_sequence = $today_requests+1;
	if($today_requests<9)
	{
		$request_no = $currentdate.'-'.'00'.$request_sequence.'-'.$proc_proctype_code.'-'.$location_shortcode;
	}
	elseif($today_requests<99)
	{
		$request_no = $currentdate.'-'.'0'.$request_sequence.'-'.$proc_proctype_code.'-'.$location_shortcode;
	}
	else
	{
		$request_no = $currentdate.'-'.$request_sequence.'-'.$proc_proctype_code.'-'.$location_shortcode;
	}
	
//updated code end
 //echo "<pre>";
		 //print_r($request);exit;
//// update procurement reference number////
if($request->get('record')=='' || $request->get('record')<1) //only update on first save
{
	$query = $db->pquery("UPDATE vtiger_procurement set proc_request_no='$request_no' where procurementid='$procurementid'");
	$query = $db->pquery("UPDATE vtiger_crmentity set label='$request_no' where crmid='$procurementid' AND setype='Procurement'");
}
$quantity = $request->get('qty1');
$psc1 = $request->get('psc1');
$localprice = $request->get('localprice');
// $currency = $request->get('currency');
$VAT = $request->get('VAT');
$PriceVAT = $request->get('PriceVAT');
$Total_local_amount = $request->get('Total_local_currency');
$Total_USD = $request->get('Total_USD');
$expense_type = $request->get('childvales');
$local_currency = $request->get('currency');
$module = $request->get('module');
$description = $request->get('description');
$parentRecordId = $request->get('relatedRecord');
$module = $request->get('module');
$relationOperation = true;
$sourceModule = $request->get('parentModule');
$sourceRecordId = $request->get('parentRecordId');
$procurementID=$request->get('procurementitemsid');
$gross = $request->get('gross');
$gross_local = $request->get('gross_local');
$current_qty = $request->get('current_qty');
$avg_consumption = $request->get('avg_consumption');
$last_purchase_price = $request->get('last_purchase_price');
$last_qty = $request->get('last_qty');
/*Updated code starts*/
$itemids = $request->get('procurementitemsid'); //this parameter comes only in edit case with old items
$subitems_array = $request->get('childvales');

$total_subitems = count($subitems_array)-1;

/*We need to get Company currency for run time calculations*/
include("include/Exchangerate/exchange_rate_class.php");
$CompanyID = $request->get('proc_title');
$result = $adb->pquery("SELECT companyid,cf_996,cf_1459 FROM `vtiger_companycf` where companyid ='$CompanyID'");
$FileTitlecurrency_id = $adb->query_result($result, 0, 'cf_1459');
$company_currency_detail =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$FileTitlecurrency_id'");
$company_currency_code = $adb->query_result($company_currency_detail, 0, 'currency_code');

/*Comapny Currensy Ends*/

if($request->get('record')=='') //date1 for new request
{
	$date = date('Y-m-d');
}
else //date2 for update request
{
	$pm_info_detail = Vtiger_Record_Model::getInstanceById($request->get('record'), 'Procurement'); //procurement
	$createdtime =  $pm_info_detail->get('createdtime');
	$date = date('Y-m-d', strtotime($createdtime));
}

$grand_local_price = 0; $grand_converted_usd_gross = 0; $grand_total_amount_net = 0; $grand_gross_amount = 0;
for($a=0;$a<$total_subitems;$a++) 
{
	$local_price = 0; $price_vat = 0; $converted_usd_gross = 0; $total_amount_net = 0; $gross_amount = 0; $gross_local_amount = 0;	
	if(!empty($quantity[$a]) && !empty($psc1[$a])) 
	{
		$local_price = $quantity[$a]*$psc1[$a];
		$price_vat = ($VAT[$a]*$local_price)/100;
		$gross_local_amount = $local_price + $price_vat;
		$gross_amount = $local_price + $price_vat;
		$total_amount_net = $local_price;
		
		if(!isset($itemids[$a])) //this check help us to recognize new & old items in edit case
		{
			//New Reacord
			$converted_usd_gross = exchange_rate_convert('USD', $company_currency_code, $gross_local_amount, $date);
			$converted_usd_gross = number_format($converted_usd_gross, 2, '.', '');
			$newprocurementitem_request  = array( "__vtrftk"=>$refToken,
			"module"=>"ProcurementItems",
			"appName"=>"MARKETING",
			"action"=>"Save",
			"record"=>"",
			"MODE"=>"",
			// "relationOperation"=>$relationOperation,
			// "sourceModule"=>$sourceModule,
			"sourceRecord"=>$procurementid,
			"sourceModule"=>"Procurement",
			"returnmodule"=> "Procurement",
			"relationOperation"=>'true',
			"procitem_qty"=> $quantity[$a],
			"procitem_unit_price"=> $psc1[$a],
			"procitem_line_price"=> $local_price,
			"procitem_vat_unit"=> $VAT[$a],
			"procitem_vat_amount"=> $price_vat,
			"procitem_proctypeitem_id"=> $expense_type[$a],
			"procitem_currency"=> $FileTitlecurrency_id,
			"procitem_total_usd"=> $converted_usd_gross,
			"procitem_gross_finalamount"=> $total_amount_net,
			"procitem_gross_amount"=> $gross_amount,
			"procitem_gross_local"=> $gross_local_amount,
			"procitem_description"=> $description[$a],
			"procitem_current_qty"=> $current_qty[$a],
			"procitem_avg_consumption"=> $avg_consumption[$a],
			"procitem_lastpurchase_price"=> $last_purchase_price[$a],
			"procitem_lastpurchase_qty"=> $last_qty[$a],
			"procitem_procid"=> $procurementid,
			"procitem_proctype"=> $proc_proctype);
			$newprocurementitem_request = new Vtiger_Request($newprocurementitem_request ,$newprocurementitem_request);
			$saveRecordAction = new Procurement_Save_Action();
			$relatedRecordData = $saveRecordAction->saveitemRecord($newprocurementitem_request,$procurementid);
				
				//return $relatedRecordId;
		}
		else
		{
			//Existing Record;
			$converted_usd_gross = exchange_rate_convert('USD', $company_currency_code, $gross_local_amount, $date);
			$converted_usd_gross = number_format($converted_usd_gross, 2, '.', '');
			
			$db->pquery("UPDATE `vtiger_procurementitemscf` set
			procitem_qty='$quantity[$a]',
			procitem_unit_price='$psc1[$a]',
			procitem_line_price='$local_price',
			procitem_currency='$FileTitlecurrency_id',
			procitem_vat_unit='$VAT[$a]',
			procitem_vat_amount='$price_vat',
			procitem_proctypeitem_id='$expense_type[$a]',
			procitem_total_usd='$converted_usd_gross',
			procitem_gross_finalamount='$total_amount_net',
			procitem_gross_amount='$gross_amount',
			procitem_gross_local='$gross_local_amount',
			procitem_description='$description[$a]',
			procitem_current_qty='$current_qty[$a]',
			procitem_avg_consumption='$avg_consumption[$a]',
			procitem_lastpurchase_price='$last_purchase_price[$a]',
			procitem_lastpurchase_qty='$last_qty[$a]'
			WHERE procurementitemsid=$procurementID[$a]");
		}
		
		$grand_local_price = $grand_local_price+$local_price;
		$grand_converted_usd_gross = $grand_converted_usd_gross+$converted_usd_gross;
		$grand_total_amount_net = $grand_total_amount_net+$total_amount_net;
		$grand_gross_amount = $grand_gross_amount+$gross_amount;
		
	}
}

	$db->pquery("UPDATE `vtiger_procurementcf` set
			proc_loc_currency='$grand_gross_amount',
			proc_total_amount='$grand_converted_usd_gross'
			WHERE procurementid=$procurementid");

	/*Updated code ends*/
	}

}
class ITEMS_MISSING extends Exception {};
class IRRELEVANT_AGREEMENT_NO extends Exception {};
