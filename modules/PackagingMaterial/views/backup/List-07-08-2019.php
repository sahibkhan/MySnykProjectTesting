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
											  vtiger_packagingmaterialcf.cf_5754=? AND vtiger_packagingmaterialcf.cf_6238=? 
											  AND vtiger_packagingmaterialcf.cf_6258!=?";
				$params_packagingmaterialcf = array($packaging_ref_no, $job_ref_no, 'Posted');
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
						
						$warehouseid = $sub_PackagingMaterial_info->get('cf_5764');
						$warehouseMaster_info = Vtiger_Record_Model::getInstanceById($warehouseid, 'WarehouseMaster');
						$warehouse_1c_Code = $warehouseMaster_info->get('cf_6254');
			
						$check_flag = false;
						//$createdTime = $job_info->get('CreatedTime');
						$CreateWriteOff =array('DateDoc' => date('YmdHis',strtotime($sub_PackagingMaterial_info->get('cf_5748'))), //Issue Date::cf_5748
											   'WarehouseCode' => $warehouse_1c_Code,
											   'PMRefNo' => $sub_PackagingMaterial_info->get('cf_5754'),
											   'JobRefNo' => $sub_PackagingMaterial_info->get('cf_6238'),
											   'CreatedBy' => $CreatedBy,
											   'FileTitle' => $job_info->getDisplayValue('cf_1186'),
											   'Location'  =>  $job_info->getDisplayValue('cf_1188'), 
											   'Department'  =>  $job_info->getDisplayValue('cf_1190'),
											  );
					}


					 $Item_code = $sub_PackagingMaterial_info->get('cf_5738');
					 $custom_request = $sub_PackagingMaterial_info->get('cf_6290');
					 
					 if (!empty($Item_code)) {
						 
						 if($custom_request!='Yes') {
						$sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
										   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
										   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
										   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='{$Item_code}'";
								   
						$rs_item = mysql_query($sql_item_query);
						$row_item = mysql_fetch_array($rs_item); 
						
						$item_1c_code = $row_item['cf_6208']; //1C code
						$item_name = $row_item['name']; //item name
						$item_description = $row_item['cf_5565']; //item code
						 
						$packagingmaterial_arr_to_1c['Element'][] = array( 
																		'NomenclatureID' => $item_1c_code,
																		'NomenclatureName' => $item_name,
																		'NomenclatureDescription' => $item_description,
																		'NQuantity' => $sub_PackagingMaterial_info->get('cf_5746'),
																		'NPrice' => $sub_PackagingMaterial_info->get('cf_6142'),
																		'NVAT' => 'wo',
																		'NSum' => $sub_PackagingMaterial_info->get('cf_6142')
																		);
						 }
						 else{
							$db_cpm = PearDatabase::getInstance();	
							$query_custom_packaging = "SELECT * FROM vtiger_custompackingmaterial
										INNER JOIN  vtiger_custompackingmaterialcf ON vtiger_custompackingmaterialcf.custompackingmaterialid = vtiger_custompackingmaterial.custompackingmaterialid
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_custompackingmaterial.custompackingmaterialid
										INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
										WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
										AND crmentityrel.module='PackagingMaterial' AND crmentityrel.relmodule='CustomPackingMaterial'";
							$params_rel = array($packagingmaterialid);	
													   
							$result_rel = $db_cpm->pquery($query_custom_packaging, $params_rel);
							$numRows_cpm = $db_cpm->num_rows($result_rel);	
							
							for($kk=0; $kk< $db_cpm->num_rows($result_rel); $kk++ ) {
								$row_sub_packaging = $db_cpm->fetch_row($result_rel,$kk);	
								
								$sub_item_code =$row_sub_packaging['cf_6268'];
								$sub_total_item = $row_sub_packaging['cf_6276'];
								
								$sql_item_query = "SELECT * FROM vtiger_whitemmastercf 
										   INNER JOIN vtiger_whitemmaster ON vtiger_whitemmaster.whitemmasterid = vtiger_whitemmastercf.whitemmasterid
										   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemmaster.whitemmasterid
										   WHERE vtiger_crmentity.deleted = 0 AND vtiger_whitemmastercf.cf_5565='{$sub_item_code}'";
								   
								$rs_item = mysql_query($sql_item_query);
								$row_item = mysql_fetch_array($rs_item); 
								
								$item_1c_code = $row_item['cf_6208']; //1C code
								$item_name = $row_item['name']; //item name
								$item_description = $row_item['cf_5565']; //item code
								 
								$packagingmaterial_arr_to_1c['Element'][] = array( 
																				'NomenclatureID' => $item_1c_code,
																				'NomenclatureName' => $item_name,
																				'NomenclatureDescription' => $item_description,
																				'NQuantity' => $sub_total_item,
																				'NPrice' => $row_sub_packaging['cf_6282'],
																				'NVAT' => 'wo',
																				'NSum' => $row_sub_packaging['cf_6282']
																				);
								$CreateWriteOff['DateDoc'] =date('YmdHis',strtotime($row_sub_packaging['cf_6278']));												
								
							}
							
						 }
							
						
						 	
					 }
					
					
				}
				
				
				$CreateWriteOff['NomenclatureTable'] = $packagingmaterial_arr_to_1c;
				
				//$web1C = 'http://89.218.38.221/gl/ws/CreateWriteOff?wsdl';  //Test Webservice link
				$web1C = 'http://89.218.38.221/glws/ws/CreateWriteOff?wsdl';  //Test Webservice link
				$con1C = array( 'login' => 'AdmWS',
								'password' => '6fc@t\Vy',
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
					  $PMRefNo_no = $PMRefNo->return;
					  if($PMRefNo_no==$packaging_ref_no)
					  {					 			  
						  $sql = "UPDATE vtiger_packagingmaterialcf SET cf_6258 = 'Posted', cf_6256 = '".date('Y-m-d')."' WHERE cf_5754 = '".$PMRefNo_no."'";
						  $result = $db->pquery($sql);
						  
						   //Item issue Notification to RRS Supervisor
						  $current_user = Users_Record_Model::getCurrentUserModel();	
						  $assigned_user_id = $PackagingMaterial_info->get('assigned_user_id');
						  $PackagingMaterial_user_info = Vtiger_Record_Model::getInstanceById($assigned_user_id, 'Users');
						  
						  $packaging_items='';  
						  $pagingModel_1 = new Vtiger_Paging_Model();
						  $pagingModel_1->set('page','1');
						
						  $relatedModuleName_1 = 'PackagingMaterial';
						  $parentRecordModel_1 = $job_info;
						  $relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
						  $models_1 = $relationListView_1->getEntries($pagingModel_1);
						  
						  	$pm_items = '';
							$total_amount=0;
							$i=1;
							foreach($models_1 as $key => $model){
									$packaging_material_items_id  = $model->getId();			
									$sourceModule   = 'PackagingMaterial';	
									$pmitem_info = Vtiger_Record_Model::getInstanceById($packaging_material_items_id, $sourceModule);
									if($pmitem_info->get('cf_5754')==$PMRefNo_no)
									{
										$detail = $pmitem_info->getDisplayValue('cf_6292');
										$parent_numbering =$i;
										$packaging_items .='<tr>
															<td>'.$i++.'</td>
															<td>'.$pmitem_info->getDisplayValue('cf_5738').''.(!empty($detail) ? '<br>'.$detail : '' ).'</td>
															<td>'.$pmitem_info->getDisplayValue('cf_5740').'</td>
															<td>'.$pmitem_info->getDisplayValue('cf_5744').'</td>
															<td>'.$pmitem_info->getDisplayValue('cf_5746').'</td>
															<td>'.$pmitem_info->getDisplayValue('cf_5748').'</td>										
															</tr>';
										$custom_request = $pmitem_info->get('cf_6290');
										if($custom_request=='Yes')
										{
											$db_cpm = PearDatabase::getInstance();	
											$query_custom_packaging = "SELECT * FROM vtiger_custompackingmaterial
														INNER JOIN  vtiger_custompackingmaterialcf ON 
												vtiger_custompackingmaterialcf.custompackingmaterialid = vtiger_custompackingmaterial.custompackingmaterialid
														INNER JOIN vtiger_crmentity ON 
														vtiger_crmentity.crmid = vtiger_custompackingmaterial.custompackingmaterialid
														INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
														WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
														AND crmentityrel.module='PackagingMaterial' AND crmentityrel.relmodule='CustomPackingMaterial'";
											$params_rel = array($packaging_material_items_id);							   
											$result_rel = $db_cpm->pquery($query_custom_packaging, $params_rel);
											$numRows_cpm = $db_cpm->num_rows($result_rel);	
											//To Access Custom Item Code
											$child_numbering=1;
											for($kk=0; $kk< $db_cpm->num_rows($result_rel); $kk++ ) {
												
												$row_sub_packaging = $db_cpm->fetch_row($result_rel,$kk);
												$custompackingmaterialid = $row_sub_packaging['custompackingmaterialid'];
												$c_sourceModule   = 'CustomPackingMaterial';
												$custom_pmitem_info = Vtiger_Record_Model::getInstanceById($custompackingmaterialid, $c_sourceModule);
												
												$packaging_items .='<tr>
																	<td>'.$parent_numbering.'.'.$child_numbering++.'</td>
																	<td>'.$custom_pmitem_info->getDisplayValue('cf_6268').'</td>
																	<td></td>
																	<td></td>
																	<td>'.$custom_pmitem_info->getDisplayValue('cf_6276').'</td>
																	<td>'.$custom_pmitem_info->getDisplayValue('cf_6278').'</td>										
																	</tr>';
												
											}
										}
									}
									
							}
						
						$content = $this->print_packaging_material($packagingmaterialid);
						$content = chunk_split(base64_encode($content));
						
						$separator = md5(time());
						// carriage return type (we use a PHP end of line constant)
						$eol = PHP_EOL;						
						// attachment name
						$filename = "PackagingMaterial_".$packagingmaterialid.".pdf";
						//$pdfdoc is PDF generated by FPDF
						$attachment = $content;
						
						$from = "From: ".$current_user->get('email1')." <".$current_user->get('email1').">";
						
						$to = $PackagingMaterial_user_info->get('email1');
						//$to = 's.mehtab@globalinklogistics.com';
						$cc  = $current_user->get('email1').';g.moldakanova@globalinklogistics.com;s.mehtab@globalinklogistics.com;';
						//$cc= '';
						
						// main header
						$headers  = $from.$eol;
						$headers .= 'Reply-To: '.$to.'' . "\n";
						$headers .= "CC:" . $cc . "\r\n";
						$headers .= "MIME-Version: 1.0".$eol; 
						$headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"";
						
						
						
						$body = "--".$separator.$eol;
						$body .= "Content-Type: text/html; charset=\"UTF-8\"".$eol;
						$body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
						//$body .= "This is a MIME<br> encoded message.".$eol;
						
						
						
						$body .="<p>Dear&nbsp; ".$PackagingMaterial_user_info->get('first_name').",</p>".$eol;
						$body .="<p>Below Item list issued from warehouse for job file ".$job_ref_no.".</p>".$eol;
						
						$body .='<table  border=1 cellspacing=0 cellpadding=4  width="100%"   ><tbody>
									<tr><td width="250"><strong>Packaging Ref #</strong></td>
										<td width="200"><strong>'.$PMRefNo_no.'</strong></td>
										<td width="200"><strong>Warehouse ID</strong></td>
										<td width="150"><strong>'.$PackagingMaterial_info->getDisplayValue('cf_5764').'</strong>
										</td></tr>								
								</tbody>    
							</table>'.$eol;
						$body .="<br>Packaging Material Items Details.<br>".$eol;
						$body .='<table border=1 cellspacing=0 cellpadding=5  width="100%"><tbody>
							<tr><td width="20"><strong>#</strong></td><td width="60"><strong>Type</strong></td><td width="60"><strong>QTY Requested
							</strong></td><td width="60"><strong>Requested Date</strong></td>
							<td width="60"><strong>QTY Issued</strong></td><td width="60"><strong>Issue Date</strong></td></tr>
							'.$packaging_items.'
							</tbody>
							</table>'.$eol;
						$body .="<p>Regards,</p>".$eol;
						$body .="<p><strong>".$current_user->get('first_name')." ".$current_user->get('last_name')."</strong></p>".$eol;
						$body .="<p><strong>Globalink Logistics - </strong><br />".$eol;
						$body .="<u><a href='mailto:".$current_user->get('email1')."'>".$current_user->get('email1')."</a></u>&nbsp; <strong>I&nbsp;</strong> Web: <u><a href='http://www.globalinklogistics.com/'>www.globalinklogistics.com</a></u><br />".$eol;
						$body .="ASIA SPECIALIST ∙ CHINA FOCUS ∙ GLOBAL NETWORK<br />".$eol;
						$body .="Important Notice. All Globalink services are undertaken subject to Globalink&#39;s Terms and Conditions of Trading. These may exclude or limit our liability in the event of claims for loss, damage and delay to cargo or otherwise and provide for all disputes to be arbitrated in London under English law.&nbsp; Please view and download our Terms and Conditions of Trading from our website <a href='http://globalinklogistics.com/Trading-Terms-and-Conditions'>http://globalinklogistics.com/Trading-Terms-and-Conditions</a></p>".$eol;
						
						
						// attachment
						$body .= "--".$separator.$eol;
						$body .= "Content-Type: application/pdf; name=\"".$filename."\"".$eol; 
						$body .= "Content-Transfer-Encoding: base64".$eol;
						$body .= "Content-Disposition: attachment".$eol.$eol;
						$body .= $attachment.$eol;
						$body .= "--".$separator."--";
						
						// no more headers after this, we start the body! //
						
					   /* $headers = "MIME-Version: 1.0" . "\n";
					    $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
					    $headers .= $from . "\n";
					    $headers .= 'Reply-To: '.$to.'' . "\n";
					    $headers .= "CC:" . $cc . "\r\n";*/
						$subject = "Job File Packaging Material Issued :: ".$packaging_ref_no."";
						mail($to,$subject,$body,$headers);						  
						  
						echo 'Success_'.date('Y-m-d');
					  }
					  
					} catch (SoapFault $e) {
						echo "<pre>";
						print_r($e);
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
	
		
	
	 function get_job_id_from_PackagingMaterial($recordId=0) {
			$adb = PearDatabase::getInstance();

			$checkjob = $adb->pquery("SELECT rel1.crmid AS job_id
																FROM `vtiger_crmentityrel` AS rel1
																WHERE rel1.relcrmid = '".$recordId."' AND rel1.module='Job' AND rel1.relmodule='PackagingMaterial'", array());
			$crmId = $adb->query_result($checkjob, 0, 'job_id');
			$job_id = $crmId;
			return $job_id;
		}


	public function print_packaging_material($record) {
	 
    $moduleName = 'PackagingMaterial';
	$record 	 =  $record;
	
    $parentid   = 0;
	if($parentid==0){
		 $parentid = $this->get_job_id_from_PackagingMaterial($record);
	}
	
    $packaging_record_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
	
    $job_info_detail = Vtiger_Record_Model::getInstanceById($parentid, 'Job');
	
    $packaging_info_detail = Vtiger_Record_Model::getInstanceById($packaging_record_id, 'PackagingMaterial');
		
	$document = $this->loadTemplate('printtemplates/whm/packaging.html');
    
	$owner_user_info = Users_Record_Model::getInstanceById($packaging_info_detail->get('assigned_user_id'), 'Users');
	
	$this->setValue('useroffice',$owner_user_info->getDisplayValue('location_id'));
	$this->setValue('userdepartment',$owner_user_info->getDisplayValue('department_id'));
	$this->setValue('mobile',$owner_user_info->get('phone_mobile'));
    $this->setValue('fax',$owner_user_info->get('phone_fax'));
    $this->setValue('email',htmlentities($owner_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
    $this->setValue('cityname',htmlentities($owner_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
    $this->setValue('countryname',htmlentities($owner_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
    $this->setValue('departmentcode',htmlentities($owner_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
    $this->setValue('dateadded',date('d.m.Y', strtotime($packaging_info_detail->get('CreatedTime'))));        
    $this->setValue('from', htmlentities($owner_user_info->get('first_name').' '.$owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
    
	$this->setValue('job_ref_no', $job_info_detail->get('cf_1198'));
	$this->setValue('packaging_ref_no',$packaging_info_detail->get('cf_5754')); 
	$this->setValue('warehouse_id',$packaging_info_detail->getDisplayValue('cf_5764'));
	
	$rs = mysql_query("SELECT cf_6294 FROM vtiger_packagingmaterialcf where cf_6294!='' AND cf_5754='".$packaging_info_detail->get('cf_5754')."' limit 1");
	$row_driver = mysql_fetch_array($rs);
	$driver_name = $row_driver['cf_6294'];
	$this->setValue('driver_name', $driver_name);
	
	$packaging_items='';  
	$pagingModel_1 = new Vtiger_Paging_Model();
	$pagingModel_1->set('page','1');
	
	$relatedModuleName_1 = 'PackagingMaterial';
	$parentRecordModel_1 = $job_info_detail;
	$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
	$models_1 = $relationListView_1->getEntries($pagingModel_1);
	
	$pm_items = '';
	$total_amount=0;
	$i=1;
	foreach($models_1 as $key => $model){
			$packaging_material_items_id  = $model->getId();			
			$sourceModule   = 'PackagingMaterial';	
			$pmitem_info = Vtiger_Record_Model::getInstanceById($packaging_material_items_id, $sourceModule);
			if($pmitem_info->get('cf_5754')==$packaging_info_detail->get('cf_5754'))
			{
				$parent_numbering =$i;
				$packaging_items .='<tr>
									<td>'.$i++.'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5738').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5740').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5744').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5746').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5748').'</td>
									<td>'.$pmitem_info->get('cf_6142').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5750').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5752').'</td>
									</tr>';
				$total_amount +=$pmitem_info->get('cf_6142');
				
				$custom_request = $pmitem_info->get('cf_6290');
				if($custom_request=='Yes')
				{
					$db_cpm = PearDatabase::getInstance();	
					$query_custom_packaging = "SELECT * FROM vtiger_custompackingmaterial
								INNER JOIN  vtiger_custompackingmaterialcf ON 
						vtiger_custompackingmaterialcf.custompackingmaterialid = vtiger_custompackingmaterial.custompackingmaterialid
								INNER JOIN vtiger_crmentity ON 
								vtiger_crmentity.crmid = vtiger_custompackingmaterial.custompackingmaterialid
								INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
								WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
								AND crmentityrel.module='PackagingMaterial' AND crmentityrel.relmodule='CustomPackingMaterial'";
					$params_rel = array($packaging_material_items_id);							   
					$result_rel = $db_cpm->pquery($query_custom_packaging, $params_rel);
					$numRows_cpm = $db_cpm->num_rows($result_rel);	
					//To Access Custom Item Code
					$child_numbering=1;
					for($kk=0; $kk< $db_cpm->num_rows($result_rel); $kk++ ) {
						
						$row_sub_packaging = $db_cpm->fetch_row($result_rel,$kk);
						$custompackingmaterialid = $row_sub_packaging['custompackingmaterialid'];
						$c_sourceModule   = 'CustomPackingMaterial';
						$custom_pmitem_info = Vtiger_Record_Model::getInstanceById($custompackingmaterialid, $c_sourceModule);
						
						$packaging_items .='<tr>
											<td>'.$parent_numbering.'.'.$child_numbering++.'</td>
											<td>'.$custom_pmitem_info->getDisplayValue('cf_6268').'</td>
											<td></td>
											<td></td>
											<td>'.$custom_pmitem_info->getDisplayValue('cf_6276').'</td>
											<td>'.$custom_pmitem_info->getDisplayValue('cf_6278').'</td>
											<td>'.$custom_pmitem_info->get('cf_6282').'</td>
											<td></td>
											<td></td>
											</tr>';
						$total_amount +=$custom_pmitem_info->get('cf_6282');					
						
					}	
				}
			}
			
	}
	
	
    $this->setValue('packaging_items',$packaging_items);
	 $this->setValue('total_amount',$total_amount);
	
    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
	
	
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 
    //$filename = 'fleet_expense.txt';
    //$this->save('fleet_expense.txt'); 
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            PMR Form, GLOBALINK
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
        </tr>
      </table>');
	
    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>');

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
        
    $pdf_name = 'pdf_docs/packaging_material.pdf';
    
	return $content = $mpdf->Output('', 'S'); // Saving pdf to attach to email 
   // $mpdf->Output($pdf_name, 'F');
    //header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
   // header('Location:'.$pdf_name);
    
  }
	
	public function template($strFilename)
	  {
		$path = dirname($strFilename);
		//$this->_tempFileName = $path.time().'.docx';
		// $this->_tempFileName = $path.'/'.time().'.txt';
		$this->_tempFileName = $strFilename;
		//copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File
		$this->_documentXML = file_get_contents($this->_tempFileName);
	  }
  
	  /**
	   * Set a Template value
	   * 
	   * @param mixed $search
	   * @param mixed $replace
	   */
	  public function setValue($search, $replace) {
		if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
		  $search = '${'.$search.'}';
		}
		// $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");
		if(!is_array($replace)) {
		  // $replace = utf8_encode($replace);
		  $replace =iconv('utf-8', 'utf-8', $replace);
		}
		$this->_documentXML = str_replace($search, $replace, $this->_documentXML);
	  }
  
	  /**
	   * Save Template
	   * 
	   * @param string $strFilename
	   */
	  public function save($strFilename) {
		if(file_exists($strFilename)) {
		  unlink($strFilename);
		}
		//$this->_objZip->extractTo('Fleettrip.txt', $this->_documentXML);
		file_put_contents($this->_tempFileName, $this->_documentXML);
		// Close zip file
		/* if($this->_objZip->close() === false) {
		  throw new Exception('Could not close zip file.');
		}*/  
		rename($this->_tempFileName, $strFilename);
	  }
  
	  public function loadTemplate($strFilename) {
		if(file_exists($strFilename)) {
		  $template = $this->template($strFilename);
		  return $template;
		} else {
		  trigger_error('Template file '.$strFilename.' not found.', E_ERROR);
		}
	  }
}