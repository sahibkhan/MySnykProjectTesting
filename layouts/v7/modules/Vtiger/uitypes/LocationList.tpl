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
{assign var=LOCATION_LIST value=$FIELD_MODEL->getLocationsList()}
<select {if $MODULE=='Job' || $MODULE=='Accounts' || $MODULE=='PMRequisitions'} disabled="" {/if} class="inputElement select2{if $MODULE=='UserList'} picarid{/if}" 
name="{$FIELD_MODEL->getFieldName()}{if {$SOURCE_MODULE}=='Job' && {$MODULE}=='JER'}[]{/if}{if $MODULE=='Job'}_copy{/if}{if {$MODULE}=='Accounts'}_copy{/if}{if {$MODULE}=='PMRequisitions'}_copy{/if}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}'  {if $MODULE eq 'JER' } style="width:80px !important;" {/if} >
{foreach item=LOCATION key=LOCATION_ID from=$LOCATION_LIST}
	<option value="{$LOCATION_ID}" data-picklistvalue= '{$LOCATION_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $LOCATION_ID || $USER_LOCATION == $LOCATION_ID} selected {/if} > {vtranslate({$LOCATION}, $MODULE)}</option>
{/foreach}
</select>
{if $MODULE=='Job' || $MODULE=='Accounts' || $MODULE=='PMRequisitions'}
<input id="{$FIELD_MODEL->getFieldName()}" type="hidden" 
		   class="assigned_user_history span10 input-large {if $FIELD_MODEL->isNameField()}nameField{/if}" 
		   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
		   name="{$FIELD_MODEL->getFieldName()}" 
		   value="{if $FIELD_MODEL->get('fieldvalue') neq ''}{$FIELD_MODEL->get('fieldvalue')}{else}{$USER_LOCATION}{/if}"
	data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator={Zend_Json::encode($SPECIAL_VALIDATOR)}{/if} />
{/if}
{/strip}