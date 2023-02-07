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
		// global $adb;
		//Restrict to store indirect relationship from Potentials to Contacts
		$sourceModule = $request->get('sourceModule');
		$relationOperation = $request->get('relationOperation');
		$skip = true;

		if ($relationOperation && $sourceModule === 'Contacts') {
			$request->set('relationOperation', false);
			$skip = false;
		}
         if($request->get('potentials_origin_state')=='')
		 {
		 	$request->set('potentials_origin_state','');
		 }
		 if($request->get('potentials_destination_state')=='')
		 {  
		      $request->set('potentials_destination_state','');
		 }
		$this->setRelatedQuoteStatus($request);		
		parent::process($request);

/* 
 		if ($request->get('record')) $record = $request->get('record');
		$adb->pquery("UPDATE `a_test_contact` SET `first_name_erp` = '$record' WHERE `cont_id` = 0"); */

		// to link the relation in updates
		if (!$skip) {
			$sourceRecordId = $request->get('sourceRecord');
			$focus = CRMEntity::getInstance($sourceModule);
			
			$destinationModule = $request->get('module');
			$destinationRecordId = $this->savedRecordId;
			$focus->trackLinkedInfo($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId);
		}
	}

	public function setRelatedQuoteStatus($request){
		global $adb;
		$recordId = $request->get('record');
		$RFQStatus = $request->get('sales_stage');

		$queryRelation = $adb->pquery("SELECT vtiger_quotes.quoteid, vtiger_quotes.quotestage
																	 FROM vtiger_quotes
																	 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_quotes.quoteid
																	 WHERE vtiger_crmentity.deleted = 0 AND vtiger_quotes.potentialid = ? ", array($recordId));
		$noOfRows = $adb->num_rows($queryRelation);
		if ($RFQStatus == 'Lost' && $noOfRows > 0){
			$relatedQuoteId = $adb->query_result($queryRelation, 0, 'quoteid');
			$relatedQuoteStatus = $adb->query_result($queryRelation, 0, 'quotestage');
			if ($relatedQuoteStatus != 'Secured'){
				$adb->pquery("UPDATE vtiger_quotes SET quotestage = ? WHERE quoteid = ? LIMIT 1", array($RFQStatus, $relatedQuoteId));
			}
		}
	}
}
