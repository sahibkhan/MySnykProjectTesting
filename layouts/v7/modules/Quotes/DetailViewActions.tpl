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
    <div class="col-lg-6 detailViewButtoncontainer">
        <div class="pull-right btn-toolbar">
            <div class="btn-group">
             <span class="btn-group">
           <button class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
                   Quote Template&nbsp;&nbsp;<i class="caret"></i>
                </button>
                 <ul class="dropdown-menu pull-right">
                    <li id="{$MODULE_NAME}_detailView_moreAction_general_en">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=general_en&app=MARKETING">General_QT_en</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_general_ru">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=general_ru&app=MARKETING">General_QT_ru</a>
                    </li>

                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_afg">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_afg&app=MARKETING">RRS_Afghanistan_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_mon">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_mon&app=MARKETING">RRS_Mongolia_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_geo">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_geo&app=MARKETING">RRS_Georgia_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_rus">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_rus&app=MARKETING">RRS_Russia_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_kyr">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_kyr&app=MARKETING">RRS_Kyrgyzstan_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_taj">
                    <a  target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_taj&app=MARKETING">RRS_Tajikistan_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_pak">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_pak&app=MARKETING">RRS_Pakistan_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_tur">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_tur&app=MARKETING">RRS_Turkmenistan_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_uzb">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_uzb&app=MARKETING">RRS_Uzbekistan_QT</a>
                    </li>
                    <li target="_blank" id="{$MODULE_NAME}_detailView_moreAction_rrs_arm">
                    <a href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_arm&app=MARKETING">RRS_Armenia_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_aze">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_aze&app=MARKETING">RRS_Azerbaijan_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_rrs_ukr">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=rrs_ukr&app=MARKETING">RRS_Ukraine_QT</a>
                    </li>
                    <li id="{$MODULE_NAME}_detailView_moreAction_private_pro">
                    <a target="_blank" href="index.php?module=Quotes&view=Print&record={$RECORD->getId()}&tpl=private_pro&app=MARKETING"> PRO Department</a>
                    </li>

                </ul>
             </span>  
             {* 
            <span class="btn-group">                
                              
                            <select class=select2 chzn-done id="qt-pdf-template">
                              <option selected> Select template </option>
                              
                              <option value='rrs_afg'> RRS_Afghanistan_QT </option>
                              <option value='rrs_mon'> RRS_Mongolia_QT </option>
                              <option value='rrs_geo'> RRS_Georgia_QT </option>
                              <option value='rrs_rus'> RRS_Russia_QT </option>
                              <option value='rrs_kyr'> RRS_Kyrgyzstan_QT </option>
                              <option value='rrs_taj'> RRS_Tajikistan_QT </option>
                              <option value='rrs_pak'> RRS_Pakistan_QT </option>
                              <option value='rrs_tur'> RRS_Turkmenistan_QT </option>
                              <option value='rrs_uzb'> RRS_Uzbekistan_QT </option>
                              <option value='rrs_arm'> RRS_Armenia_QT </option>
                              <option value='rrs_aze'> RRS_Azerbaijan_QT </option>
                              <option value='rrs_ukr'> RRS_Ukraine_QT </option>
                              <option value='general_en'> General_QT_en </option>
                              <option value='general_ru'> General_QT_ru </option>                             
                              <option value='private_pro'> PRO Department </option> 
                            </select>   
                            
                                
                            </span> 
                            
                            <span class="btn-group">
                              <a class="btn" href="/pdf_docs/{$RECORD->getQTPdfName()}" target="_blank"> PDF  </a>
                            </span>*}
            {assign var=STARRED value=$RECORD->get('starred')}
            {if $MODULE_MODEL->isStarredEnabled()}
                <button class="btn btn-default markStar {if $STARRED} active {/if}" id="starToggle" style="width:100px;">
                    <div class='starredStatus' title="{vtranslate('LBL_STARRED', $MODULE)}">
                        <div class='unfollowMessage'>
                            <i class="fa fa-star-o"></i> &nbsp;{vtranslate('LBL_UNFOLLOW',$MODULE)}
                        </div>
                        <div class='followMessage'>
                            <i class="fa fa-star active"></i> &nbsp;{vtranslate('LBL_FOLLOWING',$MODULE)}
                        </div>
                    </div>
                    <div class='unstarredStatus' title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
                        {vtranslate('LBL_FOLLOW',$MODULE)}
                    </div>
                </button>
            {/if}
            {foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
                <button class="btn btn-default" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_BASIC_LINK->getLabel())}"
                        {if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
                            onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
                        {else}
                            onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
                        {/if}
                        {if $MODULE_NAME eq 'Documents' && $DETAIL_VIEW_BASIC_LINK->getLabel() eq 'LBL_VIEW_FILE'}
                            data-filelocationtype="{$DETAIL_VIEW_BASIC_LINK->get('filelocationtype')}" data-filename="{$DETAIL_VIEW_BASIC_LINK->get('filename')}"
                        {/if}>
                    {vtranslate($DETAIL_VIEW_BASIC_LINK->getLabel(), $MODULE_NAME)}
                </button>
            {/foreach}
            {if $DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0}
                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
                   {vtranslate('LBL_MORE', $MODULE_NAME)}&nbsp;&nbsp;<i class="caret"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    {foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
                        {if $DETAIL_VIEW_LINK->getLabel() eq ""} 
                            <li class="divider"></li>	
                            {else}
                            <li id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}">
                                {if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"} 
                                    <a href='{$DETAIL_VIEW_LINK->getUrl()}'>{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                                {else}
                                    <a href='{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}' >{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                                {/if}
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            {/if}
            </div>
            {if !{$NO_PAGINATION}}
            <div class="btn-group pull-right">
                <button class="btn btn-default " id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if} >
                    <i class="fa fa-chevron-left"></i>
                </button>
                <button class="btn btn-default  " id="detailViewNextRecordButton"{if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            {/if}        
        </div>
        <input type="hidden" name="record_id" value="{$RECORD->getId()}">
    </div>
{strip}
