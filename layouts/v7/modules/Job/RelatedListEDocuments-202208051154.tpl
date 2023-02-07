{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
{assign var=RELATED_MODULE_NAME value=$RELATED_MODULE->get('name')}


{if $TYPE eq 'False'}
      <div class="fieldBlockContainer duplicationMessageContainer">
        <div class="duplicationMessageHeader"><b>You can only upload files with extention ( jpg, jpeg, png, gif, pdf, xlsx, docx, zip, 7z ) !</br>
        For another extention permission, contact with gems team.
        </b></div>
      </div>
  {/if}


<div class="relatedContainer">
  {assign var=IS_RELATION_FIELD_ACTIVE value="{if $RELATION_FIELD}{$RELATION_FIELD->isActiveField()}{else}false{/if}"}
  <input type="hidden" name="currentPageNum" value="{$PAGING->getCurrentPage()}" />
  <input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE_NAME}" />
  <input type="hidden" value="{$ORDER_BY}" id="orderBy">
  <input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
  <input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
  <input type='hidden' value="{$PAGING->getPageLimit()}" id='pageLimit'>
  <input type='hidden' value="{$PAGING->get('page')}" id='pageNumber'>
  <input type="hidden" value="{$PAGING->isNextPageExists()}" id="nextPageExist"/>
  <input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
  <input type='hidden' value="{$TAB_LABEL}" id='tab_label' name='tab_label'>
  <input type='hidden' value="{$IS_RELATION_FIELD_ACTIVE}" id='isRelationFieldActive'>


  {include file="partials/RelatedListHeader.tpl"|vtemplate_path:$RELATED_MODULE_NAME}
  
  {if $MODULE eq 'Products' && $RELATED_MODULE_NAME eq 'Products' && $TAB_LABEL === 'Product Bundles' && $RELATED_LIST_LINKS}
  <div data-module="{$MODULE}" style = "margin-left:20px">
    {assign var=IS_VIEWABLE value=$PARENT_RECORD->isBundleViewable()}
    <input type="hidden" class="isShowBundles" value="{$IS_VIEWABLE}">
    <label class="showBundlesInInventory checkbox"><input type="checkbox" {if $IS_VIEWABLE}checked{/if} value="{$IS_VIEWABLE}">&nbsp;&nbsp;{vtranslate('LBL_SHOW_BUNDLE_IN_INVENTORY', $MODULE)}</label>
  </div>
  {/if}

  <form class="form-horizontal recordEditView" id="QuickCreate" name="QuickCreate" method="post" action="index.php" enctype='multipart/form-data'>
    <div class="relatedContents col-lg-12 col-md-12 col-sm-12 table-container">
      <div class="bottomscroll-div">
        <table id="listview-table" class="table listview-table listViewEntriesTable">
          <thead>
            <tr class="listViewHeaders">
              <th style="min-width:100px">
              </th>
              {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
              {if $HEADER_FIELD->get('column') eq 'name'}   {continue}{/if}
              {* hide time_start,time_end columns in the list as they are merged with with Start Date and End Date fields *}
              
              {if $HEADER_FIELD->get('column') eq 'upload_edocument_clone'}

              {else if $HEADER_FIELD->get('column') eq 'file_name'}

              {else if $HEADER_FIELD->get('column') eq 'path'}

              {else if $HEADER_FIELD->get('column') eq 'time_start' or $HEADER_FIELD->get('column') eq 'time_end'}
              <th class="nowrap" style="width:15px">
                {else}
              <th class="nowrap">
                {if $HEADER_FIELD->get('column') eq "access_count" or $HEADER_FIELD->get('column') eq "idlists"}
                <a href="javascript:void(0);" class="noSorting">{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE_NAME)}</a>
                {else}
                <a href="javascript:void(0);" class="listViewContentHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-fieldname="{$HEADER_FIELD->get('column')}">
                {if $COLUMN_NAME eq $HEADER_FIELD->get('column')}
                <i class="fa fa-sort {$FASORT_IMAGE}"></i>
                {else}
                <i class="fa fa-sort customsort"></i>
                {/if}
                &nbsp;
                {vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE_NAME)}
                &nbsp;{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}<img class="{$SORT_IMAGE}">{/if}&nbsp;
                </a>
                {if $COLUMN_NAME eq $HEADER_FIELD->get('column')}
                <a href="#" class="removeSorting"><i class="fa fa-remove"></i></a>
                {/if}
                {/if}
                {/if}
              </th>
              {/foreach}
            </tr>


            {*
            <tr class="searchRow">
              <th class="inline-search-btn">
                <button class="btn btn-success btn-sm" data-trigger="relatedListSearch">{vtranslate("LBL_SEARCH",$MODULE)}</button>
              </th>
              {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
              {if $HEADER_FIELD->get('column') eq 'name'}   {continue}{/if}
              <th>
                {if $HEADER_FIELD->get('column') eq 'time_start' or $HEADER_FIELD->get('column') eq 'time_end' or $HEADER_FIELD->getFieldDataType() eq 'reference'}
                {else}
                {assign var=FIELD_UI_TYPE_MODEL value=$HEADER_FIELD->getUITypeModel()}
                {include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$RELATED_MODULE_NAME) FIELD_MODEL= $HEADER_FIELD SEARCH_INFO=$SEARCH_DETAILS[$HEADER_FIELD->getName()] USER_MODEL=$USER_MODEL}
                <input type="hidden" class="operatorValue" value="{$SEARCH_DETAILS[$HEADER_FIELD->getName()]['comparator']}">
                {/if}
              </th>
              {/foreach}
            </tr>
            *}


          </thead>


         <tbody>

         {if $DOC_COUNT eq 0}
              {**{if {$TYPES_COUNT} > 0}   **}
              {foreach key=TYPE item=TYPE_MODEL from=$TYPES}
                <tr class="listViewEntries" id="2" name="everyrecord[]">
                  <td class="related-list-actions">&nbsp;&nbsp; {if $TYPE_MODEL['required_doc'] eq 1}
                      Mandatory <span class="redColor">*</span>{else}Optional{/if}</td>
                  <td id="document_name" class="fieldLabel col-lg-2">
                    <select class="select2 inputElement" id="dfields1" name="document_name[]" tabindex="-1" title="">
                      <option value="{$TYPE_MODEL['echecklistid']}" data-picklistvalue="{$TYPE_MODEL['echecklistid']}" selected>{$TYPE_MODEL['name']}</option>
                      
                      <input type="hidden" value="{$TYPE_MODEL['echecklistid']}" name="document_name[]">
                    </select>
                  </td>
                  <td id="upload_edocument" class="relatedListEntryValues" colspan="1">
                    <input id="EDocuments_editView_fieldName_upload_edocument" type="file" data-fieldname="upload_edocument" data-fieldtype="string" multiple="" class="inputElement multi max-6  MultiFile-applied" name="upload_edocument[{$TYPE_MODEL['echecklistid']}][]" value="" {if $TYPE_MODEL['required_doc'] eq 1}required{/if}>
                  </td>
                </tr>
              {/foreach}
            {/if}

          {if $DOC_COUNT > 0 }
            {foreach key=row_no item=doc from=$DOCS}   
             <tr></tr>
             <tr class="listViewEntries" data-id="4802585">
                
                <td class="related-list-actions">

                  {if $JOBSTATUS eq 'Completed' || $JOBSTATUS eq 'Returned for Additional Uploading'}
                    {if $USERID neq '405'}
                      <span class="actionImages">&nbsp;&nbsp;&nbsp;<a name="relationEdit" data-url="index.php?module=EDocuments&amp;view=Edit&amp;record={$doc.edocumentsid}">
                        <i class="fa fa-pencil" title="Edit"></i></a> &nbsp;&nbsp;
                      </span>
                    {/if}
                  {/if}

                </td>

                <td class="" title="{$doc.document_type}" data-field-type="ECheckList" nowrap="">
                  <span class="value textOverflowEllipsis">{$doc.document_type}</span>
                </td>
                
                <td class="" title="{$doc.document_name}" data-field-type="image" nowrap="">

                  {foreach  item=link from=$DOC_LINKS} 
                    {if $doc.document_name eq $link.document_name}
                      {if $link.file_name neq '' && $link.deleted eq '0'}
                        <span class="value textOverflowEllipsis">        
                            <a name="downloadfile" href="{$link.doc_path}{$link.upload_document_clone}" download="{$link.upload_document_clone}" target="_blank">
                              {$link.file_name}&nbsp;
                              <i title="Download file" class="fa fa-download  fa-lg"></i>
                            </a>
                        </span>



                        {if $JOBSTATUS eq 'Completed' || $JOBSTATUS eq 'Returned for Additional Uploading'}
                            {if $USERID neq '405'}
                              <span class="value textOverflowEllipsis">        
                                <a name="deletefile" onclick="return confirm('Are you sure you want to delete this Document?');" href="index.php?module=EDocuments&view=Edit&jobid={$link.job_id}&displayMode=overlay&returnmodule=Job&returnrelatedModuleName=EDocuments&returnview=Detail&deleterecord={$link.edocumentsrecordsid}&returnpage=1&returnmode=showRelatedList&returnrelationId=325&returntab_label=EDocuments">
                                  <i title="Delete file" class="fa fa-trash fa-lg" style="color:#e83131;"></i>
                                </a>&nbsp;
                              </span>&nbsp;|&nbsp;
                            {/if}
                          {/if}

                          {*
                      {else $link.file_name neq '' && $link.deleted eq 1}
                        <span class="textOverflowEllipsis">No Files Uploaded !</span>
                      {else}
                        <span class="textOverflowEllipsis">No Files Uploaded !</span>*}


                      {/if}
                    {/if}
                  {/foreach}
                </td>
             </tr>
             {/foreach}
          {/if}
        </tbody>

          <tfoot>
            <tr class="listViewEntries">
              <td  nowrap=""  class="relatedListEntryValues" align="right" colspan="3">                
                            
                <button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
              
                <input type="hidden" value="{$MODULE}" name="sourceModule">
                <input type="hidden" value="{$PARENT_RECORD->getId()}" name="sourceRecord">
                <input type="hidden" value="true" name="relationOperation">
                <input type="hidden" name="module" value="{$RELATED_MODULE->get('name')}">
                <input type="hidden" name="action" value="SaveAjax">
              </td>
  
            </tr>

          </tfoot>
        </table>
      </div>
    </div>
  </form>

    {if $DOC_COUNT > 0 }
    	{include file="RelatedListEDocumentsComments.tpl"|vtemplate_path:$MODULE}
    {/if}

  <script type="text/javascript">
    var related_uimeta = (function () {

      jQuery('#dfields1').prop('disabled', true);
      jQuery('#dfields2').prop('disabled', true);
      jQuery('#dfields3').prop('disabled', true);
      jQuery('#dfields4').prop('disabled', true);
      jQuery('#dfields5').prop('disabled', true);
      jQuery('#dfields6').prop('disabled', true);
      jQuery('#dfields7').prop('disabled', true);


      var fieldInfo = {$RELATED_FIELDS_INFO};
      return {
        field: {
          get: function (name, property) {
            if (name && property === undefined) {
              return fieldInfo[name];
            }
            if (name && property) {
              return fieldInfo[name][property]
            }
          },
          isMandatory: function (name) {
            if (fieldInfo[name]) {
              return fieldInfo[name].mandatory;
            }
            return false;
          },
          getType: function (name) {
            if (fieldInfo[name]) {
              return fieldInfo[name].type
            }
            return false;
          }
        }
      };
    })();
  </script>


</div>
{/strip}