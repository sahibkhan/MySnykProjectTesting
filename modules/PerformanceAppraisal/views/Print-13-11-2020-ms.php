mm<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PerformanceAppraisal_Print_View extends Vtiger_Print_View {
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
    
    $this->print_performance_appraisal($request);
    
  }
  
  public function print_performance_appraisal($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $lang = $request->get('lang');
    
    $appraisal_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
	
    $performanceAppraisal_info_detail = Vtiger_Record_Model::getInstanceById($appraisal_id, 'PerformanceAppraisal');
    
    if(isset($lang) && $lang=='rus')
    { $document = $this->loadTemplate('printtemplates/HR/performanceappraisal_ru.html');}
    else{$document = $this->loadTemplate('printtemplates/HR/performanceappraisal.html');}
    
      
    $evaluator_user_info = Users_Record_Model::getInstanceById($performanceAppraisal_info_detail->get('assigned_user_id'), 'Users');

    $userProfile_id = $performanceAppraisal_info_detail->get('cf_6560');
    $user_profile_info = Vtiger_Record_Model::getInstanceById($userProfile_id, 'UserList');
    
    
    $this->setValue('name_of_appraisee',$performanceAppraisal_info_detail->getDisplayValue('cf_6560'));
    $this->setValue('department',$performanceAppraisal_info_detail->getDisplayValue('cf_6564'));
    $this->setValue('office',$performanceAppraisal_info_detail->getDisplayValue('cf_6562'));
    $this->setValue('position',$user_profile_info->getDisplayValue('cf_3341'));
    $this->setValue('date_joined',$user_profile_info->getDisplayValue('cf_3431'));
    $this->setValue('question_1',$performanceAppraisal_info_detail->getDisplayValue('cf_6612'));
    $this->setValue('question_2',$performanceAppraisal_info_detail->getDisplayValue('cf_6614'));


     $adb = PearDatabase::getInstance();
     $result_answer_s = $adb->pquery("SELECT sum(answer_value) as subtotal FROM `appraisal_questions_answer` WHERE appraisal_id=? ",array($appraisal_id));
     $row_answer_s = $adb->fetch_array($result_answer_s);
     $subtotal = $row_answer_s['subtotal'];
     $this->setValue('subtotal',$subtotal);
     $this->setValue('voice_average_score',round($subtotal/12,1));

     $voice_average_score = round($subtotal/12,1);
     //For final Grading based on voice average score
				switch($voice_average_score)
				{
					case $voice_average_score >= '7.8' and $voice_average_score <= '8':
						$average_score_grade = 'Excellent';
						$final_grade = 'A';
					break;
					case $voice_average_score >= '6.8' and $voice_average_score <= '7.7':
						$average_score_grade = 'Outstanding';
						$final_grade = 'A-';
						break;
					case $voice_average_score >= '4.8' and $voice_average_score <= '6.7':
						$average_score_grade = 'Good';
						if($voice_average_score >='5.8' and $voice_average_score <='6.7')
						{
							$final_grade = 'B+';
						}
						elseif($voice_average_score >='4.8' and $voice_average_score <='5.7')
						{
							$final_grade = 'B';
						}
						break;
					case $voice_average_score >= '3.8' and $voice_average_score <= '4.7':
						$average_score_grade = 'Average';
						$final_grade = 'B-';
						break;	
					case $voice_average_score >= '2.8' and $voice_average_score <= '3.7':
						$average_score_grade = 'Improvement Needed';
						if($voice_average_score >='2.8' and $voice_average_score <='3.7')
						{
							$final_grade = 'C+';
						}
						elseif($voice_average_score >='1.8' and $voice_average_score <='2.7')
						{
							$final_grade = 'C';
						}
						break;	
					case $voice_average_score >= '1' and $voice_average_score <= '1.7':
						$average_score_grade = 'Poor';
						$final_grade = 'D';
						break;	
					default:
						$average_score_grade ='';
						$final_grade ='';
				}
        $this->setValue('average_score_grade',$average_score_grade);
        $this->setValue('final_grade',$final_grade);
    

    //Appraisal Parent Category
   
		$result_parent_cat = $adb->pquery("SELECT cf_6528id, cf_6528 FROM `vtiger_cf_6528` ORDER BY `vtiger_cf_6528`.`sortorderid` ASC ", array());
		for($jj=0; $jj< $adb->num_rows($result_parent_cat); $jj++ ) {
				$row_parent_cat = $adb->fetch_row($result_parent_cat,$jj);
				$parent_cat_id = $row_parent_cat['cf_6528id'];
				$parent_cat_name = $row_parent_cat['cf_6528'];
				
				$final_appraisal_arr[$parent_cat_id] = array('parent_cat' => $parent_cat_name);

				$result_questions = $adb->pquery("SELECT * FROM `vtiger_appraisalquestionscf` WHERE cf_6528=? 
												  ORDER BY vtiger_appraisalquestionscf.appraisalquestionsid ASC",array($parent_cat_name));
				for($qq=0; $qq< $adb->num_rows($result_questions); $qq++ ) {
					$row_question = $adb->fetch_row($result_questions,$qq);
					$question_id = $row_question['appraisalquestionsid'];	
					$question_name_eng = $row_question['cf_6532'];
					$question_name_rus = $row_question['cf_6534'];
					$question_sub_cat_id = $row_question['cf_6530'];

					$result_cat = $adb->pquery("SELECT * FROM `vtiger_appraisalcategoriescf` WHERE appraisalcategoriesid=? ",array($question_sub_cat_id));
					$row_sub_cat = $adb->fetch_array($result_cat);
					$sub_cat_eng = $row_sub_cat['cf_6520'];
          $sub_cat_rus = $row_sub_cat['cf_6522'];
          
          $result_answer = $adb->pquery("SELECT * FROM `appraisal_questions_answer` WHERE appraisal_id=? AND question_id=? ",array($appraisal_id, $question_id));
          $row_answer = $adb->fetch_array($result_answer);
          $question_answer_value = $row_answer['answer_value'];
					
					$final_appraisal_arr[$parent_cat_id]['cat_question'][] = array('question_sub_cat_id' => $question_sub_cat_id, 
                                                                         'sub_cat_eng' => $sub_cat_eng,
                                                                         'sub_cat_rus' => $sub_cat_rus,
                                                                         'question_id' => $question_id, 
                                                                         'question_name_eng' => $question_name_eng,
                                                                         'question_name_rus' => $question_name_rus,
                                                                         'question_answer_value' => $question_answer_value
                                                                        );
					
					$result_level = $adb->pquery("SELECT * FROM `vtiger_perfomancelevelcf` WHERE cf_6550=? 
													  ",array($question_sub_cat_id));
					for($mm=0; $mm< $adb->num_rows($result_level); $mm++ ) {
						$row_level = $adb->fetch_row($result_level,$mm);
						$perfomancelevelid = $row_level['perfomancelevelid'];
						$level = $row_level['cf_6548'];
						$level_ans_eng = $row_level['cf_6552'];
						$level_ans_rus = $row_level['cf_6554'];
						
						$level_key =str_replace(' ','_', $level);
						$final_appraisal_arr[$parent_cat_id]['cat_question'][$qq]['level'][$level_key] = array('level' => $level, 
																									'level_ans_eng' => $level_ans_eng,
																									'level_ans_rus' => $level_ans_rus
																									);

					}								  
																					  
				}
    }
    
    $appraisal_html = '';
    foreach($final_appraisal_arr as $key => $appraisal_cat)
    {
     
       $appraisal_html .='<tr><td  rowspan="2" ><b>'.$appraisal_cat['parent_cat'].'</b></td>';
       foreach($appraisal_cat['cat_question'] as $key_q => $question)
       {
        $appraisal_html .='<td style="vertical-align: top;"><b>'.$question['sub_cat_'.$lang.''].'</b><br>
                            - '.$question['question_name_'.$lang.''].'
                            </td>';
        $APPRAISAL_LEVEL =$question['level'];                    
        $appraisal_html .='<td style="vertical-align: top;text-align: center;"><input type="checkbox" '.(($question['question_answer_value']==8) ? 'checked="checked"' : '').' ><br>'.$APPRAISAL_LEVEL['Excellent']['level_ans_'.$lang.''].'</td> 
				                   <td style="vertical-align: top;text-align: center;"><input type="checkbox" '.(($question['question_answer_value']==7) ? 'checked="checked"' : '').'  ><br>'.$APPRAISAL_LEVEL['Outstanding']['level_ans_'.$lang.''].'</td> 
				                   <td style="vertical-align: top;text-align: center;"><input type="checkbox"  '.(($question['question_answer_value']==6) ? 'checked="checked"' : '').' >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" '.(($question['question_answer_value']==5) ? 'checked="checked"' : '').'  ><br>'.$APPRAISAL_LEVEL['Good']['level_ans_'.$lang.''].'</td>  
				                   <td style="vertical-align: top;text-align: center;"><input type="checkbox" '.(($question['question_answer_value']==4) ? 'checked="checked"' : '').'  ><br>'.$APPRAISAL_LEVEL['Average']['level_ans_'.$lang.''].'</td>
				                   <td style="vertical-align: top;text-align: center;"><input type="checkbox"  '.(($question['question_answer_value']==3) ? 'checked="checked"' : '').' >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" '.(($question['question_answer_value']==2) ? 'checked="checked"' : '').' ><br>'.$APPRAISAL_LEVEL['Improvement_Needed']['level_ans_'.$lang.''].'</td> 
				                   <td style="vertical-align: top;text-align: center;" ><input type="checkbox"  '.(($question['question_answer_value']==1) ? 'checked="checked"' : '').' ><br>'.$APPRAISAL_LEVEL['Poor']['level_ans_'.$lang.''].'<br>
                           </td>';
        
        if($key_q==0)
        {
           $appraisal_html .=' </tr><tr> '; 
        }
       }
       $appraisal_html .='</tr>';
    }
  
     $this->setValue('appraisal_html',$appraisal_html);


    
  include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
	
	
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 
    
    $mpdf->SetHTMLHeader('
    <table width="100%" cellpadding="0" cellspacing="0">       
    <tr>
      <td width="50%" align="left"><h5 align="left" style="font-family:Verdana, Geneva, sans-serif;">EMPLOYEE PERFORMANCE APPRAISAL REPORT</h5></td>
      <td width="50%" align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30" /></td>
    </tr>
    <tr>
    <td colspan="2" align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold; padding-right:30px; ">
      <u>CONFIDENTIAL</u></td>
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
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
        
    $pdf_name = 'pdf_docs/performanceappraisal.pdf';
    
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