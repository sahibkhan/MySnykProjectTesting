<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class DailyTimeSheet_Save_Action extends Vtiger_Save_Action {

		
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		try {
			global $adb;
			global $current_user;

			$recordid = $request->get('record');
			//$creatorid = $request->get('assigned_user_id');
			$creatorid = $current_user->id;
			$recorddate = $request->get('cf_6892');
			$currentdate = date("d-m-Y");
			$starttime = date("Y-m-d 01:00:00");
			$endtime = date("Y-m-d 23:59:59");

			$query_1 = "SELECT * FROM `vtiger_users` 
						INNER JOIN vtiger_loginhistory ON vtiger_loginhistory.user_name = vtiger_users.user_name 
			  			WHERE vtiger_loginhistory.login_time between '$starttime' and '$endtime'  and vtiger_users.id  = '$creatorid' limit 1";

			$result_1 = $adb->pquery($query_1);
			
			$row_1 = $adb->fetch_array($result_1);
			$logintime_db = $row_1['login_time'];
			$zone = $row_1['time_zone'];
			$date = new \DateTime(date($logintime_db));
			$date->setTimezone(new \DateTimeZone($zone));
			$logintime =  $date->format('Y-m-d h:i A');
			$request->set('cf_6912',$logintime);

			if(empty($recordid)){
				$location_id = $request->get('cf_6884');
				$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
				$location = $recordLocation->get('cf_1559'); 
				$value = date('Y');

				$sql_m =  'SELECT MAX(cf_6914) as max_ordering from vtiger_dailytimesheet
					 INNER JOIN vtiger_dailytimesheetcf ON vtiger_dailytimesheetcf.dailytimesheetid = vtiger_dailytimesheet.dailytimesheetid 
					 where vtiger_dailytimesheetcf.cf_6916="'.$value.'"';
		
				$result_m = $adb->pquery($sql_m);
				$row = $adb->fetch_array($result_m);
				if($adb->num_rows($result_m)==0)
				{
					$ordering = 0;
				}
				else{
					$max_ordering = $row["max_ordering"]; 
					if (!is_numeric($max_ordering))
					{
						$ordering = 0;
					}
					else
					{
						$ordering = $max_ordering;
					}
				}
				
				$serial_number = sprintf("%02d", $ordering+1);
				
				
				$subject = strtoupper($location).'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y'); 

				$request->set('name',$subject);
			}

			$recordModel = $this->saveRecord($request);

			if(empty($recordid)){
				$sql =  "UPDATE vtiger_dailytimesheetcf SET cf_6916 = '".date('Y')."', cf_6914 = '".str_pad($serial_number, 5, "0", STR_PAD_LEFT)."' WHERE dailytimesheetid = '".$recordModel->getId()."'";
				$result = $adb->pquery($sql); 
			}

			//related list block code starts
			$relatedmodules = $request->get('relatedModuleName');
			$parentmoduleid = $recordModel->get('id');
			$parentmodulename = $request->get('module');
			foreach($relatedmodules as $relatedmodule)
			{
				$this->relatedlistblock($request,$relatedmodule,$parentmoduleid,$parentmodulename);
			}
			//related list block code ends

			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			if(empty($parentModuleName)){
				$parentModuleName = $request->get('returnmodule');
			}
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			if(empty($parentRecordId)){
				$parentRecordId = $request->get('returnrecord');				
			}
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		$this->savedRecordId = $recordModel->getId();
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}

	private function relatedlistblock(Vtiger_Request $request,$relatedmodule,$parentmoduleid,$parentmodulename)
	{
		$length = 0;
		$relatedmodulefields = $request->get($relatedmodule);
		foreach($relatedmodulefields as $relatedfield)
		{
			$length = 0;
			$relatedfieldvalues = $request->get("rlb_".$relatedfield);
			foreach($relatedfieldvalues as $relatedfieldvalue)
			{
				$length++;
				if(is_array($relatedfieldvalue))
				{
					$fieldvalue = implode(",", $relatedfieldvalue);
				}
				else
				{
					$fieldvalue = $relatedfieldvalue;
				}
				$field[$relatedfield][] = $fieldvalue;
			}
		}

	//	echo "<pre>";
	//	print_r($field);
	//	print_r($request);
	//	exit;

		//ready for entry in module
		for($i=0;$i<$length;$i++)
		{
			$test = array();
			$saverelatedrecord = new Vtiger_Save_Action();
			$related_request = new Vtiger_Request($test);
			$related_request->set("__vtrftk",$request->get("__vtrftk"));
			$related_request->set("module",$relatedmodule);
			$related_request->set("appName",$request->get("appName"));
			$related_request->set("action","Save");
			$related_request->set("record","");
			$related_request->set("name",'Manual');
			foreach($relatedmodulefields as $relatedfield)
			{
				$related_request->set($relatedfield,$field[$relatedfield][$i]);
			}
			$relatedrecordModel = $this->saveRecord($related_request);
			//relation building
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentmodulename);
			$parentRecordId = $parentmoduleid;
			$relatedModule = $relatedrecordModel->getModule();
			$relatedRecordId = $relatedrecordModel->getId();
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
	}

	
}
