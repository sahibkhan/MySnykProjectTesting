{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
{assign var="FIELD_INFO" value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_MODEL->getFieldInfo()))}
{assign var=WAGON_LIST value=$FIELD_MODEL->getWagonList()}
<select {if $MODULE eq 'WagonTrip' && $FIELD_MODEL->get('fieldvalue') neq ''} disabled="" {/if} 
class="cinputElement select2" 
name="{$FIELD_MODEL->getFieldName()}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}' >
<option value=''>--Select Wagon--</option>
{foreach item=WAGON key=WAGON_ID from=$WAGON_LIST}
	<option value="{$WAGON_ID}" data-picklistvalue= '{$WAGON_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $WAGON_ID} selected {/if} > {vtranslate({$WAGON}, $MODULE)}</option>
{/foreach}
</select>

{if $MODULE eq 'WagonTrip' && $FIELD_MODEL->get('fieldvalue') neq ''}
<input id="{$FIELD_MODEL->getFieldName()}" type="hidden" 
		   class="assigned_user_history span10 input-large {if $FIELD_MODEL->isNameField()}nameField{/if}" 
		   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
		   name="{$FIELD_MODEL->getFieldName()}" 
		   value="{$FIELD_MODEL->get('fieldvalue')}"
	data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator={Zend_Json::encode($SPECIAL_VALIDATOR)}{/if} />
{/if}

{/strip}