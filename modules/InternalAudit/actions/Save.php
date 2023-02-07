<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class InternalAudit_Save_Action extends Vtiger_Save_Action
{

	public function checkPermission(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$actionName = ($record) ? 'EditView' : 'CreateView';
		if (!Users_Privileges_Model::isPermitted($moduleName, $actionName, $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		if (!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		if ($record) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
	}

	public function validateRequest(Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request)
	{
		try {

			$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
			$_FILES = $result['cf_8552'];


			$recordModel = $this->saveRecord($request);
			if ($request->get('returntab_label')) {
				$loadUrl = 'index.php?' . $request->getReturnURL();
			} else if ($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?' . $request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if (strlen($appName) > 0) {
				$loadUrl = $loadUrl . $appName;
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
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl() . '&record=' . $request->get('record'));
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
	public function saveRecord($request)
	{
		$recordModel = $this->getRecordModelFromRequest($request);


		if ($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach ($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
		$parentRecordId1 = $recordModel->getId();
		// code here

		$projectName = $recordModel->get('name');

		$startdate = $recordModel->get('cf_8628');
		$deadline = $recordModel->get('cf_8630');
		$actualenddate = $recordModel->get('cf_8550');
		$revisedDeadlineReasion = $recordModel->get('cf_8548');

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$fromEmail = $currentUserModel->get('email1');
		$fromName = $currentUserModel->get('first_name') . ' ' . $currentUserModel->get('last_name');

		$creatorUserModel = Vtiger_Record_Model::getInstanceById($recordModel->get('assigned_user_id'), 'Users');
		$projectCreator = $creatorUserModel->get('first_name') . ' ' . $creatorUserModel->get('last_name');
		$toEmail = strtolower(trim($creatorUserModel->get('email1')));


		$recordIds = $request->get('recordIds');
		$taskUsers = $request->get('users');
		$taskNames = $request->get('taskName');
		$taskDates = $request->get('taskDate');
		$taskStatuses = $request->get('taskStatus');
		$taskComments = $request->get('taskComment');
		$taskUpdates = $request->get('updateStatus');
		$deleteStatus = $request->get('deleteStatus');
		$locations = $request->get('locations');
		$departments = $request->get('departments');

		$users = $request->get('users');
		$nOfTasks = count($taskNames);
		$isRecordNew = false;

		for ($i = 1; $i <= $nOfTasks; $i++) {
			$recordId = $recordIds[$i];

			$taskName = trim($taskNames[$i]);
			$taskUser = $taskUsers[$i];
			$taskDate = $taskDates[$i];
			$taskStatus = $taskStatuses[$i];
			$taskComment = trim($taskComments[$i]);
			$isTaskUpdated = $taskUpdates[$i];
			$isTaskDeleted = $deleteStatus[$i];
			$location = $locations[$i];
			$department = $departments[$i];
			$involvedUsers[] = $taskUser;

			$taskUserModel = Vtiger_Record_Model::getInstanceById($taskUser, 'Users');
			$taskAssignedTo = $taskUserModel->get('first_name') . ' ' . $taskUserModel->get('last_name');
			$taskAssignedEmail = trim($taskUserModel->get('email1'));

			$user = $users[$i];
			if (!empty($taskName) && (int)$recordId == 0) {

				$recordModel2 = Vtiger_Record_Model::getCleanInstance('ProjectMilestone');
				$recordModel2->set('assigned_user_id', $taskUser);
				$recordModel2->set('mode', 'create');
				$recordModel2->set("projectmilestonename", $taskName);
				$recordModel2->set("cf_7838", $taskStatus);
				$recordModel2->set("projectmilestonedate", $taskDate);
				$recordModel2->set("description", $taskComment);
				$recordModel2->set("projectid", $parentRecordId1);

				$recordModel2->set("cf_7844", $location);
				$recordModel2->set("cf_7846", $department);
				$recordModel2->save();

				$parentModuleModel1 = Vtiger_Module_Model::getInstance("Project");
				$relatedModule1 = $recordModel2->getModule();
				$relatedRecordId1 = $recordModel2->get('id');
				$relationModel1 = Vtiger_Relation_Model::getInstance($parentModuleModel1, $relatedModule1);
				$relationModel1->addRelation($parentRecordId1, $relatedRecordId1);

				$projectTasksArr[] = array(
					"taskId" => $relatedRecordId1, "taskName" => $taskName,
					"taskAssignedTo" => $taskAssignedTo,
					"taskAssignedEmail" => $taskAssignedEmail,
					"taskStatus" => $taskStatus,
					"taskComment" => $taskComment, "taskDate" => $taskDate
				);
				$isRecordNew = true;
			} else if ((int)$recordId > 0 && $isTaskDeleted == 1) {

				$taskModel = Vtiger_Record_model::getInstanceById($recordId, 'ProjectMilestone');
				$taskModel->delete();
			} else if ((int)$recordId > 0 && $isTaskUpdated == 1) {


				$taskModel = Vtiger_Record_model::getInstanceById($recordId, 'ProjectMilestone');
				$taskModel->set('mode', 'edit');
				$taskModel->set('assigned_user_id', $taskUser);
				$taskModel->set("projectmilestonename", $taskName);
				$taskModel->set("cf_7838", $taskStatus);
				$taskModel->set("projectmilestonedate", $taskDate);
				$taskModel->set("description", $taskComment);
				$taskModel->set("cf_7844", $location);
				$taskModel->set("cf_7846", $department);
				$taskModel->save();

				$isRecordNew = false;

				$projectTasksArr[] = array(
					"taskId" => $recordId, "taskName" => $taskName,
					"taskAssignedTo" => $taskAssignedTo,
					"taskAssignedEmail" => $taskAssignedEmail,
					"taskStatus" => $taskStatus,
					"taskComment" => $taskComment, "taskDate" => $taskDate
				);
			}
		}

		$instanceProject = new Project();
		$details = array();
		$details['toEmail'] = $toEmail;

		$details['fromEmail'] = $fromEmail;
		$details['fromName'] = $fromName;

		$details['projectCreator'] = $projectCreator;
		$details['projectName'] = $projectName;

		$details['startdate'] = $startdate;
		$details['deadline'] = $deadline;
		$details['revisedDeadline'] = $actualenddate;
		$details['revisedDeadlineReasion'] = $revisedDeadlineReasion;

		$details['projectTasks'] = $projectTasksArr;
		$details['involvedUsers'] = $involvedUsers;
		$details['recordId'] = $parentRecordId1;
		$details['isRecordNew'] = $isRecordNew;
		// $instanceProject->sendEmailNotification($details);


		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if ($relatedModule->getName() == 'Events') {
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
	protected function getRecordModelFromRequest(Vtiger_Request $request)
	{

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if (!empty($recordId)) {
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
			if ($fieldDataType == 'time') {
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if ($fieldValue !== null) {
				if (!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
