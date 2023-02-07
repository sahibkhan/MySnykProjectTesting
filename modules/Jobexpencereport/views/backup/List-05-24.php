<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Jobexpencereport_List_View extends Vtiger_List_View {
	protected $listViewEntries = false;
	protected $listViewCount = false;
	protected $listViewLinks = false;
	protected $listViewHeaders = false;
	function __construct() {
		parent::__construct();
		
	}

	function preProcess(Vtiger_Request $request, $display=true) {
		
		parent::preProcess($request, false);

		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName);
		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'));
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName));
		$this->viewName = $request->get('viewname');
		if(empty($this->viewName)){
			//If not view name exits then get it from custom view
			//This can return default view id or view id present in session
			$customView = new CustomView();
			$this->viewName = $customView->getViewId($moduleName);
		}

		$quickLinkModels = $listViewModel->getSideBarLinks($linkParams);
		$viewer->assign('QUICK_LINKS', $quickLinkModels);
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('VIEWID', $this->viewName);

		if($display) {
			$this->preProcessDisplay($request);
		}
	}

	function preProcessTplName(Vtiger_Request $request) {

		return 'ListViewPreProcess.tpl';
	}

	//Note : To get the right hook for immediate parent in PHP,
	// specially in case of deep hierarchy
	/*function preProcessParentTplName(Vtiger_Request $request) {
		return parent::preProcessTplName($request);
	}*/

	protected function preProcessDisplay(Vtiger_Request $request) {
		parent::preProcessDisplay($request);
	}


	function process (Vtiger_Request $request) {
		global $adb;
		
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$this->viewName = $request->get('viewname');
		
		$this->initializeListViewContents($request, $viewer);
		
		$header = $this->listViewHeaders;		
		$models = $this->listViewEntries;
		
		
		$expence_arr = array('cf_1453',  'cf_1367', 'cf_1212', 'cf_1210', 'cf_1216', 'cf_1214', 'cf_1339', 'cf_1337', 'cf_1343', 'cf_1341', 
								  'cf_1222', 'cf_1345', 'cf_1349', 'cf_1347', 'cf_1369', 'cf_1351', 'cf_1353', 'cf_1457');
		$selling_arr = array('cf_1455', 'cf_1445', 'cf_1359', 'cf_1361', 'cf_1363', 'cf_1365', 'cf_1355', 'cf_1357', 'cf_1234', 'cf_1228',
							 'cf_1230', 'cf_1232', 'cf_1238', 'cf_1236', 'cf_1242', 'cf_1240', 'cf_1246', 'cf_1244', 'cf_1457', 'cf_1250', 'cf_1248');
		$viewer->assign('EXPENCE_ARR', $expence_arr);
		$viewer->assign('SELLING_ARR', $selling_arr);
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$count_parent_role = 0;
		if($current_user->get('is_admin')!='on')
		{
			$privileges   = $current_user->get('privileges');
			$parent_roles = $privileges->parent_roles;
			$count_parent_role = count($parent_roles);
		}
		
		$job_id = '';
		//if(!empty($models))
		//{
		$get_first_record = current($models);
		$head_status = $get_first_record->rawData['cf_1973'];
		$job_id = $get_first_record->rawData['jobid'];
		$head_status_expense = $head_status;
		//}
		
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==5) 
		{							
				//$buying = new StdClass;
				//$buying['page'] = 1;
				//$buying['total'] = 7;
				//$buying['records'] = 7;
				$buying = array();
				$i=0;
				//$models = $relationListView->getEntries($pagingModel);
				$ui_type_array = array('cf_1214', 'cf_1345');
				
				foreach($models as $key => $model){
					if($model->getDisplayValue('cf_1457') == 'Selling'){
						continue; 
					}
					
					$expense_record = $model->getInstanceById($model->getId());			
										
					//$col_data['myname'] = '<a class="relationDelete"><i class="icon-trash alignMiddle" title="Delete"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Edit&amp;record=42414&amp;jrertype=expence&amp;cf_1457=Expence"><i class="icon-pencil alignMiddle" title="Edit"></i></a><a href="index.php?module=Jobexpencereport&amp;view=Detail&amp;record=42414&amp;mode=showDetailViewByMode&amp;requestMode=full"><i class="icon-th-list alignMiddle" title="Complete Details"></i></a>';
					$action = '';
					
					$col_data['jobexpencereportid'] = $model->getId();
					
					$job_id 			  = $model->rawData['jobid'];
					$sourceModule_job 	= 'Job';	
					//$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
					$job_info_detail = get_job_details($job_id);
					$ref_no_link = '<a href="index.php?module=Job&view=Detail&record='.$job_id.'&mode=showDetailViewByMode&requestMode=full" target="_blank" >'.$job_info_detail['cf_1198'].'</a>';
					$col_data['ref_no'] = $ref_no_link;
					
					$vpo = '<a href="VPO_export_pdf.php?record='.$model->rawData['vpoid'].'" >'.$model->rawData['vpo_order_no'].'-'.$model->rawData['vpoid'].'</a>';
					
					//$col_data['myname'] = $action;
					//$col_data['coordinator'] ='';
					$col_data['b_gl_account'] = $model->get('b_gl_account');
					$col_data['b_ar_gl_account'] = $model->get('b_ar_gl_account');
					$vpo_order_no = $model->rawData['vpo_order_no'];
					$col_data['vpo_order_no'] = (!empty($vpo_order_no)? $vpo:'');	
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $selling_arr))
						{
							continue;
						}				
						
						$RELATED_HEADERNAME = $header_info->get('name');
						if(in_array($RELATED_HEADERNAME, $ui_type_array))
						{
							$column_value = $model->getDisplayValue($RELATED_HEADERNAME);
						}else{
							$column_value = $model->get($RELATED_HEADERNAME);
						}
						
						if(empty($column_value))
						{
							$column_value =$model->rawData[$RELATED_HEADERNAME];
						}
						$col_data[$RELATED_HEADERNAME] = $column_value;					
					}
					
					//$col_data['cf_1351'] = $expense_record->get('cf_1351');
					/*$assigned_user_info = Users_Record_Model::getInstanceById($expense_record->get('assigned_user_id'), 'Users');					
					$col_data['assigned_user_id'] = $assigned_user_info->get('first_name').' '.$assigned_user_info->get('last_name');
					*/
					$currency_info =  Vtiger_CurrencyList_UIType::getDisplayValue($model->rawData['cf_1345']);
					$col_data['cf_1345'] = $currency_info;
					
					$location  = Vtiger_Record_Model::getInstanceById($expense_record->get('cf_1477'), 'Location');
					$col_data['cf_1477'] = $location->get('cf_1559');
					
					$department  = Vtiger_Record_Model::getInstanceById($expense_record->get('cf_1479'), 'Department');
					$col_data['cf_1479'] = $department->get('cf_1542');
					
					$companyaccount  = Vtiger_Record_Model::getInstanceById($model->rawData['cf_1453'], 'CompanyAccount');
					$chartofaccount  = Vtiger_Record_Model::getInstanceById($companyaccount->get('cf_1501'), 'ChartofAccount');
					$col_data['cf_1453'] = $chartofaccount->get('name');
					
					//$col_data['b_confirmed_send_to_accounting_software'] = ($model->rawData['b_confirmed_send_to_accounting_software']==1 ? 'Accept':'Select');
					if($count_parent_role>3)
					{
					$col_data['payables_action'] = ($model->rawData['b_confirmed_send_to_accounting_software']==1 ? 'Accept':'Select');
					}
					else{
						if($head_status=='Approved' || $head_status=='Declined')
						{
							$col_data['cf_1973'] = 	$model->rawData['cf_1973'];
							if($head_status=='Approved')
							{	
							$col_data['cf_1975'] = $model->rawData['cf_1975'];	
							}
						}
						else
						{
						$col_data['send_to_payables_and_generate_payment_voucher'] = ($model->get('b_send_to_payables_and_generate_payment_voucher')==1 ? 'Accept':'Select');
						}
					}
					$buying['rows'][$i++] = $col_data;
					
				}
			
			echo json_encode($buying);
			exit;
			//return json_encode($buying);	
		}
		
		
		
		//EXPENSE PAYABLES
		$expence_header_payables[] = 'Expense ID';
		$buying_field_payables[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
		
		$expence_header_payables[] = 'Job Number';
		$buying_field_payables[] = array('name' => 'ref_no', "align" => "center", 'width'=>'115', 'frozen'=>true);
				
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
			
			$expence_header_payables[] = vtranslate($header_info->get('label').'_SHORT', $moduleName);
			//$RELATED_HEADERNAME = $header_info->get('name');
			//echo $model->getDisplayValue($RELATED_HEADERNAME);
			//echo "<br>";
			$RELATED_HEADERNAME = $header_info->get('name');
			$frozen = ($i<4 ? true:false);
			$buying_field_payables[] = array('name' =>$RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' => ($header_info->get('column')=='cf_1367' ? '200':'100' ), 'frozen' => $frozen);
			$i++;
			
		}
		
		
		/*foreach($models as $key => $model){
		echo $model->rawData['cf_1975'];
		exit;
		}*/
		if($count_parent_role>3)
		{	
		$buying_field_payables[] = array('name' =>'payables_action', 'index' => 'payables_action', "width" => "200", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));
		$expence_header_payables[] ='Payables Action';
		}
		else{
			if($head_status=='Approved' || $head_status=='Declined')
			{				
					$expence_header_payables[] = 'Head Status';
					$buying_field_payables[] = array('name' => 'cf_1973', "align" => "center", 'width'=>'80');
				if($head_status=='Approved')
				{
					$expence_header_payables[] = 'Payable Status';
					$buying_field_payables[] = array('name' => 'cf_1975', "align" => "center", 'width'=>'80');
				}
			}
			else
			{
			$buying_field_payables[] = array('name' =>'send_to_payables_and_generate_payment_voucher', 'index' => 'send_to_payables_and_generate_payment_voucher', "width" => "200", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));	
			//$buying_field_payables[] = array('name' =>'payables_action', 'index' => 'payables_action', "width" => "200", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));
			$expence_header_payables[] ='Send to Payables';
			}
		}
		
		
					
		$viewer->assign('EXPENCE_HEADER_PAYABLES' , "'" .implode("','",$expence_header_payables). "'");
		$viewer->assign('EXPENCE_FIELD_PAYABLES' , json_encode($buying_field_payables));			
		//EXPENSE PAYABLES END		
		
		
		//SELLING INVOICING initialization	
		$head_status = $get_first_record->rawData['cf_1250'];
		$job_id = $get_first_record->rawData['jobid'];
		
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_REQUEST['grid']==3) 
		{				
			
			//$buying = new StdClass;
			//$buying['page'] = 1;
			//$buying['total'] = 7;
			//$buying['records'] = 7;
			$selling = array();
			$i=0;
			$check_invoice_instruction_no = array();
			if(!empty($models))
			{
				foreach($models as $key => $model)
				{
					if($model->get('cf_1457') == 'Expence')
					{
						continue; 
					}
					
					$selling_record= $model->getInstanceById($model->getId());
					
					//$job_selling_info = Vtiger_Record_Model::getInstanceById($model->getId(), 'Jobexpencereport');
									
					$col_data['jobexpencereportid'] = $model->getId();
					
					$job_id 			  = $model->rawData['jobid'];
					$sourceModule_job 	= 'Job';	
					
					$job_info_detail = get_job_details($job_id);
					//$job_info_detail 	 = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
					$ref_no_link 		 = '<a href="index.php?module=Job&view=Detail&record='.$job_id.'&mode=showDetailViewByMode&requestMode=full" target="_blank" >'.$job_info_detail['cf_1198'].'</a>';
					$col_data['ref_no']  = @$ref_no_link;
					
					
					$col_data['gl_account'] = $model->rawData['gl_account'];
					$col_data['ar_gl_account'] = $model->rawData['ar_gl_account'];
					$col_data['invoice_no'] = $model->rawData['invoice_no'];
					
					$col_data['coordinator'] ='';
					
					foreach($header as $header_info)
					{
						if(in_array($header_info->get('column'), $expence_arr))
						{
							continue;
						}
						
						$RELATED_HEADERNAME = $header_info->get('name');
						if($RELATED_HEADERNAME=='assigned_user_id')
						{
						$column_value = $model->get($RELATED_HEADERNAME);	
						}
						else{						
						$column_value = $model->rawData[$RELATED_HEADERNAME];
						}
						$col_data[$RELATED_HEADERNAME] = $column_value; 						
					}
					
					
					//
					$accounts  = Vtiger_Record_Model::getInstanceById($selling_record->get('cf_1445'), 'Accounts');
					$col_data['cf_1445'] = $accounts->get('accountname');
					
					$currency_info =  Vtiger_CurrencyList_UIType::getDisplayValue($model->rawData['cf_1234']);
					$col_data['cf_1234'] = $currency_info;
					
					$location  = Vtiger_Record_Model::getInstanceById($selling_record->get('cf_1477'), 'Location');
					$col_data['cf_1477'] = $location->get('cf_1559');
					
					$department  = Vtiger_Record_Model::getInstanceById($selling_record->get('cf_1479'), 'Department');
					$col_data['cf_1479'] = $department->get('cf_1542');
					
					$companyaccount  = Vtiger_Record_Model::getInstanceById($model->rawData['cf_1455'], 'CompanyAccount');
					$chartofaccount  = Vtiger_Record_Model::getInstanceById($companyaccount->get('cf_1501'), 'ChartofAccount');
					$col_data['cf_1455'] = $chartofaccount->get('name');
				
					//echo $model->getDisplayValue('cf_1248');
					//echo "<br>";					
					$col_data['cf_1248'] = $selling_record->get('cf_1248');
					$col_data['cf_1250'] = $selling_record->get('cf_1250');
					
					$col_data['sdate'] = $model->get('cf_1355');
					//$col_data['cf_1248'] =$model->getDisplayValue('cf_1248');
					//$col_data['cf_1248'] = $model->getId();				
					if(!empty($job_id))
					{
						if($head_status=='Approved' || $head_status=='Declined')
						{				
							if($head_status=='Approved')
							{
								$col_data['send_to_1c'] = '';
							}
						}
						else{
						$col_data['invoice'] = ($model->get('accept_generate_invoice')==1 ? 'Accept':'Select');	
						}
					}
					$selling['rows'][$i++] = $col_data;
					
				}
			}
			echo json_encode($selling);
			exit;
		}
		
		
		$selling_header_invoice[] = 'Selling ID';
		$selling_field_invoice[] = array('name' => 'jobexpencereportid', 'key'=> true, 'index' => 'id', "align" => "center", 'hidden'=>true, 'frozen' =>true);
		
		$selling_header_invoice[] = 'Job Number';
		$selling_field_invoice[] = array('name' => 'ref_no', "align" => "center", 'width'=>'115', 'frozen'=>true);
		
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
				
				$selling_header_invoice[] = vtranslate($header_info->get('label').'_SHORT', $moduleName);
									
				$RELATED_HEADERNAME = $header_info->get('name');
				$frozen = ($i<4 ? true:false);
				$selling_field_invoice[] = array('name' => $RELATED_HEADERNAME, 'index' => $RELATED_HEADERNAME, 'width' =>($header_info->get('column')=='cf_1445' ? '200':'100' ), 'frozen' => $frozen);
				$i++;
			}
		//echo $current_user->get('roleid');
		//exit;	
		$selling_header_invoice[] = "Date";
		$selling_field_invoice[] = array("name" => 'sdate',"index" => 'sdate',"width"=>150, "editable" =>true);
		
		if(!empty($job_id))
		{
			if($head_status=='Approved' || $head_status=='Declined')
			{				
				if($head_status=='Approved')
				{
					$selling_header_invoice[] = 'Send to 1C';
					$selling_field_invoice[] = array('name' => 'send_to_1c', "align" => "center", "editable" => true, 
								"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
	
				}
			}
			else
			{
			$selling_header_invoice[] = "Generate Invoice";	
			$selling_field_invoice[] = array("name"=>'invoice',"index"=>'invoice', "width" => "225", "editable" => true, "edittype" => "select", "editoptions" => array("value" => "0:Select;accept:Accept;reject:Reject"));
			}
		}
		
		
		//$selling_header[] = 'Generate Invoice Instruction and Send to Invoicing coordinator';	
		//$selling_header[] = 'Invoicing Status';	
		//$selling_field[] = array('name' => 'cf_1248', "align" => "center", "editable" => true, 
		//					"edittype" => 'checkbox', "formatter" => 'checkbox', "formatoptions" =>  array( "disabled" => false ), "editoptions" => array("value" => 'Yes:No'));
		//$selling_field[] = array('name' => 'cf_1250', "align" => "center", 'width'=>'80');
		
		
		$viewer->assign('SELLING_HEADER_INVOICING' , "'" .implode("','",$selling_header_invoice). "'");	
		$viewer->assign('SELLING_FIELD_INVOICING' , json_encode($selling_field_invoice));
		//SELLING INVOICING END	
		
		
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$viewer->assign('JOB_ID', (!empty($job_id)? $job_id :'none'));
		
		if($count_parent_role>3)
		{
			if($current_user->get('roleid')=='H185')
			{				
				$viewer->assign('SELLING_INVOICE_STATUS', $head_status);
				$viewer->view('ListViewContentsInvoicing.tpl', $moduleName);
			}
			else{
				
				$viewer->view('ListViewContents.tpl', $moduleName);
			}
		}
		else{
		$viewer->assign('EXPENSE_HEAD_STATUS', $head_status_expense);	
		$viewer->view('ListViewContentsHead.tpl', $moduleName);	
		}
	}

	function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$viewer->view('ListViewPostProcess.tpl', $moduleName);
		parent::postProcess($request);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.List',
			"modules.$moduleName.resources.List",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			"modules.Emails.resources.MassEdit",
			"modules.Vtiger.resources.CkEditor"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */
	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$moduleName = $request->getModule();
		$cvId = $this->viewName;
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if($sortOrder == "ASC"){
			$nextSortOrder = "DESC";
			$sortImage = "icon-chevron-down";
		}else{
			$nextSortOrder = "ASC";
			$sortImage = "icon-chevron-up";
		}

		if(empty ($pageNumber)){
			$pageNumber = '1';
		}

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId);
		
		
		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'), 'CVID'=>$cvId);
		$linkModels = $listViewModel->getListViewMassActions($linkParams);

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);

		if(!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder',$sortOrder);
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if(!empty($operator)) {
			$listViewModel->set('operator', $operator);
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
		}
		if(!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		if(!$this->listViewHeaders){
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		
		if(!$this->listViewEntries){
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);

		$viewer->assign('MODULE', $moduleName);

		if(!$this->listViewLinks){
			$this->listViewLinks = $listViewModel->getListViewLinks($linkParams);
		}
		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);

		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER',$pageNumber);

		$viewer->assign('ORDER_BY',$orderBy);
		$viewer->assign('SORT_ORDER',$sortOrder);
		$viewer->assign('NEXT_SORT_ORDER',$nextSortOrder);
		$viewer->assign('SORT_IMAGE',$sortImage);
		$viewer->assign('COLUMN_NAME',$orderBy);

		$viewer->assign('LISTVIEW_ENTIRES_COUNT',$noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		

		if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
			if(!$this->listViewCount){
				$this->listViewCount = $listViewModel->getListViewCount();
			}
			$totalCount = $this->listViewCount;
			$pageLimit = $pagingModel->getPageLimit();
			$pageCount = ceil((int) $totalCount / (int) $pageLimit);

			if($pageCount == 0){
				$pageCount = 1;
			}
			$viewer->assign('PAGE_COUNT', $pageCount);
			$viewer->assign('LISTVIEW_COUNT', $totalCount);
		}

		$viewer->assign('IS_MODULE_EDITABLE', $listViewModel->getModule()->isPermitted('EditView'));
		$viewer->assign('IS_MODULE_DELETABLE', $listViewModel->getModule()->isPermitted('Delete'));
		
		
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param Vtiger_Request $request
	 */
	function getRecordsCount(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$count = $this->getListViewCount($request);

		$result = array();
		$result['module'] = $moduleName;
		$result['viewname'] = $cvId;
		$result['count'] = $count;

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get listView count
	 * @param Vtiger_Request $request
	 */
	function getListViewCount(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		if(empty($cvId)) {
			$cvId = '0';
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId);
		$listViewModel->set('search_key', $searchKey);
		$listViewModel->set('search_value', $searchValue);
		$listViewModel->set('operator', $request->get('operator'));

		$count = $listViewModel->getListViewCount();

		return $count;
	}



	/**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	function getPageCount(Vtiger_Request $request){
		$listViewCount = $this->getListViewCount($request);
		$pagingModel = new Vtiger_Paging_Model();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $listViewCount / (int) $pageLimit);

		if($pageCount == 0){
			$pageCount = 1;
		}
		$result = array();
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}