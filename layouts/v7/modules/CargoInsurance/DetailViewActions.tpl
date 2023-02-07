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
	{assign var="MODULE_NAME" value=$MODULE_MODEL->get('name')}
	{assign var="WIS_REF_NUMBER" value=$WIS_REF}
	{assign var="REFERALL" value=$REFERAL}
	{assign var="DECLARATION_ID" value=$DECLARATION_ID}
	{assign var="CANCEL_SENT_S" value=$CANCEL_SENT}
	{assign var="INSURANCE_TYPE" value=$INSURANCE_TYPE}
	{assign var="CANBECANCELLED" value=$CAN_BE_CANCELLED}
	{assign var="INSUREDCOMPANY" value=$INSURED_COMPANY}

	{*{$CANBECANCELLED|@var_dump}*}
	<input id="record" type="hidden" value="{$RECORD->getId()}" >
	<input id="user_id" type="hidden" value="{$smarty.session.authenticated_user_id}" />
	<input id="declaration_id" type="hidden" value="{$DECLARATION_ID}" />

	<input id="wis_ref_id" type="hidden" value="{$WIS_REF}" />

    <div class="col-lg-6 detailViewButtoncontainer">
        <div class="pull-right btn-toolbar">
      
			 
			referal : {$REFERALL} cancel sent : {$CANCEL_SENT_S} CanCancel : {$CANBECANCELLED}
		
			<div id="display" style="color: green"></div>
			<div id="diserror" style="color: red"></div>
			<div id="refstatus" style="color: green"></div>
	  
            <div class="btn-group">
				<span class="btn-group listViewMassActions">
					{if $INSURANCE_TYPE == 'WIS Insurance'}
						{if $WIS_REF_NUMBER != null}
							<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><strong>Print</strong>&nbsp;&nbsp;<i class="caret"></i></button>
							<ul class="dropdown-menu">
								<li><a href="index.php?module=CargoInsurance&view=Print&record={$RECORD->getId()}" target="_blank">Insurance</a></li>
								<li><a href="index.php?module=CargoInsurance&view=Print&record={$RECORD->getId()}&type=certificate" target="_blank">Certificate</a></li>
							</ul>
						{/if}
					{/if}
					</span>
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
			<br>
			<div class="btn-group">
				<span style="color:red;" id="disperror"></span>
				<table>
					<tr>
						<td width="80">
							{if $MODULE_NAME == 'CargoInsurance'}
								<a onclick="javascript:window.location='index.php?module=Job&relatedModule=CargoInsurance&view=Detail&record={$HISTORY_JOB_ID}&mode=showRelatedList&tab_label=Insurance';" type="reset" class="btn btn-info"><strong> Go Back </strong></a>
							{/if}
						</td>
						{if $INSURANCE_TYPE == 'WIS Insurance'}
                            <td width="120">
								<a href="javascript:void(0);" id="postwis" class="btn btn-primary"><strong> Post to WIS </strong></a> 
							</td>
						{/if}
						<input id="record" type="hidden" value="{$RECORD->getId()}" >
						
								{if $INSURANCE_TYPE == 'WIS Insurance'}
									{if $REFERALL == 'yes' || $CANCEL_SENT_S == 'yes' }
										<td width="120"> <a {*target="_blank"*} id="check_referal" style="display: block;"
											href="#{*include/CargoInsurance/webhookstatus.php?record={$RECORD->getId()}*}"
											class="btn btn-primary"><strong> Check Referal </strong></a>
										</td>
									{/if}


									<input id="check_referal_hidden" type="hidden" value="{$REFERAL}">
									<div id="referal" style="color: red"></div>

									<input id="can_be_cancelled" type="hidden" value="{$CANBECANCELLED}">
									
									{if $INSURANCE_TYPE == 'WIS Insurance'}
									{if $WIS_REF_NUMBER != null}
										{if $WIS_STATUS=="Booked"}
										<button class="btn btn-default">
											<a target="_blank" id="hidebutton" style="display: block;"						href="include/CargoInsurance/printcertificate.php?record={$RECORD->getId()}&declaration_id={$DECLARATION_ID}&insured_comp={$INSUREDCOMPANY}" ><strong> Print WIS Certificate </strong></a>
										</button>					
										{/if}
									{/if}
								{/if}
									
									
									{if $CANBECANCELLED=="1"}
									{if $CANCEL_SENT_S != "yes"}
										{if $CURRENT_USER_ID==767}
											{if $WIS_STATUS=="Booked"}	
												
												<td width="120"> 
													
													<button type="button" class="btn btn-danger" id="cancelButton">Cancel Declaration</button> 
												</td>
											{/if}
										{/if}
									{/if}
									{/if}
									
									<!-- cancel form -->
									<div id="wisCancelForm" class="modal" tabindex="-1" role="dialog">
									  <div class="modal-dialog" role="document">
										<div class="modal-content">
										  <div class="modal-header">
											<h5 class="modal-title">Cancellation Reason</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											  <span aria-hidden="true">&times;</span>
											</button>
										  </div>
										  <div class="modal-body">
											<form>
												<div style="display:flex;  align-items: baseline; flex-direction:column;">
													<textarea id="cancellationReason" rows="5" style="width:100%" placeholder="Cancelation Reason" required ></textarea>
													
													<button style="width:100%" type="button" id="cancelSubmit" class="btn btn-warning" data-dismiss="modal">Submit</button>
												</div>
											</form>
										  </div>
										  <div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
											<!-- <button type="button" class="btn btn-primary">Save changes</button> -->
										  </div>
										</div>
									  </div>
									</div>
									
									
									
									

									
								{/if}

                            </tr>
                            </table>
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
