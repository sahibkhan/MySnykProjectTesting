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
		$noOfEntries = count($models);;
		$relationModel = $relationListView->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relationField = $relationModel->getRelationField();
        
        $fieldsInfo = array();
        foreach($moduleFields as $fieldName => $fieldModel){
            $fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
        }

		$viewer = $this->getViewer($request);
		$viewer->assign('RELATED_FIELDS_INFO', json_encode($fieldsInfo));
		
		//$viewer->assign('IS_CREATE_PERMITTED', ($relatedModuleName == 'PostTrip' ? FALSE : isPermitted($relatedModuleName, 'CreateView')));
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

		if($relatedModuleName =='Jobexpencereport')
		{
			$current_user = Users_Record_Model::getCurrentUserModel();
			$privileges  = $current_user->get('privileges');
			$parent_roles = $privileges->parent_roles;
			$coordinator_department_head_role = @$parent_roles[3];
			$count_parent_role = count($parent_roles);

			if($_REQUEST['module']=='Fleettrip' && $count_parent_role==0)
			{
				$role_id =  $current_user->get('roleid');
				$depth_role = "SELECT * FROM vtiger_role where roleid='".$role_id."' ";
				$result_role = $adb->pquery($depth_role, array());
				$row_depth = $adb->fetch_array($result_role);
				$count_parent_role = $row_depth['depth'];				
			}
			
			$viewer->assign('COORDINATOR_DEPARTMENT_HEAD_ROLE', ($coordinator_department_head_role!='' ? $coordinator_department_head_role : 0));
			$viewer->assign('FLEET_TRIP_ID', $fleettrip_id);

			$width_exp_in_arr = array('cf_1477', 'cf_1479' , 'cf_1367', 'cf_1214', 'cf_1216', 'cf_1210', 'cf_1339', 'cf_1341', 'cf_1345', 'cf_1222');
			
			
			$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
			$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
								 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 'cf_1248');
			$viewer->assign('EXPENCE_ARR', $expence_arr);
			$viewer->assign('SELLING_ARR', $selling_arr);

			if($current_user->get('roleid')=='H184')
			{
							
				$buying = array();
				$check_buying_invoice_no = array();
				$i=0;
				
				$ui_type_array = array('cf_1214', 'cf_1345');
				
				//$models = $relationListView->getEntries($pagingModel);
				foreach($models as $key => $model){
					
					$expense_record= $model->getInstanceById($model->getId());				
					
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
					elseif($current_user->getId()==422 || $current_user->getId()==562) //Aigerim = 422
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
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="fa fa-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						$action .='<a name="relationEdit" data-url="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="fa fa-pencil alignMiddle"></i></a>&nbsp;';
					}					
					$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="fa fa-th-list alignMiddle"></i></a>&nbsp;';			  
					$action = '';
					if($expense_record->getDisplayValue('cf_1214')!='')
					{
						$action .='&nbsp;<a href="PV_fleet_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
					}
					
					$action_inv = '';
					if(!in_array($expense_record->get('cf_1212'), $check_buying_invoice_no))
					{
						$check_buying_invoice_no[] = $expense_record->get('cf_1212');
						if($expense_record->getDisplayValue('cf_1214')!='')
						{
							$action_inv ='&nbsp;<a href="PV_fleet_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
						}
					}
					
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
					
					$buying[$i++] = $col_data;					
				}
				$coordinator_buying = json_encode($buying);					
				$viewer->assign('BUYING_ARRAY_PAYABLES' ,$coordinator_buying);
				//return json_encode($buying);	
					
					$EXPENSE_HEADER_PAYABLES = "'Expense ID','Action','GL Account','Accounts Payable GL Account','Coordinator','Branch','Dept','Charge','Pay To','Invoice #','Invoice Date','Due Date','Type','Buy(Vendor Cur Net)','VAT Rate','VAT','Buy(Vendor Cur Gross)','Vendor Curr','Exch Rate','Buy(Local Curr Gross)','Buy(Local Cur Net)','Expected Buy(Local Cur NET)','Variation expected and actual buying','Payables Action','Payables Status'";
					$EXPENSE_FIELD_PAYABLES = '{"name":"jobexpencereportid","key":true,"index":"id","align":"center","hidden":true,"frozen":true},{"name":"myname","frozen":true,"width":"60","align":"center"},{"name":"b_gl_account","align":"center","width":"50","frozen":true},{"name":"b_ar_gl_account","align":"center","width":"50","frozen":true},{"name":"assigned_user_id","index":"assigned_user_id","width":"100","frozen":true},{"name":"cf_1477","index":"cf_1477","width":"50","frozen":true},{"name":"cf_1479","index":"cf_1479","width":"50","frozen":true},{"name":"cf_1453","index":"cf_1453","width":"100","frozen":true},{"name":"cf_1367","index":"cf_1367","width":null,"frozen":false},{"name":"cf_1212","index":"cf_1212","width":"100","frozen":false},{"name":"cf_1216","index":"cf_1216","width":null,"frozen":false},{"name":"cf_1210","index":"cf_1210","width":null,"frozen":false},{"name":"cf_1214","index":"cf_1214","width":null,"frozen":false},{"name":"cf_1337","index":"cf_1337","width":"100","frozen":false},{"name":"cf_1339","index":"cf_1339","width":null,"frozen":false},{"name":"cf_1341","index":"cf_1341","width":null,"frozen":false},{"name":"cf_1343","index":"cf_1343","width":"100","frozen":false},{"name":"cf_1345","index":"cf_1345","width":null,"frozen":false},{"name":"cf_1222","index":"cf_1222","width":null,"frozen":false},{"name":"cf_1347","index":"cf_1347","width":"100","frozen":false},{"name":"cf_1349","index":"cf_1349","width":"100","frozen":false},{"name":"cf_1351","index":"cf_1351","width":"100","frozen":false},{"name":"cf_1353","index":"cf_1353","width":"100","frozen":false},{"name":"payables_action","index":"payables_action","width":"200","editable":true,"edittype":"select","editoptions":{"value":"0:Select;accept:Accept;reject:Reject"}},{"name":"cf_1975","index":"payables_approval_status","align":"center","width":"80"}';							
					
					$viewer->assign('EXPENCE_HEADER_PAYABLES' , $EXPENSE_HEADER_PAYABLES);
					$viewer->assign('EXPENCE_FIELD_PAYABLES' , $EXPENSE_FIELD_PAYABLES);	
					//EXPENSE PAYABLES END
					
					//return $viewer->view('RelatedListJRERPayables.tpl', $moduleName, 'true');
			}
			else{
							
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
						$action .='<a class="relationDelete" data-id="'.$model->getId().'" data-recordUrl="'.$model->getDetailViewUrl().'" ><i title="'.vtranslate('LBL_DELETE', $moduleName).'" class="fa fa-trash alignMiddle"></i></a>&nbsp;';
					}					
					if($relationModel->isEditable()){
						$action .='<a name="relationEdit" data-url="'.$model->getEditViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_EDIT', $moduleName).'" class="fa fa-pencil alignMiddle"></i></a>&nbsp;';
					}
										
					//$action .='<a href="'.$model->getFullDetailViewUrl().'&jrertype=expence&cf_1457=Expence"><i title="'.vtranslate('LBL_SHOW_COMPLETE_DETAILS', $moduleName).'" class="icon-th-list alignMiddle"></i></a>&nbsp;';			  
					if($expense_record->getDisplayValue('cf_1214')!='')
					{
						//$action .='&nbsp;<a href="PV_fleet_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="icon-print alignMiddle"></i></a>&nbsp;';
						$action .='&nbsp;<a href="index.php?module=Jobexpencereport&view=Print&record='.$model->getId().'&fleettripid='.$fleettrip_id.'&expense=PV" target="_blank"><i title="Print Fleet Payment Voucher" class="fa fa-print alignMiddle"></i></a>&nbsp;';
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
					
					
					//$buying['rows'][$i++] = $col_data;
					$buying[$i++] = $col_data;
					
				}
			
			//return json_encode($buying);			
			//}	
			
			$coordinator_buying = json_encode($buying);					
			$viewer->assign('BUYING_ARRAY' ,$coordinator_buying);			
			
			$EXPENSE_HEADER="'Expense ID','Action','Coordinator','Branch','Dept','Charge','Pay To','Invoice #','Invoice Date','Due Date','Type','Buy(Vendor Cur Net)','VAT Rate','VAT','Buy(Vendor Cur Gross)','Vendor Curr','Exch Rate','Buy(Local Curr Gross)','Buy(Local Cur Net)','Expected Buy(Local Cur NET)','Variation expected and actual buying','Send to Head of Department and Generate Payment Voucher','Head of Department Status','Payables Status'";	
			$EXPENSE_FILED = '{"name":"jobexpencereportid","key":true,"index":"id","align":"center","hidden":true,"frozen":true},{"name":"myname","frozen":true,"align":"center"},{"name":"assigned_user_id","index":"assigned_user_id","width":"100","frozen":true},{"name":"cf_1477","index":"cf_1477","width":"100","frozen":true},{"name":"cf_1479","index":"cf_1479","width":"100","frozen":true},{"name":"cf_1453","index":"cf_1453","width":"100","frozen":true},{"name":"cf_1367","index":"cf_1367","width":"200","frozen":false},{"name":"cf_1212","index":"cf_1212","width":"100","frozen":false},{"name":"cf_1216","index":"cf_1216","width":"100","frozen":false},{"name":"cf_1210","index":"cf_1210","width":"100","frozen":false},{"name":"cf_1214","index":"cf_1214","width":"100","frozen":false},{"name":"cf_1337","index":"cf_1337","width":"100","frozen":false},{"name":"cf_1339","index":"cf_1339","width":"100","frozen":false},{"name":"cf_1341","index":"cf_1341","width":"100","frozen":false},{"name":"cf_1343","index":"cf_1343","width":"100","frozen":false},{"name":"cf_1345","index":"cf_1345","width":"100","frozen":false},{"name":"cf_1222","index":"cf_1222","width":"100","frozen":false},{"name":"cf_1347","index":"cf_1347","width":"100","frozen":false},{"name":"cf_1349","index":"cf_1349","width":"100","frozen":false},{"name":"cf_1351","index":"cf_1351","width":"100","frozen":false},{"name":"cf_1353","index":"cf_1353","width":"100","frozen":false},{"name":"send_to_head_of_department_for_approval","align":"center","editable":true,"edittype":"checkbox","formatter":"checkbox","formatoptions":{"disabled":false},"editoptions":{"value":"Yes:No"}},{"name":"head_of_department_approval_status","index":"head_of_department_approval_status","align":"center","width":"80"},{"name":"send_to_payables_and_generate_payment_voucher","index":"send_to_payables_and_generate_payment_voucher"}';			
			
			$viewer->assign('EXPENCE_HEADER' , $EXPENSE_HEADER);
			$viewer->assign('EXPENCE_FIELD' , $EXPENSE_FILED);
			//EXPENCE END
			}

			
		}

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
