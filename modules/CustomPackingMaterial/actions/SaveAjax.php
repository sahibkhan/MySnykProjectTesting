<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class CustomPackingMaterial_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		
		$request->set('cf_6280', 'Requested');
		
		$recordModel = $this->saveRecord($request);
		
		$recordId  = $recordModel->getId();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$from_warehouse_id = $current_user->get('assign_warehouse_id');
		$glk_company_id = $current_user->get('company_id');
		$company_field = "  cf_5634 = '{$glk_company_id}'";
   		$inhouse ='Yes';
		
		$qty_issued = $recordModel->get('cf_6276');
		$from_warehouse_location_id = $recordModel->get('cf_6274');
		
		$packaging_material_id = $request->get('sourceRecord');
		$job_id = $this->get_job_id_from_PackagingMaterial($packaging_material_id);

		$sourceModule_job 	= 'Job';
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		$sourceModule = 'PackagingMaterial';
		$packaging_material_id = $request->get('sourceRecord');
		$packaging_material_info = Vtiger_Record_Model::getInstanceById($packaging_material_id, $sourceModule);
		
		if($qty_issued > 0 && $from_warehouse_location_id != 0)
		{
			$issue_date = $recordModel->get('cf_6278');
			$issue_date = date('Y-m-d', strtotime($issue_date));
			
			//Due to check in posting packaging material to 1C.. to update status
			mysql_query("UPDATE vtiger_packagingmaterialcf set cf_6124='Received', cf_5748='".$issue_date."' WHERE packagingmaterialid='".$packaging_material_id."'");
					
			
			mysql_query("UPDATE vtiger_custompackingmaterialcf set cf_6280='Received', cf_6278='".$issue_date."' WHERE custompackingmaterialid='".$recordId."'");
			$item_code = $recordModel->get('cf_6268');
			$from_warehouse_location_id = $recordModel->get('cf_6274');
			$transaction_quantity = $recordModel->get('cf_6276');
			$transation_operator = "-";
			
			$sqlr = "UPDATE vtiger_whitemqtymastercf  SET cf_5646=cf_5646 {$transation_operator} {$transaction_quantity}
					 WHERE cf_5630 = '{$item_code}' AND cf_5638 = '{$from_warehouse_id}' AND cf_5640='{$from_warehouse_location_id}'
					 AND cf_5632 = '{$inhouse}' AND ".$company_field."  ";
			$rsi =mysql_query($sqlr);
			
			
			$sql_reportofpackaging_material = "SELECT reportofpackagingmaterialcf.cf_6172 as closing_quantity, 
											  reportofpackagingmaterialcf.cf_6174 as closing_unit,
											  reportofpackagingmaterialcf.cf_6176 as closing_value
											  FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf
											  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid
											  WHERE vtiger_crmentity.deleted=0 
											  AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' 
											  AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}'
											  AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}'
											  ORDER BY vtiger_crmentity.createdtime DESC LIMIT 1 
											  ";
													  //we replace with createdtime from date of reportofpackingmaterial
													  //ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1				 
					 
			$rs_reportofpackaging_ = mysql_query($sql_reportofpackaging_material);
			$total_rows_ = mysql_num_rows($rs_reportofpackaging_);
			$issue_unit_=0; //Issue rate / Average weighted cost			 
			
			if($total_rows_>0)
			{
				$row_last_price_ = mysql_fetch_array($rs_reportofpackaging_);
				$opening_quantity_ = $row_last_price_['closing_quantity'];//Closing Quantity
				$opening_unit_ = $row_last_price_['closing_unit'];//last purchase price						
				$opening_value_ = $row_last_price_['closing_value']; //Closing Value
				//Insert ReportofPackingMaterial Issue activity
				$adb = PearDatabase::getInstance();
				$new_id = $adb->getUniqueId('vtiger_crmentity');
				$current_user = Users_Record_Model::getCurrentUserModel();
				$date_var = date("Y-m-d H:i:s");
				$db = PearDatabase::getInstance();
				$db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$current_user->getId()."', smownerid ='".$current_user->getId()."', setype = 'ReportofPackingMaterial', createdtime='".$date_var."', modifiedtime='".$date_var."'");
				
				$adb_e = PearDatabase::getInstance();
				$reportofpm_insert_query = "INSERT INTO vtiger_reportofpackingmaterial(reportofpackingmaterialid, name) VALUES(?,?)";
				$params_reportofpm = array($new_id, $packaging_material_info->get('cf_5754'));
				$adb_e->pquery($reportofpm_insert_query, $params_reportofpm);
				$reportofpackingmaterialid = $adb_e->getLastInsertID();
				
				
				$issue_unit_ = $opening_unit_;
				$issue_value_ = $qty_issued * $issue_unit_;
				
				$closing_quantity_ = $opening_quantity_ - $qty_issued;
				$closing_value_ = $opening_value_ - $issue_value_;
				$closing_unit_rate = $closing_value_ / $closing_quantity_;
				
				$adb_ecf = PearDatabase::getInstance();
				$reportofpmcf_insert_query = "INSERT INTO vtiger_reportofpackingmaterialcf
				SET reportofpackingmaterialid='".$new_id."', 
				cf_6148='".$item_code."', cf_6150='".$glk_company_id."', cf_6152='".$from_warehouse_id."', 
				cf_6178='".$packaging_material_info->get('cf_5754')."', cf_6180='".$job_info->get('cf_1198')."', 
				cf_6182='".$issue_date."', cf_6184='Issue', cf_6154='".$opening_quantity_."', cf_6156='".$opening_unit_."', 
				cf_6158='".$opening_value_."', cf_6166='".$qty_issued."', cf_6168='".$issue_unit_."', 
				cf_6170='".$issue_value_."', cf_6172='".$closing_quantity_."', cf_6174='".$closing_unit_rate."', cf_6176='".$closing_value_."'
				";
				/*
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				$params_reportofpmcf = array($new_id, $item_code, $glk_company_id, $from_warehouse_id, $packaging_material_info->get('cf_5754'), $job_info->get('cf_1198'), $issue_date, 'Issue', $opening_quantity_, $opening_unit_, $opening_value_, $qty_issued, $issue_unit_, $issue_value_, $closing_quantity_, $closing_unit_rate, $closing_value_);
				*/
				//$adb_ecf->pquery($reportofpmcf_insert_query, $params_reportofpmcf);
				$adb_ecf->pquery($reportofpmcf_insert_query);
			}
			$total_final_item_price_ = $qty_issued * $issue_unit_; //Issue Value
			
			mysql_query("UPDATE vtiger_custompackingmaterialcf set cf_6280='Received', cf_6282='".$total_final_item_price_."' WHERE custompackingmaterialid='".$recordId."'");
			
			include("include/Exchangerate/exchange_rate_class.php");
			$sourceModule = 'PackagingMaterial';
			$packaging_material_id = $request->get('sourceRecord');
			$packaging_material_info = Vtiger_Record_Model::getInstanceById($packaging_material_id, $sourceModule);

			$packaging_material_user_info = Users_Record_Model::getInstanceById($packaging_material_info->get('assigned_user_id'), 'Users');
			$packaging_material_user_company_id = $packaging_material_user_info->get('company_id');
			
			$packaging_material_user_local_currency_code = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$packaging_material_user_company_id);
			$packaging_material_user_currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$packaging_material_user_company_id);
			$CompanyAccountTypeList = Vtiger_Field_Model::getCompanyAccountTypeListNew();
			$CompanyAccountType_Bank_R_Key = array_search ('Bank R', $CompanyAccountTypeList);
			//$CompanyAccountType_Cash_R_Key = array_search ('Cash R', $CompanyAccountTypeList);
			$local_account_type[] = $CompanyAccountType_Bank_R_Key;
			//$local_account_type[] = $CompanyAccountType_Cash_R_Key;
			$local_account  = implode(",",$local_account_type);

			$qty_received_date = $recordModel->get('cf_6278');//QTY Received Date
			$selling_date = date('Y-m-d', strtotime($qty_received_date));
			
			$job_id = $this->get_job_id_from_PackagingMaterial($packaging_material_id);
			 
			$sourceModule_job 	= 'Job';
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

			$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
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
			
			
			//To calculate from last transaction of item recevied in warehouse
			//Select total no of packing material request by packaging ref # and then calculate individual price for each packing material
			//Fetch all Packaging material data against packaging ref number.

			$db_packaging = PearDatabase::getInstance();

			$query_packaging = "SELECT vtiger_packagingmaterialcf.packagingmaterialid as packagingmaterialid, 
								vtiger_packagingmaterialcf.cf_5754 as packaging_ref_no, 
								vtiger_packagingmaterialcf.cf_5738 as item_code,
								(vtiger_packagingmaterialcf.cf_5746 - vtiger_packagingmaterialcf.cf_5750) as total_item, 
								vtiger_packagingmaterialcf.cf_5748 as issue_date, 
								vtiger_packagingmaterialcf.cf_6290 as custom_request
								FROM vtiger_packagingmaterialcf
								INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_packagingmaterialcf.packagingmaterialid
								WHERE vtiger_crmentity.deleted=0
								AND vtiger_packagingmaterialcf.cf_5754='{$packaging_material_info->get('cf_5754')}'
								";

			$params_packaging = array();
			$result_packaging = $db_packaging->pquery($query_packaging, $params_packaging);
			$numRows_packaging = $db_packaging->num_rows($result_packaging);
			$total_final_item_price = 0;
			$last_purchase_price = 0;
			
			for($jj=0; $jj< $db_packaging->num_rows($result_packaging); $jj++ ) {
						$row_packaging = $db_packaging->fetch_row($result_packaging,$jj);

						$item_code =$row_packaging['item_code'];
						$total_item = $row_packaging['total_item'];
						$issue_date = $row_packaging['issue_date'];
						$issue_date = date('Y-m-d', strtotime($issue_date));
						$packaging_ref_no = $row_packaging['packaging_ref_no'];
						$custom_request =  $row_packaging['custom_request'];
						$packagingmaterialid = $row_packaging['packagingmaterialid'];
						
						//if($custom_request=='Yes')
						if($item_code=="SR-1" || $item_code=="SL-1")
						{
						$db_cpm = PearDatabase::getInstance();	
						$query_custom_packaging = "SELECT * FROM vtiger_custompackingmaterial
									INNER JOIN  vtiger_custompackingmaterialcf ON vtiger_custompackingmaterialcf.custompackingmaterialid = vtiger_custompackingmaterial.custompackingmaterialid
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_custompackingmaterial.custompackingmaterialid
									INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
									WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
									AND crmentityrel.module='PackagingMaterial' AND crmentityrel.relmodule='CustomPackingMaterial'";
						$params_rel = array($packagingmaterialid);							   
						$result_rel = $db_cpm->pquery($query_custom_packaging, $params_rel);
						$numRows_cpm = $db_cpm->num_rows($result_rel);	
						//To Access Custom Item Code
							for($kk=0; $kk< $db_cpm->num_rows($result_rel); $kk++ ) {
								$row_sub_packaging = $db_cpm->fetch_row($result_rel,$kk);	
								
								$sub_item_code =$row_sub_packaging['cf_6268'];
								$sub_total_item = $row_sub_packaging['cf_6276'];
								$sub_issue_date = $row_sub_packaging['cf_6278'];
								$sub_issue_date = date('Y-m-d', strtotime($sub_issue_date));
								$sub_packaging_ref_no = $row_sub_packaging['name'];
								
								$sql_last_purchase = "SELECT reportofpackagingmaterialcf.cf_6166 as issue_quantity, 
												  reportofpackagingmaterialcf.cf_6168 as issue_unit
												  FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf
												  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid
												  WHERE vtiger_crmentity.deleted=0 
												  AND reportofpackagingmaterialcf.cf_6148 = '{$sub_item_code}' 
												  AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}'
												  AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}'
												  
												  AND reportofpackagingmaterialcf.cf_6178 = '{$sub_packaging_ref_no}'
												  AND reportofpackagingmaterialcf.cf_6184 ='Issue'
												  ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1";
								
								$rs_last_purchase = mysql_query($sql_last_purchase);
								$rowsrs_last_purchase = mysql_num_rows($rs_last_purchase);
								$last_purchase_price=0;
		
								if($rowsrs_last_purchase>0)
								{
									$row_last_price = mysql_fetch_array($rs_last_purchase);
									$last_purchase_price = $row_last_price['issue_unit']; //last purchase price
								}
		
								//$last_purchase_price=35;
								$total_final_item_price += ($sub_total_item * $last_purchase_price);			  
										   
							}
						}
						else{
							//For issue date fetch issue rate
							//AND reportofpackagingmaterialcf.cf_6182 = '{$issue_date}'
							$sql_last_purchase = "SELECT reportofpackagingmaterialcf.cf_6166 as issue_quantity, 
												  reportofpackagingmaterialcf.cf_6168 as issue_unit
												  FROM vtiger_reportofpackingmaterialcf as reportofpackagingmaterialcf
												  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = reportofpackagingmaterialcf.reportofpackingmaterialid
												  WHERE vtiger_crmentity.deleted=0 
												  AND reportofpackagingmaterialcf.cf_6148 = '{$item_code}' 
												  AND reportofpackagingmaterialcf.cf_6150 = '{$glk_company_id}'
												  AND reportofpackagingmaterialcf.cf_6152 = '{$from_warehouse_id}'
												  
												  AND reportofpackagingmaterialcf.cf_6178 = '{$packaging_ref_no}'
												  AND reportofpackagingmaterialcf.cf_6184 ='Issue'
												  ORDER BY reportofpackagingmaterialcf.cf_6182 DESC limit 1";
							
							$rs_last_purchase = mysql_query($sql_last_purchase);
							$rowsrs_last_purchase = mysql_num_rows($rs_last_purchase);
							$last_purchase_price=0;
	
							if($rowsrs_last_purchase>0)
							{
								$row_last_price = mysql_fetch_array($rs_last_purchase);
								$last_purchase_price = $row_last_price['issue_unit']; //last purchase price
							}
	
							//$last_purchase_price=35;
							$total_final_item_price += ($total_item * $last_purchase_price);
						}
					}
					
					
					//To update packing material expense
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
									'b_vat_rate'        => '',
									'b_vat'             => '',
									'b_buy_vendor_currency_gross' => $costing_breakdown_b_buy_vendor_currency_gross,
									'b_vendor_currency'    => $b_vendor_currency,
									'b_exchange_rate'      => $b_exchange_rate,
									'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
									'b_buy_local_currency_net'   => $b_buy_local_currency_net,
									'b_expected_buy_local_currency_net' => 0,
									'b_variation_expected_and_actual_buying' => 0
									);
									
					//Bank R=85785=Update
					$db_plus_main = PearDatabase::getInstance();
					$query_packing_material_own_update_main = "UPDATE vtiger_jobexpencereportcf
								INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
												INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid
														   SET
														   vtiger_jobexpencereportcf.cf_1337 = '".$extra_buying['b_buy_vendor_currency_net']."',
														   vtiger_jobexpencereportcf.cf_1339 = '".$extra_buying['b_vat_rate']."',
														   vtiger_jobexpencereportcf.cf_1341 = '".$extra_buying['b_vat']."',
														   vtiger_jobexpencereportcf.cf_1343 = '".$extra_buying['b_buy_vendor_currency_gross']."',
														   vtiger_jobexpencereportcf.cf_1347 = '".$extra_buying['b_buy_local_currency_gross']."',
														   vtiger_jobexpencereportcf.cf_1349 = '".$extra_buying['b_buy_local_currency_net']."',
														   vtiger_jobexpencereportcf.cf_1351 = '".$extra_buying['b_expected_buy_local_currency_net']."',
														   vtiger_jobexpencereportcf.cf_1353 = '".$extra_buying['b_variation_expected_and_actual_buying']."',
														   vtiger_jobexpencereportcf.cf_1345 = '".$extra_buying['b_vendor_currency']."',
														   vtiger_jobexpencereportcf.cf_1222 = '".$extra_buying['b_exchange_rate']."',
														   vtiger_jobexpencereportcf.cf_1216 = '".$extra_buying['b_invoice_date']."',
														   vtiger_jobexpencereportcf.cf_1210 = '".$extra_buying['b_invoice_due_date']."'
														   WHERE vtiger_jobexpencereportcf.cf_1453='85880' AND vtiger_crmentityrel.crmid='".$job_id."'
														   AND vtiger_jobexpencereport.packaging_ref_no='".$packaging_material_info->get('cf_5754')."'
														   AND vtiger_jobexpencereport.owner_id = '".$job_info->get('assigned_user_id')."'
														   AND vtiger_jobexpencereportcf.cf_1214 = '".$CompanyAccountType_Bank_R_Key."' ";
					 $db_plus_main->query($query_packing_material_own_update_main);
					 
					 if($job_info->get('assigned_user_id')!=$packaging_material_info->get('assigned_user_id'))
					 {
						 $db = PearDatabase::getInstance();	
						 
						  $rs_query  = mysql_query("select * from vtiger_jobtask
													where job_id='".$job_id."' and user_id='".$packaging_material_info->get('assigned_user_id')."' limit 1");
						  $row_task = mysql_fetch_array($rs_query);
						   if(mysql_num_rows($rs_query)>0)
						 {
							 $file_title_id = $row_task['sub_jrer_file_title'];
							 //Commented out if condition to check sub job file title must update again if its updated in main job file
							 //if(empty($file_title_id))
							 //{
								 $job_office_id = $job_info->get('cf_1188');
								 $packing_material_user_info = Users_Record_Model::getInstanceById($packaging_material_info->get('assigned_user_id'), 'Users');
								 $packing_material_user_office_id = $packing_material_user_info->get('location_id');

								 //if same office then job file title must apply
								 if($job_office_id==$packing_material_user_office_id){
									 $file_title_id = $job_info->get('cf_1186');
								 }
								 else{
									 //by default KZ file title
									 //$file_title_id = '85757'; //for old KZ file title
									 $file_title_id = $packaging_material_user_company_id;
								 }
								 $query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
								 $params = array($file_title_id, $job_id, $packaging_material_info->get('assigned_user_id'));
								 $db->pquery($query,$params);
							 //}
						 }
						 
						 $job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_id);
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

						 //To calculate from last transaction of item recevied in warehouse

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
									 'b_vat_rate'        => '',
									 'b_vat'             => '',
									 'b_buy_vendor_currency_gross' => $costing_breakdown_b_buy_vendor_currency_gross,
									 'b_vendor_currency'    => $b_vendor_currency,
									 'b_exchange_rate'      => $b_exchange_rate,
									 'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
									 'b_buy_local_currency_net'   => $b_buy_local_currency_net,
									 'b_expected_buy_local_currency_net' => 0,
									 'b_variation_expected_and_actual_buying' => 0
									 );

									 $db_plus_sub = PearDatabase::getInstance();

									$query_sub_packing_update_sub = "UPDATE vtiger_jobexpencereportcf
							INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
										   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid
										   SET
										   vtiger_jobexpencereportcf.cf_1337 = '".$assigne_extra_buying['b_buy_vendor_currency_net']."',
										   vtiger_jobexpencereportcf.cf_1339 = '".$assigne_extra_buying['b_vat_rate']."',
										   vtiger_jobexpencereportcf.cf_1341 = '".$assigne_extra_buying['b_vat']."',
										   vtiger_jobexpencereportcf.cf_1343 = '".$assigne_extra_buying['b_buy_vendor_currency_gross']."',
										   vtiger_jobexpencereportcf.cf_1347 = '".$assigne_extra_buying['b_buy_local_currency_gross']."',
										   vtiger_jobexpencereportcf.cf_1349 = '".$assigne_extra_buying['b_buy_local_currency_net']."',
										   vtiger_jobexpencereportcf.cf_1351 = '".$assigne_extra_buying['b_expected_buy_local_currency_net']."',
										   vtiger_jobexpencereportcf.cf_1353 = '".$assigne_extra_buying['b_variation_expected_and_actual_buying']."',
										   vtiger_jobexpencereportcf.cf_1345 = '".$assigne_extra_buying['b_vendor_currency']."',
										   vtiger_jobexpencereportcf.cf_1222 = '".$assigne_extra_buying['b_exchange_rate']."',
										   vtiger_jobexpencereportcf.cf_1216 = '".$assigne_extra_buying['b_invoice_date']."',
										   vtiger_jobexpencereportcf.cf_1210 = '".$assigne_extra_buying['b_invoice_due_date']."'
										   WHERE vtiger_jobexpencereportcf.cf_1453='85880' AND vtiger_crmentityrel.crmid='".$job_id."'
										   AND vtiger_jobexpencereport.packaging_ref_no='".$packaging_material_info->get('cf_5754')."'
										   AND vtiger_jobexpencereport.owner_id = '".$packaging_material_info->get('assigned_user_id')."'
										   AND vtiger_jobexpencereportcf.cf_1214 = '".$CompanyAccountType_Bank_R_Key."' ";
									// AND vtiger_jobexpencereportcf.cf_1214 = '85785' ";
									 $db_plus_sub->query($query_sub_packing_update_sub);
					 }
			
			
		}
		

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
	
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();	
		
	}
	
	function get_job_id_from_PackagingMaterial($recordId=0) {
			$adb = PearDatabase::getInstance();

			$checkjob = $adb->pquery("SELECT rel1.crmid AS job_id
									FROM `vtiger_crmentityrel` AS rel1
									WHERE rel1.relcrmid = '".$recordId."' AND rel1.module='Job' AND rel1.relmodule='PackagingMaterial'", array());
			$crmId = $adb->query_result($checkjob, 0, 'job_id');
			$job_id = $crmId;
			return $job_id;
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
