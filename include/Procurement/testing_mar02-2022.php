<?php
/*echo "<pre>";
print_r($_SERVER);exit;*/
//echo "asdasd";

	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	set_time_limit(0);
	date_default_timezone_set("UTC");
	ini_set('memory_limit','64M');
	$adb = PearDatabase::getInstance();
	 $data=$_POST['search'];
	 $data = explode(',',$data);
	 $majorid = $data[0];
	 $currentusercurrencyID = $data[1];
	 $PMtitle = $data[2];
	if($majorid){
	$result = $adb->pquery("SELECT companyid,cf_996,cf_1459 FROM `vtiger_companycf` where companyid ='$PMtitle'");
	$PMcurrency_id = $adb->query_result($result, 0, 'cf_1459');
	$paid_currency =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info` where id='$PMcurrency_id'");
  $paid_currency_id = $adb->query_result($paid_currency, 0, 'id');
//echo "SELECT * FROM `vtiger_procurementtypeitemscf` WHERE `cf_7520`=$majorid";
$query_rate =  $adb->pquery("SELECT * FROM `vtiger_procurementtypeitemscf` WHERE `proctypeitem_proctype`=$majorid");
$get_shortcode = $adb->pquery("SELECT * FROM `vtiger_procurementtypescf` WHERE `procurementtypesid`=$majorid");
$Proc_Type_shortcode = $adb->query_result($get_shortcode, 0, 'proctype_shortcode');

$pricebookarray=array();
if($adb->num_rows($query_rate)>0){
 	for($i=0; $i<$adb->num_rows($query_rate); $i++) {
//	echo "SELECT * FROM `vtiger_procurementtypeitems` WHERE `procurementtypeitemsid`=".$adb->query_result($query_rate, $i, 'cf_7520')."";
		  $childdata =  $adb->pquery("SELECT * FROM `vtiger_procurementtypeitems` inner join vtiger_procurementtypeitemscf on vtiger_procurementtypeitems.procurementtypeitemsid = vtiger_procurementtypeitemscf.procurementtypeitemsid  WHERE vtiger_procurementtypeitems.`procurementtypeitemsid`=".$adb->query_result($query_rate, $i, 'procurementtypeitemsid')."");
			$pricebookarray[$i]['procurementtypeitemsid'] = $adb->query_result($childdata, 0, 'procurementtypeitemsid');
			$pricebookarray[$i]['name'] = $adb->query_result($childdata, 0, 'name');
			$pricebookarray[$i]['code'] = $adb->query_result($childdata, 0, 'proctypeitem_code');

		}
		$query_currency =  $adb->pquery("SELECT `id`,`currency_code` FROM `vtiger_currency_info`");

		$currency_code=array();
		 	for($i=0; $i<$adb->num_rows($query_currency); $i++) {
					$currency_code[$i]['id'] = $adb->query_result($query_currency, $i, 'id');
					$currency_code[$i]['currency_code'] = $adb->query_result($query_currency, $i, 'currency_code');

				}

//print_r($pricebookarray);exit;
		?>
		<tr id="appendtr2" class="listViewEntries" style="max-width:100%">

			<td nowrap="">
		<select readon onchange="<?php if($Proc_Type_shortcode=='PM') echo 'get_stock(this);'; ?>"  class="<?php if($Proc_Type_shortcode=='PM') echo 'pm_list'; ?> select2 attribute" id="childvalues" name="childvales[]">
			<option>Select Option</option>
			<?php
		 foreach($pricebookarray as $value){
			 
			?>

				<option value="<?php echo lcfirst($value['procurementtypeitemsid']);?>" ><?php 
				if($Proc_Type_shortcode=='PM')
				{
					echo $value['code']." : ";
				}
				echo $value['name'];?></option>

			<?php
		 }
		?>
			</select>
		</td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;">
			<textarea class="attribute" name="description[]"  id="description"></textarea>
		</td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" name="qty1[]" type="text"  id="qty1"/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="psc1[]" id="psc1" onblur=""/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="localprice[]" id="localprice" readonly/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="VAT[]" id="VAT" onblur="get_currency_data('{$FIELD_MODEL['procurementitemid']}')"/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;" ><input class="attribute" type="text" name="PriceVAT[]" id="pricevat" readonly/></td>
<td style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="gross[]" readonly id="gross"/></td>
		<td nowrap="">
			<input type="hidden" id="hidden_change_currency" value="0">
	<select  class="select2 attribute" id="currency" name="currency[]" readonly="readonly" >
		<option>Select Option</option>
		<?php
   foreach($currency_code as $currencyvalue){
	  ?>

			<option value="<?php echo lcfirst($currencyvalue['id']);?>" <?php if($paid_currency_id==$currencyvalue['id']){?>selected<?php }?>><?php echo $currencyvalue['currency_code'];?></option>

		<?php
   }
  ?>
		</select>
	</td>

	  <td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="gross_local[]" readonly id="gross_local"/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;">
			<input class="attribute" type="hidden" name="Total_local_currency_hidden" readonly id="Total_local_currency_hidden" value="0"/>
			<input class="attribute" type="text" name="Total_local_currency[]" readonly id="Total_local_currency" />
		</td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="Total_USD[]" readonly id="Total_USD"/></td>

		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="current_qty[]" readonly  id="current_qty"/></td>

		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="last_qty[]" readonly  id="last_qty"/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="last_purchase_price[]" readonly id="last_purchase_price"/></td>
		<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="avg_consumption[]" readonly id="avg_consumption"/></td>
		
</tr>
<?php
}else{
	echo "no";
}
	}
?>