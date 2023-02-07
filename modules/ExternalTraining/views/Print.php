<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class ExternalTraining_Print_View extends Vtiger_Print_View {
	
	/**
	 * Temporary Filename
	 *
	 * @var string
	 */
	private $_tempFileName;
	function __construct()
	{
		parent::__construct();
		ob_start();
	}

	function checkPermission (Vtiger_Request $request)	{
		return true;
	}

	function process (Vtiger_Request $request)	{
		// ini_set('display_errors', 1);
		// ini_set('display_startup_errors', 1);
		// error_reporting(E_ALL);

		$moduleName = $request->getModule();
		$record = $request->get('record');
		$training_info = Vtiger_Record_Model::getInstanceById($record, 'ExternalTraining');
		$training_attachment = $training_info->getImageDetails(); //getting externalTraining attachment

		$participants = $this->getInviteeList($record);
		$trainer = $training_info->getDisplayValue('cf_7370');


		$trainerInfoDiv = '';
		if ($training_info->get('cf_7368') == 'Internal')
		{
			$trainersInfo = $this->getRelatedTrainers($record);
			
			$trainerInfoDiv .= '<tbody> <tr>
					                <td width="153" valign="top"> <p> <strong>Participants</strong> </p> </td>
					                <td colspan="3" width="489" valign="top"> <p>'.$participants.'</p> </td>
					                </tr>
					            <tr style="background-color: #cacaca;">
					                <td width="153" valign="top"> <p> <strong></strong> </p> </td>
					                <td width="166" valign="top"> <p> <strong>Trainers</strong> </p> </td>
					                <td width="166" valign="top"> <p> <strong>Date</strong> </p> </td>
					                <td width="157" valign="top"> <p> <strong>Time</strong> </p> </td> </tr>';
					        for ($t = 0; $t < count($trainersInfo); $t++) {
					        $trainerInfoDiv .= '<tr>
					                <td width="153" valign="top"> </td>
					                <td width="166" valign="top">
					                    <p>'.$trainersInfo[$t]['trainerName'].'</p>
					                </td>
					                <td width="166" valign="top">
					                    <p>'.date('d.m.Y', strtotime($trainersInfo[$t]['trainingDate'])) .'</p>
					                </td>
					                <td width="157" valign="top">
					                    <p>'.$trainersInfo[$t]['timeSlot'].'</p>
					                </td>
					            </tr>';
					        }
					    $trainerInfoDiv .= '</tbody>';
		}
		else if($training_info->get('cf_7368') == 'External')
		{
			$training_attachment = $training_info->getImageDetails(); //getting externalTraining attachment
			$training_info->set('trainingAgreement', $training_attachment[0]['path'].'_'.$training_attachment[0]['name']); //set picture in employee object
			$trainerInfoDiv .= '<tr style="background-color: #cacaca;">
				                <td width="320" valign="top">
				                    <p> <strong>Participants</strong> </p>
				                </td>
				                <td width="328" valign="top">
				                    <p> <strong>Training Provider</strong> </p>
				                </td>
				            </tr>
				            <tr>
				                <td width="320" valign="top">
				                    <p>'.$training_info->getDisplayValue('participants').'</p>
				                </td>
				                <td width="328" valign="top">
				                    <p>'.$trainer.'</p>
				                </td>
				            </tr>';
				            // exit;
		}

		$document = $this->loadTemplate('printtemplates/ExternalTraining/pdf.html');
		$request_date = date("Y-m-d", strtotime($employeeArr['CreatedTime']));
		$request_edit_date = date("Y-m-d", strtotime($employeeArr['ModifiedTime']));

		// feedback user info
		if ($this->getRequestedBy($training_info->get('name')) <> ''){
			$trainingName = $this->getRequestedBy($training_info->get('name'));
		} else $trainingName = '';

		$this->setValue('training_name', $trainingName, ENT_QUOTES, "UTF-8");
		$this->setValue('training_location', $training_info->getDisplayValue('cf_7356'), ENT_QUOTES, "UTF-8");
		$this->setValue('training_duration', $training_info->get('cf_7362'), ENT_QUOTES, "UTF-8");
		$this->setValue('training_startDate', date("d.m.Y", strtotime($training_info->get('cf_7364'))), ENT_QUOTES, "UTF-8");

		$this->setValue('training_ednDate', date("d.m.Y", strtotime($training_info->get('cf_7366'))), ENT_QUOTES, "UTF-8");
		$this->setValue('training_type', $training_info->get('cf_7368'), ENT_QUOTES, "UTF-8");
		$this->setValue('training_aim', $training_info->getDisplayValue('cf_7398'), ENT_QUOTES, "UTF-8");
		$this->setValue('training_participant', $participants, ENT_QUOTES, "UTF-8");
		
		// $this->setValue('training_otherParticipant', $training_info->get('cf_7378'), ENT_QUOTES, "UTF-8");
		$this->setValue('trainer_Detail', $trainerInfoDiv, ENT_QUOTES, "UTF-8");
		$this->setValue('training_sum', $training_info->get('cf_7372').' '.$training_info->getDisplayValue('cf_7442'), ENT_QUOTES, "UTF-8");
		$this->setValue('training_total', $training_info->get('cf_7374').' '.$training_info->getDisplayValue('cf_7442'), ENT_QUOTES, "UTF-8");

		$this->setValue('training_qty', $training_info->get('cf_7376'), ENT_QUOTES, "UTF-8");
		$this->setValue('training_terms', $training_info->get('cf_7388'), ENT_QUOTES, "UTF-8");

		// Line Manager Approval
		$this->setValue('approvedBy_LM', $training_info->getDisplayValue('cf_7380'), ENT_QUOTES, "UTF-8");
		$this->setValue('approvedOn_LM', $training_info->get('cf_7390'), ENT_QUOTES, "UTF-8");
		// HR Approval
		$this->setValue('approvedBy_HR', $training_info->getDisplayValue('cf_7382'), ENT_QUOTES, "UTF-8");
		$this->setValue('approvedOn_HR', $training_info->get('cf_7392'), ENT_QUOTES, "UTF-8");
		// CFO Approval
		$this->setValue('approvedBy_CFO', $training_info->getDisplayValue('cf_7384'), ENT_QUOTES, "UTF-8");
		$this->setValue('approvedOn_CFO', $training_info->get('cf_7394'), ENT_QUOTES, "UTF-8");
		// CEO Approval
		$this->setValue('approvedBy_CEO', $training_info->getDisplayValue('cf_7386'), ENT_QUOTES, "UTF-8");
		$this->setValue('approvedOn_CEO', $training_info->get('cf_7396'), ENT_QUOTES, "UTF-8");


		// user Work detail
		$this->setValue('CV_work', $workDiv, ENT_QUOTES, "UTF-8");
		$this->setValue('CV_totalExp', $calculatedWorkExp, ENT_QUOTES, "UTF-8");
		// user Education detail
		$this->setValue('CV_edu', $eduDiv, ENT_QUOTES, "UTF-8");
		// user Lang detail
		$this->setValue('CV_lang', $langDiv, ENT_QUOTES, "UTF-8");
		// user Skills detail
		$this->setValue('CV_lang', $langDiv, ENT_QUOTES, "UTF-8");
		

		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', 'sans-serif', 10, 10, 7, 7, 10, 10);
		$mpdf->charset_in = 'utf8';

		// $mpdf->list_indent_first_level = 0;
		// $mpdf->SetDefaultFontSize(12);

		$mpdf->list_indent_first_level = 0;

		// $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$stylesheet = file_get_contents('libraries/bootstrap/css/bootstrap.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML);
		$pdf_name = "pdf_docs/training_" . $record . ".pdf";
		$mpdf->Output($pdf_name, 'F');

		// header('Location:http://mb.globalink.net/vt60/'.$pdf_name);

		header('Location:' . $pdf_name);
		exit;
	}

	function getInviteeList($recordId){

		global $adb;
		$inviteUserList = '';
		$result_g = $adb->pquery("
		Select CONCAT(vtiger_users.first_name,' ', vtiger_users.last_name) as name
		FROM vtiger_invitees
		LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_invitees.inviteeid
		WHERE vtiger_invitees.activityid = ?", array($recordId));
		$noofrow = $adb->num_rows($result_g);
		for($i=0; $i<$noofrow ; $i++) $inviteUserList .= $adb->query_result($result_g, $i, 'name').', ';
		return $inviteUserList;
		
	}

	public function template ($strFilename) {
		$path = dirname($strFilename);

		// $this->_tempFileName = $path.time().'.docx';
		// $this->_tempFileName = $path.'/'.time().'.txt';

		$this->_tempFileName = $strFilename;

		// copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File

		$this->_documentXML = file_get_contents($this->_tempFileName);
	}

/**
 * Set a Template value
 *
 * @param mixed $search
 * @param mixed $replace
 */
	public function setValue ($search, $replace)	{
		if (substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
			$search = '${' . $search . '}';
		}

		// $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");

		if (!is_array($replace)) {

			// $replace = utf8_encode($replace);

			$replace = iconv('utf-8', 'utf-8', $replace);
		}

		$this->_documentXML = str_replace($search, $replace, $this->_documentXML);
	}

	/**
	 * Save Template
	 *
	 * @param string $strFilename
	 */
	public function save ($strFilename) {
		if (file_exists($strFilename)) {
			unlink($strFilename);
		}

		// $this->_objZip->extractTo('fleet.txt', $this->_documentXML);

		file_put_contents($this->_tempFileName, $this->_documentXML);

		// Close zip file

		/* if($this->_objZip->close() === false) {
		throw new Exception('Could not close zip file.');
		}*/
		rename($this->_tempFileName, $strFilename);
	}

	public function loadTemplate ($strFilename) {
		if (file_exists($strFilename)) {
			$template = $this->template($strFilename);
			return $template;
		} else {
			trigger_error('Template file ' . $strFilename . ' not found.', E_ERROR);
		}
	}

	// get trainers info
	function getRelatedTrainers($sourcerecordid)
	{
		// $trainer_info = Vtiger_Record_Model::getInstanceById($sourcerecordid, 'Trainer');
		$db = PearDatabase::getInstance();
		//$id = $request->get('record');	
			$queryTrainers = "SELECT *
			FROM vtiger_trainercf
			INNER JOIN vtiger_trainer ON vtiger_trainer.trainerid = vtiger_trainercf.trainerid
			INNER JOIN vtiger_crmentity ON vtiger_trainer.trainerid = vtiger_crmentity.crmid
			INNER JOIN vtiger_crmentityrel ON vtiger_trainer.trainerid = vtiger_crmentityrel.relcrmid
			WHERE
				vtiger_crmentityrel.crmid = '$sourcerecordid' 
				AND vtiger_crmentity.deleted = 0 
			ORDER BY
				vtiger_trainercf.trainerid ASC;"; 
        	$resultTrainers = $db->pquery($queryTrainers, array());
        	// echo '<pre>';
        	// print_r($resultTrainers);
        	// exit;

				for($t=0;$t<$db->num_rows($resultTrainers);$t++){
					$trainerDetailList[$t]['relatedTrainerInfoId'] = $db->query_result($resultTrainers,$t,'trainerid');

					$trainer_model = Vtiger_Record_Model::getInstanceById($trainerDetailList[$t]['relatedTrainerInfoId'], 'Trainer'); 
					$trainerDetailList[$t]['trainerName'] = $trainer_model->getDisplayValue('name'); 
					
					$trainerDetailList[$t]['trainingDate'] = $db->query_result($resultTrainers,$t,'cf_7402');
					$trainerDetailList[$t]['timeSlot'] = $db->query_result($resultTrainers,$t,'cf_7404');
					$trainerDetailList[$t]['trainingCompleted'] = $db->query_result($resultTrainers,$t,'cf_7406');
					$trainerDetailList[$t]['trainerComments'] = $db->query_result($resultTrainers,$t,'cf_7408');
				}
			return $trainerDetailList;
	}
	
	public function getVendorName($vendor_id){
   		$user_names = '';
   		$adb = PearDatabase::getInstance();
   		
   		$Glk_vendorList_query = "SELECT vtiger_account.accountname FROM vtiger_account WHERE vtiger_account.account_type = 'Vendor' AND vtiger_account.accountid = ?";
   		$result = $adb->pquery($Glk_vendorList_query, array( $vendor_id ));
		$vendor_name = $adb->query_result($result, 0, 'accountname');

	   	return $vendor_name;
	}

	public function getRequestedBy($id){
   		$adb = PearDatabase::getInstance();
   		
   		$sqlUser = "SELECT name FROM `vtiger_userlist` WHERE `userlistid` = ?";
   		$result = $adb->pquery($sqlUser, array( $id ));
			$name = $adb->query_result($result, 0, 'name');
	   	return $name;
	}

}