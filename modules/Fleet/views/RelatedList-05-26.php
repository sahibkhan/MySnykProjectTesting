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
							 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Fleet' AND crmentityrel.relmodule='Jobexpencereport' order by vtiger_crmentity.modifiedtime DESC limit 1";
			// parentId = Job Id	
			$job_id = $parentId;
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
			
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==1) 
			{				
				//if(@$_REQUEST['grid']==1) {
				$expence_header[] = 'Action';
				//$expence_header[] = 'Coordinator';
				
				foreach($header as $header_info)
				{						
					if(in_array($header_info->get('column'), $selling_arr))
					{
						continue;
					}					
					$expence_header[] = vtranslate($header_info->get('label').'_SHORT', $relatedModuleModel->get('name'));
				}
									
				$viewer->assign('EXPENCE_HEADER' , implode(',',$expence_header));
				
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$i=0;
				$models = $relationListView->getEntries($pagingModel);
				foreach($models as $key => $model){
					
					if($model->getDisplayValue('cf_1457') == 'Selling'){
						continue; 
					}
					
					$expense_record= $model->getInstanceById($model->getId());				
					
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
					if($model->getDisplayValue('cf_1214')!='')
					{
						$action .='&nbsp;<a href="PV_export_pdf.php?record='.$model->getId().'&jobid='.$job_id.'" target="_blank"><i title="Print Payment Voucher" class="icon-print alignMiddle"></i></a>&nbsp;';
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
						$column_value = $model->getDisplayValue($RELATED_HEADERNAME);
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
		}
		
		if($relatedModuleName == 'Jobexpencereport')
		{
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