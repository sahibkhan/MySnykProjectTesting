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
{assign var=DOC_TYPE_LIST value=$FIELD_MODEL->getWHDocumentTypeList()} 
<select class="inputElement select2" 
name="{$FIELD_MODEL->getFieldName()}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}' >
<option value="">--Select an Option--</option>
{foreach item=DOC_TYPE key=DOC_TYPE_ID from=$DOC_TYPE_LIST}
	<option value="{$DOC_TYPE_ID}" data-picklistvalue= '{$DOC_TYPE_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $DOC_TYPE_ID} selected {/if} > {vtranslate({$DOC_TYPE}, $MODULE)}</option>
{/foreach}
</select>

{/strip}