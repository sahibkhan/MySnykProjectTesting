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
 * PackingList Record Model Class
 */
class PackingList_Record_Model extends Vtiger_Record_Model {
	 
	/**
	 * Function to get Image Details
	 * @return <array> Image Details List
	 */
	public function getImageDetails() {
		$db = PearDatabase::getInstance();
		$imageDetails = array();
		$recordId = $this->getId();

		if ($recordId) {
				$sql = "SELECT vtiger_attachments.*, vtiger_crmentity.setype FROM vtiger_attachments
						INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
						WHERE vtiger_seattachmentsrel.crmid = ?";

			 $result = $db->pquery($sql, array($recordId));
			$count = $db->num_rows($result);

			for($i=0; $i<$count; $i++) {
				$imageIdsList[] = $db->query_result($result, $i, 'attachmentsid');
				$imagePathList[] = $db->query_result($result, $i, 'path');
				$imageName = $db->query_result($result, $i, 'storedname');

				//decode_html - added to handle UTF-8 characters in file names
				$imageOriginalNamesList[] = decode_html($imageName);

				//urlencode - added to handle special characters like #, %, etc.,
				$imageNamesList[] = $imageName;
			}

			if(is_array($imageOriginalNamesList)) {
				$countOfImages = count($imageOriginalNamesList);
				for($j=0; $j<$countOfImages; $j++) {
					$imageDetails[] = array(
							'id' => $imageIdsList[$j],
							'orgname' => $imageOriginalNamesList[$j],
							'path' => $imagePathList[$j].$imageIdsList[$j],
							'name' => $imageNamesList[$j]
					);
				}
			}
		}
		return $imageDetails;
	}

	public function getPackingListSugnatures() {
		global $adb;
		$imageDetails = array();
		$recordId = $this->getId();

		if ($recordId) {

				$sql_images = $adb->pquery("Select cf_7112,cf_7110
																						FROM vtiger_packinglistcf
																						WHERE packinglistid = ?", array($recordId));
				$r_image = $adb->fetch_array($sql_images);
				$supervisorImage = $r_image['cf_7112'];
				$clientImage = $r_image['cf_7110'];
				
				$imageDetails[] = array("imageName" => $supervisorImage, "type" => 'Supervisor');
				$imageDetails[] = array("imageName" => $clientImage, "type" => 'Client');
						
		}
		// print_r($imageDetails);
		return $imageDetails;
	}

/* 
	public function delete() {
		global $adb;
		$record = $this->getId();
		$date = date('Y-m-d h:i:s', time());
		$sql = mysql_query("UPDATE `vtiger_surveycf` SET `cf_6402` = '$date' WHERE `surveyid` = $record");
		$this->getModule()->deleteRecord($this);
	} */

}