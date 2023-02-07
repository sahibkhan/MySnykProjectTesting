<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MOTIW_Print_View extends Vtiger_Print_View { 
	
	
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
		


		$moduleName = $request->getModule();
		$record = $request->get('record');		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ms_info = Vtiger_Record_Model::getInstanceById($record, 'MOTIW');
				
		$qt_owner_user_info = Users_Record_Model::getInstanceById($ms_info->get('assigned_user_id'), 'Users');
			
		$document = $this->loadTemplate('printtemplates/MotiwSystem/pdf.html');
		$adb = PearDatabase::getInstance(); 

		if ($ms_info->get('cf_6362')){
			$accountId = $ms_info->get('cf_6362');
			$accountRecord = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
			if ($accountRecord->get('accountname')){
				$accountname = $accountRecord->get('accountname');
			} else {
				$accountname = '';
			}
		} else {
			$accountname = '';
		}


		// Motiw System Details
		$this->setValue('title',$ms_info->get('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('created_time',$ms_info->get('CreatedTime'), ENT_QUOTES, "UTF-8");
		$this->setValue('modified_time',$ms_info->get('ModifiedTime'), ENT_QUOTES, "UTF-8");


		$this->setValue('account',$accountname, ENT_QUOTES, "UTF-8");
		$this->setValue('type_of_agreement',$ms_info->get('cf_6366'), ENT_QUOTES, "UTF-8");
		$this->setValue('type_of_document',$ms_info->get('cf_6758'), ENT_QUOTES, "UTF-8");
		$this->setValue('agreement_ref_no',$ms_info->get('cf_6760'), ENT_QUOTES, "UTF-8");
		$this->setValue('date_of_registration',$ms_info->get('cf_6370'), ENT_QUOTES, "UTF-8");
		$usersRecord = Vtiger_Record_Model::getInstanceById($ms_info->get('assigned_user_id'), 'Users');
		$created_by = $usersRecord->get('first_name')." ".$usersRecord->get('last_name');


		
		$this->setValue('created_by',$created_by, ENT_QUOTES, "UTF-8");
		$this->setValue('agreement_amount',$ms_info->get('cf_6364'), ENT_QUOTES, "UTF-8");
		//$this->setValue('attachments',$ms_info->get('cf_6282'), ENT_QUOTES, "UTF-8");
		//$this->setValue('dead_line',$ms_info->get('cf_6284'), ENT_QUOTES, "UTF-8");
		//$this->setValue('related_doucments',$ms_info->get('cf_6394'), ENT_QUOTES, "UTF-8");
		$this->setValue('description',$ms_info->get('cf_6374'), ENT_QUOTES, "UTF-8");
		$usersRecord = Vtiger_Record_Model::getInstanceById($ms_info->get('cf_6764'), 'Users');
		$controller = $usersRecord->get('first_name')." ".$usersRecord->get('last_name');
		$this->setValue('controller',$controller, ENT_QUOTES, "UTF-8");
		$usersRecord = Vtiger_Record_Model::getInstanceById($ms_info->get('cf_6376'), 'Users');
		$cordinator = $usersRecord->get('first_name')." ".$usersRecord->get('last_name');

		$this->setValue('coordinator',$cordinator, ENT_QUOTES, "UTF-8");
		$this->setValue('coordinator_location',Vtiger_LocationList_UIType::getDisplayValue($ms_info->get('cf_6766')), ENT_QUOTES, "UTF-8");
		$this->setValue('coordinator_deparment',Vtiger_DepartmentList_UIType::getDisplayValue($ms_info->get('cf_6768')), ENT_QUOTES, "UTF-8");


		require_once('Detail.php');
		$instanceOfDetails = new MOTIW_Detail_View();
		$queryApprovalParties = $instanceOfDetails->getApprovalParties($record);
		$sql = $queryApprovalParties;

		$result = $adb->pquery($sql);
		$noofrows = $adb->num_rows($result);
		$aprrovalroutehistoryid=$adb->query_result($result,$noofrows-1,'approvalroutehistoryid');
		    if($noofrows > 0){
				$approvalroutes = array();
				$routes = '<table width="850" border=1 cellspacing=0 cellpadding=4>
					  <tbody>';
								  
				
				for($n=0; $n<$noofrows; $n++) {
					
					$approvalroutes[$n]['name']=$adb->query_result($result,$n,'name');
					$usersRecord = Vtiger_Record_Model::getInstanceById($adb->query_result($result,$n,'cf_6788'), 'Users');
					$first_name = $usersRecord->get('first_name');
					$last_name = $usersRecord->get('last_name');
					$userEmail = $usersRecord->get('email1');
					$approvalroutes[$n]['username']=$first_name." ".$last_name;
					$approvalroutes[$n]['sequence']=$adb->query_result($result,$n,'cf_6790');
					$approvalroutes[$n]['status']=$adb->query_result($result,$n,'cf_6792');
					$approvalroutes[$n]['uDate']=$adb->query_result($result,$n,'cf_6794');

/* 
					$department_id = $adb->query_result($result, $n, $usersRecord->get('department_id'));
					$location_id = $adb->query_result($result, $n, $usersRecord->get('location_id')); */

					
					$sqlDepartment = "SELECT cf_1542
														FROM `vtiger_departmentcf`
														WHERE departmentid = ?";
					$departmentResult = $adb->pquery($sqlDepartment, array($usersRecord->get('department_id')));
					$partieDepartmentName = $adb->query_result($departmentResult, 0, 'cf_1542');
					
					
					$sqlLocation = "SELECT cf_1559
														FROM `vtiger_locationcf`
														WHERE locationid = ?";
					$locationResult = $adb->pquery($sqlLocation, array($usersRecord->get('location_id')));
					$partieLocationName = $adb->query_result($locationResult, 0, 'cf_1559');
					

					$sqlUser = "SELECT cf_3341, cf_3349, cf_3421
											FROM `vtiger_userlistcf`
											WHERE cf_3355 = ?";
					$sqlUserRes = $adb->pquery($sqlUser, array($userEmail));
					$approvalPartiePosition = $adb->query_result($sqlUserRes, 0, 'cf_3341');


					$userPosition = $approvalPartiePosition.', '.$partieDepartmentName .','. $partieLocationName;
					$approvalroutes[$n]['userDesignation'] = $userPosition;
					
					  $routes.='<tr>
						<td width="30%"> 
						  <p>&nbsp;&nbsp;'.$adb->query_result($result,$n,'cf_6790').'.'.$userPosition.' </p>
						</td>
						<td width="40%"> 
						  <p>  </p>
						</td>
					  </tr>
					  <tr>
						<td width="70"> 
						  <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$first_name.' '.$last_name.'</p>
						</td>
						<td width="70"> 
						  <p>&nbsp;&nbsp;'.ucfirst($adb->query_result($result,$n,'cf_6792')).'</p>
						</td>
					  </tr>';
					
						  $status = ucfirst($approvalroutes[$n]['status']);
						
				}
				
				$routes.='</tbody>
				</table>';
		    }
		
		$this->setValue('APPROVALROUTESGROUP', $approvalroutes[0]['name'], ENT_QUOTES, "UTF-8");
		$this->setValue('APPROVALROUTES', $routes);
		$this->setValue('STATUS', $status);
		
		//$this->setValue('aprrovalroutehistoryid', $aprrovalroutehistoryid);	
						
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		
		$pdf_name = "pdf_docs/ms_".$record.".pdf";
		
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		
		header('Location:'.$pdf_name);
		exit;
		  
		  
		  /*
		if ($type == 1) {
		  header('Location:'.$pdf_name);
		  exit;
		} else
		if ($type == 2) {
		  header('Location:'.$pdf_name); 
		  exit;
		}
			*/
		/*//ob_start();
		header('Content-Description: File Transfer');
		header('Content-Type: text/plain; charset=UTF-8');
		header('Content-Disposition: attachment; filename='.$filename);
		header('Content-Transfer-Encoding: binary');		
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filename));
		flush();
		ob_end_flush();
		readfile($filename);
		unlink($filename); // deletes the temporary file
		exit;	*/
		/*
		$response = new Vtiger_Response();
		$response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
		$response->emit();
		*/	
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
			//$replace = utf8_encode($replace);
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
