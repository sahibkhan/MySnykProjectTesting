<?php
chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';
require_once('include/custom_connectdb2.php'); // Подключение к базе данных
set_time_limit(0);
ini_set('memory_limit','64M');


$location_id = $_REQUEST['location_id'];
$pm_type_id = $_REQUEST['pm_type_id'];
$glk_company_id = $_REQUEST['company_id'];
$user_id = $_REQUEST['user_id'];


  $sourceModule = 'Users';
  $Users_info = Vtiger_Record_Model::getInstanceById($user_id, $sourceModule);
  $warehouse_id = $Users_info->get('assign_warehouse_id'); // User assigned warehouse

  $PMType_sourceModule = 'ProcurementTypeItems';
  $PMType_info = Vtiger_Record_Model::getInstanceById($pm_type_id, $PMType_sourceModule);
  $item_code = $PMType_info->get('proctypeitem_code');
  
  $sql_current_qty = "SELECT cf_6172 as current_qty
					FROM vtiger_reportofpackingmaterialcf
					inner join vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_reportofpackingmaterialcf.reportofpackingmaterialid
					WHERE vtiger_crmentity.deleted = 0 
					AND vtiger_reportofpackingmaterialcf.cf_6148='".$item_code."' 
					AND vtiger_reportofpackingmaterialcf.cf_6150 = '{$glk_company_id}'
					AND vtiger_reportofpackingmaterialcf.cf_6152 = '{$warehouse_id}'
					ORDER BY vtiger_crmentity.crmid DESC LIMIT 1";
		$rs_currentQty = mysqli_query($conn, $sql_current_qty);
	   	$total_rows = mysqli_num_rows($rs_currentQty);
		$stock=0;

		if($total_rows>0)
		{
			$row2 = mysqli_fetch_assoc($rs_currentQty);
			$stock = $row2['current_qty']; //current quantity
		}

/* $sql_last_qty = "SELECT cf_6160 as last_quantity_received,cf_6162 as last_purchase_price
					FROM vtiger_reportofpackingmaterialcf
					inner join vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_reportofpackingmaterialcf.reportofpackingmaterialid
					WHERE vtiger_crmentity.deleted = 0 
					AND vtiger_reportofpackingmaterialcf.cf_6148='".$item_code."' 
					AND vtiger_reportofpackingmaterialcf.cf_6150 = '{$glk_company_id}'
					AND vtiger_reportofpackingmaterialcf.cf_6152 = '{$warehouse_id}'
					AND vtiger_reportofpackingmaterialcf.cf_6184 = 'Receipt'
					AND vtiger_reportofpackingmaterialcf.cf_6178 LIKE 'RCVD%'
					ORDER BY vtiger_crmentity.crmid DESC LIMIT 1";
		$rs_lastQty = mysqli_query($conn, $sql_last_qty);
	   	$total_rows = mysqli_num_rows($rs_lastQty);
		$last_quantity_received=0;
		$last_purchase_price=0;
		if($total_rows>0)
		{
			$row2 = mysqli_fetch_assoc($rs_lastQty);
			$last_quantity_received = $row2['last_quantity_received']; //last_quantity_received
			$last_purchase_price = $row2['last_purchase_price']; //last_purchase_price
		} */
		
					
					

$sql_last_qty = "SELECT procitem_qty as last_quantity_purchased,procitem_unit_price as last_purchase_price
					FROM vtiger_procurementitemscf
					inner join vtiger_procurementcf ON vtiger_procurementcf.procurementid = vtiger_procurementitemscf.procitem_procid
					inner join vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_procurementitemscf.procurementitemsid
					WHERE vtiger_crmentity.deleted = 0 
					AND vtiger_procurementitemscf.procitem_proctypeitem_id='".$pm_type_id."'
					AND vtiger_procurementcf.proc_location='".$location_id."' 
					AND vtiger_procurementcf.proc_title = '{$glk_company_id}'
					AND (vtiger_procurementcf.proc_order_status = 'Approved' OR  vtiger_procurementcf.proc_order_status = 'Paid')
					ORDER BY vtiger_crmentity.crmid DESC LIMIT 1";
		$rs_lastQty = mysqli_query($conn, $sql_last_qty);
	   	$total_rows = mysqli_num_rows($rs_lastQty);
		$last_quantity_purchased=0;
		$last_purchase_price=0;
		if($total_rows>0)
		{
			$row2 = mysqli_fetch_assoc($rs_lastQty);
			$last_quantity_purchased = $row2['last_quantity_purchased']; //last_quantity_purchased
			$last_purchase_price = $row2['last_purchase_price']; //last_purchase_price
		}
		/*Below get Qty,Price from old module (PMRequsitions), else case */
		else
		{
			//$item_code
			$sql_last_qty = "SELECT cf_4281 as last_quantity_purchased,cf_4283 as last_purchase_price FROM vtiger_pmitemscf 
					inner join vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_pmitemscf.pmitemsid 
					inner join vtiger_pmtypecf ON vtiger_pmtypecf.pmtypeid = vtiger_pmitemscf.cf_4279 
					inner join vtiger_crmentityrel on vtiger_pmitemscf.pmitemsid = vtiger_crmentityrel.relcrmid 
					inner join vtiger_pmrequisitionscf on vtiger_pmrequisitionscf.pmrequisitionsid = vtiger_crmentityrel.crmid 
					WHERE vtiger_crmentity.deleted = 0 
					AND vtiger_pmtypecf.cf_4037='".$item_code."'
					AND vtiger_pmrequisitionscf.cf_4273='".$location_id."' 
					AND vtiger_pmrequisitionscf.cf_4271 = '{$glk_company_id}'
					AND (vtiger_pmrequisitionscf.cf_4593 = 'Approved' OR vtiger_pmrequisitionscf.cf_4593 = 'Paid') 
					AND vtiger_crmentityrel.relmodule = 'PMItems' 
					AND vtiger_crmentityrel.module = 'PMRequisitions' 
					ORDER BY vtiger_crmentity.crmid DESC LIMIT 1";
			$rs_lastQty = mysqli_query($conn, $sql_last_qty);
			$total_rows = mysqli_num_rows($rs_lastQty);
			$last_quantity_purchased=0;
			$last_purchase_price=0;
			if($total_rows>0)
			{
				$row2 = mysqli_fetch_assoc($rs_lastQty);
				$last_quantity_purchased = $row2['last_quantity_purchased']; //last_quantity_purchased
				$last_purchase_price = $row2['last_purchase_price']; //last_purchase_price
			}
			
		}

  //vtiger_whitemqtymastercf
  $inhouse = 'Yes';

