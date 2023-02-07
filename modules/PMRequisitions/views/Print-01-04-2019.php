<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMRequisitions_Print_View extends Vtiger_Print_View {

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
		
		$this->print_pm_requisitions($request);
		
		/*
		$response = new Vtiger_Response();
		$response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
		$response->emit();
		*/	
	}
	
	public function print_pm_requisitions($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$pm_requisition_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		
		$pm_info_detail = Vtiger_Record_Model::getInstanceById($pm_requisition_id, 'PMRequisitions');
		
				
		$document = $this->loadTemplate('printtemplates/packing_material.html');
		
		$owner_pm_user_info = Users_Record_Model::getInstanceById($pm_info_detail->get('assigned_user_id'), 'Users');
		
		$this->setValue('useroffice',$owner_pm_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$owner_pm_user_info->getDisplayValue('department_id'));
		
		$this->setValue('cityname',htmlentities($pm_info_detail->getDisplayValue('cf_4273'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($pm_info_detail->getDisplayValue('cf_4275'), ENT_QUOTES, "UTF-8"));	
		$this->setValue('dateadded',date('d.m.Y', strtotime($pm_info_detail->get('CreatedTime'))));				
		//$this->setValue('billingto', $pay_to_info->get('accountname'));
		$this->setValue('from', htmlentities($owner_pm_user_info->get('first_name').' '.$owner_pm_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('pm_ref_no', $pm_info_detail->getDisplayValue('cf_4717'));
		
		$this->setValue('ceo_approval', $pm_info_detail->getDisplayValue('cf_4581'));
		if($pm_info_detail->getDisplayValue('cf_4583')!='')
		{
		$this->setValue('ceo_approved_on', date('d.m.Y', strtotime($pm_info_detail->getDisplayValue('cf_4583'))));
		}
		else{
			$this->setValue('ceo_approved_on','');
		}
		
		$this->setValue('rrs_approval', $pm_info_detail->getDisplayValue('cf_4585'));
		if($pm_info_detail->getDisplayValue('cf_4587')!='')
		{
			$this->setValue('rrs_approved_on', date('d.m.Y', strtotime($pm_info_detail->getDisplayValue('cf_4587'))));
		}
		else{
			$this->setValue('rrs_approved_on','');
		}
		
		$this->setValue('fd_approval', $pm_info_detail->getDisplayValue('cf_4589'));
		if($pm_info_detail->getDisplayValue('cf_4591')!='')
		{
			$this->setValue('fd_approved_on', date('d.m.Y', strtotime($pm_info_detail->getDisplayValue('cf_4591'))));
		}
		else{
			$this->setValue('fd_approved_on','');
		}
		
		$this->setValue('pm_status',$pm_info_detail->getDisplayValue('cf_4593'));
		
		
		$pagingModel_1 = new Vtiger_Paging_Model();
		$pagingModel_1->set('page','1');
		
		$relatedModuleName_1 = 'PMItems';
		$parentRecordModel_1 = $pm_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);
		
		$pm_items = '';
		$i=1;
		foreach($models_1 as $key => $model){
			$pm_items_id  = $model->getId();			
			$sourceModule   = 'PMItems';	
			$pmitem_info = Vtiger_Record_Model::getInstanceById($pm_items_id, $sourceModule);
			
			$pm_items .='<tr>
						<td>'.$i++.'</td>
						<td>'.$pmitem_info->getDisplayValue('cf_4279').'</td>
						<td>'.$pmitem_info->getDisplayValue('cf_4281').'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4283'), 2 ,  "." , "," ).'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4573'), 2 ,  "." , "," ).'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4719'), 2 ,  "." , "," ).'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4721'), 2 ,  "." , "," ).'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4725'), 2 ,  "." , "," ).'</td>	
						<td>'.$pmitem_info->getDisplayValue('cf_4563').'</td>
						<td>'.$pmitem_info->getDisplayValue('cf_4565').'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4723'), 2 ,  "." , "," ).'</td>
						<td>'.number_format ($pmitem_info->getDisplayValue('cf_4567'), 2 ,  "." , "," ).'</td>
						<td>'.$pmitem_info->getDisplayValue('cf_4575').'</td>
						</tr>';
		}
		$this->setValue('pmitems', $pm_items);
		
		
			$pmitems_sum_sql = "SELECT sum(pmitemscf.cf_4723) as total_cost_local_currency , sum(pmitemscf.cf_4575) as total_cost_in_usd FROM `vtiger_pmitemscf` as pmitemscf 
							    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = pmitemscf.pmitemsid
							    INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 	WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
								AND crmentityrel.module='PMRequisitions' AND crmentityrel.relmodule='PMItems'";
					
			$params = array($pm_requisition_id);
			$result = $adb->pquery($pmitems_sum_sql, $params);
			$row_pm_items = $adb->fetch_array($result);
			
			$total_cost_local_currency = $row_pm_items['total_cost_local_currency'];
			$total_cost_in_usd = $row_pm_items['total_cost_in_usd'];
			
			$this->setValue('TOTAL_COST_LOCAL_CURRENCY' , number_format ( $total_cost_local_currency , 2 ,  "." , "," ));
			$this->setValue('TOTAL_COST_IN_USD' , number_format ( $total_cost_in_usd , 2 ,  "." , "," ));
			
			
			include("include/Exchangerate/exchange_rate_class.php");
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pm_info_detail->get('cf_4271'));
			$file_title_currency = $reporting_currency;
			$createdtime =  $pm_info_detail->get('CreatedTime');
			$exchange_rate_date = date('Y-m-d', strtotime($createdtime));
			
			$final_exchange_rate = 0;
			if(!empty($exchange_rate_date))
			{
				if($file_title_currency!='USD')
				{
					$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
				}else{
					$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
				}
			}
			$this->setValue('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 
		
		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">PM Form, GLOBALINK, designed: March, 2010</td></tr>
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