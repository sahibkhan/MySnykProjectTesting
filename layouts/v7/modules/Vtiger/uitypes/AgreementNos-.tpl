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
{assign var=JOB_FILE_LIST value=$FIELD_MODEL->getAgreementNos()}

<!--
<select class="inputElement select2" name="{$FIELD_MODEL->getFieldName()}[]" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}]" 
data-fieldinfo='{$FIELD_INFO}'
 style="z-index:999 !important;">
-->

<select data-fieldname="{$FIELD_MODEL->getFieldName()}" data-fieldtype="picklist" class="inputElement select2  select2-offscreen" type="picklist" name="{$FIELD_MODEL->getFieldName()}" data-selected-value=" " data-rule-required="{if $FIELD_MODEL->isMandatory() eq true}true{/if}" tabindex="" title="" aria-required="{if $FIELD_MODEL->isMandatory() eq true}true{/if}">

 <option value="">Select Option</option>
{foreach item=JOB_FILE_REF_NO key=JOB_FILE_ID from=$JOB_FILE_LIST}
  <option value="{$JOB_FILE_ID}" data-picklistvalue= '{$JOB_FILE_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $JOB_FILE_ID } selected {/if} > {vtranslate({$JOB_FILE_REF_NO}, $MODULE)}</option>
{/foreach}
</select>

{/strip}