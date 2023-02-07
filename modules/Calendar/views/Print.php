<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_Print_View extends Vtiger_Print_View {
	
	
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
		$adb = PearDatabase::getInstance();	
		ini_set('display_errors', 1);
		error_reporting(E_ALL);

		$moduleName = $request->getModule();
		$record = $request->get('record');

		$vtigerRecordInstance = new Vtiger_Record_Model();
		$inviteList = $vtigerRecordInstance->getRecordInvitees($record);
/* 		echo "<pre>";
		print_r($ins);
		exit;
 */
		$current_user = Users_Record_Model::getCurrentUserModel();
		$calendar_info = Vtiger_Record_Model::getInstanceById($record, 'Calendar');		
		$creator_user_info = Users_Record_Model::getInstanceById($calendar_info->get('assigned_user_id'), 'Users');

		$inviteUsers = '';
/* 		$q_invitees = $adb->pquery("SELECT vtiger_users.first_name, vtiger_users.last_name
																FROM vtiger_users
																LEFT JOIN vtiger_invitees ON  vtiger_invitees.inviteeid = vtiger_users.id
																WHERE vtiger_invitees.activityid = ?", array($record));
		$num_rows = $adb->num_rows($q_invitees);
		for($i=0; $i<$num_rows; $i++) {
			$inviteUsers .= $adb->query_result($q_invitees, $i,'first_name').' '. $adb->query_result($q_invitees, $i,'last_name').', ';
		} */

		foreach ($inviteList as $inviteUser){
			$inviteUsers .= $inviteUser['nameWithLocationAndDepartment'].', ';
		}



		$contactName = '';
		if ($calendar_info->get('contact_id') > 0){
			$contact_info = Vtiger_Record_Model::getInstanceById($calendar_info->get('contact_id'), 'Contacts');
			$contactName = $contact_info->get('firstname').' '.$contact_info->get('lastname');
			$contactPosition = $contact_info->get('title');
			$contactTel = $contact_info->get('phone');
			$contactMobile = $contact_info->get('mobile');
			$contactEmail = $contact_info->get('email');
		}


		// Field account
		$account_id = $calendar_info->get('parent_id');
		if ($account_id != 0){
	 	  $account_info = Users_Record_Model::getInstanceById($account_id, 'Accounts');		
		  $account = $account_info->get('cf_2395');
		  $bill_street = $account_info->get('bill_street');
		  $website = $account_info->get('website');
		}
		else{
			$account ='';
		} 


		// Gathering info

		$station = $creator_user_info->get('address_country').'/'.$creator_user_info->get('address_city');
		$eventType = $calendar_info->get('activitytype');


		$calendar_tpl_name = 'pdf';		
		$document = $this->loadTemplate('printtemplates/Calendar/'.$calendar_tpl_name.'.html');
				
		$this->setValue('to', 'Mr. Siddique Khan');		
		$this->setValue('station', $station);

		$this->setValue('from',htmlentities($creator_user_info->get('first_name').' '.$creator_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		// $this->setValue('attn',htmlentities($attn, ENT_QUOTES, "UTF-8"));
		// $user_city = trim($r_users['address_city']);
		
		$this->setValue('invitees', $inviteUsers);
		$this->setValue('eventType', $eventType);
		
		// visitSalesReport
		$visitSalesReport = '<table border=0>';
			$visitSalesReport .= '<tr><td colspan="2"> <h3> VISIT/SALES REPORT </h3> </td> <tr>';
			$visitSalesReport .= '<tr><td> Company: </td> <td>'.$account.'</td><tr>';
			$visitSalesReport .= '<tr><td> Address: </td> <td>'.$bill_street.'</td><tr>';		
			$visitSalesReport .= '<tr><td> Web: </td> <td>'.$website.'</td><tr>';
		$visitSalesReport .= '</table>';
		$this->setValue('visitSalesReport', $visitSalesReport);




		// contactPerson
		$contactPerson = '<table border=0>';
		$contactPerson .= '<tr><td colspan="2"> <h3> CONTACT PERSON </h3> </td> <tr>';
			$contactPerson .= '<tr><td> Name: </td> <td>'.$contactName.'</td><tr>';		
			$contactPerson .= '<tr><td> Position: </td> <td>'.$contactPosition.'</td><tr>';
			$contactPerson .= '<tr><td> Telephone: </td> <td>'.$contactTel.'</td><tr>';
			$contactPerson .= '<tr><td> Mobile Phone: </td> <td>'.$contactMobile.'</td><tr>';
			$contactPerson .= '<tr><td> Email: </td> <td>'.$contactEmail.'</td><tr>';
		$contactPerson .= '</table>';

		$this->setValue('contactPerson', $contactPerson);	


		// eventInformation
		$eventInformation = '<table border=0>';
		$eventInformation .= '<tr><td colspan="2"> <h3> EVENT INFORMATION </h3>		</td> <tr>';
			$eventInformation .= '<tr><td> Event Type: </td> <td>'.$eventType.'</td><tr>';		
		$eventInformation .= '</table>';
		$this->setValue('eventInformation', $eventInformation);	

	
/* 
		$sql_branch_tel = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b
									 where b.city = '".$creator_user_info->get('address_city')."'", array());
	    $branch_tel = $adb->query_result($sql_branch_tel, 0, 'tel');
		$this->setValue('tel',$branch_tel); */
		
		$subject = $calendar_info->get('subject');	
		$subject = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $subject)));	
			
		$this->setValue('date',date('d.m.Y', strtotime($calendar_info->get('createdtime'))));
		if ($email == '') $email = $creator_user_info->get('email1'); 
		$this->setValue('email',$email, ENT_QUOTES, "UTF-8");
		$this->setValue('ref',htmlentities($record, ENT_QUOTES, "UTF-8"));
		$this->setValue('dep',htmlentities($creator_user_info->get('department'), ENT_QUOTES, "UTF-8"));		
		$this->setValue('subject',$subject);
	  			
	
	$description = $calendar_info->get('description');
	$s = '';
	if ($description != ''){
		// $s = html_entity_decode($description);		
		$desc = str_replace("\n","<br />",$description); // заменяем \n на бырки
		$s = "<div class=remarks-block>
			<h3> DESCRIPTION:</h3>
			<p> $desc </p>
	  </div>";
	 
	  		
	}
	$this->setValue('description', $s);

	// Get Action plans

	

											// *** Блок Action Plan  *** 
					
			$stroka = $calendar_info->get('cf_745');
			$action_plan = $calendar_info->get('cf_747');
			$deadline = $calendar_info->get('cf_749');
							
			
			if ($stroka != ''){
				
				$actionPlan .= '<h3> ACTION PLAN </h3> ';
			
			$rec_count = 0;
			$mas_assigned = array();
			$mas_action_plan = array();
			$mas_deadline = array();
			
			$str_rows = "";
		
						// Поиск } в строке поле Assigned to 
				for($c = 0; $c < strlen($stroka); $c++){
				if ($stroka[$c] == "}"){
					$rec_count++;
					$mas_assigned[$rec_count] = $str_rows;
					echo $str_rows.'<br>';
					$str_rows = ''; 
				} else $str_rows = $str_rows . $stroka[$c];
				}
								
/* 		print_r($mas_assigned);
		exit; */
				
				$str_rows = '';
				$sign_count = 0; 
						
						// Поиск } в строке поле Action Plan
						
						
				for($c = 0; $c < strlen($action_plan); $c++){
				if ($action_plan[$c] == '}'){
					$sign_count++;
					$mas_action_plan[$sign_count] = $str_rows;
					$str_rows = ''; 
				} else $str_rows = $str_rows . $action_plan[$c];
				}
			
				
				$str_rows = '';
				$sign_count = 0; 
						// Поиск } в строке поле Deadline
						
				for($c = 0; $c < strlen($deadline); $c++){
				if ($deadline[$c] == '}'){
					$sign_count++;
					$mas_deadline[$sign_count] = $str_rows;
					$str_rows = ''; 
				} else $str_rows = $str_rows . $deadline[$c];
				}

					// В цикле проверяем количество Action Plan 
			for($z=1; $z<=$rec_count; $z++){
						//  Assigned users
					$main_str = $mas_assigned[$z];	
					$one_line = '';
					for($c = 0; $c < strlen($main_str); $c++){
					if ($main_str[$c] == '|'){
						$sign_count++;
						$main_str[$sign_count] = $str_rows;
						// Собираем только пользователей
						$one_line = $one_line . $str_rows.', ';
							$str_rows = ''; 
					} else $str_rows = $str_rows . $main_str[$c];
					}
				$two_line = $mas_deadline[$z];		
					$test .= $one_line .  ' - ' . $mas_action_plan[$z] . ' - ' . $two_line.'<br/><br/>';
			}
		
		
			$actionPlan .= $test.' <br/>';
		
			}
			

			$this->setValue('actionPlan', $actionPlan);
	


					// Signature
					
