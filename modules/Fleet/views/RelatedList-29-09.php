<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleet_RelatedList_View extends Vtiger_RelatedList_View {
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
			//'cf_1477', 'cf_1479',
			$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
			$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 'cf_1248');
			$viewer->assign('EXPENCE_ARR', $expence_arr);
			$viewer->assign('SELLING_ARR', $selling_arr);
			
			//$job_id 			  = $parentId;
			$fleet_expense  = $this->get_job_id_from_fleet($parentId);
			$job_id = $fleet_expense;
			$sourceModule_job 	= 'Job';	
			$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
			$current_user = Users_Record_Model::getCurrentUserModel();
			
			$count_parent_role = 4;
			if($current_user->get('is_admin')!='on')
			{
				$privileges   = $current_user->get('privileges');
				$parent_roles_arr = $privileges->parent_roles;				
				$count_parent_role = count($parent_roles_arr);
			}
			
			// Added below code for expence summary 
			$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross, 
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net, 
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 
							  
							 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet' and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'";
			
			//if($count_parent_role>3)
			//{
			//For Bakhytgul access, right now her role is GM. Later we can change.
				if($current_user->get('roleid')=='H3')
				{
					$user_query =' AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'" ' ;
				}
				else if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
				{
					$jrer_sum_sql .=' AND vtiger_jobexpencereport.owner_id = "'.$current_user->getId().'" AND vtiger_jobexpencereport.user_id = "'.$current_user->getId().'" ' ;
				}
				else
				{
					$jrer_sum_sql .=' AND vtiger_jobexpencereport.owner_id = "'.$current_user->getId().'" ' ;
				}
			//}
							 
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
			//$parent_id_of_expense_report = $parentId; 
			//$result_rel = $adb->pquery('SELECT * FROM `vtiger_crmentityrel` where relcrmid=?', array($parent_id_of_expense_report));
			//$row_job_jrer_rel = $adb->fetch_array($result_rel);
			//$job_id = $row_job_jrer_rel['crmid'];
			// parentId = Job Id	
			//$job_id = $parentId;	
			$fleet_expense  = $this->get_job_id_from_fleet($parentId);
			$job_id = $fleet_expense;
			$job_info = get_job_details($job_id);
			//$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
			
			//For checking user is main owner or sub user
			$company_id = $current_user->get('company_id');
			$job_office_id = $job_info_detail->get('cf_1188');
			$current_user_office_id = $current_user->get('location_id');
			if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
			{
				if($job_office_id==$current_user_office_id){
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info['cf_1186']);	
				}
				else{
					$db_sub = PearDatabase::getInstance();
					$query_sub = 'SELECT sub_jrer_file_title from vtiger_jobtask WHERE job_id=? and user_id=? limit 1';
					//$job_info->get('record_id') = jobid
					$params_sub = array($job_info_detail->get('record_id'), $current_user->getId());
					$result_sub = $db_sub->pquery($query_sub,$params_sub);
					$file_title_info = $db_sub->fetch_array($result_sub);
					$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency((empty($file_title_info['sub_jrer_file_title']) ? $company_id : $file_title_info['sub_jrer_file_title']));
				}
			}
			else{						
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info['cf_1186']);
			}
			
			$file_title_currency = $reporting_currency;
			
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
			
			
			$jrer_last_sql =  "SELECT vtiger_crmentity.modifiedtime FROM vtiger_jobexpencereportcf  
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Fleet' 
							 AND crmentityrel.relmodule='Jobexpencereport' order by vtiger_crmentity.modifiedtime DESC limit 1";
			// parentId = Fleet Id	
			$fleet_id = $parentId;
			$params = array($parentId);
			$result_last = $adb->pquery($jrer_last_sql, $params);
			$row_jrer_last = $adb->fetch_array($result_last);
			$count_last_modified = $adb->num_rows($result_last);
			
			$exchange_rate_date  = date('Y-m-d');
			if($count_last_modified>0)
			{
				$modifiedtime = $row_jrer_last['modifiedtime'];
				$modifiedtime = strtotime($row_jrer_last['modifiedtime']);
				$exchange_rate_date = date('Y-m-d', $modifiedtime);
			}		
			
			if($file_title_currency!='USD')
			{
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
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
			
			//Selling Start for gross
			$jrer_selling_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1232) as sell_customer_currency_gross, 
								     sum(vtiger_jobexpencereportcf.cf_1238) as sell_local_currency_gross,
									 sum(vtiger_jobexpencereportcf.cf_1240) as sell_local_currency_net, 
									 sum(vtiger_jobexpencereportcf.cf_1242) as expected_sell_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1244) as variation_expected_and_actual_selling,
									 sum(vtiger_jobexpencereportcf.cf_1246) as variation_expect_and_actual_profit
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 
							  
							 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Fleet' 
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'";
			
			//if($count_parent_role>3)
			//{
				if($current_user->get('roleid')=='H3')
				{
					$user_query =' AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'" ' ;
				}
				else if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
				{
					$jrer_selling_sum_sql .=' AND vtiger_jobexpencereport.owner_id = "'.$current_user->getId().'" AND vtiger_jobexpencereport.user_id = "'.$current_user->getId().'" ' ;
				}
				else
				{
					$jrer_selling_sum_sql .=' AND vtiger_jobexpencereport.owner_id = "'.$current_user->getId().'" ' ;
				}
			//}
							 
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
			$viewer->assign('TOTAL_COST_IN_USD_SELL_NET' , number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_EXPECTED_SELL_IN_USD_NET' , number_format ( $total_expected_sell_in_usd_net , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_VARIATION_EXPECT_AND_ACTUAL_PROFIT_COST_IN_USD' , number_format ( $total_variation_expect_and_actual_profit_cost_in_usd , 2 ,  "." , "" ));
			$viewer->assign('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_SELLING_COST_IN_USD' , number_format ( $total_variation_expected_and_actual_selling_cost_in_usd , 2 ,  "." , "" ));
			
			//End of Selling
			// Expected Profit And Actual Profit in USD
			$m32 = $total_expected_sell_in_usd_net;
			$o19 = $total_expected_cost_in_usd_net;
			$expected_profit_usd = $m32 - $o19;
			
			//$l32 = $total_cost_in_usd_sell_net/$final_exchange_rate;
			//$n19 = $total_cost_in_usd_net/$final_exchange_rate;
			$l32 = $total_cost_in_usd_sell_net;
			$n19 = $total_cost_in_usd_net;
			$actual_profit_usd = $l32 - $n19;
			$difference_of = $actual_profit_usd - $expected_profit_usd;
			
			$viewer->assign('EXPECTED_PROFIT_USD' , number_format ( $expected_profit_usd , 2 ,  "." , "" ));
			$viewer->assign('ACTUAL_PROFIT_USD' , number_format ( $actual_profit_usd , 2 ,  "." , "" ));
			$viewer->assign('DIFFERENCE_OF' , number_format ( $difference_of , 2 ,  "." , "" ));
			//Selling end for gross
			
			$fleet_id = $parentId;
			$sourceModule_fleet = 'Fleet';	
			$fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, $sourceModule_fleet);
			
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==1) 
			{				
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$i=0;
				//$models = $relationListView->getEntries($pagingModel);
				foreach($models as $key => $model){
					
					$expense_record= $model->getInstanceById($model->getId());	
					
					if($expense_record->getDisplayValue('cf_1457') == 'Selling'){
						continue; 
					}
								
					
					$head_of_department_status = $expense_record->get('cf_1973');
					
					//$col_data['myname'] = '<a class="relationDelete"><i class="icon-trash alignMiddle" title="Delete"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Edit&amp;record=42414&amp;jrertype=expence&amp;cf_1457=Expence"><i class="icon-pencil alignMiddle" title="Edit"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Detail&amp;record=42414&amp;mode=showDetailViewByMode&amp;requestMode=full"><i class="icon-th-list alignMiddle" title="Complete Details"></i></a>';
					$action = '';
					
					if($relationModel->isDeletable()){
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						$action .='<a href="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="icon-pencil alignMiddle"></i></a>&nbsp;';
					}
										
					$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
					if($expense_record->getDisplayValue('cf_1214')!='')
					{
						$action .='&nbsp;<a href="PV_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="icon-print alignMiddle"></i></a>&nbsp;';
						//$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&expense=PV" target="_blank"><i title="Print Payment Voucher" class="icon-print alignMiddle"></i></a>&nbsp;';
					}
					
					$col_data['jobexpencereportid'] = $model->getId();
					
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
						$column_value = $expense_record->getDisplayValue($RELATED_HEADERNAME);
						$col_data[$RELATED_HEADERNAME] = $column_value;						
					}
					$col_data['send_to_head_of_department_for_approval'] = $model->get('b_send_to_head_of_department_for_approval');
					//$col_data['head_of_department_approval_status'] = $model->get('b_head_of_department_approval_status');
					$col_data['head_of_department_approval_status'] = $expense_record->get('cf_1973');
					$col_data['send_to_payables_and_generate_payment_voucher'] =$model->get('b_payables_approval_status');
					
					
					$buying['rows'][$i++] = $col_data;
					
				}
			
			return json_encode($buying);
			
			}
			
			$expence_header[] = 'Expense ID';
			$buying_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
			
			$expence_header[] = 'Action';
			//$expence_header[] = 'Coordinator'; 
			
			$buying_field[] = array('name' => 'myname', 'frozen' =>true,  "align" => "center");
			//$buying_field[] = array('name' => 'coordinator', 'index' => 'coordinator', 'frozen' => true);
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
				$frozen = ($i<4 ? true:false);
				$buying_field[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => ($header_info->get('column')=='cf_1367' ? '200':'100' ), 'frozen' => $frozen);
				$i++;
			}
				
			//$buying_field[] = array('name' =>'send_to_head_of_department_for_approval', 'index' => 'send_to_head_of_department_for_approval');
			$buying_field[] = array('name' => 'send_to_head_of_department_for_approval', "align" => "center", "editable" => true, 
								"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
			
			$buying_field[] = array('name' =>'head_of_department_approval_status', 'index' => 'head_of_department_approval_status', "align" => "center", 'width'=>'80');
			$buying_field[] = array('name' =>'send_to_payables_and_generate_payment_voucher', 'index' => 'send_to_payables_and_generate_payment_voucher');
			
			$expence_header[] ='Send to Head of Department and Generate Payment Voucher';	
			$expence_header[] ='Head of Department Status';	
			$expence_header[] ='Payables Status';		
						
			$viewer->assign('EXPENCE_HEADER' , "'" .implode("','",$expence_header). "'");
			$viewer->assign('EXPENCE_FIELD' , json_encode($buying_field));
			//EXPENCE END
			
			
			//For Coordinator
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==2) 
			{
						
			//$buying = new StdClass;
			//$buying['page'] = 1;
			//$buying['total'] = 7;
			//$buying['records'] = 7;
			$selling = array();
			$i=0;
			$check_invoice_instruction_no = array();
			//$models = $relationListView->getEntries($pagingModel);
			foreach($models as $key => $model){
				
				$selling_record= $model->getInstanceById($model->getId());
				
				if($selling_record->getDisplayValue('cf_1457') == 'Expence'){
					continue; 
				}
				
				
				
				$action = '';
				if($relationModel->isDeletable()){
					$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'"><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
				}					
				if($relationModel->isEditable()){
					$action .='<a href="'.$model->getEditViewUrl().'&jrertype=selling&cf_1457=Selling"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="icon-pencil alignMiddle"></i></a>&nbsp;';
				}					
				$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=selling&cf_1457=Selling&jobid='.$job_id.'"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
				
				$instruction_no = $model->get('invoice_instruction_no');
				$s_generate_invoice_instruction = $selling_record->getDisplayValue('cf_1248');
				if(!in_array($model->get('invoice_instruction_no'), $check_invoice_instruction_no) && !empty($instruction_no) && ($s_generate_invoice_instruction=='Yes'))
				{
					$check_invoice_instruction_no[]=$instruction_no;
					$action .='<a href="invoice_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$model->get('cf_1445').'&invoice_instruction_no='.$model->get('invoice_instruction_no').'"><i title="Generate Invoice Instruction" class="icon-print alignMiddle"></i></a>&nbsp;';
				}
				
				
				$col_data['jobexpencereportid'] = $model->getId();					
				$col_data['myname'] = $action;					
				//$col_data['myname'] = '';
				$col_data['coordinator'] ='';
				
				foreach($header as $header_info)
				{
					if(in_array($header_info->get('column'), $expence_arr))
					{
						continue;
					}
					
					$RELATED_HEADERNAME = $header_info->get('name');
					$column_value = $selling_record->getDisplayValue($RELATED_HEADERNAME);
					$col_data[$RELATED_HEADERNAME] = $column_value; 						
				}
													
				$col_data['cf_1248'] = $selling_record->get('cf_1248');
				$col_data['cf_1250'] = $selling_record->get('cf_1250');
				//$col_data['cf_1248'] =$model->getDisplayValue('cf_1248');
				//$col_data['cf_1248'] = $model->getId();
				
				$selling['rows'][$i++] = $col_data;
				
			}
		return json_encode($selling);
		
		}
		
			// Selling initialization
			$selling_header[] = 'Selling ID';
			$selling_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
			$selling_header[] = 'Action';
			//$selling_header[] = 'Coordinator'; 
			
			
			$selling_field[] = array('name' => 'myname', 'frozen' =>true, "align" => "center");
			//$selling_field[] = array('name' => 'coordinator', 'index' => 'coordinator', 'frozen' => true);
			$i=0;
			
			foreach($header as $header_info)
				{
					if(in_array($header_info->get('column'), $expence_arr))
					{
						continue;
					}
					
					if($header_info->get('name')=='cf_1248')
					{
						continue;
					}
					
					$selling_header[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
					//$RELATED_HEADERNAME = $header_info->get('name');
					//echo $model->getDisplayValue($RELATED_HEADERNAME);
					//echo "<br>";			
						
					$RELATED_HEADERNAME = $header_info->get('name');
					$frozen = ($i<4 ? true:false);
					$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>($header_info->get('column')=='cf_1445' ? '200':'100' ), 'frozen' => $frozen);
					$i++;
				}
			$selling_header[] = 'Generate Invoice Instruction and Send to Invoicing coordinator';	
			$selling_header[] = 'Invoicing Status';	
			$selling_field[] = array('name' => 'cf_1248', "align" => "center", "editable" => true, 
								"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
			$selling_field[] = array('name' => 'cf_1250', "align" => "center", 'width'=>'80');
			
			
			$viewer->assign('SELLING_HEADER' , "'" .implode("','",$selling_header). "'");	
			$viewer->assign('SELLING_FIELD' , json_encode($selling_field));
			// SELLING END
			
			
			//For Profit Share
			$j=0;
			$profit_share = array();
			
			foreach($models as $key => $model){
				$selling_record= $model->getInstanceById($model->getId());
				
				if($selling_record->getDisplayValue('cf_1457') == 'Expence')
				{
					continue; 
				}
				
				foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $expence_arr))
						{
							continue;
						}
						//$RELATED_HEADERNAME = $header_info->get('name')}
						//fieldname = $header_info->get('name')
						$RELATED_HEADERNAME_P = $header_info->get('name');
						
						$column_value_P = $selling_record->getDisplayValue($RELATED_HEADERNAME_P);
						
						$col_data_P[$RELATED_HEADERNAME_P] = $column_value_P; 						
					}
					$col_data_P['cf_1477_location_id'] = $selling_record->get('cf_1477');
					$col_data_P['cf_1479_department_id'] = $selling_record->get('cf_1479');	
					
					$profit_share[] = $col_data_P;			
			 }
			 
						 
			 $profit_share_check = array();
			 $profit_share_data = array();
			 
			  $sum_of_cost = 0;
			  $sum_of_external_selling = 0;
			  $sum_of_job_profit = 0;
			  $sum_of_internal_selling = 0;
			  $sum_of_profit_share = 0;
			  $sum_of_net_profit = 0;
			 
			 if(!empty($profit_share))
			 {
				 foreach($profit_share as $key => $p_share)
				 {
					 $dept_branch = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];					 
					 if(!in_array($dept_branch, $profit_share_check))
					 {
						$profit_share_check[] = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];						
						$brach_department_name = $p_share['cf_1477'].' '.$p_share['cf_1479'];						
										
						$adb_buy_local = PearDatabase::getInstance();										   
						$sum_buy_local_currency_net =  "SELECT sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net
									 					FROM `vtiger_jobexpencereport` 
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 														left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
							 							where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet' 
							 	   						and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
								   						and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? AND vtiger_jobexpencereport.owner_id=? AND vtiger_jobexpencereportcf.cf_2195=?
								   						";
						$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId(), $fleet_info_detail->get('cf_2001'));//cf_2001=truck id
						
						$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
						$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);
						  
														 
						$adb_sell_local = PearDatabase::getInstance();	
						$sum_sell_local_currency_net =  "SELECT sum(vtiger_jobexpencereportcf.cf_1240) as sell_local_currency_net
									 					FROM `vtiger_jobexpencereport` 
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 														left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  							where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet' 
														and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Selling'
														and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? AND vtiger_jobexpencereport.owner_id = ? AND vtiger_jobexpencereportcf.cf_2195=?
														";
						$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId(), $fleet_info_detail->get('cf_2001')); //parentid=truck id
						$result_sell_locall = $adb_sell_local->pquery($sum_sell_local_currency_net, $params_sell_local);
						$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_array($result_sell_locall);				   
						
						
						$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid 
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job' 
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?	
												";				   
						
						$params_internal = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);
						
						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);	
						
											
						$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];	
						$cost = $cost_local/$final_exchange_rate;
						
						$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];	
						$external_selling = $s_sell_local_currency_net/$final_exchange_rate;
						
						$job_profit = 0;
						if($job_info_detail->get('assigned_user_id')==$current_user->getId())
						{							
							$job_profit = $external_selling - $cost;
						}
						else{
							if($s_sell_local_currency_net<=0)
							{
								$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;	
							}								
							else{
								$job_profit = $external_selling - $cost;
							}
						}
						$brach_department = $p_share['cf_1479_department_id'].' '.$p_share['cf_1477_location_id'];
						$job_branch_department = $job_info_detail->get('cf_1190').' '.$job_info_detail->get('cf_1188');
						
						if(trim($brach_department)==trim($job_branch_department))
						{
							$profit_share_col = 0;
						}
						else{
							$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
						}
						$net_profit = $job_profit - $profit_share_col;				   
						
						$profit_share_data[] = array('brach_department' => $brach_department_name, 
													 'cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'external_selling' => number_format ( $external_selling , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'office_id' => $p_share['cf_1477_location_id'], 
													 'department_id' => $p_share['cf_1479_department_id'], 
													 'job_id' => $parentId,
													 'profit_share_col' => ((trim($brach_department)!=trim($job_branch_department)) ? number_format($profit_share_col, 2 ,  "." , "," ) :''),
													 'net_profit' => ($job_info_detail->get('assigned_user_id')==$current_user->getId() ? number_format ( $net_profit , 2 ,  "." , "," ):''),
													 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling_type' => ((trim($brach_department)!=trim($job_branch_department)) ? 'text' : 'hidden' )
													 );
						
						$sum_of_cost += $cost;
						$sum_of_external_selling +=$external_selling;
						$sum_of_job_profit +=$job_profit;
						$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_profit_share +=$profit_share_col;
						$sum_of_net_profit +=$net_profit;
													 
					 }					 
				 }
			 }
			 
		
			$viewer->assign('SUM_OF_COST' , number_format($sum_of_cost , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_EXTERNAL_SELLING' , number_format($sum_of_external_selling , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_JOB_PROFIT' , number_format($sum_of_job_profit , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_INTERNAL_SELLING' , number_format($sum_of_internal_selling , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_PROFIT_SHARE' , number_format($sum_of_profit_share , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_NET_PROFIT' , ($job_info_detail->get('assigned_user_id')==$current_user->getId()? number_format($sum_of_net_profit , 2 ,  "." , "," ) : ''));
			$viewer->assign('NET_PROFIT_LABEL' , ($job_info_detail->get('assigned_user_id')==$current_user->getId() ? 'Net profit' : ''));
			$viewer->assign('PROFIT_SHARE_LABEL' , ($job_info_detail->get('assigned_user_id')==$current_user->getId() ? 'Profit Share Received' : 'Profit Share'));
			//echo "<pre>";
			//print_r($profit_share_data);
			//exit;
			
			$viewer->assign('PROFIT_SHARE' , $profit_share_data);
			$viewer->assign('JOB_ID', $job_id);
			
			$truck_id = $fleet_info_detail->get('cf_2001');
			$sourceModule_truck = 'Truck';	
			$truck_info_detail = Vtiger_Record_Model::getInstanceById($truck_id, $sourceModule_truck);
			$viewer->assign('TRUCK_INFO_DETAIL', $truck_info_detail);
			//End Profit Share
			
			
		}
		
		
		
		
		if($relatedModuleName == 'Jobexpencereport')
		{
			$privileges  = $current_user->get('privileges');
			$parent_roles = $privileges->parent_roles;
			$coordinator_department_head_role = @$parent_roles[3];
			$count_parent_role = count($parent_roles);
			
			$viewer->assign('COORDINATOR_DEPARTMENT_HEAD_ROLE', ($coordinator_department_head_role!='' ? $coordinator_department_head_role : 0));
			
			return $viewer->view('RelatedListJRERFleet.tpl', $moduleName, 'true');
		}
		else{
			return $viewer->view('RelatedList.tpl', $moduleName, 'true');
		}
	}
	
	public function get_job_id_from_fleet($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
										
		 $checkjob = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1 
				  							where rel1.relcrmid='".$recordId."'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}
}