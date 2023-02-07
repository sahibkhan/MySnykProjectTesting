<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/


class Jobexp_RelatedList_View extends Vtiger_RelatedList_View {
	function __construct() {
		parent::__construct();
		
	}
	function process(Vtiger_Request $request) {
		global $adb;
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');
		$label = $request->get('tab_label');
		$requestedPage = $request->get('page');
		if(empty($requestedPage)) {
			$requestedPage = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page',$requestedPage);

		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'icon-chevron-down';
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'icon-chevron-up';
		}
		if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder',$sortOrder);
		}
		$models = $relationListView->getEntries($pagingModel);
		$links = $relationListView->getLinks();
		$header = $relationListView->getHeaders();
		$noOfEntries = count($models);

		$relationModel = $relationListView->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relationField = $relationModel->getRelationField();

		$viewer = $this->getViewer($request);
		
		if($relatedModuleName =='JER')
		{
			// Added below code for expected cost and revenue summary with expected profit in local currency and USD currency
			$jer_sum_sql =  "SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency FROM `vtiger_jercf` as jercf 
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON jercf.jerid= crmentityrel.relcrmid 
							 where crmentityrel.crmid=? and crmentityrel.module='Job' and crmentityrel.relmodule='JER'";
			// parentId = Job Id	
			$job_id = $parentId;		 
			$params = array($parentId);
			$result = $adb->pquery($jer_sum_sql, $params);
			$row_job_costing = $adb->fetch_array($result);
			
			$total_cost_local_currency = $row_job_costing['total_cost_local_currency'];
			$total_revenue_local_currency = $row_job_costing['total_revenue_local_currency'];
			
			$viewer->assign('TOTAL_COST_LOCAL_CURRENCY' , number_format ( $row_job_costing['total_cost_local_currency'] , 2 ,  "." , "," ));
			$viewer->assign('TOTAL_REVENUE_LOCAL_CURRENCY' , number_format ( $row_job_costing['total_revenue_local_currency'] , 2 ,  "." , "," ));
			
			include("include/Exchangerate/exchange_rate_class.php");
			$job_info = get_job_details($job_id);
			$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
			$file_title_currency = $reporting_currency;
			
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
			if($file_title_currency!='USD')
			{
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, date('Y-m-d'));
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, date('Y-m-d'));
			}
			$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));
			
			$total_cost_usd = $total_cost_local_currency/$final_exchange_rate;
			$total_revenue_usd = $total_revenue_local_currency/$final_exchange_rate;
			
			$viewer->assign('TOTAL_COST_USD', number_format ( $total_cost_usd , 2 ,  "." , "," ));
			$viewer->assign('TOTAL_REVENUE_USD', number_format ( $total_revenue_usd , 2 ,  "." , "," ));
			
			 $total_cost_local = $total_cost_local_currency; 
             $total_revenue_local = $total_revenue_local_currency;
			
			$expected_profit_local_currency = number_format($total_revenue_local - $total_cost_local, 2 ,  "." , ",");
			$viewer->assign('EXPECTED_PROFIT', $expected_profit_local_currency);
			$expected_profit_usd = number_format($total_revenue_usd - $total_cost_usd, 2 ,  "." , ",");
			$viewer->assign('EXPECTED_PROFIT_USD', $expected_profit_usd);
							 
		}
		
		
		$viewer->assign('RELATED_RECORDS' , $models);
		$viewer->assign('PARENT_RECORD', $parentRecordModel);
		$viewer->assign('RELATED_LIST_LINKS', $links);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE', $relatedModuleModel);
		$viewer->assign('RELATED_ENTIRES_COUNT', $noOfEntries);
		$viewer->assign('RELATION_FIELD', $relationField);

		if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
			$totalCount = $relationListView->getRelatedEntriesCount();
			$pageLimit = $pagingModel->getPageLimit();
			$pageCount = ceil((int) $totalCount / (int) $pageLimit);

			if($pageCount == 0){
				$pageCount = 1;
			}
			$viewer->assign('PAGE_COUNT', $pageCount);
			$viewer->assign('TOTAL_ENTRIES', $totalCount);
			$viewer->assign('PERFORMANCE', true);
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('PAGING', $pagingModel);

		$viewer->assign('ORDER_BY',$orderBy);
		$viewer->assign('SORT_ORDER',$sortOrder);
		$viewer->assign('NEXT_SORT_ORDER',$nextSortOrder);
		$viewer->assign('SORT_IMAGE',$sortImage);
		$viewer->assign('COLUMN_NAME',$orderBy);

		$viewer->assign('IS_EDITABLE', $relationModel->isEditable());
		$viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('VIEW', $request->get('view'));
		
		if($relatedModuleName =='Jobexpencereport')
		{
			$expence_arr = array('cf_1453', 'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
			$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457');
			$viewer->assign('EXPENCE_ARR', $expence_arr);
			$viewer->assign('SELLING_ARR', $selling_arr);
			
			// Added below code for expence summary 
			$jrer_sum_sql =  "SELECT sum(jrercf.cf_1347) as buy_local_currency_gross, 
								     sum(jrercf.cf_1349) as buy_local_currency_net,
									 sum(jrercf.cf_1351) as expected_buy_local_currency_net, 
									 sum(jrercf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereportcf` as jrercf 
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON jrercf.jobexpencereportid= crmentityrel.relcrmid 
							 where crmentityrel.crmid=? and crmentityrel.module='Jobexp' and crmentityrel.relmodule='Jobexpencereport' and jrercf.cf_1457='Expence'";
							 
			// parentId = Job Id	
			//$job_id = $parentId;			 
			$params = array($parentId);
			$result = $adb->pquery($jrer_sum_sql, $params);
			$row_job_jrer = $adb->fetch_array($result);
			
			$viewer->assign('BUY_LOCAL_CURRENCY_GROSS' , number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , "" ));
			$viewer->assign('BUY_LOCAL_CURRENCY_NET' , number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "" ));
			$viewer->assign('EXPECTED_BUY_LOCAL_CURRENCY_NET' , number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "" ));
			$viewer->assign('VARIATION_EXPECTED_AND_ACTUAL_BUYING' , number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "" ));
			
			include("include/Exchangerate/exchange_rate_class.php");
			$parent_id_of_expense_report = $parentId; 
			$result_rel = $adb->pquery('SELECT * FROM `vtiger_crmentityrel` where relcrmid=?', array($parent_id_of_expense_report));
			$row_job_jrer_rel = $adb->fetch_array($result_rel);
			$job_id = $row_job_jrer_rel['crmid'];
			$job_info = get_job_details($job_id);
			$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
			$file_title_currency = $reporting_currency;
			
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
			if($file_title_currency!='USD')
			{
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, date('Y-m-d'));
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, date('Y-m-d'));
			}
			$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "" ));
			
			$total_cost_in_usd_gross = $row_job_jrer['buy_local_currency_gross']/$final_exchange_rate;
			$total_cost_in_usd_net = $row_job_jrer['buy_local_currency_net']/$final_exchange_rate;
			$total_expected_cost_in_usd_net = $row_job_jrer['expected_buy_local_currency_net']/$final_exchange_rate;
			$total_variation_expected_and_actual_buying_cost_in_usd = $row_job_jrer['variation_expected_and_actual_buying']/$final_exchange_rate;
			
			$viewer->assign('TOTAL_COST_USD_GROSS' , number_format ( $total_cost_in_usd_gross , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_COST_IN_USD_NET' , number_format ( $total_cost_in_usd_net , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_EXPECTED_COST_USD_NET' , number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD' , number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "" ));
			
			//End of Expence
			// Added below code for selling summary
		    $jrer_selling_sum_sql = "SELECT sum(jrercf.cf_1232) as sell_customer_currency_gross, 
								     sum(jrercf.cf_1238) as sell_local_currency_gross,
									 sum(jrercf.cf_1240) as sell_local_currency_net, 
									 sum(jrercf.cf_1242) as expected_sell_local_currency_net,
									 sum(jrercf.cf_1244) as variation_expected_and_actual_selling,
									 sum(jrercf.cf_1246) as variation_expect_and_actual_profit
									 FROM `vtiger_jobexpencereportcf` as jrercf 
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON jrercf.jobexpencereportid= crmentityrel.relcrmid 
							 where crmentityrel.crmid=? and crmentityrel.module='Jobexp' and crmentityrel.relmodule='Jobexpencereport' and jrercf.cf_1457='Selling'";
							 
			// parentId = Job Id	
			//$job_id = $parentId;			 
			$params_selling = array($parentId);
			$result_selling = $adb->pquery($jrer_selling_sum_sql, $params_selling);
			$row_job_jrer_selling = $adb->fetch_array($result_selling);
			
			$viewer->assign('SELL_CUSTOMER_CURRENCY_GROSS' , number_format ( $row_job_jrer_selling['sell_customer_currency_gross'] , 2 ,  "." , "" ));
			$viewer->assign('SELL_LOCAL_CURRENCY_GROSS' , number_format ( $row_job_jrer_selling['sell_local_currency_gross'] , 2 ,  "." , "" ));
			$viewer->assign('SELL_LOCAL_CURRENCY_NET' , number_format ( $row_job_jrer_selling['sell_local_currency_net'] , 2 ,  "." , "" ));
			$viewer->assign('EXPECTED_SELL_LOCAL_CURRENCY_NET' , number_format ( $row_job_jrer_selling['expected_sell_local_currency_net'] , 2 ,  "." , "" ));
			$viewer->assign('VARIATION_EXPECT_AND_ACTUAL_PROFIT' , number_format ( $row_job_jrer_selling['variation_expect_and_actual_profit'] , 2 ,  "." , "" ));
			$viewer->assign('VARIATION_EXPECTED_AND_ACTUAL_SELLING' , number_format ( $row_job_jrer_selling['variation_expected_and_actual_selling'] , 2 ,  "." , "" ));
			
			$total_cost_in_usd_customer = $row_job_jrer_selling['sell_customer_currency_gross']/$final_exchange_rate;
			$total_cost_in_usd_sell_gross = $row_job_jrer_selling['sell_local_currency_gross']/$final_exchange_rate;
			$total_cost_in_usd_sell_net = $row_job_jrer_selling['sell_local_currency_net']/$final_exchange_rate;
			$total_expected_sell_in_usd_net = $row_job_jrer_selling['expected_sell_local_currency_net']/$final_exchange_rate;
			$total_variation_expected_and_actual_selling_cost_in_usd = $row_job_jrer_selling['variation_expected_and_actual_selling']/$final_exchange_rate;
			$total_variation_expect_and_actual_profit_cost_in_usd = $row_job_jrer_selling['variation_expect_and_actual_profit']/$final_exchange_rate;
			
			$viewer->assign('TOTAL_COST_IN_USD_CUSTOMER' , number_format ( $total_cost_in_usd_customer , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_COST_IN_USD_SELL_GROSS' , number_format ( $total_cost_in_usd_sell_gross , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_COST_IN_USD_SELL_NET' , number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "," ));
			$viewer->assign('TOTAL_EXPECTED_SELL_IN_USD_NET' , number_format ( $total_expected_sell_in_usd_net , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_VARIATION_EXPECT_AND_ACTUAL_PROFIT_COST_IN_USD' , number_format ( $total_variation_expect_and_actual_profit_cost_in_usd , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_SELLING_COST_IN_USD' , number_format ( $total_variation_expected_and_actual_selling_cost_in_usd , 2 ,  "." , "" ));
			
			//End of Selling
			// Expected Profit And Actual Profit in USD
			$m32 = $total_expected_sell_in_usd_net;
			$o19 = $total_expected_cost_in_usd_net;
			$expected_profit_usd = $m32 - $o19;
			
			$l32 = $total_cost_in_usd_sell_net/$final_exchange_rate;
			$n19 = $total_cost_in_usd_net/$final_exchange_rate;
			$actual_profit_usd = $l32 - $n19;
			$difference_of = $actual_profit_usd - $expected_profit_usd ;
			
			$viewer->assign('EXPECTED_PROFIT_USD' , number_format ( $expected_profit_usd , 2 ,  "." , "" ));
			$viewer->assign('ACTUAL_PROFIT_USD' , number_format ( $actual_profit_usd , 2 ,  "." , "" ));
			$viewer->assign('DIFFERENCE_OF' , number_format ( $difference_of , 2 ,  "." , "" ));

			//End of Expected Profit and Actual Profit in USD
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==1) 
			{
				//if(@$_REQUEST['grid']==1) {
				$expence_header[] = 'Action';
				$expence_header[] = 'Coordinator'; 	
				foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $selling_arr))
						{
							continue;
						}
						$expence_header[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
						//$RELATED_HEADERNAME = $header_info->get('name');
						//echo $model->getDisplayValue($RELATED_HEADERNAME);
						//echo "<br>";
					}				
				$viewer->assign('EXPENCE_HEADER' , implode(',',$expence_header));
				
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$i=0;
				foreach($models as $key => $model){
					if($model->getDisplayValue('cf_1457') == 'Selling'){
						continue; 
					}
					//$col_data['myname'] = '<a class="relationDelete"><i class="icon-trash alignMiddle" title="Delete"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Edit&amp;record=42414&amp;jrertype=expence&amp;cf_1457=Expence"><i class="icon-pencil alignMiddle" title="Edit"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Detail&amp;record=42414&amp;mode=showDetailViewByMode&amp;requestMode=full"><i class="icon-th-list alignMiddle" title="Complete Details"></i></a>';
					$action = '';
					if($relationModel->isDeletable()){
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						$action .='<a href="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="icon-pencil alignMiddle"></i></a>&nbsp;';
					}					
					$action .='<a href="'.$model->getFullDetailViewUrl().'"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
	
					$col_data['myname'] = $action;
					$col_data['coordinator'] ='';
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $selling_arr))
						{
							continue;
						}
						//$RELATED_HEADERNAME = $header_info->get('name')}
						//fieldname = $header_info->get('name')
						$RELATED_HEADERNAME = $header_info->get('name');
						$column_value = $model->getDisplayValue($RELATED_HEADERNAME);
						$col_data[$RELATED_HEADERNAME] = $column_value; 						
					}
					$col_data['send_to_head_of_department_for_approval'] = '';
					$col_data['head_of_department_approval_status'] = '';
					$col_data['send_to_payables_and_generate_payment_voucher'] = '';
					
					$buying['rows'][$i++] = $col_data;
					
				}
			return json_encode($buying);
			
			}
			
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==2) 
			{
				//if(@$_REQUEST['grid']==1) {
				$selling_header[] = 'Action';
				$selling_header[] = 'Coordinator'; 	
				foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $expence_arr))
						{
							continue;
						}
						$selling_header[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
						//$RELATED_HEADERNAME = $header_info->get('name');
						//echo $model->getDisplayValue($RELATED_HEADERNAME);
						//echo "<br>";
					}				
				$viewer->assign('SELLING_HEADER' , implode(',',$selling_header));
				
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$selling = array();
				$i=0;
				foreach($models as $key => $model){
					if($model->getDisplayValue('cf_1457') == 'Expence'){
						continue; 
					}
					
					$action = '';
					if($relationModel->isDeletable()){
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'"><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						$action .='<a href="'.$model->getEditViewUrl().'&jrertype=selling&cf_1457=Selling"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="icon-pencil alignMiddle"></i></a>&nbsp;';
					}					
					$action .='<a href="'.$model->getFullDetailViewUrl().'"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
	
					$col_data['myname'] = $action;
					
					//$col_data['myname'] = '';
					$col_data['coordinator'] ='';
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $expence_arr))
						{
							continue;
						}
						//$RELATED_HEADERNAME = $header_info->get('name')}
						//fieldname = $header_info->get('name')
						$RELATED_HEADERNAME = $header_info->get('name');
						$column_value = $model->getDisplayValue($RELATED_HEADERNAME);
						$col_data[$RELATED_HEADERNAME] = $column_value; 						
					}
					//$col_data['send_to_head_of_department_for_approval'] = '';
					//$col_data['head_of_department_approval_status'] = '';
					//$col_data['send_to_payables_and_generate_payment_voucher'] = '';
					
					$selling['rows'][$i++] = $col_data;
					
				}
			return json_encode($selling);
			
			}
			
			
			$expence_header[] = 'Action';
			$expence_header[] = 'Coordinator'; 
			
			$buying_field[] = array('name' => 'myname', 'frozen' =>true,  "align" => "center");
			$buying_field[] = array('name' => 'coordinator', 'index' => 'coordinator', 'frozen' => true);
			$i=0;
			
			foreach($header as $header_info)
				{
					if(in_array($header_info->get('column'), $selling_arr))
					{
						continue;
					}
					$expence_header[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
					//$RELATED_HEADERNAME = $header_info->get('name');
					//echo $model->getDisplayValue($RELATED_HEADERNAME);
					//echo "<br>";
					$RELATED_HEADERNAME = $header_info->get('name');
					$frozen = ($i==0 ? true:false);
					$buying_field[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>'100', 'frozen' => $frozen);
				$i++;
				}
			$buying_field[] = array('name' =>'send_to_head_of_department_for_approval', 'index' => 'send_to_head_of_department_for_approval');
			$buying_field[] = array('name' =>'head_of_department_approval_status', 'index' => 'head_of_department_approval_status');
			$buying_field[] = array('name' =>'send_to_payables_and_generate_payment_voucher', 'index' => 'send_to_payables_and_generate_payment_voucher');
				
			$expence_header[] ='Send to Head of department for approval';	
			$expence_header[] ='Head of department approval status';	
			$expence_header[] ='Send to payables and generate payment voucher';		
			
			$viewer->assign('EXPENCE_HEADER' , "'" .implode("','",$expence_header). "'");
			
			$viewer->assign('EXPENCE_FIELD' , json_encode($buying_field));
			//EXPENCE END
			
			// Selling initialization
			$selling_header[] = 'Action';
			$selling_header[] = 'Coordinator'; 
			
			$selling_field[] = array('name' => 'myname', 'frozen' =>true, "align" => "center");
			$selling_field[] = array('name' => 'coordinator', 'index' => 'coordinator', 'frozen' => true);
			$i=0;
			
			foreach($header as $header_info)
				{
					if(in_array($header_info->get('column'), $expence_arr))
					{
						continue;
					}
					$selling_header[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
					//$RELATED_HEADERNAME = $header_info->get('name');
					//echo $model->getDisplayValue($RELATED_HEADERNAME);
					//echo "<br>";
					$RELATED_HEADERNAME = $header_info->get('name');
					$frozen = ($i==0 ? true:false);
					$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>'100', 'frozen' => $frozen);
					$i++;
				}
			
			
			$viewer->assign('SELLING_HEADER' , "'" .implode("','",$selling_header). "'");			
			$viewer->assign('SELLING_FIELD' , json_encode($selling_field));
			// SELLING END							
		}
			
		return $viewer->view('RelatedList.tpl', $moduleName, 'true');
		
	}
}