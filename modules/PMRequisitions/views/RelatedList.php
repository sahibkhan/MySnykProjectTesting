<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMRequisitions_RelatedList_View extends Vtiger_RelatedList_View {
	
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
		//user_id :: 604 :: Rustam Balayev 
			//user_id :: 374 :: Mehtab Shah
			//user_id :: 60  :: Mr. Khan
			//user_id :: 1245 :: Murat Makhmatov :: Procurement Manager 
			//user_id :: 1663 :: Zhan Kazlykov :: New Procurement Manager
			
			$approval_by_arr = array('604', '374','1663');
			$current_user = Users_Record_Model::getCurrentUserModel();
			$current_user_id = $current_user->getId();
			$button_approved_flag = false; 
			if(in_array($current_user_id, $approval_by_arr) && $parentRecordModel->get('cf_4593')=='Pending')
			{
				$button_approved_flag = true;
				$rrs_signature = $parentRecordModel->getDisplayValue('cf_4585');
				if($current_user_id==604 && empty($rrs_signature))
				{
					$button_approved_flag = false;
				}
			}
			$viewer->assign('BUTTON_APPROVED_FLAG', $button_approved_flag);
			
			global $adb;
			$pmitems_sum_sql =  "SELECT sum(pmitemscf.cf_4723) as total_cost_local_currency , sum(pmitemscf.cf_4575) as total_cost_in_usd FROM `vtiger_pmitemscf` as pmitemscf 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = pmitemscf.pmitemsid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='PMRequisitions' AND crmentityrel.relmodule='PMItems'";
			// parentId = Job Id	
			$job_id = $parentId;		 
			$params = array($parentId);
			$result = $adb->pquery($pmitems_sum_sql, $params);
			$row_pm_items = $adb->fetch_array($result);
			
			$total_cost_local_currency = $row_pm_items['total_cost_local_currency'];
			$total_cost_in_usd = $row_pm_items['total_cost_in_usd'];
			
			$viewer->assign('TOTAL_COST_LOCAL_CURRENCY' , number_format ( $total_cost_local_currency , 2 ,  "." , "," ));
			$viewer->assign('TOTAL_COST_IN_USD' , number_format ( $total_cost_in_usd , 2 ,  "." , "," ));
			
			
			
			include("include/Exchangerate/exchange_rate_class.php");
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$parentRecordModel->get('cf_4271'));
			$file_title_currency = $reporting_currency;
			
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
			
			$createdtime =  $parentRecordModel->get('CreatedTime');
			$exchange_rate_date = date('Y-m-d', strtotime($createdtime));
			
			$final_exchange_rate = 0;
			if(!empty($exchange_rate_date))
			{
				if($file_title_currency!='USD')
				{
					$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
				}else{
					$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
				}
			}
			$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));
        return $viewer->view('RelatedList.tpl', $moduleName, 'true');
	}
}
