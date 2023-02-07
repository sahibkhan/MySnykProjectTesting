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


{assign var=CHART_OF_ACCOUNT_LIST value=$FIELD_MODEL->getjobDescription()}
<select class="inputElement select2" name="{$FIELD_MODEL->getFieldName()}" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO}'>
<option value="">Select an Option</option>
{foreach item=CHART_OF_ACCOUNT key=CHART_ACCOUNT_ID from=$CHART_OF_ACCOUNT_LIST}
	<option value="{$CHART_ACCOUNT_ID}" data-picklistvalue='{$CHART_ACCOUNT_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $CHART_ACCOUNT_ID} selected {/if} > {vtranslate({$CHART_OF_ACCOUNT}, $MODULE)}</option>
{/foreach}
</select>
{literal} 
<script>
var chart_account_id = {/literal}{$FIELD_MODEL->get('fieldvalue')}{literal}
</script>
{/literal} 
{/strip}