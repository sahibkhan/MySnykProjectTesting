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
	{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
	{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
	
	{assign var=COMPANY_LIST value=$FIELD_MODEL->getCompaniesList()}
	{assign var=PICKLIST_COLORS value=$FIELD_INFO['picklistColors']}


    {if $MODULE=='Users' AND ({$FIELD_MODEL->get('name')}=='assign_company_id' || {$FIELD_MODEL->get('name')}=='access_company_id')}
    {assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
		<input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value=""  data-fieldtype="multipicklist"/>
    <select id="{$MODULE}_{$smarty.request.view}_fieldName_{$FIELD_MODEL->getFieldName()}" multiple class="select2" name="{$FIELD_MODEL->getFieldName()}[]" data-fieldtype="multipicklist" style='width:210px;height:30px;' 
			{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
			{if count($FIELD_INFO['validator'])} 
				data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
			{/if}
			>
		
        {foreach item=COMPANY key=COMPANY_ID from=$COMPANY_LIST}
        	{assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$COMPANY_ID|replace:' ':'_'}"}
            <option value="{Vtiger_Util_Helper::toSafeHTML($COMPANY_ID)}" {if $PICKLIST_COLORS[$COMPANY_ID]}class="{$CLASS_NAME}"{/if} {if in_array(Vtiger_Util_Helper::toSafeHTML($COMPANY_ID), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($COMPANY, $MODULE)}</option>
		{/foreach}
	</select>


    {elseif $MODULE=='VPO' || $MODULE=='BO'}
    {assign var=COMPANY_LIST value=$FIELD_MODEL->getUserCompaniesList()}
    <select   {if $MODULE eq 'Job' && $FIELD_MODEL->get('fieldvalue') neq ''} disabled="" {/if}   data-fieldname="{$FIELD_MODEL->getFieldName()}" data-fieldtype="picklist" class="inputElement select2 {if $OCCUPY_COMPLETE_WIDTH} row {/if}" type="picklist" name="{$FIELD_MODEL->getFieldName()}" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} data-selected-value='{$FIELD_MODEL->get('fieldvalue')}'
	{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
	{if count($FIELD_INFO['validator'])}
		data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
	{/if}
	>
	{if $FIELD_MODEL->isEmptyPicklistOptionAllowed()}<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>{/if}
    {foreach item=COMPANY key=COMPANY_ID from=$COMPANY_LIST}
		{assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$COMPANY_ID|replace:' ':'_'}"}
		<option value="{Vtiger_Util_Helper::toSafeHTML($COMPANY_ID)}" {if $PICKLIST_COLORS[$COMPANY_ID]}class="{$CLASS_NAME}"{/if} {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($COMPANY_ID)} selected {/if}>{$COMPANY}</option>
	{/foreach}
</select>

    {else}
	<select   {if $MODULE eq 'Job' && $FIELD_MODEL->get('fieldvalue') neq '' && $FLAG_COSTING && $sourceModule neq 'Quotes'} disabled="" {/if}   data-fieldname="{$FIELD_MODEL->getFieldName()}" data-fieldtype="picklist" class="inputElement select2 {if $OCCUPY_COMPLETE_WIDTH} row {/if}" type="picklist" name="{$FIELD_MODEL->getFieldName()}" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} data-selected-value='{$FIELD_MODEL->get('fieldvalue')}'
	{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
	{if count($FIELD_INFO['validator'])}
		data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
	{/if}
	>
	{if $FIELD_MODEL->isEmptyPicklistOptionAllowed()}<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>{/if}
	{foreach item=COMPANY key=COMPANY_ID from=$COMPANY_LIST}
		{assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$PICKLIST_NAME|replace:' ':'_'}"}
        {if $COMPANY_ID eq '85772'} 
		<option {if !in_array($COMPANY_ID, $ACCESS_USER_COMPANY)}disabled{/if} value="{Vtiger_Util_Helper::toSafeHTML($COMPANY_ID)}" {if $PICKLIST_COLORS[$COMPANY_ID]}class="{$CLASS_NAME}"{/if} {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($COMPANY_ID) || $USER_COMPANY == $COMPANY_ID} selected {/if}>{$COMPANY}</option>
         {else}

        <option value="{Vtiger_Util_Helper::toSafeHTML($COMPANY_ID)}" {if $PICKLIST_COLORS[$COMPANY_ID]}class="{$CLASS_NAME}"{/if} {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($COMPANY_ID) || $USER_COMPANY == $COMPANY_ID} selected {/if}>{$COMPANY}</option>

         {/if}
    {/foreach}
</select>

{if $MODULE eq 'Job' && $FIELD_MODEL->get('fieldvalue') neq '' && $FLAG_COSTING && $sourceModule neq 'Quotes'}
<input id="{$FIELD_MODEL->getFieldName()}" type="hidden" 
		   class="assigned_user_history span10 input-large {if $FIELD_MODEL->isNameField()}nameField{/if}" 
		   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
		   name="{$FIELD_MODEL->getFieldName()}" 
		   value="{$FIELD_MODEL->get('fieldvalue')}"
	data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator={Zend_Json::encode($SPECIAL_VALIDATOR)}{/if} />
{/if}

    {/if}

	
{/strip}