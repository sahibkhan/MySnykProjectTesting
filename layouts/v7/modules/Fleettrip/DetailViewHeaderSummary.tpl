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
<style>
    .rcorners2 {
        border-radius: 5px;
        padding: 10px;
        width: 40px;
        height: 40px;
        float: left;
    }

    .header-div {
        float: left;
        width: 20%;
        padding: 2px;
    }

    .c-header {
        padding: 5px;
        border: 1px solid;
        border-color: #dddddd;
    }

    #div_custome_header {
        display: none;
    }
</style>
<div class="col-lg-12 c-header" id="div_custome_header" style="display: block; width:140%">
    


{assign var=FIELDS_MODELS_LIST value=$MODULE_MODEL->getFields()}
    {foreach item=FIELD_MODEL from=$FIELDS_MODELS_LIST}
        {assign var=FIELD_DATA_TYPE value=$FIELD_MODEL->getFieldDataType()}
        {assign var=FIELD_NAME value={$FIELD_MODEL->getName()}}

        {if $FIELD_MODEL->isHeaderField() && $FIELD_MODEL->isActiveField() && $RECORD->get($FIELD_NAME) && $FIELD_MODEL->isViewable()}
            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue', $RECORD->get({$FIELD_NAME}))}
            
                    {assign var=DISPLAY_VALUE value="{$FIELD_MODEL->getDisplayValue($RECORD->get($FIELD_NAME))}"}
                    
                    <div class="header-div">
        
        <div style="text-align: left;margin-top: 4px;"><span class="l-header muted" style="vertical-align: left; padding-left: 11px;">{vtranslate($FIELD_MODEL->get('label'),$MODULE)} :</span></div>
        <div style="text-align: left;"><span class="l-value" style="vertical-align: left; padding-left: 11px;text-align: left;">
        {strip_tags($DISPLAY_VALUE)} 
         {if $FIELD_NAME eq 'cf_1084'} {$RECORD->getDisplayValue('cf_1520')} {/if}
         {if $FIELD_NAME eq 'cf_1086'} {$RECORD->getDisplayValue('cf_1522')} {/if}
         </span></div>
    </div>
                        
        {/if}
    {/foreach}
</div>
    