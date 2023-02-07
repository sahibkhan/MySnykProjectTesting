<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Potentials_Print_View extends Vtiger_Print_View {
	
	
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
		//$adb->setDebug(true);
		include 'libraries/tcpdf/tcpdf.php';

		$moduleName = $request->getModule();
		$record = $request->get('record');
			
		$inquiry_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();		
			
		$inquiry_info = Vtiger_Record_Model::getInstanceById($inquiry_id, 'Potentials');
				
		$inquiry_owner_user_info = Users_Record_Model::getInstanceById($inquiry_info->get('assigned_user_id'), 'Users');

		/*if ($inquiry_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($inquiry_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}*/
		$smcreatorid = $inquiry_info->get('cf_755');
		$inquiry_creator_user_info = Users_Record_Model::getInstanceById($smcreatorid, 'Users');



		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		// убираем на всякий случай шапку и футер документа
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false); 
		$pdf->SetMargins(20, 25, 25); // устанавливаем отступы (20 мм - слева, 25 мм - сверху, 25 мм - справа)
		$pdf->AddPage(); // создаем первую страницу, на которой будет содержимое
		//$pdf->AddPage('P', 'A4');
		//$pdf->setPage(1, true);

		$pdf->Image('include/calendar_logo.jpg',75,5,0,0);
		
		$pdf->SetFontSize(10);  
		$pdf->SetFont('TimesNewRoman', '', 11, '', 'false');
		
		// Установка координат x y откуда будут рисоваться ячейки
		$pos_x = 35;
		$pos_y = 25;
		
		$x = 35;   // Ширина ячейки
		$y = 7;   // Высота ячейки
		
		$shag = 5;
		$shag_2 = 10;

		$user_time_zone = $inquiry_creator_user_info->get('time_zone'); 
		$user_city = trim($inquiry_creator_user_info->get('address_city'));

		$branch_tel = get_branch_details($user_city,'tel');
		$branch_fax = get_branch_details($user_city,'fax');

		// Convert user time zone To City, Country 
		$s_customtimezone = $adb->pquery("Select * From `vtiger_customtimezone` where `name`='$user_time_zone'");
		$r_customtimezone = $adb->fetch_array($s_customtimezone);
		$creator_station = $r_customtimezone['description'];

		$pdf->SetFillColor(250,250,250); // Цвет заливки ячеек
	 
		// Header
		
		$pdf->SETXY($pos_x,$pos_y);
		$pdf->CELL(8,$y,'Tel:',0,0,'L',0);	
		$pdf->CELL($x,$y,$branch_tel,0,0,'L',0);
						
		$pdf->CELL(8,$y,'Fax:',0,0,'L',0);
		$pdf->CELL($x,$y,$branch_fax,0,0,'L',0);
		
		$pdf->CELL(8,$y,'E:',0,0,'L',0);
		$pdf->CELL(25,$y,$inquiry_creator_user_info->get('email1'),0,0,'L',0);

		// Header

		$pos_x = 10;
		$pos_y = 32;
		
		$x = 35;   // Ширина ячейки
		$y = 5;   // Высота ячейки
		
		//$pos_y = $pos_y + $shag;  // Увеличиваем расстояние между ячейками по y	
		
		$pdf->SETXY($pos_x,$pos_y);
		$pdf->CELL($x,$y,'Station:',1,0,'L',0);	
		$pdf->CELL($x+25,$y,$creator_station,1,0,'L',0);
						
		$pdf->CELL($x,$y,'Created By:',1,0,'L',0);
		$pdf->CELL($x+25,$y,$inquiry_creator_user_info->get('first_name') . ' ' . $inquiry_creator_user_info->get('last_name'),1,0,'L',0);
	
		$pos_y = $pos_y + $shag;  // Увеличиваем расстояние между ячейками по y
		
		$pdf->SETXY($pos_x,$pos_y);
		$pdf->CELL($x,$y,'Department:',1,0,'L',0);	
		$pdf->CELL($x+120,$y,$inquiry_creator_user_info->get('department'),1,0,'L',0);

		$pos_y = $pos_y + $shag;  // Увеличиваем расстояние между ячейками по y
		// Отделение даты от времени
		$parent = $inquiry_info->get('createdtime');
		$timestamp = strtotime($parent);
		$child1 = date('d.m.y', $timestamp); // d.m.YYYY
		
		$pdf->SETXY($pos_x,$pos_y);
		$pdf->CELL($x,$y,'Date:',1,0,'L',0);	
		$pdf->CELL($x+25,$y,$child1,1,0,'L',0);
						
		$pdf->CELL($x,$y,'Ref.:',1,0,'L',0);
		$pdf->CELL($x+25,$y,'Inquiry Ref.: GL/INQ - '.$record,1,0,'L',0);
			
		$pos_y = $pos_y + $shag;  // Увеличиваем расстояние между ячейками по y	
		$pdf->SETXY($pos_x,$pos_y);
		$pdf->CELL($x,$y+5,'Subject:',1,0,'L',0);	

	
		$pdf->SetLeftMargin(10);
		$inquiry_from_ = "";
		$customer_id = $inquiry_info->get('related_to');
		$customer_info = Vtiger_Record_Model::getInstanceById($customer_id, 'Accounts');
		$inquiry_from_ = $customer_info->get('accountname');
		if ($customer_info->get('cf_743') != '')   $inquiry_from_ .= ' Phone: '  . $customer_info->get('cf_743');
		if ($customer_info->get('cf_741') != '')   $inquiry_from_ .= ' Mobile: ' . $customer_info->get('cf_741');
		if ($customer_info->get('cf_737') != '')   $inquiry_from_ .= ' E-mail: ' . $customer_info->get('cf_737');
		
		$pdf->writeHTMLCell(155,10,$pos_x+35,$pos_y,'Inquiry From ' . $inquiry_from_,1);
		
		$assigned_details = '';		
		
		$assgined = $inquiry_info->get('cf_755');
		if ($assgined != ''){
			$assigned_details .= '<br/><br/><br/><table border="0.1em" style="padding:2px;">';
			$assigned_details .= '<tr style="border:none;"><td width="99px">Assigned To:</td>';
		
			// Get Assgined Persons
			$s_res = "";
			$assigned_users = "";
			for ($i=0;$i<=strlen($assgined);$i++){
			if ($assgined[$i] == '|'){
								//  Select assigned
				$s_res = trim($s_res);
				$res_users = $adb->pquery("Select * From `vtiger_users` where `user_name` = '$s_res' ");
				$row_user = $adb->fetch_array($res_users);
				$assigned_users .= $row_user['first_name'] . ' ' . $row_user['last_name'].', ';
				$s_res = "";
			} else if ($assgined[$i] != '') $s_res .= $assgined[$i];
		}

		$assigned_details .= '<td width="440px">'.$assigned_users.'</td></tr>';
		$assigned_details .= '</table>';
		}


		// Get Recipients 
		$recipients_details = '';
	 	$s_rec = $inquiry_info->get('cf_757');
		
		$bufer = '';
		if ($s_rec != ''){
		  if ($assigned_details == '') $bufer = '<br/><br/><br/>';
		  $recipients_details .= $bufer.'<table border="0.1em" style="padding:2px;">';
		  $recipients_details .= '<tr style="border:none;"><td width="99px">Recipients:</td>';
		
			$s_res = "";
			$recipients_login = "";
			$invited_users = "";
			for ($i=0;$i<=strlen($s_rec);$i++){
			  if ($s_rec[$i] == '|'){
				$recipients_login .= ' '.$s_res;
							   //  Select recipients
				$res_users = $adb->pquery("Select * From `vtiger_users` where `user_name` = '$s_res' ");
				$row_user = $adb->fetch_array($res_users);
				  $invited_users .= $row_user['first_name'] . ' ' . $row_user['last_name'].', ';
				$s_res = "";
			  } else if ($s_rec[$i] != ' ') $s_res .= $s_rec[$i];
			}
   
			
		  $recipients_details .= '<td width="440px">'.$invited_users.'</td></tr>';
		  $recipients_details .= '</table>';
		  
		}

		// Блок Inquiry c номером
		$route_details = '';  							
		$route = $inquiry_info->get('cf_717');
		$n = substr_count($route,'#');  
		$details = arrange_route_block_v2($route);


		// Если есть данные в полях routing
								
		if ($route != ''){
			$route_details .= '<span align="center"> <h3>Inquiry Ref.: GL/INQ - '.$record.' </h3></span>';
	 
	 
			for ($i=1;$i<=$n;$i++){
			
				$route_details .= '<table border="0.1em" style="padding:2px;">';	
		
				$route_details .= '<tr><td width="60px">Origin: </td><td width="365px">'.$details[1][$i].'</td>';
				$route_details .= '<td width="60px">Transport mode: </td><td width="50px">'.$details[2][$i].'</td></tr>';
				
				$route_details .= '<tr><td>Destination: </td><td width="365px">'.$details[3][$i].'</td>';
				$route_details .= '<td width="60px">Incoterms: </td><td width="50px">'.$details[4][$i].'</td></tr>';
				
				$route_details .= '<tr><td>Weight: </td><td width="365px">'.$details[5][$i].'</td>';
				$route_details .= '<td width="60px">Volume: </td><td width="50px">'.$details[6][$i].'</td></tr>';
		
				$route_details .= '<tr><td>Dimensions: </td><td width="365px">'.$details[7][$i].'</td>';
				$route_details .= '<td width="60px">Commodity: </td><td width="50px">'.$details[8][$i].'</td></tr>';
				
				$route_details .= '<tr><td>Transport Type: </td><td width="365px">'.$details[9][$i].'</td>';
				$route_details .= '<td width="60px">Quantity: </td><td width="50px">'.$details[10][$i].'</td></tr>';
				$route_details .= '</table><div style="margin-bottom:3px;"></div>'; 
			}
			
		  } 
		  
			// Блок Cargo Description продолжение
			$cargo_details = "";
			// Получаем данные по соответствующим полям
			$str_rows = '';  // Переменная для хранения записи
			$sign_count = 0; // Переменная для хранения количество записей
			$rec_count = 0;  // Переменная для хранения всех записей			
			
			//  Массивы для хранения значение полей Cargo Description 
			$mas_cf_weight = Array();
			$mas_cf_volume = Array();
			$mas_cf_dimensions = Array();
			
			$mas_cf_commodity = Array();
			$mas_cf_transport_type = Array();
			$mas_cf_quantity = Array();

			// Родные поля Vtiger, т.е. которые будут сохраняться в базе	
			$cf_weight =  $inquiry_info->get('cf_703');
			$cf_volume =  $inquiry_info->get('cf_705');
			$cf_dimensions =  $inquiry_info->get('cf_707');
			
			$cf_commodity =  $inquiry_info->get('cf_709');
			$cf_transport_type =  $inquiry_info->get('cf_715');
			$cf_quantity =  $inquiry_info->get('cf_813');

			if ($cf_weight != ''){
				$cargo_details .= '<span align="center"><h3>Cargo Description</h3></span>';
				$cargo_details .= '<table border="0.1em;" style="padding:2px;">';		
			 
		 
						 // Поиск # в поле Weight
			   for($c = 0; $c < strlen($cf_weight); $c++){
				 if ($cf_weight[$c] == '#'){
				   $rec_count++;
				   $mas_cf_weight[$rec_count] = $str_rows;
				   $str_rows = ''; 
				 } else $str_rows = $str_rows . $cf_weight[$c];
			   }
			   
			   $str_rows = '';
			   $sign_count = 0;
		 
			   
						 // Поиск # в поле Volume				
			   for($c = 0; $c < strlen($cf_volume); $c++){
				 if ($cf_volume[$c] == '#'){
				   $sign_count++;
				   $mas_cf_volume[$sign_count] = $str_rows;
				   $str_rows = ''; 
				 } else $str_rows = $str_rows . $cf_volume[$c];
			   }
			  
			   $str_rows = '';
			   $sign_count = 0;
			   
			  
			   
						 // Поиск # в поле Dimensions				
			   for($c = 0; $c < strlen($cf_dimensions); $c++){
				 if ($cf_dimensions[$c] == '#'){
				   $sign_count++;
				   $mas_cf_dimensions[$sign_count] = $str_rows;
				   $str_rows = ''; 
				 } else $str_rows = $str_rows . $cf_dimensions[$c];
			   }
			   
			   $str_rows = '';
			   $sign_count = 0;
			   
			   
		 
						 // Поиск # в поле Commodity				
			   for($c = 0; $c < strlen($cf_commodity); $c++){
				 if ($cf_commodity[$c] == '#'){
				   $sign_count++;
				   $mas_cf_commodity[$sign_count] = $str_rows;
				   $str_rows = ''; 
				 } else $str_rows = $str_rows . $cf_commodity[$c];
			   }
			   
			   $str_rows = '';
			   $sign_count = 0;
			   
			   
			   
						 // Поиск # в поле Transport Type				
			   for($c = 0; $c < strlen($cf_transport_type); $c++){
				 if ($cf_transport_type[$c] == '#'){
				   $sign_count++;
				   $mas_cf_transport_type[$sign_count] = $str_rows;
				   $str_rows = ''; 
				 } else $str_rows = $str_rows . $cf_transport_type[$c];
			   }
			   
			   $str_rows = '';
			   $sign_count = 0;
			   
		 
			   
						 // Поиск # в поле Quantity				
			   for($c = 0; $c < strlen($cf_quantity); $c++){
				 if ($cf_quantity[$c] == '#'){
				   $sign_count++;
				   $mas_cf_quantity[$sign_count] = $str_rows;
				   $str_rows = ''; 
				 } else $str_rows = $str_rows . $cf_quantity[$c];
			   }
		 
		 
				for($z=1; $z<=$rec_count; $z++){
			
							//  Weight
					$main_str = $mas_cf_weight[$z];
					$ln = strlen($mas_cf_weight[$z]);
					$main_str[($ln-1)] = ' ';		 
					$cargo_details .= '<tr><td width="60px;">Weight:</td><td width="207px;">'.$main_str.'</td>'; 
					
							//  Volume
							
					$main_str = $mas_cf_volume[$z];
					$ln = strlen($mas_cf_volume[$z]);
					$main_str[($ln-1)] = ' ';	 
					$cargo_details .= '<td  width="60px;">Volume:</td><td width="207px;">'.$main_str.'</td></tr>';
					
			
							//  Dimensions		 
					$main_str = $mas_cf_dimensions[$z];
					$ln = strlen($mas_cf_dimensions[$z]);
					$main_str[($ln-1)] = ' ';
					$cargo_details .= '<tr><td width="60px;">Dimensions:</td><td width="207px;">'.$main_str.'</td>';
				
							//  Commodity
					
					$main_str = $mas_cf_commodity[$z];
					$ln = strlen($mas_cf_commodity[$z]);
					$main_str[($ln-1)] = ' ';
					$cargo_details .= '<td>Commodity:</td><td width="207px;">'.$main_str.'</td></tr>';
			
			
							//  Transport_type
							
					$main_str = $mas_cf_transport_type[$z];
					$ln = strlen($mas_cf_transport_type[$z]);
					$main_str[($ln-1)] = ' ';
					// $pdf->SETXY($pos_x,$pos_y);
					$cargo_details .= '<tr><td>Tran. Type:</td><td width="207px;">'.$main_str.'</td>';
			
					
					$main_str = $mas_cf_quantity[$z];
					$ln = strlen($mas_cf_quantity[$z]);
					$main_str[($ln-1)] = ' ';
					$cargo_details .= '<td>Quantity:</td><td width="207px;">'.$main_str.'</td></tr>';
					if ($z != $rec_count) $cargo_details .= '<tr style="border:0em;"><td colspan="4"></td></tr>';
			
				}
			
			$cargo_details .= '</table>';
		  }

		  $combained_details = '';   
		  $combained_details .= $assigned_details.$recipients_details.'<br/>'.$route_details.'<br/>'.$cargo_details;
		  
		// Получаем данные по соответствующим полям
			//$sql_inqdesc = $adb->pquery("Select * From `vtiger_crmentity` where `crmid`=".$record);
			//$r_descr = $adb->fetch_array($sql_inqdesc);
			$description = $inquiry_info->get('description');
			// echo $description;
			// exit;
			if ($description != ''){
					$combained_details .= '<h4>Description:</h4> <br/>'.str_replace("\n","<br />",$description).'<br/>';
			}
		
		$deadline = $inquiry_info->get('closingdate');
		if ($deadline != ''){
			$combained_details .= '<h4>Deadline:</h4>'.$deadline;
		}  

		$inq_name = $inquiry_info->get("potentialname");
			
			
		$inquiry_from_ = str_replace("ü", "u", $inquiry_from_);
		$inquiry_from_ = str_replace("â", "a", $inquiry_from_);
		$inquiry_from_ = str_replace("ä", "a", $inquiry_from_);
		$inquiry_from_ = str_replace("ễ", "e", $inquiry_from_);
		$inquiry_from_ = str_replace("ủ", "u", $inquiry_from_);
		$inquiry_from_ = str_replace('"', "", $inquiry_from_);
		$inquiry_from_ = str_replace("'", "", $inquiry_from_);
		$inquiry_from_ = str_replace("Ş", 'Ltd.', $inquiry_from_);
		$inquiry_from_ = str_replace('ç', "c", $inquiry_from_);
		$inquiry_from_ = str_replace(':', "", $inquiry_from_);
		$inquiry_from_ = str_replace('НА', "HA", $inquiry_from_);
		
		$inq_name = str_replace("ü", "u", $inq_name);
		$inq_name = str_replace("â", "a", $inq_name);
		$inq_name = str_replace("ä", "a", $inq_name);
		$inq_name = str_replace("ễ", "e", $inq_name);
		$inq_name = str_replace("ủ", "u", $inq_name);
		$inq_name = str_replace('"', " ", $inq_name);
		$inq_name = str_replace("'", " ", $inq_name);
		$inq_name = str_replace("Ş", 'Ltd.', $inq_name);
		$inq_name = str_replace('ç', "c", $inq_name);
		$inq_name = str_replace(':', " ", $inq_name);
		$inq_name = str_replace('НА', "HA", $inq_name);

		$name=iconv("UTF-8", "CP1251", $name);
		$inq_name=iconv("UTF-8", "CP1251", $inq_name);

		//$name = htmlspecialchars($name, ENT_QUOTES);						
		$inquiry_from_ = str_replace("'", "", $inquiry_from_);
		
		$inquiry_from_ = str_replace('"', "", $inquiry_from_);
		$inquiry_from_ = str_replace("“", '', $inquiry_from_);
		$inquiry_from_ = str_replace("”", '', $inquiry_from_);
		$inquiry_from_ = str_replace("/", "", $inquiry_from_);
		$inquiry_from_ = str_replace("\ ", "", $inquiry_from_);
		$inquiry_from_ = str_replace('?', "", $inquiry_from_);
		$inquiry_from_ = str_replace("«", "", $inquiry_from_);
		$inquiry_from_ = str_replace("»", "", $inquiry_from_);
		$inquiry_from_ = str_replace("-", "", $inquiry_from_);
		$inquiry_from_ = str_replace(",,", "", $inquiry_from_);
		$inquiry_from_ = str_replace("”", "", $inquiry_from_);
		
		$inq_name = str_replace("'", " ", $inq_name);
		$inq_name = str_replace('"', " ", $inq_name);
		$inq_name = str_replace("“", ' ', $inq_name);
		$inq_name = str_replace("”", ' ', $inq_name);
		$inq_name = str_replace("/", " ", $inq_name);
		$inq_name = str_replace("\ ", " ", $inq_name);
		$inq_name = str_replace('?', " ", $inq_name);
		$inq_name = str_replace("«", " ", $inq_name);
		$inq_name = str_replace("»", " ", $inq_name);
		$inq_name = str_replace("-", " ", $inq_name);
		$inq_name = str_replace(",,", " ", $inq_name);
		$inq_name = str_replace("”", " ", $inq_name);

		$pdf->writeHTMLCell(185,60,($pos_x - 1),$pos_y,$combained_details);
	
		if (empty($inquiry_from_) === false){
		$pdf->Output('pdf_docs/inquiry/INQ from '.$inquiry_from_.'('.$inq_name.').pdf', 'F'); 	
		$pdf_name = 'pdf_docs/inquiry/INQ from '.$inquiry_from_.'('.$inq_name.').pdf';
		}
		else {
			$pdf->Output('pdf_docs/inquiry/INQ from ('.$inq_name.').pdf', 'F');
			$pdf_name = 'pdf_docs/inquiry/INQ from ('.$inq_name.').pdf';
		}

		header('Location:'.$pdf_name);
		exit;	  		  
		
	}


	 // Function arranging routing details
	 function arrange_route_block_v2($route){
		$n = substr_count($route,'#');
		$details = array();
		$buffer = '';

		$j = 1;
		$i = 0;

		for($c = 0; $c <=strlen($route); $c++){
			if ($route[$c] == '#') { $j++; $i = 0;}
			if ($route[$c] == "|"){
				$i ++;
				$details[$i][$j] = $buffer;
				$buffer = '';
			} else if ($route[$c] != '#') $buffer = $buffer . $route[$c];
		}

		for ($i=1;$i<=$n;$i++){

			$details[1][$i] = str_replace('From:','',$details[1][$i]);
			$details[2][$i] = str_replace('Mode:','',$details[2][$i]);

			$details[3][$i] = str_replace('To:','',$details[3][$i]);
			$details[4][$i] = str_replace('Inco:','',$details[4][$i]);

			$details[5][$i] = str_replace('Wt:','',$details[5][$i]);
			$details[6][$i] = str_replace('Vol:','',$details[6][$i]);

			$details[7][$i] = str_replace('Dim:','',$details[7][$i]);
			$details[8][$i] = str_replace('Comm:','',$details[8][$i]);

			$details[9][$i] = str_replace('Trans:','',$details[9][$i]);
			$details[10][$i] = str_replace('Qty:','', $details[10][$i]);

			$details[11][$i] = str_replace('Rate:','', $details[11][$i]);
		}

		return $details;

	}

	function get_branch_details($city,$field){
		$adb = PearDatabase::getInstance();
		$sql_branch = $adb->pquery("SELECT * FROM `vtiger_branch_details` where `city`='".$city."'");
		$r_branch = $adb->fetch_array($sql_branch);
		return $r_branch["$field"];
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
