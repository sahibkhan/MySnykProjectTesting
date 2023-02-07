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
				//doc_rigging_ins_crg


				if (!empty($fileName1)) {
					//$targetDir = 'cache/projectcargochecklist/';
					$targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
					$image_name = explode('.',$fileName1);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_liability_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_liability_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName2)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName2);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_ins_globalink_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_ins_globalink_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName3)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName3);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_ins_customer_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_ins_customer_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName4)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName4);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_rigging_ins_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_rigging_ins_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName5)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName5);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_loading_plan_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_loading_plan_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}

						

				}

				if (!empty($fileName6)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName6);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_lifting_plan_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_lifting_plan_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
						

				}

				if (!empty($fileName7)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName7);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_obstacles_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_obstacles_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName8)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName8);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_road_permit_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_road_permit_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName9)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName9);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_pilot_cars_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_pilot_cars_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName10)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName10);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_civil_works_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_civil_works_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
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

				if (!empty($fileName1)) {
					//$targetDir = 'cache/projectcargochecklist/';
					$targetDir = Vtiger_Functions::initProjectCargoCheckListStorageFileDirectory();
					$image_name = explode('.',$fileName1);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_liability_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_liability_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName2)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName2);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_ins_globalink_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_ins_globalink_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName3)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName3);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_ins_customer_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_ins_customer_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName4)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName4);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_rigging_ins_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_rigging_ins_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName5)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName5);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_loading_plan_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_loading_plan_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName6)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName6);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_lifting_plan_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_lifting_plan_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName7)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName7);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_obstacles_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_obstacles_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName8)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName8);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_road_permit_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_road_permit_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName9)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName9);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_pilot_cars_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_pilot_cars_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}

				if (!empty($fileName10)) {
					$targetDir = 'cache/projectcargochecklist/';
					$image_name = explode('.',$fileName10);
					$write_file_name =$image_name['0'].'_'.date("Ymdhisa");
					$targetFilePath = $targetDir . 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name['1'];
					$fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
					$allowTypes = array('jpg','png','jpeg','gif','pdf','xlsx');	
					$newname = 'ProjectCargoCheckList_'.$write_file_name.'.'.$image_name[1];
					$request->set('clone_doc_civil_works_crg',$newname);
					if(in_array($fileType, $allowTypes)){
						move_uploaded_file($result['doc_civil_works_crg'][0]['tmp_name'], $targetFilePath);
						chmod($targetFilePath,0777);
						}
				}


		}
			

			$recordModel = $this->saveRecord($request);
			//echo "here i am ";
			//die();
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
		} catch (Exception $e) {
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
