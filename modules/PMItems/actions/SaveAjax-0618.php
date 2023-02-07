<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMItems_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		
		$cf_4279 = $request->get('cf_4279'); //pm type
		$cf_4281 = $request->get('cf_4281'); //quantity
		$cf_4283 = $request->get('cf_4283'); //price per unit
		
		//$cf_4573 = $cf_4281 * $cf_4283;  //price per line
		$cf_4563 = $request->get('cf_4563'); //pay to currency
		$cf_4719 = $request->get('cf_4719'); //VAT Rate
		
		$pmrequisitions_id   = $request->get('sourceRecord');
		$sourceModule 		= $request->get('sourceModule');	
		$pmrequisitions_info = Vtiger_Record_Model::getInstanceById($pmrequisitions_id, $sourceModule);
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pmrequisitions_info->get('cf_4271'));
		$file_title_currency = $reporting_currency;
		include("include/Exchangerate/exchange_rate_class.php");
		
		$createdtime =  $pmrequisitions_info->get('CreatedTime');
		$createdtime_ex = date('Y-m-d', strtotime($createdtime));
		
		$item = 0;
		
		if(is_array($cf_4279))
		{
			foreach($cf_4279 as $key => $value)
			{
			$pm_type = $cf_4279[$key];
			$request->set('cf_4279', $cf_4279[$key]);
			
			$quantity = $cf_4281[$key];
			if(empty($quantity)) { $quantity = 0; }
			$request->set('cf_4281', $cf_4281[$key]);
			
			$price_per_unit = $cf_4283[$key];
			if(empty($price_per_unit)) { $price_per_unit = 0; }
			$request->set('cf_4283', $cf_4283[$key]);
			
			$price_per_line = $quantity * $price_per_unit;
			$request->set('cf_4573', $price_per_line);
			
			$pm_vat_rate = $cf_4719[$key];
			$request->set('cf_4719', $pm_vat_rate);
			
			$pay_to_currency_code = $cf_4563[$key];
			$request->set('cf_4563', $cf_4563[$key]);
			
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

				$request->set('cf_4721', $pm_vat);
				$request->set('cf_4725', $price_per_line_gross);
				
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
		}
		
			mysql_query("update vtiger_pmrequisitionscf set cf_4593='Pending' WHERE cf_4593='In Progress' AND pmrequisitionsid='".$pmrequisitions_id."' ");
		}
		
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_id = $current_user->getId();
		global $adb;
		
		//user_id :: 604 :: Rustam Balayev 
		//user_id :: 374 :: Mehtab Shah
		//user_id :: 60  :: Mr. Khan
		
		$approval_by_arr = array('604', '60');
		$field_name_arr = array('604' => array('approved_by' => 'cf_4589', 'approved_date' =>'cf_4591'),
								'60' => array('approved_by' => 'cf_4581', 'approved_date' =>'cf_4583')
								);
		//echo "<pre>";
		//print_r($field_name_arr);
		//echo $field_name_arr[$current_user_id]['approved_by'];
		//exit;
		$pmitems_sum_sql =  "SELECT sum(pmitemscf.cf_4567) as total_cost_local_currency , 
									sum(pmitemscf.cf_4575) as total_cost_in_usd 
							 FROM `vtiger_pmitemscf` as pmitemscf 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = pmitemscf.pmitemsid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
							 AND crmentityrel.module='PMRequisitions' AND crmentityrel.relmodule='PMItems'";
		
		// parentId = PM Requisitions ID		
		$params = array($pmrequisitions_id);
		$result = $adb->pquery($pmitems_sum_sql, $params);
		$row_pm_items = $adb->fetch_array($result);	
		$total_cost_in_usd = $row_pm_items['total_cost_in_usd'];
		
		
		if(in_array($current_user_id, $approval_by_arr) && $pmrequisitions_info->get('cf_4593')=='Pending')
		{			
			$approved_by_fieldname = $field_name_arr[$current_user_id]['approved_by'];		
			$approved_date_fieldname = $field_name_arr[$current_user_id]['approved_date'];
				
			$update_query = "update vtiger_pmrequisitionscf set $approved_by_fieldname=?, $approved_date_fieldname=? where pmrequisitionsid=?";
			$update_params = array($current_user_id, date('Y-m-d'), $pmrequisitions_id);
			
			$adb->pquery($update_query, $update_params);
			
			$delete = $request->get('delete');
			if(isset($delete) && $delete==1)
			{
				$cancelled_query = "UPDATE vtiger_pmrequisitionscf SET cf_4593=? WHERE pmrequisitionsid=?";
				$cancelled_params = array('Cancelled', $pmrequisitions_id);
				$adb->pquery($cancelled_query, $cancelled_params);
			}
			else{
				if($total_cost_in_usd>=500)
				{
					if($current_user_id==60)
					{					
					mysql_query("update vtiger_pmrequisitionscf set cf_4593='Approved' WHERE cf_4593='Pending' AND pmrequisitionsid='".$pmrequisitions_id."' ");
					}
				}
				else{
					mysql_query("update vtiger_pmrequisitionscf set cf_4593='Approved' WHERE cf_4593='Pending' AND pmrequisitionsid='".$pmrequisitions_id."' ");
				}			
				
			}
		}
		
		
        $pmrequisitions_info_email = Vtiger_Record_Model::getInstanceById($pmrequisitions_id, $sourceModule);
		
		$ceo_signature = $pmrequisitions_info_email->getDisplayValue('cf_4581');
		$ceo_signature_date = $pmrequisitions_info_email->get('cf_4583');
		
		$fd_signature = $pmrequisitions_info_email->getDisplayValue('cf_4589');
		$fd_signature_date = $pmrequisitions_info_email->get('cf_4591');
		
		$rrs_signature = $pmrequisitions_info_email->getDisplayValue('cf_4585');
		$rrs_signature_date = $pmrequisitions_info_email->get('cf_4587');
		
		$department = $pmrequisitions_info_email->getDisplayValue('cf_4275');
		$location = $pmrequisitions_info_email->getDisplayValue('cf_4273');
		$createdtime = $pmrequisitions_info_email->get('CreatedTime');
		
		
		
		$pm_owner_info = Users_Record_Model::getInstanceById($pmrequisitions_info_email->get('assigned_user_id'), 'Users');
		$owner_email   = $pm_owner_info->get('email1');
		$owner_first_email = $pm_owner_info->get('first_name');
		$owner_last_time   = $pm_owner_info->get('last_name');
		$created_by_name = $owner_first_email.' '.$owner_last_time;
		
		
		$fd_approval_info = Users_Record_Model::getInstanceById(604, 'Users');
		$fd_email   		 = $fd_approval_info->get('email1');
		$fd_first_email   = $fd_approval_info->get('first_name');
		$fd_last_time     = $fd_approval_info->get('last_name');
		
		$ceo_approval_info = Users_Record_Model::getInstanceById(60, 'Users');
		$ceo_email   		 = $ceo_approval_info->get('email1');
		$ceo_first_email   = $ceo_approval_info->get('first_name');
		$ceo_last_time     = $ceo_approval_info->get('last_name');
		
		
		$to = $owner_email.','.$fd_email.'';
		//$to = $fd_email;
		$cc = 's.mansoor@globalinklogistics.com, it@globalinklogistics.com';
		
		
		
		$pmrequisitions_info_check = Vtiger_Record_Model::getInstanceById($pmrequisitions_id, $sourceModule);
		$rrs_supervsior = $pmrequisitions_info_check->get('cf_4589');
		
		if(!empty($rrs_supervsior))
		{
			if($total_cost_in_usd>=500)
			{
				$cc .= $ceo_email;
			}
		}
		  
		 // $owner_email1 = trim($current_user->get('email1'));
		 $current_user_info = Users_Record_Model::getInstanceById($current_user->getId(), 'Users');
		 $owner_email1   = $current_user_info->get('email1');
		  	
		  $link = domain_name(); 
		  $link .= '/index.php?module=PMRequisitions&relatedModule=PMItems&view=Detail&record='.$pmrequisitions_id.'&mode=showRelatedList&tab_label=PM%20Items';
		  $from = "From: ".$owner_email1." <".$owner_email1.">";  
		  $body = '';
		  
		  $body .= "<p>Request for Packing ID: $pmrequisitions_id from : $created_by_name / $department / $location </p>";
		  
		   $body .= "<p>Created: $createdtime <br>";
		   
		  if($total_cost_in_usd>=500)
		  {
		  $body .= "CEO Signature:- $ceo_signature, Date:$ceo_signature_date<br>";
		  }
		  
		//$body .= "RRS Signature:- $rrs_signature, Date:$rrs_signature_date<br>";
		  $body .= "CFO Signature:- $fd_signature, Date:$fd_signature_date<br>";
		  
		  $delete = $request->get('delete');
		  if(isset($delete) && $delete==1)
		  {
			$cancelled_by = $current_user_info->get('first_name').' '.$current_user_info->get('last_name');
			$body .= "Packing Material Status: Cancelled<br>"; 
			$body .= "Cancelled By: $cancelled_by<br>";
		  }
		  
		  $body .= "Please see details on this link: <a href='$link'> Click To Follow Link </p>";
		  
										
			// Set content-type when sending HTML email
		  $headers = "MIME-Version: 1.0" . "\n";
		  $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		  $headers .= $from . "\n";
		  $headers .= 'Reply-To: '.$to.'' . "\n";
		  $headers .= "CC:" . $cc . "\r\n";
		  
		  $subject = "Request for Packing materials ID: ".$pmrequisitions_id."";	
		 
		  mail($to,$subject,$body,$headers);
		
		
		//$response = new Vtiger_Response();
		//$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		//$response->setResult($result);
		//$response->emit();
		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			
			//$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			//$loadUrl = $parentRecordModel->getListUrl();
			
			$loadUrl = 'index.php?module='.$parentModuleName.'&relatedModule='.$request->get('module').
				'&view=Detail&record='.$parentRecordId.'&mode=showRelatedList&tab_label=PM%20Items';
		}			
		header("Location: $loadUrl");
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
