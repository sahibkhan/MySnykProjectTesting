<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class EDocuments_SaveAjax_Action extends Vtiger_Save_Action
{

    public function process(Vtiger_Request $request)
    {

        $fieldToBeSaved = $request->get('field');
        $response = new Vtiger_Response();
        $result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
        $recordId = $request->get('sourceRecord');
        
        $allowTypes = array(
                        'jpg',
                        'JPG',
                        'png',
                        'PNG',
                        'jpeg',
                        'JPEG',
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
        
        try
        {
            vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode', false));
            

          

            $job_id = $request->get('sourceRecord');
            session_start();
            $_SESSION["jobid"]=$job_id;
            $jobid = $_SESSION["jobid"];
        
            $edoc_name = $request->get('document_name');
            $edoc_name = array_unique($edoc_name);

            $docs= $result['upload_edocument'];
            $docs_count= count($result['upload_edocument']);

            if (!empty($edoc_name))
            {
                $targetDir = Vtiger_Functions::initEDocumentsStorageFileDirectory();

                foreach ($docs as $key => $value)
                {
                    $files = $result['upload_edocument'][$key]['name'];
                    $key_files = count($files);

                    for ($i=0; $i < $key_files; $i++) {
                        $fileName = $result['upload_edocument'][$key]['name'][$i];

                        $image_name = explode('.', $fileName);   
                        $new_filename = array_shift($image_name);
                        // Remove file basename.
                        $validatefile = array_pop($image_name);
                        
                        //check if no documents uploaded against any single type
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
                            $file_department = $job->getDisplayValue('cf_1190');
                            $file_company = $job->getDisplayValue('cf_1186');
                            $file_location = $job->getDisplayValue('cf_1188');

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

                            //$request->set('count_documents', $key_files);

                            
                            if (in_array($validatefile, $allowTypes))
                            {
                                move_uploaded_file($result['upload_edocument'][$key]['tmp_name'][$i], $targetFilePath);
                                chmod($targetFilePath, 0777);
                            }
                            $doc_name = $key;
                            $request->set('document_name', $doc_name);
                            $job = Vtiger_Record_Model::getInstanceById($recordId, 'Job');
                            $job_name = $job->getDisplayValue('cf_1198');
                            $file_department = $job->getDisplayValue('cf_1190');
                            $file_company = $job->getDisplayValue('cf_1186');
                            $file_location = $job->getDisplayValue('cf_1188');

                            $request->set('name', $job_name);
                            $request->set('file_department', $file_department);
                            $request->set('file_company', $file_company);
                            $request->set('file_location', $file_location);

                            $request->set('jobid', $jobid);

                            //$recordModel = $this->saveRecord($request);
                            
                            global $adb;
                            //$lastid = $recordModel->getId();
                            
                            $query = 'INSERT INTO vtiger_edocumentsrecords(job_id,job_refnumber,document_name, upload_document,upload_document_clone,doc_path,file_name,file_location,file_department,file_company)VALUES(?,?,?,?,?,?,?,?,?,?)';
                            $qparams = array($job_id,$job_name,$doc_name, $newname, $newname, $targetDir,$fileName,$file_location,$file_department,$file_company);
                            $adb->pquery($query, $qparams);
                        }
                        /*else{
                            throw new FormatException('- The File Formats you are trying to Upload 
                                Not Allowed !');
                        }*/
                    } 

                    $recordModel = $this->saveRecord($request);

                    global $adb;
                    $lastid = $recordModel->getId();
                    $adb->pquery(" UPDATE vtiger_edocumentsrecords SET edocumentsid = '$lastid' WHERE job_id = '$job_id' AND document_name='$doc_name' ");
                }//end foreach loop



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

            //global $adb;
            //$lastid = $recordModel->getId();
            //$adb->pquery(" UPDATE vtiger_edocumentscf SET 'count_documents' = $documents_count WHERE 'jobid' = $job_id ");
            //unset($_SESSION['jobid']);
            //unset($_SESSION['jobname']);

            vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);
            $fieldModelList = $recordModel->getModule()
                ->getFields();
            $result = array();
            $picklistColorMap = array();
            foreach ($fieldModelList as $fieldName => $fieldModel)
            {
                if ($fieldModel->isViewable())
                {
                    $recordFieldValue = $recordModel->get($fieldName);
                    if (is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist')
                    {
                        foreach ($recordFieldValue as $picklistValue)
                        {
                            $picklistColorMap[$picklistValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $picklistValue);
                        }
                        $recordFieldValue = implode(' |##| ', $recordFieldValue);
                    }
                    if ($fieldModel->getFieldDataType() == 'picklist')
                    {
                        $picklistColorMap[$recordFieldValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $recordFieldValue);
                    }
                    $fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
                    if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date' && $fieldModel->getFieldDataType() !== 'double')
                    {
                        $displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId());
                    }
                    if ($fieldModel->getFieldDataType() == 'currency')
                    {
                        $displayValue = Vtiger_Currency_UIType::transformDisplayValue($fieldValue);
                    }
                    if (!empty($picklistColorMap))
                    {
                        $result[$fieldName] = array(
                            'value' => $fieldValue,
                            'display_value' => $displayValue,
                            'colormap' => $picklistColorMap
                        );
                    }
                    else
                    {
                        $result[$fieldName] = array(
                            'value' => $fieldValue,
                            'display_value' => $displayValue
                        );
                    }
                }
            }

            //Handling salutation type
            if ($request->get('field') === 'firstname' && in_array($request->getModule() , array(
                'Contacts',
                'Leads'
            )))
            {
                $salutationType = $recordModel->getDisplayValue('salutationtype');
                $firstNameDetails = $result['firstname'];
                $firstNameDetails['display_value'] = $salutationType . " " . $firstNameDetails['display_value'];
                if ($salutationType != '--None--') $result['firstname'] = $firstNameDetails;
            }

            if ($request->get('relationOperation'))
            {
                $parentModuleName = $request->get('sourceModule');
                $parentRecordId = $request->get('sourceRecord');

                //$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
                //TODO : Url should load the related list instead of detail view of record
                //$loadUrl = $parentRecordModel->getListUrl();
                //index.php?module=Fleettrip&relatedModule=Roundtrip&view=Detail&record=2231835&mode=showRelatedList&relationId=208&tab_label=Round%20Trip&app=MARKETING
                $loadUrl = 'index.php?module=' . $parentModuleName . '&relatedModule=' . $request->get('module') . '&view=Detail&record=' . $parentRecordId . '&mode=showRelatedList&relationId=325&tab_label=EDocuments&app=MARKETING';
            }
            header("Location: $loadUrl");
            // removed decode_html to eliminate XSS vulnerability
            //$result['_recordLabel'] = decode_html($recordModel->getName());
            //$result['_recordId'] = $recordModel->getId();
            //$response->setEmitType(Vtiger_Response::$EMIT_JSON);
            //$response->setResult($result); 
        }

        /*catch(Exception $e)
        { 
            $requestData['type'] = true;
            global $vtiger_current_version;
            $viewer = new Vtiger_Viewer();
            $viewer->assign('TYPE', $requestData);
        }*/

        catch(FormatException $e) {
            //$msg = $response->setError($e->getMessage());
            session_start();
            $_SESSION["FILE_TYPE"]='False';
           if ($request->get('relationOperation'))
            {
                $parentModuleName = $request->get('sourceModule');
                $parentRecordId = $request->get('sourceRecord');
                $loadUrl = 'index.php?module=' . $parentModuleName . '&relatedModule=' . $request->get('module') . '&view=Detail&record=' . $parentRecordId . '&mode=showRelatedList&relationId=325&tab_label=EDocuments&app=MARKETING';
            }
            header("Location: $loadUrl");

              
        }


        catch(DuplicateException $e)
        {
            $response->setError($e->getMessage() , $e->getDuplicationMessage() , $e->getMessage());
        }
        catch(Exception $e)
        {
            $response->setError($e->getMessage());
        }
        $response->emit();
    }

    /**
     * Function to get the record model based on the request parameters
     * @param Vtiger_Request $request
     * @return Vtiger_Record_Model or Module specific Record Model instance
     */
    public function getRecordModelFromRequest(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        if ($moduleName == 'Calendar')
        {
            $moduleName = $request->get('calendarModule');
        }
        $recordId = $request->get('record');

        if (!empty($recordId))
        {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            $recordModel->set('id', $recordId);
            $recordModel->set('mode', 'edit');

            $fieldModelList = $recordModel->getModule()
                ->getFields();
            foreach ($fieldModelList as $fieldName => $fieldModel)
            {
                //For not converting createdtime and modified time to user format
                $uiType = $fieldModel->get('uitype');
                if ($uiType == 70)
                {
                    $fieldValue = $recordModel->get($fieldName);
                }
                else
                {
                    $fieldValue = $fieldModel->getUITypeModel()
                        ->getUserRequestValue($recordModel->get($fieldName));
                }
                // To support Inline Edit in Vtiger7
                if ($request->has($fieldName))
                {
                    $fieldValue = $request->get($fieldName, null);
                }
                else if ($fieldName === $request->get('field'))
                {
                    $fieldValue = $request->get('value');
                }
                $fieldDataType = $fieldModel->getFieldDataType();
                if ($fieldDataType == 'time' && $fieldValue !== null)
                {
                    $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
                }
                if ($fieldValue !== null)
                {
                    if (!is_array($fieldValue))
                    {
                        $fieldValue = trim($fieldValue);
                    }
                    $recordModel->set($fieldName, $fieldValue);
                }
                $recordModel->set($fieldName, $fieldValue);
                if ($fieldName === 'contact_id' && isRecordExists($fieldValue))
                {
                    $contactRecord = Vtiger_Record_Model::getInstanceById($fieldValue, 'Contacts');
                    $recordModel->set("relatedContact", $contactRecord);
                }
            }
        }
        else
        {
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $recordModel->set('mode', '');

            $fieldModelList = $moduleModel->getFields();
            foreach ($fieldModelList as $fieldName => $fieldModel)
            {
                if ($request->has($fieldName))
                {
                    $fieldValue = $request->get($fieldName, null);
                }
                else
                {
                    $fieldValue = $fieldModel->getDefaultFieldValue();
                }
                if ($fieldValue)
                {
                    $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue, $fieldModel);
                }
                $fieldDataType = $fieldModel->getFieldDataType();
                if ($fieldDataType == 'time' && $fieldValue !== null)
                {
                    $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
                }
                if ($fieldValue !== null)
                {
                    if (!is_array($fieldValue))
                    {
                        $fieldValue = trim($fieldValue);
                    }
                    $recordModel->set($fieldName, $fieldValue);
                }
            }
        }

        return $recordModel;
    }
}

class FormatException extends Exception {};


