<?php
chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';

//require_once('include/database/PearDatabase.php');
date_default_timezone_set('UTC');
  
 
  $x = simplexml_load_file('http://nationalbank.kz/rss/rates_all.xml');

  $posts = array();
  $tableName_cur = 'vtiger_currency_info';
  
  global $adb;
  $currency_code_query = 'SELECT currency_code FROM '.$tableName_cur.'';
  $result = $adb->pquery($currency_code_query, array());
  $savedCurrencies = array();		
  for($jj=0; $jj< $adb->num_rows($result); $jj++ ) {
							
	$row_currency = $adb->fetch_row($result,$jj);
	$savedCurrencies[] = $row_currency['currency_code'];				
 }

 $final_array = array('date' => date('Y-m-d'),  'rate' => '1.00', 'currency_code' => 'KZT', 'quant' => 1); 
 saveExchangeRate($final_array);

 $today_strtotime = date('Y-m-d');
 //echo "<br>";
 foreach ($x->channel->item as $item)
	  {    
			 $currency_code = (string) $item->title;
		   
		   $pubdate_strtotime =  date('Y-m-d', strtotime($item->pubDate));
		   
			  //if(in_array($currency_code, $savedCurrencies) && $today_strtotime==$pubdate_strtotime)			
		   if(in_array($currency_code, $savedCurrencies))			
		   {
			  $pubDate    = strtotime($item->pubDate);
			  
			  //$post['date']  = date('Y-m-d', $pubDate);
			  $post['date'] = $today_strtotime;
			  //$post['link']  = (string) $item->link;
			  $post['currency_code'] = (string) $item->title;
			  $rate = (string) $item->description;
			  $quant = (string) $item->quant;
			  if(@$quant>0)
			   {
				   $rate = @$rate/@$quant;
			   }
			  $post['rate']  = $rate;
			  $post['quant']  = (string) $item->quant;
			 
			  $final_array = $post;
			  
			 //echo "<pre>";
			 //print_r($final_array);
			  //exit;
			 saveExchangeRate($final_array);
			  
		   
			  
		   }
		 
	   }

 function saveExchangeRate($final_array)
		{
		  	$adb = PearDatabase::getInstance();
			$current_user = 405;
			$date_var = date("Y-m-d H:i:s");
			$usetime = $adb->formatDate($date_var, true);
			$current_id = $adb->getUniqueId('vtiger_crmentity');
			
			//$rs = mysql_query("select * from vtiger_exchangeratecf where cf_1106='".$final_array['currency_code']."' AND cf_1108='".date('Y-m-d')."' ");
			
			$rs = "select * from vtiger_exchangeratecf where cf_1106='".$final_array['currency_code']."' AND cf_1108='".date('Y-m-d')."' ";
			$result_rs = $adb->pquery($rs, array());
			$num_rows = $adb->num_rows($result_rs);
			
			if($num_rows==0 && !empty($final_array))
			{
			
				$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime, presence, deleted, label)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array($current_id, $current_user, $current_user, 'Exchangerate', 'NULL', $date_var, $date_var, 1, 0, 'TEST LABEL'));
				
			//INSERT rate to vtiger_exchangerate
				$adb_e = PearDatabase::getInstance();
				$exchangerate_insert_query = "INSERT INTO vtiger_exchangerate(exchangerateid, name) VALUES(?,?)";
				$params_exchangerate= array($current_id, $final_array['rate']);			
				$adb_e->pquery($exchangerate_insert_query, $params_exchangerate);			
				$exchangerateid = $adb_e->getLastInsertID();
			
			
			//INSERT rate to vtiger_exchangeratecf
				$adb_ecf = PearDatabase::getInstance();
				$exchangeratecf_insert_query = "INSERT INTO vtiger_exchangeratecf(exchangerateid, cf_1106, cf_1108,cf_1112) VALUES(?, ?, ?, ?)";
				$params_params_exchangeratecf = array($current_id, $final_array['currency_code'], date("Y-m-d"), $final_array['quant']);
				$adb_ecf->pquery($exchangeratecf_insert_query, $params_params_exchangeratecf);
				$exchangeratecfid = $adb_ecf->getLastInsertID();
				
			}
			   
		}
?>