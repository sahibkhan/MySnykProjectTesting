<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class LegalCases_Save_Action extends Vtiger_Save_Action	 {

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
		
		$recordModel->save();
		
		$recordId = $request->get('record');
		if(empty($recordId)) {
			$cc = '';
			$eol = PHP_EOL;
			$current_user = Users_Record_Model::getCurrentUserModel();
			//$to = $current_user->get('email1');
			$to = 't.makarov@globalinklogistics.com;a.kabdykarim@globalinklogistics.com;s.mansoor@globalinklogistics.com';
			$cc =$current_user->get('email1');	
			
			$assigned_user_id = $recordModel->get('assigned_user_id');			
			if($current_user->getId()!=$assigned_user_id)
			{
				$legalcase_user_info = Vtiger_Record_Model::getInstanceById($assigned_user_id, 'Users');
				$assigneduser_email  = $legalcase_user_info->get('email1');
				$cc .=';'.$assigneduser_email;	
			}
			
			//except: 412373
			//For Head of Department
			$userlist_result = mysql_query("SELECT vtiger_userlistcf.*
											FROM vtiger_userlistcf 
											LEFT JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
											WHERE vtiger_userlistcf.cf_3355 = '".$current_user->get('email1')."' limit 1 ");
			$userlist_row = mysql_fetch_array($userlist_result);
			$creator_gm_id = $userlist_row['cf_3385']; //GM id from userlist
			
			// Get General manager info
			if($creator_gm_id!='412373') {
				$creator_gm_sql = mysql_query("SELECT * FROM `vtiger_userlistcf` WHERE `userlistid`='".$creator_gm_id."'  ");
				$creator_gm_query = mysql_fetch_array($creator_gm_sql);
				$creator_gm_id = $creator_gm_query['userlistid']; //gm
				$cc .= ';'.$creator_gm_query['cf_3355'];
			}			
			
			
			$from     = "From: ".$current_user->get('first_name')." ".$current_user->get('last_name')." <".$current_user->get('email1').">";
			$headers  = "MIME-Version: 1.0" . $eol;
			$headers .= "Content-type:text/html;charset=UTF-8" . $eol;
			$headers .= $from . "\n";
			$headers .= "CC:" . $cc . $eol;
			$headers .= 'Reply-To: '.$to.'' . $eol;
			
			$subject = "Legal Case Notification";
			
			$body = '';						
			$body .="<p>Dear&nbsp;Colleagues,</p>";
			//$body .="<p>Can some one help me on this issue…<br />";
			$body .="<p>".$recordModel->get('cf_6334')."</p>";
			$body .="<p>Please see details on this link: <a href='https://erp.globalink.net/index.php?module=LegalCases&view=Detail&record=".$recordModel->getId()."' target='_blank'>Click To Follow Link</a><p>";
			$body .="<p>Will be looking forward to some valuable comments on this.</p>";
			$body .="<p>Regards,</p>";
			$body .="<p><strong>".$current_user->get('first_name')." ".$current_user->get('last_name')."</strong>,</p>";
			$body .="<p><strong>Globalink Logistics - </strong>";
			mail($to,$subject,$body,$headers);
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
