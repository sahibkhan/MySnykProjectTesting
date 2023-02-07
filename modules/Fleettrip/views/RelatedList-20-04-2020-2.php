<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleettrip_RelatedList_View extends Vtiger_RelatedList_View {
	
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

		$recordId = $request->get('record');
		$fleettrip_id = $recordId;
		//$current_user = Users_Record_Model::getCurrentUserModel();
		$fleettrip_info_detail = Vtiger_Record_Model::getInstanceById($fleettrip_id, $moduleName);
		$viewer->assign('FLEET_TRIP_DETAIL', $fleettrip_info_detail);

		if($relatedModuleName =='Roundtrip')
		{
			include("include/Exchangerate/exchange_rate_class.php");
			$current_user = Users_Record_Model::getCurrentUserModel();
			$total_revenew = 0;
			$sum_of_cost = 0;
			$sum_of_job_profit = 0;
			$sum_of_internal_selling = 0;

			foreach($models as $key => $model){

				$total_revenew +=$model->get('cf_3173');
				
				$job_id 			  = $model->get('cf_3175');
				$sourceModule_job 	= 'Job';	
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

				$sourceModule_roundtrip 	= 'Roundtrip';	
				$round_trip_id = $model->getId();
				$round_trip_info = Vtiger_Record_Model::getInstanceById($round_trip_id, $sourceModule_roundtrip);

				$roundtrip_user_info = Users_Record_Model::getInstanceById($round_trip_info->get('assigned_user_id'), 'Users');
				$roundtrip_user_office_id = $roundtrip_user_info->get('location_id');

				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;

					if($job_info->get('assigned_user_id')!=$round_trip_info->get('assigned_user_id'))
					{
						//$db = PearDatabase::getInstance();
								
						$rs_query  = $adb->pquery("select * from vtiger_jobtask 
												where job_id='".$job_id."' and user_id='".$round_trip_info->get('assigned_user_id')."' limit 1", array());
						$row_task = $adb->fetch_array($rs_query);
											
						if($adb->num_rows($rs_query)>0)
						{
							$file_title_id = $row_task['sub_jrer_file_title'];
							if(empty($file_title_id))
							{
								$job_office_id = $job_info->get('cf_1188');
								$roundtrip_user_info = Users_Record_Model::getInstanceById($round_trip_info->get('assigned_user_id'), 'Users');
								$roundtrip_user_office_id = $roundtrip_user_info->get('location_id');
								
								//if same office then job file title must apply
								if($job_office_id==$roundtrip_user_office_id){
									$file_title_id = $job_info->get('cf_1186');
								}
								else{
									//by default KZ file title								
									$file_title_id = '85757';
								}
									
							}
							$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_id);
							$file_title_currency = $job_reporting_currency;
						}
					}

					//$adb_buy_local = PearDatabase::getInstance();										   
					$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
														vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
														vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
													FROM `vtiger_jobexpencereport` 
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
													INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
													left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
													where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job' 
													and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
													and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? 
													AND vtiger_jobexpencereport.fleettrip_id=?
													AND vtiger_jobexpencereport.roundtrip_id =?
													AND vtiger_jobexpencereport.owner_id=?
													";
					
					$params_buy_local = array($model->get('cf_3175'), $roundtrip_user_office_id, '85844', $parentId, $round_trip_id, $round_trip_info->get('assigned_user_id'));
					//latest old one
					//
					//$params_buy_local = array($model->get('cf_3175'), '85805', '85844', $parentId, $current_user->getId());
					$result_buy_locall = $adb->pquery($sum_buy_local_currency_net, $params_buy_local);
					$numRows_buy_profit = $adb->num_rows($result_buy_locall);
					$cost = 0;

					for($jj=0; $jj< $adb->num_rows($result_buy_locall); $jj++ ) {
						$row_jrer_buy_local_currency_net = $adb->fetch_row($result_buy_locall,$jj);
						//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);
						
						$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];	
						
						$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];
						
						$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
						if ($CurId) {
						  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
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

					    //$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid 
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job' 
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?	
												";				   
						
						//$params_internal = array($model->get('cf_3175'), '85805', '85844');
						$params_internal = array($model->get('cf_3175'), $roundtrip_user_office_id, '85844');
						
						$result_internal = $adb->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb->fetch_array($result_internal);	
				
						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $model->get('cf_3173') - $cost;
						
						$profit_share_data[] = array('cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'job_ref_no' => $model->getDisplayValue('cf_3175'),
													 'job_id' => $model->get('cf_3175'),
												 	 //'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling' => number_format ( $model->get('cf_3173') , 2 ,  "." , "," ),
													// 'user_id' => $current_user->getId()
													'user_id' => $round_trip_info->get('assigned_user_id')
													 );	
					
					
						$sum_of_cost += $cost;
						$sum_of_job_profit +=$job_profit;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_3173');
			}

			$viewer->assign('SUM_OF_COST' , number_format($sum_of_cost , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_JOB_PROFIT' , number_format($sum_of_job_profit , 2 ,  "." , "," ));
			$viewer->assign('SUM_OF_INTERNAL_SELLING' , number_format($sum_of_internal_selling , 2 ,  "." , "," ));
			$viewer->assign('PROFIT_SHARE' , $profit_share_data);
			
			$viewer->assign('TOTAL_REVENEW' , $total_revenew);
			
			$flag = false;
			if($fleettrip_info_detail->get('assigned_user_id')==$current_user->getId())
			{
				$flag = true;
			}
			$rs_internal_selling_query =  $adb->pquery("select * from vtiger_fleettrip where fleettripid='".$recordId."'", array());
			$row_internal_selling = $adb->fetch_array($rs_internal_selling_query);
			$viewer->assign('INTERNAL_SELLING_FINAL', $row_internal_selling['internal_selling_final']);
			$viewer->assign('INTERNAL_SELLING_DISTRIBUTION', $row_internal_selling['internal_selling_distribution']);
			$viewer->assign('FLEET_TRIP_OWNER_FLAG' , $flag);
			
			//Round Trip Status			
			$viewer->assign('ROUND_TRIP_STATUS',$fleettrip_info_detail->get('cf_4803'));
			
			//For FROM & To DATE Check
			$viewer->assign('FROM_TO_DATE_MESSAGE','');
			if(isset($_SESSION['FROM_TO_DATE_FLAG']) && $_SESSION['FROM_TO_DATE_FLAG']==1)
			{
				//For FROM & To DATE Check
				$viewer->assign('FROM_TO_DATE_MESSAGE','Please update From and To Date in Fleet Trip, before final distribution.');
			}
			
			return $viewer->view('RelatedListRoundTrip.tpl', $moduleName, 'true');	
		}
		else if($relatedModuleName == 'Jobexpencereport')
		{
			return $viewer->view('RelatedListJRERFleetTrip.tpl', $moduleName, 'true');
		}
		else if($relatedModuleName == 'PostTrip')
		{
			$adb = PearDatabase::getInstance();	
			$post_pre_trip_sum_sql =  "SELECT sum(posttripcf.cf_4321) as post_total_tenge , sum(posttripcf.cf_4327) as pre_total_tenge 
							  	   FROM vtiger_posttripcf as posttripcf 
							  	   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = posttripcf.posttripid
							 	   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 	   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
								   AND crmentityrel.module='Fleettrip' AND crmentityrel.relmodule='PostTrip' AND posttripcf.cf_4315!='549416'";
			
			$params = array($fleettrip_id);
			$result = $adb->pquery($post_pre_trip_sum_sql, $params);
			$row_post_pre_trip = $adb->fetch_array($result);
			
			$viewer->assign('POST_TOTAL_TENGE',  number_format($row_post_pre_trip['post_total_tenge'] , 2 ,  "." , "," ));
			$viewer->assign('PRE_TOTAL_TENGE', number_format($row_post_pre_trip['pre_total_tenge'] , 2 ,  "." , "," ));	
			
			return $viewer->view('RelatedListPostTrip.tpl', $moduleName, 'true');
		}
		else if($relatedModuleName == 'CustomsCarrier')
		{
			return $viewer->view('RelatedListCustomsCarrier.tpl', $moduleName, 'true');
		}
		else if($relatedModuleName=='TripAddresses')
		{			
			//For TRIP Address Check
			$viewer->assign('TRIP_ADDRESS_MESSAGE','');
			if(isset($_SESSION['tripaddresses']) && $_SESSION['tripaddresses']==1) {
				unset($_SESSION['tripaddresses']);
				$viewer->assign('TRIP_ADDRESS_MESSAGE', 'Please create trip addresses information before proceeding to trip budget.');
			}
			return $viewer->view('RelatedListAddresses.tpl', $moduleName, 'true');
		}
		else{
		return $viewer->view('RelatedList.tpl', $moduleName, 'true');
		}
	}
}
