<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class ItemTRXDetail_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		try{
			global $adb;
			
			if($request->get('cf_5609')=='Received' && $request->get('record')!='')
			{
				include("include/Exchangerate/exchange_rate_class.php");
				$get_transaction_id = $adb->pquery("select vtiger_crmentityrel.crmid as transaction_id from vtiger_crmentityrel 
				INNER JOIN vtiger_crmentity ON vtiger_crmentityrel.crmid = vtiger_crmentity.crmid 
				where module='ItemTRXMaster' AND relmodule='ItemTRXDetail' and vtiger_crmentityrel.relcrmid='".$request->get('record')."'
				AND vtiger_crmentity.deleted = 0 limit 1
				");
				
				if($adb->num_rows($get_transaction_id)>0){
					$transaction_record = $adb->query_result_rowdata($get_transaction_id, 0);
					$transaction_id = $transaction_record['transaction_id'];
				}
				$transaction_info_detail = Vtiger_Record_Model::getInstanceById($transaction_id, 'ItemTRXMaster'); //PM Transaction
				$procurement_id = $transaction_info_detail->get('cf_6064');
				if($procurement_id!='')
				{
					$pm_info_detail = Vtiger_Record_Model::getInstanceById($procurement_id, 'Procurement'); //procurement
					
					
					$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pm_info_detail->get('proc_title'));
					$file_title_currency = $reporting_currency;
					$createdtime =  $pm_info_detail->get('createdtime');
					$createdtime_ex = date('Y-m-d', strtotime($createdtime));
					
					$qty = $request->get('cf_5621');
					$unitprice = $request->get('cf_6030');								
					$vatunit = $request->get('cf_6034');
					
					$currency = $request->get('cf_6040');
					$get_currency_code =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$currency'");
					$item_currency = $adb->query_result($get_currency_code, 0, 'currency_code');
					$pay_to_currency = $item_currency;
					
					$pm_items_qty = $qty;					
					$pm_items_price_per_unit = $unitprice;
					//$pm_items_price_per_line = $pmitem_info->get('cf_4573');
					$pm_items_price_per_line = $pm_items_qty * $pm_items_price_per_unit;
					$pm_items_vat_rate = $vatunit;
					//$pm_items_vat = $pmitem_info->get('cf_4721');
					//$pm_items_price_per_line_gross = $pmitem_info->get('cf_4725');
					$pm_items_vat = '0.00';
					if(!empty($pm_items_vat_rate) && $pm_items_vat_rate>0)
					{
						$pm_vat_rate_cal = $pm_items_vat_rate/100; 
						$pm_items_vat    = $pm_items_price_per_line * $pm_vat_rate_cal;
					}		
					$pm_items_price_per_line_gross = $pm_items_price_per_line + $pm_items_vat;				
					
					
					//$pay_to_currency = Vtiger_CurrencyList_UIType::getDisplayValue($currency);
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
					$request->set('cf_5621',$pm_items_qty);//qty
					$request->set('cf_6030',$pm_items_price_per_unit);//price_per_unit
					$request->set('cf_6032',$pm_items_price_per_line);//price_per_line
					$request->set('cf_6034',$pm_items_vat_rate);//vat_rate
					$request->set('cf_6036',$pm_items_vat);//vat_price
					$request->set('cf_6038',$pm_items_price_per_line_gross);//price_per_line_gross
					$request->set('cf_6042',$pm_items_exchange_rate);//items_exchange_rate
					$request->set('cf_6044',$pm_items_final_amount_gross);//final_amount_gross
					$request->set('cf_6046',$pm_items_final_amount_net);//final_amount_net
					$request->set('cf_6048',number_format($pm_items_total_gross_dollar, 2, '.', ''));//total_gross_dollar
				}
				
			}
			elseif($request->get('cf_5609')=='Transfer' && $request->get('record')!='')
			{
				include("include/Exchangerate/exchange_rate_class.php");
				$get_transaction_id = $adb->pquery("select vtiger_crmentityrel.crmid as transaction_id from vtiger_crmentityrel 
				INNER JOIN vtiger_crmentity ON vtiger_crmentityrel.crmid = vtiger_crmentity.crmid 
				where module='ItemTRXMaster' AND relmodule='ItemTRXDetail' and vtiger_crmentityrel.relcrmid='".$request->get('record')."'
				AND vtiger_crmentity.deleted = 0 limit 1
				");
				
				if($adb->num_rows($get_transaction_id)>0){
					$transaction_record = $adb->query_result_rowdata($get_transaction_id, 0);
					$transaction_id = $transaction_record['transaction_id'];
				}
				$transaction_info_detail = Vtiger_Record_Model::getInstanceById($transaction_id, 'ItemTRXMaster'); //PM Transaction
				$ref_doc_procurement_no = $transaction_info_detail->get('cf_5599');
				$get_procurement_id = $adb->pquery("SELECT vtiger_procurement.procurementid FROM `vtiger_procurement` 
				inner join vtiger_procurementcf on vtiger_procurement.procurementid = vtiger_procurementcf.procurementid
				inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementcf`.procurementid
				
				where proc_request_no = '$ref_doc_procurement_no' AND vtiger_crmentity.deleted=0 AND (proc_order_status='Approved' OR proc_order_status='Paid')");
				
				$procurement_details =  $adb->query_result_rowdata($get_procurement_id,0);
				$procurement_id =  $procurement_details['procurementid'];
				
				
				if($procurement_id!='')
				{
					$pm_info_detail = Vtiger_Record_Model::getInstanceById($procurement_id, 'Procurement'); //procurement
					
					
					$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pm_info_detail->get('proc_title'));
					$file_title_currency = $reporting_currency;
					$createdtime =  $pm_info_detail->get('createdtime');
					$createdtime_ex = date('Y-m-d', strtotime($createdtime));
					
					$qty = $request->get('cf_5621');
					$unitprice = $request->get('cf_6030');								
					$vatunit = $request->get('cf_6034');
					
					$currency = $request->get('cf_6040');
					$get_currency_code =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$currency'");
					$item_currency = $adb->query_result($get_currency_code, 0, 'currency_code');
					$pay_to_currency = $item_currency;
					
					$pm_items_qty = $qty;					
					$pm_items_price_per_unit = $unitprice;
					//$pm_items_price_per_line = $pmitem_info->get('cf_4573');
					$pm_items_price_per_line = $pm_items_qty * $pm_items_price_per_unit;
					$pm_items_vat_rate = $vatunit;
					//$pm_items_vat = $pmitem_info->get('cf_4721');
					//$pm_items_price_per_line_gross = $pmitem_info->get('cf_4725');
					$pm_items_vat = '0.00';
					if(!empty($pm_items_vat_rate) && $pm_items_vat_rate>0)
					{
						$pm_vat_rate_cal = $pm_items_vat_rate/100; 
						$pm_items_vat    = $pm_items_price_per_line * $pm_vat_rate_cal;
					}		
					$pm_items_price_per_line_gross = $pm_items_price_per_line + $pm_items_vat;				
					
					
					//$pay_to_currency = Vtiger_CurrencyList_UIType::getDisplayValue($currency);
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
					$request->set('cf_5621',$pm_items_qty);//qty
					$request->set('cf_6030',$pm_items_price_per_unit);//price_per_unit
					$request->set('cf_6032',$pm_items_price_per_line);//price_per_line
					$request->set('cf_6034',$pm_items_vat_rate);//vat_rate
					$request->set('cf_6036',$pm_items_vat);//vat_price
					$request->set('cf_6038',$pm_items_price_per_line_gross);//price_per_line_gross
					$request->set('cf_6042',$pm_items_exchange_rate);//items_exchange_rate
					$request->set('cf_6044',$pm_items_final_amount_gross);//final_amount_gross
					$request->set('cf_6046',$pm_items_final_amount_net);//final_amount_net
					$request->set('cf_6048',number_format($pm_items_total_gross_dollar, 2, '.', ''));//total_gross_dollar
				}
				
			}
			elseif($request->get('cf_5609')=='Opening Balance')
			{
				$duplicate_query = $adb->pquery("Select * from vtiger_itemtrxdetailcf 

				inner join vtiger_crmentityrel on vtiger_itemtrxdetailcf.itemtrxdetailid = vtiger_crmentityrel.relcrmid 

				inner join vtiger_itemtrxmastercf on vtiger_crmentityrel.crmid = vtiger_itemtrxmastercf.itemtrxmasterid AND vtiger_itemtrxmastercf.itemtrxmasterid = '".$request->get('sourceRecord')."'

				where vtiger_crmentityrel.relmodule = 'ItemTRXDetail' 

				and vtiger_crmentityrel.module = 'ItemTRXMaster'

				AND vtiger_itemtrxdetailcf.cf_5613 = '".$request->get('cf_5613')."'
				");
					
				$qty = $request->get('cf_5621');
				$unitprice = $request->get('cf_6030');
				$vatunit = $request->get('cf_6034');
				//echo "$duplicate_query <pre>";print_r($request);echo "</pre>";exit;
				if($adb->num_rows($duplicate_query)>0){
					throw new VALIDATION_ERROR('- Same	item already exists. Duplicate Item with code ('.$request->get('cf_5613').') is not allowed.');
				}
				if($qty<=0)
				{
					throw new VALIDATION_ERROR('- Quantity is missing. Please add valid quantity in "TRX QTY" field.');
				}
				if($unitprice<=0)
				{
					throw new VALIDATION_ERROR('- Unit Price is missing. Please add valid price in "Price Per Unit" field.');
				}
				include("include/Exchangerate/exchange_rate_class.php");
				//cf_6034 VAT rate
				//cf_6040 currency
				
				$currency = $request->get('cf_6040');
				$get_currency_code =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$currency'");
				$item_currency = $adb->query_result($get_currency_code, 0, 'currency_code');
				$pay_to_currency = $item_currency;
				//echo $pay_to_currency;exit;
				$pm_items_qty = $qty;					
				$pm_items_price_per_unit = $unitprice;
				//$pm_items_price_per_line = $pmitem_info->get('cf_4573');
				$pm_items_price_per_line = $pm_items_qty * $pm_items_price_per_unit;
				$pm_items_vat_rate = $vatunit;
				//$pm_items_vat = $pmitem_info->get('cf_4721');
				//$pm_items_price_per_line_gross = $pmitem_info->get('cf_4725');
				$pm_items_vat = '0.00';
				if(!empty($pm_items_vat_rate) && $pm_items_vat_rate>0)
				{
					$pm_vat_rate_cal = $pm_items_vat_rate/100; 
					$pm_items_vat    = $pm_items_price_per_line * $pm_vat_rate_cal;
				}		
				$pm_items_price_per_line_gross = $pm_items_price_per_line + $pm_items_vat;
				
				$transaction_data = Vtiger_Record_Model::getInstanceById($request->get('sourceRecord'), 'ItemTRXMaster');
				$file_title = $transaction_data->get('cf_5595');
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title);
				$file_title_currency = $reporting_currency;
				$createdtime_ex = date('Y-m-d');
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
				
				
				$request->set('cf_6032',$pm_items_price_per_line);//price_per_line
				$request->set('cf_6036',$pm_items_vat);//vat_price
				$request->set('cf_6038',$pm_items_price_per_line_gross);//price_per_line_gross
				$request->set('cf_6042',$pm_items_exchange_rate);//items_exchange_rate
				$request->set('cf_6044',$pm_items_final_amount_gross);//final_amount_gross
				$request->set('cf_6046',$pm_items_final_amount_net);//final_amount_net
				$request->set('cf_6048',number_format($pm_items_total_gross_dollar, 2, '.', ''));//total_gross_dollar
			}
			if($request->get('record')=='')
			{
				$request->set('item_post_status','Not Posted');//for new item set value to Not Posted
			}
			//echo "<pre>";print_r($request);echo "</pre>";exit;
			$recordModel = $this->saveRecord($request);
			
			//For batch item entries
			$item_code = $recordModel->get('cf_5613'); 
			
			//Checking batch item and inserting locationa and quantity
			// $sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
			// 				   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
			// 				   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
			// 				   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='{$item_code}'";
							   
			// $rs_item = mysql_query($sql_item_query);
			// $row_item = mysql_fetch_assoc($rs_item);
			$rs_item = $adb->pquery("SELECT * FROM vtiger_whitemmastercf 
							   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
							   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
							   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565=? ", array($item_code));
			$row_item = $adb->fetch_array($rs_item);
			
			$item_tracking = $row_item['cf_5575'];
			if($item_tracking=='Batch')
			{
				$quantity = $request->get('quantity');
				$location = $request->get('location');
				foreach($location as $key => $value)
				{
					$location_id =  $location[$key];
					$quantity_value =  $quantity[$key];
					
					if($location_id!=0 && $quantity_value>0)
					{
						$adb = PearDatabase::getInstance();
						$current_user = Users_Record_Model::getCurrentUserModel();
						$ownerId = $recordModel->get('assigned_user_id');
						$date_var = date("Y-m-d H:i:s");
						$usetime = $adb->formatDate($date_var, true);
						$ItemTRXDetail_id = $recordModel->getId();
						
						$current_id = $adb->getUniqueId('vtiger_crmentity');
						$source_id = $ItemTRXDetail_id;
						
						//INSERT data in JRER expense module from job costing
						$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
							 setype, description, createdtime, modifiedtime, presence, deleted, label)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
							array($current_id, $current_user->getId(), $current_user->getId(), 'ItemTRXBDetail', 'NULL', $date_var, $date_var, 1, 0, 'Batch Item'));
						
							
						$adb_rel = PearDatabase::getInstance();
						$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
						$params_crmentityrel = array($source_id, 'ItemTRXDetail', $current_id, 'ItemTRXBDetail');
						$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
						
						
						$adb_e = PearDatabase::getInstance();
						$batchitem_insert_query = "INSERT INTO vtiger_itemtrxbdetail(itemtrxbdetailid, name) VALUES(?,?)";
						$params_batchitem= array($current_id, $source_id);	
						$adb_e->pquery($batchitem_insert_query, $params_batchitem);
						
						$adb_bcf = PearDatabase::getInstance();
						//Batch::cf_5716 = TRX::cf_5609 = Doc Type
						//Batch::cf_5718 = TRX::cf_5611 = Doc Number
						//Batch::cf_5720 = TRX::cf_5613 = Item Code
						//Batch::cf_5722 = TRX::cf_5615 = From WH
						//Batch::cf_5724 = TRX::location_id = From Location WH
						//Batch::cf_5726 = TRX::cf_5710 = Batch ID
						//Batch::cf_5728 = TRX::quantity_value = TRX QTY
						$batchitemcf_insert_query = "INSERT INTO vtiger_itemtrxbdetailcf(itemtrxbdetailid, cf_5716, cf_5718, cf_5720, cf_5722, cf_5724, cf_5726, cf_5728) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
						$params_batchitemcf = array($current_id, $recordModel->get('cf_5609'), $recordModel->get('cf_5611'), $recordModel->get('cf_5613'), $recordModel->get('cf_5615'), $location_id, $recordModel->get('cf_5710'),$quantity_value);
						$adb_bcf->pquery($batchitemcf_insert_query, $params_batchitemcf);
					}
				}
			}
			
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
		} 
		catch(VALIDATION_ERROR $e)	{
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			$requestData['view'] = 'Edit';
			$requestData['VALIDATION_ERROR'] = $e->getMessage();
			
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			global $vtiger_current_version;
			$viewer = new Vtiger_Viewer();
			
			$viewer->assign('REQUEST_DATA', $requestData);
			$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
			$viewer->view('RedirectToEditView.tpl', 'Vtiger');

		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
class VALIDATION_ERROR extends Exception {};
