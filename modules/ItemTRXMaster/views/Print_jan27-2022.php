<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ItemTRXMaster_Print_View extends Vtiger_Print_View {

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
		
		$this->print_pick_list($request);
		
		/*
		$response = new Vtiger_Response();
		$response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
		$response->emit();
		*/	
	}
	
	public function print_pick_list($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$ItemTRXMaster_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		
		$ItemTRXMaster_info_detail = Vtiger_Record_Model::getInstanceById($ItemTRXMaster_id, 'ItemTRXMaster');
		
				
		$document = $this->loadTemplate('printtemplates/whm/pick_list.html');
		
		$owner_ItemTRXMaster_user_info = Users_Record_Model::getInstanceById($ItemTRXMaster_info_detail->get('assigned_user_id'), 'Users');
		
	
		$this->setValue('from', htmlentities($owner_ItemTRXMaster_user_info->get('first_name').' '.$owner_ItemTRXMaster_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('warehouse_id', $ItemTRXMaster_info_detail->getDisplayValue('cf_5591'));
		$this->setValue('document_type',$ItemTRXMaster_info_detail->getDisplayValue('cf_5583'));
		$this->setValue('document_number',$ItemTRXMaster_info_detail->getDisplayValue('cf_5585'));
		$this->setValue('transaction_date',$ItemTRXMaster_info_detail->getDisplayValue('cf_5587'));
		$this->setValue('posting_date',$ItemTRXMaster_info_detail->getDisplayValue('cf_5589'));
		$this->setValue('transaction_status',$ItemTRXMaster_info_detail->getDisplayValue('cf_5601'));
		$this->setValue('glk_company',$ItemTRXMaster_info_detail->getDisplayValue('cf_5595'));
		$this->setValue('glk_ref_doc_number',$ItemTRXMaster_info_detail->getDisplayValue('cf_6064'));
		$this->setValue('company_id',$ItemTRXMaster_info_detail->getDisplayValue('cf_5597'));
		$this->setValue('ref_doc_number',$ItemTRXMaster_info_detail->getDisplayValue('cf_5599'));
		
		
		$pagingModel_1 = new Vtiger_Paging_Model();
		$pagingModel_1->set('page','1');
		
		$relatedModuleName_1 = 'ItemTRXDetail';
		$parentRecordModel_1 = $ItemTRXMaster_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);
		
		$ItemTRXDetail_items = '';
		$i=1;
		foreach($models_1 as $key => $model){
			$ItemTRXDetail_id  = $model->getId();			
			$sourceModule   = 'ItemTRXDetail';	
			$ItemTRXDetail_info = Vtiger_Record_Model::getInstanceById($ItemTRXDetail_id, $sourceModule);
			
			$ItemTRXDetail_items .='<tr>
									<td>'.$i++.'</td>
									<td>'.$ItemTRXDetail_info->getDisplayValue('cf_5617').'</td>
									<td>'.$ItemTRXDetail_info->getDisplayValue('cf_5613').'</td>
									<td>'.$ItemTRXDetail_info->getDisplayValue('cf_5621').'</td>
									</tr>';
		}
		$this->setValue('pick_list', $ItemTRXDetail_items);
		
		
			
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 
		
		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">PL Form, GLOBALINK, designed: March, 2010</td></tr>
		<tr><td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td></tr></table>');
				$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
		
				
		$pdf_name = 'pm_requisition.pdf';
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
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