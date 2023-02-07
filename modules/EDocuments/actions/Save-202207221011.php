<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class EDocuments_Save_Action extends Vtiger_Save_Action {

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
	                        'DOCX',
	                        'ZIP',
	                        'zip',
	                        '7Z',
	                        '7z',
                        	'JFIF',
                        	'jfif',
                        	'msg',
                        	'MSG'
	                    );
		try {

			$record_id = $request->get('record');
			
			if($record_id){

           		$job_id = $request->get('returnrecord');
	            session_start();
	            $_SESSION["jobid"]=$job_id;
	            $jobid = $_SESSION["jobid"];
	            
	            $edoc_name = $request->get('document_name');
	            $edoc_name = array_unique($edoc_name);

	            $docs= $result['upload_edocument'];
	            $docs_count= count($result['upload_edocument']);

	            $doc_name = $request->get('document_name')['0'];



				if (!empty($edoc_name))
	            {

	            	
	                $targetDir = Vtiger_Functions::initEDocumentsStorageFileDirectory();

	                foreach ($docs as $key => $value)
	                {

	                	

                   		$files = $result['upload_edocument'][$key]['name'];
	               		$key_files = count($files);

                        $fileName = $result['upload_edocument'][$key]['name'];
                        $image_name = explode('.', $fileName); 
                        $new_filename = array_shift($image_name);
                        // Remove file basename.
                        $validatefile = array_pop($image_name);
	                      
	                        if (empty($fileName)) {

	                            unset($_SESSION['FILE_TYPE']);

	                            $doc_name = $key;
	                            $request->set('upload_edocument_clone', '');
	                            $request->set('file_name', '');
	                            $request->set('upload_edocument', '');
	                            $request->set('path', '');

	                            $request->set('document_name', $doc_name);
	                            $job = Vtiger_Record_Model::getInstanceById($recordId, 'Job');
	                            $job_name = $job->getDisplayValue('cf_1198');
	                            $file_department = $job->get('cf_1190');
                            	$file_company = $job->get('cf_1186');
                            	$file_location = $job->get('cf_1188');

	                            session_start();
	                            $_SESSION["jobname"]=$job_name;
	                            $jobname = $_SESSION["jobname"];

	                            $request->set('name', $jobname);
	                            $request->set('file_department', $file_department);
                            	$request->set('file_company', $file_company);
                           		$request->set('file_location', $file_location);

	                            $request->set('jobid', $jobid);
	                            
	                            //$recordModel = $this->saveRecord($request);

	                            global $adb;
	                            //$lastid = $recordModel->getId();
	                            $query = 'INSERT INTO vtiger_edocumentsrecords(job_id,job_refnumber,document_name, upload_document,upload_document_clone,doc_path,file_name,file_location,file_department,file_company)VALUES(?,?,?,?,?,?,?,?,?,?)';
	                            $qparams = array($job_id,$job_name,$doc_name,'','', '','',$file_location,$file_department,$file_company);
	                            $adb->pquery($query, $qparams);

	                        }

	                        elseif (!empty($fileName) && in_array($validatefile, $allowTypes))
	                        {


	                        	

	                        	

	                            unset($_SESSION['FILE_TYPE']);
	                            $targetDir = Vtiger_Functions::initEDocumentsStorageFileDirectory();
	                            $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename);
	                            $write_file_name = $file . '_' . date("Ymdhisa");
	                            $targetFilePath = $targetDir . 'EDoc_' . $write_file_name . '.' . $validatefile;
	                            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                            $newname = 'EDoc_' . $write_file_name . '.' . $validatefile;
	                            
	                            $request->set('upload_edocument_clone', $newname);
	                            $request->set('file_name', $fileName);
	                            $request->set('upload_edocument', $newname);
	                            $request->set('path', $targetDir);
	                            
	                            if (in_array($validatefile, $allowTypes))
	                            {
	                                move_uploaded_file($result['upload_edocument'][$key]['tmp_name'], $targetFilePath);
	                                chmod($targetFilePath, 0777);
	                            }

	                            $request->set('document_name', $doc_name);
	                            $job = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
	                            $job_name = $job->getDisplayValue('cf_1198');
	                            $file_department = $job->get('cf_1190');
                           		$file_company = $job->get('cf_1186');
                            	$file_location = $job->get('cf_1188');

	                            $request->set('name', $job_name);
	                            $request->set('file_department', $file_department);
                            	$request->set('file_company', $file_company);
                            	$request->set('file_location', $file_location);

	                            $request->set('jobid', $jobid);

	                            

	                            //$recordModel = $this->saveRecord($request);
	                            global $adb;
	                            //$lastid = $recordModel->getId();
	                            
	                           $query = 'INSERT INTO vtiger_edocumentsrecords(job_id,job_refnumber,document_name,edocumentsid,upload_document,upload_document_clone,doc_path,file_name,file_department,file_location,file_company)VALUES(?,?,?,?,?,?,?,?,?,?,?)';
	                            $qparams = array($job_id,$job_name,$doc_name,$record_id,$newname, $newname, $targetDir,$fileName,$file_department,$file_location,$file_company);
	                            $adb->pquery($query, $qparams);	
	                        /*else{
	                            throw new FormatException('- The File Formats you are trying to Upload 
	                                Not Allowed !');
	                        }*/
	                   	    
	                   	    } 

	                    $recordModel = $this->saveRecord($request);
	                }



	           	   	global $adb;
			        $qry ="SELECT  ercds.*
			                     FROM vtiger_edocumentsrecords AS ercds
			                     WHERE ercds.deleted = 0
			                     AND ercds.file_name != ''
			                     AND ercds.job_id = '$job_id'";
			            $link= $adb->pquery($qry, array());
			            $documents_count = $adb->num_rows($link);
			            $adb->pquery(" UPDATE vtiger_edocumentscf SET count_documents = '$documents_count' WHERE jobid = '$job_id' ");




	            }
			}//ends if with record check condition
			else{

           		$job_id = $request->get('returnrecord');
	            session_start();
	            $_SESSION["jobid"]=$job_id;
	            $jobid = $_SESSION["jobid"];
	            
	            $edoc_name = $request->get('document_name');
	            $edoc_name = array_unique($edoc_name);

	            $docs= $result['upload_edocument'];
	            $docs_count= count($result['upload_edocument']);

	            $doc_name = $request->get('document_name')['0'];

				if (!empty($edoc_name))
	            {
	                $targetDir = Vtiger_Functions::initEDocumentsStorageFileDirectory();
	                foreach ($docs as $key => $value)
	                {
	                    $files = $result['upload_edocument'][$key]['name'];
		                $key_files = count($files);

	                        $fileName = $result['upload_edocument'][$key]['name'];
	                        $image_name = explode('.', $fileName); 
	                        $new_filename = array_shift($image_name);
	                        // Remove file basename.
	                        $validatefile = array_pop($image_name);
	                      
	                        if (empty($fileName)) {
	                            unset($_SESSION['FILE_TYPE']);

	                            $doc_name = $key;
	                            $request->set('upload_edocument_clone', '');
	                            $request->set('file_name', '');
	                            $request->set('upload_edocument', '');
	                            $request->set('path', '');

	                            $request->set('document_name', $doc_name);
	                            $job = Vtiger_Record_Model::getInstanceById($recordId, 'Job');
	                            $job_name = $job->getDisplayValue('cf_1198');

	                            session_start();
	                            $_SESSION["jobname"]=$job_name;
	                            $jobname = $_SESSION["jobname"];

	                            $request->set('name', $jobname);
	                            $request->set('jobid', $jobid);
	                            
	                            //$recordModel = $this->saveRecord($request);

	                            global $adb;
	                            //$lastid = $recordModel->getId();
	                            $query = 'INSERT INTO vtiger_edocumentsrecords(job_id,job_refnumber,edocumentsid,document_name, upload_document,upload_document_clone,doc_path,file_name)VALUES(?,?,?,?,?,?,?,?)';
	                            $qparams = array($job_id,$job_name,$lastid,$doc_name, '', '', '','');
	                            $adb->pquery($query, $qparams);
	                        }

	                        elseif (!empty($fileName) && in_array($validatefile, $allowTypes))
	                        {
	                            unset($_SESSION['FILE_TYPE']);
	                            $targetDir = Vtiger_Functions::initEDocumentsStorageFileDirectory();
	                            $file = preg_replace("/[^-_a-z0-9]+/i", "_", $new_filename);
	                            $write_file_name = $file . '_' . date("Ymdhisa");
	                            $targetFilePath = $targetDir . 'EDoc_' . $write_file_name . '.' . $validatefile;
	                            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
	                            $newname = 'EDoc_' . $write_file_name . '.' . $validatefile;
	                            
	                            $request->set('upload_edocument_clone', $newname);
	                            $request->set('file_name', $fileName);
	                            $request->set('upload_edocument', $newname);
	                            $request->set('path', $targetDir);
	                            
	                            if (in_array($validatefile, $allowTypes))
	                            {
	                                move_uploaded_file($result['upload_edocument'][$key]['tmp_name'], $targetFilePath);
	                                chmod($targetFilePath, 0777);
	                            }

	                            $request->set('document_name', $doc_name);
	                            $job = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
	                            $job_name = $job->getDisplayValue('cf_1198');
	                            $request->set('name', $job_name);
	                            $request->set('jobid', $jobid);
	                            //$recordModel = $this->saveRecord($request);
	                            global $adb;
	                            //$lastid = $recordModel->getId();
	                            
	                            $query = 'INSERT INTO vtiger_edocumentsrecords(job_id,job_refnumber,document_name, upload_document,upload_document_clone,doc_path,file_name)VALUES(?,?,?,?,?,?,?)';
	                            $qparams = array($job_id,$job_name,$doc_name, $newname, $newname, $targetDir,$fileName);
	                            $adb->pquery($query, $qparams);
	                          	
	                        /*else{
	                            throw new FormatException('- The File Formats you are trying to Upload 
	                                Not Allowed !');
	                        }*/
	                    } 
	                    $recordModel = $this->saveRecord($request);
	                }
	            }//ending if 
			}//ending else



		
			//$recordModel = $this->saveRecord($request);

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
		} 

		catch(FormatException $e) {
            session_start();
            $_SESSION["FILE_TYPE"]='False';
            
        }


		catch (DuplicateException $e) {
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
			if(empty($parentModuleName)){
				$parentModuleName = $request->get('returnmodule');
			}
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			if(empty($parentRecordId)){
				$parentRecordId = $request->get('returnrecord');				
			}
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

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
