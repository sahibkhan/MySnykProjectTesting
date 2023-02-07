{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
	{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}

	{if (!$FIELD_NAME)}
		{assign var="FIELD_NAME" value=$FIELD_MODEL->getFieldName()}
	{/if}


	<input id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" 

	{if {$FIELD_NAME} == 'cf_917'}
	type="hidden" 
	{else}
	type="text" 
	{/if}
	
	data-fieldname="{$FIELD_NAME}" data-fieldtype="string" 
	class="inputElement {if $FIELD_MODEL->isNameField()}nameField{/if} {if {$FIELD_NAME} == 'cf_917'}crmfield_recipients{/if}" 
	name="{$FIELD_NAME}" value="{$FIELD_MODEL->get('fieldvalue')}"
		{if $FIELD_MODEL->get('uitype') eq '3' || $FIELD_MODEL->get('uitype') eq '4'|| $FIELD_MODEL->isReadOnly()}
			{if $FIELD_MODEL->get('uitype') neq '106'}
				readonly
			{else if $FIELD_MODEL->get('uitype') eq '106' && $MODE eq 'edit'}
				readonly
			{/if}
		{/if}
		{if !empty($SPECIAL_VALIDATOR)}data-validator="{Zend_Json::encode($SPECIAL_VALIDATOR)}"{/if}
		{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
		{if count($FIELD_INFO['validator'])}
			data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
		{/if}
		   />

	{if {$FIELD_NAME} == 'cf_917'}
	<table >
	  <tr><td>
	  <select class="select2 cls-recipients" id="recipients_select" style="width:225px">
	  {include_php file='include/Vtiger/pullout_users.php'}	 
	  </select></td></tr>
	</table>
	<table class="table table-borderless" id=tbl_recipients>{$RECEIPENT_LIST}</table>
	{/if}	   
{/strip}
