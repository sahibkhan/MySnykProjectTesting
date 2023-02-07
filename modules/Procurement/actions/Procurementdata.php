<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Procurementdata_Action extends Vtiger_Action_Controller {

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

		include("include/Exchangerate/exchange_rate_class.php");
		$page = $request->get('page');
		if($page == 'add_currency_conversion'){
	 $total_local_currency = $request->get('search');
   $fromcurrency = $request->get('currency');
	 $date = date('Y-m-d');
	 $query = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$fromcurrency");

	 $currency_code = $adb->query_result($query, 0, 'currency_code');
	// print_r($currency_code);exit;
	// $currentUser = Users_Record_Model::getCurrentUserModel();
	// $currency_code = $currentUser->get('currency_code');
	$total_amount_in_dollar = $this->currencyConvert('USD', $currency_code, $total_local_currency, $date);
  $total_amount_in_dollar = number_format($total_amount_in_dollar, 2, '.', '');
	//	$thisIRDetail = Vtiger_Record_Model::getInstanceById($IR_ID, 'IntermediateRoutes');
	$response = new Vtiger_Response();
	$response->setResult($total_amount_in_dollar);
	$response->emit();
}else{

	$gross_value = $request->get('search');
	$total_local_currency_net = $request->get('total_local_currency_net');
  $pmtitle = $request->get('pmtitle');
	$result = $adb->pquery("SELECT companyid,cf_996,cf_1459 FROM `vtiger_companycf` where companyid ='$pmtitle'");
	$PMcurrency_id = $adb->query_result($result, 0, 'cf_1459');
	$paid_currency =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$PMcurrency_id'");
	 $paid_currency_code = $adb->query_result($paid_currency, 0, 'currency_code');

	$fromcurrency = $request->get('currency');
	$date = date('Y-m-d');
// echo "SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`='$fromcurrency'";
	$query = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`='$fromcurrency'");

	 $currency_code = $adb->query_result($query, 0, 'currency_code');
// echo $total_local_currency_net;exit;
 // print_r($currency_code);exit;
 // $currentUser = Users_Record_Model::getCurrentUserModel();
 // $currency_code = $currentUser->get('currency_code');
 $total_amount_in_dollar = $this->currencyConvert('USD', $currency_code, $gross_value, $date);
 $total_amount_for_grosss_local = $this->currencyConvert($paid_currency_code, $currency_code, $gross_value, $date);
 $total_amount_for_total_local_net = $this->currencyConvert($paid_currency_code, $currency_code, $total_local_currency_net, $date);
 $total_amount_in_dollar = number_format($total_amount_in_dollar, 2, '.', '');
 $total_amount_for_grosss_local = number_format($total_amount_for_grosss_local, 2, '.', '');
 $total_amount_for_total_local_net = number_format($total_amount_for_total_local_net, 2, '.', '');
 //	$thisIRDetail = Vtiger_Record_Model::getInstanceById($IR_ID, 'IntermediateRoutes');
echo $total_amount_for_grosss_local.','.$total_amount_for_total_local_net.','.$total_amount_in_dollar;
}
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
