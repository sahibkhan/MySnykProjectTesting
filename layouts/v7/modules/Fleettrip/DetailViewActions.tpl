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
                                        <strong>Report</strong>&nbsp;&nbsp;<i class="caret"></i>
                                    </button>
                                    <ul class="dropdown-menu pull-right">
                                            <li id="{$MODULE_NAME}_detailView_moreAction_TRUCKJCR">
                                                <a href="index.php?module=Jobexpencereport&view=Print&record={$RECORD->getId()}&expense=Fleettrip" target="_blank">
                                                    Truck JCR
                                                </a>
                                            </li>
                                            
                                            <li id="{$MODULE_NAME}_detailView_moreAction_TRIPBUDGET">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}" target="_blank">
                                                    Print Trip Budget
                                                </a>
                                            </li>
                                            
                                            <li id="{$MODULE_NAME}_detailView_moreAction_ADVANCEREPORT">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=TripExpense" target="_blank">
                                                    Advance Report
                                                </a>
                                            </li>
                                            
                                            <li id="{$MODULE_NAME}_detailView_moreAction_TRIPLISTFUEL_ENG">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=TripListFuel&lang=eng"  target="_blank">
                                                    Trip List Fuel (ENG)
                                                </a>
                                            </li>

                                            <li id="{$MODULE_NAME}_detailView_moreAction_TRIPLISTFUEL_RU">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=TripListFuel&lang=ru"  target="_blank">
                                                    Trip List Fuel (RU)
                                                </a>
                                            </li>

                                            <li id="{$MODULE_NAME}_detailView_moreAction_TRIPLIST_LOCAL">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=TripListLocal"  target="_blank" download>
                                                    Trip List Local
                                                </a>
                                            </li>

                                            <li id="{$MODULE_NAME}_detailView_moreAction_TRIPLIST_INTERNATIONAL">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=TripListInternational"  target="_blank" download>
                                                    Trip List International
                                                </a>
                                            </li>

                                            <li id="{$MODULE_NAME}_detailView_moreAction_FUELDATA_ENG">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=FuelData&lang=eng"  target="_blank" download>
                                                    Refill Data (ENG)
                                                </a>
                                            </li>

                                            <li id="{$MODULE_NAME}_detailView_moreAction_FUELDATA_RU">
                                                <a href="index.php?module=Fleettrip&view=Print&record={$RECORD->getId()}&checklist=FuelData&lang=ru"  target="_blank" download>
                                                    Refill Data (RU)
                                                </a>
                                            </li>
                                                                        
                                    </ul>
                                </span>
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
