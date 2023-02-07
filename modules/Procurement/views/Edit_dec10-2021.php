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

Class Procurement_Edit_View extends Vtiger_Edit_View {

	public function process(Vtiger_Request $request) {
// echo "<pre>";
// print_r($request);exit;
		$db = PearDatabase::getInstance(); //connectiondatabase
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$currentUser = Users_Record_Model::getCurrentUserModel(); ///users k folder k andr model ka folder hai js mai record ki file hai


		$username = $currentUser->get('first_name').' '.$currentUser->get('last_name');///////////////sss//////
		$id = $currentUser->get('id');///////////////sss//////
		$assign_department_id = $currentUser->get('department_id');///////////////sss//////
		
	  $assign_location_id = $currentUser->get('location_id');///////////////sss//////
		$department_data = Vtiger_Record_Model::getInstanceById($assign_department_id, "Department");
		$department = $department_data->get('cf_1542');
		$location_data = Vtiger_Record_Model::getInstanceById($assign_location_id, "Location");

		$location = $location_data->get('cf_1559');
		$currency_code = $currentUser->get('currency_code');
		$currency_id = $currentUser->get('currency_id');
			include("include/Exchangerate/exchange_rate_class.php");
		//$invoice_amount_in_the_client_currency = $this->currencyConvert($client_currency_code, $invoice_currency_code, $invoice_amount, $request->get('cf_7126'));
		//print_r($currency_code);exit;
	 //zero
		//print_r($maximum_request_no);exit;
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
				$request_no = $updateddate.'001'.'-'.$department;

		}
		$result = $db->pquery("SELECT procurementtypesid,name from vtiger_procurementtypes");
			$items=array();

			for($i=0; $i<$db->num_rows($result); $i++) {
			$items[$i]['name'] = $db->query_result($result, $i, 'name');
			$items[$i]['procurementtypesid'] = $db->query_result($result, $i, 'procurementtypesid');
			}
//echo $request_no;
			////// custom query /////
		$record_id=$record;
		$childdata=array();
		$childname=array();
		$counter=0;

///// query for getting major items  this query will run in both cases add and edit///////





/// this information is about to create a table for adding expence type in procurment add and edit view //////
		if($record_id){//// parent if start////
			$requestedurl = 'Edit';
			$send_approval_result = $db->pquery("SELECT * FROM `vtiger_send_approval` where procurement_id = $record_id and status=0 order by id DESC limit 1");
			$send_approval_value_array = array();
			if($db->num_rows($send_approval_result)>0){
				for($send=0; $send<$db->num_rows($send_approval_result); $send++) {
					$send_approval_value_array[$send]['who_approve_id'] = $db->query_result($send_approval_result, $send, 'who_approve_id');
					$send_approval_value_array[$send]['approval_status'] = $db->query_result($send_approval_result, $send, 'approval_status');
				}
			}else{
			$send_approval_value_array[$send]['who_approve_id'] = 0;
			$send_approval_value_array[$send]['approval_status'] = 0;
			}

			$result = $db->pquery("SELECT id, currency_code FROM `vtiger_currency_info`");
			if($db->num_rows($result)>0){
				$currency_data = array();
				for($j=0; $j<$db->num_rows($result); $j++) {
					 $currency_data[$j]['id'] = $db->query_result($result, $j, 'id');
					  $currency_data[$j]['currency_code'] = $db->query_result($result, $j, 'currency_code');
				 }
			 }
			//////// this query will get the id of major items from procurment table//////
$Requested_item_data = Vtiger_Record_Model::getInstanceById($record_id, $moduleName);

$request_item_ID = $Requested_item_data->get('proc_proctype');

		//	print_r($Requested_item_data);exit;
if(count($Requested_item_data)>0){

$Requested_item_id = $Requested_item_data->get('proc_proctype');

//////// this query will get the child items on the basis of major item ID////////
$result = $db->pquery("SELECT * FROM `vtiger_procurementtypeitemscf` WHERE `proctypeitem_proctype`=$Requested_item_id");
if($db->num_rows($result)>0){
for($j=0; $j<$db->num_rows($result); $j++) {

 $childdata[$j]['procurementtypeitemsid'] = $db->query_result($result, $j, 'procurementtypeitemsid');
 $childvariable = $childdata[$j]['procurementtypeitemsid'];

 $result1 = $db->pquery("SELECT * FROM `vtiger_procurementtypeitems` WHERE `procurementtypeitemsid`=$childvariable");
 if($db->num_rows($result)>0){
for($k=0; $k<$db->num_rows($result1); $k++) {
 $childname[$counter]['procurementtypeitemsid'] = $db->query_result($result1, $k, 'procurementtypeitemsid');
 $childname[$counter]['childname'] = $db->query_result($result1, $k, 'name');
}
}
$counter++;
} //// inner for close

}/// inner if close
}//// main if close
$totalrecords=count($childname);

////// this query will get all the expense details on the basis of procurment ID ///////
//echo "SELECT * FROM vtiger_procurementitemscf as p INNER JOIN vtiger_crmentity as c ON p.procurementitemsid = c.crmid where c.deleted=0 and p.procitem_procid=$record_id";
$result = $db->pquery("SELECT * FROM vtiger_procurementitemscf as p INNER JOIN vtiger_crmentity as c ON p.procurementitemsid = c.crmid where c.deleted=0 and p.procitem_procid=$record_id");
$expense_details=array();
$expensetotalrecords = $db->num_rows($result);
$totalusdamount=0;
$totallocaldamount=0;
for($i=0; $i<$db->num_rows($result); $i++) {
$expense_details[$i]['procurementitemsid'] = $db->query_result($result, $i, 'procurementitemsid');
$expense_details[$i]['expence_type'] = $db->query_result($result, $i, 'procitem_proctypeitem_id');
$expense_details[$i]['description'] = $db->query_result($result, $i, 'procitem_description');
$expense_details[$i]['quantity'] = $db->query_result($result, $i, 'procitem_qty');
$expense_details[$i]['ppu'] = number_format($db->query_result($result, $i, 'procitem_unit_price'), 2, '.', '');
$expense_details[$i]['local_price'] = number_format($db->query_result($result, $i, 'procitem_line_price'), 2, '.', '');
$expense_details[$i]['vat_rate'] = number_format($db->query_result($result, $i, 'procitem_vat_unit'), 2, '.', '');
$expense_details[$i]['vat'] = number_format($db->query_result($result, $i, 'procitem_vat_amount'), 2, '.', '');
$expense_details[$i]['gross'] = number_format($db->query_result($result, $i, 'procitem_gross_amount'), 2, '.', '');
$expense_details[$i]['gross_local'] = number_format($db->query_result($result, $i, 'procitem_gross_local'), 2, '.', '');
$expense_details[$i]['local_currency_code'] = $db->query_result($result, $i, 'procitem_currency');
$total_in_usd = $db->query_result($result, $i, 'procitem_total_usd');
$expense_details[$i]['total_in_usd'] = number_format($total_in_usd, 2, '.', '');
$totalusdamount+=$db->query_result($result, $i, 'procitem_total_usd'); //usman
$expense_details[$i]['total_local_amount'] = number_format($db->query_result($result, $i, 'procitem_gross_finalamount'), 2, '.', '');
$totallocaldamount += $db->query_result($result, $i, 'procitem_gross_finalamount');
$expense_details[$i]['procurement_id'] = $db->query_result($result, $i, 'procitem_procid');
$expense_details[$i]['requested_item_id'] = $db->query_result($result, $i, 'procitem_proctype');
$expense_details[$i]['current_qty'] = $db->query_result($result, $i, 'procitem_current_qty');
$expense_details[$i]['avg_consumption'] = number_format($db->query_result($result, $i, 'procitem_avg_consumption'), 2, '.', '');
$expense_details[$i]['last_purchase_price'] = number_format($db->query_result($result, $i, 'procitem_lastpurchase_price'), 2, '.', '');
$expense_details[$i]['last_qty'] = $db->query_result($result, $i, 'procitem_lastpurchase_qty');
}
$request->set('proc_total_amount',$totalusdamount);
$request->set('proc_loc_currency',$totallocaldamount);
// echo "<pre>";
// print_r($expense_details);exit;

//
//print_r($major_items_onthe_basisof_procurment_id);exit;
//print_r($majoritems);exit;
//echo $Requested_item_id;exit;
	////$viewer->assign('listdata', $majoritems);////// this variable contains all major items/////
	$viewer->assign('requestedurl', $requestedurl);
	$viewer->assign('sending_approvals', $send_approval_value_array);
	$viewer->assign('recordid', $record_id);  ////// this variable contains single major ID/////////
	$viewer->assign('expense_details', $expense_details); ///// this variable contains all the expense details against this procurment ID
	$viewer->assign('CHILD_TABLE_RECORD', $childname); /// THIS varible contains all the child items
	$viewer->assign('totalrecords', $totalrecords);
	$viewer->assign('expensetotalrecords', $expensetotalrecords);
	$viewer->assign('ITEMDATA', $items);
	$viewer->assign('CURRENCYDATA', $currency_data);
	$viewer->assign('ITEMDATAID', $Requested_item_id);
	$request_no = $Requested_item_data->get('proc_request_no');
	$viewer->assign('proc_proctype', $Requested_item_data->get('proc_proctype'));
}else{/// parent if close////

	$viewer->assign('requestedurl', 'Add');
	$exchange_rate = $this->get_exchange_rate('USD', date('d-m-Y'));
	
	//arif code
	$current_user_companyid = $currentUser->get('company_id');
	$current_user_company_currency = $db->pquery("SELECT companyid,cf_1459 FROM `vtiger_companycf` where companyid=$current_user_companyid");
	$current_user_company_currency_id = $db->query_result($current_user_company_currency, 0, 'cf_1459'); 
	$base_currency_id = $current_user_company_currency_id;
	if($base_currency_id!=85756) //Not eq DW company, DW uses dollar currency
	{
		$get_currency_info = $db->pquery("SELECT id, currency_code FROM `vtiger_currency_info` where id='$base_currency_id' ");
		$base_currency_code = $db->query_result($get_currency_info, 0, 'currency_code');
		$exchange_rate = 1/$this->get_exchange_rate_convert('USD',$base_currency_code,1, date('Y-m-d'));
	}
	$viewer->assign('USER_COMPANY', $current_user_companyid);
	//arif code ends
	$request->set('proc_currency_usd_rate',$exchange_rate);
	$request->set('proc_company_status','In Progress');
	//$viewer->assign('listdata', $majoritems);
	$send_approval_value_array[0]['who_approve_id'] = 0;
	$viewer->assign('sending_approvals', $send_approval_value_array);
	$viewer->assign('recordid', "");
	$viewer->assign('expense_details', "");
	$viewer->assign('totalrecords', $totalrecords);
	$viewer->assign('ITEMDATA', $items);
	$request_no = '-'; //by default do not set any value for request no	
}

//print_r($items);exit;
		if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');

			//While Duplicating record, If the related record is deleted then we are removing related record info in record model
			$mandatoryFieldModels = $recordModel->getModule()->getMandatoryFieldModels();
			foreach ($mandatoryFieldModels as $fieldModel) {
				if ($fieldModel->isReferenceField()) {
					$fieldName = $fieldModel->get('name');
					if (Vtiger_Util_Helper::checkRecordExistance($recordModel->get($fieldName))) {
						$recordModel->set($fieldName, '');
					}
				}
			}
		}else if(!empty($record)) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
		}
		if(!$this->record){
			$this->record = $recordModel;
		}

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);

		$relContactId = $request->get('contact_id');
		if ($relContactId && $moduleName == 'Calendar') {
			$contactRecordModel = Vtiger_Record_Model::getInstanceById($relContactId);
			$requestFieldList['parent_id'] = $contactRecordModel->get('account_id');
		}
		foreach($requestFieldList as $fieldName=>$fieldValue){
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling
			// so a bit of special treatment is required if we come from QuickCreate
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) {
				$specialField = true;
				// Convert the incoming user-picked time to GMT time
				// which will get re-translated based on user-time zone on EditForm
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i");

			}

			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) {
				$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
				$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
				list($startDate, $startTime) = explode(' ', $startDateTime);
				$fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
			}
			if($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);////////78///////sss//////
		$viewer->assign('creator', $username);///////910////////sss//////
		$viewer->assign('departmentname', $department);/////1112//////////sss//////
		$viewer->assign('locations', $location);
		$viewer->assign('departmentID', $assign_department_id);/////1112//////////sss//////
		$viewer->assign('locationID', $assign_location_id);
		$viewer->assign('CURRENT_USERCURRENCYID', $currency_id);/////1112//////////sss//////
		$viewer->assign('CURRENCY_CODE', $currency_code);
		$viewer->assign('REQUESTNUMBER', $request_no);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('ITEMDATA', $items);
		

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		// added to set the return values
		if($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		if($request->get('displayMode')=='overlay'){
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request));
			$viewer->view('OverlayEditView.tpl', $moduleName);
		}
		else{
			$viewer->view('EditView.tpl', $moduleName);
		}
	}

	public function getOverlayHeaderScripts(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}

	public function get_exchange_rate($fromCurrencyCode, $invoiceDate){


		$payment_date_formated =  date('Y-m-d', strtotime($invoiceDate));

		$exchange_rate = '';

			$exchange_rate  = exchange_rate_currency($payment_date_formated, $fromCurrencyCode);


		return $exchange_rate;
	}
	public function get_exchange_rate_convert($from,$to,$amount, $exchange_rate_date)
	{
		return exchange_rate_convert($from,$to,$amount, $exchange_rate_date); //from exchange_rate_class
	}
}
