<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

 Class Project_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get the summary information for module
	 * @return <array> - values which need to be shown as summary
	 */
	public function getSummaryInfo() {
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$projectTaskInstance = Vtiger_Module_Model::getInstance('ProjectTask');
		if($userPrivilegesModel->hasModulePermission($projectTaskInstance->getId())) {
			$adb = PearDatabase::getInstance();

			$query ='SELECT smownerid,enddate,projecttaskstatus,projecttaskpriority
					FROM vtiger_projecttask
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_projecttask.projecttaskid
								AND vtiger_crmentity.deleted=0
							WHERE vtiger_projecttask.projectid = ? ';

			$result = $adb->pquery($query, array($this->getId()));

			$tasksOpen = $taskCompleted = $taskDue = $taskDeferred = $numOfPeople = 0;
			$highTasks = $lowTasks = $normalTasks = $otherTasks = 0;
			$currentDate = date('Y-m-d');
			$inProgressStatus = array('Open', 'In Progress');
			$usersList = array();

			while($row = $adb->fetchByAssoc($result)) {
				$projectTaskStatus = $row['projecttaskstatus'];
				switch($projectTaskStatus){
					case 'Open'		: $tasksOpen++;		break;
					case 'Deferred'	: $taskDeferred++;	break;
					case 'Completed': $taskCompleted++;	break;
				}
				$projectTaskPriority = $row['projecttaskpriority'];
				switch($projectTaskPriority){
					case 'high' : $highTasks++;break;
					case 'low' : $lowTasks++;break;
					case 'normal' : $normalTasks++;break;
					default : $otherTasks++;break;
				}

				if(!empty($row['enddate']) && (strtotime($row['enddate']) < strtotime($currentDate)) &&
						(in_array($row['projecttaskstatus'], $inProgressStatus))) {
					$taskDue++;
				}
				$usersList[] = $row['smownerid'];
			}

			$usersList = array_unique($usersList);
			$numOfPeople = count($usersList);

			$summaryInfo['projecttaskstatus'] =  array(
													'LBL_TASKS_OPEN'	=> $tasksOpen,
													'Progress'			=> $this->get('progress'),
													'LBL_TASKS_DUE'		=> $taskDue,
													'LBL_TASKS_COMPLETED'=> $taskCompleted,
			);

			$summaryInfo['projecttaskpriority'] =  array(
													'LBL_TASKS_HIGH'	=> $highTasks,
													'LBL_TASKS_NORMAL'	=> $normalTasks,
													'LBL_TASKS_LOW'		=> $lowTasks,
													'LBL_TASKS_OTHER'	=> $otherTasks,
			);
		}

		return $summaryInfo;
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
		 * Function to get list of invitees id's
		 * @return <Array> - List of invitees id's
		 */
		public function getInvities() {
				return array_keys($this->getInviteesDetails());
		}


/*
			Get Invitee list
	*/
	public function getProjectInviteeUsers() {
		global $adb;
		/*
			 Fetch user invitees
		*/
			$inviteUsers = '';
			$q_invitees = $adb->pquery("SELECT vtiger_users.first_name, vtiger_users.last_name
																	FROM vtiger_users
																	LEFT JOIN vtiger_invitees ON  vtiger_invitees.inviteeid = vtiger_users.id
																	WHERE vtiger_invitees.activityid = ?", array($this->getId())
			);
			$num_rows = $adb->num_rows($q_invitees);

			for($i=0; $i<$num_rows; $i++) {
				$inviteUsers .= "<tr><td>".$adb->query_result($q_invitees, $i,'first_name').' '. $adb->query_result($q_invitees, $i,'last_name').'</td></tr>';
			}

			return $inviteUsers;
		}     



	/** 
	 * Function to get the project task for a project
	 * @return <Array> - $projectTasks
	 */
	public function getProjectTasks() {
		$recordId  = $this->getId();
		$db = PearDatabase::getInstance();

		$sql = "SELECT projecttaskid as recordid,projecttaskname as name,startdate,enddate,projecttaskstatus FROM vtiger_projecttask 
				INNER JOIN vtiger_crmentity  ON vtiger_projecttask.projecttaskid = vtiger_crmentity.crmid
				WHERE projectid=? AND vtiger_crmentity.deleted=0 AND vtiger_projecttask.startdate IS NOT NULL AND vtiger_projecttask.enddate IS NOT NULL";

		$result = $db->pquery($sql, array($recordId));
		$i = -1;
		while($record = $db->fetchByAssoc($result)){
			$record['id'] = $i;
			$record['name'] = decode_html(textlength_check($record['name']));
			$record['status'] = self::getGanttStatus($record['projecttaskstatus']);
			$record['start'] = strtotime($record['startdate']) * 1000;
			$record['duration'] = $this->getDuration($record['startdate'], $record['enddate']);
			$record['end'] = strtotime($record['enddate']) * 1000;
			$projectTasks[] = $record;
			$i--;
		}

		return $projectTasks;
	}

	/**
	 * Function to get the duration
	 * @param <string> $startDate,$endDate
	 * @return $duration
	 */
	public function getDuration($startDate,$endDate) {
		$difference = strtotime($endDate) - strtotime($startDate);
		$duration = floor($difference/(3600*24)+1);

		// if the start date and end date are same
		if($duration == 0) {
			return $duration+0.1;
		} else if($duration < 0) { // if end date is null or less than start date
			return 0; 
		}

		return $duration;
	}

	static public function getGanttStatus($status) {
		switch($status) {
			case 'Open'			: return 'STATUS_UNDEFINED';
			case 'In Progress'  : return 'STATUS_ACTIVE';
			case 'Completed'	: return 'STATUS_DONE';
			case 'Deferred'		: return 'STATUS_SUSPENDED';
			case 'Canceled'		: return 'STATUS_FAILED';
			default				: return $status;
		}
	}

 function getStatusColors() {
		$statusColorMap = array();
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT *FROM vtiger_projecttask_status_color');
		if ($db->num_rows($result) > 0) {
			for ($i = 0; $i < $db->num_rows($result); $i++) {
				$status = decode_html($db->query_result($result, $i, 'status'));
				$color = $db->query_result($result, $i, 'color');
				if (empty($color)) {
					$color = $db->query_result($result, $i, 'defaultcolor');
				}
				$statusColorMap[$status] = $color;
			}
		}

		return $statusColorMap;
	}

	static function getGanttStatusCss($status, $color) {
		return '.taskStatus[status="'.self::getGanttStatus($status).'"]{
					background-color: '.$color.';
				}';
	}

	static function getGanttSvgStatusCss($status, $color) {
		return '.taskStatusSVG[status="'.self::getGanttStatus($status).'"]{
					fill: '.$color.';
				}';
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
							WHERE vtiger_crmentity.setype = ? AND vtiger_crmentity.deleted = 0 and vtiger_seattachmentsrel.crmid = ?";
				// $result = $db->pquery($sql, array($this->getModuleName().' Image',$recordId));
				$result = $db->pquery($sql, array($this->getModuleName().' Attachment',$recordId));

				// Show all images
				while($row = $db->fetch_array($result)){

					$imageId = $row['attachmentsid'];
					$imagePath = $row['path'];
					$imageName = $row['name'];
					$storedName = $row['storedname'];
					$type = $row['type'];
					$url = \Vtiger_Functions::getFilePublicURL($imageId, $imageName);
					//decode_html - added to handle UTF-8 characters in file names
					$imageOriginalName = urlencode(decode_html($imageName));

					if(!empty($imageName)){
						$imageDetails[] = array(
								'id' => $imageId,
								'recordId' => $recordId,
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



	
}

?>
