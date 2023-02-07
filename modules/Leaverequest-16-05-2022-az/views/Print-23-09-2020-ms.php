<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class Leaverequest_Print_View extends Vtiger_Print_View {
  
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

  function checkPermission (Vtiger_Request $request)  {
    return true;
  }

  function process (Vtiger_Request $request)  {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $current_user = Users_Record_Model::getCurrentUserModel();
    $leaverequest_info = Vtiger_Record_Model::getInstanceById($record, 'Leaverequest');
    $userprofile_info = Vtiger_Record_Model::getInstanceById($leaverequest_info->get('cf_3423') , 'UserList');
    $qt_owner_user_info = Users_Record_Model::getInstanceById($leaverequest_info->get('assigned_user_id') , 'Users');
    /*if ($leaverequest_info->get('contact_id') != ''){
    $contact_info = Vtiger_Record_Model::getInstanceById($leaverequest_info->get('contact_id'), 'Contacts');
    $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
    }*/

    // echo "<pre>";
    // print_r($userprofile_info);
    // echo "</pre>";
    // exit;
    $document = $this->loadTemplate('printtemplates/Leaverequest/pdf.html');

    $createdTime = date("Y-m-d", strtotime($leaverequest_info->get('CreatedTime')));
    $modifiedTime = date("Y-m-d", strtotime($leaverequest_info->get('ModifiedTime')));

    $this->setValue('leaverequest_id', $record, ENT_QUOTES, "UTF-8");
    $this->setValue('requester', $userprofile_info->get('name'), ENT_QUOTES, "UTF-8");
    $this->setValue('created_time', $createdTime, ENT_QUOTES, "UTF-8");
    $this->setValue('modified_time', $modifiedTime, ENT_QUOTES, "UTF-8");
    $this->setValue('office', $leaverequest_info->get('cf_3467'), ENT_QUOTES, "UTF-8");
    $this->setValue('type_of_leave', $leaverequest_info->get('cf_3409'), ENT_QUOTES, "UTF-8");
    $this->setValue('department', $leaverequest_info->getDisplayValue('cf_3467'), ENT_QUOTES, "UTF-8");
    $this->setValue('branch', $leaverequest_info->getDisplayValue('cf_4801'), ENT_QUOTES, "UTF-8");
    $this->setValue('from_date', $leaverequest_info->get('cf_3391'), ENT_QUOTES, "UTF-8");
    $this->setValue('till_date', $leaverequest_info->get('cf_3393'), ENT_QUOTES, "UTF-8");
    $date = explode('-', $leaverequest_info->get('cf_5355'));
    $this->setValue('from_time', $date[0], ENT_QUOTES, "UTF-8");

    // if($leaverequest_info->getDisplayValue('cf_3409') == "Annual paid leave") {
    $this->setValue('till_time', $date[1], ENT_QUOTES, "UTF-8");
    // } else {
    //   $this->setValue('till_time', $leaverequest_info->get('cf_4095'), ENT_QUOTES, "UTF-8");
    // }
    
    $this->setValue('mobile_number', $leaverequest_info->get('cf_3401'), ENT_QUOTES, "UTF-8");
    $this->setValue('private_email', $leaverequest_info->get('cf_3403'), ENT_QUOTES, "UTF-8");
    $this->setValue('forwarding_email', $leaverequest_info->get('cf_3405'), ENT_QUOTES, "UTF-8");
    $this->setValue('auto_reply', $leaverequest_info->get('cf_3407'), ENT_QUOTES, "UTF-8");
    $this->setValue('head_approval', $leaverequest_info->get('cf_3411'), ENT_QUOTES, "UTF-8");
    $this->setValue('head_approved_on', $leaverequest_info->get('cf_3413'), ENT_QUOTES, "UTF-8");
    $this->setValue('hr_approval', $leaverequest_info->get('cf_3415'), ENT_QUOTES, "UTF-8");
    $this->setValue('hr_approved_on', $leaverequest_info->get('cf_3417'), ENT_QUOTES, "UTF-8");
    $this->setValue('comments_to_request', $leaverequest_info->get('cf_4677'), ENT_QUOTES, "UTF-8");   

    include('include/mpdf60/mpdf.php');

    $mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*Ð·Ð°Ð´Ð°ÐµÐ¼ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚, Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð¸.Ñ‚.Ð´.*/
    $mpdf->charset_in = 'utf8';

    // $mpdf->list_indent_first_level = 0;
    // $mpdf->SetDefaultFontSize(12);

    $mpdf->list_indent_first_level = 0;
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Leave Request Form, GLOBALINK
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
        </tr>
      </table>');

    $mpdf->SetHTMLFooter('
      <table width="80%" align="center" cellpadding="0" cellspacing="0">
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
    $mpdf->WriteHTML($stylesheet, 1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML, 2);

    $pdf_name = "pdf_docs/leaverequestform_" . $record . ".pdf";
    $mpdf->Output($pdf_name, 'F');
    header('Location:' . $pdf_name);
    exit;
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
  public function setValue ($search, $replace)  {
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
}