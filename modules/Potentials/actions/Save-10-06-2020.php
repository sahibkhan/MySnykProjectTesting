<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Potentials_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		global $adb;
		//Restrict to store indirect relationship from Potentials to Contacts
		$sourceModule = $request->get('sourceModule');
		$relationOperation = $request->get('relationOperation');
		$skip = true;

		if ($relationOperation && $sourceModule === 'Contacts') {
			$request->set('relationOperation', false);
			$skip = false;
		}
		
		//$recordModel = $this->saveRecord($request);
		// echo "<pre>"; print_r($_REQUEST); exit;
		// $recordId  = $recordModel->get('id');
	  // $adb->pquery("UPDATE `a_test` SET `user_name` = '$recordId' WHERE `a_test`.`record_id` = 990679");


		parent::process($request);

		
	//	$RFQ = new Potentials();
	//	if ($request->get('record')) $record = $request->get('record');
		//$RFQ->handleEmailNotification($record);




		// to link the relation in updates
		if (!$skip) {
			$sourceRecordId = $request->get('sourceRecord');
			$focus = CRMEntity::getInstance($sourceModule);
			
			$destinationModule = $request->get('module');
			$destinationRecordId = $this->savedRecordId;
			$focus->trackLinkedInfo($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId);
		}
	}
}
