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
		 {if $RELATED_MODULE->get('name')=='JER'}
          {assign var=SHORT_LLB value='_SHORT'}
          {else}
           {assign var=SHORT_LLB value=''}
          {/if}
		<div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container">
			<div class="bottomscroll-div">
				<table id="listview-table" class="table listview-table" >
					<thead>
					<tr class="listViewHeaders">
					<th class="nowrap"></th>
					<th class="nowrap"></th>
					<th class="nowrap"></th>
            			<th colspan="11" class="nowrap">Expected Cost</th>
                		<th style="border-left: 2px solid rgb(0, 0, 255) ! important;" colspan="8" class="nowrap">Expected Revenue</th>      
              		</tr>

						<tr class="listViewHeaders">
							<th style="min-width:100px">
							</th>
							{foreach item=HEADER_FIELD from=$RELATED_HEADERS}

							{if $HEADER_FIELD@first} 
								<th> 
								{if $PARENT_RECORD->get('cf_1190') == '85840'} 
								Shipping Instructions						
								{else}
								Vendor Purchase Order
								{/if} </th><th>
								{if $PARENT_RECORD->get('cf_1190') == '85840'} 
								Booking Order						
								{else}
								Customer Booking Order
								{/if}						
								</th>

								{/if}

							{if $HEADER_FIELD->get('column') eq 'name'}  {continue} {/if}
								{* hide time_start,time_end columns in the list as they are merged with with Start Date and End Date fields *}
								{if $HEADER_FIELD->get('column') eq 'time_start' or $HEADER_FIELD->get('column') eq 'time_end'}
									<th class="nowrap" style="width:15px">
								{else}
									<th class="nowrap"  {if $HEADER_FIELD->getId()==1444}style="border-left: 2px solid rgb(0, 0, 255) ! important;" {/if} >
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
											{* {vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE_NAME)} *}
											{vtranslate($HEADER_FIELD->get('label')|cat:$SHORT_LLB, $RELATED_MODULE_NAME)}
											
											&nbsp;{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}<img class="{$SORT_IMAGE}">{/if}&nbsp;
										</a>
										{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}
											<a href="#" class="removeSorting"><i class="fa fa-remove"></i></a>
										{/if}
									{/if}
								{/if}
								</th>

								
								{if $HEADER_FIELD->get('column')=='cf_1160'}
								<th class="nowrap" >Local Currency</th>
								{/if}
								{if $HEADER_FIELD->get('column')=='cf_1168'}
								<th class="nowrap">Local Currency</th>
								{/if}
								

							{/foreach}
						</tr>
						<tr class="searchRow">
							<th class="inline-search-btn">
								<button class="btn btn-success btn-sm" data-trigger="relatedListSearch">{vtranslate("LBL_SEARCH",$MODULE)}</button>
							</th>
							<th>&nbsp;</th><th>&nbsp;</th>
							{foreach item=HEADER_FIELD from=$RELATED_HEADERS}
							 {if $HEADER_FIELD->get('column') eq 'name'} {continue} {/if}
								<th  {if $HEADER_FIELD->getId()==1444}style="border-left: 2px solid rgb(0, 0, 255) ! important;" {/if} >
									{if $HEADER_FIELD->get('column') eq 'time_start' or $HEADER_FIELD->get('column') eq 'time_end' or $HEADER_FIELD->getFieldDataType() eq 'reference'}
									{else}
										{assign var=FIELD_UI_TYPE_MODEL value=$HEADER_FIELD->getUITypeModel()}
										{include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$RELATED_MODULE_NAME) FIELD_MODEL= $HEADER_FIELD SEARCH_INFO=$SEARCH_DETAILS[$HEADER_FIELD->getName()] USER_MODEL=$USER_MODEL}
										<input type="hidden" class="operatorValue" value="{$SEARCH_DETAILS[$HEADER_FIELD->getName()]['comparator']}">
									{/if}
								</th>

								{if $HEADER_FIELD->get('column')=='cf_1160'}
								<th  >&nbsp;</th>
								{/if}
								{if $HEADER_FIELD->get('column')=='cf_1168'}
								<th >&nbsp;</th>
								{/if}

							{/foreach}

							

						</tr>
					</thead>
					 {assign var='i' value=0}
           			 {assign var='vpo' value=array()}
          			 {assign var='cbo' value=array()}

					{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
						<tr class="listViewEntries" data-id='{$RELATED_RECORD->getId()}' 
							{if $RELATED_MODULE_NAME eq 'Calendar'}
								data-recurring-enabled='{$RELATED_RECORD->isRecurringEnabled()}'
								{assign var=DETAILVIEWPERMITTED value=isPermitted($RELATED_MODULE_NAME, 'DetailView', $RELATED_RECORD->getId())}
								{if $DETAILVIEWPERMITTED eq 'yes'}
									data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'
								{/if}
							{else}
								data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'
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
										>
										{if ($PARENT_RECORD->get('cf_2197') eq "No Costing" || $PARENT_RECORD->get('cf_2197') eq "In Progress" || $PARENT_RECORD->get('cf_2197') eq "Revision") }
										<i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $MODULE)}"></i></a> &nbsp;&nbsp;
										{/if}   
									{/if}

									{if $IS_DELETABLE}
										<a class="relationDelete"><i title="{vtranslate('LBL_UNLINK', $MODULE)}" class="vicon-linkopen"></i></a>
									{/if}
								</span>

							</td>
							{foreach item=HEADER_FIELD from=$RELATED_HEADERS}
							
								{assign var=RELATED_HEADERNAME value=$HEADER_FIELD->get('name')}
								{assign var=RELATED_LIST_VALUE value=$RELATED_RECORD->get($RELATED_HEADERNAME)}

								 {if $HEADER_FIELD@first}	
										<td nowrap class="{$WIDTHTYPE}"> 
										
										{if !in_array($RELATED_RECORD->get('cf_1176'),$vpo)}
										{append var='vpo' value=$RELATED_RECORD->get('cf_1176')}
										
										<div class="pull-left">
											<span class="actionImages">
												{if $RELATED_RECORD->get('vpo_view_id') neq ''}
												<a href="index.php?module=VPO&view=Detail&record={$RELATED_RECORD->get('vpo_view_id')}&mode=showDetailViewByMode&requestMode=full"><i title="" class="icon-th-list alignMiddle"></i></a>
												
												{else} 
												{if $RELATED_RECORD->get('cf_1176') neq 0}                               
												<a href="index.php?module=VPO&view=Edit&sourceModule=JER&sourceRecord={$RELATED_RECORD->getId()}&relationOperation=true&vpo={$RELATED_RECORD->get('cf_1176')}"><i title="" class="icon-plus alignMiddle"></i>Add VPO</a>
												{/if}
											
												{/if}
											</span>
										</div>                           
										{/if}
										</td>
										<td nowrap class="{$WIDTHTYPE}" >                   
										{if !in_array($RELATED_RECORD->get('cf_1443'),$cbo)}
										{append var='cbo' value=$RELATED_RECORD->get('cf_1443')}
										<div class="pull-left">
											<span class="actionImages">                            
											
											{if $RELATED_RECORD->get('bo_view_id') neq ''}
												<a href="index.php?module=BO&view=Detail&record={$RELATED_RECORD->get('bo_view_id')}&mode=showDetailViewByMode&requestMode=full" title="View Booking Order"><i title="" class="icon-th-list alignMiddle"></i></a>
									
												{else}
												{if $RELATED_RECORD->get('cf_1443') neq 0}
												<a href="index.php?module=BO&view=Edit&sourceModule=JER&sourceRecord={$RELATED_RECORD->getId()}&relationOperation=true&cbo={$RELATED_RECORD->get('cf_1443')}"><i title="" class="icon-plus alignMiddle"></i>Add BO</a>
												{/if}
											{/if}
											</span>
										</div>   
										{/if}
										</td>	                        						
										
										{/if}

										 {if $HEADER_FIELD->get('column') eq 'name'}  {continue} {/if}
								
								<td class="relatedListEntryValues" title="{strip_tags($RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME))}" data-field-type="{$HEADER_FIELD->getFieldDataType()}"  
								{if $HEADER_FIELD->getId()==1444}style="border-left: 2px solid rgb(0, 0, 255) ! important;" {/if}
								  nowrap >
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
											{else}
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


								 {if $HEADER_FIELD->get('column')=='cf_1160'}
								<td class="relatedListEntryValues" nowrap><span class="value textOverflowEllipsis">{$FILE_TITLE_CURRENCY}</span></td>
								{/if}
								{if $HEADER_FIELD->get('column')=='cf_1168'}
								<td class="relatedListEntryValues" nowrap><span class="value textOverflowEllipsis">{$FILE_TITLE_CURRENCY}</span></td>
								{/if}

							{/foreach}
						</tr>
					{/foreach}


					<tr class="listViewEntries">
             <td nowrap=""  class="medium">&nbsp;</td>
			  <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium" style="border-left: 2px solid rgb(0, 0, 255) ! important;">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
            </tr>


					<tr class="listViewEntries">
            <td colspan="12" nowrap=""  class="medium" style="text-align:right !important">Total Cost Local Currency</td>
            <td nowrap=""  class="medium">{$TOTAL_COST_LOCAL_CURRENCY}</td>
            <td nowrap="" class="medium"></td>
            <td nowrap="" colspan="6" class="medium" style="text-align:right !important ; border-left: 2px solid rgb(0, 0, 255) ! important;">  Total Revenue Local Currency</td>           
            <td nowrap="" data-field-type="double" class="medium">{$TOTAL_REVENUE_LOCAL_CURRENCY}</td>
            <td nowrap="" class="medium"></td>
           
            
            </tr>
            <tr class="listViewEntries">
           
            <td colspan="12" nowrap=""  class="medium" style="text-align:right !important">Exchange Rate</td>
            <td nowrap=""  class="medium">{$FINAL_EXCHANGE_RATE}</td>
             <td nowrap="" class="medium">&nbsp;</td>
            <td nowrap="" colspan="6" class="medium" style="text-align:right !important; border-left: 2px solid rgb(0, 0, 255) ! important;">Exchange Rate</td>           
            <td nowrap="" data-field-type="double" class="medium">{$FINAL_EXCHANGE_RATE}</td>
            <td nowrap="" class="medium">&nbsp;</td>
           
               
            </tr>
            <tr class="listViewEntries">
           
            <td colspan="12" nowrap=""  class="medium" style="text-align:right !important">Total Cost USD</td>
            <td nowrap="" data-field-type="double" class="medium">{$TOTAL_COST_USD}</td>
             <td nowrap="" class="medium">&nbsp;</td>
            <td nowrap="" colspan="6" class="medium" style="text-align:right !important; border-left: 2px solid rgb(0, 0, 255) ! important;">Total Revenue USD</td>           
            <td nowrap="" data-field-type="double" class="medium">{$TOTAL_REVENUE_USD}</td>
            <td nowrap="" class="medium">&nbsp;</td>
             
               
            </tr>
            <tr class="listViewEntries">
                
            <td colspan="19" nowrap=""  class="medium">Expected Profit Local currency</td>  
            <td nowrap="" class="medium">{$FILE_TITLE_CURRENCY}</td>   
            <td nowrap="" class="medium">{$EXPECTED_PROFIT}</td>  
            <td nowrap="" class="medium"></td>
           
                 
            </tr>
            <tr class="listViewEntries">
                 
            <td colspan="19" nowrap=""  class="medium">Expected Profit USD</td>  
            <td nowrap="" class="medium">USD</td>   
            <td nowrap="" class="medium">{$EXPECTED_PROFIT_USD}</td>    
             <td nowrap="" class="medium"></td> 
             
            </tr>

				<tr class="listViewEntries">
             <td nowrap=""  class="medium">&nbsp;</td>
			  <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap="" class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
             <td nowrap=""  class="medium">&nbsp;</td>
            </tr>

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
{/strip}