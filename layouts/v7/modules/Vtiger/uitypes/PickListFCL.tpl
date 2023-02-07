{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	{assign var=FCL_LIST value=$FIELD_MODEL->getPickListFCL($FCL_CODE)}
	<select class="select2 inputElement" name="{$FIELD_MODEL->getFieldName()}">
	<option value="">Select and Option</option>
		{foreach item=FCL_NAME key=CURRENCY_ID from=$FCL_LIST}
			<option value="FCL" data-picklistvalue= 'FCL' {if $FIELD_MODEL->get('fieldvalue') eq 'FCL'} selected {/if}>FCL</option>
			<option value="LCL" data-picklistvalue= 'LCL' {if $FIELD_MODEL->get('fieldvalue') eq 'LCL'} selected {/if}>LCL</option>
		{/foreach}
	</select>
{/strip}