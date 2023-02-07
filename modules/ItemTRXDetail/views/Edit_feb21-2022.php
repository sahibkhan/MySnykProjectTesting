<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class ItemTRXDetail_Edit_View extends Vtiger_Edit_View {
    protected $record = false;
	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('autocomplete');
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
		
		$mode = $request->get('mode');
        if(!empty($mode)) {
            $this->invokeExposedMethod($mode,$request);
			exit;
		}
		
		$moduleName = $request->getModule();
		$record = $request->get('record');
        if(!empty($record) && $request->get('isDuplicate') == true) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $viewer->assign('MODE', '');
        }else if(!empty($record)) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('RECORD_ID', $record);
            $viewer->assign('MODE', 'edit');
			
			$viewer->assign('WHID', $recordModel->get('cf_5615')); //FROM WAREHOUSE
			
			$viewer->assign('CUSTOMER_ID', '');
			$viewer->assign('IN_HOUSE', '');
			$viewer->assign('GLK_COMPANY_ID', '');
			
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $viewer->assign('MODE', '');
			
			$isRelationOperation = $request->get('relationOperation');
			//if it is relation edit
			$ItemTRXMaster_id = $request->get('sourceRecord');	 
			$sourceModule = 'ItemTRXMaster';	
			$item_trx_info = Vtiger_Record_Model::getInstanceById($ItemTRXMaster_id, $sourceModule);	 
			$item_trx_wh_id = $item_trx_info->get('cf_5591');
			$item_trx_doc_type = $item_trx_info->getDisplayValue('cf_5583');
			$in_house = $item_trx_info->get('cf_5593');
			$glk_company_id = $item_trx_info->get('cf_5595');
			$item_trx_customer_id = $item_trx_info->get('cf_5597');
			
			$recordModel->set('cf_5615', $item_trx_wh_id); // Warehouse ID
			$recordModel->set('cf_5609', $item_trx_info->getDisplayValue('cf_5583'));// Document Type
			$recordModel->set('cf_5611', $item_trx_info->getDisplayValue('cf_5585'));// Document Number
			$recordModel->set('cf_5710','-'); //Batch ID
        }
		
        if(!$this->record){
            $this->record = $recordModel;
        }
        
		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

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
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
			
			$viewer->assign('WHID', $item_trx_wh_id); //FROM WAREHOUSE
			$viewer->assign('DOC_TYPE', $item_trx_doc_type); //Document Type		
			$viewer->assign('TO_WHID', 0); //FROM WAREHOUSE
			$viewer->assign('CUSTOMER_ID', $item_trx_customer_id);
			$viewer->assign('IN_HOUSE', $in_house);
			$viewer->assign('GLK_COMPANY_ID', $glk_company_id);
			
			$whlocationlist = Vtiger_Field_Model::getWHLocationList($item_trx_wh_id, $parent_id=0, $item_trx_doc_type, $item_trx_customer_id, $in_house, $glk_company_id);
			$viewer->assign('WH_LOCATION_LIST', $whlocationlist);
		}
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->view('EditView.tpl', $moduleName);
	}
	
	public function autocomplete(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$batch_arr = array();
		$param = $request->get('param');
		$whid = $request->get('whid');
		$sourceRecord = $request->get('sourceRecord');
		
		$ItemTRXMaster_id = $sourceRecord;
	    if(empty($ItemTRXMaster_id))
	    {
		  $record_id = $request->get('record_id');
		  //to add extra code to get parent record inforamtion in case of edit
		  //extra code remaining for parent record id
	  	}
		$sourceModule = 'ItemTRXMaster';	
	    $item_trx_info = Vtiger_Record_Model::getInstanceById($ItemTRXMaster_id, $sourceModule);	
	    $inhouse = $item_trx_info->get('cf_5593');
	    $glk_company_id = $item_trx_info->get('cf_5595');
	    $customer_id = $item_trx_info->get('cf_5597');
		
		if($inhouse=='Yes')
  		{			
		$query = "SELECT DISTINCT(vtiger_whitemqtymastercf.cf_5732) FROM vtiger_whitemqtymastercf
				  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemqtymastercf.whitemqtymasterid 
				  WHERE vtiger_crmentity.deleted = 0 
				  AND vtiger_whitemqtymastercf.cf_5638='".$whid."' 
				  AND vtiger_whitemqtymastercf.cf_5632 = '{$inhouse}'
				  AND vtiger_whitemqtymastercf.cf_5634 = '{$glk_company_id}'
				  AND vtiger_whitemqtymastercf.cf_5732 like '".$param."%'";
					
			
		}else{
		$query = "SELECT DISTINCT(vtiger_whitemqtymastercf.cf_5732) FROM vtiger_whitemqtymastercf
				  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_whitemqtymastercf.whitemqtymasterid 
				  WHERE vtiger_crmentity.deleted = 0 
				  AND vtiger_whitemqtymastercf.cf_5638='".$whid."' 
				  AND vtiger_whitemqtymastercf.cf_5632 = '{$inhouse}'
				  AND vtiger_whitemqtymastercf.cf_5636 = '{$customer_id}'
				  AND vtiger_whitemqtymastercf.cf_5732 like '".$param."%'";
		}
		$result = $db->pquery($query, array());
		for($i=0;$i<$db->num_rows($result);$i++){
			$batch_arr[] = $db->query_result($result,$i,'cf_5732'); 					
		}
		echo json_encode($batch_arr);
	}
}