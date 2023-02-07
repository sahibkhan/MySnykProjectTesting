<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class CreditApprovalForm_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$module_get = $_GET['module'];
		$record_get = $_GET['record'];
		$custom_permission_check = custom_access_rules($record_get,$module_get);
		//$record_owner = get_crmentity_details_own($record_get,'smcreatorid');
		//global $current_user;
		
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) && ($custom_permission_check == 'yes')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {
		$recordModel = $this->saveRecord($request);

		$userid = $request->get('assigned_user_id');
		$recordid = $recordModel->getId();

		$this->caf_message($userid,$recordid);

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
	public function caf_message($userid,$recordid) {
		$adb = PearDatabase::getInstance();
		$sql = $adb->pquery("SELECT * FROM `vtiger_creditapprovalformcf` WHERE `creditapprovalformid`='".$recordid."' LIMIT 1");
		$arr = $adb->fetch_array($sql);
		$sql = $adb->pquery("SELECT `user`.`email1`,`list`.`cf_3385` FROM `vtiger_users` AS `user` INNER JOIN `vtiger_userlistcf` AS `list` ON `list`.`cf_3355`=`user`.`email` WHERE `user`.`id`='".$userid."' LIMIT 1");
		$arr1 = $adb->fetch_array($sql);

		$email = [];//list of emails
		$email[] = $arr1['email1'];//email coordinator
		$email[] = 'a.serikbekkyzy@globalinklogistics.com';//Credit Controller

		if ($arr1['cf_3385'] != 11183) {
			$sql = $adb->pquery("SELECT `cf_3355` FROM `vtiger_userlistcf` WHERE `userlistid`='".$arr1['cf_3385']."' LIMIT 1");
			$arr2 = $adb->fetch_array($sql);
			$email[] = $arr2['cf_3355'];
		}

		$from = 'From: '.$arr1['email1'].' <'.$arr1['email1'].'>';
		$link = "https://erp.globalink.net/index.php?module=CreditApprovalForm&view=Detail&record=".$recordid;

		$body = "<html>
            <head>
              <style> 
                #calendar_notification tr td{ margin:3px;}
                .edited {font-weight: bold; color:green;}
              </style> 
            </head>
            <body>
              <table id='calendar_notification'>
                 <tr><td colspan=2>Credit Controller: ".$arr['cf_5133']."</td></tr>
                 <tr><td colspan=2>Head of Department: ".$arr['cf_5127']."</td></tr>
                 <tr><td colspan=2>FD Head: ".$arr['cf_5129']."</td></tr>
                 <tr><td colspan=2>New Credit Approval Form created ".$arr['cf_4817']."</td></tr>
                 <tr><td colspan=2> Please see details on this link: <a href='$link' target='_blank'> Credit Approval Form </a></td></tr>
              </table>
            </body>
          </html>";
          	$headers = "MIME-Version: 1.0\n";
  			$headers .= "Content-type:text/html;charset=UTF-8\n";
  			$headers .= $from . "\n";
  			$headers .= 'Reply-To: '.$arr1['email1']. "\n";
  			$subject = 'Credit Approval Form '.$arr['cf_4817'];
  			$to = implode(',',$email);
  			mail($to,$subject,$body,$headers);
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
?>