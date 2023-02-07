<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

class Job_RelatedList_View extends Vtiger_RelatedList_View {
	
	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		$permissions[] = array('module_parameter' => 'relatedModule', 'action' => 'DetailView');
		
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}
	
	function process(Vtiger_Request $request) {
		
		global $adb;
		//$adb->setDebug(true);
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');
		$label = $request->get('tab_label');

		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
		$moduleFields = $relatedModuleModel->getFields();
        $searchParams = $request->get('search_params');
        
        if(empty($searchParams)) {
            $searchParams = array();
		}
		elseif(isset($_SESSION['JER_SAVE']) && !empty($_SESSION['JER_SAVE']))
		{
			unset($_SESSION['JER_SAVE']);
			$searchParams = array();
		}
        
        $whereCondition = array();
        
        foreach($searchParams as $fieldListGroup){
            foreach($fieldListGroup as $fieldSearchInfo){
                $fieldModel = $moduleFields[$fieldSearchInfo[0]];
                $tableName = $fieldModel->get('table');
                $column = $fieldModel->get('column');
                $whereCondition[$fieldSearchInfo[0]] = array($tableName.'.'.$column, $fieldSearchInfo[1],  $fieldSearchInfo[2], $fieldSearchInfo[3]);
                
                $fieldSearchInfoTemp= array();
                $fieldSearchInfoTemp['searchValue'] = $fieldSearchInfo[2];
                $fieldSearchInfoTemp['fieldName'] = $fieldName = $fieldSearchInfo[0];
                $fieldSearchInfoTemp['comparator'] = $fieldSearchInfo[1];
                $searchParams[$fieldName] = $fieldSearchInfoTemp;
            }
       }
       
		$requestedPage = $request->get('page');
		if(empty($requestedPage)) {
			$requestedPage = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page',$requestedPage);

		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
        
        if(!empty($whereCondition))
            $relationListView->set('whereCondition', $whereCondition);
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'icon-chevron-down';
            $faSortImage = "fa-sort-desc";
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'icon-chevron-up';
            $faSortImage = "fa-sort-asc";
		}
		if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder',$sortOrder);
		}
		$relationListView->tab_label = $request->get('tab_label');
		//$models = $relationListView->getEntries($pagingModel);
		if($relatedModuleName =='Jobexpencereport')
		{
			$QRY_GROUB_BY = '0';
			$JRER_TYPE = '0';	
			$models = $relationListView->getEntries($pagingModel, $JRER_TYPE, $QRY_GROUB_BY);
		}
		else{
			$models = $relationListView->getEntries($pagingModel);
		}

		$links = $relationListView->getLinks();
		$header = $relationListView->getHeaders();
		$_SESSION['header'] = $header;
		
		$noOfEntries = $pagingModel->get('_relatedlistcount');
		if(!$noOfEntries) {
			$noOfEntries = count($models);
		}
		$relationModel = $relationListView->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relationField = $relationModel->getRelationField();
        
        $fieldsInfo = array();
        foreach($moduleFields as $fieldName => $fieldModel){
            $fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
        }

		$viewer = $this->getViewer($request);

		// Custom Code for Job Costing Summary
		// 19-10-2015 :: Mehtab Code
		$for_JRER_expected_profit_usd = 0;
		if($relatedModuleName =='JER' || $relatedModuleName =='Jobexpencereport')
		{
			
			//$relatedModuleName =='Jobexpencereport' // For Expected Profit
			// Added below code for expected cost and revenue summary with expected profit in local currency and USD currency
			$jer_sum_sql =  "SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency FROM `vtiger_jercf` as jercf 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER'";
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
			//$job_info = get_job_details($job_id);
			
			$job_info = $parentRecordModel;
			//$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
			//$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info['cf_1186']);
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
			$file_title_currency = $reporting_currency;
			
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
			
			$jer_last_sql =  "SELECT vtiger_crmentity.modifiedtime FROM `vtiger_jercf` as jercf 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Job' 
							 AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1";
			// parentId = Job Id	
			$job_id = $parentId;		 
			$params = array($parentId);
			$result_last = $adb->pquery($jer_last_sql, $params);
			$row_costing_last = $adb->fetch_array($result_last);
			$count_last_modified = $adb->num_rows($result_last);
			
			$exchange_rate_date  = date('Y-m-d');
			if($count_last_modified>0)
			{
				$modifiedtime = $row_costing_last['modifiedtime'];
				$modifiedtime = strtotime($row_costing_last['modifiedtime']);
				$exchange_rate_date = date('Y-m-d', $modifiedtime);
			}
			
			if($file_title_currency!='USD')
			{			
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);			
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
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
			
			$for_JRER_expected_profit_usd = $total_revenue_usd - $total_cost_usd;
			
			$viewer->assign('EXPECTED_PROFIT_USD', $expected_profit_usd);	
								 
		}		
		// End of Custom Code for Job Costing Summary

        $viewer->assign('RELATED_FIELDS_INFO', json_encode($fieldsInfo));
		$viewer->assign('IS_CREATE_PERMITTED', isPermitted($relatedModuleName, 'CreateView'));
		$viewer->assign('RELATED_RECORDS' , $models);
		$viewer->assign('PARENT_RECORD', $parentRecordModel);
		$viewer->assign('RELATED_LIST_LINKS', $links);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE', $relatedModuleModel);
		$viewer->assign('RELATED_ENTIRES_COUNT', $noOfEntries);
		$viewer->assign('RELATION_FIELD', $relationField);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MARKETING');

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
        $viewer->assign('FASORT_IMAGE',$faSortImage);
		$viewer->assign('COLUMN_NAME',$orderBy);

		$viewer->assign('IS_EDITABLE', $relationModel->isEditable());
		$viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('PARENT_ID', $parentId);
        $viewer->assign('SEARCH_DETAILS', $searchParams);
		$viewer->assign('TAB_LABEL', $request->get('tab_label'));


		if($relatedModuleName =='Jobexpencereport')
		{
			//'cf_1477', 'cf_1479',
			
			$jer_count_sql =  "SELECT count(*) as total_jer FROM `vtiger_crmentity` 
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER' 
							 AND crmentityrel.crmid=?";
			// parentId = Job Id	
			$job_id = $parentId;		 
			$params = array($parentId);
			$result_count_jer = $adb->pquery($jer_count_sql, $params);
			$row_count_jer = $adb->fetch_array($result_count_jer);
			$viewer->assign('JER_COUNT', $row_count_jer['total_jer']);
			
			
			$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
			$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 
								 'cf_1248','cf_2691',
								 );
			
			/*
			$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1457');
			$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 
								 'cf_1248','cf_2691',
								 'cf_1353','cf_1351','cf_1349','cf_1341','cf_1210',
								 );
			*/					 
			$viewer->assign('EXPENCE_ARR', $expence_arr);
			$viewer->assign('SELLING_ARR', $selling_arr);
			
			$job_id 			  = $parentId;
		    $sourceModule_job 	= 'Job';	
	 	    $job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
			$current_user = Users_Record_Model::getCurrentUserModel();
			
			$count_parent_role = 4;
			if($current_user->get('is_admin')!='on')
			{
				$privileges   = $current_user->get('privileges');
				$parent_roles_arr = $privileges->parent_roles;				
				$count_parent_role = count($parent_roles_arr);
				
				if($_REQUEST['module']=='Job' && $count_parent_role==0)
				{
					$role_id =  $current_user->get('roleid');
					$depth_role = "SELECT * FROM vtiger_role where roleid='".$role_id."' ";
					//$row_depth = mysql_fetch_array($depth_role);
					$result_role = $adb->pquery($depth_role, array());
					$row_depth = $adb->fetch_array($result_role);
					$count_parent_role = $row_depth['depth'];
				}
			}			
				
			//include("include/Exchangerate/exchange_rate_class.php");
			//$parent_id_of_expense_report = $parentId; 
			//$result_rel = $adb->pquery('SELECT * FROM `vtiger_crmentityrel` where relcrmid=?', array($parent_id_of_expense_report));
			//$row_job_jrer_rel = $adb->fetch_array($result_rel);
			//$job_id = $row_job_jrer_rel['crmid'];
			// parentId = Job Id	
			$job_id = $parentId;	
			//$job_info = get_job_details($job_id);
			$job_info = $parentRecordModel;
			//$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
			
			//For checking user is main owner or sub user
			$company_id = $current_user->get('company_id');
			$job_office_id = $job_info_detail->get('cf_1188');
			$current_user_office_id = $current_user->get('location_id');
			if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
			{
				if($job_office_id==$current_user_office_id){
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));	
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
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
			}
			
			if($current_user->get('is_admin')=='on' || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 )
			{
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
			}
			
			$file_title_currency = $reporting_currency;
			
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);		
			
			
			
			
			//End of Selling
			// Expected Profit And Actual Profit in USD
			//$m32 = $total_expected_sell_in_usd_net;
			//$o19 = $total_expected_cost_in_usd_net;
			//$expected_profit_usd = $m32 - $o19;
			
			//$l32 = $total_cost_in_usd_sell_net;
			//$n19 = $total_cost_in_usd_net;
			//$actual_profit_usd = $l32 - $n19;
			//$difference_of = $actual_profit_usd - $expected_profit_usd;
			
			//$expected_profit_usd = $expected_profit_usd;
			//$actual_profit_usd = 0;
			//$difference_of = 0;
			
			//$viewer->assign('EXPECTED_PROFIT_USD' , number_format ( $expected_profit_usd , 2 ,  "." , "" ));
			//$viewer->assign('ACTUAL_PROFIT_USD' , number_format ( $actual_profit_usd , 2 ,  "." , "" ));
			//$viewer->assign('DIFFERENCE_OF' , number_format ( $difference_of , 2 ,  "." , "" ));
			
			
			$width_exp_in_arr = array('cf_1477', 'cf_1479' , 'cf_1367', 'cf_1214', 'cf_1216', 'cf_1210', 'cf_1339', 'cf_1341', 'cf_1345', 'cf_1222');
			$width_exp_val = array('cf_1477'=> '51', 'cf_1479' => '50', 'cf_1367' => '180', 'cf_1214' => '50', 'cf_1216' => '70', 'cf_1210'=>'70', 
							   'cf_1339'=>'50', 'cf_1341'=>'50', 'cf_1345'=>'53', 'cf_1222' => '50');			
			
			//For Coordinator
			$width_sell_in_arr = array('cf_1477', 'cf_1479', 'cf_1355', 'cf_1445', 'cf_1228', 'cf_1230', 'cf_1234', 'cf_1236');
			$width_sell_val = array('cf_1477'=>'51', 'cf_1479'=>'50', 'cf_1355'=>'70', 'cf_1445'=>'200', 'cf_1228'=>'50', 'cf_1230'=>'50', 'cf_1234'=>'50', 'cf_1236'=>'50');			
				
			
			
			//For Profit Share
			$j=0;
			
			$profit_share_e = array();
			$profit_share_check_new_e = array();
			
			$JOB_EXPENSE_ID = '';		
			foreach($models as $key => $model){
				
				if($model->get('assigned_user_id')==$current_user->getId())
				{
					$JOB_EXPENSE_ID  = $model->getId();
				}
				//Because selling section only add main owner
				$expense_record= $model->getInstanceById($model->getId());
				
				if($expense_record->getDisplayValue('cf_1457') == 'Selling')
				{
					continue; 
				}
				
				$dept_branch_new_e = $expense_record->get('cf_1477').'-'.$expense_record->get('cf_1479');	
				 if(!in_array($dept_branch_new_e, $profit_share_check_new_e))
				 {
					$profit_share_check_new_e[] = $expense_record->get('cf_1477').'-'.$expense_record->get('cf_1479');		
						
					$col_data_P_e['cf_1477'] = $expense_record->getDisplayValue('cf_1477');
					$col_data_P_e['cf_1479'] = $expense_record->getDisplayValue('cf_1479');
					$col_data_P_e['cf_1477_location_id'] = $expense_record->get('cf_1477');
					$col_data_P_e['cf_1479_department_id'] = $expense_record->get('cf_1479');	
					
					$profit_share_e[] = $col_data_P_e;	
				 }			
			 }
			
			$profit_share = array();
			$profit_share_check_new = array();
			
			
			foreach($models as $key => $model){
				
				//Because selling section only add main owner
				$selling_record= $model->getInstanceById($model->getId());
				
				/*if($selling_record->getDisplayValue('cf_1457') == 'Expence')
				{
					continue; 
				}*/
				
				$dept_branch_new = $selling_record->get('cf_1477').'-'.$selling_record->get('cf_1479');	
				 if(!in_array($dept_branch_new, $profit_share_check_new))
				 {
					$profit_share_check_new[] = $selling_record->get('cf_1477').'-'.$selling_record->get('cf_1479');		
						
					$col_data_P['cf_1477'] = $selling_record->getDisplayValue('cf_1477');
					$col_data_P['cf_1479'] = $selling_record->getDisplayValue('cf_1479');
					$col_data_P['cf_1477_location_id'] = $selling_record->get('cf_1477');
					$col_data_P['cf_1479_department_id'] = $selling_record->get('cf_1479');	
					
					$profit_share[] = $col_data_P;	
				 }			
			 }
			 //$profit_share = array_merge_recursive($profit_share, $profit_share_e);
			 
						
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
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid 06-15-2017								   
						$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
															   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
															   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 					FROM `vtiger_jobexpencereport` 
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 														left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
							 							where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job' 
							 	   						and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
								   						and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? 
														
								   						";
						
						if($current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
						{
							$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);
							$sum_buy_local_currency_net .=' AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'" ' ;
						}
						else{
							$sum_buy_local_currency_net .= " AND vtiger_jobexpencereport.owner_id=? ";
							$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						}
														
						//$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
						$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
						
						$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {
							
							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);
							
							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];	
							
							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];
							
							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
							  //$row_cur = mysql_fetch_array($q_cur);
							  $result_q_cur = $adb->pquery($q_cur, array());
							  $row_cur = $adb->fetch_array($result_q_cur);
							  $Cur = $row_cur['currency_code'];
							}
							$b_exchange_rate = 1;						
							if(!empty($buy_invoice_date))
							{
								if($file_title_currency!='USD')
								{
									$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $buy_invoice_date);
								}else{
									$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $buy_invoice_date);
								}
							}
							
							if($file_title_currency!='USD')
							{
							$cost += $cost_local/$b_exchange_rate;
							}
							else{
							$cost += $cost_local;	
							}				
							
						}
						  
														 
						$adb_sell_local = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid	
						$sum_sell_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net,
																vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
																vtiger_jobexpencereportcf.cf_1234 as currency_id
									 					FROM `vtiger_jobexpencereport` 
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 														LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  							WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job' 
														AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
														AND vtiger_jobexpencereportcf.cf_1477=? AND vtiger_jobexpencereportcf.cf_1479=? 
														
														";
						if($current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
						{
						$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);
						}
						else{
						$sum_sell_local_currency_net .=" AND vtiger_jobexpencereport.owner_id = ? ";	
						$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						}
														
						//$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						$result_sell_locall = $adb_sell_local->pquery($sum_sell_local_currency_net, $params_sell_local);
						
						$numRows_sell_profit = $adb_sell_local->num_rows($result_buy_locall);
						
						$external_selling = 0;
						for($ji=0; $ji< $adb_sell_local->num_rows($result_sell_locall); $ji++ ) {
							$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_row($result_sell_locall,$ji);
							//$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_array($result_sell_locall);				  
							
							$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];	
							$sell_invoice_date = @$row_jrer_sell_local_currency_net['sell_invoice_date'];
							
							$CurId = $row_jrer_sell_local_currency_net['currency_id'];
							if ($CurId) {
							 // $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
							//  $row_cur = mysql_fetch_array($q_cur);

							  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
							  //$row_cur = mysql_fetch_array($q_cur);
							  $result_q_cur = $adb->pquery($q_cur, array());
							  $row_cur = $adb->fetch_array($result_q_cur);
							  $Cur = $row_cur['currency_code'];
							  
							}						
							
							$s_exchange_rate = 1;
							if(!empty($sell_invoice_date))
							{
								if($file_title_currency!='USD')
								{
									$s_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $sell_invoice_date);
								}else{
									$s_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $sell_invoice_date);
								}
							}
							
							$new_rate = $s_exchange_rate;						
							if($file_title_currency!='USD')
							{
								$external_selling += $s_sell_local_currency_net/$s_exchange_rate;
							}
							else{
								$external_selling += $s_sell_local_currency_net;
							}
							
											
						}				 
						
						
						$adb_internal = PearDatabase::getInstance();	
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 												LEFT JOIN vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid 
		 					  					WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job' 
												AND vtiger_crmentityrel.relmodule='Jobexp' AND vtiger_jobexpcf.cf_1257=? AND vtiger_jobexpcf.cf_1259=?	
												";				   
						
						$params_internal = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);
						
						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);	
						
						/*					
						$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];	
						$cost = $cost_local/$final_exchange_rate;
						
						$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];	
						$external_selling = $s_sell_local_currency_net/$final_exchange_rate;
						*/
						
						$job_profit = 0;
						if($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
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
							//$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
							if(empty($row_jrer_internal_selling['internal_selling']) || $row_jrer_internal_selling['internal_selling']<=0)
							{
								$profit_share_col =  0;
							}
							else{
								$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
							}
							
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
													 'net_profit' => (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3' || $count_parent_role <= 3  || $current_user->get('roleid')=='H2') ? number_format ( $net_profit , 2 ,  "." , "," ):''),
													 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling_type' => ((trim($brach_department)!=trim($job_branch_department)) ? 'text' : 'hidden' ),
													 //'fleet_field_readonly' => (($p_share['cf_1479_department_id']=='85844') ? 'readonly="readonly"' : '')
													 'fleet_field_readonly' => (($p_share['cf_1479_department_id']=='85844') ? '' : '')
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
			$viewer->assign('SUM_OF_NET_PROFIT' , (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')? number_format($sum_of_net_profit , 2 ,  "." , "," ) : ''));
			$viewer->assign('NET_PROFIT_LABEL' , (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2') ? 'Net profit' : ''));
			$viewer->assign('PROFIT_SHARE_LABEL' , (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2') ? 'Profit Share Received' : 'Profit Share'));
			//echo "<pre>";
			//print_r($profit_share_data);

			//exit;
			// Expected Profit And Actual Profit in USD
			
			
			if($current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
			{
				$expected_profit_usd = $for_JRER_expected_profit_usd;
			}
			else if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
			{
				$expected_profit_usd = 0;
			}
			else
			{
				$expected_profit_usd = $for_JRER_expected_profit_usd;
			}

			
			
			//$expected_profit_usd = $for_JRER_expected_profit_usd;
			$actual_profit_usd = $sum_of_job_profit;
			$difference_of = $actual_profit_usd - $expected_profit_usd ;
			
			$viewer->assign('EXPECTED_PROFIT_USD' , number_format ( $expected_profit_usd , 2 ,  "." , "" ));
			$viewer->assign('ACTUAL_PROFIT_USD' , number_format ( $actual_profit_usd , 2 ,  "." , "" ));
			$viewer->assign('DIFFERENCE_OF' , number_format ( $difference_of , 2 ,  "." , "" ));
			
			
			$viewer->assign('PROFIT_SHARE' , $profit_share_data);
			$viewer->assign('JOB_ID', $job_id);
			$viewer->assign('JOB_EXPENSE_ID', $JOB_EXPENSE_ID);
			//End Profit Share
			
			//Data from Job Task
			$viewer->assign('JOB_OWNER', FALSE);
			if($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
			{
				$adb_jobtask = PearDatabase::getInstance();	
				//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
				$query_jobtask = "SELECT * from vtiger_jobtask 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobtask.jobtaskid 
								 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 INNER JOIN vtiger_users ON vtiger_users.id = vtiger_jobtask.user_id
								 LEFT  JOIN vtiger_locationcf ON vtiger_locationcf.locationid = vtiger_users.location_id
								 LEFT  JOIN vtiger_departmentcf ON vtiger_departmentcf.departmentid = vtiger_users.department_id
								 LEFT JOIN vtiger_jobtaskcf as vtiger_jobtaskcf on vtiger_jobtaskcf.jobtaskid=vtiger_jobtask.jobtaskid 
								 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job' 
								 AND vtiger_crmentityrel.relmodule='JobTask' AND vtiger_jobtask.job_id=? AND vtiger_jobtask.job_owner='0'	
								 ";				   
				
				$params_jobtask = array($parentId, $parentId);
				
				$result_jobtask = $adb_jobtask->pquery($query_jobtask, $params_jobtask);
				//$row_jobtask[] = $adb_jobtask->fetch_array($result_jobtask);
				$row_jobtask = array();
				for($i=0; $i< $adb_jobtask->num_rows($result_jobtask); $i++ ) {
					$row_jobtask[] = $adb_jobtask->fetch_row($result_jobtask,$i);
				}
							
				$viewer->assign('JOB_ASSIGNED_USER', $row_jobtask);
				$viewer->assign('JOB_OWNER', TRUE);	
			}
			//End of Data from Job Task						
		}

		$viewer->assign('JOB_INFO_FLAG', FALSE);			
		if(!empty($relatedModuleName))
		{
			$recordId = $request->get('record');
			$job_id = $recordId;
			$current_user = Users_Record_Model::getCurrentUserModel();
			$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $moduleName);
			
			//$job_user_info = Users_Record_Model::getCurrentUserModel($job_info_detail->get('assigned_user_id'), 'Users');
			$job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');
			$viewer->assign('JOB_USER_INFO', $job_user_info);	
			$viewer->assign('JOB_INFO_DETAIL', $job_info_detail);
			$viewer->assign('JOB_INFO_FLAG', TRUE);	
			
			
			$adb_job_quotes = PearDatabase::getInstance();
			$query_job_quotes = 'SELECT * FROM `vtiger_crmentityrel` where relcrmid=?';
			$params_job_quotes = array($recordId);
				
			$result_job_quotes = $adb_job_quotes->pquery($query_job_quotes, $params_job_quotes);
			$row_job_quotes = $adb_job_quotes->fetch_array($result_job_quotes);
			$viewer->assign('JOB_QUOTATION_REF','');
			if(isset($row_job_quotes['crmid']))
			{
				$viewer->assign('JOB_QUOTATION_REF','GL/QT - '.$row_job_quotes['crmid']);
			}
			
		}

		

		if($relatedModuleName =='JER')
		{			
			return $viewer->view('RelatedListCosting.tpl', $moduleName, 'true');			
		}
		elseif($relatedModuleName == 'Jobexpencereport')
		{
					
			$privileges  = $current_user->get('privileges');
			$parent_roles = $privileges->parent_roles;
			if(empty($parent_roles)) { $parent_roles = array();}
			$coordinator_department_head_role = @$parent_roles[3];
			$count_parent_role = count($parent_roles);
			
			if($_REQUEST['module']=='Job' && $count_parent_role==0)
			{
				$role_id =  $current_user->get('roleid');
				//$depth_role = mysql_query("SELECT * FROM vtiger_role where roleid='".$role_id."' ");
				//$row_depth = mysql_fetch_array($depth_role);
				$depth_role = "SELECT * FROM vtiger_role where roleid='".$role_id."' ";
				$result_depth_role = $adb->pquery($depth_role, array());
				$row_depth = $adb->fetch_array($result_depth_role);
				$count_parent_role = $row_depth['depth'];
			}
			
			$viewer->assign('COORDINATOR_DEPARTMENT_HEAD_ROLE', ($coordinator_department_head_role!='' ? $coordinator_department_head_role : 0));
							
			if($count_parent_role>3)
			{
				if($current_user->get('roleid')=='H185')
				{					
					//SELLING INVOICING initialization
					$selling_header_invoice[] = 'Selling ID';
					$selling_field_invoice[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					
					$selling_header_invoice[] = 'Action';
					$selling_field_invoice[] = array('name' => 'myname', 'frozen' =>true, "align" => "center" , 'width' => '60');
		
					$selling_header_invoice[] = 'GL Account';
					$selling_field_invoice[] = array('name' => 'gl_account', "align" => "center", 'width'=>'80', 'frozen'=>true);
					$selling_header_invoice[] = 'AR GL Account';			
					$selling_field_invoice[] = array('name' => 'ar_gl_account', "align" => "center", 'width'=>'80', 'frozen'=>true);
					$selling_header_invoice[] = 'Invoice No';			
					$selling_field_invoice[] = array('name' => 'invoice_no', "align" => "center", 'width'=>'80', 'frozen'=>true);
					//$selling_field[] = array('name' => 'myname', 'frozen' =>true, "align" => "center");
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
							
							$selling_header_invoice[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
							//$RELATED_HEADERNAME = $header_info->get('name');
							//echo $model->getDisplayValue($RELATED_HEADERNAME);
							//echo "<br>";			
								
							$RELATED_HEADERNAME = $header_info->get('name');
							$frozen = ($i<4 ? true:false);
							//$selling_field_invoice[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>($header_info->get('column')=='cf_1445' ? '200':'100' ), 'frozen' => $frozen);
							$selling_field_invoice[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>(in_array($header_info->get('column'), $width_sell_in_arr) ? $width_sell_val[$header_info->get('column')] : '100'), 'frozen' => $frozen);
							
							$i++;
						}
					$selling_header_invoice[] = "Date";
					$selling_field_invoice[] = array("name" => 'sdate',"index" => 'sdate',"width"=>150, "editable" =>true);
					
					$selling_header_invoice[] = "Generate Invoice";	
					$selling_field_invoice[] = array("name"=>'invoice',"index"=>'invoice', "width" => "200", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));
					
					
					$selling_header_invoice[] = 'Local Invoice #';
					$selling_field_invoice[] = array('name' => 'local_invoice_no', "align" => "center", 'width'=>'120', 'sortable' =>'false',
													 'editable' => true, 'edittype' => 'textarea', 'editoptions' => array('rows'=>'3', 'cols' => '12'));
													 
					//$selling_header[] = 'Generate Invoice Instruction and Send to Invoicing coordinator';	
					$selling_header_invoice[] = 'Invoicing Status';	
					//$selling_field[] = array('name' => 'cf_1248', "align" => "center", "editable" => true, 
					//					"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
					$selling_field_invoice[] = array('name' => 'cf_1250', "align" => "center", 'width'=>'80');
					
					
					
					
					$viewer->assign('SELLING_HEADER_INVOICING' , "'" .implode("','",$selling_header_invoice). "'");
					$viewer->assign('SELLING_FIELD_INVOICING' , json_encode($selling_field_invoice));
					//SELLING INVOICING END	
					if($current_user->get('company_id')=='85756')
					{
						return $viewer->view('RelatedListJRERInvoicing.tpl', $moduleName, 'true');
					}
					//&& $current_user->get('location_id')=='85805'
					elseif($current_user->get('company_id')=='85757')
					{
						return $viewer->view('RelatedListJRERInvoicingLocalKZ.tpl', $moduleName, 'true');	
					}
					else{
						return $viewer->view('RelatedListJRERInvoicingLocal.tpl', $moduleName, 'true');	
					}
				}
				else if($current_user->get('roleid')=='H184')
				{
					//EXPENSE PAYABLES
					$expence_header_payables[] = 'Expense ID';
					$buying_field_payables[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					
					$expence_header_payables[] = 'Action';			
					$buying_field_payables[] = array('name' => 'myname', 'frozen' =>true,  'width'=>'50', "align" => "center", 'width' => '60' );
					
					$expence_header_payables[] = 'Collective PV';
					$buying_field_payables[] = array('name' => 'mynameinv', 'frozen' =>true,  "align" => "center", 'width' => '80');
					
					$expence_header_payables[] = 'GL Account';
					$buying_field_payables[] = array('name' => 'b_gl_account', "align" => "center", 'width'=>'80', 'frozen'=>true);
					$expence_header_payables[] = 'Accounts Payable GL Account';			
					$buying_field_payables[] = array('name' => 'b_ar_gl_account', "align" => "center", 'width'=>'80', 'frozen'=>true);
					
					$expence_header_payables[] = 'Vendor Purchase Order No.';
					$buying_field_payables[] = array('name' => 'vpo_order_no', "align" => "center", 'width'=>'80', 'frozen'=>true);
					$i=0;
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $selling_arr))
						{
							continue;
						}					
						
						$expence_header_payables[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
						//$RELATED_HEADERNAME = $header_info->get('name');
						//echo $model->getDisplayValue($RELATED_HEADERNAME);
						//echo "<br>";
						$RELATED_HEADERNAME = $header_info->get('name');
						$frozen = ($i<4 ? true:false);
						//$buying_field_payables[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => ($header_info->get('column')=='cf_1367' ? '200':'100' ), 'frozen' => $frozen);
						$buying_field_payables[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => (in_array($header_info->get('column'), $width_exp_in_arr) ? $width_exp_val[$header_info->get('column')] : '100' ), 'frozen' => $frozen);
		
						$i++;
					}
						
					$buying_field_payables[] = array('name' =>'payables_action', 'index' => 'payables_action', "width" => "200", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));
					$expence_header_payables[] ='Payables Action';
					
					$buying_field_payables[] = array('name' =>'cf_1975', 'index' => 'payables_approval_status', "align" => "center", 'width'=>'80');
					$expence_header_payables[] ='Payables Status';
						
								
					$viewer->assign('EXPENCE_HEADER_PAYABLES' , "'" .implode("','",$expence_header_payables). "'");
					$viewer->assign('EXPENCE_FIELD_PAYABLES' , json_encode($buying_field_payables));			
					//EXPENSE PAYABLES END
					
					return $viewer->view('RelatedListJRERPayables.tpl', $moduleName, 'true');
				}
				else{
					
					//EXPENCE
					$expence_header[] = 'Expense ID';
					$buying_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					
					$expence_header[] = 'Action';
					//$expence_header[] = 'Coordinator'; 
					
					$buying_field[] = array('name' => 'myname', 'frozen' =>true,  "align" => "center", 'width' => '60');
					
					$expence_header[] = 'Collective PV';
					$buying_field[] = array('name' => 'mynameinv', 'frozen' =>true,  "align" => "center", 'width' => '80');
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
						$buying_field[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => (in_array($header_info->get('column'), $width_exp_in_arr) ? $width_exp_val[$header_info->get('column')] : '100'), 'frozen' => $frozen);
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
					
					// Selling initialization
					$selling_header[] = 'Selling ID';
					$selling_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					$selling_header[] = 'Action';
					//$selling_header[] = 'Coordinator'; 		
					$selling_field[] = array('name' => 'myname', 'frozen' =>true, "align" => "center" , 'width' => '60');
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
							//$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>($header_info->get('column')=='cf_1445' ? '200':'100' ), 'frozen' => $frozen);
							$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>(in_array($header_info->get('column'), $width_sell_in_arr) ? $width_sell_val[$header_info->get('column')] : '100' ), 'frozen' => $frozen);
							$i++;
						}
					
					/*	
					$selling_header[] = 'Preview Invoice Instruction Before Sending';			
					$selling_field[] = array('name' => 'cf_2439', "align" => "center", "editable" => false, 
										"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => true ), "editoptions" => array("value" => 'Yes:No'));
					*/
						
					$selling_header[] = 'Choose For Preview/Generate/Recall Invoices';	
					$selling_header[] = 'Invoicing Status';	
					$selling_field[] = array('name' => 'cf_1248', "align" => "center", "editable" => true, 
										"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
					$selling_field[] = array('name' => 'cf_1250', "align" => "center", 'width'=>'80');
					
					
					$viewer->assign('SELLING_HEADER' , "'" .implode("','",$selling_header). "'");	
					$viewer->assign('SELLING_FIELD' , json_encode($selling_field));
					// SELLING END
				
					return $viewer->view('RelatedListJRER.tpl', $moduleName, 'true');
				}
			}
			else{
				
				//For department head or branch head if job belongs to own
				if($job_info_detail->get('assigned_user_id')==$current_user->getId())
				{
					$expence_header[] = 'Expense ID';
					$buying_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					
					$expence_header[] = 'Action';
					//$expence_header[] = 'Coordinator'; 
					
					$buying_field[] = array('name' => 'myname', 'frozen' =>true,  "align" => "center", 'width' => '60');
					
					$expence_header[] = 'Collective PV';
					$buying_field[] = array('name' => 'mynameinv', 'frozen' =>true,  "align" => "center", 'width' => '80');
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
						$buying_field[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => (in_array($header_info->get('column'), $width_exp_in_arr) ? $width_exp_val[$header_info->get('column')] : '100'), 'frozen' => $frozen);
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
					
					// Selling initialization
					$selling_header[] = 'Selling ID';
					$selling_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					$selling_header[] = 'Action';
					//$selling_header[] = 'Coordinator'; 		
					$selling_field[] = array('name' => 'myname', 'frozen' =>true, "align" => "center" , 'width' => '60');
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
						//$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>($header_info->get('column')=='cf_1445' ? '200':'100' ), 'frozen' => $frozen);
						$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>(in_array($header_info->get('column'), $width_sell_in_arr) ? $width_sell_val[$header_info->get('column')] : '100' ), 'frozen' => $frozen);
						$i++;
					}
				
					$selling_header[] = 'Preview Invoice Instruction Before Sending';			
					$selling_field[] = array('name' => 'cf_2439', "align" => "center", "editable" => false, 
										"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => true ), "editoptions" => array("value" => 'Yes:No'));
					
						
					$selling_header[] = 'Generate Invoice Instruction and Send to Invoicing coordinator';	
					$selling_header[] = 'Invoicing Status';	
					$selling_field[] = array('name' => 'cf_1248', "align" => "center", "editable" => true, 
										"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
					$selling_field[] = array('name' => 'cf_1250', "align" => "center", 'width'=>'80');
					
					
					$viewer->assign('SELLING_HEADER' , "'" .implode("','",$selling_header). "'");	
					$viewer->assign('SELLING_FIELD' , json_encode($selling_field));
					// SELLING END
					
					return $viewer->view('RelatedListJRER.tpl', $moduleName, 'true');
				}
				else
				{
					
					//EXPENSE HEAD
					$expence_header_head[] = 'Expense ID';
					$buying_field_head[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					
					$expence_header_head[] = 'Action';
					//$expence_header[] = 'Coordinator';			
					$buying_field_head[] = array('name' => 'myname', 'frozen' =>true,  "align" => "center", 'width' => '60');
					
					$expence_header_head[] = 'Collective PV';
					$buying_field_head[] = array('name' => 'mynameinv', 'frozen' =>true,  "align" => "center", 'width' => '80');
					//$buying_field[] = array('name' => 'coordinator', 'index' => 'coordinator', 'frozen' => true);
					$i=0;
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $selling_arr))
						{
							continue;
						}					
						
						$expence_header_head[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
						//$RELATED_HEADERNAME = $header_info->get('name');
						//echo $model->getDisplayValue($RELATED_HEADERNAME);
						//echo "<br>";
						$RELATED_HEADERNAME = $header_info->get('name');
						$frozen = ($i<4 ? true:false);
						//$buying_field_head[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => ($header_info->get('column')=='cf_1367' ? '200':'100' ), 'frozen' => $frozen);
						$buying_field_head[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => (in_array($header_info->get('column'), $width_exp_in_arr) ? $width_exp_val[$header_info->get('column')] : '100' ), 'frozen' => $frozen);
						$i++;
					}
						
					//$buying_field[] = array('name' =>'send_to_head_of_department_for_approval', 'index' => 'send_to_head_of_department_for_approval');
					//$buying_field_head[] = array('name' => 'send_to_head_of_department_for_approval', "align" => "center", "editable" => true, "edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
					
					$buying_field_head[] = array('name' =>'head_of_department_approval_status', 'index' => 'head_of_department_approval_status', "align" => "center", 'width'=>'80');
					$buying_field_head[] = array('name' =>'send_to_payables_and_generate_payment_voucher', 'index' => 'send_to_payables_and_generate_payment_voucher', "width" => "200", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));
					$buying_field_head[] = array('name' =>'payables_approval_status', 'index' => 'payables_approval_status', "align" => "center", 'width'=>'80');
					//$expence_header_head[] ='Send to Head of Department and Generate Payment Voucher';	
					$expence_header_head[] ='Head of Department Status';	
					$expence_header_head[] ='Send to Payables';	
					$expence_header_head[] ='Payables Status';
						
								
					$viewer->assign('EXPENCE_HEADER_HEAD' , "'" .implode("','",$expence_header_head). "'");
					$viewer->assign('EXPENCE_FIELD_HEAD' , json_encode($buying_field_head));			
					//EXPENSE HEAD END
					
					// Selling initialization
					$selling_header[] = 'Selling ID';
					$selling_field[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
					$selling_header[] = 'Action';
					//$selling_header[] = 'Coordinator'; 		
					$selling_field[] = array('name' => 'myname', 'frozen' =>true, "align" => "center" , 'width' => '60');
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
							//$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>($header_info->get('column')=='cf_1445' ? '200':'100' ), 'frozen' => $frozen);
							$selling_field[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>(in_array($header_info->get('column'), $width_sell_in_arr) ? $width_sell_val[$header_info->get('column')] : '100' ), 'frozen' => $frozen);
							$i++;
				}
					
			$selling_header[] = 'Preview Invoice Instruction Before Sending';			
			$selling_field[] = array('name' => 'cf_2439', "align" => "center", "editable" => true, 
								"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
			
				
			$selling_header[] = 'Generate Invoice Instruction and Send to Invoicing coordinator';	
			$selling_header[] = 'Invoicing Status';	
			$selling_field[] = array('name' => 'cf_1248', "align" => "center", "editable" => true, 
								"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
			$selling_field[] = array('name' => 'cf_1250', "align" => "center", 'width'=>'80');
			
			
			$viewer->assign('SELLING_HEADER' , "'" .implode("','",$selling_header). "'");	
			$viewer->assign('SELLING_FIELD' , json_encode($selling_field));
					// SELLING END
					
					return $viewer->view('RelatedListJRERHead.tpl', $moduleName, 'true');
				}
			}		
		}elseif($relatedModuleName == 'PackagingMaterial'){
			$recordId = $request->get('record');
			$viewer->assign('JOB_ID' , $recordId);
			return $viewer->view('RelatedListPackagingMaterial.tpl', $moduleName, 'true');			
		}
		else { 
			return $viewer->view('RelatedList.tpl', $moduleName, 'true');
		}
        
	}




	//For Expense Coordinator
	function process_expense(Vtiger_Request $request)
	{
		//$models = $_SESSION['models'];
		
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
		
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		
		/*
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
		}*/

			
			$orderBy = $request->get('orderby');
			$sortOrder = $request->get('sortorder');
			if($sortOrder == 'ASC') {
				$nextSortOrder = 'DESC';
				$sortImage = 'icon-chevron-down';
				$faSortImage = "fa-sort-desc";
			} else {
				$nextSortOrder = 'ASC';
				$sortImage = 'icon-chevron-up';
				$faSortImage = "fa-sort-asc";
			}
			if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder',$sortOrder);
			}
			$relationListView->tab_label = $request->get('tab_label');
	
		//$models = $relationListView->getEntries($pagingModel);
		$JRER_TYPE = 'Expence';
		$models = $relationListView->getEntries($pagingModel, $JRER_TYPE);		
		$header = $_SESSION['header'];		
		
		$relationModel = $relationListView->getRelationModel();	
		
		$current_user = Users_Record_Model::getCurrentUserModel();	
		
		$job_id 			  = $parentId;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		
		$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
							 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 
							 'cf_1248','cf_2691');
	
						 
				
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==1) 
		{				
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$check_buying_invoice_no = array();
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
										
					if($relationModel->isDeletable() && ($expense_record->get('cf_1973')=='' || $expense_record->get('cf_1973')=='Declined' || $expense_record->get('cf_1975')=='Declined' )){
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					//if($relationModel->isEditable() && ($expense_record->get('cf_1973')=='' || $expense_record->get('cf_1973')=='Declined' || $expense_record->get('cf_1975')=='Declined' )){
					if($relationModel->isEditable() && ($expense_record->get('cf_1975')!='Approved' && $expense_record->get('cf_1453')!='85900' && $expense_record->get('cf_1453')!='1512294' )  && ($job_info_detail->get('cf_2197') == "No Costing" || $job_info_detail->get('cf_2197') == "In Progress" || $job_info_detail->get('cf_2197') == "Revision") ){	
						$action .='<a name="relationEdit" data-url="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="fa fa-pencil alignMiddle"></i></a>&nbsp;';
					}
					//For view					
					//$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
					$action .='<a href="index.php?module=Jobexpencereport&relatedModule=Documents&view=Detail&record='.$model->getId().'&mode=showRelatedList&tab_label=Documents"><i title="Upload Supporting Document" class="fa fa-th-list alignMiddle"></i></a>&nbsp;';					
					if($expense_record->getDisplayValue('cf_1214')!='')
					{
						//$action .='&nbsp;<a href="PV_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&expense=JPV" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
					}
					
					$action_inv = '';
					if(!in_array($expense_record->get('cf_1212'), $check_buying_invoice_no))
					{
						$check_buying_invoice_no[] = $expense_record->get('cf_1212');
						if($expense_record->getDisplayValue('cf_1214')!='' && $expense_record->get('cf_1212')!='')
						{
							$action_inv ='&nbsp;<a href="PV_export_inv_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						}
					}
					
					$col_data['jobexpencereportid'] = $model->getId();
					
					if($expense_record->get('assigned_user_id')!=$current_user->getId())
					{
						$action = '';
						$action_inv = '';
					}
					$action_inv = '';
					
					$col_data['myname'] = $action;
					$col_data['mynameinv'] = $action_inv;
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
					
					$fleettrip_id = $model->get('fleettrip_id');
					
					if(!empty($fleettrip_id))
					{
						$col_data['cf_1212'] = '<a href="index.php?module=Fleettrip&relatedModule=Roundtrip&view=Detail&record='.$fleettrip_id.'&mode=showRelatedList&tab_label=Round%20Trip" title="Fleet Trip" target="_blank">'.$expense_record->getDisplayValue('cf_1212').'</a>';
					}
					
					$wagontrip_id = $model->get('wagontrip_id');
					
					if(!empty($wagontrip_id))
					{
						$col_data['cf_1212'] = '<a href="index.php?module=WagonTrip&relatedModule=RailwayFleet&view=Detail&record='.$wagontrip_id.'&mode=showRelatedList&tab_label=Railway%20Fleet" title="Fleet Trip" target="_blank">'.$expense_record->getDisplayValue('cf_1212').'</a>';
					}					
					
					$col_data['send_to_head_of_department_for_approval'] = $model->get('b_send_to_head_of_department_for_approval');
					
					//$col_data['head_of_department_approval_status'] = $model->get('b_head_of_department_approval_status');
					$col_data['head_of_department_approval_status'] = $expense_record->get('cf_1973');
					$col_data['send_to_payables_and_generate_payment_voucher'] =$model->get('b_payables_approval_status');
					
					
					$buying['rows'][$i++] = $col_data;
					
				}			
			return json_encode($buying);			
		}	
		
	}
	
	//For Expense Head
	function process_expense_head(Vtiger_Request $request)
	{
		
		global $adb;
		//$adb->setDebug(true);
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
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
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
		
		//$models = $relationListView->getEntries($pagingModel);
		$JRER_TYPE = 'Expence';
		$models = $relationListView->getEntries($pagingModel, $JRER_TYPE);
		
		$relationModel = $relationListView->getRelationModel();	
		
		$header = $_SESSION['header'];		
		
		
		$current_user = Users_Record_Model::getCurrentUserModel();	
		
		$job_id 			  = $parentId;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		
		$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 'cf_1248','cf_2691');
				
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==4) 
		{
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$check_buying_invoice_no = array();
				$i=0;
				//$models = $relationListView->getEntries($pagingModel);
				foreach($models as $key => $model){
					
					$expense_record= $model->getInstanceById($model->getId());	
					
					if($expense_record->getDisplayValue('cf_1457') == 'Selling'){
						continue; 
					}
					
					//$col_data['myname'] = '<a class="relationDelete"><i class="icon-trash alignMiddle" title="Delete"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Edit&amp;record=42414&amp;jrertype=expence&amp;cf_1457=Expence"><i class="icon-pencil alignMiddle" title="Edit"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Detail&amp;record=42414&amp;mode=showDetailViewByMode&amp;requestMode=full"><i class="icon-th-list alignMiddle" title="Complete Details"></i></a>';
					$action = '';
					if($relationModel->isDeletable()){
						//Commented due to security check	
						//$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						//commented due to security check
						//$action .='<a href="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="icon-pencil alignMiddle"></i></a>&nbsp;';
					}					
					$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="fa fa-th-list alignMiddle"></i></a>&nbsp;';			  
					
					if($expense_record->getDisplayValue('cf_1214')!='')
					{
						//$action .='&nbsp;<a href="PV_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&expense=JPV" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
					}
					
					$action_inv = '';
					if(!in_array($expense_record->get('cf_1212'), $check_buying_invoice_no))
					{
						$check_buying_invoice_no[] = $expense_record->get('cf_1212');
						if($expense_record->getDisplayValue('cf_1214')!='')
						{
							//$action_inv ='&nbsp;<a href="PV_export_inv_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
							//$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&expense=JPV" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
						}
					}
					$action_inv = '';
					
					$col_data['jobexpencereportid'] = $model->getId();
					
					$col_data['myname'] = $action;
					$col_data['mynameinv'] = $action_inv;
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
					$fleettrip_id = $model->get('fleettrip_id');
					
					if(!empty($fleettrip_id))
					{
						$col_data['cf_1212'] = '<a href="index.php?module=Fleettrip&relatedModule=Roundtrip&view=Detail&record='.$fleettrip_id.'&mode=showRelatedList&tab_label=Round%20Trip" title="Fleet Trip" target="_blank">'.$expense_record->getDisplayValue('cf_1212').'</a>';
					}
					
					$wagontrip_id = $model->get('wagontrip_id');
					
					if(!empty($wagontrip_id))
					{
						$col_data['cf_1212'] = '<a href="index.php?module=WagonTrip&relatedModule=RailwayFleet&view=Detail&record='.$wagontrip_id.'&mode=showRelatedList&tab_label=Railway%20Fleet" title="Fleet Trip" target="_blank">'.$expense_record->getDisplayValue('cf_1212').'</a>';
					}
					//$col_data['send_to_head_of_department_for_approval'] = $model->get('b_send_to_head_of_department_for_approval');
					$col_data['head_of_department_approval_status'] = $model->get('b_head_of_department_approval_status');
					$col_data['send_to_payables_and_generate_payment_voucher'] = $model->get('b_send_to_payables_and_generate_payment_voucher');
					
					$col_data['payables_approval_status'] = $model->get('b_payables_approval_status'); 
					
					
					$buying['rows'][$i++] = $col_data;
					
				}
			
			return json_encode($buying);
			
			}		
	}
	
	function process_expense_payable(Vtiger_Request $request)
	{
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
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
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
		
		//$models = $relationListView->getEntries($pagingModel);
		
		$JRER_TYPE = 'Expence';
		$models = $relationListView->getEntries($pagingModel, $JRER_TYPE);
		
		$relationModel = $relationListView->getRelationModel();	
		
		$header = $_SESSION['header'];
		
		$current_user = Users_Record_Model::getCurrentUserModel();	
		
		$job_id 			  = $parentId;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 'cf_1248','cf_2691');
	
			
		
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==5) 
		{
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$check_buying_invoice_no = array();
				$i=0;
				
				$ui_type_array = array('cf_1214', 'cf_1345');
				
				//$models = $relationListView->getEntries($pagingModel);
				foreach($models as $key => $model){
					
					$expense_record= $model->getInstanceById($model->getId());
					
					//echo "<pre>";
					//print_r($expense_record);
					
					if($expense_record->getDisplayValue('cf_1975')=='')
					{
						continue; 
					}			
					
					if($expense_record->getDisplayValue('cf_1457') == 'Selling'){
						continue; 
					}
					
					$makpal_currency = array(1);
					$makpal_account_type = array('Bank R', 'Cash R');
					$makpal_company = array('85757', '85772', '85756');
					//$yuliya_om_currency = array(2 , 13, 14, 28);
					$yuliya_om_currency_not = array(1);
					$currency_id = $expense_record->get('cf_1345');	
					
					$company_account_type = '';
					$company_account_type_id = $expense_record->get('cf_1214');
					if(!empty($company_account_type_id))
					{						
					$CompanyAccountType  = Vtiger_Record_Model::getInstanceById($expense_record->get('cf_1214'), 'CompanyAccountType');
					$company_account_type = $CompanyAccountType->get('name');
					}
					
					//Aksuirik = 430
 					if($current_user->getId()==430)
					{											
						if($company_account_type!='Cash N' || $company_account_type!='Cash D')
						{
							continue;
						}
					}
					elseif($current_user->getId()==422 ) //Aigerim = 422  || $current_user->getId()==562
					{
						if($company_account_type!='Bank N' || $company_account_type!='Bank D')
						{
							continue;
						}
					}
					elseif($current_user->getId()==447) // Anel = 447
					{						
						if($company_account_type=='Bank N' || $company_account_type=='Bank D')
						{
							continue;
						}
					}
					//elseif($current_user->getId()==463) // Makpal = 463
					elseif($current_user->getId()==420) // Maral = 420
					{
						//if(!in_array($currency_id, $makpal_currency) || ($job_info_detail['cf_1186']!='85757'))
						if(!in_array($currency_id, $makpal_currency) || !in_array($job_info_detail->get('cf_1186'), $makpal_company))
						{
							continue;
						}
						//85757=kz
						//cf_1186=file title						
					}
					elseif($current_user->getId()==414) // Yulia= 414
					{
						if(in_array($currency_id, $yuliya_om_currency_not) || ($job_info_detail->get('cf_1186')!='85757'))
						{
							continue;
						}
					}			
					
					//$col_data['myname'] = '<a class="relationDelete"><i class="icon-trash alignMiddle" title="Delete"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Edit&amp;record=42414&amp;jrertype=expence&amp;cf_1457=Expence"><i class="icon-pencil alignMiddle" title="Edit"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Detail&amp;record=42414&amp;mode=showDetailViewByMode&amp;requestMode=full"><i class="icon-th-list alignMiddle" title="Complete Details"></i></a>';
					$action = '';
					if($relationModel->isDeletable()){
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						$action .='<a href="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="fa fa-pencil alignMiddle"></i></a>&nbsp;';
					}					
					$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="fa fa-th-list alignMiddle"></i></a>&nbsp;';			  
					$action = '';
					$action ='<a href="index.php?module=Jobexpencereport&relatedModule=Documents&view=Detail&record='.$model->getId().'&mode=showRelatedList&tab_label=Documents"><i title="Upload Supporting Document" class="fa fa-th-list alignMiddle"></i></a>&nbsp;';					
					
					if($expense_record->getDisplayValue('cf_1214')!='')
					{
						//$action .='&nbsp;<a href="PV_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&expense=JPV" target="_blank" ><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
					}
					
					$action_inv = '';
					if(!in_array($expense_record->get('cf_1212'), $check_buying_invoice_no))
					{
						$check_buying_invoice_no[] = $expense_record->get('cf_1212');
						if($expense_record->getDisplayValue('cf_1214')!='')
						{
							$action_inv ='&nbsp;<a href="PV_export_inv_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						}
					}
					$action_inv = '';
					$col_data['jobexpencereportid'] = $model->getId();
					
					$vpo = '<a href="VPO_export_pdf.php?record='.$model->get('vpoid').'" >'.$model->get('vpo_order_no').'</a>';
					
					$col_data['myname'] = $action;
					$col_data['mynameinv'] = $action_inv;
					//$col_data['coordinator'] ='';
					$col_data['b_gl_account'] = $model->get('b_gl_account');
					$col_data['b_ar_gl_account'] = $model->get('b_ar_gl_account');
					$vpo_order_no = $model->get('vpo_order_no');
					$col_data['vpo_order_no'] = (!empty($vpo_order_no)? $vpo:'');				
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $selling_arr))
						{
							continue;
						}					
						
						$RELATED_HEADERNAME = $header_info->get('name');
						$column_value = $expense_record->getDisplayValue($RELATED_HEADERNAME);
						$col_data[$RELATED_HEADERNAME] = $column_value;						
					}
					
					$col_data['payables_action'] = $model->get('b_confirmed_send_to_accounting_software');
					$payable_status = $expense_record->getDisplayValue('cf_1975');
					$col_data['cf_1975'] = $payable_status;
					
					/*if($payable_status=='Approved' || $payable_status=='Declined')
					{
						$col_data['payables_action'] ='';
					}
					*/
					
					$buying['rows'][$i++] = $col_data;					
				}
			
			return json_encode($buying);
			
			}
	}
	
	function process_selling(Vtiger_Request $request)
	{
		//global $adb;
		//$models = $_SESSION['models'];
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
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
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
		
		//$models = $relationListView->getEntries($pagingModel);
		$JRER_TYPE = 'Selling';
		$models = $relationListView->getEntries($pagingModel,$JRER_TYPE);
		
		$relationModel = $relationListView->getRelationModel();
		
		$header = $_SESSION['header'];		
		
		$current_user = Users_Record_Model::getCurrentUserModel();	
		
		$job_id 			  = $parentId;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
		
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==2) 
		{
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$selling = array();
				$i=0;
				$check_invoice_instruction_no = array();
				$check_preview_instruction_no = array();
				
				//$models = $relationListView->getEntries($pagingModel);
				foreach($models as $key => $model){
					
					$selling_record= $model->getInstanceById($model->getId());
					
					if($selling_record->getDisplayValue('cf_1457') == 'Expence'){
						continue; 
					}
					
					$action = '';
					
					$instruction_no = $model->get('invoice_instruction_no');
					
					$pre_instruction_no = $model->get('preview_instruction_no');
					$s_preview_invoice_instruction = $selling_record->get('cf_2439');
					
					if(!in_array($model->get('preview_instruction_no'), $check_preview_instruction_no) && !empty($pre_instruction_no) && ($s_preview_invoice_instruction=='1') && empty($instruction_no))
					{
						$check_preview_instruction_no[]=$pre_instruction_no;
						//$action .='<a href="invoice_preview_pdf.php?record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$model->get('cf_1445').'&invoice_instruction_no='.$model->get('preview_instruction_no').'"><i title="Preview Invoice Instruction" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$selling_record->get('cf_1445').'&invoice_instruction_no='.$model->get('invoice_instruction_no').'&expense=GII" target="_blank" ><i title="Preview Invoice Instruction" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
					}
					
					
					$instruction_no = $model->get('invoice_instruction_no');
					$s_generate_invoice_instruction = $selling_record->get('cf_1248');
					if(!in_array($model->get('invoice_instruction_no'), $check_invoice_instruction_no) && !empty($instruction_no) && ($s_generate_invoice_instruction=='1'))
					{
						$action = '';
						$check_invoice_instruction_no[]=$instruction_no;
						//$action .='<a href="invoice_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$selling_record->get('cf_1445').'&invoice_instruction_no='.$model->get('invoice_instruction_no').'"><i title="Generate Invoice Instruction" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$selling_record->get('cf_1445').'&invoice_instruction_no='.$model->get('invoice_instruction_no').'&expense=GII" target="_blank" ><i title="Generate Invoice Instruction" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
					}
					
					
					if($relationModel->isDeletable() &&  ($selling_record->get('cf_1250')=='' ||  $selling_record->get('cf_1250')=='Declined' )){
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'"><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="icon-trash alignMiddle"></i></a>&nbsp;';
					}					
					//if($relationModel->isEditable() &&  ($selling_record->get('cf_1250')=='' ||  $selling_record->get('cf_1250')=='Declined' )){
					//if($relationModel->isEditable() &&  ( $selling_record->get('cf_1250')!='Approved')){	
					if($relationModel->isEditable() && ($current_user->getId() ==  $job_info_detail->get('assigned_user_id') ) && ($job_info_detail->get('cf_2197')=='No Costing' || $job_info_detail->get('cf_2197')=='In Progress' || $job_info_detail->get('cf_2197')=='Revision')){
						$action .='<a name="relationEdit" data-url="'.$model->getEditViewUrl().'&jrertype=selling&cf_1457=Selling"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="fa fa-pencil alignMiddle"></i></a>&nbsp;';
					}					
					//For view
					//$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=selling&cf_1457=Selling&jobid='.$job_id.'"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
					
								
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
						
						if($header_info->get('name')=='cf_1248')
						{
							continue;
						}					
						
						$RELATED_HEADERNAME = $header_info->get('name');
						$column_value = $selling_record->getDisplayValue($RELATED_HEADERNAME);
						$col_data[$RELATED_HEADERNAME] = $column_value; 						
					}
					$col_data['cf_1359'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1359'));
					$col_data['cf_1361'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1361'));
					$col_data['cf_1363'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1363'));
					$col_data['cf_1365'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1365'));
					
					//$col_data['cf_2439'] = $selling_record->get('cf_2439');									
					$col_data['cf_1248'] = $selling_record->get('cf_1248');
					$col_data['cf_1250'] = $selling_record->get('cf_1250');
					//$col_data['cf_1248'] =$model->getDisplayValue('cf_1248');
					//$col_data['cf_1248'] = $model->getId();
					
					$selling['rows'][$i++] = $col_data;
					
				}
			return json_encode($selling);
			
			}	
	}
	
	function process_selling_invoices(Vtiger_Request $request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');
		$label = $request->get('tab_label');

		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
		$requestedPage = $request->get('page');
		if(empty($requestedPage)) {
			$requestedPage = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page',$requestedPage);

		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
        
        if(!empty($whereCondition))
            $relationListView->set('whereCondition', $whereCondition);
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'icon-chevron-down';
            $faSortImage = "fa-sort-desc";
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'icon-chevron-up';
            $faSortImage = "fa-sort-asc";
		}
		if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder',$sortOrder);
		}
		$relationListView->tab_label = $request->get('tab_label');
		
		//$models = $relationListView->getEntries($pagingModel);
		$JRER_TYPE = 'Selling';
		$models = $relationListView->getEntries($pagingModel,$JRER_TYPE);
	
		$relationModel = $relationListView->getRelationModel();
		
		$header = $_SESSION['header'];		
		
		$current_user = Users_Record_Model::getCurrentUserModel();	
		
		$job_id 			  = $parentId;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
								  
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==3) 
		{				
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$selling = array();
				$i=0;
				$check_invoice_instruction_no = array();
				
				//roselle user id=389
				//$roselle_branch = array('85805','85812', '85807', '85813', '85808', '85816', '85820', '85809', '85832', '85831', '85819');
				$roselle_branch = array('85805','85806','85807','85808','85809','85810','85811','85812','85813','85814','85815','85816','85817','85818','85819',
										'85820','85821','85822','85823','85824','85825','85826','85827','85828','85829','85830','85831','85832','85833','85834',
										'85835','85946','85947','85948', '2540991','2467467');
				$roselle_company = array('85772','85756','85768','85761', '85771', '85763', '85762', '205751', '420284','85759');	
				//dw = 85756					
				//$roselle_company = array('85772', '85763', '85771', '85761', '85768', '85762');
				
				//Kazbek userid = 436
				//Tatyana(Invoicing) userid = 1711
				
				//$kazbek_branch = array('85805','85829', '85826');
				//$kazbek_company = array('85757');
				//$kazbek_branch = array('85805','85829');//only almaty and shi
				$kazbek_branch = array('85805','85829','85806','85815', '85822', '85834', '85812', '85813', '85946','85814','85831','85808', '85809', '85824', '85820','85819','85832', '2540991');
				$kazbek_company = array('85757','85764');
				
				$jyldyz_branch = array('85816');
				$jyldyz_company = array('85764');
				
				//AST
				$aisra_branch = array('85806'); //685 useri 
				$aisra_company = array('85757');
			    //ARA
				$sarieva_branch = array('85813'); //685 useri 
				$sarieva_company = array('85757');
				
				//AKT
				$sara_branch = array('85812'); //685 useri 
				$sara_company = array('85757');
				
				//ATU
				$irina_branch = array('85815'); //685 useri 
				$irina_company = array('85757');
				
			    
				
				foreach($models as $key => $model){
					$selling_record= $model->getInstanceById($model->getId());
					
					if($selling_record->get('cf_1250')=='')
					{
						continue; 
					}
					
					if($selling_record->getDisplayValue('cf_1457') == 'Expence'){
						continue; 
					}
					
					if($current_user->getId()==389  || $current_user->getId()==547 || $current_user->getId()==1131 || $current_user->getId()==1268)
					{
						if(!in_array($selling_record->get('cf_1477'), $roselle_branch))
						{
							continue;
						}
					}
					elseif($current_user->getId()==436 || $current_user->getId()==1711)
					{
						if(!in_array($selling_record->get('cf_1477'), $kazbek_branch))
						{
							continue;
						}
					}
					elseif($current_user->getId()==605)
					{
						if(!in_array($selling_record->get('cf_1477'), $jyldyz_branch))
						{
							continue;
						}
					}
					elseif($current_user->getId()==685)
					{
						if(!in_array($selling_record->get('cf_1477'), $aisra_branch))
						{
							continue;
						}
					}
					elseif($current_user->getId()==549)
					{
						if(!in_array($selling_record->get('cf_1477'), $sarieva_branch))
						{
							continue;
						}
					}
					elseif($current_user->getId()==686)
					{
						if(!in_array($selling_record->get('cf_1477'), $sara_branch))
						{
							continue;
						}
					}
					elseif($current_user->getId()==687)
					{
						if(!in_array($selling_record->get('cf_1477'), $irina_branch))
						{
							continue;
						}
					}
					
					
					if($current_user->getId()==389 || $current_user->getId()==547 || $current_user->getId()==1131 || $current_user->getId()==1268)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $roselle_company))
						{
							continue;
						}
					}
					elseif($current_user->getId()==436 || $current_user->getId()==1711)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $kazbek_company))
						{
							continue;
						}
					}
					elseif($current_user->getId()==605)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $jyldyz_company))
						{
							continue;
						}
					}
					elseif($current_user->getId()==685)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $aisra_company))
						{
							continue;
						}
					}
					elseif($current_user->getId()==549)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $sarieva_company))
						{
							continue;
						}
					}
					elseif($current_user->getId()==686)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $sara_company))
						{
							continue;
						}
					}
					elseif($current_user->getId()==687)
					{
						if(!in_array($job_info_detail->get('cf_1186'), $irina_company))
						{
							continue;
						}
					}				
					
					//$job_selling_info = Vtiger_Record_Model::getInstanceById($model->getId(), 'Jobexpencereport');
									
					$col_data['jobexpencereportid'] = $model->getId();
					
					$action = '';
					$instruction_no = $model->get('invoice_instruction_no');
					$s_generate_invoice_instruction = $selling_record->get('cf_1248');
					if(!in_array($model->get('invoice_instruction_no'), $check_invoice_instruction_no) && !empty($instruction_no) && ($s_generate_invoice_instruction=='1'))
					{
						$action = '';
						$check_invoice_instruction_no[]=$instruction_no;
						//$action .='<a href="invoice_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$selling_record->get('cf_1445').'&invoice_instruction_no='.$model->get('invoice_instruction_no').'"><i title="Generate Invoice Instruction" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&jobid='.$job_id.'&bill_to='.$selling_record->get('cf_1445').'&invoice_instruction_no='.$model->get('invoice_instruction_no').'&expense=GII" target="_blank" ><i title="Generate Invoice Instruction" class="fa fa-print alignMiddle"></i></a>&nbsp;';
			
					}
					$col_data['myname'] = $action;	
					$col_data['gl_account'] = $model->get('gl_account');
					$col_data['ar_gl_account'] = $model->get('ar_gl_account');
					$col_data['invoice_no'] = $model->get('invoice_no');
					
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
					
				
					//echo $model->getDisplayValue('cf_1248');
					//echo "<br>";	
					$col_data['cf_1359'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1359'));
					$col_data['cf_1361'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1361'));
					$col_data['cf_1363'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1363'));
					$col_data['cf_1365'] = str_replace(array("\r", "\n"), '', $selling_record->get('cf_1365'));
									
					$col_data['cf_1248'] = $selling_record->get('cf_1248');
					$col_data['cf_1250'] = $selling_record->get('cf_1250');
					
					$invoice_status = $selling_record->get('cf_1250');
					
					$col_data['invoice'] = $model->get('accept_generate_invoice');
					
					$col_data['local_invoice_no'] ='';
					/*
					if($invoice_status=='Approved' || $invoice_status=='Declined')
					{
						$col_data['invoice'] ='';
					}
					*/					
					$col_data['sdate'] = $selling_record->get('cf_1355');
					//cf_1250
					//$col_data['cf_1248'] =$model->getDisplayValue('cf_1248');
					//$col_data['cf_1248'] = $model->getId();					
					$selling['rows'][$i++] = $col_data;					
				}
			return json_encode($selling);
			}
			
	}
	
}
