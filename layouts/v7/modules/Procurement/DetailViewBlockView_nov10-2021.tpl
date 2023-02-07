{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}

	{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
		<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
	{/if}

	{foreach key=BLOCK_LABEL_KEY item=FIELD_MODEL_LIST from=$RECORD_STRUCTURE}
		{assign var=BLOCK value=$BLOCK_LIST[$BLOCK_LABEL_KEY]}
		{if $BLOCK eq null or $FIELD_MODEL_LIST|@count lte 0}{continue}{/if}
		<div class="block block_{$BLOCK_LABEL_KEY}" data-block="{$BLOCK_LABEL_KEY}" data-blockid="{$BLOCK_LIST[$BLOCK_LABEL_KEY]->get('id')}">
			{assign var=IS_HIDDEN value=$BLOCK->isHidden()}
			{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
			<input type=hidden name="timeFormatOptions" data-value='{$DAY_STARTS}' />
			<div>
				<h4 class="textOverflowEllipsis maxWidth50">
					<img class="cursorPointer alignMiddle blockToggle {if !($IS_HIDDEN)} hide {/if}" src="{vimage_path('arrowRight.png')}" data-mode="hide" data-id={$BLOCK_LIST[$BLOCK_LABEL_KEY]->get('id')}>
					<img class="cursorPointer alignMiddle blockToggle {if ($IS_HIDDEN)} hide {/if}" src="{vimage_path('arrowdown.png')}" data-mode="show" data-id={$BLOCK_LIST[$BLOCK_LABEL_KEY]->get('id')}>&nbsp;
					{vtranslate({$BLOCK_LABEL_KEY},{$MODULE_NAME})}
				</h4>
			</div>
			<hr>
			<div class="blockData">
				<table class="table detailview-table no-border">
					<tbody {if $IS_HIDDEN} class="hide" {/if}>
						{assign var=COUNTER value=0}
						<tr>
							{foreach item=FIELD_MODEL key=FIELD_NAME from=$FIELD_MODEL_LIST}
								{assign var=fieldDataType value=$FIELD_MODEL->getFieldDataType()}
								{if !$FIELD_MODEL->isViewableInDetailView()}
									{continue}
								{/if}
								{if $FIELD_MODEL->get('uitype') eq "83"}
									{foreach item=tax key=count from=$TAXCLASS_DETAILS}
										{if $COUNTER eq 2}
											</tr><tr>
											{assign var="COUNTER" value=1}
										{else}
											{assign var="COUNTER" value=$COUNTER+1}
										{/if}
										<td class="fieldLabel {$WIDTHTYPE}">
											<span class='muted'>{vtranslate($tax.taxlabel, $MODULE)}(%)</span>
										</td>
										<td class="fieldValue {$WIDTHTYPE}">
											<span class="value textOverflowEllipsis" data-field-type="{$FIELD_MODEL->getFieldDataType()}" >
												{if $tax.check_value eq 1}
													{$tax.percentage}
												{else}
													0
												{/if}
											</span>
										</td>
									{/foreach}
								{else if $FIELD_MODEL->get('uitype') eq "69" || $FIELD_MODEL->get('uitype') eq "105"}
									{if $COUNTER neq 0}
										{if $COUNTER eq 2}
											</tr><tr>
											{assign var=COUNTER value=0}
										{/if}
									{/if}
									<td class="fieldLabel {$WIDTHTYPE}"><span class="muted">{vtranslate({$FIELD_MODEL->get('label')},{$MODULE_NAME})}</span></td>
									<td class="fieldValue {$WIDTHTYPE}">
										<ul id="imageContainer">
											{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
												{if !empty($IMAGE_INFO.url) && !empty({$IMAGE_INFO.orgname})}
													<li><img src="{$IMAGE_INFO.url}" title="{$IMAGE_INFO.orgname}" width="400" height="300" /></li>
												{/if}
											{/foreach}
										</ul>
									</td>
									{assign var=COUNTER value=$COUNTER+1}
								{else}
									{if $FIELD_MODEL->get('uitype') eq "20" or $FIELD_MODEL->get('uitype') eq "19" or $fieldDataType eq 'reminder' or $fieldDataType eq 'recurrence'}
										{if $COUNTER eq '1'}
											<td class="fieldLabel {$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td></tr><tr>
											{assign var=COUNTER value=0}
										{/if}
									{/if}
									{if $COUNTER eq 2}
										</tr><tr>
										{assign var=COUNTER value=1}
									{else}
										{assign var=COUNTER value=$COUNTER+1}
									{/if}
									<td class="fieldLabel textOverflowEllipsis {$WIDTHTYPE}" id="{$MODULE_NAME}_detailView_fieldLabel_{$FIELD_MODEL->getName()}" {if $FIELD_MODEL->getName() eq 'description' or $FIELD_MODEL->get('uitype') eq '69'} style='width:8%'{/if}>
										<span class="muted">
											{if $MODULE_NAME eq 'Documents' && $FIELD_MODEL->get('label') eq "File Name" && $RECORD->get('filelocationtype') eq 'E'}
												{vtranslate("LBL_FILE_URL",{$MODULE_NAME})}
											{else}
												{vtranslate({$FIELD_MODEL->get('label')},{$MODULE_NAME})}
											{/if}
											{if ($FIELD_MODEL->get('uitype') eq '72') && ($FIELD_MODEL->getName() eq 'unit_price')}
												({$BASE_CURRENCY_SYMBOL})
											{/if}
										</span>
									</td>
									<td class="fieldValue {$WIDTHTYPE}" id="{$MODULE_NAME}_detailView_fieldValue_{$FIELD_MODEL->getName()}" {if $FIELD_MODEL->get('uitype') eq '19' or $fieldDataType eq 'reminder' or $fieldDataType eq 'recurrence'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
										{assign var=FIELD_VALUE value=$FIELD_MODEL->get('fieldvalue')}
										{if $fieldDataType eq 'multipicklist'}
											{assign var=FIELD_DISPLAY_VALUE value=$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'))}
										{else}
											{assign var=FIELD_DISPLAY_VALUE value=Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}
										{/if}

										<span class="value" data-field-type="{$FIELD_MODEL->getFieldDataType()}" {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '21'} style="white-space:normal;" {/if}>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getDetailViewTemplateName(),$MODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}
										</span>
										{if $IS_AJAX_ENABLED && $FIELD_MODEL->isEditable() eq 'true' && $FIELD_MODEL->isAjaxEditable() eq 'true'}
											<span class="hide edit pull-left">
												{if $fieldDataType eq 'multipicklist'}
													<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}[]' data-type="{$fieldDataType}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
												{else}
													<input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$fieldDataType}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
												{/if}
											</span>
											<span class="action pull-right"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span>
										{/if}
									</td>
								{/if}

								{if $FIELD_MODEL_LIST|@count eq 1 and $FIELD_MODEL->get('uitype') neq "19" and $FIELD_MODEL->get('uitype') neq "20" and $FIELD_MODEL->get('uitype') neq "30" and $FIELD_MODEL->get('name') neq "recurringtype" and $FIELD_MODEL->get('uitype') neq "69" and $FIELD_MODEL->get('uitype') neq "105"}
									<td class="fieldLabel {$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
								{/if}
							{/foreach}
							{* adding additional column for odd number of fields in a block *}
							{if $FIELD_MODEL_LIST|@end eq true and $FIELD_MODEL_LIST|@count neq 1 and $COUNTER eq 1}
								<td class="fieldLabel {$WIDTHTYPE}"></td><td class="{$WIDTHTYPE}"></td>
							{/if}
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<br>
	{/foreach}
	<style>
	table .adjust-width tr td .attribute{
		width:100% !important;
	}
	table .total_row {
	border:0;
	}
	table .total_row td {
	border:0;
	}
	.bg_grey{
	background-color:#ccc;font-weight:bold;border-right:solid #999 1px !important;
	}
	</style>

	{assign var=totalvaluescounter value=$totalrecords}

	<p id="counterinput" style="visibility:hidden">{$totalvaluescounter}</p>
	<div name='editContent'>
		<div class="fieldBlockContainer details" data-block="{$BLOCK_LABEL}">
			<h4 class="fieldBlockHeader">Details</h4>
			<div class="relatedContents contents-bottomscroll">
				<div class="bottomscroll-div">
			<table id="tablemainid" class="table table-striped table-bordered nowrap table-responsive" style="border: 1px solid Grey">

				<thead style="background-color:White;color:Grey;">
					<hr>
				<tr class="listViewHeaders">

					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Expense Type</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Description</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Quantity</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Price Per Unit</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Local Price</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">VAT (%)</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Price VAT</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Gross (Local)</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Currency</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Gross (Local)</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Total (Local Currency Net)</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Total USD</th>

					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Current Qty</th>				

					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Last QTY Purchased</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Last Purchase Unit Price</th>
					<th class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">Last 12 Month Consumption</th>

				</tr>

							</thead>
				<tbody style="" class="adjust-width">
					{if $Procurementitemsdata}

					{foreach key=FIELD_NAME item=FIELD_MODEL from=$Procurementitemsdata}

					<tr class="listViewEntries" style="background-color:#ffffff;">
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_proctypeitem_id']}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_description']}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_qty']}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_unit_price']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_line_price']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_vat_unit']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_vat_amount']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_gross_amount']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_currency']}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_gross_local']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_gross_finalamount']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_total_usd']|number_format:2:".":","}</td>

						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_current_qty']}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_lastpurchase_qty']}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_lastpurchase_price']|number_format:2:".":","}</td>
						<td class="medium" nowrap="" style="border:1px solid #D4D4D4 !important;">{$FIELD_MODEL['procitem_avg_consumption']|number_format:2:".":","}</td>
						
					</tr>
						
					{/foreach}
					
					<tr class="listViewEntries total_row" style="">
						<td colspan="3" nowrap="" style="background-color:#666;color:#fff;text-align:center;font-weight:bold;">TOTAL AMOUNT</td>
						<td class="bg_grey medium" nowrap="">{$unit_price_total}</td>
						<td class="bg_grey medium" nowrap="">{$local_price_total}</td>
						<td class="bg_grey medium" nowrap="">-</td>
						<td class="bg_grey medium" nowrap="">{$vat_price_total}</td>
						<td class="bg_grey medium" nowrap="">{$gross_local}</td>
						<td class="bg_grey medium" nowrap="">-</td>
						<td class="bg_grey medium" nowrap="">{$gross_local_total}</td>
						<td class="bg_grey medium" nowrap="">{$total_net}</td>
						<td class="bg_grey medium" nowrap="">{$total_usd}</td>
						<td class="bg_grey medium" nowrap="">-</td>
						<td class="bg_grey medium" nowrap="">-</td>
						<td class="bg_grey medium" nowrap="">-</td>
						<td class="bg_grey medium" nowrap="">-</td>
					</tr>
					
					{/if}
				</tbody>

			</table>
		</div></div>
	<p>&nbsp;</p>
	{* arif code *}
	{if $Show_Approvals eq 1}
