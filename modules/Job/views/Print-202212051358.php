<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_Print_View extends Vtiger_Print_View {
	
	
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
		$rtype = $request->get('rtype');	
		$type = $request->get('type');	
		
		if(!empty($rtype) && $type == 'qsr')
		{
			$this->print_qsr($request);
		}
		elseif(!empty($type) && $type == 'sticker') {
			$this->print_sticker($request);
		}
		elseif(!empty($type) && $type == 'pod' )
		{
			$this->print_pod($request);
		}
		elseif(!empty($type) && $type == 'podeng' ) //add by Azhar 15 nov 2022
		{
			$this->print_pod_english($request);
		}
		elseif(!empty($type) && $type == 'edocuments' ){
			$this->print_edocuments($request);
		}
		elseif(!empty($type) && $type == 'edocs' ){
			$this->print_edocs($request);
		}
		else
		{
			$this->print_coverletter($request);
		}
		
	}




	public function print_edocsold($request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$job_info_detail = Vtiger_Record_Model::getInstanceById($record, 'Job');


		

		global $adb;
		$query ="SELECT DISTINCT ercds.document_name,vtiger_echecklist.name AS doc_name,ecf.jobid
					 FROM vtiger_edocumentsrecords AS ercds
					 INNER JOIN vtiger_edocumentscf AS ecf ON ecf.jobid = ercds.job_id
					 LEFT JOIN vtiger_echecklist ON vtiger_echecklist.echecklistid = ercds.document_name
					 WHERE ercds.deleted = 0
					 AND ecf.jobid IN ('$record')
					 LIMIT 0,50";
			$doc= $adb->pquery($query, array());
			$doc_count = $adb->num_rows($doc);
			
			$docs = array();
			for($j=0; $j<$adb->num_rows($doc); $j++) {
				$docs[$j]['job_id'] = $adb->query_result($doc, $j, 'jobid');
				$docs[$j]['document_type'] = $adb->query_result($doc, $j, 'doc_name');
				//$docs[$j]['edocumentsid'] = $adb->query_result($doc, $j, 'edocumentsid');
				$docs[$j]['document_name'] = $adb->query_result($doc, $j, 'document_name');

			}

			$document = $this->loadTemplate('printtemplates/job/EDocuments/edocs.html');

			

			if (($noofrows > 0)){
				 $edocuments = "<h4> EDocuments </h4>";
				 $edocuments .= "<table width='850' border=0 cellspacing=4 cellpadding=4 border=1>";
				foreach($docs as $apr){
			    $edocuments .= "<tr> <td> $apr[document_name] </td>
								  <td> $apr[document_type] </td>
								  <td> $apr[job_id] </td> 
							</tr>";
			  }
				$edocuments .= '</table>';
			}

			$this->setValue('approval_history', $edocuments);
			

			include('include/mpdf60/mpdf.php');

		

	  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
	  		$mpdf->charset_in = 'utf8';
			
			$mpdf->list_indent_first_level = 0; 

			$mpdf->SetDefaultFontSize(14);
			$mpdf->list_indent_first_level = 0;
			$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/

			//echo $subject;
			//exit;
			//$subject = 'Ruslan';
			
			$pdf_name = "pdf_docs/edocs_".$record.".pdf";
			$mpdf->Output($pdf_name, 'F');
			header('Location:'.$pdf_name);
			exit;
		
	}

	/*
	public function print_edocs($request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');	
		$rtype = $request->get('rtype');	
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
		
		// Basic information
		$owner_user_info = Users_Record_Model::getInstanceById($job_details->get('assigned_user_id'), 'Users');
		$assigned_to = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		
		// Get Job file details
		$job_ref = $job_details->get('cf_1198');		
		$account_info = Users_Record_Model::getInstanceById($job_details->get('cf_1441'), 'Accounts');
		$customer = $account_info->get('cf_2395');
		
		
		//$subject = $job_details->get('name');
		//$created_date = date('d.m.Y', strtotime($job_details->get('createdtime')));
		
		//$customer_account = $job_details->get('cf_3489');
		

		//echo "<pre>";
		//print_r($job_details);
		//die();

		$station = $job_details->get('cf_1510');
		//$consignee = $job_details->get('cf_1074');
		//$origin = $job_details->get('cf_1508');
		//$destination = $job_details->get('cf_1510');

		
		$userdepartment = $owner_user_info->get('department');
		$userinfo = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		$dateadded = $job_details->get('createdtime');

		global $adb;
		$query ="SELECT DISTINCT ercds.document_name,vtiger_echecklist.name AS doc_name,vtiger_echecklistcf.cf_8382 AS required_doc,ecf.jobid
				FROM vtiger_edocumentsrecords AS ercds
				INNER JOIN vtiger_edocumentscf AS ecf ON ecf.jobid = ercds.job_id
				LEFT JOIN vtiger_echecklist ON vtiger_echecklist.echecklistid = ercds.document_name
				INNER JOIN vtiger_echecklistcf ON vtiger_echecklistcf.echecklistid = ercds.document_name
				WHERE ercds.deleted = 0
				AND ecf.jobid IN ('$record')
				LIMIT 0,50";
			$doc= $adb->pquery($query, array());
			$doc_count = $adb->num_rows($doc);
			
			$docs = array();
			for($j=0; $j<$adb->num_rows($doc); $j++) {
				$docs[$j]['job_id'] = $adb->query_result($doc, $j, 'jobid');
				$docs[$j]['document_type'] = $adb->query_result($doc, $j, 'doc_name');
				$docs[$j]['required_doc'] = $adb->query_result($doc, $j, 'required_doc');
				$docs[$j]['document_name'] = $adb->query_result($doc, $j, 'document_name');

			}

			$qry ="SELECT  ercds.*
					 FROM vtiger_edocumentsrecords AS ercds
					 WHERE ercds.deleted = 0
					 AND ercds.job_id = '$record'";
			$link= $adb->pquery($qry, array());
			$link_count = $adb->num_rows($link);

			$doclinks = array();
			for($j=0; $k<$link_count; $k++) {
				$doclinks[$k]['job_id'] = $adb->query_result($link, $k, 'job_id');
				$doclinks[$k]['document_name'] = $adb->query_result($link, $k, 'document_name');
				$doclinks[$k]['upload_document'] = $adb->query_result($link, $k, 'upload_document');
				$doclinks[$k]['upload_document_clone'] = $adb->query_result($link, $k, 'upload_document_clone');
				$doclinks[$k]['doc_path'] = $adb->query_result($link, $k, 'doc_path');
				$doclinks[$k]['file_name'] = $adb->query_result($link, $k, 'file_name');
			}

		if (($doc_count > 0)){
				 $edocuments = "<h4> EDocuments List</h4>";
				 $edocuments .= "<table width='100%' border=0 cellspacing=0 cellpadding=4 border=1>";

				 $edocuments .= "<tr>
								  <th aligh='center'> Document Type</th>
								 
								  <th align='center'> Documents </th> 
							</tr>";

			foreach($docs as $apr){
			    $edocuments .= "<tr> 
								  <td> $apr[document_type]";
				if ($apr['required_doc']==1) {		 	
					$edocuments .= "&emsp;<span class='redColor' style='color:red;'>*<div></div></span>";
				}
				$edocuments .= " </td><td>"; 
					
								
					

					foreach ($doclinks as $link) {
						if ($apr['document_name']==$link['document_name']) {
								
							$edocuments .= "$link[file_name]&ensp;|";
							//$edocuments .= "count($doclinks)";
						}
					}

				

					$edocuments .= "</td></tr>";
			  }//endforeach

				$edocuments .= '</table>';
			}


	
		$document = $this->loadTemplate('printtemplates/Job/EDocuments/edocs.html');
		
		// Basic information		
		$this->setValue('assigned_to',$assigned_to, ENT_QUOTES, "UTF-8");
		$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
		$this->setValue('userdepartment',$userdepartment, ENT_QUOTES, "UTF-8");
		$this->setValue('dateadded',$dateadded, ENT_QUOTES, "UTF-8");
		$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");

		$this->setValue('userinfo',$userinfo, ENT_QUOTES, "UTF-8");


		//Shipping Details
		$this->setValue('station',$station, ENT_QUOTES, "UTF-8");
		//$this->setValue('consignee',$consignee, ENT_QUOTES, "UTF-8");
		//$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
		//$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
		$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");

		$this->setValue('edocuments', $edocuments);
	
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); 
  		//???????????? ????????????, ?????????????? ??.??.??.
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2);//?????????????????? pdf

		//$account_name = html_entity_decode($to);
		//$account = str_replace("/", "", $account_name);
		
		if ($rtype == 'origin') $fname = 'qsro_'.$record; else if ($rtype == 'destination') $fname = 'qsrd_'.$record; 
		$pdf_name = "pdf_docs/edocs_".$record.".pdf";
				
		$mpdf->Output($pdf_name, 'F');		
		header('Location:'.$pdf_name);
		exit;		
	}*/



	public function print_edocuments($request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');	
		$rtype = $request->get('rtype');	
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
		
		// Basic information
		$owner_user_info = Users_Record_Model::getInstanceById($job_details->get('assigned_user_id'), 'Users');
		$assigned_to = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		
		// Get Job file details
		$job_ref = $job_details->get('cf_1198');		
		$account_info = Users_Record_Model::getInstanceById($job_details->get('cf_1441'), 'Accounts');
		$customer = $account_info->get('cf_2395');
		
		
		//$subject = $job_details->get('name');
		//$created_date = date('d.m.Y', strtotime($job_details->get('createdtime')));
		
		//$customer_account = $job_details->get('cf_3489');
		

		//echo "<pre>";
		//print_r($job_details);
		//die();

		$station = $job_details->get('cf_1510');
		//$consignee = $job_details->get('cf_1074');
		//$origin = $job_details->get('cf_1508');
		//$destination = $job_details->get('cf_1510');

		
		$userdepartment = $owner_user_info->get('department');
		$userinfo = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		$dateadded = $job_details->get('createdtime');

		global $adb;
		$query ="SELECT DISTINCT ercds.document_name,vtiger_echecklist.name AS doc_name,vtiger_echecklistcf.cf_8382 AS required_doc,ecf.jobid
				FROM vtiger_edocumentsrecords AS ercds
				INNER JOIN vtiger_edocumentscf AS ecf ON ecf.jobid = ercds.job_id
				LEFT JOIN vtiger_echecklist ON vtiger_echecklist.echecklistid = ercds.document_name
				INNER JOIN vtiger_echecklistcf ON vtiger_echecklistcf.echecklistid = ercds.document_name
				WHERE ercds.deleted = 0
				AND ecf.jobid IN ('$record')
				LIMIT 0,50";
			$doc= $adb->pquery($query, array());
			$doc_count = $adb->num_rows($doc);
			
			$docs = array();
			for($j=0; $j<$adb->num_rows($doc); $j++) {
				$docs[$j]['job_id'] = $adb->query_result($doc, $j, 'jobid');
				$docs[$j]['document_type'] = $adb->query_result($doc, $j, 'doc_name');
				$docs[$j]['required_doc'] = $adb->query_result($doc, $j, 'required_doc');
				$docs[$j]['document_name'] = $adb->query_result($doc, $j, 'document_name');

			}

			$qry ="SELECT  ercds.*
					 FROM vtiger_edocumentsrecords AS ercds
					 WHERE ercds.deleted = 0
					 AND ercds.job_id = '$record'";
			$link= $adb->pquery($qry, array());
			$link_count = $adb->num_rows($link);

			$doclinks = array();
			for($j=0; $k<$link_count; $k++) {
				$doclinks[$k]['job_id'] = $adb->query_result($link, $k, 'job_id');
				$doclinks[$k]['document_name'] = $adb->query_result($link, $k, 'document_name');
				$doclinks[$k]['upload_document'] = $adb->query_result($link, $k, 'upload_document');
				$doclinks[$k]['upload_document_clone'] = $adb->query_result($link, $k, 'upload_document_clone');
				$doclinks[$k]['doc_path'] = $adb->query_result($link, $k, 'doc_path');
				$doclinks[$k]['file_name'] = $adb->query_result($link, $k, 'file_name');
			}

		if (($doc_count > 0)){
				 $edocuments = "<h4> EDocuments List</h4>";
				 $edocuments .= "<table width='100%' border=0 cellspacing=0 cellpadding=4 border=1>";

				 $edocuments .= "<tr>
								  <th aligh='center'> Document Type</th>
								 
								  <th align='center'> Documents </th> 
							</tr>";

			foreach($docs as $apr){
			    $edocuments .= "<tr> 
								  <td> $apr[document_type]";
				if ($apr['required_doc']==1) {		 	
					$edocuments .= "&emsp;<span class='redColor' style='color:red;'>*<div></div></span>";
				}
				$edocuments .= " </td><td>"; 
					
								
					

					foreach ($doclinks as $link) {
						if ($apr['document_name']==$link['document_name']) {
								
							$edocuments .= "$link[file_name]&ensp;|";
							//$edocuments .= "count($doclinks)";
						}
					}

				

					$edocuments .= "</td></tr>";
			  }//endforeach

				$edocuments .= '</table>';
			}


	
		$document = $this->loadTemplate('printtemplates/Job/EDocuments/edocs.html');
		
		// Basic information		
		$this->setValue('assigned_to',$assigned_to, ENT_QUOTES, "UTF-8");
		$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
		$this->setValue('userdepartment',$userdepartment, ENT_QUOTES, "UTF-8");
		$this->setValue('dateadded',$dateadded, ENT_QUOTES, "UTF-8");
		$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");

		$this->setValue('userinfo',$userinfo, ENT_QUOTES, "UTF-8");


		//Shipping Details
		$this->setValue('station',$station, ENT_QUOTES, "UTF-8");
		//$this->setValue('consignee',$consignee, ENT_QUOTES, "UTF-8");
		//$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
		//$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
		$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");

		$this->setValue('edocuments', $edocuments);
	
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/

		//$account_name = html_entity_decode($to);
		//$account = str_replace("/", "", $account_name);
		
		if ($rtype == 'origin') $fname = 'qsro_'.$record; else if ($rtype == 'destination') $fname = 'qsrd_'.$record; 
		$pdf_name = "pdf_docs/edocs_".$record.".pdf";
				
		$mpdf->Output($pdf_name, 'F');		
		header('Location:'.$pdf_name);
		exit;		
	}






	public function print_edocs($request)
		{
			$moduleName = $request->getModule();
			$record = $request->get('record');	
			$rtype = $request->get('rtype');	
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
			
			// Basic information
			$owner_user_info = Users_Record_Model::getInstanceById($job_details->get('assigned_user_id'), 'Users');
			$assigned_to = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
			
			// Get Job file details
			$job_ref = $job_details->get('cf_1198');		
			$account_info = Users_Record_Model::getInstanceById($job_details->get('cf_1441'), 'Accounts');
			$customer = $account_info->get('cf_2395');
			
			
			//$subject = $job_details->get('name');
			//$created_date = date('d.m.Y', strtotime($job_details->get('createdtime')));
			
			//$customer_account = $job_details->get('cf_3489');
			

			//echo "<pre>";
			//print_r($job_details);
			//die();

			$station = $job_details->get('cf_1510');
			//$consignee = $job_details->get('cf_1074');
			//$origin = $job_details->get('cf_1508');
			//$destination = $job_details->get('cf_1510');

			
			$userdepartment = $owner_user_info->get('department');
			$userinfo = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
			$dateadded = $job_details->get('createdtime');

			global $adb;
			$query ="SELECT DISTINCT ercds.document_name,vtiger_echecklist.name AS doc_name,vtiger_echecklistcf.cf_8382 AS required_doc,ecf.jobid
					FROM vtiger_edocsrecords AS ercds
					INNER JOIN vtiger_edocscf AS ecf ON ecf.jobid = ercds.job_id
					LEFT JOIN vtiger_echecklist ON vtiger_echecklist.echecklistid = ercds.document_name
					INNER JOIN vtiger_echecklistcf ON vtiger_echecklistcf.echecklistid = ercds.document_name
					WHERE ercds.deleted = 0
					AND ecf.jobid IN ('$record')
					LIMIT 0,50";
				$doc= $adb->pquery($query, array());
				$doc_count = $adb->num_rows($doc);
				
				$docs = array();
				for($j=0; $j<$adb->num_rows($doc); $j++) {
					$docs[$j]['job_id'] = $adb->query_result($doc, $j, 'jobid');
					$docs[$j]['document_type'] = $adb->query_result($doc, $j, 'doc_name');
					$docs[$j]['required_doc'] = $adb->query_result($doc, $j, 'required_doc');
					$docs[$j]['document_name'] = $adb->query_result($doc, $j, 'document_name');

				}

				$qry ="SELECT  ercds.*
						 FROM vtiger_edocsrecords AS ercds
						 WHERE ercds.deleted = 0
						 AND ercds.job_id = '$record'";
				$link= $adb->pquery($qry, array());
				$link_count = $adb->num_rows($link);

				$doclinks = array();
				for($j=0; $k<$link_count; $k++) {
					$doclinks[$k]['job_id'] = $adb->query_result($link, $k, 'job_id');
					$doclinks[$k]['document_name'] = $adb->query_result($link, $k, 'document_name');
					$doclinks[$k]['upload_document'] = $adb->query_result($link, $k, 'upload_document');
					$doclinks[$k]['upload_document_clone'] = $adb->query_result($link, $k, 'upload_document_clone');
					$doclinks[$k]['doc_path'] = $adb->query_result($link, $k, 'doc_path');
					$doclinks[$k]['file_name'] = $adb->query_result($link, $k, 'file_name');
				}

			if (($doc_count > 0)){
					 $edocs = "<h4> EDocs List</h4>";
					 $edocs .= "<table width='100%' border=0 cellspacing=0 cellpadding=4 border=1>";

					 $edocs .= "<tr>
									  <th aligh='center'> Document Type</th>
									 
									  <th align='center'> Documents </th> 
								</tr>";

				foreach($docs as $apr){
				    $edocs .= "<tr> 
									  <td> $apr[document_type]";
					if ($apr['required_doc']==1) {		 	
						$edocs .= "&emsp;<span class='redColor' style='color:red;'>*<div></div></span>";
					}
					$edocs .= " </td><td>"; 
						
									
						

						foreach ($doclinks as $link) {
							if ($apr['document_name']==$link['document_name']) {
									
								$edocs .= "$link[file_name]&ensp;|";
								//$edocs .= "count($doclinks)";
							}
						}

					

						$edocs .= "</td></tr>";
				  }//endforeach

					$edocs .= '</table>';
				}


		
			$document = $this->loadTemplate('printtemplates/Job/EDocs/edocs.html');
			
			// Basic information		
			$this->setValue('assigned_to',$assigned_to, ENT_QUOTES, "UTF-8");
			$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
			$this->setValue('userdepartment',$userdepartment, ENT_QUOTES, "UTF-8");
			$this->setValue('dateadded',$dateadded, ENT_QUOTES, "UTF-8");
			$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");

			$this->setValue('userinfo',$userinfo, ENT_QUOTES, "UTF-8");


			//Shipping Details
			$this->setValue('station',$station, ENT_QUOTES, "UTF-8");
			//$this->setValue('consignee',$consignee, ENT_QUOTES, "UTF-8");
			//$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
			//$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
			$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");

			$this->setValue('edocs', $edocs);
		
			include('include/mpdf60/mpdf.php');

	  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
	  		$mpdf->charset_in = 'utf8';
			
			//$mpdf->list_indent_first_level = 0; 

			//$mpdf->SetDefaultFontSize(12);
			$mpdf->list_indent_first_level = 0;
			$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/

			//$account_name = html_entity_decode($to);
			//$account = str_replace("/", "", $account_name);
			
			if ($rtype == 'origin') $fname = 'qsro_'.$record; else if ($rtype == 'destination') $fname = 'qsrd_'.$record; 
			$pdf_name = "pdf_docs/edocs_".$record.".pdf";
					
			$mpdf->Output($pdf_name, 'F');		
			header('Location:'.$pdf_name);
			exit;		
		}



		









		public function print_sticker($request) {

			$moduleName = $request->getModule();
			$record = $request->get('record');
			$boxCount = $request->get('boxCount');

			$job_info_detail = Vtiger_Record_Model::getInstanceById($record, 'Job');
			//$account_info = Users_Record_Model::getInstanceById($job_info_detail->get('cf_1441'), 'Accounts');
			//$customer = $account_info->get('cf_2395');
			//$mode = str_replace(' |##|', ',', $job_info_detail->get('cf_1711'));

			$fromQ = $adb->pquery("SELECT country_name FROM countries WHERE country_code = ?", [$job_info_detail->get('cf_1504')]);
			$toQ = $adb->pquery("SELECT country_name FROM countries WHERE country_code = ?", [$job_info_detail->get('cf_1506')]);

			$from = $fromQ->fields[0] != '' ? $fromQ->fields[0].', ' : '';
			$to = $toQ->fields[0] != '' ? $toQ->fields[0].', ' : '';


			ob_clean();
			include ('include/mpdf60/mpdf.php');

			$mpdf = new mPDF('utf-8','A4','','',0,0,0,0,0,0);
			$mpdf->SetDisplayMode('fullpage');
			$mpdf->list_indent_first_level = 0; 
			$stylesheet = file_get_contents('include/mpdf60/examples/sticker.css');
			$mpdf->useFixedNormalLineHeight = true;
			$mpdf->WriteHTML($stylesheet,1);

			for($i=1; $i<=$boxCount; $i++) {
				if($i == 1 || ($i > 10 && substr($i, -1, 1) == 1)) {
					$html = '<table class="pdf-card-table"><tbody>';
				}
				if($i % 2 != 0) {
					$html .= '<tr>';
				}
				$html .= '<td class="pdf-card">
						<table align="center">
							<tr>
								<td><img src="include/logo.jpg" alt="Logo" width="149" /></td>
							</tr>
						</table>
						
						<table class="pdf-content-table">
							<tbody>
								<tr>
									<td class="pdf-td-title">Customer:</td>
									<td class="pdf-td-value">'.$job_info_detail->get('name').'</td>
								</tr>
								<tr>
									<td class="pdf-td-title">From:</td>
									<td class="pdf-td-value">'.$from.$job_info_detail->get('cf_1508').'</td>
								</tr>
								<tr>
									<td class="pdf-td-title">To:</td>
									<td class="pdf-td-value">'.$to.$job_info_detail->get('cf_1510').'</td>
								</tr>
								<tr>
									<td class="pdf-td-title">Mode:</td>
									<td class="pdf-td-value">'.str_replace(' |##|', ',', $job_info_detail->get('cf_1711')).'</td>
								</tr>
								<tr>
									<td class="pdf-td-title">Box #:</td>
									<td class="pdf-td-value pdf-td-value-box">'.$i.'</td>
								</tr>
							</tbody>
						</table>
						<table align="center">
							<tr>
								<td>removals@globalinklogistics.com | www.globalinklogistics.com</td>
							</tr>
						</table>
					</td>';
				if($i % 2 == 0) {
					$html .= '</tr>';
				}
				if($i % 10 == 0 || $i == $boxCount) {
					$html .= '</tbody></table>';
					$mpdf->WriteHTML($html, 2);
				}

			}

			$pdf_name = "pdf_docs/Sticker_Job_" . $record . ".pdf";
			$mpdf->Output($pdf_name, 'F');
			header('Location:'.$pdf_name);
			exit;
		}
		
		public function print_coverletter($request)
		{		
			//error_reporting(E_ALL);
			$moduleName = $request->getModule();
			$record = $request->get('record');
			
			$current_user = Users_Record_Model::getCurrentUserModel();		
			$job_info_detail = Vtiger_Record_Model::getInstanceById($record, 'Job');
			
					
			$document = $this->loadTemplate('printtemplates/Job/job_cover.html');
			
			$this->setValue('ref_no',$job_info_detail->get('cf_1198'));
			$this->setValue('CreatedTime',date('d/m/Y',strtotime($job_info_detail->get('createdtime'))));		
			
			$this->setValue('type',strtoupper($job_info_detail->get('cf_1200')));
			
			$this->setValue('shipper',$job_info_detail->get('cf_1072'));
			$this->setValue('consignee',$job_info_detail->get('cf_1074'));
			
			$this->setValue('origin_agent',$job_info_detail->getDisplayValue('cf_1082'));
			$this->setValue('destination_agent','');
			
			$this->setValue('waybill',$job_info_detail->get('cf_1096'));
			$this->setValue('pieces',$job_info_detail->get('cf_1429'));
			
			$this->setValue('weight',$job_info_detail->get('cf_1084').' '.$job_info_detail->get('cf_1520'));
			$this->setValue('volume',$job_info_detail->get('cf_1086').' '.$job_info_detail->get('cf_1522'));
			
			$this->setValue('commodity',$job_info_detail->get('cf_1518'));
			
			$job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');
			
			$this->setValue('coordinator',htmlentities($job_user_info->get('first_name').' '.$job_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
			
			$this->setValue('booker',$job_info_detail->getDisplayValue('cf_1441'));
			
			$this->setValue('remarks',$job_info_detail->get('cf_1102'));
			
			include('include/mpdf60/mpdf.php');

	  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
	  		$mpdf->charset_in = 'utf8';
			
			$mpdf->list_indent_first_level = 0; 

			$mpdf->SetDefaultFontSize(14);
			$mpdf->list_indent_first_level = 0;
			$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/

			//echo $subject;
			//exit;
			//$subject = 'Ruslan';
			
			$pdf_name = "pdf_docs/cover_letter_".$record.".pdf";
			
			
			$mpdf->Output($pdf_name, 'F');
			
			
			header('Location:'.$pdf_name);
			exit;	  
			
		}
		
		public function print_qsr($request)
		{
			$moduleName = $request->getModule();
			$record = $request->get('record');	
			$rtype = $request->get('rtype');	
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
			

		
			// Basic information
			$owner_user_info = Users_Record_Model::getInstanceById($job_details->get('assigned_user_id'), 'Users');
			$assigned_to = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
			
			// Get Job file details
			$job_ref = $job_details->get('cf_1198');		
			$account_info = Users_Record_Model::getInstanceById($job_details->get('cf_1441'), 'Accounts');
			$customer = $account_info->get('cf_2395');
			
			
			//$subject = $job_details->get('name');
			//$created_date = date('d.m.Y', strtotime($job_details->get('createdtime')));
			
			//$customer_account = $job_details->get('cf_3489');
			
			$shipper = $job_details->get('cf_1072');
			$consignee = $job_details->get('cf_1074');
			$origin = $job_details->get('cf_1508');
			$destination = $job_details->get('cf_1510');
			
			// cf_1072 shipper
	 
			$to = '';
			if ($job_details->get('cf_1082') != 0){
			  $account_info2 = Users_Record_Model::getInstanceById($job_details->get('cf_1082'), 'Accounts');
			  $booking_agent = $account_info2->get('cf_2395');
			} else $booking_agent = "";
	 
			$fname = '';
			
			if ($rtype == 'origin') $fname = 'QSR_origin'; else if ($rtype == 'destination') $fname = 'QSR_destination'; 
			$document = $this->loadTemplate('printtemplates/Job/'.$fname.'.html');
			
			// Basic information		
			$this->setValue('assigned_to',$assigned_to, ENT_QUOTES, "UTF-8");
			$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
			$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");

			//Shipping Details
			$this->setValue('shipper',$shipper, ENT_QUOTES, "UTF-8");
			$this->setValue('consignee',$consignee, ENT_QUOTES, "UTF-8");
			$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
			$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
			$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");

			
			include('include/mpdf60/mpdf.php');

	  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
	  		$mpdf->charset_in = 'utf8';
			
			//$mpdf->list_indent_first_level = 0; 

			//$mpdf->SetDefaultFontSize(12);
			$mpdf->list_indent_first_level = 0;
			$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/

			//$account_name = html_entity_decode($to);
			//$account = str_replace("/", "", $account_name);
			
			if ($rtype == 'origin') $fname = 'qsro_'.$record; else if ($rtype == 'destination') $fname = 'qsrd_'.$record; 
			$pdf_name = "pdf/".$fname.".pdf";
				
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

		public function print_pod($request)
		{
			$moduleName = $request->getModule();
			$record = $request->get('record');	
			//$rtype = $request->get('rtype');	
			
			//$current_user = Users_Record_Model::getCurrentUserModel();
			$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
			$shipper = $this->splitText([$job_details->get('cf_1072'),[[0,35],[35,50]]]);
			$PICK_UP = $job_details->get('cf_1516');
			$ETA = $job_details->get('cf_1591');
			$CONSIGNEE = $this->splitText([$job_details->get('cf_1074'),[[0,30],[30,50]]]);;
			$DELIVERY = $job_details->get('cf_1583');
			$FULL_COLLECTION_ADDRESS = $this->splitText([$job_details->get('cf_1512'),[[0,50],[50,50],[100,50]]]);
			$FULL_DELIVERY_ADDRESS = $this->splitText([$job_details->get('cf_1514'),[[0,50],[50,50],[100,50]]]);
			$WAYBILL = $job_details->get('cf_1096');
			$NOOFPIECES = $job_details->get('cf_1429');
			$WEIGHT_AND_VOLUME = $this->splitText([$job_details->get('cf_1084').' '.$job_details->get('cf_1520').' / '.$job_details->get('cf_1086').' '.$job_details->get('cf_1522'),[[0,24],[24,50]]]);
			$DESCRIPTION = $this->splitText([$job_details->get('cf_1547'),[[0,50],[50,50],[100,50],[150,50]]]);

			$document = $this->loadTemplate('printtemplates/Job/job_pod.html');
			$this->setValue('shipper0',$shipper[0]);
			$this->setValue('shipper1',$shipper[1]);
			$this->setValue('PICK_UP',$PICK_UP);
			$this->setValue('ETA',$ETA);
			$this->setValue('CONSIGNEE0',$CONSIGNEE[0]);
			$this->setValue('CONSIGNEE1',$CONSIGNEE[1]);
			$this->setValue('DELIVERY',$DELIVERY);
			$this->setValue('FULL_COLLECTION_ADDRESS0',$FULL_COLLECTION_ADDRESS[0]);
			$this->setValue('FULL_COLLECTION_ADDRESS1',$FULL_COLLECTION_ADDRESS[1]);
			$this->setValue('FULL_COLLECTION_ADDRESS2',$FULL_COLLECTION_ADDRESS[2]);
			$this->setValue('FULL_DELIVERY_ADDRESS0',$FULL_DELIVERY_ADDRESS[0]);
			$this->setValue('FULL_DELIVERY_ADDRESS1',$FULL_DELIVERY_ADDRESS[1]);
			$this->setValue('FULL_DELIVERY_ADDRESS2',$FULL_DELIVERY_ADDRESS[2]);
			$this->setValue('WAYBILL',$WAYBILL);
			$this->setValue('NOOFPIECES',$NOOFPIECES);
			$this->setValue('WEIGHT_AND_VOLUME0',$WEIGHT_AND_VOLUME[0]);
			$this->setValue('WEIGHT_AND_VOLUME1',$WEIGHT_AND_VOLUME[1]);
			$this->setValue('DESCRIPTION0',$DESCRIPTION[0]);
			$this->setValue('DESCRIPTION1',$DESCRIPTION[1]);
			$this->setValue('DESCRIPTION2',$DESCRIPTION[2]);
			$this->setValue('DESCRIPTION3',$DESCRIPTION[3]);
			$HTML = '<!doctype html>
			<html>
			<head>
			<meta charset="UTF-8">
			
			</head>
			<body>
			<div>
				<div class="b1"><img src="include/logo_doc.jpg" style="float: left;"></div>
				<div class="b2">
					60 Nutsubidze street, Tbilisi, Georgia 0186<br>
					Tel.: +995 32 2000238; 2208147<br>
					Fax.: +995 32 2208147<br>
					E-mail: tbilisi@globalinklogistics.com<br>
					Web Site: <span class="b6">www.globalinklogistics.com</span>
				</div>
				<div class="b3">
				????????????????????? ??????????????????????????? ??????????????????:<br>
				<div class="b4">DELIVERY REPORT</div>
				</div>
				<div class="b5">
					<div class="b7">
						<div class="b8">
							<div class="b10">?????????????????????????????????:</div>
							<div class="b11">SHIPPER:</div>
							<div class="b12"></div>
							<div class="info-1">'.$shipper[0].'</div>
							<div class="b14"></div>
							<div class="info-2">'.$shipper[1].'</div>
							<div class="b10 b13">?????????????????????????????????:</div>
							<div class="b11">CONSIGNEE:</div>
							<div class="b15"></div>
							<div class="info-4">'.$CONSIGNEE[0].'</div>
							<div class="b16"></div>
							<div class="info-2">'.$CONSIGNEE[1].'</div>
						</div>
						<div class="b9">
							<div class="b10">????????????????????? ??????????????????????????? ??????????????????:</div>
							<div class="b11">CARGO ARRIVAL DATE:</div>
							<div class="b12 b17"></div>
							<div class="info-3">'.$ETA.'</div>
							<div class="b18">?????????????????? ??????????????????:</div>
							<div class="b11">PICK UP TIME:</div>
							<div class="b12 b19"></div>
							<div class="info-5">'.$PICK_UP.'</div>
							<div class="b18">????????????????????? ??????????????????:</div>
							<div class="b11">DELIVERY TIME:</div>
							<div class="b12 b19"></div>
							<div class="info-5">'.$DELIVERY.'</div>
						</div>
					</div>
					<div class="b7">
						<div class="b8">
							<div class="b10">?????????????????? ??????????????????:</div>
							<div class="b11">FULL COLLECTION ADDRESS:</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_COLLECTION_ADDRESS[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_COLLECTION_ADDRESS[1].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_COLLECTION_ADDRESS[2].'</div>
						</div>
						<div class="b9">
							<div class="b10">????????????????????? ???????????????????????????????????? ??????????????????:</div>
							<div class="b11">FULL DELIVERY ADDRESS:</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_DELIVERY_ADDRESS[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_DELIVERY_ADDRESS[1].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_DELIVERY_ADDRESS[2].'</div>
						</div>
					</div>
					<div class="b7 b21">
						<div class="b8 b22">
							<div class="b10">????????????????????? ????????????????????????:</div>
							<div class="b11">SHIPPING DETAILS:</div>
							<div class="b10 b23">?????????????????????????????????????????? <strong>???</strong></div>
							<div class="b11">WAYBILL ???:</div>
							<div class="b15 b24"></div>
							<div class="info-6">'.$WAYBILL.'</div>
							<div class="b10">??????????????????????????? ???????????????????????????:</div>
							<div class="b11">NO. OF PIECES:</div>
							<div class="b15 b24"></div>
							<div class="info-6">'.$NOOFPIECES.'</div>
							<div class="b10">????????????????????? ????????????:</div>
							<div class="b11">WEIGHT/VOLUME:</div>
							<div class="b15 b24 b34"></div>
							<div class="info-7">'.$WEIGHT_AND_VOLUME[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$WEIGHT_AND_VOLUME[1].'</div>
						</div>
						<div class="b9 b22">
							<div class="b10">????????????????????? ??????????????????????????????/??????????????????????????????:</div>
							<div class="b11">DESCRIPTION:</div>
							<div class="b14 b25"></div>
							<div class="info-2">'.$DESCRIPTION[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$DESCRIPTION[1].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$DESCRIPTION[2].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$DESCRIPTION[3].'</div>
						</div>
					</div>
				</div>
				<div class="b26">?????? ?????????????????? ??????????????????????????? ?????????????????????????????? (POD) ??????????????????????????????, ?????????????????????????????? ???????????????????????? ??????????????????????????? ??????????????????????????? ?????????????????????????????? ???????????????????????? ?????? ???????????????????????? ????????????????????? ?????????????????????, ??????????????? ???????????? ???????????????????????????????????????????????? ??????????????????????????? ?????????????????????????????? ???????????????????????????????????????.</div>
				<div class="b26">By signing this Proof Of Delivery (POD)document, Consignee/Signee bears full responsibility for any discrepancies in the
			number of boxes that may arise after inspection during the Delivery Phase/Completion of Delivery.</div>
				<div class="b26 b27">????????????????????????:</div>
				<div class="b26 b27 b28"><strong>COMMENTS:</strong></div>
				<div class="b29"></div>
				<div class="b29"></div>
				<div class="b29"></div>
				<div class="b29"></div>
				<div class="b26 b27 b33">???????????????????????? ??????????????????/??????????????? & ???????????????????????????:</div>
				<div class="b26 b27 b28"><strong>RECEIVERS NAME & SIGNATURE:</strong></div>
				<div class="b32"></div>
				<div class="b30"><strong>Date/</strong>??????????????????:</div>
				<div class="b31"></div>
			</div>

			</body>
			</html>';
			$HTML = mb_convert_encoding($HTML, 'UTF-8', 'UTF-8');
			include('include/mpdf60/mpdf.php');
			$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
	  		$mpdf->charset_in = 'utf8';
			
			$mpdf->list_indent_first_level = 0; 

			$mpdf->SetDefaultFontSize(14);
			$mpdf->list_indent_first_level = 0;
			$stylesheet = file_get_contents('printtemplates/Job/style_pod.css');
			$mpdf->WriteHTML($stylesheet,1);
			//$mpdf->WriteHTML($HTML);
			$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/


			$pdf_name = 'pdf_docs/proof_of_delivery.pdf';


			//$mpdf->WriteHTML($html_2); /*?????????????????? pdf*/
			ob_clean();
			$mpdf->Output($pdf_name, 'F');

			//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
			header('Location:'.$pdf_name);



			exit;
		}
		
		//add by Azhar - 15 Nov 2022
		public function print_pod_english($request)
		{
			$moduleName = $request->getModule();
			$record = $request->get('record');	
			//$rtype = $request->get('rtype');	
			
			//$current_user = Users_Record_Model::getCurrentUserModel();
			$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
			$shipper = $this->splitText([$job_details->get('cf_1072'),[[0,35],[35,50]]]);
			$PICK_UP = $job_details->get('cf_1516');
			$ETA = $job_details->get('cf_1591');
			$CONSIGNEE = $this->splitText([$job_details->get('cf_1074'),[[0,30],[30,50]]]);;
			$DELIVERY = $job_details->get('cf_1583');
			$FULL_COLLECTION_ADDRESS = $this->splitText([$job_details->get('cf_1512'),[[0,50],[50,50],[100,50]]]);
			$FULL_DELIVERY_ADDRESS = $this->splitText([$job_details->get('cf_1514'),[[0,50],[50,50],[100,50]]]);
			$WAYBILL = $job_details->get('cf_1096');
			$NOOFPIECES = $job_details->get('cf_1429');
			$WEIGHT_AND_VOLUME = $this->splitText([$job_details->get('cf_1084').' '.$job_details->get('cf_1520').' / '.$job_details->get('cf_1086').' '.$job_details->get('cf_1522'),[[0,24],[24,50]]]);
			$DESCRIPTION = $this->splitText([$job_details->get('cf_1547'),[[0,50],[50,50],[100,50],[150,50]]]);

			$document = $this->loadTemplate('printtemplates/Job/job_pod_english.html');
			$this->setValue('shipper0',$shipper[0]);
			$this->setValue('shipper1',$shipper[1]);
			$this->setValue('PICK_UP',$PICK_UP);
			$this->setValue('ETA',$ETA);
			$this->setValue('CONSIGNEE0',$CONSIGNEE[0]);
			$this->setValue('CONSIGNEE1',$CONSIGNEE[1]);
			$this->setValue('DELIVERY',$DELIVERY);
			$this->setValue('FULL_COLLECTION_ADDRESS0',$FULL_COLLECTION_ADDRESS[0]);
			$this->setValue('FULL_COLLECTION_ADDRESS1',$FULL_COLLECTION_ADDRESS[1]);
			$this->setValue('FULL_COLLECTION_ADDRESS2',$FULL_COLLECTION_ADDRESS[2]);
			$this->setValue('FULL_DELIVERY_ADDRESS0',$FULL_DELIVERY_ADDRESS[0]);
			$this->setValue('FULL_DELIVERY_ADDRESS1',$FULL_DELIVERY_ADDRESS[1]);
			$this->setValue('FULL_DELIVERY_ADDRESS2',$FULL_DELIVERY_ADDRESS[2]);
			$this->setValue('WAYBILL',$WAYBILL);
			$this->setValue('NOOFPIECES',$NOOFPIECES);
			$this->setValue('WEIGHT_AND_VOLUME0',$WEIGHT_AND_VOLUME[0]);
			$this->setValue('WEIGHT_AND_VOLUME1',$WEIGHT_AND_VOLUME[1]);
			$this->setValue('DESCRIPTION0',$DESCRIPTION[0]);
			$this->setValue('DESCRIPTION1',$DESCRIPTION[1]);
			$this->setValue('DESCRIPTION2',$DESCRIPTION[2]);
			$this->setValue('DESCRIPTION3',$DESCRIPTION[3]);
			$HTML = '<!doctype html>
			<html>
			<head>
			<meta charset="UTF-8">
			
			</head>
			<body>
			<div>
				<div class="b1"><img src="include/logo_doc.jpg" style="float: left;"></div>
				<div class="b2">
					60 Nutsubidze street, Tbilisi, Georgia 0186<br>
					Tel.: +995 32 2000238; 2208147<br>
					Fax.: +995 32 2208147<br>
					E-mail: tbilisi@globalinklogistics.com<br>
					Web Site: <span class="b6">www.globalinklogistics.com</span>
				</div>
				<div class="b3">
				<br>
				<div class="b4">DELIVERY REPORT</div>
				</div>
				<div class="b5">
					<div class="b7">
						<div class="b8">
							
							<div class="b11">SHIPPER:</div>
							<div class="b12"></div>
							<div class="info-1">'.$shipper[0].'</div>
							<div class="b14"></div>
							<div class="info-2">'.$shipper[1].'</div>
							
							<div class="b11">CONSIGNEE:</div>
							<div class="b15"></div>
							<div class="info-4">'.$CONSIGNEE[0].'</div>
							<div class="b16"></div>
							<div class="info-2">'.$CONSIGNEE[1].'</div>
						</div>
						<div class="b9">
							
							<div class="b11">CARGO ARRIVAL DATE:</div>
							<div class="b12 b17"></div>
							<div class="info-3">'.$ETA.'</div>
							
							<div class="b11">PICK UP TIME:</div>
							<div class="b12 b19"></div>
							<div class="info-5">'.$PICK_UP.'</div>
							
							<div class="b11">DELIVERY TIME:</div>
							<div class="b12 b19"></div>
							<div class="info-5">'.$DELIVERY.'</div>
						</div>
					</div>
					<div class="b7">
						<div class="b8">
							
							<div class="b11">FULL COLLECTION ADDRESS:</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_COLLECTION_ADDRESS[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_COLLECTION_ADDRESS[1].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_COLLECTION_ADDRESS[2].'</div>
						</div>
						<div class="b9">
							
							<div class="b11">FULL DELIVERY ADDRESS:</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_DELIVERY_ADDRESS[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_DELIVERY_ADDRESS[1].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$FULL_DELIVERY_ADDRESS[2].'</div>
						</div>
					</div>
					<div class="b7 b21">
						<div class="b8 b22">
							
							<div class="b11">SHIPPING DETAILS:</div>
							
							<div class="b11">WAYBILL ???:</div>
							<div class="b15 b24"></div>
							<div class="info-6">'.$WAYBILL.'</div>
							
							<div class="b11">NO. OF PIECES:</div>
							<div class="b15 b24"></div>
							<div class="info-6">'.$NOOFPIECES.'</div>
							
							<div class="b11">WEIGHT/VOLUME:</div>
							<div class="b15 b24 b34"></div>
							<div class="info-7">'.$WEIGHT_AND_VOLUME[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$WEIGHT_AND_VOLUME[1].'</div>
						</div>
						<div class="b9 b22">
							
							<div class="b11">DESCRIPTION:</div>
							<div class="b14 b25"></div>
							<div class="info-2">'.$DESCRIPTION[0].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$DESCRIPTION[1].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$DESCRIPTION[2].'</div>
							<div class="b14 b20"></div>
							<div class="info-2">'.$DESCRIPTION[3].'</div>
						</div>
					</div>
				</div>
				
				<div class="b26">By signing this Proof Of Delivery (POD)document, Consignee/Signee bears full responsibility for any discrepancies in the
			number of boxes that may arise after inspection during the Delivery Phase/Completion of Delivery.</div>
				
				<div class="b26 b27 b28"><strong>COMMENTS:</strong></div>
				<div class="b29"></div>
				<div class="b29"></div>
				<div class="b29"></div>
				<div class="b29"></div>
				
				<div class="b26 b27 b28"><strong>RECEIVERS NAME & SIGNATURE:</strong></div>
				<div class="b32"></div>
				<div class="b30"><strong>Date/</strong>??????????????????:</div>
				<div class="b31"></div>
			</div>

			</body>
			</html>';
			$HTML = mb_convert_encoding($HTML, 'UTF-8', 'UTF-8');
			include('include/mpdf60/mpdf.php');
			$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*???????????? ????????????, ?????????????? ??.??.??.*/
	  		$mpdf->charset_in = 'utf8';
			
			$mpdf->list_indent_first_level = 0; 

			$mpdf->SetDefaultFontSize(14);
			$mpdf->list_indent_first_level = 0;
			$stylesheet = file_get_contents('printtemplates/Job/style_pod.css');
			$mpdf->WriteHTML($stylesheet,1);
			//$mpdf->WriteHTML($HTML);
			$mpdf->WriteHTML($this->_documentXML,2); /*?????????????????? pdf*/


			$pdf_name = 'pdf_docs/proof_of_delivery_english.pdf';


			//$mpdf->WriteHTML($html_2); /*?????????????????? pdf*/
			ob_clean();
			$mpdf->Output($pdf_name, 'F');

			//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
			header('Location:'.$pdf_name);



			exit;
		}
	}
