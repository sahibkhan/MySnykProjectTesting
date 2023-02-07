{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}



{strip}	

	{assign var=ECHECKLIST value=$FIELD_MODEL->getECheckList($JOB_TYPE,$JOB_FILETITLE,$JOB_DEPARTMENT)}
	<select class="select2 inputElement" name="{$FIELD_MODEL->getFieldName()}[]">
		{foreach item=ECHECKLIST_NAME key=ECHECKLIST_ID from=$ECHECKLIST}
			<option value="{$ECHECKLIST_ID}" data-picklistvalue= '{$ECHECKLIST_ID}' {if $FIELD_MODEL->get('fieldvalue') eq $ECHECKLIST_ID} selected {/if}>{vtranslate($ECHECKLIST_NAME, $MODULE)}</option>
		{/foreach}
	</select>
{/strip}