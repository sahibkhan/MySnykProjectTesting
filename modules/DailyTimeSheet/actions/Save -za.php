<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class DailyTimeSheet_Save_Action extends Vtiger_Save_Action {

	function __construct() {
		
		parent::__construct();
	}

	public function process(Vtiger_Request $request) {

        $adb = PearDatabase::getInstance();
        $recordid = $request->get('record');
		$creatorid = $request->get('assigned_user_id');
		$recorddate = $request->get('cf_6892');
		$currentdate = date("d-m-Y");
		$starttime = date("Y-m-d 01:00:00");
        $endtime = date("Y-m-d 23:59:59");
		
		$query = "SELECT * FROM `vtiger_dailytimesheetcf` 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_dailytimesheetcf.dailytimesheetid
							where vtiger_crmentity.smcreatorid='".$creatorid."'  
							AND vtiger_crmentity.setype='DailyTimeSheet' 
							AND vtiger_crmentity.deleted = 0  
							AND vtiger_crmentity.createdtime between '".$starttime."' and '".$endtime."'  
							AND vtiger_dailytimesheetcf.cf_6892 = DATE('".$starttime."')"; 
							
		$result = $adb->pquery($query);
		$row = $adb->fetch_array($result);
		$numrows = $adb->num_rows($result);
		
		if(($numrows > 0 && empty($recordid)) || $recorddate != $currentdate){
	    $url = "http://" . $_SERVER['SERVER_NAME']."/index.php?module=DailyTimeSheet&view=List";
		header("Location: $url");
		exit;
		}
		
		$query_1 = "SELECT * FROM `vtiger_users` INNER JOIN vtiger_loginhistory ON vtiger_loginhistory.user_name = vtiger_users.user_name 
			  where vtiger_loginhistory.login_time between '$starttime' and '$endtime'  and vtiger_users.id  = '$creatorid'";

		$result_1 = $adb->pquery($query_1);
		
		$row_1 = $adb->fetch_array($result_1);
		$logintime_db = $row_1['login_time'];
		$zone = $row_1['time_zone'];
		$date = new \DateTime(date($logintime_db));
		$date->setTimezone(new \DateTimeZone($zone));
		$logintime =  $date->format('Y-m-d h:i A');
		$request->set('cf_6912',$logintime);
		
		
        if(empty($recordid)){
					
        $location_id = $request->get('cf_6884');
		$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
		$location = $recordLocation->get('cf_1559'); 
        $value = date('Y');
		$sql_m =  'SELECT MAX(cf_6914) as max_ordering from vtiger_dailytimesheet
					 INNER JOIN vtiger_dailytimesheetcf ON vtiger_dailytimesheetcf.dailytimesheetid = vtiger_dailytimesheet.dailytimesheetid 
					 where vtiger_dailytimesheetcf.cf_6916="'.$value.'"';
		
			$result_m = $adb->pquery($sql_m);
			$row = $adb->fetch_array($result_m);
		    if($adb->num_rows($result_m)==0)
			{
				$ordering = 0;
			}
			else{
				$max_ordering = $row["max_ordering"]; 
				if (!is_numeric($max_ordering))
				{
					$ordering = 0;
				}
				else
				{
					$ordering = $max_ordering;
				}
			}
			
			$serial_number = sprintf("%02d", $ordering+1);
			
			
			$subject = strtoupper($location).'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y'); 

		    $request->set('name',$subject);
			
		
        }
		
		$recordModel = $this->saveRecord($request);
		$sourceRecordId = $recordModel->get('id');

		if(empty($recordid)){

		     $value = date('Y');
		$sql_m =  'SELECT MAX(cf_6914) as max_ordering from vtiger_dailytimesheet
					 INNER JOIN vtiger_dailytimesheetcf ON vtiger_dailytimesheetcf.dailytimesheetid = vtiger_dailytimesheet.dailytimesheetid 
					 where vtiger_dailytimesheetcf.cf_6916="'.$value.'"';
		
			$result_m = $adb->pquery($sql_m);
			$row = $adb->fetch_array($result_m);
		    if($adb->num_rows($result_m)==0)
			{
				$ordering = 0;
			}
			else{
				$max_ordering = $row["max_ordering"]; 
				if (!is_numeric($max_ordering))
				{
					$ordering = 0;
				}
				else
				{
					$ordering = $max_ordering;
				}
			}
			
			$serial_number = sprintf("%02d", $ordering+1);

             $sql =  "UPDATE vtiger_dailytimesheetcf SET cf_6916 = '".date('Y')."', cf_6914 = '".str_pad($serial_number, 5, "0", STR_PAD_LEFT)."' WHERE dailytimesheetid = '".$sourceRecordId."'";
		     $result = $adb->pquery($sql); 
		}
		
		//echo "<pre>"; print_r($request); exit;
		// getting work info from request
		$workCount = count($request->get("cf_6904"));
		for ($w = 0; $w < $workCount; $w++) {
			$saveWorkRecord = new Vtiger_Save_Action();
			$workRequest = new Vtiger_Request("","");
			$workRequest->set("module","DailyTimeSheetTask");
			$workRequest->set("action","Save");
			$dailytimesheettaskid = $request->get('dailytimesheettaskid');
			$type = $request->get("cf_6904");
			$desc = $request->get("cf_6906");
			$qty = $request->get("cf_6908");
			$hours = $request->get("cf_6910");
			
			if(!empty($dailytimesheettaskid[$w])){
				$dailytimesheettask = Vtiger_Record_Model::getInstanceById($dailytimesheettaskid[$w], 'DailyTimeSheetTask');
				if($dailytimesheettask->get('name') == 'cron'){
					continue;
				}
				
				$workRequest->set("record",$dailytimesheettaskid[$w]);
			} else
			{
				$workRequest->set("record","");
				
			}
			
			/*if($dailytimesheettaskid[$w] == 0){
				$workRequest->set("record","");
			}*/
			
            
			$int = 1;
            $dec = $int / $hours[$w];

			$workRequest->set("assigned_user_id", $request->get('assigned_user_id'));
			$workRequest->set("name",'Manual');
			$workRequest->set("cf_6904",$type[$w]);
			$workRequest->set("cf_6906",$desc[$w]);
			$workRequest->set("cf_6908",$qty[$w]);
			$workRequest->set("cf_6910", $hours[$w]);
            
			$workRecordModel = $saveWorkRecord->saveRecord($workRequest);
			//related list entry
			$parentModuleCV = "DailyTimeSheet";
			$parentModuleCV = Vtiger_Module_Model::getInstance("DailyTimeSheet");
			$parentRecordId1 = $sourceRecordId;
			$relatedWorkModule = $workRecordModel->getModule();
			$relatedWorkRecordId = $workRecordModel->get('id');
			$workRelationModel = Vtiger_Relation_Model::getInstance($parentModuleCV, $relatedWorkModule);
		   	$workRelationModel->addRelation($parentRecordId1, $relatedWorkRecordId);
			
		}
	
	    $loadUrl = $recordModel->getDetailViewUrl();
		header("Location: $loadUrl");
		
	}
}