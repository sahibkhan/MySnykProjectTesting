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
{php}
if($_GET['module']=='OrgChart') {
            echo '<script type="text/javascript" src="https://code.jquery.com/jquery-3.1.0.min.js"></script>';
            echo '<script type="text/javascript" src="include/OrgChart/js/jquery.orgchart.js"></script>';
            echo '<script type="text/javascript" src="include/OrgChart/data.js"></script>';
            echo '<link rel="stylesheet" type="text/css" href="https://cdn.rawgit.com/FortAwesome/Font-Awesome/master/css/font-awesome.min.css" />';
            echo '<link rel="stylesheet" type="text/css" href="include/OrgChart/css/jquery.orgchart.css" />';
            echo '<link rel="stylesheet" type="text/css" href="include/OrgChart/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="include/OrgChart/style.css" />';
            echo '<script type="text/javascript" src="https://cdn.rawgit.com/stefanpenner/es6-promise/master/dist/es6-promise.auto.min.js"></script>';
            echo '<script type="text/javascript" src="https://cdn.rawgit.com/niklasvh/html2canvas/master/dist/html2canvas.min.js"></script>';
            echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.2/jspdf.debug.js"></script>';
            echo '<script type="text/javascript" src="include/OrgChart/scripts.js"></script>';
        }
        
    {/php}
{strip}

 
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/purl.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/select2/select2.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.class.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery-ui-1.11.3.custom/jquery-ui.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/todc/js/bootstrap.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/jstorage.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery-validation/jquery.validate.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.slimscroll.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/jquery.ba-outside-events.min.js')}"></script>
	<script type="text/javascript" src="{vresource_url('libraries/jquery/defunkt-jquery-pjax/jquery.pjax.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/multiplefileupload/jquery_MultiFile.js')}"></script>
    <script type="text/javascript" src="{vresource_url('resources/jquery.additions.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/bootstrap-notify/bootstrap-notify.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/websockets/reconnecting-websocket.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery-play-sound/jquery.playSound.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/malihu-custom-scrollbar/jquery.mousewheel.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/malihu-custom-scrollbar/jquery.mCustomScrollbar.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/autoComplete/jquery.textcomplete.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.qtip.custom/jquery.qtip.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/jquery-visibility.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/momentjs/moment.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/daterangepicker/moment.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/daterangepicker/jquery.daterangepicker.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.timeago.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/ckeditor/ckeditor.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/ckeditor/adapters/jquery.js')}"></script>
	<script type='text/javascript' src="{vresource_url('layouts/v7/lib/anchorme_js/anchorme.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Class.js')}"></script>
    <script type='text/javascript' src="{vresource_url('layouts/v7/resources/helper.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/resources/application.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Utils.js')}"></script>
    <script type='text/javascript' src="{vresource_url('layouts/v7/modules/Vtiger/resources/validation.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/bootbox/bootbox.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Base.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Vtiger.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Calendar/resources/TaskManagement.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Import/resources/Import.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Emails/resources/EmailPreview.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Base.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Google/resources/Settings.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/CkEditor.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Documents/resources/Documents.js')}"></script>
   
    {if ($MODULE=='Job' || $MODULE=='Fleettrip' || $MODULE=='WagonTrip')}
    <script type="text/javascript" src="{vresource_url('libraries/jqgrid/ui.multiselect.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jqgrid/grid.locale-en.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jqgrid/jquery.jqGrid.js')}"></script>
    {/if}
    {foreach key=index item=jsModel from=$SCRIPTS}
        <script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
    {/foreach}

    <script type="text/javascript" src="{vresource_url('layouts/v7/resources/v7_client_compat.js')}"></script>
    <!-- Added in the end since it should be after less file loaded -->
    <script type="text/javascript" src="{vresource_url('libraries/bootstrap/js/less.min.js')}"></script>
  

    <!-- Enable tracking pageload time -->	
{/strip}

