{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}

    {assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
    {assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
    {assign var=PICKLIST_COLORS value=$FIELD_INFO['picklistColors']}
	{assign var=CITY_LIST value=$FIELD_MODEL->getDischargeCityList($GEN_DEST_COUNTRY_CODE,$DIS_COUNTRY_CODE,$GEN_DEST_STATE_CODE,$DIS_STATE_CODE)}

    <select data-fieldname="{$FIELD_MODEL->getFieldName()}" data-fieldtype="picklist" class="inputElement select2 {if $OCCUPY_COMPLETE_WIDTH} row {/if}" type="picklist" name="{$FIELD_MODEL->getFieldName()}" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} data-selected-value='{$FIELD_MODEL->get('fieldvalue')}'
	{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
	{if count($FIELD_INFO['validator'])}
		data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
	{/if}
	>
	{if $FIELD_MODEL->isEmptyPicklistOptionAllowed()}<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>{/if}
	{foreach item=CITY_NAME key=CITY_CODE from=$CITY_LIST}
	{$CITY_NAME}
		{assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$CITY_CODE|replace:' ':'_'}"}
		<option value="{Vtiger_Util_Helper::toSafeHTML($CITY_CODE)}" {if $PICKLIST_COLORS[$CITY_CODE]}class="{$CLASS_NAME}"{/if} {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($CITY_CODE)} selected {/if}>{$CITY_NAME}</option>
	{/foreach}
</select>
{/strip}