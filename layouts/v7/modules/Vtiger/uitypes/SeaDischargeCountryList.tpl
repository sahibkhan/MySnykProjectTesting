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
	{assign var=COUNTRY_LIST value=$FIELD_MODEL->getSeaDischargeCountryList($GEN_DEST_COUNTRY_CODE)}

    <select data-fieldname="{$FIELD_MODEL->getFieldName()}" data-fieldtype="picklist" class="inputElement select2 {if $OCCUPY_COMPLETE_WIDTH} row {/if}" type="picklist" name="{$FIELD_MODEL->getFieldName()}" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} data-selected-value='{$FIELD_MODEL->get('fieldvalue')}'
	{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
	{if count($FIELD_INFO['validator'])}
		data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
	{/if}
	>
	{if $FIELD_MODEL->isEmptyPicklistOptionAllowed()}<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>{/if}
	{foreach item=COUNTRY_NAME key=COUNTRY_ID from=$COUNTRY_LIST}
		{assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$COUNTRY_ID|replace:' ':'_'}"}
		<option value="{Vtiger_Util_Helper::toSafeHTML($COUNTRY_ID)}" {if $PICKLIST_COLORS[$COUNTRY_ID]}class="{$CLASS_NAME}"{/if} {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($COUNTRY_ID)} selected {/if}>{$COUNTRY_NAME}</option>
	{/foreach}
</select>
{/strip}