/* 	$user_city = $creator_user_info->get('address_city');
    $adb = PearDatabase::getInstance();							
    $sql_branch = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b 
									where b.city = '".$user_city."'", array());
    $branch_tel = $adb->query_result($sql_branch, 0, 'tel'); 
					
					
	$creator_name = $creator_user_info->get('first_name').' '.$creator_user_info->get('last_name');
	$creator_title =  $creator_user_info->get('title');
	$email = $creator_user_info->get('email1');
	
	if ($qt_type == 1){
	  $quote_html_footer = '
	  <div class="best-regards-block">
	  <span>----</span>
	  <p>Thank you and Best Wishes,<br />
	  '.$creator_name.'<br />
	  Price Coordinator <br />
	  </div>';

	} else if ($qt_type == 2){
	  $quote_html_footer = '
	  <div class="best-regards-block">
	  <span>----</span>
	  <p>Thank you and Best Wishes,<br />
	  '.$creator_name.'<br />
	  '.$creator_title.'<br />
	  Globalink Logistics
	  <p>
	  Tel:'.$branch_tel.'<br />
	  E-mail: '.$email.'<br />
	  Web Site: www.globalinklogistics.com</p>
	  </div>
	';
	}

	
	
	 
	$this->setValue('signature',$quote_html_footer);	
	 */
	
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/
		//$mpdf->WriteHTML('Hello World');

		$subject = $calendar_info->get('subject');	
		$subject = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $subject)));	
		
		if ($subject != ''){
		  $subname = $to . "(".$subject.")";
		} else $subname = $to;
		
		
		$subname = preg_replace("~/~","",$subname);
		$subname = str_replace("&#039;", "'", $subname);
		$subname = str_replace("«", '"', $subname);
		$subname = str_replace("»", '"', $subname);
		$subname = html_entity_decode($subname);
		$pdf_name = "pdf_docs/VR for ".$subname.".pdf";		
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
