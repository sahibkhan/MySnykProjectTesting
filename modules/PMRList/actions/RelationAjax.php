<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMRList_RelationAjax_Action extends Vtiger_RelationAjax_Action {
	function __construct() {
		parent::__construct();
		$this->exposeMethod('addRelation');
		$this->exposeMethod('deleteRelation');
		$this->exposeMethod('getRelatedListPageCount');
	}

	function checkPermission(Vtiger_Request $request) { }

	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}

	function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/*
	 * Function to add relation for specified source record id and related record id list
	 * @param <array> $request
	 *		keys					Content
	 *		src_module				source module name
	 *		src_record				source record id
	 *		related_module			related module name
	 *		related_record_list		json encoded of list of related record ids
	 */
	function addRelation($request) {
		$sourceModule = $request->getModule();
		$sourceRecordId = $request->get('src_record');

		$relatedModule = $request->get('related_module');
		$relatedRecordIdList = $request->get('related_record_list');

		$sourceModuleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModule);
		$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach($relatedRecordIdList as $relatedRecordId) {
			$relationModel->addRelation($sourceRecordId,$relatedRecordId);
		}
	}

	/**
	 * Function to delete the relation for specified source record id and related record id list
	 * @param <array> $request
	 *		keys					Content
	 *		src_module				source module name
	 *		src_record				source record id
	 *		related_module			related module name
	 *		related_record_list		json encoded of list of related record ids
	 */
	function deleteRelation($request) {
		$sourceModule = $request->getModule();
		
		$sourceRecordId = $request->get('src_record');
		
		$relatedModule = $request->get('related_module');
		
		$relatedRecordIdList = $request->get('related_record_list');
		
		//Setting related module as current module to delete the relation
		vglobal('currentModule', $relatedModule);

		$sourceModuleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModule);
		$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach($relatedRecordIdList as $relatedRecordId) {
			$response = $relationModel->deleteRelation($sourceRecordId,$relatedRecordId);
		}
        $parent = $sourceRecordId;
        if ($parent) {
            $sql = mysql_query("SELECT cf.cf_2373 from vtiger_pmritemcf cf
                inner join vtiger_crmentityrel r on r.crmid = ".$parent." 
                inner join vtiger_crmentity e on e.crmid = r.relcrmid and e.deleted = 0
                where cf.pmritemid = r.relcrmid limit 1");
            if (empty($sql) === false) {
                $row = mysql_fetch_assoc($sql);
                $currency = $row['cf_2373'];
                $sql = mysql_query("update vtiger_pmrlistcf set cf_2359 = ".$currency." where pmrlistid = ".$parent);
            }
            $sql = mysql_query("SELECT sum(cf.cf_2379) as prelSum, sum(cf.cf_2381) as actualSum from vtiger_pmritemcf cf
                inner join vtiger_crmentityrel r on r.crmid = ".$parent." 
                inner join vtiger_crmentity e on e.crmid = r.relcrmid and e.deleted = 0
                where cf.pmritemid = r.relcrmid");
            if (empty($sql) === false) {
                $row = mysql_fetch_assoc($sql);
                $prelSum = $row['prelSum'];
                if (is_null($prelSum)) { $prelSum = 0; }
                $actualSum = $row['actualSum'];
                if (is_null($actualSum)) { $actualSum = 0; }
                $sql = mysql_query("update vtiger_pmrlistcf set cf_2355 = ".$prelSum.", cf_2357 = ".$actualSum." where pmrlistid = ".$parent);
            } else {
                $sql = mysql_query("update vtiger_pmrlistcf set cf_2355 = 0, cf_2357 = 0 where pmrlistid = ".$parent);
            }
        }
		echo $response;
	}
	
	/**
	 * Function to get the page count for reltedlist
	 * @return total number of pages
	 */
	function getRelatedListPageCount(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');
		$label = $request->get('tab_label');
		$pagingModel = new Vtiger_Paging_Model();
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$totalCount = $relationListView->getRelatedEntriesCount();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $totalCount / (int) $pageLimit);

		if($pageCount == 0){
			$pageCount = 1;
		}
		$result = array();
		$result['numberOfRecords'] = $totalCount;
		$result['page'] = $pageCount;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
