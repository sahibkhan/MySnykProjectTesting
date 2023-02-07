<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class FleetInquiry_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		$fieldToBeSaved = $request->get('field');
		$response = new Vtiger_Response();
		try {
			$recordId = $request->get('record');

			$old_fleet_inquiry_status = '';
			$assigned_user_id = 0;

			if(!empty($recordId))
			{
				$fleet_inquiry_info = Vtiger_Record_Model::getInstanceById($recordId, 'FleetInquiry');
	
				$old_fleet_inquiry_status = $fleet_inquiry_info->get('cf_3295');
				$assigned_user_id = $fleet_inquiry_info->get('assigned_user_id');
				
				//To Send Notification if Inquiry Status changed to Rejected
				$fleet_inquiry_status = $request->get('value');

				if($fleet_inquiry_status=='Rejected' && ($old_fleet_inquiry_status=='Pending' || $old_fleet_inquiry_status=='Accepted'))
				{
					$rejection_reason = $fleet_inquiry_info->get('cf_6114');
					if(empty($rejection_reason))
					{			
						$_SESSION['rejection_reason'] = '1';			
						//$loadUrl = "index.php?module=FleetInquiry&view=Edit&record=".$recordId."";
						//header("Location: $loadUrl");
						//exit;
					}
					else{
						$current_user = Users_Record_Model::getCurrentUserModel();
						$fleet_coordinator = $current_user->get('first_name').' '.$current_user->get('last_name');
						
						$body  = "<p>Dear Dauren,</p>";	
						$body .= "<p>Writing just to let you know that we are rejecting below fleet inquiry due to following reason.</p>";
						$body .="<p>".$rejection_reason."</p>";
						$body .="<pre>Please see details on this link:<a href='/index.php?module=FleetInquiry&view=Edit&record=".$recordId."&app=MARKETING'>Click To Follow Inquiry</a></p>";
						$body .="<p>Regards,</p>";
						$body .="<p><strong>".$fleet_coordinator."</strong>, Coordinator </p>";
						$body .="<p><strong>Globalink Logistics - </strong>52, Kabanbai Batyr Street, 050010, Almaty, Kazakhstan&nbsp;<br />";
						
						$from = $current_user->get('email1');
						$to = "d.israilov@globalinklogistics.com;k.bhat@globalinklogistics.com;a.oriyashova@globalinklogistics.com";
						$email = 's.mehtab@globalinklogistics.com';
			
						$from = "From: ".$from." <".$from.">";
						$headers = "MIME-Version: 1.0" . "\n";
						$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
						$headers .= $from . "\n";
						$headers .= 'Reply-To: '.$to.'' . "\n";
						$headers .= "CC:" . $email . "\r\n";
						
						mail($to,"Fleet Inquiry Rejection Notification",$body,$headers);	
						
					}
				}
			}

			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode',false));
			$recordModel = $this->saveRecord($request);
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);

			if(!empty($recordId)) {
				$fleet_inquiry_status = $request->get('value');
				
				$current_user = Users_Record_Model::getCurrentUserModel();
				//$fleet_coordinator_arr = array('573','64','480','386');
				$fleet_coordinator_arr = array('1312','286','64','796','386','860','30','1033','1070','465','1086','1039','816','1176','1215','795','250','1455');
				
				if($fleet_inquiry_status=='Accepted' && $old_fleet_inquiry_status=='Pending' && in_array($current_user->getId(), $fleet_coordinator_arr))
				{
					
					$db = PearDatabase::getInstance();
					$sql = "SELECT * FROM `vtiger_crmentityrel` WHERE module=? AND relcrmid=? AND relmodule=? limit 1";
					$params = array('Job', $recordId, 'FleetInquiry');
					$result = $db->pquery($sql, $params);
					$row = $db->fetch_array($result);
					$job_id = $row['crmid'];
					if(!empty($job_id))
					{
						$sql_task = "SELECT * FROM `vtiger_jobtask` WHERE job_id=? AND user_id=?";
						$params_task =  array($job_id, $current_user->getId());
						$result_task = $db->pquery($sql_task, $params_task);
						if($db->num_rows($result_task)==0)
						{					
							$new_id = $db->getUniqueId('vtiger_crmentity');
						
							$db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$assigned_user_id."' ,smownerid ='".$current_user->getId()."', setype = 'JobTask'");
							$db->pquery("INSERT INTO vtiger_jobtask SET jobtaskid = '".$new_id."', job_id = '".$job_id."', name = 'FLT', user_id ='".$current_user->getId()."', job_owner = '0'");
				
							$db->pquery("INSERT INTO vtiger_crmentityrel SET crmid = '".$job_id."', module = 'Job', relcrmid = '".$new_id."', relmodule = 'JobTask'");
						}					
					}
				}
			}	

			$fieldModelList = $recordModel->getModule()->getFields();
			$result = array();
			$picklistColorMap = array();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if($fieldModel->isViewable()){
					$recordFieldValue = $recordModel->get($fieldName);
					if(is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist') {
						foreach ($recordFieldValue as $picklistValue) {
							$picklistColorMap[$picklistValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $picklistValue);
						}
						$recordFieldValue = implode(' |##| ', $recordFieldValue);     
					}
					if($fieldModel->getFieldDataType() == 'picklist') {
						$picklistColorMap[$recordFieldValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $recordFieldValue);
					}
					$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
					if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date' && $fieldModel->getFieldDataType() !== 'double') { 
						$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId()); 
					}
					if ($fieldModel->getFieldDataType() == 'currency') {
						$displayValue = Vtiger_Currency_UIType::transformDisplayValue($fieldValue);
					}
					if(!empty($picklistColorMap)) {
						$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue, 'colormap' => $picklistColorMap);
					} else {
						$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
					}
				}
			}

			//Handling salutation type
			if ($request->get('field') === 'firstname' && in_array($request->getModule(), array('Contacts', 'Leads'))) {
				$salutationType = $recordModel->getDisplayValue('salutationtype');
				$firstNameDetails = $result['firstname'];
				$firstNameDetails['display_value'] = $salutationType. " " .$firstNameDetails['display_value'];
				if ($salutationType != '--None--') $result['firstname'] = $firstNameDetails;
			}

			// removed decode_html to eliminate XSS vulnerability
			$result['_recordLabel'] = decode_html($recordModel->getName());
			$result['_recordId'] = $recordModel->getId();
			$response->setEmitType(Vtiger_Response::$EMIT_JSON);
			$response->setResult($result);
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if($moduleName == 'Calendar') {
			$moduleName = $request->get('calendarModule');
		}
		$recordId = $request->get('record');

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			$fieldModelList = $recordModel->getModule()->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				//For not converting createdtime and modified time to user format
				$uiType = $fieldModel->get('uitype');
				if ($uiType == 70) {
					$fieldValue = $recordModel->get($fieldName);
				} else {
					$fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));
				}

				// To support Inline Edit in Vtiger7
				if($request->has($fieldName)){
					$fieldValue = $request->get($fieldName,null);
				}else if($fieldName === $request->get('field')){
					$fieldValue = $request->get('value');
				}
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
				if($fieldName === 'contact_id' && isRecordExists($fieldValue)) {
					$contactRecord = Vtiger_Record_Model::getInstanceById($fieldValue, 'Contacts');
					$recordModel->set("relatedContact",$contactRecord);
				}
			}
		} else {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');

			$fieldModelList = $moduleModel->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if ($request->has($fieldName)) {
					$fieldValue = $request->get($fieldName, null);
				} else {
					$fieldValue = $fieldModel->getDefaultFieldValue();
				}
                if($fieldValue){
                    $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue,$fieldModel);
                }
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
			} 
		}

		return $recordModel;
	}
}
