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
			{if $BLOCK_LABEL eq 'Sea Shipments'}
                           <div class="fieldBlockContainer" data-block="{$BLOCK_LABEL}" style="max-width:100%">
                               <div class="relatedContents contents-bottomscroll">
						<div class="bottomscroll-div">

						<table id="tablemainid2" class="table table-bordered nowrap table-responsive">
                          <TBODY style="max-width:100%" class="adjust-width">
							<thead>
								
							<tr>
								<th width="25.5%" >Container Type</th>
								<th width="21%">Container Size</th>
								<th width="26%">Quantity</th>
								<th width="20.5%">FCL/LCL</th>
								<th  width="25%">Action</th>
                               
							</tr>
					</thead>
                    </TBODY>
						</table>
					</div>
					    {if $RECORD_ID neq ''}
					 
                             <TABLE id="dataTable"  class="table table-bordered  table-responsive">
						<TBODY style="max-width:100%" class="adjust-width">
                        
						{foreach key=FIELD_NAME item=FIELD_MODEL from=$container_details}
                       
							<TR>
								<TD  width="25.5%">
									<SELECT name="container_type[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="Dry Container" {if $FIELD_MODEL['container_type'] eq 'Dry Container'} selected {else}  {/if}>Dry Container</OPTION>
										<OPTION value="Operating Reefer" {if $FIELD_MODEL['container_type'] eq 'Operating Reefer'} selected {else} {/if}>Operating Reefer</OPTION>
										<OPTION value="Non-operating Reefer"  {if $FIELD_MODEL['container_type'] eq 'Non-operating Reefer'} selected {else} {/if}>Non-operating Reefer</OPTION>
									</SELECT>
								</TD>
							
								<TD width="21%">
									<SELECT name="container_size[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="10"  {if $FIELD_MODEL['container_size'] eq '10'} selected {else}  {/if}>10</OPTION>
										<OPTION value="20" {if $FIELD_MODEL['container_size'] eq '20'} selected {else}  {/if}>20</OPTION>
										<OPTION value="40" {if $FIELD_MODEL['container_size'] eq '40'} selected {else}  {/if}>40</OPTION>
										<OPTION value="40 HQ" {if $FIELD_MODEL['container_size'] eq '40 HQ'} selected {else}  {/if}>40 HQ</OPTION>
										<OPTION value="45" {if $FIELD_MODEL['container_size'] eq '45'} selected {else}  {/if}>45</OPTION>
										<OPTION value="53" {if $FIELD_MODEL['container_size'] eq '53'} selected {else}  {/if}>53</OPTION>
										<OPTION value="Other" {if $FIELD_MODEL['container_size'] eq 'Other'} selected {else}  {/if}>Other</OPTION>
									</SELECT>
								</TD>	
								<TD width="26%">
								      {if $FIELD_MODEL['container_qty'] neq '0'}
								        <INPUT type="text" name="container_qty[]" id="container_qty"  value="{$FIELD_MODEL['container_qty']}"/>
								        {else}
                                           <INPUT type="text" name="container_qty[]" id="container_qty"  value="0"/>

								        {/if}
								</TD>
								<TD width="20.5%">
									<SELECT name="container_fcl_lcl[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="FCL" {if $FIELD_MODEL['container_fcl_lcl'] eq 'FCL'} selected {else}  {/if} >FCL</OPTION>
										<OPTION value="LCL" {if $FIELD_MODEL['container_fcl_lcl'] eq 'LCL'} selected {else}  {/if}>LCL</OPTION>
									</SELECT>
								</TD>	
								<TD width="25%">	
								        
                                         <button type='button' class='delete' id='delete' data-id="{$FIELD_MODEL['id']}"><i class="fa fa-trash"></i></span></button>
                                             <INPUT type="hidden" name="contaierID[]" id="contaierID"  value="{$FIELD_MODEL['id']}" />
							            <INPUT type="hidden" name="jobid[]" id="jobid"  value="{$FIELD_MODEL['jobid']}" />
		

								</TD>

							</TR>
							{foreachelse}
							<TR>
								<TD width="25.5%">
									<SELECT name="container_type[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="Dry Container">Dry Container</OPTION>
										<OPTION value="Operating Reefer">Operating Reefer</OPTION>
										<OPTION value="Non-operating Reefer">Non-operating Reefer</OPTION>
									</SELECT>
								</TD>
							
								<TD width="21%">
									<SELECT name="container_size[]" >
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
								<TD width="26%">
								    <INPUT type="number" name="container_qty[]" id="container_qty" value="0" />
								</TD>
								<TD width="20.5%">
									<SELECT name="container_fcl_lcl[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="FCL">FCL</OPTION>
										<OPTION value="LCL">LCL</OPTION>
									</SELECT>
								</TD>	
								<TD width="25%">	
								        <button type='button' class='btnDelete' id='btnDelete'  onclick="deleteRow('dataTable')" ><i class="fa fa-trash"></i></span></button>
										
								</TD>
							</TR>

						{/foreach}

						 <INPUT type="hidden" name="countId" id="countId"  value="{$totalrecords}" />
							</TBODY>
						</TABLE>
					
		         	<INPUT type="button"   id="addingbutton2" value="Add +" onclick="addRow('dataTable')" />
					        {else}	
						<TABLE id="dataTable"  class="table table-bordered nowrap table-responsive">
						<TBODY style="max-width:100%" class="adjust-width">
							
							<TR>
								<TD  width="25.5%">
									<SELECT name="container_type[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="Dry Container">Dry Container</OPTION>
										<OPTION value="Operating Reefer">Operating Reefer</OPTION>
										<OPTION value="Non-operating Reefer">Non-operating Reefer</OPTION>
									</SELECT>
								</TD>
							
								<TD width="21%">
									<SELECT name="container_size[]" >
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
								<TD width="26%">
								    <INPUT type="number" name="container_qty[]" id="container_qty" value="0" />
								</TD>
								<TD width="20.5%">
									<SELECT name="container_fcl_lcl[]">
										<OPTION value="">Select an Option</OPTION>
										<OPTION value="FCL">FCL</OPTION>
										<OPTION value="LCL">LCL</OPTION>
									</SELECT>
								</TD>	
								<TD width="25%">	
								        <button type='button' class='btnDelete' id='btnDelete'  onclick="deleteRow('dataTable')" ><i class="fa fa-trash"></i></span></button>
										
								</TD>
							</TR>
							</TBODY>
						</TABLE>
						 <INPUT type="hidden" name="countId" id="countId"  value="" />

			

					   <INPUT type="hidden" name="contaierID" id="contaierID"  value="" />
		         	<INPUT type="button"   id="addingbutton2" value="Add +" onclick="addRow('dataTable')" />
		          {/if}
					</div></div></div>
               {/if}
		{/foreach}


		

	</div>
{/strip}
