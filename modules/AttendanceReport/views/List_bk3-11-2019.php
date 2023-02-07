<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class AttendanceReport_List_View extends Vtiger_List_View {
	protected $listViewEntries = false;
	protected $listViewCount = false;
	protected $listViewLinks = false;
	protected $listViewHeaders = false;
	function __construct() {
		parent::__construct();
		$this->exposeMethod('attendencereportcronjob');
		$this->exposeMethod('attendence1C');
	}

	function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request, false);

		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName);
		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'));
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName));
		$this->viewName = $request->get('viewname');
		if(empty($this->viewName)){
			//If not view name exits then get it from custom view
			//This can return default view id or view id present in session
			$customView = new CustomView();
			$this->viewName = $customView->getViewId($moduleName);
		}

		$quickLinkModels = $listViewModel->getSideBarLinks($linkParams);
		$viewer->assign('QUICK_LINKS', $quickLinkModels);
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('VIEWID', $this->viewName);

		if($display) {
			$this->preProcessDisplay($request);
		}
	}

	function preProcessTplName(Vtiger_Request $request) {

		return 'ListViewPreProcess.tpl';
	}

	//Note : To get the right hook for immediate parent in PHP,
	// specially in case of deep hierarchy
	/*function preProcessParentTplName(Vtiger_Request $request) {
		return parent::preProcessTplName($request);
	}*/

	protected function preProcessDisplay(Vtiger_Request $request) {
		parent::preProcessDisplay($request);
	}


	function process (Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$mode = $request->get('mode');
        if(!empty($mode)) {
            $this->invokeExposedMethod($mode,$request);
			exit;
		}
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$this->viewName = $request->get('viewname');
		
		$this->initializeListViewContents($request, $viewer);
		
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('ListViewContents.tpl', $moduleName);
	}

	function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$viewer->view('ListViewPostProcess.tpl', $moduleName);
		parent::postProcess($request);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.List',
			"modules.$moduleName.resources.List",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			"modules.Emails.resources.MassEdit",
			"modules.Vtiger.resources.CkEditor"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */
	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$moduleName = $request->getModule();
		$cvId = $this->viewName;
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if($sortOrder == "ASC"){
			$nextSortOrder = "DESC";
			$sortImage = "icon-chevron-down";
		}else{
			$nextSortOrder = "ASC";
			$sortImage = "icon-chevron-up";
		}

		if(empty ($pageNumber)){
			$pageNumber = '1';
		}

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId);
		
		
		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'), 'CVID'=>$cvId);
		$linkModels = $listViewModel->getListViewMassActions($linkParams);

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);

		if(!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder',$sortOrder);
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if(!empty($operator)) {
			$listViewModel->set('operator', $operator);
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
		}
		if(!empty($searchKey) && !empty($searchValue)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		if(!$this->listViewHeaders){
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if(!$this->listViewEntries){
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$noOfEntries = count($this->listViewEntries);
		
		$viewer->assign('MODULE', $moduleName);

		if(!$this->listViewLinks){
			$this->listViewLinks = $listViewModel->getListViewLinks($linkParams);
		}
		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);

		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER',$pageNumber);

		$viewer->assign('ORDER_BY',$orderBy);
		$viewer->assign('SORT_ORDER',$sortOrder);
		$viewer->assign('NEXT_SORT_ORDER',$nextSortOrder);
		$viewer->assign('SORT_IMAGE',$sortImage);
		$viewer->assign('COLUMN_NAME',$orderBy);

		$viewer->assign('LISTVIEW_ENTIRES_COUNT',($noOfEntries==19 ? 20 : $noOfEntries));
		//$viewer->assign('LISTVIEW_ENTIRES_COUNT',$noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);

		if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
			if(!$this->listViewCount){
				$this->listViewCount = $listViewModel->getListViewCount();
			}
			$totalCount = $this->listViewCount;
			$pageLimit = $pagingModel->getPageLimit();
			$pageCount = ceil((int) $totalCount / (int) $pageLimit);

			if($pageCount == 0){
				$pageCount = 1;
			}
			$viewer->assign('PAGE_COUNT', $pageCount);
			$viewer->assign('LISTVIEW_COUNT', $totalCount);
		}

		$viewer->assign('IS_MODULE_EDITABLE', $listViewModel->getModule()->isPermitted('EditView'));
		$viewer->assign('IS_MODULE_DELETABLE', $listViewModel->getModule()->isPermitted('Delete'));
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param Vtiger_Request $request
	 */
	function getRecordsCount(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$count = $this->getListViewCount($request);

		$result = array();
		$result['module'] = $moduleName;
		$result['viewname'] = $cvId;
		$result['count'] = $count;

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get listView count
	 * @param Vtiger_Request $request
	 */
	function getListViewCount(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		if(empty($cvId)) {
			$cvId = '0';
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId);
		$listViewModel->set('search_key', $searchKey);
		$listViewModel->set('search_value', $searchValue);
		$listViewModel->set('operator', $request->get('operator'));

		$count = $listViewModel->getListViewCount();

		return $count;
	}



	/**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	function getPageCount(Vtiger_Request $request){
		$listViewCount = $this->getListViewCount($request);
		$pagingModel = new Vtiger_Paging_Model();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $listViewCount / (int) $pageLimit);

		if($pageCount == 0){
			$pageCount = 1;
		}
		$result = array();
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
	
	public function attendence1C(Vtiger_Request $request) {
		require_once('include/custom_connectdb.php'); // ??????????? ? ???? ??????
	require_once('include/Vtiger/crm_data_arrange.php');
	echo "<pre>";
	date_default_timezone_set("UTC");
	$web1C = 'http://89.218.38.221/gl/ws/CreateJobFile?wsdl';
	$con1C = array( 'login' => 'AdmWS',
							'password' => '906900',
							'soap_version' => SOAP_1_2,
							'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
							'exceptions' => true,
							'trace' => 1);
							
	if (!function_exists('is_soap_fault')) {
		echo '<br>not found module php-soap.<br>';
		return false;
	}
	try {
		$Client1C = new SoapClient($web1C, $con1C);
		echo "Done";
	} catch(SoapFault $e) {
		//var_dump($e);
		echo '<br>error at connecting to 1C<br>'.$e;
		return false;
	}
	if (is_soap_fault($Client1C)){
		echo '<br>inner server error at connecting to 1C<br>';
		return false;
	}
	}
	
	public function attendencereportcronjob(Vtiger_Request $request) {
		$matchdate = "1970-01-01";
		$matchid = 0;
		$db = PearDatabase::getInstance();	
			$query = "SELECT * FROM vtiger_userattendancecf inner join vtiger_userattendance on vtiger_userattendance.userattendanceid = vtiger_userattendancecf.userattendanceid inner join vtiger_crmentity on vtiger_userattendancecf.userattendanceid = vtiger_crmentity.crmid where cf_5914 != 1 and vtiger_crmentity.deleted = 0 order by cf_5904 DESC, cf_5916 ASC";
        	$result = $db->pquery($query, array());
				for($i=0;$i<$db->num_rows($result);$i++){
				if($db->query_result($result,$i,'cf_5902') == "Has come to firm")
				{ 
					if($db->query_result($result,$i,'cf_5894') == $matchdate && $db->query_result($result,$i,'cf_5904') == $matchid)
					{
					}
					else
					{
					$matchdate = $db->query_result($result,$i,'cf_5894');
					$matchid = $db->query_result($result,$i,'cf_5904');
					$date = $db->query_result($result,$i,'cf_5894');
					$d = date_parse_from_format("Y-m-d", $date);
					$date1=date_create($date);
					$dateObj   = DateTime::createFromFormat('!m', date_format($date1,"m"));
					$month = $dateObj->format('F');
					if($db->query_result($result,$i,'cf_5916') == "Biotrack")
					{
						$daysworked = $this->getdaysworked($db->query_result($result,$i,'cf_5904'),$month,$d["year"],"Biotrack");
					}
					else
					{
						$daysworked = $this->getdaysworked($db->query_result($result,$i,'cf_5904'),$month,$d["year"],"Picar");
					}
					$uiduserlist = $this->getuseridbypicar($db->query_result($result,$i,'cf_5904'),$db->query_result($result,$i,'cf_5916'));
					$splituserid = explode(',',$uiduserlist);
					$uidpicar = $splituserid[0];
					$office = $splituserid[1];
					$department = $splituserid[2];
					$leaves = $this->getleavesdata($uidpicar,$d["month"],$d["year"]);
					$calendardayscount = $this->getcalendardays(85757,$d["month"],$d["year"]);
					$getleaves = explode(',',$leaves);
					$request->set("cf_5940",$getleaves[0]);
					$request->set("cf_5944",$getleaves[1]);
					$request->set("cf_5942",$getleaves[2]);
					$request->set("cf_5950",$getleaves[3]);
					$request->set("cf_5946",$getleaves[4]);
					$request->set("cf_5948",$getleaves[5]);
					$request->set("cf_5976",$department);
					$request->set("cf_5978",$office);
					//echo "<pre>"; print_r($request); exit;
					$workingdays = intval($this->getworkingdays($d["month"],$d["year"])+$calendardayscount);
					$getid = explode(',',$daysworked);
					$request->set("module","AttendanceReport");
					$request->set("action","Save");
					if($daysworked == "no Record")
					{
					$request->set("record","");
					}
					else
					{
					$request->set("record",$getid[0]);
					}
					$request->set("name",$splituserid[3]);
					$request->set("cf_5920",$workingdays);
					$request->set("cf_5922",($workingdays*8));
					if($db->query_result($result,$i,'cf_5916') == "Biotrack")
					{
						$request->set("cf_5932",$getid[1]+1);
						$request->set("cf_5934",(($getid[1]+1)*8));
						$request->set("cf_5962",$db->query_result($result,$i,'cf_5904'));
					}
					else
					{
						$request->set("cf_5928",$getid[1]+1);
						$request->set("cf_5930",($getid[1]+1)*8);
						$request->set("cf_5952",$db->query_result($result,$i,'cf_5904'));
					}
					
					$request->set("cf_5958",$month);
					$request->set("cf_5960",$d["year"]);
					$saveattendencereport = new Vtiger_Save_Action();
					//print_r($request); exit;
					$recordModel1 = $saveattendencereport->saveRecord($request);
				}
				}
					$saveattendencereport = new Vtiger_Save_Action();
					$request = new Vtiger_Request();
					$request->set("module","UserAttendance");
					$request->set("action","Save");
					$request->set("record",$db->query_result($result,$i,'userattendanceid'));
					$request->set("cf_5896",$db->query_result($result,$i,'cf_5896'));
					$request->set("cf_5914",1);
					//print_r($request); exit;
					$recordModel2 = $saveattendencereport->saveRecord($request);
				}
				echo "<script>window.location.href='index.php?module=AttendanceReport&view=List';</script>";
				//echo "innnnn";
				//header ('Location: index.php?module=AttendanceReport&view=List');
				//return;
	}
	
	public function getdaysworked($id,$month,$year,$databy) {
		$db = PearDatabase::getInstance();	
		if($databy == "Biotrack")
		{
			$query = "SELECT attendancereportid,cf_5932 FROM vtiger_attendancereportcf inner join vtiger_crmentity on vtiger_attendancereportcf.attendancereportid = vtiger_crmentity.crmid where cf_5962 = ".$id." and cf_5958 = '".$month."' and cf_5960 = ".$year." and vtiger_crmentity.deleted = 0";
			$result = $db->pquery($query, array());
			
				for($i=0;$i<$db->num_rows($result);$i++){ 
					return $db->query_result($result,$i,'attendancereportid').",".$db->query_result($result,$i,'cf_5932'); 
				}
		}
		else
		{
			$query = "SELECT attendancereportid,cf_5928 FROM vtiger_attendancereportcf inner join vtiger_crmentity on vtiger_attendancereportcf.attendancereportid = vtiger_crmentity.crmid where cf_5952 = ".$id." and cf_5958 = '".$month."' and cf_5960 = ".$year." and vtiger_crmentity.deleted = 0";
			$result = $db->pquery($query, array());
			
				for($i=0;$i<$db->num_rows($result);$i++){ 
					return $db->query_result($result,$i,'attendancereportid').",".$db->query_result($result,$i,'cf_5928'); 
				}
		}
			return "no Record";
	}
	
	public function getcalendardays($countryid,$month,$year) {
		$calendardays = 0;
		$db = PearDatabase::getInstance();	
			$query = "SELECT calendardaysid,cf_5263,cf_5265 FROM vtiger_calendardayscf inner join vtiger_crmentity on vtiger_calendardayscf.calendardaysid = vtiger_crmentity.crmid where YEAR(cf_5263) = ".$year." and MONTH(cf_5263) = ".$month." and cf_5267 = ".$countryid." and vtiger_crmentity.deleted = 0";
        	$result = $db->pquery($query, array());
				for($i=0;$i<$db->num_rows($result);$i++){ 
					if($db->query_result($result,$i,'cf_5265') == "working day")
					{$calendardays++;}else{$calendardays--;} 
				}
				return $calendardays;
	}
	
	public function getworkingdays($m,$y) {
		$workdays = array();
		$type = CAL_GREGORIAN;
		$month = date($m); // Month ID, 1 through to 12.
		$year = date($y); // Year in 4 digit 2009 format.
		$day_count = cal_days_in_month($type, $month, $year); // Get the amount of days
		
		//loop through all days
		for ($i = 1; $i <= $day_count; $i++) {
		
				$date = $year.'/'.$month.'/'.$i; //format date
				$get_name = date('l', strtotime($date)); //get week day
				$day_name = substr($get_name, 0, 3); // Trim day name to 3 chars
		
				//if not a weekend add day to array
				if($day_name != 'Sun' && $day_name != 'Sat'){
					$workdays[] = $i;
				}
		
		}
//		$db = PearDatabase::getInstance();	
//			$query = "SELECT * FROM vtiger_leaverequestcf inner join vtiger_leaverequest on vtiger_leaverequest.leaverequestid = vtiger_leaverequestcf.leaverequestid inner join vtiger_crmentity on vtiger_leaverequestcf.leaverequestid = vtiger_crmentity.crmid where cf_5914 != 1 and vtiger_crmentity.deleted = 0";
//        	$result = $db->pquery($query, array());
//				for($i=0;$i<$db->num_rows($result);$i++){
//				}
		return count($workdays);
	}
	
	public function getuseridbypicar($picarid,$databy) {
		if($databy == "Biotrack")
		{
			$fieldname = "cf_6002";
		}else
		{
			$fieldname = "cf_6000";
		}
		$db = PearDatabase::getInstance();	
			$query = "SELECT * FROM vtiger_userlistcf inner join vtiger_userlist on vtiger_userlist.userlistid = vtiger_userlistcf.userlistid inner join vtiger_crmentity on vtiger_userlistcf.userlistid = vtiger_crmentity.crmid where ".$fieldname." = ".$picarid." and vtiger_crmentity.deleted = 0";
        	$result = $db->pquery($query, array());
				for($i=0;$i<$db->num_rows($result);$i++){
					//echo $db->query_result($result,$i,'userlistid').",".$db->query_result($result,$i,'cf_3421').",".$db->query_result($result,$i,'cf_3349')."<br> userdata";
					return $db->query_result($result,$i,'userlistid').",".$db->query_result($result,$i,'cf_3421').",".$db->query_result($result,$i,'cf_3349').",".$db->query_result($result,$i,'name');
				}
				return "no Record";
	}
	
	public function getleavesdata($uid,$month,$year){
		$leavedata = array();
		$anual = 0;
		$casual = 0;
		$sick = 0;
		$childcare = 0;
		$unpaid = 0;
		$meternity = 0;
		$db = PearDatabase::getInstance();	
			$query = "SELECT * FROM vtiger_leaverequestcf inner join vtiger_leaverequest on vtiger_leaverequest.leaverequestid = vtiger_leaverequestcf.leaverequestid inner join vtiger_crmentity on vtiger_leaverequestcf.leaverequestid = vtiger_crmentity.crmid where cf_3423 = ".$uid." and MONTH(cf_3391) = ".$month." and YEAR(cf_3391) = ".$year." and vtiger_crmentity.deleted = 0 and cf_3411 != '' and cf_3415 != ''";
        	$result = $db->pquery($query, array());
				for($i=0;$i<$db->num_rows($result);$i++){
					//echo $db->query_result($result,$i,'cf_3409');
					$leavetype = $db->query_result($result,$i,'cf_3409');
					$fromdate = strtotime($db->query_result($result,$i,'cf_3391'));
					$tilldate = strtotime($db->query_result($result,$i,'cf_3393'));
					$year1 = date('Y', $fromdate);
					$year2 = date('Y', $tilldate);
					$month1 = date('m', $fromdate);
					$month2 = date('m', $tilldate);
					$diff = (($year2 - $year1) * 12) + ($month2 - $month1);
					if($diff > 0)
					{
						$tilldate = strtotime(date("Y-m-t", strtotime($db->query_result($result,$i,'cf_3391'))));
					}
					$leavedays = ($tilldate - $fromdate)/(60 * 60 * 24);
					if($leavedays == 0)
					{
						$fromtime = strtotime($db->query_result($result,$i,'cf_3391')." ".$db->query_result($result,$i,'cf_3395'));
						$tilltime = strtotime($db->query_result($result,$i,'cf_3391')." ".$db->query_result($result,$i,'cf_3397'));
						$leavedays = 0.125*($tilltime - $fromtime)/(60*60);
					}
					//echo $leavedays;
					if($leavedays <= 0)
					{
						$leavedays = 1;
					}
					//echo "-".$leavedays."-";
					if($leavetype == "Annual paid leave")
					{$anual=$anual+$leavedays;}
					elseif($leavetype == "Unpaid leave")
					{$unpaid=$unpaid+$leavedays;}
					elseif($leavetype == "Sick leave (medical certificate required)")
					{$sick=$sick+$leavedays;}
					elseif($leavetype == "Casual (short-term) paid leave")
					{$casual=$casual+$leavedays;}
					elseif($leavetype == "Maternity leave")
					{$meternity=$meternity+$leavedays;}
					elseif($leavetype == "Child care leave")
					{$childcare=$childcare+$leavedays;}
				} 
				//echo $uid."-".$anual.",".$casual.",".$sick.",".$childcare.",".$unpaid.",".$meternity."<br>";
		return $anual.",".$casual.",".$sick.",".$childcare.",".$unpaid.",".$meternity;
	}
}