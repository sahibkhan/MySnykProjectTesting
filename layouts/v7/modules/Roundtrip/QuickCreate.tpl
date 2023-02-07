{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/QuickCreateAjax.php *}
    
{strip}
    {foreach key=index item=jsModel from=$SCRIPTS}
        <script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
    {/foreach}
    
<tr class="listViewEntries" id="{$TRID}">
    {assign var=COUNTER value=0}
    {foreach key=FIELD_NAME item=FIELD_MODEL from=$RECORD_STRUCTURE name=blockfields}
      {if $FIELD_MODEL@first}
        <td class="fieldLabel col-lg-2">&nbsp;&nbsp;<i class="fa fa-trash deleteRow cursorPointer" title="{vtranslate('LBL_DELETE',$MODULE)}"></i></td>
      {/if}
         {if $FIELD_MODEL->get('uitype') neq "83" 
                	AND $FIELD_MODEL->getFieldName() neq 'name' 
                    AND $FIELD_MODEL->getFieldName() neq 'assigned_user_id'
                   
                    }

        {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
        {assign var="referenceList" value=$FIELD_MODEL->getReferenceList()}
        {assign var="referenceListCount" value=count($referenceList)}
       
        <td id="{$FIELD_MODEL->getFieldName()}" class='fieldLabel col-lg-2' {assign var=COUNTER value=$COUNTER+1}>                 
        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) COUNTER=$COUNTER MODULE=$MODULE PULL_RIGHT=true}
               
        </td>
       
     {/if}    
    {/foreach}
</tr>

{/strip}