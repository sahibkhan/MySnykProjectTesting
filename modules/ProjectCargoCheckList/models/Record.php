<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger Entity Record Model Class
 */

class ProjectCargoCheckList_Record_Model extends Vtiger_Record_Model {
	protected $module = false;


	public function getCurUserInfo() {
		$current_user = Users_Record_Model::getCurrentUserModel();
		$curuser_login = $current_user->get('user_name');
		$curuser_email = $current_user->get('email1');
		
		$c_user_id = $current_user->get('id');
		
		
		
		//print_r($current_user);
		
		global $adb;
		//$adb->setDebug(true);
		/*
			 Fetch request user email
		*/
		$request_q = $adb->pquery("
								Select vtiger_users.email1,vtiger_users.id
								FROM vtiger_crmentity
								LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
								WHERE vtiger_crmentity.crmid = ?",array($this->getId())
							);
		$request_creator_email = $adb->query_result($request_q, 0, 'email1'); 
		
		$doc_creator_id=$adb->query_result($request_q, 0, 'id'); 
		//echo "Req Cr Emal ". $request_creator_email;
		//exit;
  
		//get user gm
		$sql_creator_gm = $adb->pquery("
								SELECT * FROM vtiger_userlistcf WHERE cf_3355=?",array($request_creator_email)
							);
		$gm_id = $adb->query_result($sql_creator_gm, 0, 'cf_3385');
		
		$creator_user_profile_id = $adb->query_result($sql_creator_gm, 0, 'userlistid');
		
		//get gm info
		$sql_gm = $adb->pquery("
								SELECT * FROM vtiger_userlistcf WHERE userlistid=?",array($gm_id)
							);
		$creator_gm_email = $adb->query_result($sql_gm, 0, 'cf_3355');
		
		
		
		$recordInfo = Vtiger_Record_Model::getInstanceById($this->getId(), 'ProjectCargoCheckList');
		
		$position="OTHER";
		
		if($c_user_id==604)
		{
			$position="RISKMNG";
		}
		if($c_user_id==266)
		{
			$position="PROALA";
		}
		if($c_user_id==$doc_creator_id && $recordInfo->get('approval_status_crg')=='')
		{
			$position="CREATOR";
		}
  
		if($curuser_email==$creator_gm_email)
		{
			$position="BGM";
		}
  
		return $position;  //." ".$curuser_email." ".$creator_gm_email." ".$gm_id;
		//return $position." cuid ".$c_user_id." duic ".$doc_creator_id;
	  }
	
		
	public function getDocStatus() {
				
		global $adb;
		$request_q = $adb->pquery("
								Select risk_management_approval_crg,proala_approval_crg, approval_status_crg from vtiger_projectcargochecklistcf WHERE projectcargochecklistid = ?",array($this->getId())
							);
		$doc_status = $adb->query_result($request_q, 0, 'approval_status_crg'); 
		$rma_status = $adb->query_result($request_q, 0, 'risk_management_approval_crg');
		$pra_status = $adb->query_result($request_q, 0, 'proala_approval_crg');				
  
		return $doc_status;
	}
	
	public function getRiskMngStatus() {
				
		global $adb;
		$request_q = $adb->pquery("
								Select risk_management_approval_crg,proala_approval_crg, approval_status_crg from vtiger_projectcargochecklistcf WHERE projectcargochecklistid = ?",array($this->getId())
							);
		$doc_status = $adb->query_result($request_q, 0, 'approval_status_crg'); 
		$rma_status = $adb->query_result($request_q, 0, 'risk_management_approval_crg');
		$pra_status = $adb->query_result($request_q, 0, 'proala_approval_crg');				
  
		return $rma_status;
	}
	
	public function getProAlaStatus() {
				
		global $adb;
		$request_q = $adb->pquery("
								Select risk_management_approval_crg,proala_approval_crg, approval_status_crg from vtiger_projectcargochecklistcf WHERE projectcargochecklistid = ?",array($this->getId())
							);
		$doc_status = $adb->query_result($request_q, 0, 'approval_status_crg'); 
		$rma_status = $adb->query_result($request_q, 0, 'risk_management_approval_crg');
		$pra_status = $adb->query_result($request_q, 0, 'proala_approval_crg');				
  
		return $pra_status;
	}
	
	public function getBGMStatus() {
				
		global $adb;
		$request_q = $adb->pquery("
								Select projectcargochecklistid,bgm_approval_crg from vtiger_projectcargochecklistcf WHERE projectcargochecklistid = ?",array($this->getId())
							);
		$bgm_status = $adb->query_result($request_q, 0, 'bgm_approval_crg'); 
		
  
		return $bgm_status;
	}
	
}
