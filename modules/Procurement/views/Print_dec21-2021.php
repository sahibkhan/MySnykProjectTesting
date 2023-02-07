<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Print_View extends Vtiger_Print_View {

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
		
		//$type = $request->get('type');	
		
		$this->print_procurement_request($request);
		
		/*
		$response = new Vtiger_Response();
		$response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
		$response->emit();
		*/	
	}
	public function print_procurement_request($request){
		include('include/Exchangerate/exchange_rate_class.php');
		global $adb;
		//$adb->setDebug(true);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		
		$current_user = Users_Record_Model::getCurrentUserModel();		
		$procurement_data = Vtiger_Record_Model::getInstanceById($recordId, 'Procurement');	
		$creatorID = $procurement_data->get('assigned_user_id');
		$ProcurementType_data = Vtiger_Record_Model::getInstanceById($procurement_data->get('proc_proctype'), 'ProcurementTypes');
		$ProcurementType_code = $ProcurementType_data->get('proctype_shortcode');
		//echo "<pre>";print_r($procurement_data);exit;		
	
		$document = $this->loadTemplate('printtemplates/Procurement/new.html');		
		//echo "....anbdkc gdhj.......";exit;
		$procurement_items = '<tr>
			<td style="background-color:#ddd; font-weight:bold">Expense Type</td>
			<td style="background-color:#ddd; font-weight:bold">Description</td>
			<td style="background-color:#ddd; font-weight:bold">Qty</td>
			<td style="background-color:#ddd; font-weight:bold">Price/Unit</td>
			<td style="background-color:#ddd; font-weight:bold">Local Price</td>
			<td style="background-color:#ddd; font-weight:bold">VAT (%)</td>
			<td style="background-color:#ddd; font-weight:bold">Price VAT</td>
			<td style="background-color:#ddd; font-weight:bold">Gross (Local)</td>
			<td style="background-color:#ddd; font-weight:bold">Currency</td>
			<td style="background-color:#ddd; font-weight:bold">Gross (Local)</td>
			<td style="background-color:#ddd; font-weight:bold">Total (Local Currency)</td>
			<td style="background-color:#ddd; font-weight:bold">Total USD</td>
		</tr>';
		$unit_price_total = 0;
		$local_price_total = 0;
		$vat_price_total = 0;
		$gross_local = 0;
		$gross_local_total = 0;
		$total_net = 0;
		$total_usd = 0;
		$result=$adb->query("SELECT pi.* FROM `vtiger_procurement` as p INNER JOIN vtiger_procurementitemscf
		 as pi ON p.procurementid= pi.procitem_procid INNER JOIN vtiger_crmentity as c ON c.crmid=pi.procurementitemsid
		  where c.deleted=0 and p.procurementid=$recordId");
		  	
			$Procureitem=array();
			$expence_type_data_code = '';
			if($adb->num_rows($result)>0){
				for($i=0; $i<$adb->num_rows($result); $i++) {
					//$Procureitem[$i]['procurementitemsid'] = $adb->query_result($result, $i, 'procurementitemsid');
					//$Procureitem[$i]['procitem_net_finalamount'] = $adb->query_result($result, $i, 'procitem_net_finalamount');
					$currency = $adb->query_result($result, $i, 'procitem_currency');
					$query = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$currency");
					$currency_code = $adb->query_result($query, 0, 'currency_code');
					$totalusdamount+=$adb->query_result($result, $i, 'procitem_total_usd'); //usman
					$expence_type_id = $adb->query_result($result, $i, 'procitem_proctypeitem_id');
				    $expence_type_data = Vtiger_Record_Model::getInstanceById($expence_type_id, 'ProcurementTypeItems');
					//$Procureitem[$i]['procitem_procid'] = $adb->query_result($result, $i, 'procitem_procid');
					//$Procureitem[$i]['cf_7568'] = $adb->query_result($result, $i, 'cf_7568');
					
					if($ProcurementType_code=='PM') //show code value for packaging material
					{
						$expence_type_data_code = '['.$expence_type_data->get('proctypeitem_code').'] : ';
					}
					$procurement_items .= '<tr>
											<td>'.$expence_type_data_code.$expence_type_data->get('name').'</td>
											<td>'.$adb->query_result($result, $i, 'procitem_description').'</td>
											<td>'.$adb->query_result($result, $i, 'procitem_qty').'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_unit_price') , 2 ,  "." , ",").'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_line_price') , 2 ,  "." , ",").'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_vat_unit') , 2 ,  "." , ",").'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_vat_amount') , 2 ,  "." , ",").'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_gross_amount') , 2 ,  "." , ",").'</td>
											<td>'.$currency_code.'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_gross_local') , 2 ,  "." , ",").'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_gross_finalamount') , 2 ,  "." , ",").'</td>
											<td>'.number_format($adb->query_result($result, $i, 'procitem_total_usd') , 2 ,  "." , ",").'</td>
										</tr>';
					$unit_price_total = $unit_price_total+$adb->query_result($result, $i, 'procitem_unit_price');
					$local_price_total = $local_price_total+$adb->query_result($result, $i, 'procitem_line_price');
					$vat_price_total = $vat_price_total+$adb->query_result($result, $i, 'procitem_vat_amount');
					$gross_local = $gross_local+$adb->query_result($result, $i, 'procitem_gross_amount');
					$gross_local_total = $gross_local_total+$adb->query_result($result, $i, 'procitem_gross_local');
					$total_net = $total_net+$adb->query_result($result, $i, 'procitem_gross_finalamount');
					$total_usd = $total_usd+$adb->query_result($result, $i, 'procitem_total_usd');
			
			}
		}
		$total_usd = $procurement_data->get('proc_total_amount');
		$procurement_items .= '
			<tr>
						<td colspan="3" style="background-color:#666;color:#fff;text-align:center;font-weight:bold;">TOTAL AMOUNT</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($local_price_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($vat_price_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($gross_local , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($gross_local_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($total_net , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($total_usd , 2 ,  "." , ",").'</td>

					</tr>
		';
		$approval_information = '<tr>
			<td style="background-color:#ddd; font-weight:bold">NO</td>
			<td style="background-color:#ddd; font-weight:bold">Authority Name</td>
			<td style="background-color:#ddd; font-weight:bold">Designation</td>
			<td style="background-color:#ddd; font-weight:bold">Approval Status</td>
			<td style="background-color:#ddd; font-weight:bold">Approval Date</td>
		</tr>';
		
		//approval information dynamic data
		$approval_array_id = 0;
		$proc_proctypeID =  $procurement_data->get('proc_proctype');
			//get user detail who has created the request
			$creator_user_data = Vtiger_Record_Model::getInstanceById($creatorID, "Users");
			//echo "<pre>"; print_r($creator_user_data); echo "</pre>";exit;
			$creator_email = $creator_user_data->get('email1');
			$creator_name = $creator_user_data->get('first_name').' '.$creator_user_data->get('last_name');
			//get location and if not ALA then get GM information
			$office_location = $procurement_data->get('proc_location');
			$sql_location = $adb->pquery("SELECT cf_1559 FROM `vtiger_locationcf` WHERE `locationid` = '$office_location'");
			$location_detail = $adb->fetch_array($sql_location);
			$location_status = $location_detail['cf_1559'];
			$sql_userprofile = $adb->pquery("SELECT `userlistid`, `cf_3355`, `cf_3385`, `cf_3421`
									FROM `vtiger_userlistcf`
									WHERE `cf_3355` = '$creator_email'");
			$userprofile = $adb->fetch_array($sql_userprofile);
			$gm_id = $userprofile['cf_3385'];
			$sql_gm_email = $adb->pquery("SELECT uf.cf_3355 as email, u.name as name FROM `vtiger_userlistcf` as uf inner join `vtiger_userlist` as u on uf.userlistid = u.userlistid  WHERE uf.`userlistid` = '$gm_id'");
			$gm = $adb->fetch_array($sql_gm_email);
			$gm_email = $gm['email'];
			if($gm_email=='s.khan@globalinklogistics.com') //add approval entry as Siddique Khan is the GM of request creator
			{
				$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$creatorID' and procurement_id='".$request->get('record')."'");
				if($adb->num_rows($procurement_gm_approval_result)>0){
					$row = $adb->query_result_rowdata($procurement_gm_approval_result, 0);
				$approval_array_id++; //auto increment to use in next loop also
				$approval_information .= '<tr>
												<td>'.$approval_array_id.'</td>
												<td>'.$creator_name.'</td>
												<td>GM</td>
												<td>'.$row['approval_status'].'</td>
												<td>'.$row['date_time_of_approval'].'</td>
											</tr>';
				}
			}
			elseif($location_status != 'ALA'){
				
					//now get gm approval status from vtiger_send_approval
					$get_gm_userid = $adb->pquery("SELECT * FROM `vtiger_users` where email1 = '".$gm_email."' limit 1"); //get gm userid from users table
					$gm_userid = $adb->query_result($get_gm_userid, 0, 'id');
					$gm_name = $adb->query_result($get_gm_userid, 0, 'first_name')." ".$adb->query_result($get_gm_userid, 0, 'last_name');;
					/*Query to check if this GM already in approval list*/
					$procurementauthorities_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` 
					inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid
					inner join vtiger_procurementapproval on vtiger_procurementapprovalcf.procurementapprovalid=vtiger_procurementapproval.procurementapprovalid 
					left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='$recordId' 
					where procapproval_proctype = $proc_proctypeID AND vtiger_procurementapprovalcf.procapproval_person='$gm_userid' AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
					if($adb->num_rows($procurementauthorities_result)==0){ //if not in approval list then add GM below in the approval information
					{
						$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$gm_userid' and procurement_id='$recordId'");
						if($adb->num_rows($procurement_gm_approval_result)>0){
							$row = $adb->query_result_rowdata($procurement_gm_approval_result, 0);
							//echo "<pre>"; print_r($row); echo "</pre>";
							$approval_array_id++; //auto increment to use in next loop also
							$approval_information .= '<tr>
															<td>'.$approval_array_id.'</td>
															<td>'.$gm_name.'</td>
															<td>GM</td>
															<td>'.$row['approval_status'].'</td>
															<td>'.$row['date_time_of_approval'].'</td>
														</tr>';
						}
					}
					
				} //end location check
			}
			

			
			$creatorUser_name = $creator_user_data->get('first_name').' '.$creator_user_data->get('last_name');
			//below make approval authorities list dynamically
			//get all approval authorities from vtiger_procurementapprovalcf for requested item, left join with vtiger_send_approval & get approval status			
			$procurementauthorities_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` 
			inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid
			inner join vtiger_procurementapproval on vtiger_procurementapprovalcf.procurementapprovalid=vtiger_procurementapproval.procurementapprovalid 
			left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='$recordId' 
			where procapproval_proctype = $proc_proctypeID AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
			if($adb->num_rows($procurementauthorities_result)>0){
				//echo "<pre>"; print_r($procurementauthorities_result); echo "</pre>";
				for($j=0; $j<$adb->num_rows($procurementauthorities_result); $j++) { //loop to build array for approval details  
					//echo "user_id: ".$adb->query_result($procurementauthorities_result, $j, 'procapproval_person')." sequence: ".$adb->query_result($procurementauthorities_result, $j, 'cf_7580')." usd_limit: ".$adb->query_result($procurementauthorities_result, $j, 'cf_7602')." <br>";
					$row = $adb->query_result_rowdata($procurementauthorities_result, $j);
					$approval_user_data = Vtiger_Record_Model::getInstanceById($row['procapproval_person'], "Users");
					$approval_user_name  = $approval_user_data->get('first_name')." ".$approval_user_data->get('last_name');
					$approval_array_id++;
					$approval_information .= '<tr>
													<td>'.$approval_array_id.'</td>
													<td>'.$approval_user_name.'</td>
													<td>'.$row['name'].'</td>
													<td>'.$row['approval_status'].'</td>
													<td>'.$row['date_time_of_approval'].'</td>
												</tr>';
					
					//echo "<pre>"; print_r($row); echo "</pre>";
				}
			}
		//approval information ends 
		
		$dep_id = $procurement_data->get('proc_department');
		
		$dep_info = Vtiger_Record_Model::getInstanceById($dep_id, "Department");
		$department = $dep_info->get('cf_1542');
		
		$company_info = Vtiger_Record_Model::getInstanceById($procurement_data->get('proc_title'), "Company");
		$procurement_title = $company_info->get('cf_996'); //company-code
		
		$mode_detail = '';
		if($ProcurementType_code=='FL') //Fleet Request
		{
			$mode_detail = '
				<tr>
					<td style="background-color:#ddd; font-weight:bold">Fleet Mode</td>
					<td>'.$procurement_data->get('proc_purchase_type_fleet').'</td>';
					if($procurement_data->get('proc_purchase_type_fleet')=='Truck' || $procurement_data->get('proc_purchase_type_fleet')!='Inventory')
					{
					$mode_detail .= '<td style="background-color:#ddd; font-weight:bold">Vehicle Number</td>
					<td>'.$procurement_data->getDisplayValue('proc_vehicle_no').'</td>
					<td style="background-color:#ddd; font-weight:bold">Vehicle Milage</td>
					<td>'.$procurement_data->get('proc_vehicle_mileage').'</td></tr>';
					}
					else
					{
						$mode_detail .= '<td style="background-color:#ddd; font-weight:bold">-</td>
					<td></td>
					<td style="background-color:#ddd; font-weight:bold">-</td>
					<td></td></tr>';
					}
		}
		if($ProcurementType_code=='PM') //Packing Material Request
		{
			$mode_detail = '
				<tr>
					<td style="background-color:#ddd; font-weight:bold">Packaging Mode</td>
					<td>'.$procurement_data->get('proc_purchase_type_pm').'</td>';
					if($procurement_data->get('proc_purchase_type_pm')=='Direct Pack-Out' || $procurement_data->get('proc_purchase_type_pm')!='Own-Stock')
					{
					$mode_detail .= '<td style="background-color:#ddd; font-weight:bold">Job Number</td>
					<td>'.$procurement_data->getDisplayValue('proc_job_no').'</td></tr>';
					}
					else
					{
					$mode_detail .= '<td style="background-color:#ddd; font-weight:bold"></td>
					<td></td>
					<td style="background-color:#ddd; font-weight:bold">-</td>
					<td></td></tr>';
					}
		}
		//echo $ProcurementType_code;exit;
		//$currency_id = $company_info->get('cf_1459');
		//$currency_info = $adb->pquery('select * from vtiger_currency_info where id = $currency_id');
		//$currency_code = $adb->query_result($currency_info, 0, 'currency_code');
		$this->setValue('procurement_items',$procurement_items);
		$this->setValue('approval_information',$approval_information);
		$this->setValue('procurement_no',$procurement_data->get('proc_request_no'));
		$this->setValue('procurement_date',$procurement_data->get('createdtime'));
		$this->setValue('procurement_creator',$creatorUser_name);
		
		$this->setValue('procurement_location',$location_status);
		$this->setValue('procurement_department',$department);
		$this->setValue('procurement_title',$procurement_title);
		$this->setValue('procurement_status',$procurement_data->get('proc_order_status'));
		$this->setValue('mode_detail',$mode_detail);
		$this->setValue('proc_proctype',$procurement_data->getDisplayValue('proc_proctype'));
		$this->setValue('procurement_which_department',$procurement_data->getDisplayValue('proc_which_department'));
		$this->setValue('procurement_document_no',$procurement_data->get('proc_doc_no'));
		
		$this->setValue('procurement_doc_type',$procurement_data->getDisplayValue('proc_doc_type'));
		$this->setValue('procurement_date_of_issue',$procurement_data->get('proc_issue_date'));
		$this->setValue('procurement_supplier',strip_tags($procurement_data->getDisplayValue('proc_supplier')));
		
		$this->setValue('company_status',$procurement_data->getDisplayValue('proc_company_status'));
		$this->setValue('supplier_location',$procurement_data->get('proc_supplier_location'));
		$this->setValue('procurement_currency_rate',number_format($procurement_data->get('proc_currency_usd_rate') , 2 ,  "." , ","));
		$this->setValue('procurement_loc_amount',number_format($procurement_data->get('proc_loc_currency') , 2 ,  "." , ","));
		$this->setValue('procurement_total_usd',number_format($procurement_data->get('proc_total_amount') , 2 ,  "." , ","));
		$this->setValue('procurement_comments',$procurement_data->get('proc_comments'));
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->SetDefaultFontSize(10.5);
		//$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//echo $subject;
		//exit;
		//$subject = 'Ruslan';
		
		$pdf_name = "pdf_docs/procurement_".$record.time().".pdf";
		
		
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

    private function splitText($a) {
		$d = [];
		$c = strlen($a[0]);
		$e = sizeof($a[1]);
		for ($b=0;$b<$e;$b++) {
			if ($c<$a[1][$b][1]) {
				$d[$b] = substr($a[0],$a[1][$b][0],$c);
			} else $d[$b] = substr($a[0],$a[1][$b][0],$a[1][$b][1]);
		}
		return $d;
	}
}