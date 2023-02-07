{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
	{assign var=RELATED_MODULE_NAME value=$RELATED_MODULE->get('name')}
	{include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE LISTVIEW_HEADERS=$RELATED_HEADERS}
	<div class="relatedContainer">
		{assign var=IS_RELATION_FIELD_ACTIVE value="{if $RELATION_FIELD}{$RELATION_FIELD->isActiveField()}{else}false{/if}"}
		<input type="hidden" name="currentPageNum" value="{$PAGING->getCurrentPage()}" />
		<input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE_NAME}" />
		<input type="hidden" value="{$ORDER_BY}" id="orderBy">
		<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
		<input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
		<input type='hidden' value="{$PAGING->getPageLimit()}" id='pageLimit'>
		<input type='hidden' value="{$PAGING->get('page')}" id='pageNumber'>
		<input type="hidden" value="{$PAGING->isNextPageExists()}" id="nextPageExist"/>
		<input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
		<input type='hidden' value="{$TAB_LABEL}" id='tab_label' name='tab_label'>
		<input type='hidden' value="{$IS_RELATION_FIELD_ACTIVE}" id='isRelationFieldActive'>

		{include file="partials/RelatedListHeader.tpl"|vtemplate_path:$RELATED_MODULE_NAME}
		{if $MODULE eq 'Products' && $RELATED_MODULE_NAME eq 'Products' && $TAB_LABEL === 'Product Bundles' && $RELATED_LIST_LINKS}
			<div data-module="{$MODULE}" style = "margin-left:20px">
				{assign var=IS_VIEWABLE value=$PARENT_RECORD->isBundleViewable()}
				<input type="hidden" class="isShowBundles" value="{$IS_VIEWABLE}">
				<label class="showBundlesInInventory checkbox"><input type="checkbox" {if $IS_VIEWABLE}checked{/if} value="{$IS_VIEWABLE}">&nbsp;&nbsp;{vtranslate('LBL_SHOW_BUNDLE_IN_INVENTORY', $MODULE)}</label>
			</div>
		{/if}
	<form class="form-horizontal recordEditView" id="QuickCreate" name="QuickCreate" method="post" action="index.php">
			<div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container">
				<div class="bottomscroll-div">
					<table id="listview-table" class="table listview-table listViewEntriesTable">
						<thead>
							<tr class="listViewHeaders">
								<th style="min-width:100px">
								</th>
							{foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                             {if $HEADER_FIELD->get('column') eq 'name'}   {continue}{/if}
								{* hide time_start,time_end columns in the list as they are merged with with Start Date and End Date fields *}
								{if $HEADER_FIELD->get('column') eq 'time_start' or $HEADER_FIELD->get('column') eq 'time_end'}
									<th class="nowrap" style="width:15px">
								{else}
									<th class="nowrap">
									{if $HEADER_FIELD->get('column') eq "access_count" or $HEADER_FIELD->get('column') eq "idlists"}
										<a href="javascript:void(0);" class="noSorting">{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE_NAME)}</a>
									{else}
										<a href="javascript:void(0);" class="listViewContentHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-fieldname="{$HEADER_FIELD->get('column')}">
											{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}
												<i class="fa fa-sort {$FASORT_IMAGE}"></i>
											{else}
												<i class="fa fa-sort customsort"></i>
											{/if}
											&nbsp;
											{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE_NAME)}
											&nbsp;{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}<img class="{$SORT_IMAGE}">{/if}&nbsp;
										</a>
										{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}
											<a href="#" class="removeSorting"><i class="fa fa-remove"></i></a>
										{/if}
									{/if}
								{/if}
								</th>
							{/foreach}
						</tr>
						{*
						<tr class="searchRow">
							<th class="inline-search-btn">
								<button class="btn btn-success btn-sm" data-trigger="relatedListSearch">{vtranslate("LBL_SEARCH",$MODULE)}</button>
							</th>
							{foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                             {if $HEADER_FIELD->get('column') eq 'name'}   {continue}{/if}
								<th>
									{if $HEADER_FIELD->get('column') eq 'time_start' or $HEADER_FIELD->get('column') eq 'time_end' or $HEADER_FIELD->getFieldDataType() eq 'reference'}
									{else}
										{assign var=FIELD_UI_TYPE_MODEL value=$HEADER_FIELD->getUITypeModel()}
										{include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$RELATED_MODULE_NAME) FIELD_MODEL= $HEADER_FIELD SEARCH_INFO=$SEARCH_DETAILS[$HEADER_FIELD->getName()] USER_MODEL=$USER_MODEL}
										<input type="hidden" class="operatorValue" value="{$SEARCH_DETAILS[$HEADER_FIELD->getName()]['comparator']}">
									{/if}
								</th>
							{/foreach}
						</tr>*}
					</thead>
                    <tbody>
					<tr></tr>
					{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
                    {*data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'*}
						<tr class="listViewEntries" data-id='{$RELATED_RECORD->getId()}' 
							{if $RELATED_MODULE_NAME eq 'Calendar'}
								data-recurring-enabled='{$RELATED_RECORD->isRecurringEnabled()}'
								{assign var=DETAILVIEWPERMITTED value=isPermitted($RELATED_MODULE_NAME, 'DetailView', $RELATED_RECORD->getId())}
								{if $DETAILVIEWPERMITTED eq 'yes'}
									data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'
								{/if}
							{else}
								
							{/if}>
							<td class="related-list-actions">
								<span class="actionImages">&nbsp;&nbsp;&nbsp;
									{if $IS_EDITABLE && $RELATED_RECORD->isEditable()}
										{if $RELATED_MODULE_NAME eq 'PriceBooks' AND (!empty($RELATED_HEADERS['listprice']) || !empty($RELATED_HEADERS['unit_price']))}
											{if !empty($RELATED_HEADERS['listprice'])}
												{assign var="LISTPRICE" value=CurrencyField::convertToUserFormat($RELATED_RECORD->get('listprice'), null, true)}
											{/if}
										{/if}
										{if $RELATED_MODULE_NAME eq 'PriceBooks'}
											<a data-url="index.php?module=PriceBooks&view=ListPriceUpdate&record={$PARENT_RECORD->getId()}&relid={$RELATED_RECORD->getId()}&currentPrice={$LISTPRICE}"
												class="editListPrice cursorPointer" data-related-recordid='{$RELATED_RECORD->getId()}' data-list-price={$LISTPRICE}
										{else if $MODULE eq 'Products' && $RELATED_MODULE_NAME eq 'Products' && $TAB_LABEL === 'Product Bundles' && $RELATED_LIST_LINKS && $PARENT_RECORD->isBundle()}
											{assign var=quantity value=$RELATED_RECORD->get($RELATION_FIELD->getName())}
											<a class="quantityEdit"
												data-url="index.php?module=Products&view=SubProductQuantityUpdate&record={$PARENT_RECORD->getId()}&relid={$RELATED_RECORD->getId()}&currentQty={$quantity}"
												onclick ="Products_Detail_Js.triggerEditQuantity('index.php?module=Products&view=SubProductQuantityUpdate&record={$PARENT_RECORD->getId()}&relid={$RELATED_RECORD->getId()}&currentQty={$quantity}');if(event.stopPropagation){ldelim}event.stopPropagation();{rdelim}else{ldelim}event.cancelBubble=true;{rdelim}"
										{else}
											<a name="relationEdit" data-url="{$RELATED_RECORD->getEditViewUrl()}"
										{/if}
										><i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $MODULE)}"></i></a> &nbsp;&nbsp;
									{/if}

									{if $IS_DELETABLE}
										<a class="relationDelete"><i title="{vtranslate('LBL_UNLINK', $MODULE)}" class="vicon-linkopen"></i></a>
									{/if}
									{if $RELATED_RECORD->getDisplayValue('cf_5820') eq 'REDJOB'}
									&nbsp;<a href="index.php?module=RailwayFleet&view=Print&record={$RELATED_RECORD->getId()}" target="_blank"><i title="Print Wagon Invoice" class="fa fa-print alignMiddle"></i></a>&nbsp;
									 {/if}
								</span>

							</td>
							{foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                             {if $HEADER_FIELD->get('column') eq 'name'}   {continue}{/if}
								{assign var=RELATED_HEADERNAME value=$HEADER_FIELD->get('name')}
								{assign var=RELATED_LIST_VALUE value=$RELATED_RECORD->get($RELATED_HEADERNAME)}
								<td class="{if $RELATED_HEADERNAME eq 'cf_5820'}related-list-actions{else}relatedListEntryValues{/if}" title="{strip_tags($RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME))}" data-field-type="{$HEADER_FIELD->getFieldDataType()}" nowrap>
									<span class="value textOverflowEllipsis">
										{if $RELATED_MODULE_NAME eq 'Documents' && $RELATED_HEADERNAME eq 'document_source'}
											<center>{$RELATED_RECORD->get($RELATED_HEADERNAME)}</center>
											{else}
												{if $HEADER_FIELD->isNameField() eq true or $HEADER_FIELD->get('uitype') eq '4'}
												<a href="{$RELATED_RECORD->getDetailViewUrl()}">{$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}</a>
											{elseif $RELATED_HEADERNAME eq 'access_count'}
												{$RELATED_RECORD->getAccessCountValue($PARENT_RECORD->getId())}
											{elseif $RELATED_HEADERNAME eq 'time_start' or $RELATED_HEADERNAME eq 'time_end'}
											{elseif $RELATED_MODULE_NAME eq 'PriceBooks' AND ($RELATED_HEADERNAME eq 'listprice' || $RELATED_HEADERNAME eq 'unit_price')}
												{if $RELATED_HEADERNAME eq 'listprice'}
													{assign var="LISTPRICE" value=CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME), null, true)}
												{/if}
												{CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME), null, true)}
											{elseif $HEADER_FIELD->get('uitype') eq '71' or $HEADER_FIELD->get('uitype') eq '72'}
												{assign var=CURRENCY_SYMBOL value=Vtiger_RelationListView_Model::getCurrencySymbol($RELATED_RECORD->get('id'), $HEADER_FIELD)}
												{assign var=CURRENCY_VALUE value=CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME))}
												{if $HEADER_FIELD->get('uitype') eq '72'}
													{assign var=CURRENCY_VALUE value=CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME), null, true)}
												{/if}
												{if Users_Record_Model::getCurrentUserModel()->get('currency_symbol_placement') eq '$1.0'}
													{$CURRENCY_SYMBOL}{$CURRENCY_VALUE}
												{else}
													{$CURRENCY_VALUE}{$CURRENCY_SYMBOL}
												{/if}
												{if $RELATED_HEADERNAME eq 'listprice'}
													{assign var="LISTPRICE" value=CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME), null, true)}
												{/if}
											{else if $HEADER_FIELD->getFieldDataType() eq 'picklist'}
												{if $RELATED_MODULE_NAME eq 'Calendar' or $RELATED_MODULE_NAME eq 'Events'}
													{if $RELATED_RECORD->get('activitytype') eq 'Task'}
														{assign var=PICKLIST_FIELD_ID value={$HEADER_FIELD->getId()}}
													{else}
														{if $HEADER_FIELD->getName() eq 'taskstatus'}
															{assign var="EVENT_STATUS_FIELD_MODEL" value=Vtiger_Field_Model::getInstance('eventstatus', Vtiger_Module_Model::getInstance('Events'))}
															{if $EVENT_STATUS_FIELD_MODEL}
																{assign var=PICKLIST_FIELD_ID value={$EVENT_STATUS_FIELD_MODEL->getId()}}
															{else} 
																{assign var=PICKLIST_FIELD_ID value={$HEADER_FIELD->getId()}}
															{/if}
														{else}
															{assign var=PICKLIST_FIELD_ID value={$HEADER_FIELD->getId()}}
														{/if}
													{/if}
												{else}
													{assign var=PICKLIST_FIELD_ID value={$HEADER_FIELD->getId()}}
												{/if}
												<span {if !empty($RELATED_LIST_VALUE)} class="picklist-color picklist-{$PICKLIST_FIELD_ID}-{Vtiger_Util_Helper::convertSpaceToHyphen($RELATED_LIST_VALUE)}" {/if}> {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)} </span>
											{elseif $RELATED_HEADERNAME eq 'cf_5820'}
                                            <a target="_blank" 
                                            href="index.php?module=Job&relatedModule=Jobexpencereport&view=Detail&record={$RELATED_RECORD->get($RELATED_HEADERNAME)}&mode=showRelatedList&relationId=167&tab_label=Job%20Revenue%20and%20Expence&app=JOB">
                                            {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}</a>
                                            {elseif $RELATED_HEADERNAME eq 'cf_5822'}
                                            <input id="RailwayFleet_editView_fieldName_cf_5822_{$RELATED_RECORD->getId()}" type="text" 
                                            class="listSearchContributor inputElement" 
                                            name="local_internal_selling[{$RELATED_RECORD->getId()}-{$RELATED_RECORD->get('cf_5820')}]" value="{$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}" 
                                            data-fieldinfo="">
                                            {elseif $RELATED_HEADERNAME eq 'cf_5824'}                            
                                            <select  class="select2" id="RailwayFleet_editView_fieldName_cf_5824_{$RELATED_RECORD->getId()}"  
                                             name="{$RELATED_HEADERNAME}[{$RELATED_RECORD->getId()}-{$RELATED_RECORD->get('cf_5820')}]" 
                                             style="width: 85px !important;" id="selRVQ">
                                             <option value="1" data-picklistvalue="1"  {if $RELATED_RECORD->get($RELATED_HEADERNAME) eq '1'} selected="selected" {/if} >KZT</option>
                                            <option value="2" data-picklistvalue="2" {if $RELATED_RECORD->get($RELATED_HEADERNAME) eq '2'} selected="selected" {/if}>USD</option>
                                            <option value="28" data-picklistvalue="28" {if $RELATED_RECORD->get($RELATED_HEADERNAME) eq '28'} selected="selected" {/if}>RUB</option>
                                            </select>

                                            {elseif $RELATED_HEADERNAME eq 'cf_5826'}
                                            <input id="RailwayFleet_editView_fieldName_cf_5826_{$RELATED_RECORD->getId()}" type="text" class="listSearchContributor inputElement" 
                                            name="internal_selling[{$RELATED_RECORD->getId()}-{$RELATED_RECORD->get('cf_5820')}]" 
                                            value="{$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}" 
                                            data-fieldinfo="" readonly="readonly"> USD

                                            {else}
												<input id="RailwayFleet_editView_fieldName_{$RELATED_HEADERNAME}" type="hidden" class="input-mini" 
												data-validation-engine="validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
												name="{$RELATED_HEADERNAME}[{$RELATED_RECORD->getId()}-{$RELATED_RECORD->get('cf_5820')}]" value="{$RELATED_RECORD->get($RELATED_HEADERNAME)}" 
												data-fieldinfo="">
												{$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
												{* Documents list view special actions "view file" and "download file" *}
												{if $RELATED_MODULE_NAME eq 'Documents' && $RELATED_HEADERNAME eq 'filename' && isPermitted($RELATED_MODULE_NAME, 'DetailView', $RELATED_RECORD->getId()) eq 'yes'}
													<span class="actionImages">
														{assign var=RECORD_ID value=$RELATED_RECORD->getId()}
														{assign var="DOCUMENT_RECORD_MODEL" value=Vtiger_Record_Model::getInstanceById($RECORD_ID)}
														{if $DOCUMENT_RECORD_MODEL->get('filename') && $DOCUMENT_RECORD_MODEL->get('filestatus')}
															<a name="viewfile" href="javascript:void(0)" data-filelocationtype="{$DOCUMENT_RECORD_MODEL->get('filelocationtype')}" data-filename="{$DOCUMENT_RECORD_MODEL->get('filename')}" onclick="Vtiger_Header_Js.previewFile(event)"><i title="{vtranslate('LBL_VIEW_FILE', $RELATED_MODULE_NAME)}" class="icon-picture alignMiddle"></i></a>&nbsp;
															{/if}
															{if $DOCUMENT_RECORD_MODEL->get('filename') && $DOCUMENT_RECORD_MODEL->get('filestatus') && $DOCUMENT_RECORD_MODEL->get('filelocationtype') eq 'I'}
															<a name="downloadfile" href="{$DOCUMENT_RECORD_MODEL->getDownloadFileURL()}"><i title="{vtranslate('LBL_DOWNLOAD_FILE', $RELATED_MODULE_NAME)}" class="icon-download-alt alignMiddle"></i></a>&nbsp;
															{/if}
													</span>
												{/if}
											{/if}
										{/if}
									</span>
								</td>
							{/foreach}
						</tr>
					{/foreach}
                    </tbody>
                    <tfoot>
                     <tr class="listViewEntries">
               		<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium" align="right">Total Trip Revenew: </td> 
					<td nowrap="" class="medium">{$TOTAL_REVENEW} USD</td> 
						<td nowrap="" class="medium"></td> 
						<!-- <td nowrap="" class="medium"></td> -->
					</tr>
					<tr class="listViewEntries">
						<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium" align="right">Internal Selling Final: </td>
					<td nowrap="" class="medium">
					
						<input type="checkbox" name="internal_selling_final" value="yes" {if $INTERNAL_SELLING_FINAL eq 'yes'}  disabled="disabled" checked="checked" {/if}  />
					
					</td> 
						<td nowrap="" class="medium"></td> 
						<!-- <td nowrap="" class="medium"></td> -->
					</tr>  
					<tr class="listViewEntries">
						<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium"></td> 
					<td nowrap="" class="medium" align="right">Final Distribution:</td>
					<td nowrap="" class="medium">
					{if $INTERNAL_SELLING_FINAL eq 'yes'}
					<input type="checkbox" name="internal_selling_flag" value="yes"  {if $INTERNAL_SELLING_DISTRIBUTION eq 'yes'} checked="checked" {/if}   />
					{/if}
					</td> 
						<td nowrap="" class="medium"></td> 
						<!--  <td nowrap="" class="medium"></td> -->
					</tr>   
					<tr class="listViewEntries">
						<td colspan="5" nowrap=""  class="medium" align="right">  
						<button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>              
					
						{if $FROM_TO_DATE_MESSAGE neq ''}               
						<font style="color:#F00;font-size:14px; font-weight:bold; padding-left:20px;">
						{$FROM_TO_DATE_MESSAGE}
						</font>
						{/if}
						<input type="hidden" value="{$MODULE}" name="sourceModule">
						<input type="hidden" value="{$PARENT_RECORD->getId()}" name="sourceRecord">
						<input type="hidden" value="true" name="relationOperation">
						<input type="hidden" name="module" value="{$RELATED_MODULE->get('name')}">
						<input type="hidden" name="action" value="SaveAjax">
						</td>  
						<td colspan="2" nowrap=""  class="medium" align="right">
					<!--<button class="btn btn-success" type="submit"><strong>Update Internal Selling</strong></button>-->
					
					
						</td>          
						</tr>
					</tfoot>
						</table>
				</div>
			</div>
		</form>
		 <div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container" style="margin-top: 10px;">
		   	 	<div class="bottomscroll-div">
                    <table class="table listview-table" id="listview-table" >
                      <thead>      
                      <tr class="listViewHeaders"><th colspan="5">Costing Breakdown</th>
                      </tr>
                       </thead>
                      <thead>
                      <tr class="listViewHeaders">
                        <th>&nbsp;</th>
                      	<th>{vtranslate('Job File', $RELATED_MODULE->get('name'))}</th><th>{vtranslate('Internal Selling', $RELATED_MODULE->get('name'))}</th>
                        <th>{vtranslate('Costing breakdown', $RELATED_MODULE->get('name'))}</th><th>{vtranslate('Profit breakdown', $RELATED_MODULE->get('name'))}</th>
                        <th>{vtranslate('JCR', $RELATED_MODULE->get('name'))}</th></tr>
                      </thead>
                      <tbody>
                       {foreach item=PROFIT from=$PROFIT_SHARE}                     
                      	<tr class="listViewEntries">
                           <td>&nbsp;</td>
                        <td>{$PROFIT['job_ref_no']}</td>
                         <td>{$PROFIT['internal_selling']}</td>
                        <td>{$PROFIT['cost']}</td>
                        <td>{$PROFIT['job_profit']}</td>                       
                        <td>
						<a href="index.php?module=Jobexpencereport&view=Print&record={$PROFIT['job_id']}&userid={$PROFIT['user_id']}&expense=SUBJCR&type=JCR"
						 target="_blank" title=""><i class="fa fa-print alignMiddle" title=""></i></a></td>
                        </tr>
                         {/foreach} 
                      </tbody>
                       <thead>
                          <tr  class="listViewHeaders">
                           <th>&nbsp;</th>
                          <th>{vtranslate('Total USD', $RELATED_MODULE->get('name'))}</th>
                          <th>{$SUM_OF_INTERNAL_SELLING}</th>                          
                          <th>{$SUM_OF_COST}</th>
                          <th>{$SUM_OF_JOB_PROFIT}</th>
                          <th></th>
                           </tr>
                         
                       </thead>
                     </table> 
                </div>
        </div>

		<script type="text/javascript">
			var related_uimeta = (function () {
				var fieldInfo = {$RELATED_FIELDS_INFO};
				return {
					field: {
						get: function (name, property) {
							if (name && property === undefined) {
								return fieldInfo[name];
							}
							if (name && property) {
								return fieldInfo[name][property]
							}
						},
						isMandatory: function (name) {
							if (fieldInfo[name]) {
								return fieldInfo[name].mandatory;
							}
							return false;
						},
						getType: function (name) {
							if (fieldInfo[name]) {
								return fieldInfo[name].type
							}
							return false;
						}
					}
				};
			})();
		</script>
	</div>

	{literal}
<script>
setTimeout(railwayexchangefile, 1500);
function  railwayexchangefile()
{
	$( '[name^="local_internal_selling"]' ).blur(function() {
		
		var tr_id = $(this).parents('tr').attr('data-id');
		
		var railway_internal_selling_local = $(this).closest('tr').find('[name^="local_internal_selling"]').val();
		
		var pay_to_currency = $(this).closest('tr').find('[name^="cf_5824"]').val();
		var internal_selling_date = $(this).closest('tr').find('[name^="cf_5818"]').val();
		//alert(railway_internal_selling_local+' '+pay_to_currency+' '+internal_selling_date);
		$.post('include/Exchangerate/usd_internal_selling.php',{internal_selling_date: internal_selling_date, railway_internal_selling_local: railway_internal_selling_local, pay_to_currency: pay_to_currency},function(data){			
				var result=JSON.parse(data);
				//alert(result['usd_internal_selling']);				
				//$('#'+tr_id).find('[name^="internal_selling"]').val( result['usd_internal_selling'] );
				$('#RailwayFleet_editView_fieldName_cf_5826_'+tr_id).val( result['usd_internal_selling'] );
				
		  });		
	});
	
	
	$('[name^="cf_5824"]').change(function(){
	var tr_id = $(this).parents('tr').attr('data-id');
	
	var railway_internal_selling_local = $(this).closest('tr').find('[name^="local_internal_selling"]').val();
		
		var pay_to_currency = $(this).closest('tr').find('[name^="cf_5824"]').val();
		var internal_selling_date = $(this).closest('tr').find('[name^="cf_5818"]').val();
		//alert(railway_internal_selling_local+' '+pay_to_currency+' '+internal_selling_date);
		$.post('include/Exchangerate/usd_internal_selling.php',{internal_selling_date: internal_selling_date, railway_internal_selling_local: railway_internal_selling_local, pay_to_currency: pay_to_currency},function(data){			
				var result=JSON.parse(data);
				//alert(result['usd_internal_selling']);				
				//$('#'+tr_id).find('[name^="internal_selling"]').val( result['usd_internal_selling'] );
				$('#RailwayFleet_editView_fieldName_cf_5826_'+tr_id).val( result['usd_internal_selling'] );
				
		  });
	});
}
</script>
{/literal}
{/strip}