<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

Class PackingList_Edit_View extends Vtiger_Edit_View {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
        $viewer = $this->getViewer ($request);
        $recordModel = $this->record;
        if(!$recordModel){
            if (!empty($recordId)) {
                $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            } else {
                $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
                $viewer->assign('MODE', '');

                // Implementing data flow
                if(empty($record)){
                  if($request->get('returnmodule') == 'Job' && $request->get('returnrecord')){
                    $sourceModule = $request->get('returnmodule');
                    $sourceRecord = $request->get('returnrecord');
                    $currentUserId = $request->get('assigned_user_id');
            
                    $jobFileModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
                    // Getting values from Job file
                    $jobFileRef = $jobFileModel->get('name');
                    $jobFileCustomer = strip_tags($jobFileModel->getDisplayValue('cf_1441'));
                    // $request->set('assigned_user_id', $jobFileModel->get('assigned_user_id')); // Assiged to
                    $request->set('cf_7008', $jobFileModel->get('assigned_user_id')); // Coordinator

                    
                    $jobFileShipper = $jobFileModel->get('cf_1072');
                    $jobFileConsignee = $jobFileModel->get('cf_1074');
                    // $jobFileMode = explode('#', $jobFileModel->get('cf_1711'));    
                    $jobFileMode = $jobFileModel->get('cf_1711');    
                    $jobFileFromAddress = $jobFileModel->get('cf_1512');
                    $request->set('name', $jobFileRef); // Subject
                    $request->set('cf_6992', $jobFileCustomer); // Account

                    $request->set("cf_7000", $jobFileMode);
        
                    $request->set("cf_6994", $request->get('cf_7904')); // Contact Phone

                    if ($jobFileModel->get('cf_1504')){
                      $originAddress = $jobFileModel->get('cf_1504').', '. $jobFileModel->get('cf_1508');
                    } else {
                      $originAddress = $jobFileModel->get('cf_1508');
                    }

                    if ($jobFileModel->get('cf_1506')){
                      $destinationAddress = $jobFileModel->get('cf_1506').', '.$jobFileModel->get('cf_1510');
                    } else {
                        $destinationAddress = $jobFileModel->get('cf_1510');
                    }

                    $request->set("cf_7062", $originAddress); // Origin
                    $request->set("cf_6998", $destinationAddress); // Destination

    
                  }
                  
                }


            }
        }
		
		$viewer = $this->getViewer($request);

		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		parent::process($request);
	}
	

}