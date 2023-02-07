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
{assign var=TRIP_TEMPLATES_LIST value=$FIELD_MODEL->getWagonTripTemplatesList()} 
<select class="inputElement select2" 
name="{$FIELD_MODEL->getFieldName()}" 
data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
data-fieldinfo='{$FIELD_INFO}' id="{$FIELD_MODEL->getFieldName()}"  style="width:300px !important;" >
<option value="">--Select Trip Template--</option>
{foreach item=TRIPTEMPLATESLIST key=TRIP_TEMPLATES_ID from=$TRIP_TEMPLATES_LIST}
	<option value="{$TRIP_TEMPLATES_ID}" data-picklistvalue= '{$TRIP_TEMPLATES_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $TRIP_TEMPLATES_ID} selected {/if} > {vtranslate({$TRIPTEMPLATESLIST}, $MODULE)}</option>
{/foreach}
</select>

{/strip}