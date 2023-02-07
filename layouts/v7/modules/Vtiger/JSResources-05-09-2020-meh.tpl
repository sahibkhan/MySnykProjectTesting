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

 
   {foreach key=index item=jsModel from=$FOOTER_SCRIPTS}
      <script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
	{/foreach}
   
   
    {foreach key=index item=jsModel from=$SCRIPTS}
        <script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
    {/foreach}

    <script type="text/javascript" src="{vresource_url('layouts/v7/resources/v7_client_compat.js')}"></script>
    <!-- Added in the end since it should be after less file loaded -->
    <script type="text/javascript" src="{vresource_url('libraries/bootstrap/js/less.min.js')}"></script>
  

    <!-- Enable tracking pageload time -->	
{/strip}

