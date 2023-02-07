<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Companydata_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {

		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView', 'record_parameter' => 'record');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		// parent::checkPermission($request);
		// $recordIds = $this->getRecordIds($request);
		// foreach ($recordIds as $key => $recordId) {
		// 	$moduleName = getSalesEntityType($recordId);
		// 	$permissionStatus  = Users_Privileges_Model::isPermitted($moduleName,  'EditView', $recordId);
		// 	if($permissionStatus){
		// 		$this->transferRecordIds[] = $recordId;
		// 	}
		// 	if(empty($this->transferRecordIds)){
		// 		throw new AppException(vtranslate('LBL_RECORD_PERMISSION_DENIED'));
		// 	}
		// }
		return true;
	}

	public function process(Vtiger_Request $request) {
		global $adb;
		$page = $request->get('page');
if($page == 'company'){
	$currentUser = Users_Record_Model::getCurrentUserModel();
	$currentUser_CompanyID = $currentUser->get('company_id');
	$result = $adb->pquery("SELECT companyid,cf_996,cf_1459 FROM `vtiger_companycf`");
	$company = array();
  if($adb->num_rows($result)>0){
				for($k=0; $k<$adb->num_rows($result); $k++) {
				$company[$k]['companyname'] = $adb->query_result($result, $k, 'cf_996');
				$company[$k]['companyid'] = $adb->query_result($result, $k, 'companyid');
				$company[$k]['currency_id'] = $adb->query_result($result, $k, 'cf_1459');
			}
				?>
				<select  class="select2 attribute" id="procurement_title" name="proc_title" style="width:49.5%">
					<option>Select Option</option>
					<?php
				 foreach($company as $companyvalue){
					?>

						<option value="<?php echo lcfirst($companyvalue['companyid']);?>" <?php if($currentUser_CompanyID == $companyvalue['companyid']){?>selected <?php } ?>><?php echo $companyvalue['companyname'];?></option>

					<?php
				 }
				?>
					</select>

				<?php


}/////// result if //////
}//// main if close///

if($page == 'Edit_page'){////
	$currentUser = Users_Record_Model::getCurrentUserModel();
	 $currentUser_CompanyID = $currentUser->get('company_id');
	$result = $adb->pquery("SELECT companyid,cf_996,cf_1459 FROM `vtiger_companycf`");
	$company = array();
	if($adb->num_rows($result)>0){
				for($k=0; $k<$adb->num_rows($result); $k++) {
				$company[$k]['companyname'] = $adb->query_result($result, $k, 'cf_996');
				$company[$k]['companyid'] = $adb->query_result($result, $k, 'companyid');
				$company[$k]['currency_id'] = $adb->query_result($result, $k, 'cf_1459');
			}
				?>
				<select  class="select2 attribute" id="procurement_title" name="proc_title" style="width:49.5%">
					<option>Select Option</option>
					<?php
				 foreach($company as $companyvalue){
					?>

						<option value="<?php echo lcfirst($companyvalue['companyid']);?>" <?php if($currentUser_CompanyID == $companyvalue['companyid']){?>selected <?php } ?>><?php echo $companyvalue['companyname'];?></option>

					<?php
				 }
				?>
					</select>

				<?php


}/////// result if //////
}

if($page == 'delete'){/////

	 $deletedID = $request->get('deletedID');
	// echo "UPDATE `vtiger_crmentity` SET `deleted` = 1 WHERE `setype` = 'Procurement' and crmid = $deletedID";
$query_rate =  $adb->pquery("UPDATE `vtiger_crmentity` SET `deleted` = 1 WHERE `setype` = 'ProcurementItems' and crmid = $deletedID");
}
if($page == 'currencyfunction'){/////

include("include/Exchangerate/exchange_rate_class.php");
 $gross_local = $request->get('gross_local');
 $total_local_currency = $request->get('total_local_currency');
 $currency = $request->get('currency');
$pmtitle = $request->get('pmtitle');
$previous_currency = $request->get('previous_currency');
$result = $adb->pquery("SELECT companyid,cf_996,cf_1459 FROM `vtiger_companycf` where companyid ='$pmtitle'");
$PMcurrency_id = $adb->query_result($result, 0, 'cf_1459');
$paid_currency =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$PMcurrency_id'");
 $paid_currency_code = $adb->query_result($paid_currency, 0, 'currency_code');
//"SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$currency";
$query = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$currency");

 $currency_code = $adb->query_result($query, 0, 'currency_code');
$query2 = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$previous_currency");

 $previous_currency_code = $adb->query_result($query2, 0, 'currency_code');
$local_currency_code = $request->get('local_currency_code');
if($request->get('rec_id')=='')
{
	$date = date('Y-m-d');
}
else
{
	$pm_info_detail = Vtiger_Record_Model::getInstanceById($request->get('rec_id'), 'Procurement'); //procurement
	$createdtime =  $pm_info_detail->get('createdtime');
	$date = date('Y-m-d', strtotime($createdtime));
}
$converted_gross = $this->currencyConvert($paid_currency_code, $currency_code, $gross_local, $date);
$converted_local_net = $this->currencyConvert($paid_currency_code, $currency_code, $total_local_currency, $date);
//$converted_usd_net = $this->currencyConvert('USD', $currency_code, $total_local_currency, $date);
$converted_gross = number_format($converted_gross, 2, '.', '');
$converted_local_net = number_format($converted_local_net, 2, '.', '');
//$converted_usd_net = number_format($converted_usd_net, 2, '.', '');

$converted_usd_gross = $this->currencyConvert('USD', $currency_code, $gross_local, $date);
$converted_usd_gross = number_format($converted_usd_gross, 2, '.', '');
//echo $converted_gross.','.$converted_local_net.','.$converted_usd_net;
echo $converted_gross.','.$converted_local_net.','.$converted_usd_gross;
}/// function close////

if($page == 'get_request_number'){ //for transaction (ItemTRXMaster) module
	
	$transaction_id = $request->get('record_id');
	$selected_company = $request->get('selected_company');
	$existing_request_no = 0;
	if($transaction_id!='' && $transaction_id>0)
	{ //get existing request number from transaction-record
		$transaction_data = Vtiger_Record_Model::getInstanceById($transaction_id, 'ItemTRXMaster');
		$existing_request_no = $transaction_data->getDisplayValue('cf_6064');
		//echo $existing_request_no;exit;
	}
	$where = '';

    $where = " AND vtiger_procurementtypescf.proctype_shortcode ='PM' AND (vtiger_procurementcf.proc_purchase_type_pm = 'Own-Stock' OR  vtiger_procurementcf.proc_purchase_type_pm = 'Own Stock') 
			   AND (vtiger_procurementcf.proc_order_status='Approved' OR vtiger_procurementcf.proc_order_status='Paid') AND vtiger_procurementcf.proc_title = '$selected_company' ";

    $current_user = Users_Record_Model::getCurrentUserModel();
    $user_location_id = $current_user->get('location_id');
    
	$where .= " AND vtiger_procurementcf.proc_location='".$user_location_id."' ";

    $pm_request_query = "SELECT vtiger_procurement.procurementid, vtiger_procurement.proc_proctype,  vtiger_procurement.proc_request_no as request_no FROM vtiger_procurement
				   INNER JOIN vtiger_procurementtypescf ON  vtiger_procurementtypescf.procurementtypesid = vtiger_procurement.proc_proctype
				   INNER JOIN vtiger_crmentity ON  vtiger_crmentity.crmid = vtiger_procurement.procurementid
				   INNER JOIN vtiger_procurementcf ON vtiger_procurementcf.procurementid = vtiger_procurement.procurementid
				   WHERE vtiger_crmentity.deleted = 0
					".$where."
				   ORDER BY vtiger_procurement.procurementid DESC";

    $result = $adb->pquery($pm_request_query, array());
    $num_rows = $adb->num_rows($result);
	
	$option  = '<option value="">--Select Procurement Request--</option>';

    for($i = 0; $i<$num_rows; $i++) {
	   $option .= '<option value="'.$adb->query_result($result, $i, 'procurementid').'" '.(($existing_request_no == $adb->query_result($result, $i, 'request_no'))?"selected":"").' >'.$adb->query_result($result, $i, 'request_no').'</option>';
    }
	
echo $option;exit;

}//// main if close///

	}
