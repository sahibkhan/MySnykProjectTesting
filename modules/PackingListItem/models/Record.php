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
 * PackingListItem Record Model Class
 */
class PackingListItem_Record_Model extends Vtiger_Record_Model {
	 
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
						WHERE vtiger_crmentity.setype = 'PackingListItem Attachment' AND vtiger_seattachmentsrel.crmid = ?";

			 $result = $db->pquery($sql, array($recordId));
			$count = $db->num_rows($result);

			for($i=0; $i<$count; $i++) {
				$imageIdsList[] = $db->query_result($result, $i, 'attachmentsid');
				$imagePathList[] = $db->query_result($result, $i, 'path');
				$imageName = $db->query_result($result, $i, 'name');

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


	public function getPackingListImages() {
		$db = PearDatabase::getInstance();
		$imageDetails = array();
		$recordId = $this->getId();

		if ($recordId) {
			// GET Crm entity rel
				$sql = "Select vtiger_pmrinventorycf.cf_7132, vtiger_pmrinventory.name, vtiger_crmentityrel.relcrmid
		
				FROM vtiger_pmrinventorycf
		
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_pmrinventorycf.pmrinventoryid
				INNER JOIN vtiger_pmrinventory ON vtiger_pmrinventory.pmrinventoryid = vtiger_crmentity.crmid
				
				INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_pmrinventorycf.pmrinventoryid
				WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.relmodule = 'PMRInventory' 
				AND vtiger_crmentityrel.module = 'PackingListItem' AND vtiger_crmentityrel.crmid = ?";

			 $result = $db->pquery($sql, array($recordId));
			 $count = $db->num_rows($result);

			for($i=0; $i<$count; $i++) {

				$relcrmid = $db->query_result($result, $i, 'relcrmid');
				$imageName = $db->query_result($result, $i, 'name');
				$comments = $db->query_result($result, $i, 'cf_7132');
/* 
				$sqlDitem = "SELECT vtiger_pmrinventory.name, vtiger_pmrinventorycf.cf_7132
				FROM vtiger_pmrinventory 
				INNER JOIN vtiger_pmrinventorycf ON vtiger_pmrinventorycf.pmrinventoryid = vtiger_pmrinventory.pmrinventoryid
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_pmrinventory.pmrinventoryid
				WHERE vtiger_crmentity.deleted = 0 AND vtiger_pmrinventory.pmrinventoryid = ?";
			 $resDItem = $db->pquery($sqlDitem, array($relcrmid));
			 $DitemCount = $db->num_rows($resDItem); */

			//  for($j=0; $j<$DitemCount; $j++) {
				// $imageName = $db->query_result($resDItem, $j, 'name');
				// $comments = $db->query_result($resDItem, $j, 'cf_7132'); 
				$imageDetails[] = array("imageName" => $imageName, "comments" => $comments);
					// echo 'imageName = ' . $imageName.' comments = ' . $comments.'<br>';
			//  }

				
			}
		}
		return $imageDetails;
	}


/* 	public function delete() {
		global $adb;
		$record = $this->getId();
		$date = date('Y-m-d h:i:s', time());
		$sql = mysql_query("UPDATE `vtiger_surveycf` SET `cf_6402` = '$date' WHERE `surveyid` = $record");
		$this->getModule()->deleteRecord($this);
	} */

}