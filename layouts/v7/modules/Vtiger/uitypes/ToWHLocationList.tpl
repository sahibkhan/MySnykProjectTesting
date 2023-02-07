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
 $SOURCE_RECORD = WAREHOUSE ID
-->*} 
{strip}
{assign var="FIELD_INFO" value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_MODEL->getFieldInfo()))}
{assign var=WH_LOC value=$FIELD_MODEL->getToWHLocationList($TO_WHID, $PARENT_ID)} 
<select class="inputElement select2" 
    name="{$FIELD_MODEL->getFieldName()}" 
    data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
    data-fieldinfo='{$FIELD_INFO}' style="width:auto;" >
        <option value="0">--Select Warehouse Location --</option>
    {foreach item=WHL_DSC key=WHL_KEY from=$WH_LOC}
	<option value="{$WHL_KEY}" data-picklistvalue= '{$WHL_KEY}' 
	   {if $FIELD_MODEL->get('fieldvalue') eq $WHL_KEY} selected {/if} > 
		{vtranslate({$WHL_DSC}, $MODULE)}</option>
     {/foreach}
</select>

{/strip}