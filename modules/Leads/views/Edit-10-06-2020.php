<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Leads_Edit_View extends Vtiger_Edit_View {

	public function process(Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
        $recordModel = $this->record;
        if(!$recordModel){
            if (!empty($recordId)) {
                $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

                $recipientList = $recordModel->get('cf_853');
                $user_login = $this->arrange_muptiple_users($recipientList,2);
                $users = $this->arrange_muptiple_users($recipientList,1);
        
                $n = count($users);
                $value = '';
                for ($i=1;$i<=$n;$i++){
                    $value .= '<tr class="remove_invite_user'.$i.'"><td class="hide_invite_login" style="display:none">'.$user_login[$i].'</td>
                <td id="invite_user_format'.$i.'">'.$users[$i].'</td><td id="removeinviteduser"  data-id="'.$i.'">
                <img src="include/images/delete.png"></td></tr>';
                }
                $viewer->assign('RECEIPENT_LIST', $value);
            
            } else {
                $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
                $current_user = Users_Record_Model::getCurrentUserModel();
                $user_id = $current_user->getId(); 
                $request->set('cf_853', $user_id);						
                
                //Mehtab Code :: 25-10-2016
                if(empty($recordId)) {
                    //$current_user = Users_Record_Model::getCurrentUserModel();
                    $user_id = $current_user->getId(); 
                    $viewer->assign('USER_ID', $user_id);
                }
                $viewer->assign('RECEIPENT_LIST', '');
            }
        }

		

	$salutationFieldModel = Vtiger_Field_Model::getInstance('salutationtype', $recordModel->getModule());
	$salutationValue = $request->get('salutationtype');
        if(!empty($salutationValue)){ 
        	$salutationFieldModel->set('fieldvalue', $salutationValue); 
        } else{ 
        	$salutationFieldModel->set('fieldvalue', $recordModel->get('salutationtype')); 
        } 
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);

		parent::process($request);
    }
    
    function arrange_muptiple_users($users,$format){
		global $adb;
		$person_array = array();
		$buffer = '';
		$n = 0;
	
		// Search count of person
		for($i = 0; $i <= strlen($users); $i++){
			if ($users[$i] == '|'){
				$n ++;
				$buffer = trim($buffer);
				$sql_user = $adb->pquery("SELECT * FROM `vtiger_users` where `user_name` = '$buffer' ");
				$r_user = $adb->fetch_array($sql_user);
				if ($format == 1){
					$person_array[$n] = $this->arrange_user_format($r_user['user_name'],1);
				}
				else
					if ($format == 2){
						$person_array[$n] = $r_user['user_name'];
					}
					else
						if ($format == 3){
							$person_array[$n] = $r_user['email1'].';';
						}
				$buffer = '';
			} else $buffer = $buffer . $users[$i];
		}
		return $person_array;
	}

	// Mentioning full user format:  first name, last name, Department;
    function arrange_user_format($users,$mode){
        global $adb;
        // Вывод данных пользователей
        $user_login = trim($users);
        $res_users = $adb->pquery("Select * From `vtiger_users` where `user_name` = '$user_login' ");
        $row_user = $adb->fetch_array($res_users);
        if ($mode == 1){
            $title = $row_user['department'];
            $location = $row_user['address_city'];
            $str = '';
            if ($location == 'Almaty'){
                $str = $title.', Almaty';
            } else {
                $str = $location;
            }
            $output_detail = $row_user['first_name'] . ' ' . $row_user['last_name'].' / '.$str;
        }

        else
            if ($mode == 2){
                $output_detail = $row_user['email1'].';';
            }
            else
                if ($mode == 3){
                    $output_detail = $row_user['user_name'];
                }
                else
                    if ($mode == 4){
                        $output_detail = $row_user['first_name'] . ' ' . $row_user['last_name'];
                    }
        return $output_detail;
    }

}
