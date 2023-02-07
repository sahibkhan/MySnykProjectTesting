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
              {assign var=userID value=Users_Record_Model::getCurrentUserModel()->get('id')}
              {if $send_status eq 'yes'}
              <button class="btn btn-success" onclick="send_first_approval({Users_Record_Model::getCurrentUserModel()->get('id')},{$RECORD->getId()},{$RECORD->get('proc_proctype')})">Send Approval</button>
                {/if}
                {if $sending_approvals[0]['who_approve_id'] eq $userID && $sending_approvals[0]['approval_status'] eq 'Pending'}
                <button class="btn btn-success" onclick="if(confirm('Please Confirm Approval!')) next_approval({Users_Record_Model::getCurrentUserModel()->get('id')},{$RECORD->getId()},{$RECORD->get('proc_proctype')},{$USDAMOUNT},1)" style="margin-right:5px !important;">Approve</button>
                <button class="btn btn-danger" onclick="$('#reject_reason_area').toggle(500,'linear');" style="margin-right:5px !important;">Reject</button>
                {/if}
				{if $cancel_status_checker eq 'Yes'}
				<button class="btn btn-danger" onclick="$('#reject_reason_area').toggle(500,'linear');" style="margin-right:5px !important;">Reject</button>
                {/if}
            {if $Show_Approvals eq 1}
            <span class="btn-group listViewMassActions">
                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><strong>Print</strong>&nbsp;&nbsp;<i class="caret"></i></button>
                <ul class="dropdown-menu">
                    <li><a href="index.php?module=Procurement&view=Print&record={$RECORD->getId()}" target="_blank">Print Record</a></li>
                </ul>
            </span>
            {/if}
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
        {if ($sending_approvals[0]['who_approve_id'] eq $userID && $sending_approvals[0]['approval_status'] eq 'Pending')}

                <div id="reject_reason_area" style="display:none;">    
                <br>                
                    <div style="border-radius: 10px;border-top-left-radius: 0px;border: solid 1px #dd4b39;margin-left: 85px;padding: 5px;margin-top: 15px;">
                      <textarea name="reject_reason" id="reject_reason" style="width: 350px; height: 55px;" placeholder="Enter rejection reason here..."></textarea> <br><button class="btn btn-default" onclick="next_approval({Users_Record_Model::getCurrentUserModel()->get('id')},{$RECORD->getId()},{$RECORD->get('proc_proctype')},{$USDAMOUNT},2)" style="margin-top: 5px !important;">Submit</button> <span style="display:none;color: #f30; font-weight: bold;" id="reject_error">Please enter reject reason to proceed</span>
                    </div>
                </div>
            {/if}
			{if $cancel_status_checker eq 'Yes'}

                <div id="reject_reason_area" style="display:none;">    
                <br>                
                    <div style="border-radius: 10px;border-top-left-radius: 0px;border: solid 1px #dd4b39;margin-left: 85px;padding: 5px;margin-top: 15px;">
                      <textarea name="reject_reason" id="reject_reason" style="width: 350px; height: 55px;" placeholder="Enter rejection reason here..."></textarea> <br><button class="btn btn-default" onclick="next_approval({Users_Record_Model::getCurrentUserModel()->get('id')},{$RECORD->getId()},{$RECORD->get('proc_proctype')},{$USDAMOUNT},3)" style="margin-top: 5px !important;">Submit</button> <span style="display:none;color: #f30; font-weight: bold;" id="reject_error">Please enter reject reason to proceed</span>
                    </div>
                </div>
            {/if}
        <input type="hidden" name="record_id" value="{$RECORD->getId()}">
    </div>
{strip}