/* $sql_avg_consumption_new = "SELECT 
						SUM(IF(month = 'January', total, 0)) AS 'January',
						SUM(IF(month = 'February', total, 0)) AS 'February',
						SUM(IF(month = 'March', total, 0)) AS 'March',
						SUM(IF(month = 'April', total, 0)) AS 'April',
						SUM(IF(month = 'May', total, 0)) AS 'May',
						SUM(IF(month = 'June', total, 0)) AS 'June',
						SUM(IF(month = 'July', total, 0)) AS 'July',
						SUM(IF(month = 'August', total, 0)) AS 'August',
						SUM(IF(month = 'September', total, 0)) AS 'September',
						SUM(IF(month = 'October', total, 0)) AS 'October',
						SUM(IF(month = 'November', total, 0)) AS 'November',
						SUM(IF(month = 'December', total, 0)) AS 'December',
						SUM(total) AS total_yearly
						FROM (
					SELECT DATE_FORMAT(cf_6182, '%b') AS month, SUM(cf_6166) as total
					FROM vtiger_reportofpackingmaterialcf
					WHERE vtiger_reportofpackingmaterialcf.cf_6184 !='Issue' 
					AND vtiger_reportofpackingmaterialcf.cf_6148='".$item_code."' 
					AND vtiger_reportofpackingmaterialcf.cf_6150 = '{$glk_company_id}'
					AND vtiger_reportofpackingmaterialcf.cf_6152 = '{$warehouse_id}'
					AND cf_6182 <= NOW() and cf_6182 >= Date_add(Now(),interval - 12 month)
					GROUP BY DATE_FORMAT(cf_6182, '%m-%Y')) as sub"; */

$sql_avg_consumption_new = "SELECT 
						SUM(IF(month = 'January', total, 0)) AS 'January',
						SUM(IF(month = 'February', total, 0)) AS 'February',
						SUM(IF(month = 'March', total, 0)) AS 'March',
						SUM(IF(month = 'April', total, 0)) AS 'April',
						SUM(IF(month = 'May', total, 0)) AS 'May',
						SUM(IF(month = 'June', total, 0)) AS 'June',
						SUM(IF(month = 'July', total, 0)) AS 'July',
						SUM(IF(month = 'August', total, 0)) AS 'August',
						SUM(IF(month = 'September', total, 0)) AS 'September',
						SUM(IF(month = 'October', total, 0)) AS 'October',
						SUM(IF(month = 'November', total, 0)) AS 'November',
						SUM(IF(month = 'December', total, 0)) AS 'December',
						SUM(total) AS total_yearly
						FROM (SELECT DATE_FORMAT(cf_6182, '%b') AS month, SUM(cf_6166) as total
					FROM vtiger_reportofpackingmaterialcf
					WHERE vtiger_reportofpackingmaterialcf.cf_6184 ='Issue' 
					AND vtiger_reportofpackingmaterialcf.cf_6148='".$item_code."' 
					AND vtiger_reportofpackingmaterialcf.cf_6150 = '{$glk_company_id}'
					AND vtiger_reportofpackingmaterialcf.cf_6152 = '{$warehouse_id}'
					AND cf_6182 <= NOW() and cf_6182 >= Date_add(Now(),interval - 12 month)
					GROUP BY DATE_FORMAT(cf_6182, '%m-%Y')) as sub";
		
$rs_avg_consumption_new = mysqli_query($conn, $sql_avg_consumption_new);
$rowsrs_avg_consumption_new = mysqli_num_rows($rs_avg_consumption_new);
$avg_consumption_new = 0;

if($rowsrs_avg_consumption_new>0)
{
	$row_avg_consumption_new = mysqli_fetch_assoc($rs_avg_consumption_new);
	$avg_consumption_new = $row_avg_consumption_new['total_yearly']!=''?$row_avg_consumption_new['total_yearly']:0; //AVG Consumption
}


$avg_consumption = $avg_consumption_new;

$inhand['last_quantity_received'] = $last_quantity_purchased; //$last_quantity_received;
$inhand['last_purchase_price'] = number_format($last_purchase_price, 2, '.', '');
$inhand['inhand'] = intval($stock); //current_quantity
$inhand['avg_consumption'] = number_format($avg_consumption, 2, '.', '');
echo json_encode($inhand);
?>