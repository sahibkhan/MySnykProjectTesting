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
{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
{assign var=JOB_FILE_LIST value=$FIELD_MODEL->getJobFileList()}
<select data-fieldname="{$FIELD_MODEL->getFieldName()}"
class="inputElement select2 {if $OCCUPY_COMPLETE_WIDTH} row {/if}"

name="{$FIELD_MODEL->getFieldName()}{if {$SOURCE_MODULE}=='Fleettrip' && {$MODULE}=='Roundtrip'}[]{/if}" 
{if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} 

{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
	{if count($FIELD_INFO['validator'])}
		data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
	{/if} 
   {if $MODULE eq 'Roundtrip' && $FIELD_MODEL->get('fieldvalue') neq '' } disabled="" {/if}
	>
	
{foreach item=JOB_FILE_REF_NO key=JOB_FILE_ID from=$JOB_FILE_LIST}
	<option value="{$JOB_FILE_ID}" data-picklistvalue= '{$JOB_FILE_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $JOB_FILE_ID } selected {/if} >{vtranslate({$JOB_FILE_REF_NO}, $MODULE)}</option>
{/foreach}
</select>

{if $MODULE eq 'Roundtrip' && $FIELD_MODEL->get('fieldvalue') neq ''}
<input id="{$FIELD_MODEL->getFieldName()}" type="hidden" 
		   class="assigned_user_history span10 input-large {if $FIELD_MODEL->isNameField()}nameField{/if}" 
		   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
		   name="{$FIELD_MODEL->getFieldName()}" 
		   value="{$FIELD_MODEL->get('fieldvalue')}"
	data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator={Zend_Json::encode($SPECIAL_VALIDATOR)}{/if} />
{/if}

{/strip}