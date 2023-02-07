{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
<style>
	span.createReferenceRecord.cursorPointer { display: none; }
</style>
	{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
		<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
	{/if}
<input type="hidden" name="username" id="username" value='{$creator}' />
<input type="hidden" name="department" id="department" value='{$departmentname}' />
<input type="hidden" name="username" id="locationID" value='{$locationID}' />
<input type="hidden" name="departmentID" id="departmentID" value='{$departmentID}' />
<input type="hidden" name="requestnumber" id="requestnumber" value='{$REQUESTNUMBER}' />
<input type="hidden" name="Requested_Item" id="Requested_Item" value='{$proc_proctype}' />
<input type="hidden" name="local_currency" id="local_currency" value='{$CURRENCY_CODE}' />
<input type="hidden" name="current_user" id="current_user" value='{$CURRENT_USERCURRENCYID}' />
<input type="hidden" name="sending_approval_status_checker" id="sending_approval_status_checker" value="{$sending_approvals[0]['who_approve_id']}" />
<input type="hidden" name="proc_department" id="proc_department" value='{$departmentname}'/>
<input type="hidden" name="requestedurl" id="requestedurl" value="{$requestedurl}"/>
<input type="hidden" name="proc_location" id="proc_location" value="{$locations}"/>

	<div name='editContent'>
		{if $DUPLICATE_RECORDS}
			<div class="fieldBlockContainer duplicationMessageContainer">
				<div class="duplicationMessageHeader"><b>{vtranslate('LBL_DUPLICATES_DETECTED', $MODULE)}</b></div>
				<div>{getDuplicatesPreventionMessage($MODULE, $DUPLICATE_RECORDS)}</div>
			</div>
		{/if}
		{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE name=blockIterator}
			{if $BLOCK_FIELDS|@count gt 0}
				<div class='fieldBlockContainer' data-block="{$BLOCK_LABEL}">
					<h4 class='fieldBlockHeader'>{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
					<hr>
					<table class="table table-borderless">
						<tr>
							{assign var=COUNTER value=0}
							{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
								{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
								{assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
								{assign var="refrenceListCount" value=count($refrenceList)}
								{if $FIELD_MODEL->isEditable() eq true}
									{if $FIELD_MODEL->get('uitype') eq "19"}
										{if $COUNTER eq '1'}
											<td></td><td></td></tr><tr>
											{assign var=COUNTER value=0}
										{/if}
									{/if}
									{if $COUNTER eq 2}
									</tr><tr>
										{assign var=COUNTER value=1}
									{else}
										{assign var=COUNTER value=$COUNTER+1}
									{/if}
									<td class="fieldLabel alignMiddle">
										{if $isReferenceField eq "reference"}
											{if $refrenceListCount > 1}
												{assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
												{assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
												{if !empty($REFERENCED_MODULE_STRUCTURE)}
													{assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCTURE->get('name')}
												{/if}
												<select style="width: 140px;" class="select2 referenceModulesList">
													{foreach key=index item=value from=$refrenceList}
														<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if}>{vtranslate($value, $value)}</option>
													{/foreach}
												</select>
											{else}
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
											{/if}
										{else if $FIELD_MODEL->get('uitype') eq "83"}
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) COUNTER=$COUNTER MODULE=$MODULE}
											{if $TAXCLASS_DETAILS}
												{assign 'taxCount' count($TAXCLASS_DETAILS)%2}
												{if $taxCount eq 0}
													{if $COUNTER eq 2}
														{assign var=COUNTER value=1}
													{else}
														{assign var=COUNTER value=2}
													{/if}
												{/if}
											{/if}
										{else}
											{if $MODULE eq 'Documents' && $FIELD_MODEL->get('label') eq 'File Name'}
												{assign var=FILE_LOCATION_TYPE_FIELD value=$RECORD_STRUCTURE['LBL_FILE_INFORMATION']['filelocationtype']}
												{if $FILE_LOCATION_TYPE_FIELD}
													{if $FILE_LOCATION_TYPE_FIELD->get('fieldvalue') eq 'E'}
														{vtranslate("LBL_FILE_URL", $MODULE)}&nbsp;<span class="redColor">*</span>
													{else}
														{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
													{/if}
												{else}
													{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
												{/if}
											{else}
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
											{/if}
										{/if}
										&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
									</td>
									{if $FIELD_MODEL->get('uitype') neq '83'}
										<td class="fieldValue" {if $FIELD_MODEL->getFieldDataType() eq 'boolean'} style="width:25%" {/if} {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										</td>
									{/if}
								{/if}
							{/foreach}
							{*If their are odd number of fields in edit then border top is missing so adding the check*}
							{if $COUNTER is odd}
								<td></td>
								<td></td>
							{/if}
						</tr>
					</table>
				</div>
			{/if}
		{/foreach}

	</div>
	{if $recordid}

		<style>
		table .adjust-width tr td .attribute{
			width:100% !important;
		}
		</style>

		{assign var=totalvaluescounter value=$totalrecords}
		<p id="counterinput" style="visibility:hidden">{$totalvaluescounter}</p>
		<div name='editContent' style="max-width:100%">
			<div class="fieldBlockContainer" data-block="{$BLOCK_LABEL}" style="max-width:100%">
				<h4 class="fieldBlockHeader">Details</h4>
				<div class="relatedContents contents-bottomscroll">
				<div class="bottomscroll-div">
					<table id="tablemainid" class="table table-striped table-bordered nowrap table-responsive" style="max-width:100%;border: 1px solid Red">

						<thead style="background-color:#White;color:Grey;max-width:100%">
							<hr>
						<tr class="listViewHeaders" style="max-width:100%">
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Expense Type</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Description</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Quantity</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Price Per Unit</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Local Price</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">VAT (%)</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Price VAT</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Gross (Local)</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Currency</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Final Amount (Gross)</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Final Amount (Net)</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Total USD</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Current Qty</th>
							
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Last QTY Purchased</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Last Purchase Unit Price</th>
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Last 12 Month Consumption</th>
								{if $sending_approvals[0]['who_approve_id'] eq 0}
							<th nowrap="" style="border:1px solid #D4D4D4 !important;">Action</th>
								{/if}
						</tr>

									</thead>
						<tbody style="max-width:100%" class="adjust-width">
							{if $expense_details}
	<input type="hidden" value="{$expensetotalrecords}" name="expensetotalrecords">
							{foreach key=FIELD_NAME item=FIELD_MODEL from=$expense_details}
							<input type="hidden" value="{$FIELD_MODEL['procurementitemsid']}" name="procurementitemsid[]">

							<tr class="listViewEntries" style="max-width:100%" id="apend-{$FIELD_MODEL['procurementitemsid']}">

								{if $CHILD_TABLE_RECORD}
													<td nowrap="" style="border:1px solid #D4D4D4 !important;">
														<select {if $proc_proctype eq '4786741'} onchange="get_stock(this);" {/if} class="attribute select2" id="toselect" name="childvales[]">
															{foreach key=CHILD_NAME item=CHILD_MODEL from=$CHILD_TABLE_RECORD}
															<option value="{$CHILD_MODEL['procurementtypeitemsid']}" {if $FIELD_MODEL['expence_type'] eq $CHILD_MODEL['procurementtypeitemsid']} selected {/if}>{$CHILD_MODEL['childname']}</option>
															{/foreach}
														</select>
													</td>
							<td nowrap="" style="border:1px solid #D4D4D4 !important;">
							<textarea class="attribute" name="description[]" id="description">{$FIELD_MODEL['description']}</textarea>
							</td>
							<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="qty1[]" value="{$FIELD_MODEL['quantity']}" id="qty1" onblur="getlocalprice('#apend-{$FIELD_MODEL['procurementitemsid']}')"/></td>
								<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="psc1[]" value="{$FIELD_MODEL['ppu']}" id="psc1" onblur="getlocalprice1('#apend-{$FIELD_MODEL['procurementitemsid']}')"/></td>
									<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="localprice[]" readonly value="{$FIELD_MODEL['local_price']}" id="localprice"/></td>
									<td nowrap="" style="border:1px solid #D4D4D4 !important;">
										<input type="hidden" value="{$FIELD_MODEL['vat_rate']}" id="vat_original_value">
										<input class="attribute" type="text" name="VAT[]" value="{$FIELD_MODEL['vat_rate']}" id="VAT" onblur="get_currency_data('#apend-{$FIELD_MODEL['procurementitemsid']}')"/></td>
										<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="PriceVAT[]" readonly value="{$FIELD_MODEL['vat']}" id="pricevat"/></td>
										<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="gross[]" readonly value="{$FIELD_MODEL['gross']}" id="gross"/></td>

									{if $CURRENCYDATA}
														<td nowrap="" style="border:1px solid #D4D4D4 !important;">
	<input type="hidden" id="hidden_change_currency" value="{$FIELD_MODEL['local_currency_code']}">

															<select class="attribute select2" id="currency" name="currency[]" onchange="get_currency_data('apend-{$FIELD_MODEL['procurementitemsid']}')">
																{foreach key=CURRENCY_NAME item=CURRENCY_MODEL from=$CURRENCYDATA}

																<option value="{$CURRENCY_MODEL['id']}" {if $FIELD_MODEL['local_currency_code'] eq $CURRENCY_MODEL['id']} selected {/if}>{$CURRENCY_MODEL['currency_code']}</option>
																{/foreach}
															</select>
														</td>
														{/if}

												<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="gross_local[]" readonly value="{$FIELD_MODEL['gross_local']}" id="gross_local"/></td>
												<td nowrap="" style="border:1px solid #D4D4D4 !important;">
													<input class="attribute" type="hidden" name="Total_local_currency_hidden" readonly id="Total_local_currency_hidden" value="0"/>
													<input class="attribute" type="text" name="Total_local_currency[]" readonly value="{$FIELD_MODEL['total_local_amount']}" id="Total_local_currency"/></td>
													<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="Total_USD[]" readonly value="{$FIELD_MODEL['total_in_usd']}" id="Total_USD"/></td>

													<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="current_qty[]" readonly value="{$FIELD_MODEL['current_qty']}" id="current_qty_{$FIELD_MODEL['procurementitemsid']}"/></td>

													
													<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="last_qty[]" readonly value="{$FIELD_MODEL['last_qty']}" id="last_qty_{$FIELD_MODEL['procurementitemsid']}"/></td>
													<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="last_purchase_price[]" readonly value="{$FIELD_MODEL['last_purchase_price']}" id="last_purchase_price_{$FIELD_MODEL['procurementitemsid']}"/></td>
													<td nowrap="" style="border:1px solid #D4D4D4 !important;"><input class="attribute" type="text" name="avg_consumption[]" readonly value="{$FIELD_MODEL['avg_consumption']}" id="avg_consumption_{$FIELD_MODEL['procurementitemsid']}"/></td>
													

							{/if}
							{if $sending_approvals[0]['who_approve_id'] eq 0}
							<td nowrap="">
								<button type="button" class="btn" id="deleteaddrecord" onClick="deleterecord({$FIELD_MODEL['procurementitemsid']})"><span class="add-on"><i class="fa fa-trash"></i></span></button>
							</td>
							{/if}


								{/foreach}
								{else}
								<input type="hidden" value="0" name="procurementitemsid[]">
																	{/if}
						</tbody>


					</table>
			</div></div>
				{if $sending_approvals[0]['who_approve_id'] eq 0}
	<input type="button" id="addingbutton" value="Add +">
		{/if}
		</div>
			</div>

			<table style="visibility:hidden !important">
				<tbody id="hiddendiv">
					<tr id="appendtr" style="max-width:100%">


		</tr>
			</tbody>
			</table>
			<input type="hidden" id="checkvalue">
			{else}
			<style>
			table .adjust-width tr td .attribute{
				width:100% !important;
			}


			</style>
			{assign var=totalvaluescounter value=$totalrecords}

			<p id="counterinput1" style="visibility:hidden">{$totalvaluescounter}</p>
			<div name='editContent' style="max-width:100%">
				<div class="fieldBlockContainer" data-block="{$BLOCK_LABEL}" style="max-width:100%">
					<h4 class="fieldBlockHeader">Details</h4>
					<div class="relatedContents contents-bottomscroll">
						<div class="bottomscroll-div">
						<table id="tablemainid2" class="table table-striped table-bordered nowrap table-responsive" style="max-width:100%;border: 1px solid Red">

							<thead style="background-color:#White;color:Grey;max-width:100%">
								<hr>


							<tr class="listViewHeaders" style="max-width:100%">

								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Expense Type</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Description</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Quantity</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Price Per Unit</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Local Price</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">VAT (%)</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Price VAT</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Gross (Local)</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Currency</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Final Amount (Gross)</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Final Amount (Net)</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Total USD</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Current Qty</th>
								
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Last QTY Purchased</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Last Purchase Unit Price</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Last 12 Month Consumption</th>
								<th nowrap="" style="border:1px solid #D4D4D4 !important;">Action</th>

							</tr>
					</thead>

							<tbody style="max-width:100%" class="adjust-width">



							</tbody>


						</table>
					</div></div>
		<p>&nbsp;</p>
		<input type="button" id="addingbutton2" value="Add +" style="display:none;">
			</div>
				</div>

				<table style="visibility:hidden !important">
					<tbody id="hiddendiv2">




						<!-- {*<td style="border:1px solid #D4D4D4 !important;"><select class="toselect attribute" id="toselect"><option>{$FIELD_MODEL['exense_type']}</option></select></td>*} -->

				</tbody>
				</table>
				<input type="hidden" id="checkvalue1">
			{/if}


<script>
var user_name = $("#username").val();
var department = ' '+$("#department").val();
var departmentID = $("#departmentID").val();
var locationsname = ' '+$("#proc_location").val();

$("[name=assigned_user_id] option").filter(function() {
    return ($(this).text() == user_name);}).prop('selected', true);

$("[name=proc_department] option").filter(function() {
    return ($(this).text() == department);}).prop('selected', true);

$("[name=proc_location] option").filter(function() {
    return ($(this).text() == locationsname);}).prop('selected', true);

/*$("p").filter(function(){  
    return $("span", this).length == 2;}).css("background-color", "yellow");

$('.id_100 option').each(function() {
    if($(this).val() == 'val2') {
        $(this).prop("selected", true);
    }
});*/


</script>

{/strip}
