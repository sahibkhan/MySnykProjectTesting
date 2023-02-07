<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ExternalTraining_Detail_View extends Vtiger_Detail_View {
	protected $record = false;

	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('');
		$this->exposeMethod('trainerApprovals');
		$this->exposeMethod('approvedByTrainer');
		$this->exposeMethod('saveTrainerComment');
	}
	
	function preProcessTplName(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		$viewer = $this->getViewer($request);
		$userRequest = new Vtiger_Request();
		$userRequest->set('user_id', $recordModel->get('assigned_user_id'));
		$userRequest->set('userModule', 'Users');
		$userRequest->set('requestMode', 'php');

		$userListFromUsers = EmployeeCV_Edit_View::getUserInfoByID($userRequest);
		if ($userListFromUsers['success'] == true)
		{
			$lineManager = $userListFromUsers['data']['generalManager'];
			// echo $lineManager;
			$viewer->assign('USER_LINE_MANAGER', $lineManager);
		}
		
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		
		return parent::preProcessTplName($request);
	}
	
	function process(Vtiger_Request $request) {
		
		$mode = $request->getMode();
		
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		
		if ($currentUserModel->get('default_record_view') === 'Summary') {
			echo $this->showModuleBasicView($request);
		} else {
				
			echo $this->showModuleDetailView($request);
		}
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		$viewer = $this->getViewer($request);
	
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		return parent::showModuleDetailView($request);
	}
	
	function isAjaxEnabled($recordModel) {
		$currentusermodel = users_record_model::getcurrentusermodel();
			if ($currentusermodel->isadminuser() == true) {
				return true;
			} else {
				return false;
			}
	}
	
	// Approvals for training request by CEO, Line Manager and HR
	public function approveExternalTraining(Vtiger_Request $request) {

	// get current user's line manager
		// $userRequest = new Vtiger_Request();
		// $userRequest->set('user_id', $request->get('assigned_user_id'));
		// $userRequest->set('userModule', 'Users');
		// $userRequest->set('requestMode', 'php');

		// $userListFromUsers = EmployeeCV_Edit_View::getUserInfoByID($userRequest);
		// $lineManager = $userListFromUsers['data']['generalManager'];

		$savetraining = new Vtiger_Save_Action();
		$request1 = new Vtiger_Request();
		$request1->set("action", "Save");
		$request1->set("module", "ExternalTraining");
		$request1->set("record", $request->get('record'));
	
	// approved by CEO
		// if($request->get('approveby') == "1196")
		if($request->get('approveby') == "60")
		{
			$request1->set("cf_6784",$request->get('approveby'));
			$request1->set("cf_6840",date("d-m-Y"));
		}
	// approved by HR
		elseif($request->get('approveby') == "1074")
		{
			$request1->set("cf_6778",$request->get('approveby'));
			$request1->set("cf_6838",date("d-m-Y"));
		}
	// approved by Line Manager
		elseif($request->get('approveby') == $lineManager)
		{
			$request1->set("cf_7040",$request->get('approveby'));
			$request1->set("cf_7042",date("d-m-Y"));
		}
	// approved by CFO
		elseif($request->get('approveby') == "277")
		{
			$request1->set("cf_7300",$request->get('approveby'));
			$request1->set("cf_7302",date("d-m-Y"));
		}
		else
			{ }

		// echo '<pre>';
		// print_r($request1);
		// echo '</pre>';
		// exit;
		$savetraining->saveRecord($request1);
		//$training_obj = new Training();	
		//$training_obj->notification_training($request->get('record'),'ExternalTraining', 'Approved');
		$loadUrl = "index.php?module=ExternalTraining&view=Detail&record=".$request->get('record');
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}

	
	public function trainerApprovals(Vtiger_Request $request) {
		// ini_set('display_errors', 1);
		// ini_set('display_startup_errors', 1);
		// error_reporting(E_ALL);

		$savetraining = new Vtiger_Save_Action();
		$trainerSaveRequest = new Vtiger_Request();
		$trainerSaveRequest->set("action","Save");
		$trainerSaveRequest->set("module","Trainer");
		$trainerSaveRequest->set("record",$request->get('relatedRecord'));
		$trainerSaveRequest->set("sendTraineeNotification",'no');
		$trainerSaveRequest->set("sendTrainerNotification",'no');

		$parentRecordId = $request->get('record');


		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, 'ExternalTraining');
		$relatedModuleName = "Trainer";
		// $recordData = $recordModel->getData();
		// $trainerListArray = explode(',', $recordData['cf_6888']);
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName);
		$relatedRecord = $relationListView->getEntries($pagingModel);
		
		// $relatedTrainerRecord = $relatedRecord[$parentRecordId];
		

		$totalCount = $relationListView->getRelatedEntriesCount();

		// $totalRecords = 0;
		foreach ($relatedRecord as $key => $trainers) {
			// $relatedTrainerId = $trainers->get('name');

			// $thisUserModel = users_record_model::getInstanceById($relatedTrainerId, "Users");
			// $trainerListArray[] = $thisUserModel->get('email1'); //get related user eMails
			$trainerListArray[] = $trainers->get('name'); //get related user eMails
		}

		// $currentusermodel = users_record_model::getcurrentusermodel();
		// $currentUseremail = $currentusermodel->get('email1');

		// approved by CEO
		// if($request->get('approveby') == "60")
		$currentTrainer = $request->get('approveby');
		
		if(in_array($currentTrainer, $trainerListArray))
		{	
			$usersCustomRequest = new Vtiger_Request();
			$usersCustomRequest->set('user_id', $request->get('approveby'));
			$usersCustomRequest->set('userModule', 'Users');
			$usersCustomRequest->set('requestMode', 'php');

			$getUserListId = EmployeeCV_Edit_View::getUserInfoByID($usersCustomRequest);
			
			$trainerSaveRequest->set("cf_7086",$getUserListId['data']['userListId']);
			$trainerSaveRequest->set("cf_7088",date("Y-m-d"));

			$trainerSaveRequest->set("cf_7068", 'Yes');
			$trainerSaveRequest->set("cf_7070",$request->get('trainerComment'));
		}
		else
		{

		}

		// echo '<pre>';
		// print_r($trainerSaveRequest);
		// echo '</pre>'; exit;
		$savetraining->saveRecord($trainerSaveRequest);
		//$training_obj = new Training();	
		//$training_obj->notification_training($request->get('record'),'ExternalTraining', 'Approved');
		$loadUrl = "index.php?module=ExternalTraining&relatedModule=Trainer&view=Detail&record=".$request->get('record')."&mode=showRelatedList&tab_label=Trainer";
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}

	public function saveTrainerComment(Vtiger_Request $request)
	{
		$saveTrainerComent = new Vtiger_Save_Action();
		$trainerComentRequest = new Vtiger_Request();
		$trainerComentRequest->set("module","Trainer");
		$trainerComentRequest->set("action","Save");
		$trainerComentRequest->set("record",$request->get('relatedRecordId'));
		// $trainerComentRequest->set("cf_7068",$request->get('trainingCompleted'));
		$trainerComentRequest->set("cf_7068", 'Yes');
		$trainerComentRequest->set("cf_7070",$request->get('comment'));
		//print_r($request); exit;
		$saveTrainerComent->saveRecord($trainerComentRequest);

		$loadUrl = "index.php?module=ExternalTraining&view=Detail&record=".$request->get('record')."&relatedModule=Trainer&mode=showRelatedList&tab_label=Trainer";
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}
}
