<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class NCRS_Edit_View extends Vtiger_Edit_View {
    protected $record = false;
	function __construct() {
		
		parent::__construct();
		//$this->exposeMethod('approveNCR');
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$recordPermission = Users_Privileges_Model::isPermitted($moduleName, 'EditView', $record);

		if(!$recordPermission) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {

	
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
        if(!empty($record) && $request->get('isDuplicate') == true) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $viewer->assign('MODE', '');
        }else if(!empty($record)) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $viewer->assign('RECORD_ID', $record);
            $viewer->assign('MODE', 'edit');
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $viewer->assign('MODE', '');
        }
        if(!empty($record)) {
			$ncrlist = $this->ncrrecords($record);
			$ncrid = $ncrlist[0]['relatedrecordid'];
			$viewer->assign('ncrlistid', $ncrid);
			unset($ncrlist[0]); //print_r($ncrid); exit;
			$viewer->assign('ncrlist', $ncrlist);
			//print_r($ncrlist); exit;
			if(!empty($ncrid)){
		$recordModel1 = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($ncrid, "NCRRaised");
		}else{
		$recordModel1 = Vtiger_Record_Model::getCleanInstance("NCRRaised");
		}
		}else{
		$recordModel1 = Vtiger_Record_Model::getCleanInstance("NCRRaised");
		}
        if(!$this->record){
            $this->record = $recordModel;
        }
        
		$moduleModel = $recordModel->getModule();
		$moduleModel1 = $recordModel1->getModule();
		$fieldList = $moduleModel->getFields();
		$fieldList1 = $moduleModel1->getFields(); 
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);
		$requestFieldList1 = array_intersect_key($request->getAll(), $fieldList1);
		//echo "<pre>"; print_r($recordStructureInstance); exit;
		foreach($requestFieldList1 as $fieldName=>$fieldValue){
			$fieldModel = $fieldList1[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) { 
				$specialField = true; 
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i"); 
                
			}
            
            if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) { 
                $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
                $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
                list($startDate, $startTime) = explode(' ', $startDateTime);
                $fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
            }
			if($fieldModel->isEditable() || $specialField) {
				$recordModel1->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		foreach($requestFieldList as $fieldName=>$fieldValue){
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) { 
				$specialField = true; 
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i"); 
                
			}
            
            if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) { 
                $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
                $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
                list($startDate, $startTime) = explode(' ', $startDateTime);
                $fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
            }
			if($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$recordStructureInstance1 = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel1, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('RECORD_STRUCTURE_MODEL1', $recordStructureInstance1);
		$viewer->assign('RECORD_STRUCTURE1', $recordStructureInstance1->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));









// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);









		$moduleName = $request->getModule();
		$relatedModuleName = "NCRRaised";//$request->get('relatedModule');
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

		//$viewer = $this->getViewer($request);
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
        //return $viewer->view('RelatedList.tpl', $moduleName, 'true');





		$viewer->view('EditView.tpl', $moduleName);
	}
	
	private function ncrrecords($sourcerecordid) {
		$db = PearDatabase::getInstance();
		//$id = $request->get('record');	
			$query = "SELECT * FROM vtiger_ncrraised INNER JOIN vtiger_ncrraisedcf ON vtiger_ncrraised.ncrraisedid = vtiger_ncrraisedcf.ncrraisedid
INNER JOIN vtiger_crmentity ON vtiger_ncrraised.ncrraisedid = vtiger_crmentity.crmid
INNER JOIN vtiger_crmentityrel ON vtiger_ncrraised.ncrraisedid = vtiger_crmentityrel.relcrmid
WHERE
vtiger_crmentityrel.crmid = ".$sourcerecordid." AND
vtiger_crmentity.deleted = 0
ORDER BY
vtiger_ncrraised.ncrraisedid ASC"; 
			//echo $query; exit;
        	$result = $db->pquery($query, array());
				for($i=0;$i<$db->num_rows($result);$i++){
					$ncrlist[$i]['relatedrecordid'] = $db->query_result($result,$i,'ncrraisedid'); 
					$ncrlist[$i]['person'] = $db->query_result($result,$i,'cf_6512');
					$ncrlist[$i]['department'] = $db->query_result($result,$i,'cf_6508');
					$ncrlist[$i]['location'] = $db->query_result($result,$i,'cf_6510');
					//$category[$db->query_result($result,$i,'categoryid')] = $db->query_result($result,$i,'name'); 
				}
			return $ncrlist;
	}

	// public function getHeaderScripts(Vtiger_Request $request) {
		
	// 	$moduleName = $request->getModule();
		
	// 	$jsFileNames = array(
	// 		"modules.Vtiger.resources.Detail",
	// 		"modules.Vtiger.resources.Pagination",
	// 		"modules.Vtiger.resources.List",
	// 		"modules.Vtiger.resources.Popup",
	// 		"modules.$moduleName.resources.RelatedList"
	// 	);

	// 	$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
	// 	return $jsScriptInstances;
	// }
public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.Detail',
			"modules.$moduleName.resources.Detail",
			'modules.Vtiger.resources.RelatedList',
			"modules.$moduleName.resources.RelatedList",
			"modules.Vtiger.resources.Pagination",
			"modules.Vtiger.resources.List",
			"modules.Vtiger.resources.Popup",
			'libraries.jquery.jquery_windowmsg',
			"libraries.jquery.ckeditor.ckeditor",
			"libraries.jquery.ckeditor.adapters.jquery",
			"modules.Emails.resources.MassEdit",
			"modules.Vtiger.resources.CkEditor",
			"~/libraries/jquery/twitter-text-js/twitter-text.js",
			"libraries.jquery.multiplefileupload.jquery_MultiFile",
			'~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js',
			'~/libraries/jquery.bxslider/jquery.bxslider.min.js',
			"~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.js",
			'modules.Vtiger.resources.Tag',
			'modules.Google.resources.Map'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
	
}