public function currencyConvert($toCurrencyCode, $fromCurrencyCode, $actualAmount, $invoiceDate){
	//file_title_currency = clientCurrency
	//b_vendor_currency_code = currency of theCost
	// b_invoice_date_format = paymentDate
	// $b_buy_vendor_currency_net = theCost
	// echo $toCurrencyCode;
	// 	echo $fromCurrencyCode;
	// 		echo $actualAmount;
			//	echo $invoiceDate = date('Y-d-m', strtotime($invoiceDate));
	$costInClientCurrency = '';
	$payment_date_formated = date('Y-m-d', strtotime($invoiceDate));
	$toCurrency = $toCurrencyCode;
	$fromCurrency = $fromCurrencyCode;
	$providedCostValue = $actualAmount;
	$convertedCostValue = '';
	$exchange_rate = '';
//print_r($payment_date_formated);
// get echange rate on the basis of provided data
	if($toCurrency =='KZT')
	{
		$exchange_rate  = exchange_rate_currency($payment_date_formated, $fromCurrency);
	}
	elseif($toCurrency =='USD')
	{
		$exchange_rate = currency_rate_convert($fromCurrency, $toCurrency, 1, $payment_date_formated);
	}
	else{
		$exchange_rate = currency_rate_convert_others($fromCurrency, $toCurrency, 1, $payment_date_formated);
	}

// calculate currency on the basis of echange rate
	if($toCurrency !='USD')
	{
		$convertedCostValue = $providedCostValue * $exchange_rate;
	}else{
		$convertedCostValue = exchange_rate_convert($fromCurrency, $toCurrency, $providedCostValue, $payment_date_formated);
	}

	// echo 'Provided: '.$providedCostValue.' '.$fromCurrency.'</br>';
	// echo 'Converted: '.$convertedCostValue.' '.$toCurrency.'</br>';
	// echo 'Date: '.$payment_date_formated.'</br>';
	// echo 'Exchange Rate: '.$exchange_rate.'</br></br>';

	// convert decimal number limit .xxx
	$convertedCostValue = number_format($convertedCostValue, 3, '.', '');
	//print_r($convertedCostValue);
	return $convertedCostValue;
}


}
