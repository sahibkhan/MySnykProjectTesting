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
{assign var=CONTRACT_AGREEMENT_LIST value=$FIELD_MODEL->getContractAgreementList($CUSTOMER_ID)}
<select class="inputElement select2" name="{$FIELD_MODEL->getFieldName()}" data-fieldinfo='{$FIELD_INFO}'  style="width:300px !important;">
<option value=''>--Select Service Agreement--</option>
{foreach item=AGREEMENT_REQUEST key=AGREEMENT_ID from=$CONTRACT_AGREEMENT_LIST}
	<option value="{$AGREEMENT_ID}" data-picklistvalue= '{$AGREEMENT_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $AGREEMENT_ID} selected {/if} > {vtranslate({$AGREEMENT_REQUEST}, $MODULE)}</option>
{/foreach}
</select>

{/strip}