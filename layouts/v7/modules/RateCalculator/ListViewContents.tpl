{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/List.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}
{include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE}

<div class="col-sm-12 col-xs-12 ">
	{if $MODULE neq 'EmailTemplates' && $SEARCH_MODE_RESULTS neq true}
		{assign var=LEFTPANELHIDE value=$CURRENT_USER_MODEL->get('leftpanelhide')}
		<div class="essentials-toggle" title="{vtranslate('LBL_LEFT_PANEL_SHOW_HIDE', 'Vtiger')}">
			<span class="essentials-toggle-marker fa {if $LEFTPANELHIDE eq '1'}fa-chevron-right{else}fa-chevron-left{/if} cursorPointer"></span>
		</div>
	{/if}
	<input type="hidden" name="view" id="view" value="{$VIEW}" />
	<input type="hidden" name="cvid" value="{$VIEWID}" />
	<input type="hidden" name="pageStartRange" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" name="pageEndRange" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" name="previousPageExist" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" name="nextPageExist" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" name="alphabetSearchKey" id="alphabetSearchKey" value= "{$MODULE_MODEL->getAlphabetSearchField()}" />
	<input type="hidden" name="Operator" id="Operator" value="{$OPERATOR}" />
	<input type="hidden" name="totalCount" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type='hidden' name="pageNumber" value="{$PAGE_NUMBER}" id='pageNumber'>
	<input type='hidden' name="pageLimit" value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
	<input type="hidden" name="noOfEntries" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">
	<input type="hidden" name="currentSearchParams" value="{Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($SEARCH_DETAILS))}" id="currentSearchParams" />
	<input type="hidden" name="currentTagParams" value="{Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($TAG_DETAILS))}" id="currentTagParams" />
	<input type="hidden" name="noFilterCache" value="{$NO_SEARCH_PARAMS_CACHE}" id="noFilterCache" >
	<input type="hidden" name="orderBy" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" name="sortOrder" value="{$SORT_ORDER}" id="sortOrder">
	<input type="hidden" name="list_headers" value='{$LIST_HEADER_FIELDS}'/>
	<input type="hidden" name="tag" value="{$CURRENT_TAG}" />
	<input type="hidden" name="folder_id" value="{$FOLDER_ID}" />
	<input type="hidden" name="folder_value" value="{$FOLDER_VALUE}" />
	<input type="hidden" name="viewType" value="{$VIEWTYPE}" />
	<input type="hidden" name="app" id="appName" value="{$SELECTED_MENU_CATEGORY}">
	<input type="hidden" id="isExcelEditSupported" value="{if $MODULE_MODEL->isExcelEditAllowed()}yes{else}no{/if}" />
	{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
		<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
	{/if}
	{if !$SEARCH_MODE_RESULTS}
		{include file="ListViewActions.tpl"|vtemplate_path:$MODULE}
	{/if}

	<div id="table-content" class="table-container">
		
		<form name='list' id='listedit' action='' onsubmit="return false;">
			<input id="qtnid" type="hidden" value="{$QTID}" />
			<table id="listview-table" class="table {if $LISTVIEW_ENTRIES_COUNT eq '0'}listview-table-norecords {/if} listview-table ">
				<thead>
					<tr>
						<th style="width:20%">Origin</th>
						<th style="width:20%">Destination</th>
						<th style="width:20%">Container</th>
						<th style="width:20%">Ship Date</th>
						<th style="width:20%"></th>
					</tr>
					<tr>
						<td>
							
							<select id="origin" class="select2 listSearchContributor">
								<option value="">Select Origin</option>
								{foreach $ORIGIN_LIST as $ORIGIN}
									<option value="{$ORIGIN}" >{$ORIGIN}</option>
								{/foreach}
							</select>
						</td>
						<td>
							<select id="dest" class="select2 listSearchContributor">
								<option value="">Select Destination</option>
								{foreach $DEST_LIST as $DEST}
									<option value="{$DEST}" >{$DEST}</option>
								{/foreach}
							</select>
						</td>
						<td>
							<select id="conttype" class="select2 listSearchContributor">
								<option value="">Container Type</option>
								{foreach $CONT_TYPE as $CONT}
									<option value="{$CONT}" >{$CONT}</option>
								{/foreach}
							</select>
						</td>
						<td>
							<input id="shipdate" class="dateField form-control" placeholder="Ship Date" >
						</td>
						<td>
							<button class="btn btn-success btn-sm" data-trigger="routSearch">Search</button>
							&nbsp;
							<button class="btn btn-primary btn-sm" data-trigger="routSearchClear">Clear Filter</button>
							&nbsp;
							<button class="btn btn-success btn-sm" data-trigger="routAddQuote">Add To Quote</button>
						</td>
					</tr>
				</thead>
				
				<tbody class="overflow-y">
					<tr class="listViewRecordActions">
						<td colspan=5>
							<div id="routResult" style="height:100vh"></div>
						</td>
					</tr>
					
					<tr class="listViewRecordActions">
						<td colspan=5>
							<div id="routText" style="width:100%">
								
							</div>
						</td>
					</tr>
					
				
				</tbody>
			</table>
		</form>
	</div>
	<div id="scroller_wrapper" class="bottom-fixed-scroll">
		<div id="scroller" class="scroller-div"></div>
	</div>
</div>
