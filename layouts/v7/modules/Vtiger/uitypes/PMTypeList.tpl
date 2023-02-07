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
{assign var=PM_TYPE value=$FIELD_MODEL->getPMTypeList()} 
<select class="inputElement select2" 
name="{$FIELD_MODEL->getFieldName()}{if {$SOURCE_MODULE}=='PMRequisitions' && {$MODULE}=='PMItems'}[]{/if}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}' style="width:auto;" >
<option value="">--Select PM Type--</option>
{foreach item=PM_TYPE_LIST key=PM_TYPE_KEY from=$PM_TYPE}
<optgroup label="{$PM_TYPE_KEY}">
{foreach item=PMTYPE key=PM_TYPE_ID from=$PM_TYPE_LIST}
	<option value="{$PM_TYPE_ID}" data-picklistvalue= '{$PM_TYPE_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $PM_TYPE_ID} selected {/if} > {vtranslate({$PMTYPE}, $MODULE)}</option>
{/foreach}
 </optgroup>
{/foreach}
</select>

{/strip}