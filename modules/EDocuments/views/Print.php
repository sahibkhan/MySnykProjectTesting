<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class VEF_Print_View extends Vtiger_Print_View {
	
	
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

		global $adb;
		$adb = PearDatabase::getInstance();

		$moduleName = $request->getModule();
		$record = $request->get('record');
		$tpl = $request->get('tpl');	
		$type = $request->get('type');

		$current_user = Users_Record_Model::getCurrentUserModel();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$vef_info = Vtiger_Record_Model::getInstanceById($record, 'VEF');

		//
		$cat = $vef_info->get('vef_service_category');
		$carrier =  $vef_info->get('vef_carrier');	

		//print_r($category);
		//print_r($carrier);
		//die();

		$sql = "SELECT * FROM vtiger_approvalroutehistory INNER JOIN vtiger_approvalroutehistorycf ON vtiger_approvalroutehistorycf.approvalroutehistoryid = vtiger_approvalroutehistory.approvalroutehistoryid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid INNER JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788 INNER JOIN vtiger_user2role ON vtiger_user2role.userid=vtiger_users.id INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid WHERE vtiger_approvalroutehistorycf.cf_6784='".$record."' AND vtiger_crmentity.deleted=0 ORDER BY vtiger_approvalroutehistorycf.cf_6790";
		$result = $adb->pquery($sql);
		$noofrows = $adb->num_rows($result);
		if($noofrows > 0){
			$approvalroutes = array();
			for($n=0; $n<$noofrows ; $n++) {
				$approvalroutes[$n]['name']=$adb->query_result($result,$n,'name');
				$usersRecord = Vtiger_Record_Model::getInstanceById($adb->query_result($result,$n,'cf_6788'), 'Users');
				$first_name = $usersRecord->get('first_name');
				$last_name = $usersRecord->get('last_name');
				$approvalroutes[$n]['username']=$first_name." ".$last_name;
				$approvalroutes[$n]['sequence']=$adb->query_result($result,$n,'cf_6790');
				$approvalroutes[$n]['status']=ucfirst($adb->query_result($result,$n,'cf_6792'));
				$approvalroutes[$n]['uDate']=$adb->query_result($result,$n,'cf_6794');
				$approvalroutes[$n]['uNotes']=$adb->query_result($result,$n,'cf_7060');
				$approvalroutes[$n]['userDesignation']=$adb->query_result($result,$n,'rolename');
				$approvaluser[] = $adb->query_result($result,$n,'cf_6788');
						
			}
		}

		/*
		$qt_owner_user_info = Users_Record_Model::getInstanceById($quote_info->get('assigned_user_id'), 'Users');
		if ($quote_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($quote_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}
		*/
		/*
		$to = '';
		// Field agent in QT
		if ($quote_info->get('cf_1827') != 0){
		  $account_info2 = Users_Record_Model::getInstanceById($quote_info->get('cf_1827'), 'Accounts');
		  $Agent = $account_info2->get('cf_2395');
		  $to = $Agent;
		} else $Agent = "";
		*/
	
		switch ([$cat, $carrier]) {
		    case ['Air', 'Yes']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Air', 'No']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Road', 'Yes']:
		        $tpl_name = "vef_transportation";
		    break;
		    case ['Road', 'No']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Rail', 'Yes']:
		        $tpl_name = "vef_rail";
		    break;
		    case ['Rail', 'No']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Sea', 'Yes']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Sea', 'No']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Brokerage', 'Yes']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Brokerage', 'No']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Ware House', 'Yes']:
		        $tpl_name = "vef_warehouse";
		    break;
		    case ['Ware House', 'No']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Others', 'Yes']:
		        $tpl_name = "vef_general";
		    break;
		    case ['Others', 'No'];
		        $tpl_name = "vef_general";
		    break;
		}

		// Detect type of PDF
		// RRS or General
		/*if ($type == 'general'){
		  $tpl_name = "vef_general";
		} else {
		  $tpl_name = "vef_rrs";		
		}*/

			
		$document = $this->loadTemplate('printtemplates/VEF/'.$tpl_name.'.html');	
		
		/*
		// Custom fields for template
		$this->setValue('price_coordinator',$price_coordinator, ENT_QUOTES, "UTF-8");
		$this->setValue('pc_tel',$pc_tel, ENT_QUOTES, "UTF-8");
		$this->setValue('move_coordinator',$move_coordinator, ENT_QUOTES, "UTF-8");
		$this->setValue('mc_tel',$mc_tel, ENT_QUOTES, "UTF-8");	
		
		
		$this->setValue('to',$to);
		$this->setValue('from',htmlentities($qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('attn',htmlentities($attn, ENT_QUOTES, "UTF-8"));
		$user_city = trim($r_users['address_city']);
		
		
		$adb = PearDatabase::getInstance();				
		$sql_branch_tel = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b
									 where b.city = '".$qt_owner_user_info->get('address_city')."'", array());
	    $branch_tel = $adb->query_result($sql_branch_tel, 0, 'tel');
		$this->setValue('tel',$branch_tel);
		
		$subject = $quote_info->get('subject');		
		$this->setValue('date',date('d.m.Y', strtotime($quote_info->get('createdtime'))));
		if ($email == '') $email = $qt_owner_user_info->get('email1'); 
		$this->setValue('email',$email, ENT_QUOTES, "UTF-8");
		$this->setValue('ref',htmlentities('GL/QT - '.$quote_id, ENT_QUOTES, "UTF-8"));
		$this->setValue('dep',htmlentities($qt_owner_user_info->get('department'), ENT_QUOTES, "UTF-8"));		
		$this->setValue('subject',$subject);
		*/
		
					// General
		//$shipper = $quote_info->get('cf_777');

			// Header
		$tel = '+7(727) 258-88-80';
		$fax = '+7(727) 258-88-85';
		$email = 'a.serikbekkyzy@globalinklogistics.com';
		
		$this->setValue('tel',$tel);
		$this->setValue('fax',$fax);
		//$this->setValue('email',$email);
		
									// Body
		// Getting values
		$b_type = $vef_info->get('vef_type_of_business');
		$service_ofdesciption = $vef_info->get('cf_4371');
		
		
		// Sginature details
		$cc_sign = $vef_info->get('cf_5676');
		$cfo_sign = $vef_info->get('cf_5678');
		
		if (!empty($cc_sign)){
			$cc_sign = $cc_sign;
		} else {
			$cc_sign = "-";
		}
		
		$this->setValue('cc_signature',$cc_sign);
		
		
		if (!empty($cfo_sign)){
			$cfo_sign = $cfo_sign;
		} else {
			$cfo_sign = "-";
		}
		
		$this->setValue('cfo_signature',$cfo_sign);
		
		// Company Information
		$reg_name = $vef_info->get('cf_4347');
		$trading_name = $vef_info->get('cf_4349');
		$reg_number = $vef_info->get('vef_registration_number');
		$reg_date = $vef_info->get('vef_registration_date');
		$vat_reg_number = $vef_info->get('vef_vat_registration_number');
		$bin_tin = $vef_info->get('vef_bin_tin');
		$basic_information = [
			'Account'=>$vef_info->get('vef_account'),
			'Account Type'=>$vef_info->get('vef_account_type'),
			'Type of Vendor'=>$vef_info->get('vef_type_of_vendor'),
			'Status'=>$vef_info->get('vef_status'),
			'Type of Business'=>$vef_info->get('vef_type_of_business'),
			'Department'=>$vef_info->get('vef_department'),
			'Service Description'=>$vef_info->get('cf_5682'),
			'Location'=>$vef_info->get('vef_location')
		];

		
		// Address
		$legal_address = $vef_info->get('cf_4359');
		$post_address = $vef_info->get('cf_4361');
		$postal_zipcode = $vef_info->get('cf_4363');	
		$tel_number = $vef_info->get('vef_telephone');
		$email = $vef_info->get('vef_email');
		$website = $vef_info->get('vef_website');
		$taxpayer_identification = $vef_info->get('vef_taxpayer_identification');
		$legal_name = $vef_info->get('name');
		$no_of_employees = $vef_info->get('vef_number_of_employees');
		$director_name = $vef_info->get('vef_director_name');
		$owner_name = $vef_info->get('vef_owners_name');
		$percentage = $vef_info->get('vef_percentage_ownership');
		$service_category = $vef_info->get('vef_service_category');

		$country = $vef_info->get('vef_country');
		$city = $vef_info->get('vef_city');
		$street = $vef_info->get('vef_street');
		$postal = $vef_info->get('vef_postal_zip_code');
		$contact_person = $vef_info->get('vef_contact_person');
		$position = $vef_info->get('vef_position');
		$nature_of_business = $vef_info->get('vef_nature_of_business');
		$mail = $vef_info->get('vef_email');

		$this->setValue('country',$country);
		$this->setValue('city',$city);
		$this->setValue('street',$street);
		$this->setValue('postal',$postal);
		$this->setValue('contact_person',$contact_person);
		$this->setValue('email',$mail);
		$this->setValue('position',$position);
		$this->setValue('nature_of_business',$nature_of_business);

		$this->setValue('service_category',$service_category);
		$this->setValue('director_name',$director_name);
		$this->setValue('owner_name',$owner_name);
		$this->setValue('percentage',$percentage);
		$this->setValue('legal_name',$legal_name);
		$this->setValue('no_of_employees',$no_of_employees);
		$this->setValue('email',$email);
		$this->setValue('reg_number',$reg_number);
		$this->setValue('vat_reg_number',$vat_reg_number);
		$this->setValue('tel_number',$tel_number);
		$this->setValue('website',$website);
		$this->setValue('taxpayer_identification',$taxpayer_identification);
		
		// Contact Information	
		$acc_manager = $vef_info->get('cf_4377');
		$acc_manager_details = $vef_info->get('cf_4383');
		
		$owner_director = $vef_info->get('cf_4373');
		$owner_director_details = $vef_info->get('cf_4379');	
		
		$qhse_manager = $vef_info->get('cf_4381');
		$qhse_manager_details = $vef_info->get('cf_4375');
		
		
		// Insurance
		$civil_resp = $vef_info->get('vef_civil_legal_responsibility');
		$liability_third = $vef_info->get('vef_liability_of_thirdparty');
		
		$loss_cargo = $vef_info->get('vef_loss_of_cargo');
		$damage_cargo = $vef_info->get('vef_damage_cargo');
		
		$ligitation_against = $vef_info->get('vef_litigations_insurance');
		$insurance_additions = $vef_info->get('vef_additions_to_insurance');
		$total_limit = $vef_info->get('vef_limit_coverage');	
		
		
		//  For completion by Responsible Person in Globalink
		$total_numberspoint = $vef_info->get('vef_total_points');
		$description = $vef_info->get('vef_description');
		
		
		//  CSR module / Transportation info
		$type_of_vehicle = '';
		  global $adb;
		  $adb = PearDatabase::getInstance();
		  $q = "SELECT vtiger_csrcf.*
			FROM vtiger_csrcf
			LEFT JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_csrcf.csrid
			WHERE vtiger_crmentityrel.module = 'VEF' AND vtiger_crmentityrel.relmodule = 'CSR' 
			AND vtiger_crmentityrel.crmid = ?";
		  $r_consol = $adb->pquery($q, array($record));
		  $numRows = $adb->num_rows($r_consol);
		
		
		//  CSR module / Transportation info
		$type_of_vehicleVEF = '';
		  $adb = PearDatabase::getInstance();
		  $q = "SELECT vtiger_typeofvehiclevefcf.*
			FROM vtiger_typeofvehiclevefcf
			LEFT JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_typeofvehiclevefcf.typeofvehiclevefid
			WHERE vtiger_crmentityrel.module = 'VEF' AND vtiger_crmentityrel.relmodule = 'TypeOfVehicleVEF' 
			AND vtiger_crmentityrel.crmid = ?";
		  $r_typeof_vehicleVEF = $adb->pquery($q, array($record));
		  $n_typeOfVehiclesVEF = $adb->num_rows($r_typeof_vehicleVEF);
		
		
		
		//Types of Provided Services	
		
		$ftl = $adb->query_result($r_consol,0,"cf_4419");
		$ltl = $adb->query_result($r_consol,0,"cf_4421");
		$consolidation = $adb->query_result($r_consol,0,"cf_4423");
		
		$ftl_dest = $adb->query_result($r_consol,0,"cf_4511");
		$ltl_dest = $adb->query_result($r_consol,0,"cf_4513");
		$consolidation_dest = $adb->query_result($r_consol,0,"cf_4515");
		
		$refri_truck = $adb->query_result($r_consol,0,"cf_4427");
		$oversized_cargo = $adb->query_result($r_consol,0,"cf_4429");	
		$dangerouse_cargo = $adb->query_result($r_consol,0,"cf_4431");
		$high_prices_cargo = $adb->query_result($r_consol,0,"cf_4433");
			
		//Information About the Drivers
		$i = 0;
		$workon_regrig = $adb->query_result($r_consol,$i,"cf_4441");
		$workon_oversizecargo = $adb->query_result($r_consol,$i,"cf_4443");
		
		$workon_dangcargo = $adb->query_result($r_consol,$i,"cf_4445");
		$workon_high = $adb->query_result($r_consol,$i,"cf_4447");
		
		$typeof_license = $adb->query_result($r_consol,$i,"cf_4449");

		//Information About Warehouse
		$i = 0;
		$squareof_warehouse = $adb->query_result($r_consol,$i,"cf_4451");
		$roofed_square = $adb->query_result($r_consol,$i,"cf_4453");	
		$square_dangerouse = $adb->query_result($r_consol,$i,"cf_4455");
		

		//Information RRS Packers
		  $q_rrspackers = "SELECT vtiger_rrspackerscf.*
			FROM vtiger_rrspackerscf
			LEFT JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_rrspackerscf.rrspackersid
			WHERE vtiger_crmentityrel.module = 'VEF' AND vtiger_crmentityrel.relmodule = 'RRSPackers' 
			AND vtiger_crmentityrel.crmid = ?";
		  $r_rrspackers = $adb->pquery($q_rrspackers, array($record));
		  $n_rrspackers = $adb->num_rows($r_rrspackers);
		  $own_warehouse = $adb->query_result($r_rrspackers,$i,"cf_4457");
		  $ownerof_warehouse = $adb->query_result($r_rrspackers,$i,"cf_4459");
		  
		  $warehouse_area = $adb->query_result($r_rrspackers,$i,"cf_4461");
		  $roofed_warehouse = $adb->query_result($r_rrspackers,$i,"cf_4463");
		  
		  $responsible_repsonwarehouse = $adb->query_result($r_rrspackers,$i,"cf_4465");
		  $CCTV_available = $adb->query_result($r_rrspackers,$i,"cf_4467");
		  
		  $responsible_repsonwarehouse = $adb->query_result($r_rrspackers,$i,"cf_4465");
		  $CCTV_available = $adb->query_result($r_rrspackers,$i,"cf_4467");

		  $security_available = $adb->query_result($r_rrspackers,$i,"cf_4469");
		  $fire_security = $adb->query_result($r_rrspackers,$i,"cf_4471");	  
	      $pest_control = $adb->query_result($r_rrspackers,$i,"cf_4471");	  
	  
			  
			  
		
		$i = 1;
		// Assigning to variables
		$company_information = '';
		if (($basic_information['Account'])||($basic_information['Account Type'])||($basic_information['Type of Vendor'])||($basic_information['Status'])||($basic_information['Type of Business'])||($basic_information['Department'])||($basic_information['Service Description'])||($basic_information['Location'])) {
			$company_information = "<h4> Basic Information </h4>";
			$company_information .= '<table width="850" cellspacing=4 cellpadding=4 border=1>';
			
			if ($basic_information['Account']) $company_information .= "<tr><td>Account: </td> <td>".$basic_information['Account']."</td></tr>";
			if ($basic_information['Account Type']) $company_information .= "<tr><td>Account Type: </td> <td>".$basic_information['Account Type']."</td></tr>";
			if ($basic_information['Type of Vendor']) $company_information .= "<tr><td>Type of Vendor: </td> <td>".$basic_information['Type of Vendor']."</td></tr>";
			if ($basic_information['Status']) $company_information .= "<tr><td>Status: </td> <td>".$basic_information['Status']."</td></tr>";
			if ($basic_information['Type of Business']) $company_information .= "<tr><td>Type of Business: </td> <td>".$basic_information['Type of Business']."</td></tr>";
			if ($basic_information['Department']) $company_information .= "<tr><td>Department: </td> <td>".$basic_information['Department']."</td></tr>";
			if ($basic_information['Service Description']) $company_information .= "<tr><td>Service Description: </td> <td>".$basic_information['Service Description']."</td></tr>";
			if ($basic_information['Location']) $company_information .= "<tr><td>Location: </td> <td>".$basic_information['Location']."</td></tr>";

			$company_information .= '</table>';
		}

		
		 // if (($b_type) || ($service_ofdesciption) || ($reg_name) || ($trading_name) || ($reg_number) || ($reg_date) || ($vat_reg_number) || ($bin_tin)) {
		 // 	$company_information = "<h4> Basic Information </h4>";
		 //  $company_information .= '<table width="850" cellspacing=4 cellpadding=4 border=1>';
		  
		 //  if ($b_type){
		 //    $company_information .= "<tr><td>Type of Business: </td> <td>".$b_type."</td></tr>";
		 //  }

		 //  if ($service_ofdesciption) {
		 //  	$company_information .= "<tr><td>Service Description </td> <td>".$service_ofdesciption."</td></tr>";
		 //  }

		 //  if ($reg_name){
			// $i++;
		 //    $company_information .= "<tr><td colspan=2> <h4> Company Information:</h4> </td></tr>";
			// $company_information .= "<tr><td>Registered Name: </td> <td>".$reg_name."</td></tr>";
		 //   }
		  
		 //  if ($trading_name){
			// $company_information .= "<tr><td>Trading Name: </td> <td>".$trading_name."</td></tr>";
		 //  } 
			
		 //  if ($reg_number){
			// $company_information .= "<tr><td>Registration Number: </td> <td>".$reg_number."</td></tr>";
		 //  }

		 //  if ($reg_date){
			// $company_information .= "<tr><td>Registration Date: </td> <td>".$reg_date."</td></tr>";
		 //  }
		  
		 //  if ($vat_reg_number){
			// $company_information .= "<tr><td>VAT Registration Number: </td> <td>".$vat_reg_number."</td></tr>";
		 //  }
		  
		 //  if ($bin_tin){
			// $company_information .= "<tr><td> BIN / TIN: </td> <td>".$bin_tin."</td></tr>";
		 //  }
		  
		 //  $company_information .= '</table>';
		  
		 // }

		 $this->setValue('company_information', $company_information);
		  
		  // Address		  
			$address_block = '';
		 
		  if (($legal_address) || ($post_address) || ($postal_zipcode) || ($tel_number) || ($email) || ($website)){
				  $address_block = "<h4> Legal Address </h4>";
				$address_block .= "<table width='850' border=0 cellspacing=4 cellpadding=4 border=1>";
				$address_block .= "<tr> <td> Legal Address: </td> <td> $legal_address </td>
									    <td> Postal Address: </td> <td> $post_address </td></tr>";
				$address_block .= "<tr> <td> Postal / ZIP Code:	 </td> <td> $postal_zipcode </td>
									    <td> Telephone No.: </td> <td> $tel_number </td></tr>";
				$address_block .= "<tr> <td> Email: </td> <td> $email </td>
									    <td> Website: </td> <td> $website </td></tr>";
				$address_block .= '</table>';
				 
			}
			$this->setValue('address_information', $address_block);
			

		// Contact Information
		if (($acc_manager) || ($owner_director) || ($qhse_manager)){
		   $contactinfo_block = "<h4> Contact Information </h4>";
			$contactinfo_block .= "<table width='850' border='1' cellspacing=4 cellpadding=4>";
				
				$contactinfo_block .= "<tr> <td> Account Manager: </td> <td> $acc_manager </td></tr>";
				$contactinfo_block .= "<tr> <td> Account Manager Contact Details:</td> <td> $acc_manager_details </td></tr>";
				
				$contactinfo_block .= "<tr> <td> Owner / Director:</td> <td> $owner_director </td></tr>";
				$contactinfo_block .= "<tr> <td> Owner / Director Contact Details:</td> <td> $owner_director_details </td></tr>";
				
				$contactinfo_block .= "<tr> <td> QHSE Manager:</td> <td> $qhse_manager </td></tr>";
				$contactinfo_block .= "<tr> <td> QHSE Manager Contact Details:</td> <td> $qhse_manager_details </td></tr>";
				
												
				$contactinfo_block .= '</table>';

			   	$this->setValue('contactinfo_information', $contactinfo_block);
		}



		
		// Insurance
		if (($civil_resp) || ($$liability_third) || ($loss_cargo) || ($damage_cargo) || ($ligitation_against) 
			|| ($insurance_additions) || ($insurance_additions) ){
			
		   $insurance_block = "<h4> Insurance </h4>";

		   
			$insurance_block .= "<table border='1' class='table table-bordered' width='850' cellspacing=4 cellpadding=4>";
			$insurance_block .= "<tr> <td> Civil Legal Responsibility: </td> <td> $civil_resp </td>
									<td> Liability of Third Party: </td> <td> $liability_third </td></tr>";
			$insurance_block .= "<tr> <td> Loss of Cargo:	 </td> <td> $loss_cargo </td>
									<td> Damage Cargo: </td> <td> $damage_cargo </td></tr>";
			$insurance_block .= "<tr> <td> Do you have any litigations against insurance: </td> <td> $ligitation_against </td>
									<td> If you have any additions to insurance: </td> <td> $insurance_additions </td></tr>";			
									
			$insurance_block .= "<tr> <td> What is the limit of total coverage?: </td> <td> $insurance_additions </td></tr>";
									
			$insurance_block .= '</table>';
			//$this->setValue('insurance_information', $insurance_block);
		}

		
		$this->setValue('insurance_information', $insurance_block);
		
		
		// For completion by Responsible Person in Globalink
			
		   $total_block = "<h4> For completion by Responsible Person in Globalink </h4>";

		   
			$total_block .= "<table border='1' class='table table-bordered' width='850' cellspacing=4 cellpadding=4>";
			$total_block .= "<tr> <td>Total Numbers of Points: </td> <td> $total_numberspoint </td></tr>";
			$total_block .= "<tr> <td> Description:	 </td> <td> $description </td></tr>";						
			$total_block .= '</table>';
			//$this->setValue('total_information', $total_block);
		$this->setValue('total_information', $total_block);
	 	
		
		// CSR	
			if ($n_typeOfVehiclesVEF > 0){
		   $total_block = "<h4> Information About Company's Services and resources </h4>";   
			$total_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";
			$total_block .= "<tr> <td> Type of vehicle </td> 
			                      <td> Quantity </td>
								  <td> Years of issuance </td>
								  <td> General Condition </td>
								  <td> Is the transport equipped with GPS navigation system </td>							  
							</tr>";

				
			  for($i=0; $i < $n_typeOfVehiclesVEF;$i++){
			    $type_of_vehicle = $adb->query_result($r_typeof_vehicleVEF,$i,"cf_4641");
				$quantity = $adb->query_result($r_typeof_vehicleVEF,$i,"cf_4643");
				$year_of_insurance = $adb->query_result($r_typeof_vehicleVEF,$i,"cf_4645");
				$general_condition = $adb->query_result($r_typeof_vehicleVEF,$i,"cf_5684"); 
				$equipment_GPS = $adb->query_result($r_typeof_vehicleVEF,$i,"cf_5686");
			    $total_block .= "<tr> <td> $type_of_vehicle </td> 
			                      <td> $quantity </td>
								  <td> $year_of_insurance </td>
								  <td> $general_condition </td>
								  <td> $equipment_GPS </td>							  
							</tr>";
				
			  }
						
			$total_block .= '</table>';
			
		}
		$this->setValue('information_about_company_services', $total_block);
			
		 	
		// Type of provided address
			//if ($numRows > 0){
		   $total_block = "<h4> Type of provided services (A) </h4>";   
			$total_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";
			$total_block .= "<tr> <td> Type of services </td> 
			                      <td> Yes/No </td>
								  <td> Destinations </td>					  
							</tr>";

				
			  $i = 0;
				
			    $total_block .= "<tr> <td> FTL </td> <td> $ftl </td> <td> $ftl_dest </td> </tr>";
				$total_block .= "<tr> <td> LTL </td> <td> $ltl </td> <<td> $ltl_dest </td> </tr>";
				$total_block .= "<tr> <td> Consolidation </td> <td> $consolidation </td> <td> $consolidation_dest </td> </tr>";	
			$total_block .= '</table>';
			$this->setValue('typeof_provided_services', $total_block);
		//}
		
		
		   $servicetype_block = "<h4> B. </h4>";   
			$servicetype_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";
			$servicetype_block .= "<tr> <td> Types of services </td> 
			                      <td> Yes/No </td>
								  <td> Destinations </td>
								  <td> What SOP / handling / transportation / process do you have? </td>
								  <td> What legal permits do you have to transport such kind of cargo? </td>							  
							</tr>";

			
			    $servicetype_block .= "<tr> <td> Refrigerated Truck	 </td> <td> $refri_truck </td> <td>  </td> </tr>";
				$servicetype_block .= "<tr> <td> Oversized Cargo </td> <td> $oversized_cargo </td> <td>  </td> </tr>";
				$servicetype_block .= "<tr> <td> Dangerous Cargo </td> <td> $dangerouse_cargo </td> <td>  </td> </tr>";	
				$servicetype_block .= "<tr> <td> Transportation of High Prices Cargo </td> <td> $high_prices_cargo </td> <td>  </td> </tr>";	
				
			$servicetype_block .= '</table>';
			$this->setValue('servicetype_information', $servicetype_block);
			
			
		
		   $drives_block = "<h4> Information about the drivers </h4>";   
			$drives_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";
			$drives_block .= "<tr> <td> Specialization of driver </td> 
			                      <td> Quantity </td>
								  <td> Type of licensewhich driver has to carry such cargo? </td>							  
							</tr>";
							
			    $drives_block .= "<tr> <td> Drivers Who Work on Refrigerated Trucks </td> <td> $workon_regrig </td> <td>  </td> </tr>";
				$drives_block .= "<tr> <td> Drivers Who Work on Oversized Cargo </td> <td> $workon_oversizecargo </td> <td>  </td> </tr>";
				$drives_block .= "<tr> <td> Drivers Who Work on Dangerous Cargo </td> <td> $workon_dangcargo </td> <td>  </td> </tr>";	
				$drives_block .= "<tr> <td> Drivers Who Work on High Prices Cargo  </td> <td> $workon_high </td> <td>  </td> </tr>";	
				$drives_block .= "<tr> <td> Type of License	</td> <td> $typeof_license </td> <td>  </td> </tr>";	

			$drives_block .= '</table>';
			$this->setValue('information_about_drivers', $drives_block);
			
			
		   $drives_block = "<h4> Information about warehouse </h4>";   
			$drives_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";

			    $drives_block .= "<tr> <td> Square of Warehouse in m2 </td> <td> $squareof_warehouse </td> </tr>";
				$drives_block .= "<tr> <td> Roofed square of Warehouse in m2 </td> <td> $roofed_square </td> </tr>";
				$drives_block .= "<tr> <td> Square for Dangerous Goods storage in m2 (if there is such square) </td> 
				<td> $square_dangerouse </td> </tr>";	

			$drives_block .= '</table>';
			$this->setValue('information_about_warehouse', $drives_block);
			
			
			// RRS Packers
			//if ($n_rrspackers > 0){
				
		      $rrspackers_block = "<h4> Information About RRS Packers </h4>";  

			  $rrspackers_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";
			  $rrspackers_block .= "<tr> 
						             <td> Does your company have own warehouse? </td>
									 <td> $own_warehouse </td>	
									</tr>
									<tr>	
									 <td> If not, please, indicate the owner company of warehouse  </td>
									 <td> $ownerof_warehouse </td>	
					              </tr>";
			  $rrspackers_block .= '</table>';	
		   
			  $rrspackers_block .= "<table class='table table-bordered' width='850' border='1' cellspacing=4 cellpadding=4>";
			  $rrspackers_block .= "<tr> <td> Warehouse Area (sq.m.) </td> <td>$warehouse_area </td> </tr>
			                      <tr><td> Roofed square of Warehouse (sq.m.) </td> <td> $roofed_warehouse </td></tr>							  
								  <tr><td> Responsible Person for Warehouse </td> <td>$responsible_repsonwarehouse </td></tr>
								  <tr><td> CCTV Available </td> <td>$CCTV_available </td></tr>
								  <tr><td> Security Available </td> <td>$security_available </td></tr>
								  <tr><td> Fire Security System Available </td> <td>$fire_security </td></tr>
								  <tr><td> Pest Control  </td> <td> $pest_control </td></tr>";
			
			   $rrspackers_block .= '</table>';
			$this->setValue('information_about_rrspackers', $rrspackers_block);
			//}




			if (($noofrows > 0)){
				 $approval_parties = "<h4> Approval Parties </h4>";
				 $approval_parties .= "<table width='850' border=0 cellspacing=4 cellpadding=4 border=1>";
				foreach($approvalroutes as $apr){
			    $approval_parties .= "<tr> <td> $apr[username] </td> 
			                      
								  <td> $apr[status] </td>
								  <td> $apr[uDate] </td>
								  							  
							</tr>";
			  }
				$approval_parties .= '</table>';
			}


			$this->setValue('approval_history', $approval_parties);





	  
			

			include('include/mpdf60/mpdf.php');

	  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
	  		$mpdf->charset_in = 'utf8';
			$mpdf->list_indent_first_level = 0;
			$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

			
			// Detect type of PDF
			// RRS or General
			/*if ($type == 'general'){
			  $pdf_name = "pdf_docs/vefr_".$record.".pdf";	
			} else if ($type == 'rrs'){
			  $pdf_name = "pdf_docs/vef_".$record.".pdf";	
			}*/
			


			$pdf_name = "pdf_docs/".$tpl_name."_".$record.".pdf";				
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
