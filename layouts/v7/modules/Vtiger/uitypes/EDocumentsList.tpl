{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}



{strip}

	{assign var=EDOCUMENTS_LIST value=$FIELD_MODEL->getEDocumentsList($JOB_TYPE)}
	<select class="select2 inputElement" name="{$FIELD_MODEL->getFieldName()}[]">
		{foreach item=EDOCUMENTS_NAME key=EDOCUMENTS_ID from=$EDOCUMENTS_LIST}
			<option value="{$EDOCUMENTS_ID}" data-picklistvalue= '{$EDOCUMENTS_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $EDOCUMENTS_ID} selected {/if}>{vtranslate($EDOCUMENTS_NAME, $MODULE)}</option>
		{/foreach}
	</select>
{/strip}