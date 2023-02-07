{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
<div class="relatedContentsComments col-lg-12 col-md-12 col-sm-12 table-container">
    <div class="summaryWidgetContainer"><div class="widgetContainer_comments" data-url="module=Job&amp;view=Detail&amp;record={$PARENT_RECORD->getId()}&amp;mode=showRecentComments&amp;page=1" data-name="ModComments"><div class="widget_header"><input type="hidden" name="relatedModule" value=""><h3 class="display-inline-block">Comments</h3></div><div class="widget_contents">﻿<div class="commentContainer recentComments"><div class="commentTitle"><div class="addCommentBlock"><div class="row"><div class=" col-lg-12"><div class="commentTextArea "><textarea name="commentcontent" class="commentcontent form-control col-lg-12" placeholder="Post your comment here" rows="2"></textarea></div></div></div><div class="row"><div class="col-xs-6 pull-right paddingTop5 paddingLeft0"><div style="text-align: right;"><button class="btn btn-success btn-sm detailViewSaveComment" type="button" data-mode="add">Post</button></div></div><div class="col-xs-6 pull-left">
    <div class="fileUploadContainer text-left">
      <div class="MultiFile-wrap" id="ModComments_editView_fieldName_filename">
      <div class="fileUploadBtn btn btn-sm btn-primary"><span><i class="fa fa-laptop"></i> Attach Files</span>
        <input type="file" id="ModComments_editView_fieldName_filename" class="inputElement  multi   MultiFile-applied" maxlength="6" name="filename[]" value="" multiple=""></div>
        <div class="MultiFile-list" id="ModComments_editView_fieldName_filename_list"></div>
      </div>&nbsp;&nbsp;<span class="uploadFileSizeLimit fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="" data-original-title="Maximum upload size is 20 MB"><span class="maxUploadSize" data-value="20971520"></span></span><div class="uploadedFileDetails "><div class="uploadedFileSize"></div><div class="uploadedFileName"></div></div></div>
      <script>
        jQuery(document).ready(function() {
          var fileElements = jQuery('input[type="file"]',jQuery(this).data('fieldinfo') == 'file');
          fileElements.on('change',function(e) {
            var element = jQuery(this);
            var fileSize = e.target.files[0].size;
            var maxFileSize = element.closest('form').find('.maxUploadSize').data('value');
            if(fileSize > maxFileSize) {
              alert(app.vtranslate('JS_EXCEEDS_MAX_UPLOAD_SIZE'));
              element.val(null);
            }
          });
        });
      </script>
    </div>
  </div>
  </div>
  </div>
</div>
{/strip}