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

<!--{strip}
{assign var="FIELD_INFO" value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_MODEL->getFieldInfo()))}
{assign var=QHSE_LIST value=$FIELD_MODEL->getQhseList()}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
{assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
<select id="{$MODULE}_{$smarty.request.view}_fieldName_{$FIELD_MODEL->get('name')}" multiple class="select2" name="{$FIELD_MODEL->getFieldName()}[]" data-fieldinfo='{$FIELD_INFO}' {if $FIELD_MODEL->isMandatory() eq true} data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} {/if} style="width: 73%">
    {foreach item=PICKLIST_VALUE key=USER_ID from=$QHSE_LIST}
        <option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE)}" {if in_array(Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE, $FIELD_VALUE_LIST), $USER_ID)} selected {/if}>{vtranslate($USER_NAME, $MODULE)}</option>
    {/foreach}
</select>
{/strip}-->

{strip}
{assign var="FIELD_INFO" value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_MODEL->getFieldInfo()))}
{assign var=QHSE_LIST value=$FIELD_MODEL->getQhseList()}
{assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
<select class="inputElement select2" name="{$FIELD_MODEL->getFieldName()}[]" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO}' multiple>
{foreach item=USER_NAME key=USER_ID from=$QHSE_LIST}
	<option value="{$USER_ID}" data-picklistvalue= '{$USER_ID}' {if in_array(Vtiger_Util_Helper::toSafeHTML($USER_ID), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($USER_NAME, $MODULE)}</option>
{/foreach}
</select>
{/strip}