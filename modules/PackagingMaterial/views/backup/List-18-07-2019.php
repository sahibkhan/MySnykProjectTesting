<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class PackagingMaterial_List_View extends Vtiger_List_View {
	protected $listViewEntries = false;
	protected $listViewCount = false;
	protected $listViewLinks = false;
	protected $listViewHeaders = false;
	function __construct() {
		parent::__construct();
		$this->exposeMethod('packagingMaterial_to_1c');
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
		
		$mode = $request->get('mode');
        if(!empty($mode)) {
            $this->invokeExposedMethod($mode,$request);
			exit;
		}
		
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$this->viewName = $request->get('viewname');
		
		$this->initializeListViewContents($request, $viewer);
		
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('ListViewContents.tpl', $moduleName);
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

		$viewer->assign('LISTVIEW_ENTIRES_COUNT',($noOfEntries==19 ? 20 : $noOfEntries));
		//$viewer->assign('LISTVIEW_ENTIRES_COUNT',$noOfEntries);
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
	
	function packagingMaterial_to_1c(Vtiger_Request $request)
	{	
		global $db;
		$db = PearDatabase::getInstance();	
		$check_packaging_ref_no = array();
		
		$recordIds = $request->get('record');
		foreach($recordIds as $record){
			
			 $PackagingMaterial_info = Vtiger_Record_Model::getInstanceById($record, 'PackagingMaterial');
			 $packaging_ref_no = $PackagingMaterial_info->get('cf_5754');
			 $job_ref_no = $PackagingMaterial_info->get('cf_6238');
			 if(!in_array($packaging_ref_no, $check_packaging_ref_no) && $PackagingMaterial_info->get('cf_6124')=='Received' && !empty($job_ref_no))
			 {
				$check_packaging_ref_no[] = $packaging_ref_no;
				
				$result_jobcf = $db->pquery("SELECT jobid FROM vtiger_jobcf WHERE cf_1198='".$job_ref_no."' ");
				$row_jobcf = $db->fetch_row($result_jobcf);
				$job_id = $row_jobcf['jobid'];
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
				
				
				$query_packagingmaterialcf = "SELECT * FROM vtiger_packagingmaterialcf
											  INNER JOIN  vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_packagingmaterialcf.packagingmaterialid
											  WHERE vtiger_crmentity.deleted=0 AND
											  vtiger_packagingmaterialcf.cf_5754=? AND vtiger_packagingmaterialcf.cf_6238=? ";
				$params_packagingmaterialcf = array($packaging_ref_no, $job_ref_no);
				$result_packagingmaterialcf = $db->pquery($query_packagingmaterialcf, $params_packagingmaterialcf);
				$numRows_packagingmaterialcf = $db->num_rows($result_packagingmaterialcf);
				
				$check_flag = true;
				$packagingmaterial_arr_to_1c = array();
				$CreateWriteOff = array();
				
				for($jj=0; $jj< $db->num_rows($result_packagingmaterialcf); $jj++ ) {
					
					$row_packagingmaterialcf = $db->fetch_row($result_packagingmaterialcf,$jj);
					$packagingmaterialid = $row_packagingmaterialcf['packagingmaterialid'];
					
					$sub_PackagingMaterial_info = Vtiger_Record_Model::getInstanceById($packagingmaterialid, 'PackagingMaterial');
					if($check_flag)
					{
						$smownerid = $sub_PackagingMaterial_info->get('assigned_user_id'); 
						$userRecord = Vtiger_Record_Model::getInstanceById($smownerid, 'Users');
						$firstName = $userRecord->get('first_name');
						$lastName = $userRecord->get('last_name');
						$CreatedBy = $firstName." ".$lastName;
			
						$check_flag = false;
						$CreateWriteOff =array('DateDoc' => $sub_PackagingMaterial_info->get('cf_5748'), //Issue Date
											   'WarehouseCode' => $sub_PackagingMaterial_info->getDisplayValue('cf_5764'),
											   'PMRefNo' => $sub_PackagingMaterial_info->get('cf_5754'),
											   'JobRefNo' => $sub_PackagingMaterial_info->get('cf_6238'),
											   'CreatedBy' => $CreatedBy,
											   'FileTitle' => $job_info->getDisplayValue('cf_1186'),
											   'Location'  =>  $job_info->getDisplayValue('cf_1188'), 
											   'Department'  =>  $job_info->getDisplayValue('cf_1190'),
											  );
					}


					 $Item_code = $sub_PackagingMaterial_info->get('cf_5738');
					 if ($Item_code) {
						 
						 $sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
										   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
										   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
										   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='{$item_code}'";
						$rs_item = mysql_query($sql_item_query);
						$row_item = mysql_fetch_array($rs_item);				   
						 
						$item_1c_code = $row_item['cf_6208']; //1C code
						$item_name = $row_item['name']; //item name
						$item_description = $row_item['cf_5565']; //item code
						 
						$packagingmaterial_arr_to_1c['ElementTZ'][] = array( 
																			'NomenclatureID' => $item_1c_code,
																			'NomenclatureName' => $item_name,
																			'NomenclatureDescription' => $item_description,
																			'NQuantity' => $sub_PackagingMaterial_info->get('cf_5746'),
																			'NPrice' => $sub_PackagingMaterial_info->get('cf_6142'),
																			'NVAT' => 'wo',
																			'NSum' => $sub_PackagingMaterial_info->get('cf_6142')
																			);
							
						
						 	
					 }
					
					
				}
				
				$CreateWriteOff['NomenclatureTable'] = $packagingmaterial_arr_to_1c;
				
				$web1C = 'http://89.218.38.221/gl/ws/CreateWriteOff?wsdl'; 
				$con1C = array( 'login' => 'AdmWS',
									'password' => '906900',
									'soap_version' => SOAP_1_2,
									'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
									'exceptions' => true,
									'trace' => 1);
									
				if (!function_exists('is_soap_fault')) {
				echo '<br>not found module php-soap.<br>';
				return false;
				}
				try {
					$Client1C = new SoapClient($web1C, $con1C);
								
				} catch(SoapFault $e) {
					var_dump($e);
					echo '<br>error at connecting to 1C<br>';
					return false;
				}
				if (is_soap_fault($Client1C)){
					echo '<br>inner server error at connecting to 1C<br>';
					return false;
				}
				
				$idc = $Client1C;
				$par = $CreateWriteOff;
				
				if (is_object($idc)) {
				try {
						
					  $PMRefNo = $idc->CreateWriteOff($par);
					  echo "<pre>";
					  print_r($PMRefNo);
					  //print_r($ret1c);
					 // $sql = "UPDATE vtiger_jobcf SET cf_5848 = 'Success', cf_5846 = '".date('Y-m-d')."' WHERE jobid = '".$jobid."'";
					 // $result = $adb->pquery($sql);
					  echo 'Success_'.date('Y-m-d');
					  
					} catch (SoapFault $e) {
						//echo "<pre>";
						//print_r($e);
						echo 'Failed_';
					}   
				}
				else{
					var_dump($idc);
					echo '<br>no connection to 1C<br>';
				}					
				
			 }
		}		
	}
}