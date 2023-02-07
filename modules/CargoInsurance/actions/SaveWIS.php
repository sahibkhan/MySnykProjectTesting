<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

//include 'modules/CargoInsurance/wisclass.php';
include 'include/CargoInsurance/wisclass.php';
//print_r($_POST); exit;

class CargoInsurance_SaveWIS_Action extends Vtiger_Action_Controller {
	var $cargoRecordIds = Array();
	
	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView', 'record_parameter' => 'record');
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		if ($request->has('record')) {
			$recordId = $request->get('record');
			$moduleName = getSalesEntityType($recordId);
			$permissionStatus  = Users_Privileges_Model::isPermitted($moduleName,  'EditView', $recordId);
			if($permissionStatus){
				$this->cargoRecordIds[] = $recordId;
			}
			if(empty($this->cargoRecordIds)){
				throw new AppException(vtranslate('LBL_RECORD_PERMISSION_DENIED'));
			}
		}
		return true;
	}
	
	public function process(Vtiger_Request $request) {
		global $adb;
		global $current_user;
		//$adb->setDebug(true);
		
		$module = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$recordId = $request->get('record');
		$current_user_id = $current_user->id;
		
		$pcompany = $request->get('company');
		//print_r($request); exit;
		//echo $recordId;
		//echo " User Id ".$current_user->id;
		//print_r($current_user);
		//die;

		
		// get record from vtiger_cargoinsurancecf
		$query_ci = "SELECT * FROM `vtiger_cargoinsurancecf` WHERE `cargoinsuranceid` = ?";
		$params_ci = array($recordId);	
		$result_ci = $adb->pquery($query_ci, $params_ci);
		$row_ci = $adb->fetch_array($result_ci);
		//echo $row_ci['cargoinsuranceid'];	
		
		//get InsuredName, InsuredAddress,
		$query_ins = "SELECT accountname FROM `vtiger_account` WHERE `accountid` = ?";
		$params_ins = array($row_ci['cf_3601']);	// Beficiery
		$result_ins = $adb->pquery($query_ins, $params_ins);
		$row_ins = $adb->fetch_array($result_ins);
		
		$insuredName = $row_ins['accountname'];
		
		if (!$insuredName) {
			echo json_encode(array(
				'status' => 0,
				'type' => 'insuredName',
				'message' => "?? ?????? accountid {$row_ci['cf_3601']} ? ??????? accountname", //acc id not found in accname table
				'errors' => ''
			));
			die;
		}
		//echo "insname". $insuredName;	
		
		//get Insured Country
		$query_ins_country = "SELECT c.country_code 
                                        FROM vtiger_accountbillads vc 
                                        JOIN countries c 
                                        ON vc.bill_country = c.country_name 
                                        WHERE vc.`accountaddressid` = ?";
		$params_ins_country = array($row_ci['cf_3601']);	// Beficiery
		$result_ins_country = $adb->pquery($query_ins_country, $params_ins_country);
		$row_ins_country = $adb->fetch_array($result_ins_country);
		$insuredCountry = $row_ins_country['country_code'];
		
		$quoteData['insuredName'] = $insuredName;
		$quoteData['insuredAddress'] = array(
			'countryCode' => $insuredCountry ? $insuredCountry : 'kz', // @TODO
			'admin1RegionName' => '',
			'cityName' => '',
			'street' => '',
			'postal' => ''
		);
		$quoteData['insuredEmail'] = '';
		$quoteData['insuredPhone'] = '';
		$quoteData['consignee'] = array(
			'name' => '',
			'address' => array(
				'countryCode' => '',
				'admin1RegionName' => '',
				'cityName' => '',
				'street' => '',
				'postal' => ''
			)
		);
		//echo "Qoute Data ";
		//print_r($quoteData);
		
		//departureDate
	//    $departureDate = $data['cf_3613'];
		$departureDate = date("Y-m-d"); //"2020-02-20"; // @TODO
		$quoteData['departureDate'] = $departureDate;

		//arrivalDate
		$arrivalDate = $row_ci['cf_3615']; //To Date
		$arrivalDate = "2020-02-30"; // @TODO
		$arrivalDate = ""; // @TODO
		$quoteData['arrivalDate'] = $arrivalDate;

		//cifMarkup
		//need to ask which field
		//need field from special range
		$quoteData['cifMarkup'] = floatval(0.00);

		//commodities

		//declaredValue cf_3639
		$declaredValue = $row_ci['cf_3639']; //Total Sum To Be Insured
		$quoteData['commodities']['declaredValue'] = $declaredValue;
		
		//print_r($quoteData);
		
		//declaredValueCurrency
		$query_ins_currency = "SELECT `currency_code` FROM `vtiger_currency_info` WHERE `id` =  ?";
		$params_ins_currency = array($row_ci['cf_3663']);	// currency
		$result_ins_currency = $adb->pquery($query_ins_currency, $params_ins_currency);
		$row_ins_currency = $adb->fetch_array($result_ins_currency);
		$declaredValueCurrency = $row_ins_currency['currency_code']; //KZT, USD etc.
		$quoteData['commodities']['declaredValueCurrency'] = $declaredValueCurrency;
		//echo "SELECT `currency_code` FROM `vtiger_currency_info` WHERE `id` = ".$row_ci['cf_3601'];
		//print_r($quoteData); exit;


		// fetch commodityid
		$query_ins_commodity = "SELECT `wis_key` FROM `vtiger_commoditytype` WHERE `commoditytypeid` = ?";
		
		
		//echo "<br>".$query_ins_commodity;
		//echo "<br> comod type : ".$row_ci['cf_3625'];
		
		$params_ins_commodity = array($row_ci['cf_3625']);	// Commodity Type
		$result_ins_commodity = $adb->pquery($query_ins_commodity, $params_ins_commodity);
		$row_ins_commodity = $adb->fetch_array($result_ins_commodity);
		
		//echo "<br>Comodity type id ".$row_ci['cf_3625'];
		//print_r($result_ins_commodity); exit;
				
		$commodityid = $row_ins_commodity['wis_key'];
		
		//echo "<br>Comodity type id ".$commodityid;
		
		//echo "commodityid:".$commodityid."\n";
		$quoteData['commodities']['id'] = $commodityid;
		$quoteData['commodities']['optionId'] = '';
		//print_r($quoteData); exit;
		
		//origin
		//countryCode cf_3605
		$countryCode = $row_ci['cf_3605'];
		$quoteData['origin']['countryCode'] = $countryCode;
		$quoteData['origin']['admin1RegionName'] = '';
		//cityName cf_3607
		$cityName = $row_ci['cf_3607'];
		$quoteData['origin']['cityName'] = $cityName;

		//destination
		//countryCode [cf_3609]
		$countryCode = $row_ci['cf_3609'];
		$quoteData['destination']['countryCode'] = $countryCode;
		$quoteData['destination']['admin1RegionName'] = $countryCode;
		//cityName cf_3611
		$cityName = $row_ci['cf_3611'];
		$quoteData['destination']['cityName'] = $cityName;
		
		
		$wis = new WIS($pcompany);
		
		//conveyance
		$transportationMode = $row_ci['cf_3619'];
		
		//echo "transo id ".$transportationMode;
		//exit;
		//echo "<br> get id ";
		//echo wis::getConveyanceId($transportationMode);
		$quoteData['conveyance']['id'] = wis::getConveyanceId($transportationMode);
		//print_r($quoteData); exit;
		

		//packaging
		//cf_3627
		$stmt3 = WIS::DB()->prepare("SELECT `wis_key` FROM `vtiger_specialrange` WHERE `specialrangeid` = ?");
		$stmt3->execute(array($row_ci['cf_3627']));
		$packagingid = $stmt3->fetchColumn();
		$quoteData['packaging']['id'] = $packagingid;

		//voyage
		$quoteData['voyage'] = array(
			'number' => '',
			'vessel' => array(
				'imo' => '',
				'name' => ''
			),
			'portFromName' => '',
			'portFromLocation' => array(
				'countryCode' => $row_ci['cf_3605'],
				'admin1RegionName' => '',
				'cityName' => ''
			),
			'portToName' => '',
			'portToLocation' => array(
				'countryCode' => $row_ci['cf_3609'],
				'admin1RegionName' => '',
				'cityName' => ''
			)
		);

		//descriptions
		//cf_3617
		$quoteData['descriptions'] = array(
			array(
				'description' => $row_ci['cf_3617'],
				'quantity' => 1
			)
		);
		/*  $quoteData['descriptions']['description']  = $data['cf_3617'] ? $data['cf_3617'] : '';
		  $quoteData['descriptions']['quantity']  = 5;*/

		$quoteData['bolNumber'] = '';
		$quoteData['lcConditions'] = '';
		$quoteData['lcNumber'] = '';
		
		//echo "Quote Data "; print_r($quoteData); exit;
		
		//reference
		//SELECT * FROM `vtiger_cargoinsurance` WHERE `cargoinsuranceid` = 1900839
		//echo "<br>Insurance ID ".$insurance_id;

		$stmt2 = WIS::DB()->prepare("SELECT `name` FROM `vtiger_cargoinsurance` WHERE `cargoinsuranceid` =  ?");
		$stmt2->execute(array($insurance_id));
		$reference = $stmt2->fetchColumn();
		$quoteData['reference'] = $reference;


		$quoteData['invoiceNumber'] = '';
		$quoteData['certificateAssignment'] = 'client';
		$quoteData['certificateAssignmentOther'] = '';
		
		
		//print_r($quoteData); exit;
		
		//START SENDING REQUEST TO WIS
		//$wis = new WIS($pcompany);

		$login = $wis->login();
		
		//var_dump($login); die;
		if ($login['status'] == 0) {
			echo json_encode(
				array(
				'status' => 0,
				'type' => 'login',
				'message' => "Error in wis Login",
				'errors' => ''
				)
			);
			die;
		}
		
		/*if ($login['status'] == 0) {
			echo json_encode(
				array(
				'status' => 0,
				'type' => 'login',
				'message' => implode(",",   $login['message'] ),
				'errors' => ''
				)
			);
			die;
		}*/
		
		//echo "dep date".$departureDate;
		
		
		$declarationId = $wis->createDeclaration($departureDate);
		//echo " declaration id ";
		//var_dump($declarationId); die;
		
		if (isset($declarationId['status'])) {
			echo json_encode(array(
				'status' => 0,
				'type' => 'createDeclaration',
				'message' => $declarationId['message']['message'],
				'errors' => $declarationId['message']['errors'][0]['message']
			));
			die;
		} else {

			//echo "bfore qutte data dec id ".$declarationId;
			//print_r($quoteData);
			//exit;
			$QuoteResponse = $wis->getQuote($declarationId, $quoteData);
			//echo "qut resp ";
			//print_r($QuoteResponse); die;
		}
		
		//echo "chk"; //exit;
		
		
		$messages = '';
		//print_r($QuoteResponse); die;

		// TODO receiving array of quotes. how to show them?
		if (!$QuoteResponse['quotes']) {
			
			echo json_encode(array(
				'status' => 0,
				'type' => 'QuoteResponse',
				'message' => $QuoteResponse['message'],
				'errors' => $QuoteResponse['errors'][0]['message']
			));
			die;
		}
		//print_r($QuoteResponse); die;
		// if there is more than one quote then need to show an make selection
		//var_dump($QuoteResponse); die;
		if( count($QuoteResponse['quotes']) > 1) {
			//echo "chount 1";
			//var_dump($declarationId); die;
			//need to create html form with submit button. when is pressed goes to different php file and gets WISREF
			foreach ($QuoteResponse['quotes'] as $quote) {
				$cout .= '<input type="radio" id="quoteIds" name="quote" value="'. $quote['id']. '">&nbsp;'/*<b>' . $quote['premium']. '</b>&nbsp;'.$QuoteResponse['currency'].'&nbsp;&nbsp;'*/ .$quote['deductible'] . '<br>';
			}
			$cout .= '<button type="button" id="quoteContinue" class="btn btn-success">Continue</button>';
			echo json_encode( array(
				'status' => -2,
				'message' => $cout,
				'declarationId' => $declarationId

			));
			exit;
		} else {
			//echo "chount";
			$cout .= '<input type="radio" checked id="quoteIds" name="quote" value="'. $QuoteResponse['quotes'][0]['id']. '">&nbsp;'/*<b>' . $QuoteResponse['quotes'][0]['premium']. '</b>&nbsp;'.$QuoteResponse['currency'].'&nbsp;&nbsp;' */.$QuoteResponse['quotes'][0]['deductible'] . '<br>';
			$cout .= '<button type="button" id="quoteContinue" class="btn btn-success">Continue</button>';
			echo json_encode( array(
				'status' => -2,
				'message' => $cout,
				'declarationId' => $declarationId
			));
			exit;
		}
		
		
		//echo "login to wis success";
		
		//die();
		
		
		/*
		
		//print_r($wis);
		//var_dump($login); die;
		if ($login['status'] == 0) {
			echo json_encode(
				array(
				'status' => 0,
				'type' => 'login',
				'message' => implode(",",   $login['message'] ),
				'errors' => ''
				)
			);
			die;
		}
		else
		{
			echo json_encode(
				array(
				'status' => 1,
				'type' => 'login',
				'message' => implode(",",   $login['message'] ),
				'errors' => ''
				)
			);
			die;
		}
		*/
		/*
		$transferOwnerId = $request->get('transferOwnerId');
		if(!empty($this->transferRecordIds)){
			$recordIds = $this->transferRecordIds;
		}
		$result = $moduleModel->transferRecordsOwnership($transferOwnerId, $recordIds);
		*/
		/*
		$result = true;
		$response = new Vtiger_Response();
		if ($result === true) {
			$response->setResult(true);
		} else {
			$response->setError($result);
		}
		$response->emit();
		*/
		$response = new Vtiger_Response();
		$response->setResult(true);
		$response->emit();
		
	}
	

}
