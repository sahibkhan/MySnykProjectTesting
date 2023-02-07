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
{assign var=PROCUREMENT_LIST value=$FIELD_MODEL->getProcurementRequestNo()}
<select class="inputElement select2" name="{$FIELD_MODEL->getFieldName()}" data-fieldinfo='{$FIELD_INFO}'  style="width:300px !important;">
<option value=''>--Select Request_No--</option>
{foreach item=PM_REQUEST key=PROCUREMENT_ID from=$PROCUREMENT_LIST}
	<option value="{$PROCUREMENT_ID}" data-picklistvalue= '{$PROCUREMENT_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $PROCUREMENT_ID} selected {/if} > {vtranslate({$PM_REQUEST}, $MODULE)}</option>
{/foreach}
</select>

{/strip}