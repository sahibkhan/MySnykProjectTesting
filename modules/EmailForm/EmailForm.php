<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';


class EmailForm extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_emailform';
	var $table_index= 'emailformid';
/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_emailformcf', 'emailformid');

	/***
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_emailform', 'vtiger_emailformcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_emailform' => 'emailformid',
		'vtiger_emailformcf'=>'emailformid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('emailform', 'name'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = 'name';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('emailform', 'name'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array('name');

	// For Alphabetical search
	var $def_basicsearch_col = 'name';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'name';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('name','assigned_user_id');

	var $default_order_by = 'name';
	var $default_sort_order='ASC';


    function save_module($module){
    	/*$current_user = Users_Record_Model::getCurrentUserModel();
    	$post = array(
    		'id'=>$this->id,
    		'userid'=>$current_user->getId(),
    		'today'=>date('Y-m-d')
    	);
    	*/
    	//$this->checkWhoCreatedRequest();
	}

	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
 		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
		} else if($eventType == 'module.disabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}
	function checkWhoCreatedRequest(){
		global $adb;
		
		$post = array(
			'id'=>$this->id,
			'today'=>date('Y-m-d')
		);

		$sql = $adb->pquery("SELECT smownerid FROM vtiger_crmentity WHERE crmid=".$post['id']." LIMIT 1");
		if ($adb->num_rows($sql)==0) return; else $post['user'] = $adb->query_result($sql,0,'smownerid');

		//user != s.khan
		if ($post['user']!=412373) {
			
			//user email
			$sql = $adb->pquery("SELECT email1 FROM vtiger_users WHERE id=".$post['user']." LIMIT 1");
			if ($adb->num_rows($sql)==0) return; else $post['email'] = $adb->query_result($sql,0,'email1');

			//user name
			$sql = $adb->pquery("SELECT first_name,last_name FROM vtiger_users WHERE email1 LIKE '%".$post['email']."%' LIMIT 1");
			if ($adb->num_rows($sql)==0) return; else $post['name'] = $adb->query_result($sql,0,'first_name').' '.$adb->query_result($sql,0,'last_name');

			//GM id
			$sql = $adb->pquery("SELECT cf_3385 FROM vtiger_userlistcf WHERE cf_3355 LIKE '%".$post['email']."%' LIMIT 1");
			if ($adb->num_rows($sql)==0) return; else $post['gm_id'] = $adb->query_result($sql,0,'cf_3385');

			if ($post['gm_id']==412373) {

				//GM email
				$sql = $adb->pquery("SELECT cf_3355 FROM vtiger_userlistcf WHERE cf_3385=".$post['gm_id']." LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['gm_email'] = $adb->query_result($sql,0,'cf_3355');

				//GM name
				$sql = $adb->pquery("SELECT first_name,last_name FROM vtiger_users WHERE email1 LIKE '%".$post['gm_email']."%' LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['gm_name'] = $adb->query_result($sql,0,'first_name').' '.$adb->query_result($sql,0,'last_name');

				//post comment
				$sql = $adb->pquery("SELECT name FROM vtiger_emailform WHERE emailformid='".$post['id']."' LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['comment'] = $adb->query_result($sql,0,'name');

				//updating info
				$adb->pquery("UPDATE vtiger_emailformcf SET cf_5147 = '".$post['name']."', cf_5151 = CURDATE() WHERE emailformid = ".$post['id']." LIMIT 1");

				$info = array(
					'post'=>array(
						'id'=>$post['id'],
						'comment'=>$post['comment']
					),
					'user'=>array(
						'id'=>$post['user'],
						'name'=>$post['name'],
						'email'=>$post['email']
					),
					'gm'=>array(
						'id'=>$post['user'],
						'name'=>$post['name'],
						'email'=>$post['email'],
						'status'=>'yes'
					),
					'ceo'=>array(
						'id'=>$post['gm_id'],
						'name'=>$post['gm_name'],
						'email'=>$post['gm_email'],
						'status'=>'-'
					),
					'email'=>[$post['gm_email'],'s.khan@globalinklogistics.com']
				);

				//sending email
				$this->emailsend($info,$conn);
			} else {

				//get email of user
				$sql = $adb->pquery("SELECT email1 FROM vtiger_users WHERE id=".$post['user']." LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['email'] = $adb->query_result($sql,0,'email1');

				//get name of user
				$sql = $adb->pquery("SELECT first_name,last_name FROM vtiger_users WHERE email1 LIKE '%".$post['email']."%' LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['name'] = $adb->query_result($sql,0,'first_name').' '.$adb->query_result($sql,0,'last_name');

				//get id of general manager
				$sql = $adb->pquery("SELECT cf_3385 FROM vtiger_userlistcf WHERE cf_3355 LIKE '%".$post['email']."%' LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['gm_id'] = $adb->query_result($sql,0,'cf_3385');

				//get email of general manager
				$sql = $adb->pquery("SELECT cf_3355 FROM vtiger_userlistcf WHERE cf_3385=".$post['gm_id']." LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['gm_email'] = $adb->query_result($sql,0,'cf_3355'); 

				//get name of general manager
				$sql = $adb->pquery("SELECT first_name,last_name FROM vtiger_users WHERE email1 LIKE '%".$post['gm_email']."%' LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['gm_name'] = $adb->query_result($sql,0,'first_name').' '.$adb->query_result($sql,0,'last_name');

				//get comment of the post
				$sql = $adb->pquery("SELECT name FROM vtiger_emailform WHERE emailformid='".$post['id']."' LIMIT 1");
				if ($adb->num_rows($sql)==0) return; else $post['comment'] = $adb->query_result($sql,0,'name');

				$info = array(
					'post'=>array(
						'id'=>$post['id'],
						'comment'=>$post['comment']
					),
					'user'=>array(
						'id'=>$post['user'],
						'name'=>$post['name'],
						'email'=>$post['email']
					),
					'gm'=>array(
						'id'=>$post['gm_id'],
						'name'=>$post['gm_name'],
						'email'=>$post['gm_email'],
						'status'=>'-'
					),
					'ceo'=>array(
						'id'=>412373,
						'name'=>'Siddique Khan',
						'email'=>'s.khan@globalinklogistics.com',
						'status'=>'-'
					),
					'email'=>[$post['email'],$post['gm_email']]
				);

				//sending email
				$this->emailsend($info,$conn);
			}
		} else {

			//Post comment
			$sql = $adb->pquery("SELECT name FROM vtiger_emailform WHERE emailformid='".$post['id']."' LIMIT 1");
			if ($adb->num_rows($sql)==0) return; else $post['comment'] = $adb->query_result($sql,0,'name');

			$info = array(
				'post'=>array(
					'id'=>$post['id'],
					'comment'=>$post['comment']
				),
				'user'=>array(
					'id'=>412373,
					'name'=>'Siddique Khan',
					'email'=>'s.khan@globalinklogistics.com'
				),
				'gm'=>array(
					'id'=>412373,
					'name'=>'Siddique Khan',
					'email'=>'s.khan@globalinklogistics.com',
					'status'=>'yes'
				),
				'ceo'=>array(
					'id'=>412373,
					'name'=>'Siddique Khan',
					'email'=>'s.khan@globalinklogistics.com',
					'status'=>'yes'
				),
				'email'=>['d.bashayev@globalinklogistics.com','s.khan@globalinklogistics.com']
			);

			//updating post
			$adb->pquery("UPDATE vtiger_emailformcf SET cf_5147 = '".$post['name']."', cf_5149 = '".$post['name']."', cf_5151 = CURDATE(),cf_5153 = CURDATE() WHERE emailformid = ".$post['id']." LIMIT 1");

			//sending email
			$this->emailsend($info,$conn);
		}
	}
	
	function emailsend($a,$conn) {

	$subject = 'Email Form #'.$a['post']['id'];

	$gm = $a['gm']['status']=='yes'?$a['gm']['name'].' ('.$this->readDate(date('Y-m-d')).')':'-';
	$ceo = $a['ceo']['status']=='yes'?$a['ceo']['name'].' ('.$this->readDate(date('Y-m-d')).')':'-';

	$from = 'e.tamabay@globalinklogistics.com';
	$link = 'https://erp.globalink.net/index.php?module=EmailForm&view=Detail&record='.$a['post']['id'];
	$html = '<html>
				<head>
					<meta charset="utf8">
				</head>
				<body>
					<table border="1">
						<tr><td colspan="3" style="font-weight:bold;text-align:center;">EMAILFORM #'.$a['post']['id'].'</td></tr>
						<tr><td colspan="1" style="text-align: center;vertival-align: middle;">Created by:</td><td colspan="2" style="text-align: center;vertival-align: middle;">'.$a['user']['name'].' ('.$this->readDate(date('Y-m-d')).')</td></tr>
						<tr><td colspan="1" style="text-align: center;vertival-align: middle;">General Manager:</td><td colspan="2" style="text-align: center;vertival-align: middle;">'.$gm.'</td></tr>
						<tr><td colspan="1" style="text-align: center;vertival-align: middle;">CEO:</td><td colspan="2" style="text-align: center;vertival-align: middle;">'.$ceo.'</td></tr>
						<tr><td colspan="1" style="text-align: center;vertival-align: middle;">comment:</td><td colspan="2" style="text-align: center;vertival-align: middle;">'.$a['post']['comment'].'</td></tr>
						<tr><td colspan="3" style="text-align: center;vertival-align: middle;">Please see details on this link: '.$link.'</td></tr>
					</table>
				</body>
			</html>';

	$headers = "MIME-Version: 1.0" . "\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
    $headers .= 'From: '.$subject.'<'.$from.'>'. "\n";
    $headers .= 'Reply-To: '.$a['user']['email']. "\n";

    $a['email'][] = $from;
   	$email = $a['email'];
		// closed by Ruslan 24.09.2019
   	//foreach ($email as &$val) mail($val,$subject,$html,$headers);
	}

	function readDate($a) {//читабелный вид даты

		$month = array(
  			1 => 'Jan',
  			2 => 'Feb',
  			3 => 'Mar', 
  			4 => 'Apr',
  			5 => 'May',
  			6 => 'Jun', 
			7 => 'jul',
			8 => 'Aug',
			9 => 'Sep', 
			10 => 'Oct',
			11 => 'Nov',
			12 => 'Dec'
		);
  
		$b = explode('-',$a);

		$b[1] = ltrim($b[1], '0');
		$b[2] = ltrim($b[2], '0');

		$c = $b[2].' '.$month[$b[1]].' '.$b[0];

		return trim($c)==''?'-':$c;

	}

	
}


?>