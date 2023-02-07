{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.1/js/all.min.js">


	<link href="layouts/vlayout/modules/CargoInsurance/resources/buttonLoader.css" rel="stylesheet">

{*	<script src="jquery-1.11.3.min.js"></script>*}

	<script src="layouts/vlayout/modules/CargoInsurance/resources/jquery.buttonLoader.js"></script>

	{assign var="MODULE_NAME" value=$MODULE_MODEL->get('name')}
	{assign var="WIS_REF_NUMBER" value=$WIS_REF}
	{assign var="REFERALL" value=$REFERAL}
	{assign var="DECLARATION_ID" value=$DECLARATION_ID}
	{assign var="CANCEL_SENT_S" value=$CANCEL_SENT}
	{assign var="INSURANCE_TYPE" value=$INSURANCE_TYPE}
	{assign var="CANBECANCELLED" value=$CAN_BE_CANCELLED}

	{*{$CANBECANCELLED|@var_dump}*}
	<input id="record" type="hidden" value="{$RECORD->getId()}" >
	<input id="user_id" type="hidden" value="{$smarty.session.authenticated_user_id}" />
	<input id="declaration_id" type="hidden" value="{$DECLARATION_ID}" />
	<div class="detailViewContainer">
		<div class="row-fluid detailViewTitle">
			<div class="{if $NO_PAGINATION} span12 {else} span10 {/if}">
				<div class="row-fluid">
					<div class="span5">
						<div class="row-fluid">
							{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
						</div>
					</div>
					<div id="display" style="color: green"></div>
					<div id="diserror" style="color: red"></div>
					<div id="refstatus" style="color: green"></div>


					<div class="span7">
						<div class="pull-right detailViewButtoncontainer">
							<div class="btn-toolbar">
                             <span class="btn-group">
                             

                            
                            <table>

                            <tr>

                            <td width="80">
								{if $MODULE_NAME == 'CargoInsurance'}
								  <a onclick="javascript:window.location='index.php?module=Job&relatedModule=CargoInsurance&view=Detail&record={$HISTORY_JOB_ID}&mode=showRelatedList&tab_label=Insurance';" type="reset" class="btn btn-info"><strong> Go Back </strong></a>
								{/if}
                            </td>
							   {if $INSURANCE_TYPE == 'WIS Insurance'}
                            		<td width="120"><a href="javascript:void(0);" id="postwis" class="btn btn-primary"><strong> Post to WIS </strong></a> </td>
							   {/if}
							<input id="record" type="hidden" value="{$RECORD->getId()}" >

                            <td width="120"><a target="_blank" href="index.php?module=CargoInsurance&view=Print&record={$RECORD->getId()}" class="btn btn-primary"><strong> Print Insurance </strong></a>
                           </td>
                            <td width="120"> <a target="_blank" href="index.php?module=CargoInsurance&view=Print&record={$RECORD->getId()}&type=certificate" class="btn btn-primary"><strong> Print Certificate </strong></a>
                           </td>
								{if $INSURANCE_TYPE == 'WIS Insurance'}
									{if $WIS_REF_NUMBER != null}
										<td width="120"> <a target="_blank" id="hidebutton" style="display: block;"
															href="include/CargoInsurance/printcertificate.php?record={$RECORD->getId()}&declaration_id={$DECLARATION_ID}"
															class="btn btn-primary"><strong> Print WIS Certificate </strong></a>
							   			</td>
									{/if}

									{if $REFERALL == 'yes' || $CANCEL_SENT_S == 'yes' }
									<td width="120"> <a {*target="_blank"*} id="check_referal" style="display: block;"
																			href="#{*include/CargoInsurance/webhookstatus.php?record={$RECORD->getId()}*}"
																			class="btn btn-primary"><strong> Check Referal </strong></a>
                           			</td>
									{/if}


									<input id="check_referal_hidden" type="hidden" value="{$REFERAL}">
									<div id="referal" style="color: red"></div>

									<input id="can_be_cancelled" type="hidden" value="{$CANBECANCELLED}">

									{if $WIS_REF_NUMBER != null}
									<td width="120"> <button type="button" class="btn btn-danger" id="cancelButton">Cancel Declaration</button> </td>
									{/if}

									<div id="form1" style="display:none;">
										<form>
											<div style="display:flex;  align-items: baseline; ">
												<b>Cancellation reason:</b>&nbsp;
												<input type="text" id="cancellationReason" placeholder="write reason">
												<button type="button" id="cancelSubmit" class="btn btn-warning">Submit</button>
											</div>
										</form>
									</div>
								{/if}

                            </tr>
                            </table>

						  <!-- <a target="_blank" href="/include/insurance_report?record={$RECORD->getId()}" class="btn btn-primary"><strong> Print </strong></a>
                          		-->				    
						   </span>
							<span class="btn-group"> 
							</span>							
							{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
							<span class="btn-group">
								<button class="btn" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_BASIC_LINK->getLabel())}"
									{if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
										onclick="window.location.href='{$DETAIL_VIEW_BASIC_LINK->getUrl()}'"
									{else}
										onclick={$DETAIL_VIEW_BASIC_LINK->getUrl()}
									{/if}>
									<strong>{vtranslate($DETAIL_VIEW_BASIC_LINK->getLabel(), $MODULE_NAME)}</strong>
								</button>
							</span>
							{/foreach}
							{if $DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0}
							<span class="btn-group">
								<button class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
									<strong>{vtranslate('LBL_MORE', $MODULE_NAME)}</strong>&nbsp;&nbsp;<i class="caret"></i>
								</button>
								<ul class="dropdown-menu pull-right">
									{foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
									<li id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}">
										<a href={$DETAIL_VIEW_LINK->getUrl()} >{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
									</li>
									{/foreach}
								</ul>
							</span>
								<div id="quotes" style="color: green"></div>

							{/if}
							
							
							</div>
						</div>
					</div>
				</div>
			</div>
			{if !{$NO_PAGINATION}}
				<div class="span2 detailViewPagingButton">
					<span class="btn-group pull-right">
						<button class="btn" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href='{$PREVIOUS_RECORD_URL}'" {/if}><i class="icon-chevron-left"></i></button>
						<button class="btn" id="detailViewNextRecordButton" {if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href='{$NEXT_RECORD_URL}'" {/if}><i class="icon-chevron-right"></i></button>
					</span>
				</div>
			{/if}
		</div>

		<div class="detailViewInfo row-fluid">
			<div class="{if $NO_PAGINATION} span12 {else} span10 {/if} details">
				<form id="detailView" data-name-fields='{ZEND_JSON::encode($MODULE_MODEL->getNameFields())}'>
					<div class="contents">


{/strip}