<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger Entity Record Model Class
 */

class ExternalTraining_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to retieve display value for a field
	 * @param <String> $fieldName - field name for which values need to get
	 * @return <String>
	 */
	public function getDisplayValue($fieldName,$recordId = false) {
		
		if(empty($recordId)) {
			$recordId = $this->getId();
		}
		$fieldModel = $this->getModule()->getField($fieldName);

		// For showing the "Date Sent" and "Time Sent" in email related list in user time zone
		if($fieldName == "time_start" && $this->getModule()->getName() == "Emails"){
			$date = new DateTime();
			$dateTime = new DateTimeField($date->format('Y-m-d').' '.$this->get($fieldName));
			$value = Vtiger_Time_UIType::getDisplayValue($dateTime->getDisplayTime());
			$this->set($fieldName, $value);
			return $value;
		}else if($fieldName == "date_start" && $this->getModule()->getName() == "Emails"){
			$dateTime = new DateTimeField($this->get($fieldName).' '.$this->get('time_start'));
			$value = $dateTime->getDisplayDate();
			$this->set($fieldName, $value);
			return $value;
		}
		// End
	
		if($fieldModel) {
			return $fieldModel->getDisplayValue($this->get($fieldName), $recordId, $this);
		}
		return false;
	}

	/**
	 * Function to save the current Record Model
	 */
	public function save() {
		$this->getModule()->saveRecord($this);
	}


		/**
		* Function to get list of invitees id's
		* @return <Array> - List of invitees id's
		*/
	  public function getInvities() {
				return array_keys($this->getInviteesDetails());
		}


     
     /**
      * Funtion to get inviteed details for the event
      * @param <Int> $userId
      * @return <Array> - list with invitees and status details
      */
			public function getInviteesDetails($userId=FALSE) {
				if(!$this->inviteesDetails || $userId) {
					 $adb = PearDatabase::getInstance();
					 $sql = "SELECT vtiger_invitees.* FROM vtiger_invitees WHERE activityid=?";
					 $sqlParams = array($this->getId());
					 if($userId !== FALSE) {
							 $sql .= " AND inviteeid = ?";
							 $sqlParams[] = $userId;
					 }
					 $result = $adb->pquery($sql,$sqlParams);
					 $inviteesDetails = array();

					 $num_rows = $adb->num_rows($result);
					//  echo 'n='.$num_rows;
					//  exit;
					 for($i=0; $i<$num_rows; $i++) {
							 $inviteesDetails[$adb->query_result($result, $i,'inviteeid')] = $adb->query_result($result, $i,'status');
					 }
					 
					 if(!$userId) {
							 $this->inviteesDetails = $inviteesDetails;
					 }
					 return $inviteesDetails;
				}
				return $this->inviteesDetails;
		}
		


		
	/**
	 * Function to get Image Details
	 * @return <array> Image Details List
	 */
	public function getImageDetails() {
        global $site_URL;
		$db = PearDatabase::getInstance();
		$imageDetails = array();
		$recordId = $this->getId();

		if ($recordId) {
			$sql = "SELECT vtiger_attachments.*, vtiger_crmentity.setype FROM vtiger_attachments
						INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
						WHERE vtiger_crmentity.setype = ? and vtiger_seattachmentsrel.crmid = ?";
			$result = $db->pquery($sql, array($this->getModuleName().' Attachment',$recordId));

			// Show all images
			while($row = $db->fetch_array($result)){

				$imageId = $row['attachmentsid'];
				$imagePath = $row['path'];
				$imageName = $row['name'];
				$storedName = $row['storedname'];
				$url = \Vtiger_Functions::getFilePublicURL($imageId, $imageName);
				//decode_html - added to handle UTF-8 characters in file names
				$imageOriginalName = urlencode(decode_html($imageName));

				if(!empty($imageName)){
					$imageDetails[] = array(
							'id' => $imageId,
							'orgname' => $imageOriginalName,
							'storedname' => $storedName,
							'path' => $imagePath.$imageId,
							'name' => $imageName,
							'url'  => $site_URL.$url
					);
				}

			}

		}
		return $imageDetails;
	}

	/**
	 * Function to delete corresponding image
	 * @param <type> $imageId
	 */
	public function deleteImage($imageId) {
		$db = PearDatabase::getInstance();

		$checkResult = $db->pquery('SELECT crmid FROM vtiger_seattachmentsrel WHERE attachmentsid = ?', array($imageId));
		$crmId = intval($db->query_result($checkResult, 0, 'crmid'));
		if (intval($this->getId()) === $crmId) {
			$db->pquery('DELETE FROM vtiger_seattachmentsrel WHERE crmid = ? AND attachmentsid = ?', array($crmId,$imageId));
			$db->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', array($imageId));
			$db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?',array($imageId));
			return true;
		}
		return false;
	}

	public function getFileDetails($attachmentId = false) {
		$db = PearDatabase::getInstance();
		$fileDetails = array();
		$query = "SELECT * FROM vtiger_attachments
				INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
				WHERE crmid = ? ";
		$params = array($this->get('id'));
		if($attachmentId) {
			$query .= 'AND vtiger_attachments.attachmentsid = ?';
			$params[] = $attachmentId;
		}
		$result = $db->pquery($query, $params);

		while($row = $db->fetch_array($result)){
			if(!empty($row)){
				$fileDetails[] = $row;
			}
		}
		return $fileDetails;
	}

	public function downloadFile($attachmentId = false) {
		$attachments = $this->getFileDetails($attachmentId);
		if(is_array($attachments[0])) {
			$fileDetails = $attachments[0];
		} else {
			$fileDetails = $attachments;
		}
		$fileContent = false;
		if (!empty ($fileDetails)) {
			$filePath = $fileDetails['path'];
			$fileName = $fileDetails['name'];
            $storedFileName = $fileDetails['storedname'];
			$fileName = html_entity_decode($fileName, ENT_QUOTES, vglobal('default_charset'));
            if (!empty($fileName)) {
                if(!empty($storedFileName)){
                    $savedFile = $fileDetails['attachmentsid']."_".$storedFileName;
                }else if(is_null($storedFileName)){
                    $savedFile = $fileDetails['attachmentsid']."_".$fileName;
                }
                $fileSize = filesize($filePath.$savedFile);
                $fileSize = $fileSize + ($fileSize % 1024);
                if (fopen($filePath.$savedFile, "r")) {
                    $fileContent = fread(fopen($filePath.$savedFile, "r"), $fileSize);
                    header("Content-type: ".$fileDetails['type']);
                    header("Pragma: public");
                    header("Cache-Control: private");
                    header("Content-Disposition: attachment; filename=\"$fileName\"");
                    header("Content-Description: PHP Generated Data");
                    header("Content-Encoding: none");
                }
            }
		}
		echo $fileContent;
	}

	public function isValidApproval() {
		global $adb;
		$isValidApproval = false;
		$recordId = $this->getId();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = $currentUser->get('email1');
		$recordExternalTraining = Vtiger_Record_Model::getInstanceById($recordId, 'ExternalTraining');


		$CEOApproval = $recordExternalTraining->get('cf_7386');
		$CEOApprovalDate = $recordExternalTraining->get('cf_7396');
		
		$CFOApproval = $recordExternalTraining->get('cf_7384');
		$CFOApprovalDate = $recordExternalTraining->get('cf_7394');

		$HRApproval = $recordExternalTraining->get('cf_7382');
		$HRApprovalDate = $recordExternalTraining->get('cf_7392');

		$lineManagerApproval = $recordExternalTraining->get('cf_7380');
		$lineManagerApprovalDate = $recordExternalTraining->get('cf_7390');
 
		// Fetch request user email		
		$queryUser = $adb->pquery("
								Select vtiger_users.email1
								FROM vtiger_crmentity
								LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
								WHERE vtiger_crmentity.crmid = ?",array($recordId));
		$recordCreatorEmail = $adb->query_result($queryUser, 0, 'email1'); 
  

		// Fetch current user's head in userlist table
		$queryHead = $adb->pquery("Select u2.cf_3355
																FROM vtiger_userlistcf as u1
																LEFT JOIN vtiger_userlistcf as u2 ON u2.userlistid = u1.cf_3385
																WHERE u1.cf_3355 = ?", array($recordCreatorEmail));
		$headEmail = $adb->query_result($queryHead, 0, 'cf_3355');

		// Fetch HR Manager
		$queryHRManager = $adb->pquery("SELECT .vtiger_userlistcf.cf_3355
															FROM `vtiger_userlistcf` 
															WHERE cf_3385 = 412373 AND cf_3421 = 85805 
															AND cf_3353 = 85757 AND cf_3349 = 414370 
															AND cf_6206 = 'Active'");
		$HRMangerEmail = $adb->query_result($queryHRManager, 0, 'cf_3355');

		if ($currentUserEmail == $headEmail && empty($headApproval) && empty($headApprovalDate)){
			$isValidApproval = true;
		} else 
		if ($currentUserEmail == $HRMangerEmail && empty($HRApproval) && empty($HRApprovalDate)){
			$isValidApproval = true;
		} else
		if ($currentUserEmail == 's.mansoor@globalinklogistics.com' && empty($CFOApproval) && empty($CFOApprovalDate)){
			$isValidApproval = true;
		} else
		if ($currentUserEmail == 's.khan@globalinklogistics.com' && empty($CEOApproval) && empty($CEOApprovalDate)){
			$isValidApproval = true;
		}

		return $isValidApproval;
	}


}
