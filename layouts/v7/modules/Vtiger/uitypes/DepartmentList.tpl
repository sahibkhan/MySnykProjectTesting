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
{assign var=DEPARTMENT_LIST value=$FIELD_MODEL->getDepartmentsList()}
{if ($MODULE=='Users' || $MODULE=='FSLBlack') AND ({$FIELD_MODEL->get('name')}=='assign_department_id'  || {$FIELD_MODEL->get('name')}=='cf_6448')}
	{if $MODULE=='FSLBlack'}
    {assign var="FIELD_VALUE_LIST" value=explode(',',$FIELD_MODEL->get('fieldvalue'))}
    {else}
     {assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
    {/if}
    
    <select id="{$MODULE}_{$smarty.request.view}_fieldName_{$FIELD_MODEL->get('name')}" multiple class="select2" name="{$FIELD_MODEL->getFieldName()}[]" data-fieldinfo='{$FIELD_INFO}' {if $FIELD_MODEL->isMandatory() eq true} data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} {/if} style="width: 73%">
    {foreach item=DEPARTMENT key=DEPARTMENT_ID from=$DEPARTMENT_LIST}
        <option value="{Vtiger_Util_Helper::toSafeHTML($DEPARTMENT_ID)}" {if in_array(Vtiger_Util_Helper::toSafeHTML($DEPARTMENT_ID), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($DEPARTMENT.name_code, $MODULE)}</option>
    {/foreach}
</select>
{else}
<select {if $MODULE eq 'Job' && $FIELD_MODEL->get('fieldvalue') neq '' && $FLAG_DEPARTMENT eq '1'} disabled="" {/if} 
class="inputElement select2" name="{$FIELD_MODEL->getFieldName()}{if {$SOURCE_MODULE}=='Job' && {$MODULE}=='JER'}[]{/if}" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO}' {if $MODULE eq 'JER' } style="width:80px !important;" {/if}>
{if $MODULE eq 'JobTask'}<option value="">Select Department</option>{/if}
{foreach item=DEPARTMENT key=DEPARTMENT_ID from=$DEPARTMENT_LIST}
	<option value="{$DEPARTMENT_ID}" data-picklistvalue= '{$DEPARTMENT_ID}' {if ($FIELD_MODEL->get('fieldvalue') eq $DEPARTMENT_ID || $USER_DEPARTMENT == $DEPARTMENT_ID)} selected {/if} > {vtranslate({$DEPARTMENT.code}, $MODULE)}</option>
{/foreach}
</select>
{if $MODULE eq 'Job' && $FIELD_MODEL->get('fieldvalue') neq '' && $FLAG_DEPARTMENT eq '1'}
<input id="{$FIELD_MODEL->getFieldName()}" type="hidden" 
		   class="assigned_user_history span10 input-large {if $FIELD_MODEL->isNameField()}nameField{/if}" 
		   data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
		   name="{$FIELD_MODEL->getFieldName()}" 
		   value="{$FIELD_MODEL->get('fieldvalue')}"
	data-fieldinfo='{$FIELD_INFO}' {if !empty($SPECIAL_VALIDATOR)}data-validator={Zend_Json::encode($SPECIAL_VALIDATOR)}{/if} />
{/if}
{/if}
{/strip}