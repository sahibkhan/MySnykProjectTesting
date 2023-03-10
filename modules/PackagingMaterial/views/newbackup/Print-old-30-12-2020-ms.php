mm<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PackagingMaterial_Print_View extends Vtiger_Print_View {
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
  

  function process(Vtiger_Request $request) {  
  	
	//$current_user = Users_Record_Model::getCurrentUserModel();
	  
    $moduleName = $request->getModule();
    $record = $request->get('record');
    
    //$checklist = $request->get('checklist');
    
   
    $this->print_packaging_material($request);
    
  }
  
  public function print_packaging_material($request) {
    global $adb;
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $parentid = $request->get('parentid');
    if($parentid==0){
      $parentid = $this->get_job_id_from_PackagingMaterial($record);
    }
	
    $packaging_record_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
	
    $job_info_detail = Vtiger_Record_Model::getInstanceById($parentid, 'Job');
	
    $packaging_info_detail = Vtiger_Record_Model::getInstanceById($packaging_record_id, 'PackagingMaterial');
	
	  $document = $this->loadTemplate('printtemplates/whm/packaging.html');
    
	  $owner_user_info = Users_Record_Model::getInstanceById($packaging_info_detail->get('assigned_user_id'), 'Users');
	
	  $this->setValue('useroffice',$owner_user_info->getDisplayValue('location_id'));
  	$this->setValue('userdepartment',$owner_user_info->getDisplayValue('department_id'));
	  $this->setValue('mobile',$owner_user_info->get('phone_mobile'));
    $this->setValue('fax',$owner_user_info->get('phone_fax'));
    $this->setValue('email',htmlentities($owner_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
    $this->setValue('cityname',htmlentities($owner_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
    $this->setValue('countryname',htmlentities($owner_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
    $this->setValue('departmentcode',htmlentities($owner_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
    $this->setValue('dateadded',date('d.m.Y', strtotime($packaging_info_detail->get('createdtime'))));        
    $this->setValue('from', htmlentities($owner_user_info->get('first_name').' '.$owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
    
	  $this->setValue('job_ref_no', $job_info_detail->get('cf_1198'));
  	$this->setValue('packaging_ref_no',$packaging_info_detail->get('cf_5754')); 
	  $this->setValue('warehouse_id',$packaging_info_detail->getDisplayValue('cf_5764'));
	
    $rs = $adb->pquery("SELECT cf_6294 FROM vtiger_packagingmaterialcf where cf_6294!='' AND cf_5754='".$packaging_info_detail->get('cf_5754')."' limit 1");
    $row_driver = $adb->fetch_array($rs);
    $driver_name = $row_driver['cf_6294'];
    $this->setValue('driver_name', $driver_name);
    
    $packaging_items='';  
    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');
    
    $relatedModuleName_1 = 'PackagingMaterial';
    $parentRecordModel_1 = $job_info_detail;
    $relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
    $models_1 = $relationListView_1->getEntries($pagingModel_1);
    
    $pm_items = '';
    $total_amount=0;
    $i=1;
  	foreach($models_1 as $key => $model){
			$packaging_material_items_id  = $model->getId();			
			$sourceModule   = 'PackagingMaterial';	
			$pmitem_info = Vtiger_Record_Model::getInstanceById($packaging_material_items_id, $sourceModule);
			if($pmitem_info->get('cf_5754')==$packaging_info_detail->get('cf_5754'))
			{
				$parent_numbering =$i;
				$detail = $pmitem_info->getDisplayValue('cf_6292');
				$packaging_items .='<tr>
									<td>'.$i++.'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5738').''.(!empty($detail) ? '<br>'.$detail : '' ).'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5740').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5744').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5746').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5748').'</td>
									<td>'.$pmitem_info->get('cf_6142').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5750').'</td>
									<td>'.$pmitem_info->getDisplayValue('cf_5752').'</td>
									</tr>';
				$total_amount +=$pmitem_info->get('cf_6142');
				
				$custom_request = $pmitem_info->get('cf_6290');
				$Special_Item_Code  = $pmitem_info->get('cf_5738'); //item code
				
				//if($custom_request=='Yes')
				if($Special_Item_Code=="SR-1" || $Special_Item_Code=="SL-1" )
				{
					$db_cpm = PearDatabase::getInstance();	
					$query_custom_packaging = "SELECT * FROM vtiger_custompackingmaterial
								INNER JOIN  vtiger_custompackingmaterialcf ON 
						vtiger_custompackingmaterialcf.custompackingmaterialid = vtiger_custompackingmaterial.custompackingmaterialid
								INNER JOIN vtiger_crmentity ON 
								vtiger_crmentity.crmid = vtiger_custompackingmaterial.custompackingmaterialid
								INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
								WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
								AND crmentityrel.module='PackagingMaterial' AND crmentityrel.relmodule='CustomPackingMaterial'";
					$params_rel = array($packaging_material_items_id);							   
					$result_rel = $db_cpm->pquery($query_custom_packaging, $params_rel);
					$numRows_cpm = $db_cpm->num_rows($result_rel);	
					//To Access Custom Item Code
					$child_numbering=1;
					for($kk=0; $kk< $db_cpm->num_rows($result_rel); $kk++ ) {
						
						$row_sub_packaging = $db_cpm->fetch_row($result_rel,$kk);
						$custompackingmaterialid = $row_sub_packaging['custompackingmaterialid'];
						$c_sourceModule   = 'CustomPackingMaterial';
						$custom_pmitem_info = Vtiger_Record_Model::getInstanceById($custompackingmaterialid, $c_sourceModule);
						
						$packaging_items .='<tr>
											<td>'.$parent_numbering.'.'.$child_numbering++.'</td>
											<td>'.$custom_pmitem_info->getDisplayValue('cf_6268').'</td>
											<td></td>
											<td></td>
											<td>'.$custom_pmitem_info->getDisplayValue('cf_6276').'</td>
											<td>'.$custom_pmitem_info->getDisplayValue('cf_6278').'</td>
											<td>'.$custom_pmitem_info->get('cf_6282').'</td>
											<td></td>
											<td></td>
											</tr>';
						$total_amount +=$custom_pmitem_info->get('cf_6282');					
						
					}	
				}
			}
			
	}
	
	
    $this->setValue('packaging_items',$packaging_items);
	 $this->setValue('total_amount',$total_amount);
	
    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
	
	
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*???????????? ????????????, ?????????????? ??.??.??.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 
    //$filename = 'fleet_expense.txt';
    //$this->save('fleet_expense.txt'); 
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            PMR Form, GLOBALINK
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
        </tr>
      </table>');
	
    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>');

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*?????????????????? pdf*/
    
        
    $pdf_name = 'pdf_docs/packaging_material_'.$record.'.pdf';
    
    $mpdf->Output($pdf_name, 'F');
    //header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
    header('Location:'.$pdf_name);
    exit;   
  }
  
  function get_job_id_from_PackagingMaterial($recordId=0) {
			$adb = PearDatabase::getInstance();

			$checkjob = $adb->pquery("SELECT rel1.crmid AS job_id
																FROM `vtiger_crmentityrel` AS rel1
																WHERE rel1.relcrmid = '".$recordId."' AND rel1.module='Job' AND rel1.relmodule='PackagingMaterial'", array());
			$crmId = $adb->query_result($checkjob, 0, 'job_id');
			$job_id = $crmId;
			return $job_id;
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
    //$this->_objZip->extractTo('Fleettrip.txt', $this->_documentXML);
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