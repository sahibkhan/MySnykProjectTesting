	{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is: vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
    <div class="row conditionRow">
	<div class="col-lg-4 col-md-4 col-sm-4">
		<select class="{if empty($NOCHOSEN)}select2{/if} col-lg-12" name="columnname">
			<option value="none">{vtranslate('LBL_SELECT_FIELD',$MODULE)}</option>
			{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
				<optgroup label='{vtranslate($BLOCK_LABEL, $SOURCE_MODULE)}'>
				{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
					{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
					{assign var=MODULE_MODEL value=$FIELD_MODEL->getModule()}
                    {assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
					{if !empty($COLUMNNAME_API)}
						{assign var=columnNameApi value=$COLUMNNAME_API}
					{else}
						{assign var=columnNameApi value=getCustomViewColumnName}
					{/if}
					<option value="{$FIELD_MODEL->$columnNameApi()}" data-fieldtype="{$FIELD_MODEL->getFieldType()}" data-field-name="{$FIELD_NAME}"
					{if decode_html($FIELD_MODEL->$columnNameApi()) eq decode_html($CONDITION_INFO['columnname'])}
						{assign var=FIELD_TYPE value=$FIELD_MODEL->getFieldType()}
						{assign var=SELECTED_FIELD_MODEL value=$FIELD_MODEL}
						{if $FIELD_MODEL->getFieldDataType() == 'reference'  ||  $FIELD_MODEL->getFieldDataType() == 'multireference'}
							{$FIELD_TYPE='V'}
						{/if}
						{$FIELD_INFO['value'] = decode_html($CONDITION_INFO['value'])}
						selected="selected"
					{/if}
					{if ($MODULE_MODEL->get('name') eq 'Calendar' || $MODULE_MODEL->get('name') eq 'Events') && ($FIELD_NAME eq 'recurringtype')}
						{assign var=PICKLIST_VALUES value = Calendar_Field_Model::getReccurencePicklistValues()}
						{$FIELD_INFO['picklistvalues'] = $PICKLIST_VALUES}
					{/if}
                    {if ($MODULE_MODEL->get('name') eq 'Calendar') && ($FIELD_NAME eq 'activitytype')}
						{$FIELD_INFO['picklistvalues']['Task'] = vtranslate('Task', 'Calendar')}
					{/if}
					{if $FIELD_MODEL->getFieldDataType() eq 'reference'}
						{assign var=referenceList value=$FIELD_MODEL->getWebserviceFieldObject()->getReferenceList()}
						{if is_array($referenceList) && in_array('Users', $referenceList)}
								{assign var=USERSLIST value=array()}
								{assign var=CURRENT_USER_MODEL value = Users_Record_Model::getCurrentUserModel()}
								{assign var=ACCESSIBLE_USERS value = $CURRENT_USER_MODEL->getAccessibleUsers()}
								{foreach item=USER_NAME from=$ACCESSIBLE_USERS}
										{$USERSLIST[$USER_NAME] = $USER_NAME}
								{/foreach}
								{$FIELD_INFO['picklistvalues'] = $USERSLIST}
								{$FIELD_INFO['type'] = 'picklist'}
						{/if}
					{/if}

					{if $FIELD_MODEL->get('uitype') eq '768'}
                 	{assign var=TRUCKSLIST value=array()}
                    {assign var=TRUCK_LIST value = $FIELD_MODEL->getTruckList()}
                    {foreach key=TRUCK_ID item=TRUCK_NAME from=$TRUCK_LIST}
						{$TRUCKSLIST[$TRUCK_ID] = $TRUCK_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $TRUCKSLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    {if $FIELD_MODEL->get('uitype') eq '599'}
                 	{assign var=DRIVERSLIST value=array()}
                    {assign var=DRIVER_LIST value = $FIELD_MODEL->getDriverList()}
                    {foreach key=DRIVER_ID item=DRIVER_NAME from=$DRIVER_LIST}
						{$DRIVERSLIST[$DRIVER_ID] = $DRIVER_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $DRIVERSLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
                    {if $FIELD_MODEL->get('uitype') eq '598'}
                 	{assign var=FLEETUSERSLIST value=array()}
                    {assign var=FLEET_USER_LIST value = $FIELD_MODEL->getFleetUserList()}
                    {foreach key=FLEET_USER_ID item=FLEET_USER_NAME from=$FLEET_USER_LIST}
						{$FLEETUSERSLIST[$FLEET_USER_ID] = $FLEET_USER_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $FLEETUSERSLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
                    {if $FIELD_MODEL->get('uitype') eq '598'}
                 	{assign var=FLEETUSERSLIST value=array()}
                    {assign var=FLEET_USER_LIST value = $FIELD_MODEL->getFleetUserList()}
                    {foreach key=FLEET_USER_ID item=FLEET_USER_NAME from=$FLEET_USER_LIST}
						{$FLEETUSERSLIST[$FLEET_USER_ID] = $FLEET_USER_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $FLEETUSERSLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
                    {if $FIELD_MODEL->get('uitype') eq '698'}
                 	{assign var=COMMODITYTYPELIST value=array()}
                    {assign var=COMMODITY_TYPE_LIST value = $FIELD_MODEL->getCommodityTypeList()}
                    {foreach key=COMMODITY_TYPE_ID item=COMMODITY_TYPE_NAME from=$COMMODITY_TYPE_LIST}
						{$COMMODITYTYPELIST[$COMMODITY_TYPE_ID] = $COMMODITY_TYPE_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $COMMODITYTYPELIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
                    {if $FIELD_MODEL->get('uitype') eq '697'}
                 	{assign var=SPECIALRANGELIST value=array()}
                    {assign var=SPECIAL_RANGE_LIST value = $FIELD_MODEL->getMainSpecialRangeList()}
                    {foreach key=SPECIAL_RANGE_ID item=SPECIAL_RANGE_NAME from=$SPECIAL_RANGE_LIST}
						{$SPECIALRANGELIST[$SPECIAL_RANGE_ID] = $SPECIAL_RANGE_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $SPECIALRANGELIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                  
                    {if $FIELD_MODEL->get('uitype') eq '597'}
                 	{assign var=GLKUSERLIST value=array()}
                    {assign var=GLK_USER_LIST value = $FIELD_MODEL->getGlkUserList()}
                    {foreach key=GLK_USER_ID item=GLK_USER_NAME from=$GLK_USER_LIST}
						{$GLKUSERLIST[$GLK_USER_ID] = $GLK_USER_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $GLKUSERLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
                     {if $FIELD_MODEL->get('uitype') eq '601'}
                 	{assign var=USERSLIST value=array()}
                    {assign var=USERS_LIST value = $FIELD_MODEL->getUsersList()}
                    {foreach key=USERS_LIST_ID item=USERS_LIST_NAME from=$USERS_LIST}
						{$USERSLIST[$USERS_LIST_ID] = $USERS_LIST_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $USERSLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
                     {if $FIELD_MODEL->get('uitype') eq '999'}
                 	{assign var=COMPANYLIST value=array()}
                    {assign var=COMPANY_LIST value = $FIELD_MODEL->getFilterCompaniesList()}
                    {foreach key=COMPANY_LIST_ID item=COMPANY_LIST_NAME from=$COMPANY_LIST}
						{$COMPANYLIST[$COMPANY_LIST_ID] = $COMPANY_LIST_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $COMPANYLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
                    
					
                     {if $FIELD_MODEL->get('uitype') eq '898'}
                 	{assign var=LOCATIONLIST value=array()}
                    {assign var=LOCATION_LIST value = $FIELD_MODEL->getFilterLocationsList()}
                    {foreach key=LOCATION_LIST_ID item=LOCATION_LIST_NAME from=$LOCATION_LIST}
						{$LOCATIONLIST[$LOCATION_LIST_ID] = $LOCATION_LIST_NAME}
                    {/foreach}
                    {$FIELD_INFO['picklistvalues'] = $LOCATIONLIST}
                    {$FIELD_INFO['type'] = 'picklist'}
                    {/if}
					
	                {if $FIELD_MODEL->get('uitype') eq '899'}						 
						{assign var=DEPARTMENTLIST value=array()}
						{assign var=DEPARTMENT_LIST value = $FIELD_MODEL->getDepartmentsList()}
						
						{foreach key=DEPARTMENT_LIST_ID item=DEPARTMENT_LIST_NAME from=$DEPARTMENT_LIST}
							{$DEPARTMENTLIST[$DEPARTMENT_LIST_NAME.name] = $DEPARTMENT_LIST_NAME.name_code}
						{/foreach}
						{$FIELD_INFO['picklistvalues'] = $DEPARTMENTLIST}
						{$FIELD_INFO['type'] = 'picklist'}
                    {/if}	

					 {if $FIELD_MODEL->get('uitype') eq '11010'}						 
						{assign var=WAREHOUSELIST value=array()}
						{assign var=WAREHOUSE_LIST value = $FIELD_MODEL->getWarehouseList()}
						
						{foreach key=WAREHOUSE_LIST_ID item=WAREHOUSE_LIST_NAME from=$WAREHOUSE_LIST}
							{$WAREHOUSELIST[$WAREHOUSE_LIST_ID] = $WAREHOUSE_LIST_NAME}
						{/foreach}
						{$FIELD_INFO['picklistvalues'] = $WAREHOUSELIST}
						{$FIELD_INFO['type'] = 'picklist'}
                    {/if}	

						{if $FIELD_MODEL->get('uitype') eq '13060'}
							{assign var=LOCATIONLIST value=array()}
								{assign var=LOCATION_LIST value = $FIELD_MODEL->getCountryList()}
								{foreach key=LOCATION_LIST_ID item=LOCATION_LIST_NAME from=$LOCATION_LIST}
									{$LOCATIONLIST[$LOCATION_LIST_ID] = $LOCATION_LIST_NAME}
								{/foreach}
								{$FIELD_INFO['picklistvalues'] = $LOCATIONLIST}
								{$FIELD_INFO['type'] = 'picklist'}
						{/if}

					data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}' 
                    {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if}>
					{if $SOURCE_MODULE neq $MODULE_MODEL->get('name')}
						({vtranslate($MODULE_MODEL->get('name'), $MODULE_MODEL->get('name'))}) {vtranslate($FIELD_MODEL->get('label'), $MODULE_MODEL->get('name'))}
					{else}
						{vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}
					{/if}
				</option>
				{/foreach}
				</optgroup>
			{/foreach}
			{* Required to display event fields also while adding conditions *}
            {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$EVENT_RECORD_STRUCTURE}
				<optgroup label='{vtranslate($BLOCK_LABEL, 'Events')}'>
				{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
					{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
					{assign var=MODULE_MODEL value=$FIELD_MODEL->getModule()}
					{if !empty($COLUMNNAME_API)}
						{assign var=columnNameApi value=$COLUMNNAME_API}
					{else}
						{assign var=columnNameApi value=getCustomViewColumnName}
					{/if}
					<option value="{$FIELD_MODEL->$columnNameApi()}" data-fieldtype="{$FIELD_MODEL->getFieldType()}" data-field-name="{$FIELD_NAME}"
					{if decode_html($FIELD_MODEL->$columnNameApi()) eq $CONDITION_INFO['columnname']}
						{assign var=FIELD_TYPE value=$FIELD_MODEL->getFieldType()}
						{assign var=SELECTED_FIELD_MODEL value=$FIELD_MODEL}
						{if $FIELD_MODEL->getFieldDataType() == 'reference' || $FIELD_MODEL->getFieldDataType() == 'multireference'}
							{$FIELD_TYPE='V'}
						{/if}
						{$FIELD_INFO['value'] = decode_html($CONDITION_INFO['value'])}
						selected="selected"
					{/if}
					{if ($MODULE_MODEL->get('name') eq 'Calendar' || $MODULE_MODEL->get('name') eq 'Events') && ($FIELD_NAME eq 'recurringtype')}
						{assign var=PICKLIST_VALUES value = Calendar_Field_Model::getReccurencePicklistValues()}
						{$FIELD_INFO['picklistvalues'] = $PICKLIST_VALUES}
					{/if}
					{if $FIELD_MODEL->getFieldDataType() eq 'reference'}
						{assign var=referenceList value=$FIELD_MODEL->getWebserviceFieldObject()->getReferenceList()}
						{if is_array($referenceList) && in_array('Users', $referenceList)}
								{assign var=USERSLIST value=array()}
								{assign var=CURRENT_USER_MODEL value = Users_Record_Model::getCurrentUserModel()}
								{assign var=ACCESSIBLE_USERS value = $CURRENT_USER_MODEL->getAccessibleUsers()}
								{foreach item=USER_NAME from=$ACCESSIBLE_USERS}
										{$USERSLIST[$USER_NAME] = $USER_NAME}
								{/foreach}
								{$FIELD_INFO['picklistvalues'] = $USERSLIST}
								{$FIELD_INFO['type'] = 'picklist'}
						{/if}
					{/if}
					data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}' >
					{if $SOURCE_MODULE neq $MODULE_MODEL->get('name')}
						({vtranslate($MODULE_MODEL->get('name'), $MODULE_MODEL->get('name'))})  {vtranslate($FIELD_MODEL->get('label'), $MODULE_MODEL->get('name'))}
					{else}
						{vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}
					{/if}
				</option>
				{/foreach}
				</optgroup>
			{/foreach}
		</select>
	</div>
	<div class="conditionComparator col-lg-3 col-md-3 col-sm-3">
		<select class="{if empty($NOCHOSEN)}select2{/if} col-lg-12" name="comparator">
			 <option value="none">{vtranslate('LBL_NONE',$MODULE)}</option>
			{assign var=ADVANCE_FILTER_OPTIONS value=$ADVANCED_FILTER_OPTIONS_BY_TYPE[$FIELD_TYPE]}
            {if $FIELD_TYPE eq 'D' || $FIELD_TYPE eq 'DT'}
                {assign var=DATE_FILTER_CONDITIONS value=array_keys($DATE_FILTERS)}
                {assign var=ADVANCE_FILTER_OPTIONS value=array_merge($ADVANCE_FILTER_OPTIONS,$DATE_FILTER_CONDITIONS)}
            {/if}
			{foreach item=ADVANCE_FILTER_OPTION from=$ADVANCE_FILTER_OPTIONS}
				<option value="{$ADVANCE_FILTER_OPTION}"
				{if $ADVANCE_FILTER_OPTION eq $CONDITION_INFO['comparator']}
						selected
				{/if}
				>{vtranslate($ADVANCED_FILTER_OPTIONS[$ADVANCE_FILTER_OPTION])}</option>
			{/foreach}
		</select>
	</div>
	<div class="col-lg-4 col-md-4 col-sm-4  fieldUiHolder">
		<input name="{if $SELECTED_FIELD_MODEL}{$SELECTED_FIELD_MODEL->get('name')}{/if}" data-value="value" class=" inputElement col-lg-12" type="text" value="{$CONDITION_INFO['value']|escape}" />
	</div>
	<span class="hide">
		<!-- TODO : see if you need to respect CONDITION_INFO condition or / and  -->
		{if empty($CONDITION)}
			{assign var=CONDITION value="and"}
		{/if}
		<input type="hidden" name="column_condition" value="{$CONDITION}" />
	</span>
	 <div class="col-lg-1 col-md-1 col-sm-1">
		<i class="deleteCondition glyphicon glyphicon-trash cursorPointer" title="{vtranslate('LBL_DELETE', $MODULE)}"></i>
	</div>
</div>
{/strip}