<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class PerformanceAppraisal_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');		
			
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) ) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {
		
		$recordModel = $this->saveRecord($request);
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		header("Location: $loadUrl");
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		$_SESSION['sendmsg_repeat'] = $request->getModule();
		$record_id = $request->get('record');
		$recordModel->save();

		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_id = $current_user->getId();
		$appraise_id = $request->get('cf_6560');
		$performanceAppraisal_id = $recordModel->getId();
		$question_ids = $request->get('question_id');
		if ( ! empty($question_ids) && empty($record_id))
		{			
			foreach ($question_ids as $question_id)
			{
				$question_answer_value = @$request->get('action_to_'.$question_id);
				$feedback_input = array('appraisal_id' => $performanceAppraisal_id, 
										'question_id' => $question_id, 
										'answer_value' => $question_answer_value, 
										//'question_category_id' => $question_category_id,
										'appraisee_id'=> $appraise_id,
										'created_by_id' => $current_user_id );
				$this->insert_question_answer($recordModel, $feedback_input);					
			}
		}

		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	public function insert_question_answer($recordModel, $feedback_input=array())
	{
		$adb = PearDatabase::getInstance();
		$question_category_id = 0;
		if(!empty($feedback_input['question_id'])){
			$question_info_detail = Vtiger_Record_Model::getInstanceById($feedback_input['question_id'], 'AppraisalQuestions');
			$question_category_id = $question_info_detail->get('cf_6530');
		}
		$adb->pquery("INSERT INTO appraisal_questions_answer(appraisal_id, question_id, answer_value, appraisee_id, created_by_id, question_category_id)
					  VALUES (?, ?, ?, ?, ?,?)",  array($feedback_input['appraisal_id'], $feedback_input['question_id'],
													   $feedback_input['answer_value'],$feedback_input['appraisee_id'],
													   $feedback_input['created_by_id'], $question_category_id
													  ));
   

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
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
