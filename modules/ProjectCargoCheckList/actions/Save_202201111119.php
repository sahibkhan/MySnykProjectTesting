<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProjectCargoCheckList_Save_Action extends Vtiger_Save_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('source_module');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'source_module';
		}
		$record = $request->get('record');
		$recordId = $request->get('id');
		if (!$record) {
			$recordParameter = '';
		}else{
			$recordParameter = 'record';
		}
		$actionName = ($record || $recordId) ? 'EditView' : 'CreateView';
        $permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'DetailView', 'record_parameter' => $recordParameter);
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => $actionName, 'record_parameter' => $recordParameter);
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}




	public function process(Vtiger_Request $request) {
			//global $adb;
			//$adb = new PearDatabase();
			//$adb->connect();
			//$adb->setDebug(true);

		 	$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
			
			$allowTypes = array(
	                        'jpg',
	                        'JPG',
	                        'jpeg',
	                        'JPEG',
	                        'png',
	                        'PNG',
	                        'gif',
	                        'GIF',
	                        'pdf',
	                        'PDF',
	                        'xlsx',
	                        'XLSX',
	                        'docx',
	                        'DOCX'
	                    );

		try {

			$record_id = $request->get('record');
			if($record_id){
				
				$fileName1 = $result['doc_liability_crg'][0]['name'];//set in clone_doc_liability_crg
				$fileName2 = $result['doc_ins_globalink_crg'][0]['name'];//set in clone_doc_insurance_globalink_crg
				$fileName3 = $result['doc_ins_customer_crg'][0]['name'];//set in clone_doc_insurance_customer_crg
				$fileName4 = $result['doc_rigging_ins_crg'][0]['name'];//set in clone_doc_rigging_insurance_crg
				$fileName5 = $result['doc_loading_plan_crg'][0]['name'];//set in clone_doc_loading_plan_crg
				$fileName6 = $result['doc_lifting_plan_crg'][0]['name'];//set in clone_doc_lifting_plan_crg
				$fileName7 = $result['doc_obstacles_crg'][0]['name'];//set in clone_doc_obstacles_crg
				$fileName8 = $result['doc_road_permit_crg'][0]['name'];//set in clone_doc_road_permit_crg
				$fileName9 = $result['doc_pilot_cars_crg'][0]['name'];//set in clone_doc_pilot_cars_crg
				$fileName10= $result['doc_civil_works_crg'][0]['name'];//set in clone_doc_civil_works_crg


                $image_name1 = explode('.', $fileName1);   
                $new_filename1 = array_shift($image_name1);
                // Remove file basename.
                $validatefile1 = array_pop($image_name1);

                $image_name2 = explode('.', $fileName2);   
                $new_filename2 = array_shift($image_name2);
                // Remove file basename.
                $validatefile2 = array_pop($image_name2);

                $image_name3 = explode('.', $fileName3);   
                $new_filename3 = array_shift($image_name3);
                // Remove file basename.
                $validatefile3 = array_pop($image_name3);

                $image_name4 = explode('.', $fileName4);   
                $new_filename4 = array_shift($image_name4);
                // Remove file basename.
                $validatefile4 = array_pop($image_name4);

                $image_name5 = explode('.', $fileName5);   
                $new_filename5 = array_shift($image_name5);
                // Remove file basename.
                $validatefile5 = array_pop($image_name5);

                $image_name6 = explode('.', $fileName6);   
                $new_filename6 = array_shift($image_name6);
                // Remove file basename.
                $validatefile6 = array_pop($image_name6);

                $image_name7 = explode('.', $fileName7);   
                $new_filename7 = array_shift($image_name7);
                // Remove file basename.
                $validatefile7 = array_pop($image_name7);

                $image_name8 = explode('.', $fileName8);   
                $new_filename8 = array_shift($image_name8);
                // Remove file basename.
                $validatefile8 = array_pop($image_name8);

                $image_name9 = explode('.', $fileName9);   
                $new_filename9 = array_shift($image_name9);
                // Remove file basename.
                $validatefile9 = array_pop($image_name9);

                $image_name10 = explode('.', $fileName10);   
                $new_filename10 = array_shift($image_name10);
                // Remove file basename.
                $validatefile10 = array_pop($image_name10);
               
                if (!empty($fileName1) && in_array($validatefile1, $allowTypes))
                {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename1);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile1;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile1;
	                    
	                    $request->set('doc_liability_crg', $newname);
	                    $request->set('clone_doc_liability_crg', $newname);
	                    $request->set('doc_liability_crg_path', $targetDir);
	                    $request->set('doc_liability_crg_name', $fileName1);
	                    if (in_array($validatefile1, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_liability_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    }
	                   	 //$recordModel = $this->saveRecord($request);
                	}else{
                		//echo "in alse file 1";
                		//die();
						 throw new FormatException('- The File Formats you are trying to Upload 
                            Not Allowed !');
				}


				if (!empty($fileName2) && in_array($validatefile2, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename2);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile2;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile2;
	                    
	                    $request->set('doc_ins_globalink_crg', $newname);
	                    $request->set('clone_doc_ins_globalink_crg', $newname);
	                    $request->set('doc_ins_globalink_crg_path', $targetDir);
	                    $request->set('doc_ins_globalink_crg_name', $fileName2);
	                    if (in_array($validatefile2, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_ins_globalink_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				if (!empty($fileName3) && in_array($validatefile3, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename3);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile3;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile3;
	                    
	                    $request->set('doc_ins_customer_crg', $newname);
	                    $request->set('clone_doc_ins_customer_crg', $newname);
	                    $request->set('doc_ins_customer_crg_path', $targetDir);
	                    $request->set('doc_ins_customer_crg_name', $fileName3);
	                    if (in_array($validatefile3, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_ins_customer_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName4) && in_array($validatefile4, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename4);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile4;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile4;
	                    
	                    $request->set('doc_rigging_ins_crg', $newname);
	                    $request->set('clone_doc_rigging_ins_crg', $newname);
	                    $request->set('doc_rigging_ins_crg_path', $targetDir);
	                    $request->set('doc_rigging_ins_crg_name', $fileName4);
	                    if (in_array($validatefile4, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_rigging_ins_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName5) && in_array($validatefile5, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename5);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile5;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile5;
	                    
	                    $request->set('doc_loading_plan_crg', $newname);
	                    $request->set('clone_doc_loading_plan_crg', $newname);
	                    $request->set('doc_loading_plan_crg_path', $targetDir);
	                    $request->set('doc_loading_plan_crg_name', $fileName5);
	                    if (in_array($validatefile5, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_loading_plan_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName6) && in_array($validatefile6, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename6);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile6;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile6;
	                    
	                    $request->set('doc_lifting_plan_crg', $newname);
	                    $request->set('clone_doc_lifting_plan_crg', $newname);
	                    $request->set('doc_lifting_plan_crg_path', $targetDir);
	                    $request->set('doc_lifting_plan_crg_name', $fileName6);
	                    if (in_array($validatefile6, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_lifting_plan_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				if (!empty($fileName7) && in_array($validatefile7, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename7);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile7;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile7;
	                    
	                    $request->set('doc_obstacles_crg', $newname);
	                    $request->set('clone_doc_obstacles_crg', $newname);
	                    $request->set('doc_obstacles_crg_path', $targetDir);
	                    $request->set('doc_obstacles_crg_name', $fileName7);
	                    if (in_array($validatefile7, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_obstacles_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				if (!empty($fileName8) && in_array($validatefile8, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename8);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile8;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile8;
	                    
	                    $request->set('doc_road_permit_crg', $newname);
	                    $request->set('clone_doc_road_permit_crg', $newname);
	                    $request->set('doc_road_permit_crg_path', $targetDir);
	                    $request->set('doc_road_permit_crg_name', $fileName8);
	                    if (in_array($validatefile8, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_road_permit_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                   // $recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName9) && in_array($validatefile9, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename9);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile9;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile9;
	                    
	                    $request->set('doc_pilot_cars_crg', $newname);
	                    $request->set('clone_doc_pilot_cars_crg', $newname);
	                    $request->set('doc_pilot_cars_crg_path', $targetDir);
	                    $request->set('doc_pilot_cars_crg_name', $fileName9);
	                    if (in_array($validatefile9, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_pilot_cars_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}



				if (!empty($fileName10) && in_array($validatefile10, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename10);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile10;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile10;
	                    
	                    $request->set('doc_civil_works_crg', $newname);
	                    $request->set('clone_doc_civil_works_crg', $newname);
	                    $request->set('doc_civil_works_crg_path', $targetDir);
	                    $request->set('doc_civil_works_crg_name', $fileName10);
	                    if (in_array($validatefile10, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_civil_works_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                   // $recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

			}else{

				$fileName1 = $result['doc_liability_crg'][0]['name'];//set in clone_doc_liability_crg
				$fileName2 = $result['doc_ins_globalink_crg'][0]['name'];//set in clone_doc_insurance_globalink_crg
				$fileName3 = $result['doc_ins_customer_crg'][0]['name'];//set in clone_doc_insurance_customer_crg
				$fileName4 = $result['doc_rigging_ins_crg'][0]['name'];//set in clone_doc_rigging_insurance_crg
				$fileName5 = $result['doc_loading_plan_crg'][0]['name'];//set in clone_doc_loading_plan_crg
				$fileName6 = $result['doc_lifting_plan_crg'][0]['name'];//set in clone_doc_lifting_plan_crg
				$fileName7 = $result['doc_obstacles_crg'][0]['name'];//set in clone_doc_obstacles_crg
				$fileName8 = $result['doc_road_permit_crg'][0]['name'];//set in clone_doc_road_permit_crg
				$fileName9 = $result['doc_pilot_cars_crg'][0]['name'];//set in clone_doc_pilot_cars_crg
				$fileName10= $result['doc_civil_works_crg'][0]['name'];//set in clone_doc_civil_works_crg


                $image_name1 = explode('.', $fileName1);   
                $new_filename1 = array_shift($image_name1);
                // Remove file basename.
                $validatefile1 = array_pop($image_name1);

                $image_name2 = explode('.', $fileName2);   
                $new_filename2 = array_shift($image_name2);
                // Remove file basename.
                $validatefile2 = array_pop($image_name2);

                $image_name3 = explode('.', $fileName3);   
                $new_filename3 = array_shift($image_name3);
                // Remove file basename.
                $validatefile3 = array_pop($image_name3);

                $image_name4 = explode('.', $fileName4);   
                $new_filename4 = array_shift($image_name4);
                // Remove file basename.
                $validatefile4 = array_pop($image_name4);

                $image_name5 = explode('.', $fileName5);   
                $new_filename5 = array_shift($image_name5);
                // Remove file basename.
                $validatefile5 = array_pop($image_name5);

                $image_name6 = explode('.', $fileName6);   
                $new_filename6 = array_shift($image_name6);
                // Remove file basename.
                $validatefile6 = array_pop($image_name6);

                $image_name7 = explode('.', $fileName7);   
                $new_filename7 = array_shift($image_name7);
                // Remove file basename.
                $validatefile7 = array_pop($image_name7);

                $image_name8 = explode('.', $fileName8);   
                $new_filename8 = array_shift($image_name8);
                // Remove file basename.
                $validatefile8 = array_pop($image_name8);

                $image_name9 = explode('.', $fileName9);   
                $new_filename9 = array_shift($image_name9);
                // Remove file basename.
                $validatefile9 = array_pop($image_name9);

                $image_name10 = explode('.', $fileName10);   
                $new_filename10 = array_shift($image_name10);
                // Remove file basename.
                $validatefile10 = array_pop($image_name10);
               
                if (!empty($fileName1) && in_array($validatefile1, $allowTypes))
                {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename1);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile1;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile1;
	                    
	                    $request->set('doc_liability_crg', $newname);
	                    $request->set('clone_doc_liability_crg', $newname);
	                    $request->set('doc_liability_crg_path', $targetDir);
	                    $request->set('doc_liability_crg_name', $fileName1);
	                    if (in_array($validatefile1, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_liability_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    }
	                   	 //$recordModel = $this->saveRecord($request);
                	}else{
                		//echo "in alse file 1";
                		//die();
						 throw new FormatException('- The File Formats you are trying to Upload 
                            Not Allowed !');
				}


				if (!empty($fileName2) && in_array($validatefile2, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename2);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile2;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile2;
	                    
	                    $request->set('doc_ins_globalink_crg', $newname);
	                    $request->set('clone_doc_ins_globalink_crg', $newname);
	                    $request->set('doc_ins_globalink_crg_path', $targetDir);
	                    $request->set('doc_ins_globalink_crg_name', $fileName2);
	                    if (in_array($validatefile2, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_ins_globalink_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				if (!empty($fileName3) && in_array($validatefile3, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename3);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile3;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile3;
	                    
	                    $request->set('doc_ins_customer_crg', $newname);
	                    $request->set('clone_doc_ins_customer_crg', $newname);
	                    $request->set('doc_ins_customer_crg_path', $targetDir);
	                    $request->set('doc_ins_customer_crg_name', $fileName3);
	                    if (in_array($validatefile3, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_ins_customer_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName4) && in_array($validatefile4, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename4);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile4;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile4;
	                    
	                    $request->set('doc_rigging_ins_crg', $newname);
	                    $request->set('clone_doc_rigging_ins_crg', $newname);
	                    $request->set('doc_rigging_ins_crg_path', $targetDir);
	                    $request->set('doc_rigging_ins_crg_name', $fileName4);
	                    if (in_array($validatefile4, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_rigging_ins_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName5) && in_array($validatefile5, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename5);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile5;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile5;
	                    
	                    $request->set('doc_loading_plan_crg', $newname);
	                    $request->set('clone_doc_loading_plan_crg', $newname);
	                    $request->set('doc_loading_plan_crg_path', $targetDir);
	                    $request->set('doc_loading_plan_crg_name', $fileName5);
	                    if (in_array($validatefile5, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_loading_plan_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName6) && in_array($validatefile6, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename6);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile6;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile6;
	                    
	                    $request->set('doc_lifting_plan_crg', $newname);
	                    $request->set('clone_doc_lifting_plan_crg', $newname);
	                    $request->set('doc_lifting_plan_crg_path', $targetDir);
	                    $request->set('doc_lifting_plan_crg_name', $fileName6);
	                    if (in_array($validatefile6, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_lifting_plan_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				if (!empty($fileName7) && in_array($validatefile7, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename7);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile7;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile7;
	                    
	                    $request->set('doc_obstacles_crg', $newname);
	                    $request->set('clone_doc_obstacles_crg', $newname);
	                    $request->set('doc_obstacles_crg_path', $targetDir);
	                    $request->set('doc_obstacles_crg_name', $fileName7);
	                    if (in_array($validatefile7, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_obstacles_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				if (!empty($fileName8) && in_array($validatefile8, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename8);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile8;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile8;
	                    
	                    $request->set('doc_road_permit_crg', $newname);
	                    $request->set('clone_doc_road_permit_crg', $newname);
	                    $request->set('doc_road_permit_crg_path', $targetDir);
	                    $request->set('doc_road_permit_crg_name', $fileName8);
	                    if (in_array($validatefile8, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_road_permit_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                   // $recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}


				if (!empty($fileName9) && in_array($validatefile9, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename9);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile9;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile9;
	                    
	                    $request->set('doc_pilot_cars_crg', $newname);
	                    $request->set('clone_doc_pilot_cars_crg', $newname);
	                    $request->set('doc_pilot_cars_crg_path', $targetDir);
	                    $request->set('doc_pilot_cars_crg_name', $fileName9);
	                    if (in_array($validatefile9, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_pilot_cars_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                    //$recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}



				if (!empty($fileName10) && in_array($validatefile10, $allowTypes))
	            {
	                	//unset($_SESSION['FILE_TYPE']);
	                    $targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
	                    $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename10);
	                    $write_file_name = $file . '_' . date("Ymdhisa");
	                    $targetFilePath = $targetDir . 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile10;
	                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                    $newname = 'ProjectCargoCheckList_' . $write_file_name . '.' . $validatefile10;
	                    
	                    $request->set('doc_civil_works_crg', $newname);
	                    $request->set('clone_doc_civil_works_crg', $newname);
	                    $request->set('doc_civil_works_crg_path', $targetDir);
	                    $request->set('doc_civil_works_crg_name', $fileName10);
	                    if (in_array($validatefile10, $allowTypes))
	                    {
	                        move_uploaded_file($result['doc_civil_works_crg'][0]['tmp_name'], $targetFilePath);
	                        chmod($targetFilePath, 0777);
	                    } 
	                   // $recordModel = $this->saveRecord($request);
                }else{
				 throw new FormatException('- The File Formats you are trying to Upload 
                        Not Allowed !');
				}

				
			}




			$recordModel = $this->saveRecord($request);

			global $adb;
			$lastid = $recordModel->getId();
			$tot_no_prod = $_REQUEST['totalProductCount'];
			$vendorCount="select count(id) as existing from vtiger_projectcargochecklist_vendorsrel 
			where vtiger_projectcargochecklist_vendorsrel.id=".$lastid;
			$vendorCount = $adb->pquery($vendorCount,array());
			if ($vendorCount->fields['existing']>0) {
				$adb->pquery("DELETE FROM vtiger_projectcargochecklist_vendorsrel WHERE id=?", Array($lastid));
				$v_agreement =  $request->get('vendorAgreement');
				$v_agreement = array_filter($v_agreement);
				$v_comment =  $request->get('vendorComment');
				$v_comment = array_filter($v_comment);
				$v_type =  $request->get('vendorType');
				$v_type = array_filter($v_type);
				for($i=1; $i<=$tot_no_prod; $i++)
				{
					$agreement = vtlib_purify($v_agreement[$i]);
					$comments = vtlib_purify($v_comment[$i]);
					$type = vtlib_purify($v_type[$i]);
					$prod_id = vtlib_purify($_REQUEST['hdnProductId'.$i]);				
					$query = 'INSERT INTO vtiger_projectcargochecklist_vendorsrel(id,vendorid,vendor_type, agreement,comments)VALUES(?,?,?,?,?)';
					$qparams = array($lastid,$prod_id,$type,$agreement,$comments);
					$adb->pquery($query,$qparams);
				}
			}else{
				$v_agreement =  $request->get('vendorAgreement');
				$v_agreement = array_filter($v_agreement);
				$v_comment =  $request->get('vendorComment');
				$v_comment = array_filter($v_comment);
				$v_type =  $request->get('vendorType');
				$v_type = array_filter($v_type);
				for($i=1; $i<=$tot_no_prod; $i++)
				{
					$agreement = vtlib_purify($v_agreement[$i]);
					$comments = vtlib_purify($v_comment[$i]);
					$type = vtlib_purify($v_type[$i]);
					$prod_id = vtlib_purify($_REQUEST['hdnProductId'.$i]);				
					$query = 'INSERT INTO vtiger_projectcargochecklist_vendorsrel(id,vendorid,vendor_type, agreement,comments)VALUES(?,?,?,?,?)';
					$qparams = array($lastid,$prod_id,$type,$agreement,$comments);
					$adb->pquery($query,$qparams);
				}
			}
			


			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (FormatException $e) {

			//session_start();
            //$_SESSION["FILE_TYPE"]='False';

            //$loadUrl = 'index.php?'.$request->getReturnURL();
            //header("Location: $loadUrl");
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}elseif($request->get('returnmodule') == 'Job' && $request->get('returnrecord')) {
			$parentModuleName = $request->get('returnmodule');
			$parentRecordId = $request->get('returnrecord');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		$this->savedRecordId = $recordModel->getId();
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
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
            $ckeditorFields = array('commentcontent', 'notecontent');
            if((in_array($fieldName, $ckeditorFields)) && $fieldValue !== null){
                $purifiedContent = vtlib_purify(decode_html($fieldValue));
                // Purify malicious html event attributes
                $fieldValue = purifyHtmlEventAttributes(decode_html($purifiedContent),true);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}

class FormatException extends Exception {};
