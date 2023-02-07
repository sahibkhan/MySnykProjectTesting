<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PackagingMaterial_MassSave_Action extends Vtiger_MassSave_Action {

	public function requiresPermission(\Vtiger_Request $request) {

		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView');
		return $permissions;
	}
	
	public function process(Vtiger_Request $request) {
		// ini_set('display_errors', 1);
		// ini_set('display_startup_errors', 1);
		// error_reporting(E_ALL);
		global $adb;

		$response = new Vtiger_Response();

		try {
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode',false));
			$moduleName = $request->getModule();
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			$current_user = Users_Record_Model::getCurrentUserModel();
			$from_warehouse_id = $current_user->get('assign_warehouse_id');
			$glk_company_id = $current_user->get('company_id');
			$company_field = "  cf_5634 = '{$glk_company_id}' ";
			$inhouse ='Yes';

			$recordModels = $this->getRecordModelsFromRequest($request);
			$allRecordSave= true;

			foreach($recordModels as $recordId => $recordModel) {
				if(Users_Privileges_Model::isPermitted($moduleName, 'Save', $recordId)) {
					$packaging_material_info  = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
					$recordModel->save();

					$mode_view = $request->get('mode_view');
					$qty_issued = $recordModel->get('cf_5746');
					
					$from_warehouse_location_id = $recordModel->get('cf_6118');
					
					$packaging_material_id = $recordId;
					$job_id = $this->get_job_id_from_PackagingMaterial($packaging_material_id);

					$sourceModule_job = 'Job';
					$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

					$issue_date = $recordModel->get('cf_5748');
					
					if($qty_issued > 0 && $from_warehouse_location_id != 0  && !empty($issue_date))
					{
						$issue_date = $recordModel->get('cf_5748');
						$issue_date = date('Y-m-d', strtotime($issue_date));
						$adb->pquery("UPDATE vtiger_packagingmaterialcf set cf_6124='Received', cf_5748='".$issue_date."' WHERE packagingmaterialid='".$recordId."'", array());

						$item_code = $recordModel->get('cf_5738');
						$from_warehouse_location_id = $recordModel->get('cf_6118');
						$transaction_quantity = $recordModel->get('cf_5746');
						$transation_operator = "-";

						if($packaging_material_info->get('cf_6124')=='Requested' && $mode_view=='LISTING' && $qty_issued > 0)
						{						
							$sqlr = "UPDATE vtiger_whitemqtymastercf  SET cf_5646=cf_5646 {$transation_operator} {$transaction_quantity} WHERE cf_5630 = '{$item_code}' AND cf_5638 = '{$from_warehouse_id}' AND cf_5640='{$from_warehouse_location_id}' AND cf_5632 = '{$inhouse}' AND ".$company_field."  ";
							$rsi =$adb->pquery($sqlr, array());
							$sql_reportofpackaging_material = "SELECT reportofpackagingmaterialcf.cf_6172 as closing_quantity, reportofpackagingmaterialcf.cf_6174 as closing_unit, reportofpackagingmaterialcf.cf_6176 as closing_value FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid WHERE vtiger_crmentity.deleted=0 AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}' AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}' ORDER BY vtiger_crmentity.createdtime DESC LIMIT 1";
							
							$rs_reportofpackaging_ = $adb->pquery($sql_reportofpackaging_material, array());
							$total_rows_ = $adb->num_rows($rs_reportofpackaging_);
							$issue_unit_=0;
							if($total_rows_>0)
							{
								$row_last_price_ = $adb->fetch_array($rs_reportofpackaging_);
								$opening_quantity_ = $row_last_price_['closing_quantity'];
								$opening_unit_ = $row_last_price_['closing_unit'];
								$opening_value_ = $row_last_price_['closing_value'];
								
								$new_id = $adb->getUniqueId('vtiger_crmentity');
								$current_user = Users_Record_Model::getCurrentUserModel();
								$date_var = date("Y-m-d H:i:s");
								
								$adb->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$current_user->getId()."', smownerid ='".$current_user->getId()."',  setype = 'ReportofPackingMaterial', createdtime='".$date_var."', modifiedtime='".$date_var."'", array());
								
								$reportofpm_insert_query = "INSERT INTO vtiger_reportofpackingmaterial(reportofpackingmaterialid, name) VALUES(?,?)";
								$params_reportofpm = array($new_id, $packaging_material_info->get('cf_5754'));
								$adb->pquery($reportofpm_insert_query, $params_reportofpm);
								//$reportofpackingmaterialid = $adb->getLastInsertID();
								$reportofpackingmaterialid = $new_id;
								
								$issue_unit_ = $opening_unit_;
								$issue_value_ = $qty_issued * $issue_unit_;
								
								$closing_quantity_ = $opening_quantity_ - $qty_issued;
								$closing_value_ = $opening_value_ - $issue_value_;
								$closing_unit_rate = $closing_value_ / $closing_quantity_;
								
								$reportofpmcf_insert_query = "INSERT INTO vtiger_reportofpackingmaterialcf SET reportofpackingmaterialid='".$new_id."', cf_6148='".$item_code."', cf_6150='".$glk_company_id."', cf_6152='".$from_warehouse_id."', cf_6178='".$packaging_material_info->get('cf_5754')."', cf_6180='".$job_info->get('cf_1198')."', cf_6182='".$issue_date."', cf_6184='Issue', cf_6154='".$opening_quantity_."', cf_6156='".$opening_unit_."', cf_6158='".$opening_value_."', cf_6166='".$qty_issued."', cf_6168='".$issue_unit_."', cf_6170='".$issue_value_."', cf_6172='".$closing_quantity_."', cf_6174='".$closing_unit_rate."', cf_6176='".$closing_value_."' ";
								$adb->pquery($reportofpmcf_insert_query);
							}

							$total_final_item_price_ = $qty_issued * $issue_unit_;

							$adb->pquery("UPDATE vtiger_packagingmaterialcf set cf_6124='Received', cf_6142='".$total_final_item_price_."' WHERE packagingmaterialid='".$recordId."'", array());
						}

						$qty_returned = $recordModel->get('cf_5750');
						$returned_date = $recordModel->get('cf_5752'); //Returned Date
						if($packaging_material_info->get('cf_6124')=='Received'  && $mode_view=='LISTING' && $qty_returned > 0 && !empty($returned_date))
						{
							$old_returned_qty = $packaging_material_info->get('cf_5750');
							$returned_date = $recordModel->get('cf_5752');
							$returned_date = date('Y-m-d', strtotime($returned_date));
							
							$sql_return_receipt = "SELECT reportofpackagingmaterialcf.cf_6166 as issue_quantity, reportofpackagingmaterialcf.cf_6168 as issue_unit FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid WHERE vtiger_crmentity.deleted=0 AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}' AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}' AND reportofpackagingmaterialcf.cf_6178 = '{$packaging_ref_no}' AND reportofpackagingmaterialcf.cf_6184 ='Receipt' AND reportofpackagingmaterialcf.cf_6182 = '{$returned_date}' ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1";
							
							$rs_return_receipt =  $adb->pquery($sql_return_receipt, array());
							$rowsrs_return_receipt = $adb->num_rows($rs_return_receipt);

							if($rowsrs_return_receipt == 0)
							{
								$sql_reportofpackaging_material = "SELECT reportofpackagingmaterialcf.cf_6172 as closing_quantity, reportofpackagingmaterialcf.cf_6174 as closing_unit, reportofpackagingmaterialcf.cf_6176 as closing_value FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid WHERE vtiger_crmentity.deleted=0 AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}' AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}' ORDER BY vtiger_crmentity.createdtime DESC LIMIT 1 ";			 
							
								$rs_reportofpackaging_ = $adb->pquery($sql_reportofpackaging_material, array());
								$total_rows_ = $adb->num_rows($rs_reportofpackaging_);
								if($total_rows_>0)
								{
									$sql_issue_unit = "SELECT reportofpackagingmaterialcf.cf_6166 as issue_quantity,  reportofpackagingmaterialcf.cf_6168 as issue_unit FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid WHERE vtiger_crmentity.deleted=0 AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}' AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}' AND reportofpackagingmaterialcf.cf_6178 = '{$packaging_ref_no}' AND reportofpackagingmaterialcf.cf_6184 ='Issue' ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1";
									$rs_issue_unit = $adb->pquery($sql_issue_unit);
									$rowsrs_issue_unit = $adb->num_rows($rs_issue_unit);
									$last_issue_unit=0;
			
									if($rowsrs_issue_unit>0)
									{
										$row_issue_unit = $adb->fetch_array($rs_issue_unit);
										$last_issue_unit = $row_issue_unit['issue_unit'];
									}
										
									$row_last_closing_ = $adb->fetch_array($rs_reportofpackaging_);
									$opening_unit_ = $row_last_closing_['closing_unit'];
									$opening_quantity_ = $row_last_closing_['closing_quantity'];
									$opening_value_ = $row_last_closing_['closing_value'];
								
									$new_id = $adb->getUniqueId('vtiger_crmentity');
									$current_user = Users_Record_Model::getCurrentUserModel();
									$date_var = date("Y-m-d H:i:s");
									$db = PearDatabase::getInstance();
									$adb->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$current_user->getId()."' ,smownerid ='".$current_user->getId()."', setype = 'ReportofPackingMaterial', createdtime='".$date_var."', modifiedtime='".$date_var."'", array());
									
									$reportofpm_insert_query = "INSERT INTO vtiger_reportofpackingmaterial(reportofpackingmaterialid, name) VALUES(?,?)";
									$params_reportofpm = array($new_id, $packaging_material_info->get('cf_5754'));
									$adb->pquery($reportofpm_insert_query, $params_reportofpm);
									//$reportofpackingmaterialid = $adb->getLastInsertID();
									$reportofpackingmaterialid = $new_id;
									
									
									$receipt_qty_ = $qty_returned;
									$receipt_unit_ = $last_issue_unit;
									$receipt_value_ = $receipt_qty_ * $receipt_unit_;
									
									$closing_quantity_ = $opening_quantity_ + $receipt_qty_;
									$closing_value_ = $opening_value_ + $receipt_value_;
									$closing_unit_rate = $closing_value_ / $closing_quantity_;
									
									
									$reportofpmcf_insert_query = "INSERT INTO vtiger_reportofpackingmaterialcf(reportofpackingmaterialid, cf_6148, cf_6150, cf_6152, cf_6178, cf_6182, cf_6184, cf_6154, cf_6156, cf_6158, cf_6160, cf_6162, cf_6164, cf_6172, cf_6174, cf_6176)
									VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
									$params_reportofpmcf = array($new_id, $item_code, $glk_company_id, $from_warehouse_id, $packaging_material_info->get('cf_5754'), $returned_date, 'Receipt', $opening_quantity_, $opening_unit_, $opening_value_, $receipt_qty_, $receipt_unit_, $receipt_value_, $closing_quantity_, $closing_unit_rate, $closing_value_);
									
									$adb->pquery($reportofpmcf_insert_query, $params_reportofpmcf);
									
									$sqlr = "UPDATE vtiger_whitemqtymastercf  SET cf_5646=cf_5646 + {$qty_returned} WHERE cf_5630 = '{$item_code}' AND cf_5638 = '{$from_warehouse_id}' AND cf_5640='{$from_warehouse_location_id}' AND cf_5632 = '{$inhouse}' AND ".$company_field."  ";
									$rsi =$adb->pquery($sqlr, array());
								}
							}
						}
						$status = $recordModel->get('cf_6124');
						include("include/Exchangerate/exchange_rate_class.php");

						$sourceModule = 'PackagingMaterial';
						$packaging_material_id = $recordId;
						$packaging_material_info = Vtiger_Record_Model::getInstanceById($packaging_material_id, $sourceModule);
						$packaging_material_user_info = Users_Record_Model::getInstanceById($packaging_material_info->get('assigned_user_id'), 'Users');
						$packaging_material_user_company_id = $packaging_material_user_info->get('company_id');

						$packaging_material_user_local_currency_code = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$packaging_material_user_company_id);
						$packaging_material_user_currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$packaging_material_user_company_id);
						$CompanyAccountTypeList = Vtiger_Field_Model::getCompanyAccountTypeList();
						$CompanyAccountType_Bank_R_Key = array_search ('Bank R', $CompanyAccountTypeList);
						//$CompanyAccountType_Cash_R_Key = array_search ('Cash R', $CompanyAccountTypeList);
						$local_account_type[] = $CompanyAccountType_Bank_R_Key;
						//$local_account_type[] = $CompanyAccountType_Cash_R_Key;
						$local_account  = implode(",",$local_account_type);

						$qty_received_date = $recordModel->get('cf_5748');//QTY Received Date
						$selling_date = date('Y-m-d', strtotime($qty_received_date));
						//$b_exchange_rate_selling = $b_exchange_rate = currency_rate_convert($packaging_material_user_local_currency_code, 'USD',  1, $selling_date);
						//SubJRER Packing Material Own Expense
						$job_id = $this->get_job_id_from_PackagingMaterial($packaging_material_id);

						$sourceModule_job 	= 'Job';
						$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

						$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($job_info->get('cf_1186'));
						$job_file_title_currency = $job_reporting_currency;

						$db_expnese = PearDatabase::getInstance();
						$b_invoice_date = $selling_date;

						$b_vendor_currency = $packaging_material_user_currency_id;
						//$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date));
						$b_invoice_date_format = $b_invoice_date;

						$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
						
						if($job_file_title_currency =='KZT')
						{
							$b_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
						}
						elseif($job_file_title_currency =='USD')
						{
							$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $job_file_title_currency, 1, $b_invoice_date_format);
						}
						else{
							$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $job_file_title_currency, 1, $b_invoice_date_format);
						}
						$query_packaging = "SELECT vtiger_packagingmaterialcf.packagingmaterialid as packagingmaterialid, vtiger_packagingmaterialcf.cf_5754 as packaging_ref_no,vtiger_packagingmaterialcf.cf_5738 as item_code, (vtiger_packagingmaterialcf.cf_5746 - vtiger_packagingmaterialcf.cf_5750) as total_item, vtiger_packagingmaterialcf.cf_5748 as issue_date, vtiger_packagingmaterialcf.cf_6290 as custom_request FROM vtiger_packagingmaterialcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_packagingmaterialcf.packagingmaterialid WHERE vtiger_crmentity.deleted=0 AND vtiger_packagingmaterialcf.cf_5754='{$packaging_material_info->get('cf_5754')}' ";

						$params_packaging = array();
						$result_packaging = $adb->pquery($query_packaging, $params_packaging);

						$numRows_packaging = $adb->num_rows($result_packaging);
						$total_final_item_price = 0;
						$last_purchase_price = 0;
						
						$warehouse_id_formula = $packaging_material_info->get('cf_5764');
						//( KZARA ::2107543 || KZALA:: 1137684  || KZAST::1938258 ) warehouse
						if($warehouse_id_formula=='2107543' || $warehouse_id_formula=='1137684' || $warehouse_id_formula=='1938258' || $warehouse_id_formula=='2107572') 
						{
							$item_code_arr  = array('PB-1415','PB-1414', 'PB-14', 'PB-16', 'SBS-24', 'NBS-25');
							$item_code_gems_unit  = array('PB-1415' => 'Roll','PB-1414' => 'Roll','PB-14' => 'Roll', 'PB-16' => 'Roll', 'SBS-24' => 'Meter', 'NBS-25' => 'Meter');
							$item_code_1c_unit  = array('PB-1415' => array('Meter', '50'),
													    'PB-1414' => array('Meter', '120'),
													    'PB-14' => array('Meter', '150'), 
													    'PB-16' => array('Meter', '30'), 
													    'SBS-24' => array('KG','0.1'), 
													    'NBS-25' => array('KG','0.06'));
						}
						else{
							$item_code_arr  = array('PB-14', 'PB-16', 'SBS-24', 'NBS-25');
							$item_code_gems_unit  = array('PB-14' => 'Roll', 'PB-16' => 'Roll', 'SBS-24' => 'Meter', 'NBS-25' => 'Meter');
							$item_code_1c_unit  = array('PB-14' => array('Meter', '150'), 
												  		'PB-16' => array('Meter', '30'), 
												  		'SBS-24' => array('KG','0.1'), 
														'NBS-25' => array('KG','0.06'));
						}

						for($jj=0; $jj< $adb->num_rows($result_packaging); $jj++ ) {
							$row_packaging = $adb->fetch_row($result_packaging,$jj);
	
							$item_code =$row_packaging['item_code'];
							$item_code_cf_5738 = $item_code;
							$total_item = $row_packaging['total_item'];
							$issue_date = $row_packaging['issue_date'];
							$issue_date = date('Y-m-d', strtotime($issue_date));
							$packaging_ref_no = $row_packaging['packaging_ref_no'];
							
							$custom_request =  $row_packaging['custom_request'];
							$packagingmaterialid = $row_packaging['packagingmaterialid'];
							
							if($item_code=="SR-1" || $item_code=="SL-1" )
							{
								$query_custom_packaging = "SELECT * FROM vtiger_custompackingmaterial INNER JOIN  vtiger_custompackingmaterialcf.custompackingmaterialid = vtiger_custompackingmaterial.custompackingmaterialid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_custompackingmaterial.custompackingmaterialid INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='PackagingMaterial' AND crmentityrel.relmodule='CustomPackingMaterial'";
								$params_rel = array($packagingmaterialid);

								$result_rel = $adb->pquery($query_custom_packaging, $params_rel);
								$numRows_cpm = $adb->num_rows($result_rel);

								for($kk=0; $kk< $adb->num_rows($result_rel); $kk++ ) {
									$row_sub_packaging = $adb->fetch_row($result_rel,$kk);	
									
									$sub_item_code =$row_sub_packaging['cf_6268'];
									$sub_total_item = $row_sub_packaging['cf_6276'];
									$sub_issue_date = $row_sub_packaging['cf_6278'];
									$sub_issue_date = date('Y-m-d', strtotime($sub_issue_date));
									$sub_packaging_ref_no = $row_sub_packaging['name'];
									
									$sql_last_purchase = "SELECT reportofpackagingmaterialcf.cf_6166 as issue_quantity, reportofpackagingmaterialcf.cf_6168 as issue_unit FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid WHERE vtiger_crmentity.deleted=0 AND reportofpackagingmaterialcf.cf_6148 = '{$sub_item_code}' AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}' AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}' AND reportofpackagingmaterialcf.cf_6178 = '{$sub_packaging_ref_no}' AND reportofpackagingmaterialcf.cf_6184 ='Issue' ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1";
									
									$rs_last_purchase = $adb->pquery($sql_last_purchase, array());
									$rowsrs_last_purchase = $adb->num_rows($rs_last_purchase);
									$last_purchase_price=0;
			
									if($rowsrs_last_purchase>0)
									{
									$row_last_price =  $adb->fetch_array($rs_last_purchase);
									$last_purchase_price = $row_last_price['issue_unit'];
									}

									$total_final_item_price += ($sub_total_item * $last_purchase_price);
								}
							}
							else{

								$sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
											   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
											   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
											   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='$item_code'";
									   
								$rs_item = $adb->pquery($sql_item_query);
								$row_item = $adb->fetch_array($rs_item); 
								$whitemmaster_unit_id = $row_item['cf_5567'];

								$sql_last_purchase = "SELECT reportofpackagingmaterialcf.cf_6166 as issue_quantity, reportofpackagingmaterialcf.cf_6168 as issue_unit 
													  FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf 
													  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid 
													  WHERE vtiger_crmentity.deleted=0 AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' 
													  AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}' 
													  AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}' 
													  AND reportofpackagingmaterialcf.cf_6178 = '{$packaging_ref_no}' 
													  AND reportofpackagingmaterialcf.cf_6184 ='Issue' 
													  ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1";

								$rs_last_purchase = $adb->pquery($sql_last_purchase, array());
								$rowsrs_last_purchase = $adb->num_rows($rs_last_purchase);
								$last_purchase_price=0;
		
								if($rowsrs_last_purchase>0)
								{
									$row_last_price = $adb->fetch_array($rs_last_purchase);
									$last_purchase_price = $row_last_price['issue_unit'];
								}

								$item_consumed_amount = ($total_item * $last_purchase_price);

								$gems_unit = '';
								$ic_unit = '';
								if(!empty($whitemmaster_unit_id)){
									$whuom_info = Vtiger_Record_Model::getInstanceById($whitemmaster_unit_id, 'WHUOM');
									$gems_unit = $whuom_info->get('name');
									$ic_unit = $whuom_info->get('name');
								}

								if(in_array($item_code_cf_5738, $item_code_arr))
								{
									$qty_consumed = $total_item;
								$gems_unit = $item_code_gems_unit[$item_code_cf_5738];
								$ic_unit = $item_code_1c_unit[$item_code_cf_5738][0];
								$c_unit_formula =  $item_code_1c_unit[$item_code_cf_5738][1];
								$ic_qty_consumed = $qty_consumed * $c_unit_formula;
								if($item_code_cf_5738=='NBS-25')
								{
									$ic_qty_consumed = $ic_qty_consumed/10;
								}
									$qty_consumed = $ic_qty_consumed;
						
									$item_consumed_amount = $qty_consumed*$last_purchase_price;
								}
								$total_final_item_price += $item_consumed_amount;
								//$total_final_item_price += ($total_item * $last_purchase_price);
							}
						}
						$costing_breakdown_b_buy_local_currency_net = $total_final_item_price;
						$costing_breakdown_b_buy_local_currency_gross = $total_final_item_price;
						$costing_breakdown_b_buy_vendor_currency_net = $costing_breakdown_b_buy_local_currency_net;
						$costing_breakdown_b_buy_vendor_currency_gross = $costing_breakdown_b_buy_local_currency_gross;

						if($job_file_title_currency !='USD')
						{
							$b_buy_local_currency_gross = $costing_breakdown_b_buy_vendor_currency_gross * $b_exchange_rate;
						}else{
							$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $job_file_title_currency, $costing_breakdown_b_buy_vendor_currency_gross, $b_invoice_date_format);
						}

						if($job_file_title_currency !='USD')
						{
							$b_buy_local_currency_net = $costing_breakdown_b_buy_vendor_currency_net * $b_exchange_rate;
						}else{
							$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $job_file_title_currency, $costing_breakdown_b_buy_vendor_currency_net, $b_invoice_date_format);
						}
						$b_invoice_due_date = $b_invoice_date;
						$extra_buying = array(
									'b_invoice_date' => $b_invoice_date,
									'b_invoice_due_date' => $b_invoice_due_date,
									'b_buy_vendor_currency_net' => $costing_breakdown_b_buy_vendor_currency_net,
									'b_vat_rate'        => '0.00',
									'b_vat'             => '0.00',
									'b_buy_vendor_currency_gross' => $costing_breakdown_b_buy_vendor_currency_gross,
									'b_vendor_currency'    => $b_vendor_currency,
									'b_exchange_rate'      => $b_exchange_rate,
									'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
									'b_buy_local_currency_net'   => $b_buy_local_currency_net,
									'b_expected_buy_local_currency_net' => 0,
									'b_variation_expected_and_actual_buying' => 0
									);

						// $db_plus_main = PearDatabase::getInstance();
						$query_packing_material_own_update_main = "UPDATE vtiger_jobexpencereportcf INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid SET vtiger_jobexpencereportcf.cf_1337 = '".$extra_buying['b_buy_vendor_currency_net']."', vtiger_jobexpencereportcf.cf_1339 = '".$extra_buying['b_vat_rate']."', vtiger_jobexpencereportcf.cf_1341 = '".$extra_buying['b_vat']."', vtiger_jobexpencereportcf.cf_1343 = '".$extra_buying['b_buy_vendor_currency_gross']."', vtiger_jobexpencereportcf.cf_1347 = '".$extra_buying['b_buy_local_currency_gross']."', vtiger_jobexpencereportcf.cf_1349 = '".$extra_buying['b_buy_local_currency_net']."', vtiger_jobexpencereportcf.cf_1351 = '".$extra_buying['b_expected_buy_local_currency_net']."', vtiger_jobexpencereportcf.cf_1353 = '".$extra_buying['b_variation_expected_and_actual_buying']."', vtiger_jobexpencereportcf.cf_1345 = '".$extra_buying['b_vendor_currency']."', vtiger_jobexpencereportcf.cf_1222 = '".$extra_buying['b_exchange_rate']."', vtiger_jobexpencereportcf.cf_1216 = '".$extra_buying['b_invoice_date']."', vtiger_jobexpencereportcf.cf_1210 = '".$extra_buying['b_invoice_due_date']."' WHERE vtiger_jobexpencereportcf.cf_1453='85880' AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_jobexpencereport.packaging_ref_no='".$packaging_material_info->get('cf_5754')."' AND vtiger_jobexpencereport.owner_id = '".$job_info->get('assigned_user_id')."' AND vtiger_jobexpencereportcf.cf_1214 = '".$CompanyAccountType_Bank_R_Key."' ";
						
						$adb->query($query_packing_material_own_update_main, array());

								if($job_info->get('assigned_user_id')!=$packaging_material_info->get('assigned_user_id'))
								{
									$rs_query  = $adb->pquery("select * from vtiger_jobtask
															   where job_id='".$job_id."' and 
															   user_id='".$packaging_material_info->get('assigned_user_id')."' limit 1", array());
									$row_task = $adb->fetch_array($rs_query);

									if($adb->num_rows($rs_query)>0)
									{
										$file_title_id = $row_task['sub_jrer_file_title'];
										$job_office_id = $job_info->get('cf_1188');
										$packing_material_user_info = Users_Record_Model::getInstanceById($packaging_material_info->get('assigned_user_id'), 'Users');
										$packing_material_user_office_id = $packing_material_user_info->get('location_id');

										if($job_office_id==$packing_material_user_office_id){
											$file_title_id = $job_info->get('cf_1186');
										}
										else{
										$file_title_id = $packaging_material_user_company_id;
										}

										$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
										$params = array($file_title_id, $job_id, $packaging_material_info->get('assigned_user_id'));
										$adb->pquery($query,$params);
									}

									$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
									$job_file_title_currency = $job_reporting_currency;

									if($job_file_title_currency =='KZT')
									{
										$b_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
									}
									elseif($job_file_title_currency =='USD')
									{
										$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $job_file_title_currency, 1, $b_invoice_date_format);
									}
									else{
										$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $job_file_title_currency, 1, $b_invoice_date_format);
									}

									$costing_breakdown_b_buy_local_currency_net =$total_final_item_price;
									$costing_breakdown_b_buy_local_currency_gross =$total_final_item_price;
									$costing_breakdown_b_buy_vendor_currency_net = $costing_breakdown_b_buy_local_currency_net;
									$costing_breakdown_b_buy_vendor_currency_gross = $costing_breakdown_b_buy_local_currency_gross;

									if($job_file_title_currency !='USD')
									{
										$b_buy_local_currency_gross = $costing_breakdown_b_buy_vendor_currency_gross * $b_exchange_rate;
									}else{
										$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $job_file_title_currency, $costing_breakdown_b_buy_vendor_currency_gross, $b_invoice_date_format);
									}

									if($job_file_title_currency !='USD')
									{
										$b_buy_local_currency_net = $costing_breakdown_b_buy_vendor_currency_net * $b_exchange_rate;
									}else{
										$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $job_file_title_currency, $costing_breakdown_b_buy_vendor_currency_net, $b_invoice_date_format);
									}

									$assigne_extra_buying = array(
											'b_invoice_date' => $b_invoice_date,
											'b_invoice_due_date' => $b_invoice_due_date,
											'b_buy_vendor_currency_net' => $costing_breakdown_b_buy_vendor_currency_net,
											'b_vat_rate'        => '0.00',
											'b_vat'             => '0.00',
											'b_buy_vendor_currency_gross' => $costing_breakdown_b_buy_vendor_currency_gross,
											'b_vendor_currency'    => $b_vendor_currency,
											'b_exchange_rate'      => $b_exchange_rate,
											'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
											'b_buy_local_currency_net'   => $b_buy_local_currency_net,
											'b_expected_buy_local_currency_net' => 0,
											'b_variation_expected_and_actual_buying' => 0
											);
									$query_sub_packing_update_sub = "UPDATE vtiger_jobexpencereportcf INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid SET vtiger_jobexpencereportcf.cf_1337 = '".$assigne_extra_buying['b_buy_vendor_currency_net']."', vtiger_jobexpencereportcf.cf_1339 = '".$assigne_extra_buying['b_vat_rate']."', vtiger_jobexpencereportcf.cf_1341 = '".$assigne_extra_buying['b_vat']."', vtiger_jobexpencereportcf.cf_1343 = '".$assigne_extra_buying['b_buy_vendor_currency_gross']."', vtiger_jobexpencereportcf.cf_1347 = '".$assigne_extra_buying['b_buy_local_currency_gross']."', vtiger_jobexpencereportcf.cf_1349 = '".$assigne_extra_buying['b_buy_local_currency_net']."', vtiger_jobexpencereportcf.cf_1351 = '".$assigne_extra_buying['b_expected_buy_local_currency_net']."', vtiger_jobexpencereportcf.cf_1353 = '".$assigne_extra_buying['b_variation_expected_and_actual_buying']."', vtiger_jobexpencereportcf.cf_1345 = '".$assigne_extra_buying['b_vendor_currency']."', vtiger_jobexpencereportcf.cf_1222 = '".$assigne_extra_buying['b_exchange_rate']."', vtiger_jobexpencereportcf.cf_1216 = '".$assigne_extra_buying['b_invoice_date']."', vtiger_jobexpencereportcf.cf_1210 = '".$assigne_extra_buying['b_invoice_due_date']."' WHERE vtiger_jobexpencereportcf.cf_1453='85880' AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_jobexpencereport.packaging_ref_no='".$packaging_material_info->get('cf_5754')."' AND vtiger_jobexpencereport.owner_id = '".$packaging_material_info->get('assigned_user_id')."' AND vtiger_jobexpencereportcf.cf_1214 = '".$CompanyAccountType_Bank_R_Key."' ";
									$adb->query($query_sub_packing_update_sub, array());
								}

					}

					// $this->print_exit("Code is fine till here...", true);

				} else {
					$allRecordSave= false;
				}
			}
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);
			if($allRecordSave) {
				$response->setResult(true);
			} else {
			   $response->setResult(false);
			}
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	function getRecordModelsFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordIds = $this->getRecordsListFromRequest($request);
		$recordModels = array();

		$fieldModelList = $moduleModel->getFields();
		foreach($recordIds as $recordId) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleModel);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			foreach ($fieldModelList as $fieldName => $fieldModel) {
				$fieldValue = $request->get($fieldName, null);
				$fieldDataType = $fieldModel->getFieldDataType();
				if($fieldDataType == 'time'){
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if(isset($fieldValue) && $fieldValue != null) {
					if(!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				} else {
					$uiType = $fieldModel->get('uitype');
					if($uiType == 70) {
						$recordModel->set($fieldName, $recordModel->get($fieldName));
					}  else {
						$uiTypeModel = $fieldModel->getUITypeModel();
						$recordModel->set($fieldName, $uiTypeModel->getUserRequestValue($recordModel->get($fieldName)));
					}
				}
			}
			$recordModels[$recordId] = $recordModel;
		}
		return $recordModels;
	}

	function get_job_id_from_PackagingMaterial($recordId=0) {
		$adb = PearDatabase::getInstance();

		$checkjob = $adb->pquery("SELECT rel1.crmid AS job_id FROM `vtiger_crmentityrel` AS rel1 WHERE rel1.relcrmid = '".$recordId."' AND rel1.module='Job' AND rel1.relmodule='PackagingMaterial'", array());
		$crmId = $adb->query_result($checkjob, 0, 'job_id');
		$job_id = $crmId;
		return $job_id;
	}

	function print_exit($data, $exit = false){
		echo "<pre>";
		print_r($data);
		echo '</pre></br>';
		if ($exit == true) {
			exit;
		}
	}
}
