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
{assign var=RRSSupervisor_LIST value=$FIELD_MODEL->getRRSSupervisorList()}
<select {if $MODULE=='Potentials'} disabled="" {/if} class="inputElement select2" 
name="{$FIELD_MODEL->getFieldName()}{if $MODULE=='Potentials'}_copy{/if}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}' style="width:300px !important;">
	<option value="">--Select Person-- </option>
{foreach item=RRSSupervisor key=RRSSupervisor_ID from=$RRSSupervisor_LIST}
	<option value="{$RRSSupervisor_ID}" data-picklistvalue= '{$RRSSupervisor_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $RRSSupervisor_ID 
	|| $USER_ID == $RRSSupervisor_ID} selected {/if} > {vtranslate({$RRSSupervisor}, $MODULE)}</option>
{/foreach}
</select>

{if $MODULE=='Potentials'}
<input id="{$FIELD_MODEL->getFieldName()}" type="hidden" 
		   class="assigned_user_history span10 input-large {if $FIELD_MODEL->isNameField()}nameField{/if}" 
		   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
		   name="{$FIELD_MODEL->getFieldName()}" 
		   value="{if $FIELD_MODEL->get('fieldvalue') neq ''}{$FIELD_MODEL->get('fieldvalue')}{else}{$USER_ID}{/if}"
	data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator={Zend_Json::encode($SPECIAL_VALIDATOR)}{/if} />
{/if}

{/strip}