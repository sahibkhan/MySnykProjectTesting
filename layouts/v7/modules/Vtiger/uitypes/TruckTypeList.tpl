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
{assign var=TRUCKTYPE_LIST value=$FIELD_MODEL->getTruckTypesList()}
<select class="inputElement select2" 
name="{$FIELD_MODEL->getFieldName()}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}' >
{foreach item=TRUCKTYPE key=TRUCKTYPE_ID from=$TRUCKTYPE_LIST}
	<option value="{$TRUCKTYPE_ID}" data-picklistvalue= '{$TRUCKTYPE_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $TRUCKTYPE_ID} selected {/if} > {vtranslate({$TRUCKTYPE}, $MODULE)}</option>
{/foreach}
</select>

{/strip}