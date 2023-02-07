<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

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
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
        
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
		$models = $relationListView->getEntries($pagingModel);
		$links = $relationListView->getLinks();
		$header = $relationListView->getHeaders();
		
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
							 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1";
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

		if($relatedModuleName =='JER')
		{			
			return $viewer->view('RelatedListCosting.tpl', $moduleName, 'true');			
		}
		elseif($relatedModuleName == 'Jobexpencereport')
		{
			return $viewer->view('RelatedListJRER.tpl', $moduleName, 'true');
		}
		else { 
			return $viewer->view('RelatedList.tpl', $moduleName, 'true');
		}
        
	}
}
