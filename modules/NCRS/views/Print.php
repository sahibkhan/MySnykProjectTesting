<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class NCRS_Print_View extends Vtiger_Print_View {

	/**
     * Temporary Filename
     * 
     * @var string
     */
    private $_tempFileName;
	
	function __construct() {
		parent::__construct();
		ob_start();			
	}

	function checkPermission(Vtiger_Request $request) {
		return true;
	}
	/*function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		//Later we change this Export to Print 
		if(!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Export')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}*/

	function process(Vtiger_Request $request) {		
		//parent::process($request, false);	
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$this->print_NCR($request);
		/*
		$response = new Vtiger_Response();
		$response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
		$response->emit();
		*/	
	}
	
	private function ncrrecords($sourcerecordid) {
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');

		$recordModel = Vtiger_Record_model::getInstanceById($sourcerecordid, 'NCRS');		
		$relatedModuleName = 'NCRRaised';
		$parentRecordModel = $recordModel;
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$models = $relationListView->getEntries($pagingModel);
		$raised_for_details='<table>';
		$i=0;
		foreach($models as $key => $model)
		{
			$i++;
			$raised_for_persons = $model->getDisplayValue('cf_6512');
			$raised_for_department = $model->getDisplayValue('cf_6508');
			$raised_for_location = $model->getDisplayValue('cf_6510');
			$raised_for_details .= "<tr><td>".$raised_for_persons." ".$raised_for_department." / ".$raised_for_location."</td></tr>";
		}
		$raised_for_details .= "</table>";
		return $raised_for_details;
	}

	public function print_NCR($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$current_user = Users_Record_Model::getCurrentUserModel();
		$NCR_info_detail = Vtiger_Record_Model::getInstanceById($record, 'NCRS');
		
		$document = $this->loadTemplate('printtemplates/NCR/nonconformance.html');

		$created_by_user_info = Users_Record_Model::getInstanceById($NCR_info_detail->get('assigned_user_id'), 'Users');
		$this->setValue('useroffice',$created_by_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$created_by_user_info->getDisplayValue('department_id'));
		$this->setValue('from', htmlentities($created_by_user_info->get('first_name').' '.$created_by_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($NCR_info_detail->get('createdtime'))));
		$this->setValue('approved_by',$NCR_info_detail->get('cf_6428'));
		$this->setValue('ncr_no', $NCR_info_detail->get('cf_1963'));

		$initiator_user_info = Users_Record_Model::getInstanceById($NCR_info_detail->get('cf_1961'), 'Users');
		$initiator_info = $initiator_user_info->get('first_name').' '.$initiator_user_info->get('last_name');
		$initiator_location= $initiator_user_info->getDisplayValue('location_id');
		$initiator_department= $initiator_user_info->getDisplayValue('department_id');
		$initiator = $initiator_info."  : ".$initiator_location." / ".$initiator_department;
		$this->setValue('initiator', $NCR_info_detail->getDisplayValue('cf_1961')." ".$initiator_department." / ".$initiator_location);
		$this->setValue('client_info', $NCR_info_detail->getDisplayValue('cf_6440'));

		$for_person = $NCR_info_detail->getDisplayValue('cf_6420');
		$for_department = $NCR_info_detail->getDisplayValue('cf_6416');
		$for_location = $NCR_info_detail->getDisplayValue('cf_6412');
		$this->setValue('close_out_date', $NCR_info_detail->getDisplayValue('cf_6514'));
		$ncr_raised_for = $this->ncrrecords($record);//$for_person." ".$for_department." / ".$for_location;
		$this->setValue('ncr_raised_for', $ncr_raised_for);
		
		$job_no = $NCR_info_detail->get('cf_6424');
		$this->setValue('job_ref_no',$job_no);
		$description_of_non_conformance = $NCR_info_detail->get('cf_1965');
		$description_of_non_conformance = nl2br($description_of_non_conformance);
		
		//$this->setValue('description_of_non_conformance',htmlentities($description_of_non_conformance, ENT_QUOTES, "UTF-8"));
		$this->setValue('description_of_non_conformance', $description_of_non_conformance);
		
		$this->setValue('root_cause',htmlentities($NCR_info_detail->get('cf_1967'), ENT_QUOTES, "UTF-8"));
		$this->setValue('classification_of_non_conformance',htmlentities($NCR_info_detail->get('cf_6410'), ENT_QUOTES, "UTF-8"));
		$this->setValue('interim_action',htmlentities($NCR_info_detail->get('cf_6422'), ENT_QUOTES, "UTF-8"));

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');
		
		$relatedModuleName = 'CorretiveAction';
		$parentRecordModel = $NCR_info_detail;
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$models = $relationListView->getEntries($pagingModel);
		$corrective_action_details='';
		$i=0;
		foreach($models as $key => $model)
		{
			$i++;
			$corrective_action = $model->get('cf_6434');
			$responsible_id = $model->get('cf_6436');
			$deadline = $model->getDisplayValue('cf_6438');

			$responsible_user_info = Users_Record_Model::getInstanceById($responsible_id, 'Users');
			$responsible_info = $responsible_user_info->get('first_name').' '.$responsible_user_info->get('last_name');
			$responsible_location= $responsible_user_info->getDisplayValue('location_id');
			$iresponsible_department= $responsible_user_info->getDisplayValue('department_id');
			$responsible_person = $responsible_info."  : ".$responsible_location." / ".$responsible_department;

			
			$corrective_action_details .= '<tr><td>7.'.$i.'</td><td>'.$corrective_action.'</td><td>'.$responsible_person.'</td><td>'.$deadline.'</td></tr>';

		}
		
		$this->setValue('corrective_action',$corrective_action_details);
		
		include('include/mpdf60/mpdf.php');
		@date_default_timezone_set($current_user->get('time_zone'));
		// $mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 30, 15, 10, 5);
  		$mpdf->charset_in = 'utf8';
				
		$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="center"><img src="include/logo_doc.jpg"/></td></tr></table>');
		$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
  <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
  <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
		$mpdf->AddPage();
		$pdf_name = 'pdf_docs/nonconformance_report.pdf';
		
		$mpdf->Output($pdf_name, 'F');
		header('Location:'.$pdf_name);
		exit;	

	}

	public function template($strFilename)
	{
		$path = dirname($strFilename);
        //$this->_tempFileName = $path.time().'.docx';
       // $this->_tempFileName = $path.'/'.time().'.txt';
		$this->_tempFileName = $strFilename;
		//copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File
		
		$this->_documentXML = file_get_contents($this->_tempFileName);
		
	}
	
	 /**
     * Set a Template value
     * 
     * @param mixed $search
     * @param mixed $replace
     */
    public function setValue($search, $replace) {
		
        if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
            $search = '${'.$search.'}';
        }
      // $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");
        if(!is_array($replace)) {
           // $replace = utf8_encode($replace);
		   $replace =iconv('utf-8', 'utf-8', $replace);
        }
       
        $this->_documentXML = str_replace($search, $replace, $this->_documentXML);
		
    }
	
	 /**
     * Save Template
     * 
     * @param string $strFilename
     */
    public function save($strFilename) {
        if(file_exists($strFilename)) {
            unlink($strFilename);
        }
        
        //$this->_objZip->extractTo('fleet.txt', $this->_documentXML);
		
		file_put_contents($this->_tempFileName, $this->_documentXML);
        
        // Close zip file
       /* if($this->_objZip->close() === false) {
            throw new Exception('Could not close zip file.');
        }*/
        
        rename($this->_tempFileName, $strFilename);
    }
	
	public function loadTemplate($strFilename) {
        if(file_exists($strFilename)) {
            $template = $this->template($strFilename);
            return $template;
        } else {
            trigger_error('Template file '.$strFilename.' not found.', E_ERROR);
        }
    }
}