<div class="block block_LBL_CUSTOM_INFORMATION_NEW" data-block="LBL_CUSTOM_INFORMATION_NEW" data-blockid="123456789">
    <input type="hidden" name="timeFormatOptions" data-value="">
    <div>
    <h4 class="textOverflowEllipsis maxWidth50">
    <img class="cursorPointer alignMiddle blockToggle  hide " src="layouts/v7/skins/images/arrowRight.png" data-mode="hide" data-id="123456789">
    <img class="cursorPointer alignMiddle blockToggle" src="layouts/v7/skins/images/arrowdown.png" data-mode="show" data-id="123456789" style="display: inline;">&nbsp;Approval Information</h4>
    </div><hr>
    <div class="blockData">
        <table class="table detailview-table no-border">
            <tbody style="display: table-row-group;">
                <tr><td class="fieldLabel textOverflowEllipsis ">
				<table id="approval_authorities" class="table table-striped table-bordered nowrap table-responsive" style="max-width: 100%; border: 1px solid red; color: rgb(0, 0, 0);">
					<thead style="background-color:White;color:Grey;max-width:100%">
						<tr style="max-width:100%">
							<th style="border:1px solid #D4D4D4 !important;">No</th>
							<th style="border:1px solid #D4D4D4 !important;">Authority Name</th>
							<th style="border:1px solid #D4D4D4 !important;">Designation</th>
							<th style="border:1px solid #D4D4D4 !important;">Approval Status</th>
							<th style="border:1px solid #D4D4D4 !important;">Date</th>
						</tr>
					</thead>
					<tbody style="max-width:100%" class="adjust-width">
						{foreach item=authority_list from=$approval_authorities_list name=approval_list}
							<tr style="background-color:#ffffff;max-width:100%">
								<td style="border:1px solid #D4D4D4 !important;">{$smarty.foreach.approval_list.iteration}</td>
								<td style="border:1px solid #D4D4D4 !important;">{$authority_list.authority_name}</td>
								<td style="border:1px solid #D4D4D4 !important;">{$authority_list.designation}</td>
								<td style="border:1px solid #D4D4D4 !important;">{$authority_list.approval_status}</td>
								<td style="border:1px solid #D4D4D4 !important;">{$authority_list.approval_date}</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
                </td></tr>
            </tbody>
        </table>
    </div></div>
    {/if}
	<p>&nbsp;</p>
	{* end arif code *}
{/strip}