{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
	{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
		<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
	{/if}

	<div name='editContent'>
		{if $DUPLICATE_RECORDS}
			<div class="fieldBlockContainer duplicationMessageContainer">
				<div class="duplicationMessageHeader"><b>{vtranslate('LBL_DUPLICATES_DETECTED', $MODULE)}</b></div>
				<div>{getDuplicatesPreventionMessage($MODULE, $DUPLICATE_RECORDS)}</div>
			</div>
		{/if}
		 {if $STATUS_MESSAGE neq ''}
		 <div class="fieldBlockContainer duplicationMessageContainer">
				<div class="duplicationMessageHeader"><b>{$STATUS_MESSAGE}</b></div>				
			</div>
		 {/if}

		  {if $COST_DEVIATION_MESSAGE neq ''}
		   <div class="fieldBlockContainer duplicationMessageContainer">
				<div class="duplicationMessageHeader"><b> {$COST_DEVIATION_MESSAGE}</b></div>				
			</div>
		  {/if}	

		   {if $REVENUE_DEVIATION_MESSAGE neq ''}
		   <div class="fieldBlockContainer duplicationMessageContainer">
				<div class="duplicationMessageHeader"><b> {$REVENUE_DEVIATION_MESSAGE}</b></div>				
			</div>
		  {/if}	

		{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE name=blockIterator}
			
			{if $BLOCK_FIELDS|@count gt 0}
				<div class='fieldBlockContainer' data-block="{$BLOCK_LABEL}">
					<h4 class='fieldBlockHeader'>{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
					<hr>
					<table class="table table-borderless">
						<tr>
							{assign var=COUNTER value=0}
							{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
								{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
								{assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
								{assign var="refrenceListCount" value=count($refrenceList)}
								{if $FIELD_MODEL->isEditable() eq true}
									{if $FIELD_MODEL->get('uitype') eq "19"}
										{if $COUNTER eq '1'}
											<td></td><td></td></tr><tr>
											{assign var=COUNTER value=0}
										{/if}
									{/if}
									{if $COUNTER eq 2}
									</tr><tr>
										{assign var=COUNTER value=1}
									{else}
										{assign var=COUNTER value=$COUNTER+1}
									{/if}
									<td class="fieldLabel alignMiddle">
										{if $isReferenceField eq "reference"}
											{if $refrenceListCount > 1}
												{assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
												{assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
												{if !empty($REFERENCED_MODULE_STRUCTURE)}
													{assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCTURE->get('name')}
												{/if}
												<select style="width: 140px;" class="select2 referenceModulesList">
													{foreach key=index item=value from=$refrenceList}
														<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if}>{vtranslate($value, $value)}</option>
													{/foreach}
												</select>
											{else}
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
											{/if}
										{else if $FIELD_MODEL->get('uitype') eq "83"}
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) COUNTER=$COUNTER MODULE=$MODULE}
											{if $TAXCLASS_DETAILS}
												{assign 'taxCount' count($TAXCLASS_DETAILS)%2}
												{if $taxCount eq 0}
													{if $COUNTER eq 2}
														{assign var=COUNTER value=1}
													{else}
														{assign var=COUNTER value=2}
													{/if}
												{/if}
											{/if}
										{else}
											{if $MODULE eq 'Documents' && $FIELD_MODEL->get('label') eq 'File Name'}
												{assign var=FILE_LOCATION_TYPE_FIELD value=$RECORD_STRUCTURE['LBL_FILE_INFORMATION']['filelocationtype']}
												{if $FILE_LOCATION_TYPE_FIELD}
													{if $FILE_LOCATION_TYPE_FIELD->get('fieldvalue') eq 'E'}
														{vtranslate("LBL_FILE_URL", $MODULE)}&nbsp;<span class="redColor">*</span>
													{else}
														{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
													{/if}
												{else}
													{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
												{/if}
											{else}
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}
											{/if}
										{/if}
										&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
									</td>
									{if $FIELD_MODEL->get('uitype') neq '83'}
										<td class="fieldValue" {if $FIELD_MODEL->getFieldDataType() eq 'boolean'} style="width:25%" {/if} {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										</td>
									{/if}
								{/if}
                             
							{/foreach}
							{*If their are odd number of fields in edit then border top is missing so adding the check*}
							{if $COUNTER is odd}
								<td></td>
								<td></td>
							{/if}
						</tr>
					</table>
				</div>
			{/if}
			{if $BLOCK_FIELDS|@count eq 16}
                           <div class="fieldBlockContainer" data-block="{$BLOCK_LABEL}" style="max-width:100%">
                               <div class="relatedContents contents-bottomscroll">
						<div class="bottomscroll-div">

						<table id="tablemainid2" class="table table-bordered nowrap table-responsive">
                          <TBODY style="max-width:100%" class="adjust-width">
							<thead>
								
							<tr>
								<th width="24.5%" >Container Type</th>
								<th width="20%">Container Size</th>
								<th width="25%">Quantity</th>
								<th width="20%">FCL/LCL</th>
								<th  width="25%">Action</th>
                               
							</tr>
					</thead>
                    </TBODY>
						</table>
					</div>
						<TABLE id="dataTable"  class="table table-bordered nowrap table-responsive">
						<TBODY style="max-width:100%" class="adjust-width">
							<TR>
								<TD>
									<SELECT name="container_type[]" data-rule-required="true" aria-required>
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="Dry Container">Dry Container</OPTION>
										<OPTION value="Operating Reefer">Operating Reefer</OPTION>
										<OPTION value="Non-operating Reefer">Non-operating Reefer</OPTION>
									</SELECT>
								</TD>
								<TD>
									<SELECT name="container_size[]" data-rule-required="true" aria-required>
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="10">10</OPTION>
										<OPTION value="20">20</OPTION>
										<OPTION value="40">40</OPTION>
										<OPTION value="40 HQ">40 HQ</OPTION>
										<OPTION value="45">45</OPTION>
										<OPTION value="53">53</OPTION>
										<OPTION value="Other">Other</OPTION>
									</SELECT>
								</TD>	
								<TD>
								    <INPUT type="number" name="container_qty[]" id="container_qty" data-rule-required="true" value="0" aria-required/>
								</TD>
								<TD>
									<SELECT name="container_fcl_lcl[]" data-rule-required="true" aria-required>
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="FCL">FCL</OPTION>
										<OPTION value="LCL">LCL</OPTION>
									</SELECT>
								</TD>	
								<TD>	
										<INPUT type="button" value="Delete" onclick="deleteRow('dataTable')" />
								</TD>
							</TR>
							</TBODY>
						</TABLE>
			<INPUT type="button"   id="addingbutton2" value="Add +" onclick="addRow('dataTable')" />
		
					</div></div></div>
               {/if}
		{/foreach}
	</div>
{/strip}
